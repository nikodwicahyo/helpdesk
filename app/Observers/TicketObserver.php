<?php

namespace App\Observers;


use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\Notification;
use App\Models\User;
use App\Models\Teknisi;
use App\Models\AdminHelpdesk;
use App\Models\SystemSetting;
use App\Services\AuthService;
use App\Services\AuditLogService;
use App\Services\DashboardMetricsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TicketObserver
{
    protected $auditLogService;
    protected $dashboardMetricsService;

    public function __construct(AuditLogService $auditLogService, DashboardMetricsService $dashboardMetricsService)
    {
        $this->auditLogService = $auditLogService;
        $this->dashboardMetricsService = $dashboardMetricsService;
    }

    /**
     * Handle the Ticket "creating" event.
     */
    public function creating(Ticket $ticket): void
    {
        // Generate ticket number if not provided
        if (!$ticket->ticket_number) {
            $ticket->ticket_number = $this->generateTicketNumber();
        }

        // Set due date based on priority if not provided
        if (!$ticket->due_date) {
            $ticket->due_date = $this->calculateDueDate($ticket->priority);
        }

        Log::info("Creating new ticket", [
            'ticket_number' => $ticket->ticket_number,
            'user_nip' => $ticket->user_nip,
            'priority' => $ticket->priority,
        ]);
    }

    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        try {
            // Log ticket creation in history
            TicketHistory::logTicketCreated($ticket, $ticket->user_nip, 'user');

            // Log ticket creation in audit log (but only if not already logged by controller)
            // We check if this was called within the last second to avoid duplicate logs
            $recentLog = \App\Models\AuditLog::where('entity_type', 'Ticket')
                ->where('entity_id', $ticket->id)
                ->where('action', 'created')
                ->where('created_at', '>=', now()->subSeconds(2))
                ->first();
            
            if (!$recentLog) {
                Log::info('TicketObserver: Logging ticket creation to audit log');
                $this->auditLogService->logTicketCreated($ticket);
            } else {
                Log::info('TicketObserver: Ticket creation already logged, skipping duplicate');
            }

            // Create notifications for relevant users
            $this->notifyTicketCreated($ticket);

            // Auto-assign teknisi if enabled
            if (SystemSetting::get('auto_assign_enabled', false)) {
                $this->autoAssignTeknisi($ticket);
            }

            // Check for SLA requirements
            $this->checkSlaRequirements($ticket);

            // Invalidate dashboard metrics cache
            $this->invalidateDashboardMetricsCache();

            Log::info("Ticket created successfully", [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
            ]);

        } catch (\Exception $e) {
            Log::error("Error in TicketObserver::created", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Ticket "updating" event.
     */
    public function updating(Ticket $ticket): void
    {
        // Update due date if priority changed
        if ($ticket->isDirty('priority') && !$ticket->getOriginal('due_date')) {
            $ticket->due_date = $this->calculateDueDate($ticket->priority);
        }
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        try {
            $changes = [];

            // Check status changes
            if ($ticket->wasChanged('status')) {
                $this->handleStatusChange($ticket, $changes);
            }

            // Check priority changes
            if ($ticket->wasChanged('priority')) {
                $this->handlePriorityChange($ticket, $changes);
            }

            // Check teknisi assignment changes
            if ($ticket->wasChanged('assigned_teknisi_nip')) {
                $this->handleAssignmentChange($ticket, $changes);
            }

            // Check escalation
            if ($ticket->wasChanged('is_escalated') && $ticket->is_escalated) {
                $this->handleEscalation($ticket, $changes);
            }

            // Check first response
            if ($ticket->wasChanged('first_response_at') && $ticket->first_response_at) {
                $this->handleFirstResponse($ticket, $changes);
            }

            // Check resolution
            if ($ticket->wasChanged('resolved_at') && $ticket->resolved_at) {
                $this->handleResolution($ticket, $changes);
            }

            // Check rating
            if ($ticket->wasChanged('user_rating') && $ticket->user_rating) {
                $this->handleRating($ticket, $changes);
            }

            // Log significant changes
            if (!empty($changes)) {
                $this->logChanges($ticket, $changes);
            }

            // Update teknisi workload if assignment changed
            if ($ticket->wasChanged('assigned_teknisi_nip') && $ticket->assigned_teknisi_nip) {
                $this->updateTeknisiWorkload($ticket);
            }

            Log::info("Ticket updated", [
                'ticket_id' => $ticket->id,
                'changes' => $changes,
            ]);

        } catch (\Exception $e) {
            Log::error("Error in TicketObserver::updated", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Ticket "deleting" event.
     */
    public function deleting(Ticket $ticket): void
    {
        Log::warning("Ticket being deleted", [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
        ]);
    }

    /**
     * Handle the Ticket "deleted" event.
     */
    public function deleted(Ticket $ticket): void
    {
        try {
            // Log deletion
            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'action' => TicketHistory::ACTION_DELETED,
                'performed_by_nip' => Auth::user()?->nip ?? 'system',
                'performed_by_type' => 'system',
                'description' => "Ticket deleted: {$ticket->ticket_number}",
                'metadata' => [
                    'ticket_title' => $ticket->title,
                    'deleted_at' => Carbon::now(),
                ],
                'category' => TicketHistory::FIELD_CATEGORY_SYSTEM,
                'severity' => 'high',
            ]);

            Log::info("Ticket deleted", [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
            ]);

        } catch (\Exception $e) {
            Log::error("Error in TicketObserver::deleted", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate unique ticket number
     */
    private function generateTicketNumber(): string
    {
        // Use the same ticket number generation as TicketService for consistency
        $ticketService = app(\App\Services\TicketService::class);
        return $ticketService->generateTicketNumber();
    }

    /**
     * Calculate due date based on priority
     */
    private function calculateDueDate(string $priority): Carbon
    {
        $slaHours = Ticket::PRIORITY_SLA_HOURS[$priority] ?? 72;
        return Carbon::now()->addHours($slaHours);
    }

    /**
     * Handle status change
     */
    private function handleStatusChange(Ticket $ticket, array &$changes): void
    {
        $oldStatus = $ticket->getOriginal('status');
        $newStatus = $ticket->status;

        $changes[] = [
            'type' => 'status_change',
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
        ];

        // Create notification
        $this->notifyStatusChange($ticket, $oldStatus, $newStatus);

        // Log status change
        TicketHistory::logStatusChange(
            $ticket,
            $oldStatus,
            $newStatus,
            Auth::user()?->nip ?? 'system',
            'system'
        );

        // Handle specific status transitions
        switch ($newStatus) {
            case Ticket::STATUS_RESOLVED:
                $this->handleTicketResolved($ticket);
                break;
            case Ticket::STATUS_CLOSED:
                $this->handleTicketClosed($ticket);
                break;
            case Ticket::STATUS_IN_PROGRESS:
                $this->handleTicketInProgress($ticket);
                break;
        }
    }

    /**
     * Handle priority change
     */
    private function handlePriorityChange(Ticket $ticket, array &$changes): void
    {
        $oldPriority = $ticket->getOriginal('priority');
        $newPriority = $ticket->priority;

        $changes[] = [
            'type' => 'priority_change',
            'old_value' => $oldPriority,
            'new_value' => $newPriority,
        ];

        // Create notification
        $this->notifyPriorityChange($ticket, $oldPriority, $newPriority);

        // Log priority change
        TicketHistory::logPriorityChange(
            $ticket,
            $oldPriority,
            $newPriority,
            Auth::user()?->nip ?? 'system',
            'system'
        );


    }

    /**
     * Handle assignment change
     */
    private function handleAssignmentChange(Ticket $ticket, array &$changes): void
    {
        $oldTeknisi = $ticket->getOriginal('assigned_teknisi_nip');
        $newTeknisi = $ticket->assigned_teknisi_nip;

        $changes[] = [
            'type' => 'assignment_change',
            'old_value' => $oldTeknisi,
            'new_value' => $newTeknisi,
        ];

        // Create notifications
        $this->notifyAssignmentChange($ticket, $oldTeknisi, $newTeknisi);

        // Log assignment change
        if ($oldTeknisi && $newTeknisi) {
            TicketHistory::logTicketAssignment(
                $ticket,
                $oldTeknisi,
                $newTeknisi,
                Auth::user()?->nip ?? 'system',
                'system'
            );
        }
    }

    /**
     * Handle escalation
     */
    private function handleEscalation(Ticket $ticket, array &$changes): void
    {
        $changes[] = [
            'type' => 'escalation',
            'old_value' => false,
            'new_value' => true,
        ];

        // Create notification
        $this->notifyEscalation($ticket);

        // Log escalation
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'action' => TicketHistory::ACTION_ESCALATED,
            'performed_by_nip' => Auth::user()?->nip ?? 'system',
            'performed_by_type' => 'system',
            'description' => "Ticket escalated: {$ticket->escalation_reason}",
            'metadata' => [
                'reason' => $ticket->escalation_reason,
                'escalated_at' => Carbon::now(),
            ],
            'category' => TicketHistory::FIELD_CATEGORY_PRIORITY,
            'severity' => 'high',
        ]);
    }

    /**
     * Handle first response
     */
    private function handleFirstResponse(Ticket $ticket, array &$changes): void
    {
        $changes[] = [
            'type' => 'first_response',
            'old_value' => null,
            'new_value' => $ticket->first_response_at,
        ];

        // Create notification
        $this->notifyFirstResponse($ticket);
    }

    /**
     * Handle resolution
     */
    private function handleResolution(Ticket $ticket, array &$changes): void
    {
        $changes[] = [
            'type' => 'resolution',
            'old_value' => null,
            'new_value' => $ticket->resolved_at,
        ];

        // Calculate and update resolution time
        $ticket->updateResolutionTime();

        // Update teknisi performance if assigned
        if ($ticket->assigned_teknisi_nip) {
            $teknisi = $ticket->assignedTeknisi;
            if ($teknisi) {
                $teknisi->recordTicketPerformance($ticket);
            }
        }

        // Create notification
        $this->notifyResolution($ticket);
    }

    /**
     * Handle rating
     */
    private function handleRating(Ticket $ticket, array &$changes): void
    {
        $changes[] = [
            'type' => 'rating',
            'old_value' => $ticket->getOriginal('user_rating'),
            'new_value' => $ticket->user_rating,
        ];

        // Update teknisi performance metrics if assigned
        if ($ticket->assigned_teknisi_nip) {
            $teknisi = $ticket->assignedTeknisi;
            if ($teknisi) {
                $teknisi->updatePerformanceMetrics();
            }
        }
    }

    /**
     * Handle ticket resolved
     */
    private function handleTicketResolved(Ticket $ticket): void
    {
        // Update resolution time
        $ticket->updateResolutionTime();

        // Check SLA compliance
        $this->checkSlaCompliance($ticket);

        // Notify user
        $this->notifyTicketResolved($ticket);


    }

    /**
     * Handle ticket closed
     */
    private function handleTicketClosed(Ticket $ticket): void
    {
        // Final workload update for teknisi
        if ($ticket->assigned_teknisi_nip) {
            $teknisi = $ticket->assignedTeknisi;
            if ($teknisi) {
                $teknisi->updateWorkloadScore();
            }
        }

        // Notify user
        $this->notifyTicketClosed($ticket);
    }

    /**
     * Handle ticket in progress
     */
    private function handleTicketInProgress(Ticket $ticket): void
    {
        // Mark first response if not already marked
        if (!$ticket->first_response_at) {
            $ticket->markFirstResponse();
        }
    }

    /**
     * Log changes to history
     */
    private function logChanges(Ticket $ticket, array $changes): void
    {
        foreach ($changes as $change) {
            TicketHistory::logFieldChange(
                $ticket,
                $change['type'],
                $change['old_value'],
                $change['new_value'],
                Auth::user()?->nip ?? 'system',
                'system'
            );
        }
    }

    /**
     * Update teknisi workload
     */
    private function updateTeknisiWorkload(Ticket $ticket): void
    {
        $teknisi = $ticket->assignedTeknisi;
        if ($teknisi) {
            $teknisi->updateWorkloadScore();
        }
    }

    // ==================== NOTIFICATION METHODS ====================

    /**
     * Notify ticket creation
     */
    private function notifyTicketCreated(Ticket $ticket): void
    {
        try {
            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            foreach ($adminHelpdesks as $admin) {
                Notification::create([
                    'type' => 'ticket_created',
                    'notifiable_type' => AdminHelpdesk::class,
                    'notifiable_id' => $admin->nip,
                    'ticket_id' => $ticket->id,
                    'triggered_by_type' => Notification::mapClassToTriggeredByType(get_class($ticket->user)),
                    'triggered_by_nip' => $ticket->user->nip,
                    'title' => 'New Ticket Created',
                    'message' => "New ticket #{$ticket->ticket_number} created by {$ticket->user->name}",
                    'priority' => Notification::PRIORITY_MEDIUM,
                    'channel' => Notification::CHANNEL_DATABASE,
                    'status' => Notification::STATUS_PENDING,
                    'action_url' => "/tickets/{$ticket->id}",
                    'data' => [
                        'ticket_number' => $ticket->ticket_number,
                        'ticket_title' => $ticket->title,
                        'priority' => $ticket->priority,
                        'user_name' => $ticket->user->name,
                    ],
                ]);
            }

            Log::info("Ticket creation notifications sent", [
                'ticket_id' => $ticket->id,
                'admin_count' => $adminHelpdesks->count(),
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending ticket creation notifications", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify status change
     */
    private function notifyStatusChange(Ticket $ticket, string $oldStatus, string $newStatus): void
    {
        try {
            $triggeredBy = Auth::user();

            // Notify ticket owner
            Notification::createTicketStatusChanged($ticket, $oldStatus, $ticket->user, $triggeredBy);

            // Notify assigned teknisi if status affects them
            if ($ticket->assigned_teknisi_nip && in_array($newStatus, [Ticket::STATUS_WAITING_RESPONSE, Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])) {
                $teknisi = $ticket->assignedTeknisi;
                if ($teknisi) {
                    Notification::createTicketStatusChanged($ticket, $oldStatus, $teknisi, $triggeredBy);
                }
            }

            // Notify admins for critical status changes
            if (in_array($newStatus, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])) {
                $adminHelpdesks = AdminHelpdesk::active()->get();
                foreach ($adminHelpdesks as $admin) {
                    Notification::createTicketStatusChanged($ticket, $oldStatus, $admin, $triggeredBy);
                }
            }

        } catch (\Exception $e) {
            Log::error("Error sending status change notifications", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify priority change
     */
    private function notifyPriorityChange(Ticket $ticket, string $oldPriority, string $newPriority): void
    {
        try {
            $triggeredBy = Auth::user();

            // Notify ticket owner
            $this->createCustomNotification(
                $ticket->user,
                'Priority Changed',
                "Priority for ticket #{$ticket->ticket_number} changed from {$oldPriority} to {$newPriority}",
                Notification::PRIORITY_MEDIUM,
                "/tickets/{$ticket->id}",
                [
                    'ticket_number' => $ticket->ticket_number,
                    'old_priority' => $oldPriority,
                    'new_priority' => $newPriority,
                ],
                'ticket_priority_changed',
                $triggeredBy
            );

            // Notify assigned teknisi
            if ($ticket->assigned_teknisi_nip) {
                $teknisi = $ticket->assignedTeknisi;
                if ($teknisi) {
                    $this->createCustomNotification(
                        $teknisi,
                        'Ticket Priority Changed',
                        "Priority for assigned ticket #{$ticket->ticket_number} changed to {$newPriority}",
                        Notification::PRIORITY_HIGH,
                        "/tickets/{$ticket->id}",
                        [
                            'ticket_number' => $ticket->ticket_number,
                            'new_priority' => $newPriority,
                        ],
                        'ticket_priority_changed',
                        $triggeredBy
                    );
                }
            }

        } catch (\Exception $e) {
            Log::error("Error sending priority change notifications", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify assignment change
     */
    private function notifyAssignmentChange(Ticket $ticket, ?string $oldTeknisiNip, string $newTeknisiNip): void
    {
        try {
            // Determine who triggered the assignment
            // If assigned_by_nip is set on ticket, use that relation
            // Otherwise fallback to Auth::user() or system
            $triggeredBy = $ticket->assignedBy ?? Auth::user();

            // Notify new teknisi
            $newTeknisi = Teknisi::where('nip', $newTeknisiNip)->first();
            if ($newTeknisi) {
                Notification::createTicketAssigned($ticket, $newTeknisi, $triggeredBy);
            }

            // Notify old teknisi if unassigned
            if ($oldTeknisiNip) {
                $oldTeknisi = Teknisi::where('nip', $oldTeknisiNip)->first();
                if ($oldTeknisi) {
                    $this->createCustomNotification(
                        $oldTeknisi,
                        'Ticket Unassigned',
                        "You have been unassigned from ticket #{$ticket->ticket_number}",
                        Notification::PRIORITY_MEDIUM,
                        "/tickets/{$ticket->id}",
                        [
                            'ticket_number' => $ticket->ticket_number,
                            'reason' => 'Reassigned to another teknisi',
                        ],
                        'ticket_unassigned',
                        $triggeredBy
                    );
                }
            }

            // Notify ticket owner
            $this->createCustomNotification(
                $ticket->user,
                'Ticket Assignment Updated',
                "Ticket #{$ticket->ticket_number} has been assigned to a teknisi",
                Notification::PRIORITY_MEDIUM,
                "/tickets/{$ticket->id}",
                [
                    'ticket_number' => $ticket->ticket_number,
                    'teknisi_name' => $newTeknisi ? $newTeknisi->name : 'Unknown',
                ],
                'ticket_assigned',
                $triggeredBy
            );

        } catch (\Exception $e) {
            Log::error("Error sending assignment change notifications", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify escalation
     */
    private function notifyEscalation(Ticket $ticket): void
    {
        try {
            $triggeredBy = Auth::user();

            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            foreach ($adminHelpdesks as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Ticket Escalated',
                    "Ticket #{$ticket->ticket_number} has been escalated: {$ticket->escalation_reason}",
                    Notification::PRIORITY_URGENT,
                    "/tickets/{$ticket->id}",
                    [
                        'ticket_number' => $ticket->ticket_number,
                        'reason' => $ticket->escalation_reason,
                        'priority' => $ticket->priority,
                    ],
                    Notification::TYPE_TICKET_ESCALATED,
                    $triggeredBy
                );
            }

            // Notify assigned teknisi
            if ($ticket->assigned_teknisi_nip) {
                $teknisi = $ticket->assignedTeknisi;
                if ($teknisi) {
                    $this->createCustomNotification(
                        $teknisi,
                        'Assigned Ticket Escalated',
                        "Your assigned ticket #{$ticket->ticket_number} has been escalated",
                        Notification::PRIORITY_HIGH,
                        "/tickets/{$ticket->id}",
                        [
                            'ticket_number' => $ticket->ticket_number,
                            'reason' => $ticket->escalation_reason,
                        ],
                        Notification::TYPE_TICKET_ESCALATED,
                        $triggeredBy
                    );
                }
            }

        } catch (\Exception $e) {
            Log::error("Error sending escalation notifications", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify first response
     */
    private function notifyFirstResponse(Ticket $ticket): void
    {
        try {
            $triggeredBy = Auth::user() ?? $ticket->assignedTeknisi;

            // Notify ticket owner
            $this->createCustomNotification(
                $ticket->user,
                'First Response Received',
                "Your ticket #{$ticket->ticket_number} has received its first response",
                Notification::PRIORITY_MEDIUM,
                "/tickets/{$ticket->id}",
                [
                    'ticket_number' => $ticket->ticket_number,
                    'response_time' => Carbon::parse($ticket->created_at)->diffForHumans($ticket->first_response_at, true),
                ],
                'ticket_first_response',
                $triggeredBy
            );

        } catch (\Exception $e) {
            Log::error("Error sending first response notifications", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify resolution
     */
    private function notifyResolution(Ticket $ticket): void
    {
        try {
            $triggeredBy = Auth::user() ?? $ticket->assignedTeknisi;

            // Notify ticket owner
            $this->createCustomNotification(
                $ticket->user,
                'Ticket Resolved',
                "Your ticket #{$ticket->ticket_number} has been resolved",
                Notification::PRIORITY_HIGH,
                "/tickets/{$ticket->id}",
                [
                    'ticket_number' => $ticket->ticket_number,
                    'resolution_time' => $ticket->formatted_resolution_time,
                    'can_rate' => true,
                ],
                Notification::TYPE_TICKET_RESOLVED,
                $triggeredBy
            );

            // Notify assigned teknisi
            if ($ticket->assigned_teknisi_nip) {
                $teknisi = $ticket->assignedTeknisi;
                if ($teknisi) {
                    $this->createCustomNotification(
                        $teknisi,
                        'Ticket Resolved',
                        "Ticket #{$ticket->ticket_number} has been marked as resolved",
                        Notification::PRIORITY_MEDIUM,
                        "/tickets/{$ticket->id}",
                        [
                            'ticket_number' => $ticket->ticket_number,
                            'resolution_time' => $ticket->formatted_resolution_time,
                        ],
                        Notification::TYPE_TICKET_RESOLVED,
                        $triggeredBy
                    );
                }
            }

        } catch (\Exception $e) {
            Log::error("Error sending resolution notifications", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify ticket resolved
     */
    private function notifyTicketResolved(Ticket $ticket): void
    {
        // This is handled in notifyResolution
    }

    /**
     * Notify ticket closed
     */
    private function notifyTicketClosed(Ticket $ticket): void
    {
        try {
            $triggeredBy = Auth::user() ?? $ticket->user;

            // Notify ticket owner
            $this->createCustomNotification(
                $ticket->user,
                'Ticket Closed',
                "Your ticket #{$ticket->ticket_number} has been closed",
                Notification::PRIORITY_MEDIUM,
                "/tickets/{$ticket->id}",
                [
                    'ticket_number' => $ticket->ticket_number,
                    'closed_at' => $ticket->formatted_closed_at,
                ],
                'ticket_closed',
                $triggeredBy
            );

        } catch (\Exception $e) {
            Log::error("Error sending ticket closed notifications", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create custom notification
     */
    private function createCustomNotification($notifiable, string $title, string $message, string $priority, string $actionUrl, array $data = [], string $type = Notification::TYPE_SYSTEM_ANNOUNCEMENT, $triggeredBy = null): void
    {
        Notification::create([
            'type' => $type,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->getKey(),
            'triggered_by_type' => $triggeredBy ? Notification::mapClassToTriggeredByType(get_class($triggeredBy)) : null,
            'triggered_by_nip' => $triggeredBy ? $triggeredBy->nip : null,
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'channel' => Notification::CHANNEL_DATABASE,
            'status' => Notification::STATUS_PENDING,
            'action_url' => $actionUrl,
            'data' => $data,
        ]);
    }

    // ==================== AUTOMATION METHODS ====================

    /**
     * Auto-assign teknisi to ticket
     */
    private function autoAssignTeknisi(Ticket $ticket): void
    {
        try {
            // Get assignment algorithm from database settings
            $algorithm = SystemSetting::get('auto_assign_algorithm', 'load_balanced');
            $maxConcurrent = SystemSetting::get('max_concurrent_tickets', 10);

            // Find best teknisi using the configured algorithm
            $teknisi = Teknisi::findBestTeknisiForTicket($ticket, $algorithm);

            if ($teknisi) {
                // Check if teknisi has capacity
                $activeTickets = $teknisi->activeTickets()->count();
                if ($activeTickets >= $maxConcurrent) {
                    Log::warning("Teknisi has reached maximum concurrent tickets", [
                        'ticket_id' => $ticket->id,
                        'teknisi_nip' => $teknisi->nip,
                        'active_tickets' => $activeTickets,
                        'max_concurrent' => $maxConcurrent,
                    ]);
                    return;
                }

                $ticket->assignToTeknisi($teknisi->nip, 'system', 'Auto-assigned by system');

                // Log the auto-assignment using AuditLogService
                $this->auditLogService->logTicketAction(
                    $ticket,
                    'assigned',
                    null, // System action
                    [
                        'assigned_to' => $teknisi->nip,
                        'assigned_to_name' => $teknisi->name,
                        'assignment_method' => 'auto',
                        'algorithm' => $algorithm,
                        'teknisi_workload' => $activeTickets,
                        'max_concurrent_tickets' => $maxConcurrent,
                    ]
                );

                Log::info("Ticket auto-assigned successfully", [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'teknisi_nip' => $teknisi->nip,
                    'teknisi_name' => $teknisi->name,
                    'algorithm' => $algorithm,
                ]);
            } else {
                Log::warning("No suitable teknisi found for auto-assignment", [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'priority' => $ticket->priority,
                    'application' => $ticket->aplikasi?->name,
                    'algorithm' => $algorithm,
                ]);

                // Log failed auto-assignment
                $this->auditLogService->logTicketAction(
                    $ticket,
                    'assignment_failed',
                    null,
                    [
                        'reason' => 'No suitable teknisi available',
                        'algorithm' => $algorithm,
                        'priority' => $ticket->priority,
                        'application' => $ticket->aplikasi?->name,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error in auto-assignment", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Log the error
            $this->auditLogService->logTicketAction(
                $ticket,
                'assignment_error',
                null,
                [
                    'error' => $e->getMessage(),
                    'algorithm' => $algorithm ?? 'unknown',
                ]
            );
        }
    }

    /**
     * Check SLA requirements
     */
    private function checkSlaRequirements(Ticket $ticket): void
    {
        try {
            // Schedule SLA monitoring
            if ($ticket->due_date) {
                // This would typically schedule a job to check SLA compliance
                Log::info("SLA monitoring scheduled", [
                    'ticket_id' => $ticket->id,
                    'due_date' => $ticket->due_date,
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Error checking SLA requirements", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check SLA compliance
     */
    private function checkSlaCompliance(Ticket $ticket): void
    {
        try {
            $slaDeadline = $ticket->getSlaDeadline();
            $resolvedAt = $ticket->resolved_at;

            if ($resolvedAt && $resolvedAt->isAfter($slaDeadline)) {
                Log::warning("SLA breach detected", [
                    'ticket_id' => $ticket->id,
                    'resolved_at' => $resolvedAt,
                    'sla_deadline' => $slaDeadline,
                ]);

                // Create SLA breach notification
                $this->notifySlaBreach($ticket, $slaDeadline, $resolvedAt);
            }

        } catch (\Exception $e) {
            Log::error("Error checking SLA compliance", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify SLA breach
     */
    private function notifySlaBreach(Ticket $ticket, Carbon $slaDeadline, Carbon $resolvedAt): void
    {
        try {
            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            foreach ($adminHelpdesks as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'SLA Breach Detected',
                    "Ticket #{$ticket->ticket_number} breached SLA by " . $slaDeadline->diffForHumans($resolvedAt, true),
                    Notification::PRIORITY_URGENT,
                    "/tickets/{$ticket->id}",
                    [
                        'ticket_number' => $ticket->ticket_number,
                        'sla_deadline' => $slaDeadline,
                        'resolved_at' => $resolvedAt,
                        'breach_duration' => $slaDeadline->diffForHumans($resolvedAt, true),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending SLA breach notifications", [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate dashboard metrics cache for all roles
     *
     * @return void
     */
    protected function invalidateDashboardMetricsCache(): void
    {
        try {
            // Invalidate cache for all dashboard roles
            $roles = ['admin_helpdesk', 'teknisi', 'user', 'admin_aplikasi'];

            foreach ($roles as $role) {
                $this->dashboardMetricsService->invalidateCache($role);
            }

            // Also clear specific cached metrics
            $todayKey = now()->format('Y-m-d');
            \Illuminate\Support\Facades\Cache::forget('admin_helpdesk_metrics_' . now()->format('Y-m-d-H'));
            \Illuminate\Support\Facades\Cache::forget("admin_dashboard_stats_{$todayKey}");
            \Illuminate\Support\Facades\Cache::forget("admin_dashboard_priority_breakdown_{$todayKey}");
            \Illuminate\Support\Facades\Cache::forget("admin_dashboard_status_trends_{$todayKey}");
            \Illuminate\Support\Facades\Cache::forget("admin_dashboard_sla_compliance_{$todayKey}");
            \Illuminate\Support\Facades\Cache::forget("admin_dashboard_status_distribution_{$todayKey}");
            \Illuminate\Support\Facades\Cache::forget("admin_dashboard_system_health_{$todayKey}");
            \Illuminate\Support\Facades\Cache::forget("admin_dashboard_chart_data_{$todayKey}");
            \Illuminate\Support\Facades\Cache::forget('avg_resolution_time');
            \Illuminate\Support\Facades\Cache::forget('sla_compliance');
            \Illuminate\Support\Facades\Cache::forget('tickets_per_application');
            \Illuminate\Support\Facades\Cache::forget('top_categories');

            Log::info("Dashboard metrics cache invalidated for all roles");

        } catch (\Exception $e) {
            Log::error("Error invalidating dashboard metrics cache", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
