<?php

namespace App\Observers;

use App\Models\Teknisi;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TeknisiObserver
{
    /**
     * Temporary storage for old values during update
     */
    private $tempOldValues = [];
    /**
     * Handle the Teknisi "creating" event.
     */
    public function creating(Teknisi $teknisi): void
    {
        // Set default values if not provided
        if (!$teknisi->status) {
            $teknisi->status = Teknisi::STATUS_ACTIVE;
        }

        if (!$teknisi->skill_level) {
            $teknisi->skill_level = Teknisi::SKILL_JUNIOR;
        }

        if (!$teknisi->max_concurrent_tickets) {
            $teknisi->max_concurrent_tickets = Teknisi::WORKLOAD_MODERATE;
        }

        Log::info("Creating new teknisi", [
            'nip' => $teknisi->nip,
            'name' => $teknisi->name,
            'skill_level' => $teknisi->skill_level,
        ]);
    }

    /**
     * Handle the Teknisi "created" event.
     */
    public function created(Teknisi $teknisi): void
    {
        try {
            // Notify admins about new teknisi
            $this->notifyAdminsOfNewTeknisi($teknisi);

            // Create welcome notification for teknisi
            $this->createWelcomeNotification($teknisi);

            // Initialize performance metrics
            $teknisi->updatePerformanceMetrics();

            // Log teknisi creation
            Log::info("Teknisi created successfully", [
                'nip' => $teknisi->nip,
                'name' => $teknisi->name,
                'skill_level' => $teknisi->skill_level,
            ]);

        } catch (\Exception $e) {
            Log::error("Error in TeknisiObserver::created", [
                'nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Teknisi "updating" event.
     */
    public function updating(Teknisi $teknisi): void
    {
        // Store old values for comparison in memory only (not as model attributes)
        // This prevents trying to save non-existent fields to database
        $this->tempOldValues = [
            'status' => $teknisi->getOriginal('status'),
            'skill_level' => $teknisi->getOriginal('skill_level'),
            'max_concurrent_tickets' => $teknisi->getOriginal('max_concurrent_tickets'),
            'department' => $teknisi->getOriginal('department'),
            'is_available' => $teknisi->getOriginal('is_available'),
        ];
    }

    /**
     * Handle the Teknisi "updated" event.
     */
    public function updated(Teknisi $teknisi): void
    {
        try {
            $changes = [];

            // Check status changes
            if ($teknisi->wasChanged('status')) {
                $this->handleStatusChange($teknisi, $changes);
            }

            // Check skill level changes
            if ($teknisi->wasChanged('skill_level')) {
                $this->handleSkillLevelChange($teknisi, $changes);
            }

            // Check workload capacity changes
            if ($teknisi->wasChanged('max_concurrent_tickets')) {
                $this->handleWorkloadCapacityChange($teknisi, $changes);
            }

            // Check department changes
            if ($teknisi->wasChanged('department')) {
                $this->handleDepartmentChange($teknisi, $changes);
            }

            // Check availability status changes
            if ($teknisi->wasChanged('is_available')) {
                $this->handleAvailabilityChange($teknisi, $changes);
            }

            // Check performance metrics updates
            if ($teknisi->wasChanged(['rating', 'ticket_count'])) {
                $this->handlePerformanceUpdate($teknisi, $changes);
            }

            // Check skills/certifications updates
            if ($teknisi->wasChanged(['skills', 'certifications'])) {
                $this->handleSkillsUpdate($teknisi, $changes);
            }

            // Check workload changes (calculated field)
            $currentWorkload = $teknisi->getCurrentWorkload();
            $oldWorkload = $teknisi->getOriginal('current_workload') ?? 0;

            if ($currentWorkload !== $oldWorkload) {
                $this->handleWorkloadChange($teknisi, $oldWorkload, $currentWorkload, $changes);
            }

            // Log significant changes
            if (!empty($changes)) {
                $this->logTeknisiChanges($teknisi, $changes);
            }

            Log::info("Teknisi updated", [
                'nip' => $teknisi->nip,
                'changes' => $changes,
            ]);

        } catch (\Exception $e) {
            Log::error("Error in TeknisiObserver::updated", [
                'nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        } finally {
            // Clear temporary values to prevent memory issues
            $this->tempOldValues = [];
        }
    }

    /**
     * Handle the Teknisi "deleting" event.
     */
    public function deleting(Teknisi $teknisi): void
    {
        try {
            // Check for active tickets before deletion
            $activeTickets = $teknisi->activeTickets()->count();

            if ($activeTickets > 0) {
                Log::warning("Attempting to delete teknisi with active tickets", [
                    'nip' => $teknisi->nip,
                    'active_tickets' => $activeTickets,
                ]);

                // Prevent deletion if teknisi has active tickets
                throw new \Exception("Cannot delete teknisi with {$activeTickets} active tickets");
            }

            Log::warning("Teknisi being deleted", [
                'nip' => $teknisi->nip,
                'name' => $teknisi->name,
                'total_tickets' => $teknisi->ticket_count ?? 0,
            ]);

        } catch (\Exception $e) {
            Log::error("Error in TeknisiObserver::deleting", [
                'nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw to prevent deletion
        }
    }

    /**
     * Handle the Teknisi "deleted" event.
     */
    public function deleted(Teknisi $teknisi): void
    {
        try {
            // Notify admins about teknisi deletion
            $this->notifyAdminsOfTeknisiDeletion($teknisi);

            // Log teknisi deletion
            Log::info("Teknisi deleted", [
                'nip' => $teknisi->nip,
                'name' => $teknisi->name,
                'total_tickets_handled' => $teknisi->ticket_count ?? 0,
            ]);

        } catch (\Exception $e) {
            Log::error("Error in TeknisiObserver::deleted", [
                'nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle status change
     */
    private function handleStatusChange(Teknisi $teknisi, array &$changes): void
    {
        $oldStatus = $this->tempOldValues['status'] ?? null;
        $newStatus = $teknisi->status;

        $changes[] = [
            'type' => 'status_change',
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
        ];

        // Create notification based on status change
        if ($oldStatus !== $newStatus) {
            $this->notifyStatusChange($teknisi, $oldStatus, $newStatus);
        }

        // Handle specific status transitions
        switch ($newStatus) {
            case Teknisi::STATUS_ACTIVE:
                $this->handleTeknisiActivated($teknisi);
                break;
            case Teknisi::STATUS_INACTIVE:
                $this->handleTeknisiDeactivated($teknisi);
                break;
            case Teknisi::STATUS_BUSY:
                $this->handleTeknisiBusy($teknisi);
                break;
        }
    }

    /**
     * Handle skill level change
     */
    private function handleSkillLevelChange(Teknisi $teknisi, array &$changes): void
    {
        $oldSkillLevel = $this->tempOldValues['skill_level'] ?? null;
        $newSkillLevel = $teknisi->skill_level;

        $changes[] = [
            'type' => 'skill_level_change',
            'old_value' => $oldSkillLevel,
            'new_value' => $newSkillLevel,
        ];

        // Notify skill level promotion
        if ($oldSkillLevel !== $newSkillLevel) {
            $this->notifySkillLevelChange($teknisi, $oldSkillLevel, $newSkillLevel);
        }
    }

    /**
     * Handle workload capacity change
     */
    private function handleWorkloadCapacityChange(Teknisi $teknisi, array &$changes): void
    {
        $oldCapacity = $this->tempOldValues['max_concurrent_tickets'] ?? null;
        $newCapacity = $teknisi->max_concurrent_tickets;

        $changes[] = [
            'type' => 'workload_capacity_change',
            'old_value' => $oldCapacity,
            'new_value' => $newCapacity,
        ];

        // Notify capacity change
        $this->notifyWorkloadCapacityChange($teknisi, $oldCapacity, $newCapacity);
    }

    /**
     * Handle department change
     */
    private function handleDepartmentChange(Teknisi $teknisi, array &$changes): void
    {
        $oldDepartment = $this->tempOldValues['department'] ?? null;
        $newDepartment = $teknisi->department;

        $changes[] = [
            'type' => 'department_change',
            'old_value' => $oldDepartment,
            'new_value' => $newDepartment,
        ];

        // Notify department change
        $this->notifyDepartmentChange($teknisi, $oldDepartment, $newDepartment);
    }

    /**
     * Handle availability change
     */
    private function handleAvailabilityChange(Teknisi $teknisi, array &$changes): void
    {
        $oldAvailability = $this->tempOldValues['is_available'] ?? null;
        $newAvailability = $teknisi->is_available;

        $changes[] = [
            'type' => 'availability_change',
            'old_value' => $oldAvailability,
            'new_value' => $newAvailability,
        ];

        // Notify availability change
        $this->notifyAvailabilityChange($teknisi, $oldAvailability, $newAvailability);
    }

    /**
     * Handle performance update
     */
    private function handlePerformanceUpdate(Teknisi $teknisi, array &$changes): void
    {
        $changes[] = [
            'type' => 'performance_update',
            'old_value' => null,
            'new_value' => 'Performance metrics updated',
        ];

        // Check for performance milestones
        $this->checkPerformanceMilestones($teknisi);
    }

    /**
     * Handle skills update
     */
    private function handleSkillsUpdate(Teknisi $teknisi, array &$changes): void
    {
        $changes[] = [
            'type' => 'skills_update',
            'old_value' => null,
            'new_value' => 'Skills or certifications updated',
        ];

        // Notify skills update
        $this->notifySkillsUpdate($teknisi);
    }

    /**
     * Handle workload change
     */
    private function handleWorkloadChange(Teknisi $teknisi, int $oldWorkload, int $currentWorkload, array &$changes): void
    {
        $changes[] = [
            'type' => 'workload_change',
            'old_value' => $oldWorkload,
            'new_value' => $currentWorkload,
        ];

        // Check workload thresholds
        $this->checkWorkloadThresholds($teknisi, $currentWorkload);
    }

    /**
     * Handle teknisi activated
     */
    private function handleTeknisiActivated(Teknisi $teknisi): void
    {
        // Update workload score
        $teknisi->updateWorkloadScore();

        // Create activation notification
        $this->createCustomNotification(
            $teknisi,
            'Account Activated',
            'Your teknisi account has been activated. You can now receive ticket assignments.',
            Notification::PRIORITY_HIGH,
            '/teknisi/dashboard',
            [
                'activation_date' => Carbon::now(),
                'account_status' => 'active',
                'current_workload' => $teknisi->getCurrentWorkload(),
            ]
        );
    }

    /**
     * Handle teknisi deactivated
     */
    private function handleTeknisiDeactivated(Teknisi $teknisi): void
    {
        // Notify deactivation
        $this->createCustomNotification(
            $teknisi,
            'Account Deactivated',
            'Your teknisi account has been deactivated. You will no longer receive new ticket assignments.',
            Notification::PRIORITY_HIGH,
            '/login',
            [
                'deactivation_date' => Carbon::now(),
                'account_status' => 'inactive',
            ]
        );
    }

    /**
     * Handle teknisi busy status
     */
    private function handleTeknisiBusy(Teknisi $teknisi): void
    {
        // Notify when teknisi becomes busy
        $this->createCustomNotification(
            $teknisi,
            'High Workload Detected',
            'You are currently at high workload capacity. Consider completing some tickets before accepting new ones.',
            Notification::PRIORITY_MEDIUM,
            '/teknisi/dashboard',
            [
                'current_workload' => $teknisi->getCurrentWorkload(),
                'max_capacity' => $teknisi->max_concurrent_tickets,
                'workload_percentage' => $teknisi->getWorkloadPercentage(),
            ]
        );
    }

    /**
     * Log teknisi changes
     */
    private function logTeknisiChanges(Teknisi $teknisi, array $changes): void
    {
        foreach ($changes as $change) {
            Log::info("Teknisi change detected", [
                'nip' => $teknisi->nip,
                'change_type' => $change['type'],
                'old_value' => $change['old_value'],
                'new_value' => $change['new_value'],
            ]);
        }
    }

    // ==================== NOTIFICATION METHODS ====================

    /**
     * Notify admins of new teknisi
     */
    private function notifyAdminsOfNewTeknisi(Teknisi $teknisi): void
    {
        try {
            // Notify admin helpdesks
            $adminHelpdesks = AdminHelpdesk::active()->get();
            foreach ($adminHelpdesks as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'New Teknisi Added',
                    "New teknisi {$teknisi->name} ({$teknisi->nip}) has been added to the system",
                    Notification::PRIORITY_MEDIUM,
                    '/admin/teknisi',
                    [
                        'teknisi_nip' => $teknisi->nip,
                        'teknisi_name' => $teknisi->name,
                        'skill_level' => $teknisi->skill_level,
                        'department' => $teknisi->department,
                        'max_capacity' => $teknisi->max_concurrent_tickets,
                    ]
                );
            }

            Log::info("New teknisi notifications sent to admins", [
                'teknisi_nip' => $teknisi->nip,
                'admin_count' => $adminHelpdesks->count(),
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending new teknisi notifications to admins", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create welcome notification for teknisi
     */
    private function createWelcomeNotification(Teknisi $teknisi): void
    {
        try {
            $this->createCustomNotification(
                $teknisi,
                'Welcome to Teknisi Portal',
                "Welcome {$teknisi->name}! Your teknisi account has been created. You can now manage and resolve support tickets.",
                Notification::PRIORITY_HIGH,
                '/teknisi/dashboard',
                [
                    'teknisi_nip' => $teknisi->nip,
                    'welcome_date' => Carbon::now(),
                    'max_concurrent_tickets' => $teknisi->max_concurrent_tickets,
                    'getting_started_url' => '/teknisi/help',
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error creating welcome notification for teknisi", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify status change
     */
    private function notifyStatusChange(Teknisi $teknisi, ?string $oldStatus, string $newStatus): void
    {
        try {
            // Notify admins of status change
            $adminHelpdesks = AdminHelpdesk::active()->get();
            foreach ($adminHelpdesks as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Teknisi Status Changed',
                    "Teknisi {$teknisi->name} ({$teknisi->nip}) status changed from {$oldStatus} to {$newStatus}",
                    Notification::PRIORITY_MEDIUM,
                    '/admin/teknisi',
                    [
                        'teknisi_nip' => $teknisi->nip,
                        'teknisi_name' => $teknisi->name,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending teknisi status change notifications", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify skill level change
     */
    private function notifySkillLevelChange(Teknisi $teknisi, string $oldSkillLevel, string $newSkillLevel): void
    {
        try {
            // Notify teknisi of promotion
            $this->createCustomNotification(
                $teknisi,
                'Skill Level Updated',
                "Congratulations! Your skill level has been updated from {$oldSkillLevel} to {$newSkillLevel}",
                Notification::PRIORITY_HIGH,
                '/teknisi/profile',
                [
                    'old_skill_level' => $oldSkillLevel,
                    'new_skill_level' => $newSkillLevel,
                    'updated_at' => Carbon::now(),
                ]
            );

            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            foreach ($adminHelpdesks as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Teknisi Skill Level Updated',
                    "Teknisi {$teknisi->name} skill level changed from {$oldSkillLevel} to {$newSkillLevel}",
                    Notification::PRIORITY_MEDIUM,
                    '/admin/teknisi',
                    [
                        'teknisi_nip' => $teknisi->nip,
                        'teknisi_name' => $teknisi->name,
                        'old_skill_level' => $oldSkillLevel,
                        'new_skill_level' => $newSkillLevel,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending skill level change notifications", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify workload capacity change
     */
    private function notifyWorkloadCapacityChange(Teknisi $teknisi, ?int $oldCapacity, int $newCapacity): void
    {
        try {
            // Notify teknisi of capacity change
            $this->createCustomNotification(
                $teknisi,
                'Workload Capacity Updated',
                "Your maximum concurrent ticket capacity has been updated from {$oldCapacity} to {$newCapacity}",
                Notification::PRIORITY_MEDIUM,
                '/teknisi/profile',
                [
                    'old_capacity' => $oldCapacity,
                    'new_capacity' => $newCapacity,
                    'updated_at' => Carbon::now(),
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error sending workload capacity change notification", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify department change
     */
    private function notifyDepartmentChange(Teknisi $teknisi, ?string $oldDepartment, string $newDepartment): void
    {
        try {
            $this->createCustomNotification(
                $teknisi,
                'Department Updated',
                "Your department has been updated from {$oldDepartment} to {$newDepartment}",
                Notification::PRIORITY_MEDIUM,
                '/teknisi/profile',
                [
                    'old_department' => $oldDepartment,
                    'new_department' => $newDepartment,
                    'updated_at' => Carbon::now(),
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error sending department change notification", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify availability change
     */
    private function notifyAvailabilityChange(Teknisi $teknisi, ?bool $oldAvailability, bool $newAvailability): void
    {
        try {
            if ($newAvailability) {
                $this->createCustomNotification(
                    $teknisi,
                    'You Are Now Available',
                    'You are now marked as available for new ticket assignments',
                    Notification::PRIORITY_MEDIUM,
                    '/teknisi/dashboard',
                    [
                        'availability_status' => 'available',
                        'current_workload' => $teknisi->getCurrentWorkload(),
                        'updated_at' => Carbon::now(),
                    ]
                );
            } else {
                $this->createCustomNotification(
                    $teknisi,
                    'You Are Now Busy',
                    'You are now marked as busy and will not receive new ticket assignments until your workload decreases',
                    Notification::PRIORITY_HIGH,
                    '/teknisi/dashboard',
                    [
                        'availability_status' => 'busy',
                        'current_workload' => $teknisi->getCurrentWorkload(),
                        'max_capacity' => $teknisi->max_concurrent_tickets,
                        'updated_at' => Carbon::now(),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending availability change notification", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify skills update
     */
    private function notifySkillsUpdate(Teknisi $teknisi): void
    {
        try {
            $this->createCustomNotification(
                $teknisi,
                'Skills & Certifications Updated',
                'Your skills and certifications profile has been updated successfully',
                Notification::PRIORITY_LOW,
                '/teknisi/profile',
                [
                    'skills_count' => count($teknisi->skills ?? []),
                    'certifications_count' => count($teknisi->certifications ?? []),
                    'updated_at' => Carbon::now(),
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error sending skills update notification", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify admins of teknisi deletion
     */
    private function notifyAdminsOfTeknisiDeletion(Teknisi $teknisi): void
    {
        try {
            $adminHelpdesks = AdminHelpdesk::active()->get();
            foreach ($adminHelpdesks as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Teknisi Removed',
                    "Teknisi {$teknisi->name} ({$teknisi->nip}) has been removed from the system",
                    Notification::PRIORITY_HIGH,
                    '/admin/teknisi',
                    [
                        'teknisi_nip' => $teknisi->nip,
                        'teknisi_name' => $teknisi->name,
                        'total_tickets_handled' => $teknisi->ticket_count ?? 0,
                        'deleted_at' => Carbon::now(),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending teknisi deletion notifications", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create custom notification
     */
    private function createCustomNotification($notifiable, string $title, string $message, string $priority, string $actionUrl, array $data = []): void
    {
        Notification::create([
            'type' => Notification::TYPE_SYSTEM_ANNOUNCEMENT,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->getKey(),
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'channel' => Notification::CHANNEL_DATABASE,
            'status' => Notification::STATUS_PENDING,
            'action_url' => $actionUrl,
            'data' => $data,
        ]);
    }

    // ==================== WORKLOAD MANAGEMENT ====================

    /**
     * Check workload thresholds
     */
    private function checkWorkloadThresholds(Teknisi $teknisi, int $currentWorkload): void
    {
        try {
            $maxCapacity = $teknisi->max_concurrent_tickets ?? Teknisi::WORKLOAD_MODERATE;
            $workloadPercentage = $teknisi->getWorkloadPercentage();

            // Check for overload (100%+ capacity)
            if ($workloadPercentage >= 100) {
                $this->handleWorkloadOverload($teknisi, $currentWorkload, $maxCapacity);
            }
            // Check for heavy workload (80%+ capacity)
            elseif ($workloadPercentage >= 80) {
                $this->handleHeavyWorkload($teknisi, $currentWorkload, $maxCapacity);
            }
            // Check for light workload (below 50% capacity)
            elseif ($workloadPercentage < 50 && $currentWorkload > 0) {
                $this->handleLightWorkload($teknisi, $currentWorkload, $maxCapacity);
            }

        } catch (\Exception $e) {
            Log::error("Error checking workload thresholds", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle workload overload
     */
    private function handleWorkloadOverload(Teknisi $teknisi, int $currentWorkload, int $maxCapacity): void
    {
        try {
            // Notify teknisi
            $this->createCustomNotification(
                $teknisi,
                'Workload Overload',
                "You are currently overloaded with {$currentWorkload} tickets (capacity: {$maxCapacity}). Consider reassigning some tickets.",
                Notification::PRIORITY_URGENT,
                '/teknisi/dashboard',
                [
                    'current_workload' => $currentWorkload,
                    'max_capacity' => $maxCapacity,
                    'overload_percentage' => $teknisi->getWorkloadPercentage(),
                ]
            );

            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            foreach ($adminHelpdesks as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Teknisi Overloaded',
                    "Teknisi {$teknisi->name} is overloaded with {$currentWorkload} tickets",
                    Notification::PRIORITY_HIGH,
                    '/admin/teknisi',
                    [
                        'teknisi_nip' => $teknisi->nip,
                        'teknisi_name' => $teknisi->name,
                        'current_workload' => $currentWorkload,
                        'max_capacity' => $maxCapacity,
                    ]
                );
            }

            Log::warning("Teknisi workload overload detected", [
                'teknisi_nip' => $teknisi->nip,
                'current_workload' => $currentWorkload,
                'max_capacity' => $maxCapacity,
            ]);

        } catch (\Exception $e) {
            Log::error("Error handling workload overload", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle heavy workload
     */
    private function handleHeavyWorkload(Teknisi $teknisi, int $currentWorkload, int $maxCapacity): void
    {
        try {
            // Notify teknisi
            $this->createCustomNotification(
                $teknisi,
                'Heavy Workload',
                "You currently have {$currentWorkload} tickets assigned. Monitor your workload to avoid overload.",
                Notification::PRIORITY_MEDIUM,
                '/teknisi/dashboard',
                [
                    'current_workload' => $currentWorkload,
                    'max_capacity' => $maxCapacity,
                    'workload_percentage' => $teknisi->getWorkloadPercentage(),
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error handling heavy workload", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle light workload
     */
    private function handleLightWorkload(Teknisi $teknisi, int $currentWorkload, int $maxCapacity): void
    {
        try {
            // Notify teknisi of availability for more work
            $this->createCustomNotification(
                $teknisi,
                'Available for More Tickets',
                "You have capacity for {max($maxCapacity - $currentWorkload, 0)} more tickets. You may receive new assignments soon.",
                Notification::PRIORITY_LOW,
                '/teknisi/dashboard',
                [
                    'current_workload' => $currentWorkload,
                    'max_capacity' => $maxCapacity,
                    'available_capacity' => $teknisi->getAvailableCapacity(),
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error handling light workload", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ==================== PERFORMANCE TRACKING ====================

    /**
     * Check performance milestones
     */
    private function checkPerformanceMilestones(Teknisi $teknisi): void
    {
        try {
            $ticketCount = $teknisi->ticket_count ?? 0;
            $rating = $teknisi->rating;

            // Check ticket count milestones
            $milestones = [10, 25, 50, 100, 250, 500, 1000];
            foreach ($milestones as $milestone) {
                if ($ticketCount === $milestone) {
                    $this->notifyTicketMilestone($teknisi, $milestone);
                    break;
                }
            }

            // Check rating milestones
            if ($rating && $rating >= 4.5) {
                $this->notifyHighRating($teknisi, $rating);
            }

        } catch (\Exception $e) {
            Log::error("Error checking performance milestones", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify ticket milestone
     */
    private function notifyTicketMilestone(Teknisi $teknisi, int $milestone): void
    {
        try {
            $this->createCustomNotification(
                $teknisi,
                'Ticket Milestone Reached!',
                "Congratulations! You have successfully handled {$milestone} tickets!",
                Notification::PRIORITY_HIGH,
                '/teknisi/dashboard',
                [
                    'milestone' => $milestone,
                    'total_tickets' => $teknisi->ticket_count,
                    'current_rating' => $teknisi->rating,
                    'achieved_at' => Carbon::now(),
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error sending ticket milestone notification", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify high rating
     */
    private function notifyHighRating(Teknisi $teknisi, float $rating): void
    {
        try {
            $this->createCustomNotification(
                $teknisi,
                'Excellent Rating Achieved!',
                "Congratulations! You have achieved a rating of {$rating}/5.0!",
                Notification::PRIORITY_HIGH,
                '/teknisi/profile',
                [
                    'current_rating' => $rating,
                    'total_tickets' => $teknisi->ticket_count,
                    'achieved_at' => Carbon::now(),
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error sending high rating notification", [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }
}