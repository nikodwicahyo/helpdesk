<?php

namespace App\Observers;

use App\Models\Aplikasi;
use App\Models\Notification;
use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AplikasiObserver
{
    /**
     * Handle the Aplikasi "creating" event.
     */
    public function creating(Aplikasi $aplikasi): void
    {
        // Set default values if not provided
        if (!$aplikasi->status) {
            $aplikasi->status = Aplikasi::STATUS_ACTIVE;
        }

        if (!$aplikasi->criticality) {
            $aplikasi->criticality = Aplikasi::CRITICALITY_MEDIUM;
        }

        if (!$aplikasi->category) {
            $aplikasi->category = Aplikasi::CATEGORY_WEB;
        }

        Log::info("Creating new aplikasi", [
            'name' => $aplikasi->name,
            'code' => $aplikasi->code,
            'status' => $aplikasi->status,
        ]);
    }

    /**
     * Handle the Aplikasi "created" event.
     */
    public function created(Aplikasi $aplikasi): void
    {
        try {
            // Notify admins about new aplikasi
            $this->notifyAdminsOfNewAplikasi($aplikasi);

            // Create system notification for aplikasi creation
            $this->createSystemNotification($aplikasi);

            // Initialize health metrics
            $aplikasi->updateHealthMetrics([
                'health_status' => 'good',
                'uptime_percentage' => 100.0,
                'response_time_avg' => 0,
                'error_rate' => 0.0,
            ]);

            // Log aplikasi creation
            Log::info("Aplikasi created successfully", [
                'id' => $aplikasi->id,
                'name' => $aplikasi->name,
                'code' => $aplikasi->code,
            ]);

        } catch (\Exception $e) {
            Log::error("Error in AplikasiObserver::created", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Aplikasi "updating" event.
     */
    public function updating(Aplikasi $aplikasi): void
    {
        // Store old values for comparison using session to avoid database conflicts
        session(['aplikasi_old_values' => [
            'status' => $aplikasi->getOriginal('status'),
            'criticality' => $aplikasi->getOriginal('criticality'),
            'health_status' => $aplikasi->getOriginal('health_status'),
            'is_maintenance_mode' => $aplikasi->getOriginal('is_maintenance_mode'),
            'current_version' => $aplikasi->getOriginal('current_version'),
        ]]);
    }

    /**
     * Handle the Aplikasi "updated" event.
     */
    public function updated(Aplikasi $aplikasi): void
    {
        try {
            $changes = [];

            // Check status changes
            if ($aplikasi->wasChanged('status')) {
                $this->handleStatusChange($aplikasi, $changes);
            }

            // Check criticality changes
            if ($aplikasi->wasChanged('criticality')) {
                $this->handleCriticalityChange($aplikasi, $changes);
            }

            // Check health status changes
            if ($aplikasi->wasChanged('health_status')) {
                $this->handleHealthStatusChange($aplikasi, $changes);
            }

            // Check maintenance mode changes
            if ($aplikasi->wasChanged('is_maintenance_mode')) {
                $this->handleMaintenanceModeChange($aplikasi, $changes);
            }

            // Check version changes
            if ($aplikasi->wasChanged('current_version')) {
                $this->handleVersionChange($aplikasi, $changes);
            }

            // Check health metrics changes
            if ($aplikasi->wasChanged(['uptime_percentage', 'response_time_avg', 'error_rate'])) {
                $this->handleHealthMetricsChange($aplikasi, $changes);
            }

            // Check capacity changes
            if ($aplikasi->wasChanged('current_users')) {
                $this->handleCapacityChange($aplikasi, $changes);
            }

            // Check contract/license expiry changes
            if ($aplikasi->wasChanged(['vendor_contract_expiry', 'license_expiry'])) {
                $this->handleContractLicenseChange($aplikasi, $changes);
            }

            // Log significant changes
            if (!empty($changes)) {
                $this->logAplikasiChanges($aplikasi, $changes);
            }

            Log::info("Aplikasi updated", [
                'aplikasi_id' => $aplikasi->id,
                'changes' => $changes,
            ]);

            // Clean up session after processing
            session()->forget('aplikasi_old_values');

        } catch (\Exception $e) {
            Log::error("Error in AplikasiObserver::updated", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);

            // Clean up session even on error
            session()->forget('aplikasi_old_values');
        }
    }

    /**
     * Handle the Aplikasi "deleting" event.
     */
    public function deleting(Aplikasi $aplikasi): void
    {
        try {
            // Check for active tickets before deletion
            $activeTickets = $aplikasi->tickets()->active()->count();

            if ($activeTickets > 0) {
                Log::warning("Attempting to delete aplikasi with active tickets", [
                    'aplikasi_id' => $aplikasi->id,
                    'active_tickets' => $activeTickets,
                ]);

                // Prevent deletion if aplikasi has active tickets
                throw new \Exception("Cannot delete aplikasi with {$activeTickets} active tickets");
            }

            Log::warning("Aplikasi being deleted", [
                'aplikasi_id' => $aplikasi->id,
                'name' => $aplikasi->name,
                'total_tickets' => $aplikasi->getTotalTicketCount(),
            ]);

        } catch (\Exception $e) {
            Log::error("Error in AplikasiObserver::deleting", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw to prevent deletion
        }
    }

    /**
     * Handle the Aplikasi "deleted" event.
     */
    public function deleted(Aplikasi $aplikasi): void
    {
        try {
            // Notify admins about aplikasi deletion
            $this->notifyAdminsOfAplikasiDeletion($aplikasi);

            // Log aplikasi deletion
            Log::info("Aplikasi deleted", [
                'aplikasi_id' => $aplikasi->id,
                'name' => $aplikasi->name,
                'total_tickets' => $aplikasi->getTotalTicketCount(),
            ]);

        } catch (\Exception $e) {
            Log::error("Error in AplikasiObserver::deleted", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle status change
     */
    private function handleStatusChange(Aplikasi $aplikasi, array &$changes): void
    {
        $oldValues = session('aplikasi_old_values', []);
        $oldStatus = $oldValues['status'] ?? null;
        $newStatus = $aplikasi->status;

        $changes[] = [
            'type' => 'status_change',
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
        ];

        // Create notification based on status change
        if ($oldStatus !== $newStatus) {
            $this->notifyStatusChange($aplikasi, $oldStatus, $newStatus);
        }

        // Handle specific status transitions
        switch ($newStatus) {
            case Aplikasi::STATUS_ACTIVE:
                $this->handleAplikasiActivated($aplikasi);
                break;
            case Aplikasi::STATUS_INACTIVE:
                $this->handleAplikasiDeactivated($aplikasi);
                break;
            case Aplikasi::STATUS_MAINTENANCE:
                $this->handleAplikasiMaintenance($aplikasi);
                break;
            case Aplikasi::STATUS_DEPRECATED:
                $this->handleAplikasiDeprecated($aplikasi);
                break;
        }
    }

    /**
     * Handle criticality change
     */
    private function handleCriticalityChange(Aplikasi $aplikasi, array &$changes): void
    {
        $oldValues = session('aplikasi_old_values', []);
        $oldCriticality = $oldValues['criticality'] ?? null;
        $newCriticality = $aplikasi->criticality;

        $changes[] = [
            'type' => 'criticality_change',
            'old_value' => $oldCriticality,
            'new_value' => $newCriticality,
        ];

        // Notify criticality change
        $this->notifyCriticalityChange($aplikasi, $oldCriticality, $newCriticality);
    }

    /**
     * Handle health status change
     */
    private function handleHealthStatusChange(Aplikasi $aplikasi, array &$changes): void
    {
        $oldValues = session('aplikasi_old_values', []);
        $oldHealthStatus = $oldValues['health_status'] ?? null;
        $newHealthStatus = $aplikasi->health_status;

        $changes[] = [
            'type' => 'health_status_change',
            'old_value' => $oldHealthStatus,
            'new_value' => $newHealthStatus,
        ];

        // Notify health status change
        $this->notifyHealthStatusChange($aplikasi, $oldHealthStatus, $newHealthStatus);
    }

    /**
     * Handle maintenance mode change
     */
    private function handleMaintenanceModeChange(Aplikasi $aplikasi, array &$changes): void
    {
        $oldValues = session('aplikasi_old_values', []);
        $oldMaintenanceMode = $oldValues['is_maintenance_mode'] ?? null;
        $newMaintenanceMode = $aplikasi->is_maintenance_mode;

        $changes[] = [
            'type' => 'maintenance_mode_change',
            'old_value' => $oldMaintenanceMode,
            'new_value' => $newMaintenanceMode,
        ];

        // Notify maintenance mode change
        if ($newMaintenanceMode) {
            $this->notifyMaintenanceModeEnabled($aplikasi);
        } else {
            $this->notifyMaintenanceModeDisabled($aplikasi);
        }
    }

    /**
     * Handle version change
     */
    private function handleVersionChange(Aplikasi $aplikasi, array &$changes): void
    {
        $oldValues = session('aplikasi_old_values', []);
        $oldVersion = $oldValues['current_version'] ?? null;
        $newVersion = $aplikasi->current_version;

        $changes[] = [
            'type' => 'version_change',
            'old_value' => $oldVersion,
            'new_value' => $newVersion,
        ];

        // Notify version update
        $this->notifyVersionUpdate($aplikasi, $oldVersion, $newVersion);
    }

    /**
     * Handle health metrics change
     */
    private function handleHealthMetricsChange(Aplikasi $aplikasi, array &$changes): void
    {
        $changes[] = [
            'type' => 'health_metrics_change',
            'old_value' => null,
            'new_value' => 'Health metrics updated',
        ];

        // Check for health degradation
        $this->checkHealthDegradation($aplikasi);
    }

    /**
     * Handle capacity change
     */
    private function handleCapacityChange(Aplikasi $aplikasi, array &$changes): void
    {
        $oldUsers = $aplikasi->getOriginal('current_users') ?? 0;
        $newUsers = $aplikasi->current_users ?? 0;

        $changes[] = [
            'type' => 'capacity_change',
            'old_value' => $oldUsers,
            'new_value' => $newUsers,
        ];

        // Check capacity thresholds
        $this->checkCapacityThresholds($aplikasi);
    }

    /**
     * Handle contract/license change
     */
    private function handleContractLicenseChange(Aplikasi $aplikasi, array &$changes): void
    {
        $changes[] = [
            'type' => 'contract_license_change',
            'old_value' => null,
            'new_value' => 'Contract or license dates updated',
        ];

        // Check for upcoming expiries
        $this->checkUpcomingExpiries($aplikasi);
    }

    /**
     * Handle aplikasi activated
     */
    private function handleAplikasiActivated(Aplikasi $aplikasi): void
    {
        // Update health status
        $aplikasi->updateHealthMetrics(['health_status' => 'good']);

        // Notify activation
        $this->notifyAplikasiActivation($aplikasi);
    }

    /**
     * Handle aplikasi deactivated
     */
    private function handleAplikasiDeactivated(Aplikasi $aplikasi): void
    {
        // Update health status
        $aplikasi->updateHealthMetrics(['health_status' => 'inactive']);

        // Notify deactivation
        $this->notifyAplikasiDeactivation($aplikasi);
    }

    /**
     * Handle aplikasi maintenance
     */
    private function handleAplikasiMaintenance(Aplikasi $aplikasi): void
    {
        // Update health status
        $aplikasi->updateHealthMetrics(['health_status' => 'maintenance']);

        // Notify maintenance mode
        $this->notifyMaintenanceMode($aplikasi);
    }

    /**
     * Handle aplikasi deprecated
     */
    private function handleAplikasiDeprecated(Aplikasi $aplikasi): void
    {
        // Update health status
        $aplikasi->updateHealthMetrics(['health_status' => 'deprecated']);

        // Notify deprecation
        $this->notifyAplikasiDeprecation($aplikasi);
    }

    /**
     * Log aplikasi changes
     */
    private function logAplikasiChanges(Aplikasi $aplikasi, array $changes): void
    {
        foreach ($changes as $change) {
            Log::info("Aplikasi change detected", [
                'aplikasi_id' => $aplikasi->id,
                'change_type' => $change['type'],
                'old_value' => $change['old_value'],
                'new_value' => $change['new_value'],
            ]);
        }
    }

    // ==================== NOTIFICATION METHODS ====================

    /**
     * Notify admins of new aplikasi
     */
    private function notifyAdminsOfNewAplikasi(Aplikasi $aplikasi): void
    {
        try {
            // Notify admin helpdesks
            $adminHelpdesks = AdminHelpdesk::active()->get();
            foreach ($adminHelpdesks as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'New Application Added',
                    "New application '{$aplikasi->name}' ({$aplikasi->code}) has been added to the system",
                    Notification::PRIORITY_MEDIUM,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'aplikasi_code' => $aplikasi->code,
                        'criticality' => $aplikasi->criticality,
                        'category' => $aplikasi->category,
                    ]
                );
            }

            // Notify admin aplikasis
            $adminAplikasis = AdminAplikasi::active()->get();
            foreach ($adminAplikasis as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'New Application Added',
                    "New application '{$aplikasi->name}' ({$aplikasi->code}) has been added to the system",
                    Notification::PRIORITY_MEDIUM,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'aplikasi_code' => $aplikasi->code,
                        'criticality' => $aplikasi->criticality,
                        'category' => $aplikasi->category,
                    ]
                );
            }

            Log::info("New aplikasi notifications sent to admins", [
                'aplikasi_id' => $aplikasi->id,
                'admin_helpdesk_count' => $adminHelpdesks->count(),
                'admin_aplikasi_count' => $adminAplikasis->count(),
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending new aplikasi notifications to admins", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create system notification for aplikasi creation
     */
    private function createSystemNotification(Aplikasi $aplikasi): void
    {
        try {
            // Create system-wide notification
            Log::info("System notification created for new aplikasi", [
                'aplikasi_id' => $aplikasi->id,
                'aplikasi_name' => $aplikasi->name,
            ]);

        } catch (\Exception $e) {
            Log::error("Error creating system notification for aplikasi", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify status change
     */
    private function notifyStatusChange(Aplikasi $aplikasi, ?string $oldStatus, ?string $newStatus): void
    {
        try {
            // Handle null values with defaults
            $oldStatusVal = $oldStatus ?? 'unknown';
            $newStatusVal = $newStatus ?? 'unknown';

            // Notify admins of status change
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Application Status Changed',
                    "Application '{$aplikasi->name}' status changed from {$oldStatusVal} to {$newStatusVal}",
                    Notification::PRIORITY_MEDIUM,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'old_status' => $oldStatusVal,
                        'new_status' => $newStatusVal,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending aplikasi status change notifications", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify criticality change
     */
    private function notifyCriticalityChange(Aplikasi $aplikasi, ?string $oldCriticality, ?string $newCriticality): void
    {
        try {
            // Handle null values with defaults
            $oldCriticalityVal = $oldCriticality ?? 'unknown';
            $newCriticalityVal = $newCriticality ?? 'unknown';

            // Notify admins of criticality change
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Application Criticality Changed',
                    "Application '{$aplikasi->name}' criticality changed from {$oldCriticalityVal} to {$newCriticalityVal}",
                    Notification::PRIORITY_HIGH,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'old_criticality' => $oldCriticalityVal,
                        'new_criticality' => $newCriticalityVal,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending aplikasi criticality change notifications", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify health status change
     */
    private function notifyHealthStatusChange(Aplikasi $aplikasi, ?string $oldHealthStatus, ?string $newHealthStatus): void
    {
        try {
            // Handle null values with defaults
            $oldStatus = $oldHealthStatus ?? 'unknown';
            $newStatus = $newHealthStatus ?? 'unknown';

            // Determine priority based on health status
            $priority = match($newStatus) {
                'poor' => Notification::PRIORITY_URGENT,
                'fair' => Notification::PRIORITY_HIGH,
                'good' => Notification::PRIORITY_MEDIUM,
                'excellent' => Notification::PRIORITY_LOW,
                default => Notification::PRIORITY_MEDIUM,
            };

            // Notify admins of health status change
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Application Health Status Changed',
                    "Application '{$aplikasi->name}' health status changed from {$oldStatus} to {$newStatus}",
                    $priority,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'old_health_status' => $oldHealthStatus,
                        'new_health_status' => $newHealthStatus,
                        'current_metrics' => [
                            'uptime' => $aplikasi->uptime_percentage,
                            'response_time' => $aplikasi->response_time_avg,
                            'error_rate' => $aplikasi->error_rate,
                        ],
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending aplikasi health status change notifications", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify maintenance mode enabled
     */
    private function notifyMaintenanceModeEnabled(Aplikasi $aplikasi): void
    {
        try {
            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Application Maintenance Mode Enabled',
                    "Application '{$aplikasi->name}' has entered maintenance mode: {$aplikasi->maintenance_reason}",
                    Notification::PRIORITY_HIGH,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'maintenance_reason' => $aplikasi->maintenance_reason,
                        'maintenance_start' => $aplikasi->maintenance_start_time,
                        'maintenance_end' => $aplikasi->maintenance_end_time,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending maintenance mode enabled notifications", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify maintenance mode disabled
     */
    private function notifyMaintenanceModeDisabled(Aplikasi $aplikasi): void
    {
        try {
            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Application Maintenance Mode Disabled',
                    "Application '{$aplikasi->name}' has exited maintenance mode and is now operational",
                    Notification::PRIORITY_MEDIUM,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'maintenance_end' => Carbon::now(),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending maintenance mode disabled notifications", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify version update
     */
    private function notifyVersionUpdate(Aplikasi $aplikasi, ?string $oldVersion, ?string $newVersion): void
    {
        try {
            // Handle null values with defaults
            $oldVersionVal = $oldVersion ?? 'unknown';
            $newVersionVal = $newVersion ?? 'unknown';

            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Application Version Updated',
                    "Application '{$aplikasi->name}' has been updated from version {$oldVersionVal} to {$newVersionVal}",
                    Notification::PRIORITY_MEDIUM,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'old_version' => $oldVersionVal,
                        'new_version' => $newVersionVal,
                        'updated_at' => Carbon::now(),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending version update notifications", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify aplikasi activation
     */
    private function notifyAplikasiActivation(Aplikasi $aplikasi): void
    {
        try {
            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Application Activated',
                    "Application '{$aplikasi->name}' has been activated and is now operational",
                    Notification::PRIORITY_MEDIUM,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'activated_at' => Carbon::now(),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending aplikasi activation notifications", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify aplikasi deactivation
     */
    private function notifyAplikasiDeactivation(Aplikasi $aplikasi): void
    {
        try {
            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Application Deactivated',
                    "Application '{$aplikasi->name}' has been deactivated",
                    Notification::PRIORITY_HIGH,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'deactivated_at' => Carbon::now(),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending aplikasi deactivation notifications", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify maintenance mode
     */
    private function notifyMaintenanceMode(Aplikasi $aplikasi): void
    {
        try {
            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Application in Maintenance',
                    "Application '{$aplikasi->name}' is now in maintenance mode",
                    Notification::PRIORITY_HIGH,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'maintenance_reason' => $aplikasi->maintenance_reason,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending maintenance mode notifications", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify aplikasi deprecation
     */
    private function notifyAplikasiDeprecation(Aplikasi $aplikasi): void
    {
        try {
            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Application Deprecated',
                    "Application '{$aplikasi->name}' has been marked as deprecated",
                    Notification::PRIORITY_HIGH,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'deprecation_date' => $aplikasi->deprecation_date,
                        'replacement_application' => $aplikasi->replacementApplication?->name,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending aplikasi deprecation notifications", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify admins of aplikasi deletion
     */
    private function notifyAdminsOfAplikasiDeletion(Aplikasi $aplikasi): void
    {
        try {
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Application Removed',
                    "Application '{$aplikasi->name}' ({$aplikasi->code}) has been removed from the system",
                    Notification::PRIORITY_HIGH,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'total_tickets' => $aplikasi->getTotalTicketCount(),
                        'deleted_at' => Carbon::now(),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending aplikasi deletion notifications", [
                'aplikasi_id' => $aplikasi->id,
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

    // ==================== HEALTH MONITORING ====================

    /**
     * Check health degradation
     */
    private function checkHealthDegradation(Aplikasi $aplikasi): void
    {
        try {
            $healthStatus = $aplikasi->health_status;
            $uptime = $aplikasi->uptime_percentage;
            $errorRate = $aplikasi->error_rate;

            // Check for poor health indicators
            if ($healthStatus === 'poor' || $uptime < 95.0 || $errorRate > 0.1) {
                $this->handleHealthIssues($aplikasi, $healthStatus, $uptime, $errorRate);
            }

        } catch (\Exception $e) {
            Log::error("Error checking health degradation", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle health issues
     */
    private function handleHealthIssues(Aplikasi $aplikasi, string $healthStatus, ?float $uptime, ?float $errorRate): void
    {
        try {
            // Notify admins of health issues
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Application Health Issues Detected',
                    "Application '{$aplikasi->name}' is experiencing health issues",
                    Notification::PRIORITY_URGENT,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'health_status' => $healthStatus,
                        'uptime_percentage' => $uptime,
                        'error_rate' => $errorRate,
                        'detected_at' => Carbon::now(),
                    ]
                );
            }

            Log::warning("Application health issues detected", [
                'aplikasi_id' => $aplikasi->id,
                'health_status' => $healthStatus,
                'uptime' => $uptime,
                'error_rate' => $errorRate,
            ]);

        } catch (\Exception $e) {
            Log::error("Error handling health issues", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ==================== CAPACITY MONITORING ====================

    /**
     * Check capacity thresholds
     */
    private function checkCapacityThresholds(Aplikasi $aplikasi): void
    {
        try {
            $utilization = $aplikasi->getCapacityUtilization();

            // Check for high utilization (90%+)
            if ($utilization >= 90) {
                $this->handleHighCapacityUtilization($aplikasi, $utilization);
            }

        } catch (\Exception $e) {
            Log::error("Error checking capacity thresholds", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle high capacity utilization
     */
    private function handleHighCapacityUtilization(Aplikasi $aplikasi, float $utilization): void
    {
        try {
            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'High Application Capacity Utilization',
                    "Application '{$aplikasi->name}' is at {$utilization}% capacity utilization",
                    Notification::PRIORITY_HIGH,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'utilization_percentage' => $utilization,
                        'current_users' => $aplikasi->current_users,
                        'max_users' => $aplikasi->max_users,
                        'detected_at' => Carbon::now(),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error handling high capacity utilization", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ==================== CONTRACT/LICENSE MONITORING ====================

    /**
     * Check upcoming expiries
     */
    private function checkUpcomingExpiries(Aplikasi $aplikasi): void
    {
        try {
            $now = Carbon::now();

            // Check vendor contract expiry (30 days)
            if ($aplikasi->vendor_contract_expiry && $aplikasi->vendor_contract_expiry->isBetween($now, $now->copy()->addDays(30))) {
                $this->notifyUpcomingContractExpiry($aplikasi);
            }

            // Check license expiry (30 days)
            if ($aplikasi->license_expiry && $aplikasi->license_expiry->isBetween($now, $now->copy()->addDays(30))) {
                $this->notifyUpcomingLicenseExpiry($aplikasi);
            }

        } catch (\Exception $e) {
            Log::error("Error checking upcoming expiries", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify upcoming contract expiry
     */
    private function notifyUpcomingContractExpiry(Aplikasi $aplikasi): void
    {
        try {
            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Application Contract Expiring Soon',
                    "Vendor contract for '{$aplikasi->name}' expires on {$aplikasi->formatted_vendor_contract_expiry}",
                    Notification::PRIORITY_HIGH,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'expiry_date' => $aplikasi->vendor_contract_expiry,
                        'days_until_expiry' => Carbon::now()->diffInDays($aplikasi->vendor_contract_expiry),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending upcoming contract expiry notifications", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify upcoming license expiry
     */
    private function notifyUpcomingLicenseExpiry(Aplikasi $aplikasi): void
    {
        try {
            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $adminAplikasis = AdminAplikasi::active()->get();

            $allAdmins = $adminHelpdesks->concat($adminAplikasis);

            foreach ($allAdmins as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Application License Expiring Soon',
                    "License for '{$aplikasi->name}' expires on {$aplikasi->formatted_license_expiry}",
                    Notification::PRIORITY_HIGH,
                    '/admin/applications',
                    [
                        'aplikasi_id' => $aplikasi->id,
                        'aplikasi_name' => $aplikasi->name,
                        'expiry_date' => $aplikasi->license_expiry,
                        'days_until_expiry' => Carbon::now()->diffInDays($aplikasi->license_expiry),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending upcoming license expiry notifications", [
                'aplikasi_id' => $aplikasi->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}