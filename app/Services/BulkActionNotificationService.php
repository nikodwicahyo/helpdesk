<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Teknisi;
use App\Models\AdminHelpdesk;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BulkActionNotificationService
{
    /**
     * Create notifications for bulk status changes with optimized performance
     *
     * @param Ticket[] $tickets Array of ticket models
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     * @param string $performedBy NIP of person performing the action
     * @param string $notes Optional notes
     * @return void
     */
    public function createStatusChangeNotifications(array $tickets, string $oldStatus, string $newStatus, string $performedBy, string $notes = ''): void
    {
        /** @var Ticket[] $tickets */
        DB::beginTransaction();
        try {
            $userNotifications = [];
            $teknisiNotifications = [];

            // Pre-fetch all unique users and teknisi to avoid N+1 queries
            $userNips = collect($tickets)->pluck('user_nip')->filter()->unique()->toArray();
            $teknisiNips = collect($tickets)->pluck('assigned_teknisi_nip')->filter()->unique()->toArray();

            $users = User::whereIn('nip', $userNips)->get()->keyBy('nip');
            $teknisis = Teknisi::whereIn('nip', $teknisiNips)->get()->keyBy('nip');

            foreach ($tickets as $ticket) {
                // Notify ticket owner
                if ($ticket->user_nip !== $performedBy && isset($users[$ticket->user_nip])) {
                    $userNotifications[] = [
                        'type' => Notification::TYPE_TICKET_STATUS_CHANGED,
                        'notifiable_type' => User::class,
                        'notifiable_id' => $users[$ticket->user_nip]->getKey(),
                        'ticket_id' => $ticket->id,
                        'triggered_by_type' => 'admin_helpdesk',
                        'triggered_by_nip' => $performedBy,
                        'title' => 'Ticket Status Updated (Bulk)',
                        'message' => "Ticket {$ticket->ticket_number} status changed from {$oldStatus} to {$newStatus} by admin",
                        'priority' => Notification::PRIORITY_MEDIUM,
                        'channel' => Notification::CHANNEL_DATABASE,
                        'status' => Notification::STATUS_PENDING,
                        'data' => json_encode([
                            'ticket_number' => $ticket->ticket_number,
                            'ticket_title' => $ticket->title,
                            'old_status' => $oldStatus,
                            'new_status' => $newStatus,
                            'bulk_action' => true,
                            'notes' => $notes,
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Notify assigned teknisi if status changed
                if ($ticket->assigned_teknisi_nip && $ticket->assigned_teknisi_nip !== $performedBy && isset($teknisis[$ticket->assigned_teknisi_nip])) {
                    $teknisiNotifications[] = [
                        'type' => Notification::TYPE_TICKET_STATUS_CHANGED,
                        'notifiable_type' => Teknisi::class,
                        'notifiable_id' => $teknisis[$ticket->assigned_teknisi_nip]->getKey(),
                        'ticket_id' => $ticket->id,
                        'triggered_by_type' => 'admin_helpdesk',
                        'triggered_by_nip' => $performedBy,
                        'title' => 'Ticket Status Updated (Bulk)',
                        'message' => "Ticket {$ticket->ticket_number} status changed to {$newStatus}",
                        'priority' => Notification::PRIORITY_MEDIUM,
                        'channel' => Notification::CHANNEL_DATABASE,
                        'status' => Notification::STATUS_PENDING,
                        'data' => json_encode([
                            'ticket_number' => $ticket->ticket_number,
                            'ticket_title' => $ticket->title,
                            'old_status' => $oldStatus,
                            'new_status' => $newStatus,
                            'bulk_action' => true,
                            'notes' => $notes,
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Bulk insert notifications
            if (!empty($userNotifications)) {
                Notification::insert($userNotifications);
            }

            if (!empty($teknisiNotifications)) {
                Notification::insert($teknisiNotifications);
            }

            DB::commit();

            Log::info("Bulk status change notifications created", [
                'user_notifications' => count($userNotifications),
                'teknisi_notifications' => count($teknisiNotifications),
                'total_tickets' => count($tickets),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating bulk status change notifications', [
                'error' => $e->getMessage(),
                'ticket_count' => count($tickets),
                'stack_trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Create notifications for bulk assignment changes with optimized performance
     *
     * @param Ticket[] $tickets Array of ticket models
     * @param string $teknisiNip NIP of the teknisi to assign to
     * @param string $performedBy NIP of person performing the action
     * @param string $notes Optional notes
     * @return void
     */
    public function createAssignmentNotifications(array $tickets, string $teknisiNip, string $performedBy, string $notes = ''): void
    {
        /** @var Ticket[] $tickets */
        DB::beginTransaction();
        try {
            $teknisiNotifications = [];
            $adminNotifications = [];

            $teknisi = Teknisi::where('nip', $teknisiNip)->first();
            if (!$teknisi) {
                Log::warning("Cannot find teknisi for bulk assignment notification", ['teknisi_nip' => $teknisiNip]);
                return;
            }

            // Pre-fetch all unique previous teknisi to avoid N+1 queries
            $oldTeknisiNips = collect($tickets)->pluck('assigned_teknisi_nip')->filter()->unique()->toArray();
            $oldTeknisis = Teknisi::whereIn('nip', $oldTeknisiNips)->get()->keyBy('nip');

            foreach ($tickets as $ticket) {
                // Notify newly assigned teknisi
                if ($teknisiNip !== $performedBy) {
                    $teknisiNotifications[] = [
                        'type' => Notification::TYPE_TICKET_ASSIGNED,
                        'notifiable_type' => Teknisi::class,
                        'notifiable_id' => $teknisi->getKey(),
                        'ticket_id' => $ticket->id,
                        'triggered_by_type' => 'admin_helpdesk',
                        'triggered_by_nip' => $performedBy,
                        'title' => 'New Ticket Assignment (Bulk)',
                        'message' => "Ticket {$ticket->ticket_number} has been assigned to you",
                        'priority' => Notification::PRIORITY_HIGH,
                        'channel' => Notification::CHANNEL_DATABASE,
                        'status' => Notification::STATUS_PENDING,
                        'data' => json_encode([
                            'ticket_number' => $ticket->ticket_number,
                            'ticket_title' => $ticket->title,
                            'priority' => $ticket->priority,
                            'bulk_action' => true,
                            'notes' => $notes,
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Notify previous assigned teknisi if exists
                if ($ticket->assigned_teknisi_nip && $ticket->assigned_teknisi_nip !== $teknisiNip && isset($oldTeknisis[$ticket->assigned_teknisi_nip])) {
                    $oldTeknisi = $oldTeknisis[$ticket->assigned_teknisi_nip];
                    if ($oldTeknisi->nip !== $performedBy) {
                        $teknisiNotifications[] = [
                            'type' => Notification::TYPE_TICKET_ASSIGNED,
                            'notifiable_type' => Teknisi::class,
                            'notifiable_id' => $oldTeknisi->getKey(),
                            'ticket_id' => $ticket->id,
                            'triggered_by_type' => 'admin_helpdesk',
                            'triggered_by_nip' => $performedBy,
                            'title' => 'Ticket Assignment Changed (Bulk)',
                            'message' => "Ticket {$ticket->ticket_number} has been reassigned from you",
                            'priority' => Notification::PRIORITY_MEDIUM,
                            'channel' => Notification::CHANNEL_DATABASE,
                            'status' => Notification::STATUS_PENDING,
                            'data' => json_encode([
                                'ticket_number' => $ticket->ticket_number,
                                'ticket_title' => $ticket->title,
                                'new_assigned_to' => $teknisi->name,
                                'bulk_action' => true,
                                'notes' => $notes,
                            ]),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            // Bulk insert notifications
            if (!empty($teknisiNotifications)) {
                Notification::insert($teknisiNotifications);
            }

            DB::commit();

            Log::info("Bulk assignment notifications created", [
                'teknisi_notifications' => count($teknisiNotifications),
                'assigned_teknisi' => $teknisi->name,
                'total_tickets' => count($tickets),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating bulk assignment notifications', [
                'error' => $e->getMessage(),
                'teknisi_nip' => $teknisiNip,
                'ticket_count' => count($tickets),
                'stack_trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Create notifications for bulk priority changes with optimized performance
     *
     * @param Ticket[] $tickets Array of ticket models
     * @param string|array<string, string> $oldPriorities Previous priority or array mapping ticket IDs to priorities
     * @param string $newPriority New priority level
     * @param string $performedBy NIP of person performing the action
     * @param string $notes Optional notes
     * @return void
     */
    public function createPriorityChangeNotifications(array $tickets, $oldPriorities, string $newPriority, string $performedBy, string $notes = ''): void
    {
        /** @var Ticket[] $tickets */
        /** @var string|array<string, string> $oldPriorities */
        DB::beginTransaction();
        try {
            $userNotifications = [];
            $teknisiNotifications = [];

            // Pre-fetch all unique users and teknisi to avoid N+1 queries
            $userNips = collect($tickets)->pluck('user_nip')->filter()->unique()->toArray();
            $teknisiNips = collect($tickets)->pluck('assigned_teknisi_nip')->filter()->unique()->toArray();

            $users = User::whereIn('nip', $userNips)->get()->keyBy('nip');
            $teknisis = Teknisi::whereIn('nip', $teknisiNips)->get()->keyBy('nip');

            foreach ($tickets as $ticket) {
                // Get the old priority for this specific ticket
                $ticketOldPriority = is_array($oldPriorities) ? ($oldPriorities[$ticket->id] ?? 'unknown') : $oldPriorities;

                // Notify ticket owner
                if ($ticket->user_nip !== $performedBy && isset($users[$ticket->user_nip])) {
                    $user = $users[$ticket->user_nip];
                    if ($user && $user->getKey()) {
                        $userNotifications[] = [
                            'type' => Notification::TYPE_TICKET_STATUS_CHANGED,
                            'notifiable_type' => User::class,
                            'notifiable_id' => $user->getKey(),
                            'ticket_id' => $ticket->id,
                            'triggered_by_type' => 'admin_helpdesk',
                            'triggered_by_nip' => $performedBy,
                            'title' => 'Ticket Priority Updated (Bulk)',
                            'message' => "Ticket {$ticket->ticket_number} priority changed from {$ticketOldPriority} to {$newPriority}",
                            'priority' => $newPriority === 'urgent' ? Notification::PRIORITY_HIGH : Notification::PRIORITY_MEDIUM,
                            'channel' => Notification::CHANNEL_DATABASE,
                            'status' => Notification::STATUS_PENDING,
                            'data' => json_encode([
                                'ticket_number' => $ticket->ticket_number,
                                'ticket_title' => $ticket->title,
                                'old_priority' => $ticketOldPriority,
                                'new_priority' => $newPriority,
                                'bulk_action' => true,
                                'notes' => $notes,
                            ]),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Notify assigned teknisi
                if ($ticket->assigned_teknisi_nip && $ticket->assigned_teknisi_nip !== $performedBy && isset($teknisis[$ticket->assigned_teknisi_nip])) {
                    $teknisi = $teknisis[$ticket->assigned_teknisi_nip];
                    if ($teknisi && $teknisi->getKey()) {
                        $teknisiNotifications[] = [
                            'type' => Notification::TYPE_TICKET_STATUS_CHANGED,
                            'notifiable_type' => Teknisi::class,
                            'notifiable_id' => $teknisi->getKey(),
                            'ticket_id' => $ticket->id,
                            'triggered_by_type' => 'admin_helpdesk',
                            'triggered_by_nip' => $performedBy,
                            'title' => 'Ticket Priority Updated (Bulk)',
                            'message' => "Ticket {$ticket->ticket_number} priority changed from {$ticketOldPriority} to {$newPriority}",
                            'priority' => $newPriority === 'urgent' ? Notification::PRIORITY_HIGH : Notification::PRIORITY_MEDIUM,
                            'channel' => Notification::CHANNEL_DATABASE,
                            'status' => Notification::STATUS_PENDING,
                            'data' => json_encode([
                                'ticket_number' => $ticket->ticket_number,
                                'ticket_title' => $ticket->title,
                                'old_priority' => $ticketOldPriority,
                                'new_priority' => $newPriority,
                                'bulk_action' => true,
                                'notes' => $notes,
                            ]),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }

            // Bulk insert notifications
            if (!empty($userNotifications)) {
                Notification::insert($userNotifications);
            }

            if (!empty($teknisiNotifications)) {
                Notification::insert($teknisiNotifications);
            }

            DB::commit();

            Log::info("Bulk priority change notifications created", [
                'user_notifications' => count($userNotifications),
                'teknisi_notifications' => count($teknisiNotifications),
                'total_tickets' => count($tickets),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating bulk priority change notifications', [
                'error' => $e->getMessage(),
                'ticket_count' => count($tickets),
                'stack_trace' => $e->getTraceAsString(),
            ]);
            // Re-throw the exception so the controller can handle it
            throw $e;
        }
    }

    /**
     * Create notifications for bulk ticket closure
     *
     * @param Ticket[] $tickets Array of ticket models
     * @param string $performedBy NIP of person performing the action
     * @param string $notes Optional notes
     * @return void
     */
    public function createClosureNotifications(array $tickets, string $performedBy, string $notes = ''): void
    {
        /** @var Ticket[] $tickets */
        DB::beginTransaction();
        try {
            $userNotifications = [];

            // Pre-fetch all unique users to avoid N+1 queries
            $userNips = collect($tickets)->pluck('user_nip')->filter()->unique()->toArray();
            $users = User::whereIn('nip', $userNips)->get()->keyBy('nip');

            foreach ($tickets as $ticket) {
                // Notify ticket owner
                if ($ticket->user_nip !== $performedBy && isset($users[$ticket->user_nip])) {
                    $userNotifications[] = [
                        'type' => Notification::TYPE_TICKET_STATUS_CHANGED,
                        'notifiable_type' => User::class,
                        'notifiable_id' => $users[$ticket->user_nip]->getKey(),
                        'ticket_id' => $ticket->id,
                        'triggered_by_type' => 'admin_helpdesk',
                        'triggered_by_nip' => $performedBy,
                        'title' => 'Ticket Closed (Bulk)',
                        'message' => "Ticket {$ticket->ticket_number} has been closed by admin",
                        'priority' => Notification::PRIORITY_MEDIUM,
                        'channel' => Notification::CHANNEL_DATABASE,
                        'status' => Notification::STATUS_PENDING,
                        'data' => json_encode([
                            'ticket_number' => $ticket->ticket_number,
                            'ticket_title' => $ticket->title,
                            'bulk_action' => true,
                            'notes' => $notes,
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Bulk insert notifications
            if (!empty($userNotifications)) {
                Notification::insert($userNotifications);
            }

            DB::commit();

            Log::info("Bulk closure notifications created", [
                'user_notifications' => count($userNotifications),
                'total_tickets' => count($tickets),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating bulk closure notifications', [
                'error' => $e->getMessage(),
                'ticket_count' => count($tickets),
                'stack_trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Invalidate dashboard metrics cache for affected users with comprehensive cache patterns
     *
     * @param Ticket[] $tickets Array of ticket models
     * @return void
     */
    public function invalidateAffectedUserCaches(array $tickets): void
    {
        /** @var Ticket[] $tickets */
        try {
            $userNips = [];
            $teknisiNips = [];

            foreach ($tickets as $ticket) {
                if ($ticket->user_nip) {
                    $userNips[] = $ticket->user_nip;
                }
                if ($ticket->assigned_teknisi_nip) {
                    $teknisiNips[] = $ticket->assigned_teknisi_nip;
                }
            }

            $uniqueUserNips = array_unique($userNips);
            $uniqueTeknisiNips = array_unique($teknisiNips);

            // Clear user-specific caches
            foreach ($uniqueUserNips as $userNip) {
                $this->clearUserCaches($userNip);
            }

            // Clear teknisi-specific caches
            foreach ($uniqueTeknisiNips as $teknisiNip) {
                $this->clearTeknisiCaches($teknisiNip);
            }

            // Clear global dashboard caches
            $this->clearGlobalCaches();

            Log::info("Dashboard caches invalidated for bulk action", [
                'affected_users' => count($uniqueUserNips),
                'affected_teknisi' => count($uniqueTeknisiNips),
                'total_tickets' => count($tickets),
            ]);

        } catch (\Exception $e) {
            Log::error('Error invalidating dashboard caches', [
                'error' => $e->getMessage(),
                'ticket_count' => count($tickets),
                'stack_trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Clear all caches for a specific user
     *
     * @param string $userNip NIP of the user
     * @return void
     */
    private function clearUserCaches(string $userNip): void
    {
        /** @var string $userNip */
        $cacheKeys = [
            "user_metrics_{$userNip}",
            "user_tickets_{$userNip}",
            "user_dashboard_{$userNip}",
            "user_notifications_{$userNip}",
            "user_sla_{$userNip}",
        ];

        foreach ($cacheKeys as $key) {
            \Illuminate\Support\Facades\Cache::forget($key);
        }
    }

    /**
     * Clear all caches for a specific teknisi
     *
     * @param string $teknisiNip NIP of the teknisi
     * @return void
     */
    private function clearTeknisiCaches(string $teknisiNip): void
    {
        /** @var string $teknisiNip */
        $cacheKeys = [
            "teknisi_metrics_{$teknisiNip}",
            "teknisi_tickets_{$teknisiNip}",
            "teknisi_dashboard_{$teknisiNip}",
            "teknisi_notifications_{$teknisiNip}",
            "teknisi_performance_{$teknisiNip}",
        ];

        foreach ($cacheKeys as $key) {
            \Illuminate\Support\Facades\Cache::forget($key);
        }
    }

    /**
     * Clear global dashboard and system caches
     */
    private function clearGlobalCaches(): void
    {
        $globalCacheKeys = [
            'admin_dashboard_metrics',
            'system_statistics',
            'active_technicians',
            'ticket_statistics',
            'sla_metrics',
        ];

        foreach ($globalCacheKeys as $key) {
            \Illuminate\Support\Facades\Cache::forget($key);
        }
    }

    /**
     * Execute bulk operation with transaction management
     *
     * @param callable $operation Operation to execute
     * @param Ticket[] $tickets Array of ticket models
     * @param string $operationName Name of the operation
     * @return array Operation results
     */
    public function executeBulkOperation(callable $operation, array $tickets, string $operationName): array
    {
        /** @var callable $operation */
        /** @var Ticket[] $tickets */
        /** @var string $operationName */
        DB::beginTransaction();
        try {
            $results = $operation($tickets);
            
            // If operation was successful, commit the transaction
            DB::commit();
            
            Log::info("Bulk operation completed successfully", [
                'operation' => $operationName,
                'ticket_count' => count($tickets),
                'successful_results' => count(array_filter($results, function($r) { return $r['success'] ?? false; })),
            ]);
            
            return $results;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Bulk operation failed", [
                'operation' => $operationName,
                'error' => $e->getMessage(),
                'ticket_count' => count($tickets),
                'stack_trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Batch process notifications for better performance
     *
     * @param array $notifications Array of notification data
     * @param int $batchSize Batch size for processing
     * @return void
     */
    public function batchCreateNotifications(array $notifications, int $batchSize = 100): void
    {
        /** @var array $notifications */
        /** @var int $batchSize */
        $chunks = array_chunk($notifications, $batchSize);
        
        foreach ($chunks as $chunk) {
            try {
                DB::beginTransaction();
                Notification::insert($chunk);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to create notification batch', [
                    'batch_size' => count($chunk),
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }
}