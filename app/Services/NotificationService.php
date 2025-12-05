<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Teknisi;
use App\Models\AdminHelpdesk;
use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Find user by NIP across all user models
     *
     * @param string $userNip
     * @return \Illuminate\Database\Eloquent\Model|null User-type model instance or null if not found
     */
    private function findUserByNip(string $userNip)
    {
        // Try each user model in priority order
        $user = \App\Models\AdminHelpdesk::where('nip', $userNip)->first();
        if ($user) return $user;

        $user = \App\Models\AdminAplikasi::where('nip', $userNip)->first();
        if ($user) return $user;

        $user = \App\Models\Teknisi::where('nip', $userNip)->first();
        if ($user) return $user;

        $user = User::where('nip', $userNip)->first();
        if ($user) return $user;

        return null;
    }

    /**
     * Create a new notification record.
     *
     * @param array $data Notification data including user_id, type, title, message, etc.
     * @return array Array with 'success' boolean and 'notification' or 'errors'
     */
    public function createNotification(array $data): array
    {
        // Validate required data
        $validation = $this->validateNotificationData($data);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors'],
            ];
        }

        try {
            DB::beginTransaction();

            // Handle polymorphic notifiable relationship
            $notifiableData = $this->prepareNotifiableData($data);

            // Create notification data array
            $notificationData = [
                'type' => $data['type'],
                'notifiable_type' => $notifiableData['type'],
                'notifiable_id' => $notifiableData['id'],
                'title' => $data['title'],
                'message' => $data['message'],
                'priority' => $data['priority'] ?? Notification::PRIORITY_MEDIUM,
                'channel' => $data['channel'] ?? Notification::CHANNEL_DATABASE,
                'status' => Notification::STATUS_PENDING,
                'action_url' => $data['action_url'] ?? null,
                'icon' => $data['icon'] ?? null,
                'data' => isset($data['data']) ? (is_array($data['data']) ? $data['data'] : json_decode($data['data'], true)) : [],
                'is_real_time' => $data['is_real_time'] ?? false,
            ];

            // Add optional fields if provided
            if (isset($data['ticket_id'])) {
                $notificationData['ticket_id'] = $data['ticket_id'];
            }

            if (isset($data['triggered_by_type']) && isset($data['triggered_by_nip'])) {
                $notificationData['triggered_by_type'] = Notification::mapClassToTriggeredByType($data['triggered_by_type']);
                $notificationData['triggered_by_nip'] = $data['triggered_by_nip'];
            }

            if (isset($data['scheduled_at'])) {
                $notificationData['scheduled_at'] = $data['scheduled_at'];
            }

            if (isset($data['expires_at'])) {
                $notificationData['expires_at'] = $data['expires_at'];
            }

            if (isset($data['metadata'])) {
                $notificationData['metadata'] = is_array($data['metadata']) ? $data['metadata'] : json_decode($data['metadata'], true);
            }

            // Create notification
            $notification = Notification::create($notificationData);

            DB::commit();

            return [
                'success' => true,
                'notification' => $notification->load(['ticket']),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'errors' => ['Failed to create notification: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Get all notifications (for Admin Helpdesk management).
     *
     * @param array $filters Optional filters
     * @param int $perPage Number of notifications per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllNotifications(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Notification::query()->with(['ticket']);
        
        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Apply filters to notification query
     */
    private function applyFilters($query, array $filters)
    {
        if (isset($filters['type']) && $filters['type']) {
            $type = $filters['type'];
            
            if ($type === 'ticket') {
                $query->where(function($q) {
                    $q->where('type', 'like', 'ticket_%')
                      ->orWhereNotNull('ticket_id');
                });
            } elseif ($type === 'ticket_updated') {
                $query->whereIn('type', [
                    'ticket_status_changed',
                    'ticket_priority_changed',
                    'ticket_comment_added',
                    'ticket_unassigned',
                    'ticket_first_response',
                    'ticket_closed',
                    'ticket_escalated'
                ]);
            } elseif ($type === 'system') {
                // Comprehensive system filter
                $query->where(function($q) {
                    $q->where('type', 'system')
                      ->orWhere('type', 'like', 'system_%')
                      ->orWhere('type', 'deadline_reminder')
                      ->orWhere('type', 'like', 'application_%');
                });
            } elseif ($type === 'user') {
                $query->where(function($q) {
                    $q->where('type', 'user')
                      ->orWhere('type', 'like', 'user_%');
                });
            } elseif ($type === 'announcement') {
                // Manual announcements only
                $query->where('type', 'announcement');
            } else {
                // Fallback for specific type search
                $query->where('type', $type);
            }
        }

        if (isset($filters['priority']) && $filters['priority']) {
            $query->byPriority($filters['priority']);
        }

        if (isset($filters['status']) && $filters['status']) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['is_read']) && $filters['is_read'] !== null && $filters['is_read'] !== '') {
            if ($filters['is_read']) {
                $query->read();
            } else {
                $query->unread();
            }
        }

        if (isset($filters['ticket_id']) && $filters['ticket_id']) {
            $query->byTicket($filters['ticket_id']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $query->search($filters['search']);
        }

        if (isset($filters['urgent']) && $filters['urgent']) {
            $query->urgent();
        }

        if (isset($filters['recent']) && $filters['recent']) {
            $hours = $filters['recent_hours'] ?? 24;
            $query->recent($hours);
        }

        if (isset($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        if (isset($filters['recipient']) && $filters['recipient']) {
             $recipient = $filters['recipient'];
             if ($recipient === 'users') {
                 $query->where('notifiable_type', 'App\Models\User');
             } elseif ($recipient === 'teknisi') {
                 $query->where('notifiable_type', 'App\Models\Teknisi');
             } elseif ($recipient === 'admins') {
                 $query->whereIn('notifiable_type', ['App\Models\AdminHelpdesk', 'App\Models\AdminAplikasi']);
             }
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        if ($sortBy === 'priority') {
            $query->orderByPriority($sortDirection);
        } elseif ($sortBy === 'read_status') {
            $query->orderByReadStatus($sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }
        
        return $query;
    }

    /**
     * Get notifications for a specific user.
     *
     * @param string $userNip User NIP
     * @param array $filters Optional filters (type, priority, status, etc.)
     * @param int $perPage Number of notifications per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserNotifications(string $userNip, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // Find user by NIP across all user models
        $user = $this->findUserByNip($userNip);
        if (!$user) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        $query = Notification::byUser($user)
            ->with(['ticket']);

        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get notifications for a teknisi.
     *
     * @param string $teknisiNip Teknisi NIP
     * @param array $filters Optional filters
     * @param int $perPage Number of notifications per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getTeknisiNotifications(string $teknisiNip, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // Find teknisi by NIP
        $teknisi = Teknisi::where('nip', $teknisiNip)->first();
        if (!$teknisi) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        $query = Notification::byTeknisi($teknisi)
            ->with(['ticket']);

        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Get notifications for an admin aplikasi (only notifications related to their managed applications).
     *
     * @param string $adminAplikasiNip Admin Aplikasi NIP
     * @param array $filters Optional filters
     * @param int $perPage Number of notifications per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAdminAplikasiNotifications(string $adminAplikasiNip, array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        // Find admin aplikasi by NIP
        $adminAplikasi = \App\Models\AdminAplikasi::where('nip', $adminAplikasiNip)->first();
        if (!$adminAplikasi) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        // Get all application IDs managed by this admin aplikasi
        $managedAppIds = [];
        
        // Method 1: From managed_applications JSON field
        if ($adminAplikasi->managed_applications && is_array($adminAplikasi->managed_applications)) {
            $managedAppIds = $adminAplikasi->managed_applications;
        }
        
        // Method 2: Get applications where this admin is assigned as admin_aplikasi_nip
        $directAppIds = \App\Models\Aplikasi::where('admin_aplikasi_nip', $adminAplikasiNip)
            ->pluck('id')
            ->toArray();
        
        // Method 3: Get applications where this admin is assigned as backup_admin_nip
        $backupAppIds = \App\Models\Aplikasi::where('backup_admin_nip', $adminAplikasiNip)
            ->pluck('id')
            ->toArray();
        
        // Combine all sources
        $managedAppIds = array_unique(array_merge($managedAppIds, $directAppIds, $backupAppIds));

        // If no managed applications, return empty result
        if (empty($managedAppIds)) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        // Build query: notifications where ticket belongs to managed applications
        // OR notifications directly addressed to this admin aplikasi
        $query = Notification::query()
            ->with(['ticket'])
            ->where(function($q) use ($managedAppIds, $adminAplikasi) {
                // Notifications for tickets in managed applications
                $q->whereHas('ticket', function($ticketQuery) use ($managedAppIds) {
                    $ticketQuery->whereIn('aplikasi_id', $managedAppIds);
                })
                // OR notifications directly to this admin aplikasi
                ->orWhere(function($directQuery) use ($adminAplikasi) {
                    $directQuery->where('notifiable_type', get_class($adminAplikasi))
                                ->where('notifiable_id', $adminAplikasi->nip);
                });
            });

        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Mark individual notification as read.
     *
     * @param int $notificationId Notification ID
     * @param string $userNip User NIP (for authorization)
     * @return array Array with 'success' boolean and 'notification' or 'errors'
     */
    public function markAsRead(int $notificationId, string $userNip): array
    {
        try {
            $notification = Notification::findOrFail($notificationId);

            // Check if user owns this notification
            if (!$this->userOwnsNotification($notification, $userNip)) {
                return [
                    'success' => false,
                    'errors' => ['You do not have permission to mark this notification as read'],
                ];
            }

            // Mark as read
            $marked = $notification->markAsRead();

            if (!$marked) {
                throw new \Exception('Failed to mark notification as read');
            }

            return [
                'success' => true,
                'notification' => $notification->fresh(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to mark notification as read: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Delete a notification.
     *
     * @param int $notificationId Notification ID
     * @param string $userNip User NIP (for authorization)
     * @return array Array with 'success' boolean and error details if any
     */
    public function deleteNotification(int $notificationId, string $userNip): array
    {
        try {
            $notification = Notification::find($notificationId);

            if (!$notification) {
                return [
                    'success' => false,
                    'error_type' => 'not_found',
                    'message' => 'Notification not found'
                ];
            }

            // Check if user owns this notification
            if (!$this->userOwnsNotification($notification, $userNip)) {
                return [
                    'success' => false,
                    'error_type' => 'unauthorized',
                    'message' => 'You do not have permission to delete this notification'
                ];
            }

            $notification->delete();

            return [
                'success' => true
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete notification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mark all notifications as read for a user.
     *
     * @param string $userNip User NIP
     * @param string|null $userType User type ('user' or 'teknisi')
     * @return array Array with 'success' boolean and 'marked_count' or 'errors'
     */
    public function markAllAsRead(string $userNip, ?string $userType = 'user'): array
    {
        try {
            if ($userType === 'teknisi') {
                $teknisi = Teknisi::where('nip', $userNip)->first();
                if (!$teknisi) {
                    return [
                        'success' => false,
                        'errors' => ['Teknisi not found'],
                    ];
                }
                $markedCount = Notification::byTeknisi($teknisi)->unread()->update([
                    'read_at' => Carbon::now(),
                    'status' => Notification::STATUS_READ,
                ]);
            } else {
                $user = $this->findUserByNip($userNip);
                if (!$user) {
                    return [
                        'success' => false,
                        'errors' => ['User not found'],
                    ];
                }
                $markedCount = Notification::byUser($user)->unread()->update([
                    'read_at' => Carbon::now(),
                    'status' => Notification::STATUS_READ,
                ]);
            }

            return [
                'success' => true,
                'marked_count' => $markedCount,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to mark all notifications as read: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Get count of unread notifications for a user.
     *
     * @param string $userNip User NIP
     * @param string|null $userType User type ('user' or 'teknisi')
     * @return array Array with 'success' boolean and 'count' or 'errors'
     */
    public function getUnreadCount(string $userNip, ?string $userType = 'user'): array
    {
        try {
            if ($userType === 'teknisi') {
                $teknisi = Teknisi::where('nip', $userNip)->first();
                if (!$teknisi) {
                    return [
                        'success' => false,
                        'errors' => ['Teknisi not found'],
                    ];
                }
                $count = Notification::byTeknisi($teknisi)->unread()->count();
            } else {
                $user = $this->findUserByNip($userNip);
                if (!$user) {
                    return [
                        'success' => false,
                        'errors' => ['User not found'],
                    ];
                }
                $count = Notification::byUser($user)->unread()->count();
            }

            return [
                'success' => true,
                'count' => $count,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to get unread count: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Get notification statistics for a user.
     *
     * @param string $userNip User NIP
     * @param string|null $userType User type ('user' or 'teknisi')
     * @return array Array with 'success' boolean and 'stats' or 'errors'
     */
    public function getNotificationStats(string $userNip, ?string $userType = 'user'): array
    {
        try {
            if ($userType === 'teknisi') {
                $teknisi = Teknisi::where('nip', $userNip)->first();
                if (!$teknisi) {
                    return [
                        'success' => false,
                        'errors' => ['Teknisi not found'],
                    ];
                }
                $stats = Notification::getUserStats($teknisi);
            } else {
                $user = $this->findUserByNip($userNip);
                if (!$user) {
                    return [
                        'success' => false,
                        'errors' => ['User not found'],
                    ];
                }
                $stats = Notification::getUserStats($user);
            }

            return [
                'success' => true,
                'stats' => $stats,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to get notification stats: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Create a ticket-related notification using the model's static methods.
     *
     * @param string $type Notification type (ticket_assigned, ticket_status_changed, etc.)
     * @param mixed $ticket Ticket model instance
     * @param mixed $notifiable User or Teknisi model instance
     * @param mixed $triggeredBy User who triggered the notification
     * @param array $additionalData Additional data for specific notification types
     * @return array Array with 'success' boolean and 'notification' or 'errors'
     */
    public function createTicketNotification(string $type, $ticket, $notifiable, $triggeredBy = null, array $additionalData = []): array
    {
        try {
            $notification = null;

            switch ($type) {
                case Notification::TYPE_TICKET_ASSIGNED:
                    $notification = Notification::createTicketAssigned($ticket, $notifiable, $triggeredBy);
                    break;

                case Notification::TYPE_TICKET_STATUS_CHANGED:
                    $notification = Notification::createTicketStatusChanged(
                        $ticket,
                        $additionalData['old_status'] ?? $ticket->status,
                        $notifiable,
                        $triggeredBy
                    );
                    break;

                case Notification::TYPE_TICKET_COMMENT_ADDED:
                    $comment = $additionalData['comment'] ?? null;
                    if (!$comment) {
                        return [
                            'success' => false,
                            'errors' => ['Comment data required for comment notifications'],
                        ];
                    }
                    $notification = Notification::createTicketCommentAdded($comment, $notifiable, $triggeredBy);
                    break;

                case Notification::TYPE_TICKET_DUE_SOON:
                    $notification = Notification::createTicketDueSoon($ticket, $notifiable, $triggeredBy);
                    break;

                case Notification::TYPE_TICKET_OVERDUE:
                    $notification = Notification::createTicketOverdue($ticket, $notifiable, $triggeredBy);
                    break;

                case Notification::TYPE_TICKET_RESOLVED:
                    // Custom implementation for resolved notifications
                    $notification = Notification::create([
                        'type' => Notification::TYPE_TICKET_RESOLVED,
                        'notifiable_type' => get_class($notifiable),
                        'notifiable_id' => $notifiable->getKey(),
                        'ticket_id' => $ticket->id,
                        'triggered_by_type' => $triggeredBy ? get_class($triggeredBy) : null,
                        'triggered_by_nip' => $triggeredBy ? $triggeredBy->nip : null,
                        'title' => 'Ticket Resolved',
                        'message' => "Ticket #{$ticket->ticket_number} has been resolved",
                        'priority' => Notification::PRIORITY_MEDIUM,
                        'channel' => Notification::CHANNEL_DATABASE,
                        'status' => Notification::STATUS_PENDING,
                        'action_url' => "/tickets/{$ticket->id}",
                        'icon' => 'check-circle',
                        'data' => [
                            'ticket_number' => $ticket->ticket_number,
                            'ticket_title' => $ticket->title,
                            'resolution_time' => $ticket->formatted_resolution_time ?? null,
                        ],
                    ]);
                    break;

                default:
                    return [
                        'success' => false,
                        'errors' => ['Unsupported notification type: ' . $type],
                    ];
            }

            return [
                'success' => true,
                'notification' => $notification,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to create ticket notification: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Validate notification data.
     *
     * @param array $data Notification data to validate
     * @return array Array with 'valid' boolean and 'errors' array
     */
    private function validateNotificationData(array $data): array
    {
        $rules = [
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ];

        // Validate notifiable (user or teknisi)
        if (!isset($data['notifiable_type']) || !isset($data['notifiable_id'])) {
            $rules['user_nip'] = 'required_without:teknisi_nip|string';
            $rules['teknisi_nip'] = 'required_without:user_nip|string';
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->all(),
            ];
        }

        return [
            'valid' => true,
            'errors' => [],
        ];
    }

    /**
     * Prepare notifiable data for polymorphic relationship.
     *
     * @param array $data Input data
     * @return array Array with 'type' and 'id' keys
     */
    private function prepareNotifiableData(array $data): array
    {
        // If notifiable_type and notifiable_id are provided directly
        if (isset($data['notifiable_type']) && isset($data['notifiable_id'])) {
            return [
                'type' => $data['notifiable_type'],
                'id' => $data['notifiable_id'],
            ];
        }

        // Determine from user_nip or teknisi_nip
        if (isset($data['user_nip'])) {
            $user = $this->findUserByNip($data['user_nip']);
            if (!$user) {
                throw new \Exception('User not found with NIP: ' . $data['user_nip']);
            }
            return [
                'type' => get_class($user),
                'id' => $user->getKey(),
            ];
        }

        if (isset($data['teknisi_nip'])) {
            $teknisi = Teknisi::where('nip', $data['teknisi_nip'])->first();
            if (!$teknisi) {
                throw new \Exception('Teknisi not found with NIP: ' . $data['teknisi_nip']);
            }
            return [
                'type' => Teknisi::class,
                'id' => $teknisi->getKey(),
            ];
        }

        throw new \Exception('No valid notifiable data provided');
    }

    /**
     * Check if user owns the notification.
     *
     * @param Notification $notification Notification instance
     * @param string $userNip User NIP
     * @return bool
     */
    private function userOwnsNotification(Notification $notification, string $userNip): bool
    {
        // Since all user models (User, Teknisi, AdminHelpdesk, AdminAplikasi) use NIP as primary key,
        // and notifiable_id stores this NIP, we can directly compare them.
        // This avoids issues where findUserByNip returns a model of a different type (e.g. AdminHelpdesk)
        // than the notification's notifiable_type (e.g. User) when the NIP is the same.
        
        // Note: We treat ownership purely by ID match. The system design allows an AdminHelpdesk 
        // who is also a User (same NIP) to manage notifications for either persona.
        return (string) $notification->notifiable_id === (string) $userNip;
    }

    /**
     * Notify Admin Helpdesk users when a new ticket is created
     *
     * @param Ticket $ticket The ticket that was created
     * @return array Array with 'success' boolean and 'notifications_created' count or 'errors'
     */
    public function notifyTicketCreated(Ticket $ticket): array
    {
        try {
            // Get all active Admin Helpdesk users
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $notificationsCreated = 0;

            foreach ($adminHelpdesks as $admin) {
                $notificationData = [
                    'type' => 'ticket_created',
                    'title' => 'New Ticket Created',
                    'message' => "A new ticket #{$ticket->ticket_number} has been created by {$ticket->user->name}. Priority: {$ticket->priority_label}. Application: " . ($ticket->aplikasi->name ?? 'Unknown') . ".",
                    'priority' => $this->mapTicketPriorityToNotificationPriority($ticket->priority),
                    'channel' => Notification::CHANNEL_DATABASE,
                    'status' => Notification::STATUS_PENDING,
                    'action_url' => "/tickets/{$ticket->id}",
                    'icon' => 'plus-circle',
                    'ticket_id' => $ticket->id,
                    'user_nip' => $admin->nip,
                    'triggered_by_type' => get_class($ticket->user),
                    'triggered_by_nip' => $ticket->user->nip,
                    'data' => [
                        'ticket_number' => $ticket->ticket_number,
                        'ticket_title' => $ticket->title,
                        'ticket_priority' => $ticket->priority,
                        'ticket_category' => $ticket->kategoriMasalah->name ?? 'Unknown',
                        'ticket_application' => $ticket->aplikasi->name ?? 'Unknown',
                        'user_name' => $ticket->user->name,
                        'user_department' => $ticket->user->department ?? 'Unknown',
                        'created_at' => $ticket->created_at->toISOString(),
                    ],
                ];

                $result = $this->createNotification($notificationData);
                if ($result['success']) {
                    $notificationsCreated++;
                }
            }

            // REMOVED: Real-time broadcasting infrastructure
            // Database notifications are created above, no WebSocket broadcasting needed

            return [
                'success' => true,
                'notifications_created' => $notificationsCreated,
                'admins_notified' => $adminHelpdesks->count(),
                // 'broadcasted' => true, // Removed - no longer broadcasting
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to notify admins about ticket creation: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Notify assigned teknisi when ticket is assigned
     *
     * @param Ticket $ticket The ticket that was assigned
     * @return array Array with 'success' boolean and 'notification' or 'errors'
     */
    public function notifyTicketAssigned(Ticket $ticket): array
    {
        try {
            if (!$ticket->assignedTeknisi) {
                return [
                    'success' => false,
                    'errors' => ['No teknisi assigned to ticket'],
                ];
            }

            $teknisi = $ticket->assignedTeknisi;
            $urgencyLevel = $this->getUrgencyLevel($ticket);

            $notificationData = [
                'type' => Notification::TYPE_TICKET_ASSIGNED,
                'title' => 'New Ticket Assigned',
                'message' => "Ticket #{$ticket->ticket_number} has been assigned to you. {$urgencyLevel} Priority: {$ticket->priority_label}. Application: " . ($ticket->aplikasi->name ?? 'Unknown') . ". Please review and take appropriate action.",
                'priority' => $this->mapTicketPriorityToNotificationPriority($ticket->priority),
                'channel' => Notification::CHANNEL_DATABASE,
                'status' => Notification::STATUS_PENDING,
                'action_url' => "/tickets/{$ticket->id}",
                'icon' => 'user-plus',
                'ticket_id' => $ticket->id,
                'teknisi_nip' => $teknisi->nip,
                'triggered_by_type' => $ticket->assignedBy ? get_class($ticket->assignedBy) : null,
                'triggered_by_nip' => $ticket->assignedBy ? $ticket->assignedBy->nip : null,
                'data' => [
                    'ticket_number' => $ticket->ticket_number,
                    'ticket_title' => $ticket->title,
                    'ticket_priority' => $ticket->priority,
                    'ticket_category' => $ticket->kategoriMasalah->name ?? 'Unknown',
                    'ticket_application' => $ticket->aplikasi->name ?? 'Unknown',
                    'user_name' => $ticket->user->name,
                    'user_department' => $ticket->user->department ?? 'Unknown',
                    'assigned_by' => $ticket->assignedBy->name ?? 'System',
                    'assigned_at' => $ticket->updated_at->toISOString(),
                    'urgency_level' => $urgencyLevel,
                    'sla_deadline' => $ticket->getSlaDeadline()->toISOString(),
                ],
            ];

            $result = $this->createNotification($notificationData);

            // REMOVED: Real-time broadcasting infrastructure
            // Database notification created above, no WebSocket broadcasting needed

            if ($result['success']) {
                return [
                    'success' => true,
                    'notification' => $result['notification'],
                    'teknisi_notified' => $teknisi->name,
                    // 'broadcasted' => true, // Removed - no longer broadcasting
                ];
            } else {
                return $result;
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to notify teknisi about ticket assignment: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Notify ticket creator when status changes
     *
     * @param Ticket $ticket The ticket with status change
     * @param string $oldStatus The previous status
     * @param string $newStatus The new status
     * @return array Array with 'success' boolean and 'notification' or 'errors'
     */
    public function notifyStatusChanged(Ticket $ticket, string $oldStatus, string $newStatus): array
    {
        try {
            if (!$ticket->user) {
                return [
                    'success' => false,
                    'errors' => ['No user associated with ticket'],
                ];
            }

            $user = $ticket->user;
            $statusChangeMessage = $this->generateStatusChangeMessage($oldStatus, $newStatus, $ticket);

            $notificationData = [
                'type' => Notification::TYPE_TICKET_STATUS_CHANGED,
                'title' => 'Ticket Status Updated',
                'message' => $statusChangeMessage,
                'priority' => $this->getStatusChangePriority($newStatus),
                'channel' => Notification::CHANNEL_DATABASE,
                'status' => Notification::STATUS_PENDING,
                'action_url' => "/tickets/{$ticket->id}",
                'icon' => 'refresh',
                'ticket_id' => $ticket->id,
                'user_nip' => $user->nip,
                'data' => [
                    'ticket_number' => $ticket->ticket_number,
                    'ticket_title' => $ticket->title,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'old_status_label' => $this->getStatusLabel($oldStatus),
                    'new_status_label' => $this->getStatusLabel($newStatus),
                    'changed_by' => $ticket->assignedTeknisi->name ?? $ticket->assignedBy->name ?? 'System',
                    'changed_at' => $ticket->updated_at->toISOString(),
                    'resolution_time' => $ticket->resolution_time_minutes ? "{$ticket->resolution_time_minutes} minutes" : null,
                ],
            ];

            $result = $this->createNotification($notificationData);

            // REMOVED: Real-time broadcasting infrastructure
            // Database notification created above, no WebSocket broadcasting needed

            if ($result['success']) {
                return [
                    'success' => true,
                    'notification' => $result['notification'],
                    'user_notified' => $user->name,
                    // 'broadcasted' => true, // Removed - no longer broadcasting
                ];
            } else {
                return $result;
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to notify user about status change: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Notify relevant parties when new comment is added
     *
     * @param TicketComment $comment The comment that was added
     * @return array Array with 'success' boolean and 'notifications_created' count or 'errors'
     */
    public function notifyCommentAdded(TicketComment $comment): array
    {
        try {
            $ticket = $comment->ticket;
            $commenter = $comment->commenter;
            $notificationsCreated = 0;

            // Notify ticket creator if not the commenter
            if ($ticket->user && $ticket->user->nip !== $comment->commenter_nip) {
                $result = $this->createCommentNotification($ticket->user, $comment, 'user');
                if ($result['success']) {
                    $notificationsCreated++;
                }
            }

            // Notify assigned teknisi if not the commenter
            if ($ticket->assignedTeknisi && $ticket->assignedTeknisi->nip !== $comment->commenter_nip) {
                $result = $this->createCommentNotification($ticket->assignedTeknisi, $comment, 'teknisi');
                if ($result['success']) {
                    $notificationsCreated++;
                }
            }

            // Notify admin helpdesk for internal comments or technical issues
            if ($comment->is_internal || $comment->type === TicketComment::TYPE_TECHNICAL) {
                $adminHelpdesks = AdminHelpdesk::active()->get();

                foreach ($adminHelpdesks as $admin) {
                    $result = $this->createCommentNotification($admin, $comment, 'admin');
                    if ($result['success']) {
                        $notificationsCreated++;
                    }
                }
            }

            // REMOVED: Real-time broadcasting infrastructure
            // Database notifications created above, no WebSocket broadcasting needed

            return [
                'success' => true,
                'notifications_created' => $notificationsCreated,
                'comment_id' => $comment->id,
                // 'broadcasted' => true, // Removed - no longer broadcasting
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to notify about comment: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Notify ticket creator when ticket is resolved
     *
     * @param Ticket $ticket The ticket that was resolved
     * @return array Array with 'success' boolean and 'notification' or 'errors'
     */
    public function notifyTicketResolved(Ticket $ticket): array
    {
        try {
            if (!$ticket->user) {
                return [
                    'success' => false,
                    'errors' => ['No user associated with ticket'],
                ];
            }

            $user = $ticket->user;
            $resolutionSummary = $this->generateResolutionSummary($ticket);

            $notificationData = [
                'type' => Notification::TYPE_TICKET_RESOLVED,
                'title' => 'Ticket Resolved',
                'message' => "Great news! Your ticket #{$ticket->ticket_number} has been resolved. {$resolutionSummary} Please take a moment to provide feedback and rate our service.",
                'priority' => Notification::PRIORITY_MEDIUM,
                'channel' => Notification::CHANNEL_DATABASE,
                'status' => Notification::STATUS_PENDING,
                'action_url' => "/tickets/{$ticket->id}",
                'icon' => 'check-circle',
                'ticket_id' => $ticket->id,
                'user_nip' => $user->nip,
                'data' => [
                    'ticket_number' => $ticket->ticket_number,
                    'ticket_title' => $ticket->title,
                    'resolution_time' => $ticket->formatted_resolution_time,
                    'resolved_by' => $ticket->assignedTeknisi->name ?? 'Support Team',
                    'resolved_at' => $ticket->resolved_at->toISOString(),
                    'resolution_notes' => $ticket->resolution_notes,
                    'feedback_url' => "/tickets/{$ticket->id}/feedback",
                    'rating_url' => "/tickets/{$ticket->id}/rate",
                ],
            ];

            $result = $this->createNotification($notificationData);

            if ($result['success']) {
                return [
                    'success' => true,
                    'notification' => $result['notification'],
                    'user_notified' => $user->name,
                ];
            } else {
                return $result;
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to notify user about ticket resolution: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Helper method to create comment notification for specific user type
     */
    private function createCommentNotification($notifiable, TicketComment $comment, string $userType): array
    {
        $ticket = $comment->ticket;
        $commentPreview = substr(strip_tags($comment->comment), 0, 100);
        if (strlen($comment->comment) > 100) {
            $commentPreview .= '...';
        }

        $notificationData = [
            'type' => Notification::TYPE_TICKET_COMMENT_ADDED,
            'title' => 'New Comment Added',
            'message' => "New {$comment->type_label} comment added to ticket #{$ticket->ticket_number} by {$comment->commenter_name}: {$commentPreview}",
            'priority' => $comment->is_internal ? Notification::PRIORITY_HIGH : Notification::PRIORITY_MEDIUM,
            'channel' => Notification::CHANNEL_DATABASE,
            'status' => Notification::STATUS_PENDING,
            'action_url' => "/tickets/{$ticket->id}#comment-{$comment->id}",
            'icon' => 'message-circle',
            'ticket_id' => $ticket->id,
            'data' => [
                'ticket_number' => $ticket->ticket_number,
                'ticket_title' => $ticket->title,
                'comment_id' => $comment->id,
                'comment_type' => $comment->type,
                'comment_preview' => $commentPreview,
                'commenter_name' => $comment->commenter_name,
                'commenter_role' => $comment->commenter_role,
                'is_internal' => $comment->is_internal,
                'visibility' => $comment->visibility,
                'commented_at' => $comment->created_at->toISOString(),
            ],
        ];

        // Set the appropriate user identifier based on type
        if ($userType === 'user') {
            $notificationData['user_nip'] = $notifiable->nip;
        } elseif ($userType === 'teknisi') {
            $notificationData['teknisi_nip'] = $notifiable->nip;
        } elseif ($userType === 'admin') {
            $notificationData['user_nip'] = $notifiable->nip; // AdminHelpdesk uses same field as User
        }

        return $this->createNotification($notificationData);
    }

    /**
     * Helper method to map ticket priority to notification priority
     */
    private function mapTicketPriorityToNotificationPriority(string $ticketPriority): string
    {
        return match($ticketPriority) {
            Ticket::PRIORITY_URGENT => Notification::PRIORITY_URGENT,
            Ticket::PRIORITY_HIGH => Notification::PRIORITY_HIGH,
            Ticket::PRIORITY_MEDIUM => Notification::PRIORITY_MEDIUM,
            Ticket::PRIORITY_LOW => Notification::PRIORITY_LOW,
            default => Notification::PRIORITY_MEDIUM,
        };
    }

    /**
     * Helper method to get urgency level based on ticket properties
     */
    private function getUrgencyLevel(Ticket $ticket): string
    {
        if ($ticket->priority === Ticket::PRIORITY_URGENT) {
            return 'Urgent attention required';
        } elseif ($ticket->isOverdue()) {
            return 'Overdue - immediate attention needed';
        } elseif ($ticket->priority === Ticket::PRIORITY_HIGH) {
            return 'High priority';
        } else {
            return 'Standard priority';
        }
    }

    /**
     * Helper method to generate status change message
     */
    private function generateStatusChangeMessage(string $oldStatus, string $newStatus, Ticket $ticket): string
    {
        $oldLabel = $this->getStatusLabel($oldStatus);
        $newLabel = $this->getStatusLabel($newStatus);

        $baseMessage = "Your ticket #{$ticket->ticket_number} status has changed from '{$oldLabel}' to '{$newLabel}'.";

        if ($newStatus === Ticket::STATUS_RESOLVED) {
            $baseMessage .= " The issue has been resolved";
            if ($ticket->resolution_time_minutes) {
                $baseMessage .= " in {$ticket->formatted_resolution_time}";
            }
            $baseMessage .= ".";
        } elseif ($newStatus === Ticket::STATUS_IN_PROGRESS) {
            $baseMessage .= " Our technical team is now working on your issue.";
        } elseif ($newStatus === Ticket::STATUS_WAITING_RESPONSE) {
            $baseMessage .= " We are waiting for your response to proceed.";
        }

        return $baseMessage;
    }

    /**
     * Helper method to get status label
     */
    private function getStatusLabel(string $status): string
    {
        return match($status) {
            Ticket::STATUS_OPEN => 'Open',
            Ticket::STATUS_IN_PROGRESS => 'In Progress',
            Ticket::STATUS_WAITING_RESPONSE => 'Waiting Response',
            Ticket::STATUS_RESOLVED => 'Resolved',
            Ticket::STATUS_CLOSED => 'Closed',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Helper method to get priority for status changes
     */
    private function getStatusChangePriority(string $newStatus): string
    {
        return match($newStatus) {
            Ticket::STATUS_RESOLVED => Notification::PRIORITY_HIGH, // Important good news
            Ticket::STATUS_CLOSED => Notification::PRIORITY_MEDIUM,
            Ticket::STATUS_WAITING_RESPONSE => Notification::PRIORITY_HIGH, // Needs user attention
            default => Notification::PRIORITY_MEDIUM,
        };
    }

    /**
     * Helper method to generate resolution summary
     */
    private function generateResolutionSummary(Ticket $ticket): string
    {
        $summary = '';

        if ($ticket->resolution_time_minutes) {
            $hours = floor($ticket->resolution_time_minutes / 60);
            $minutes = $ticket->resolution_time_minutes % 60;

            if ($hours > 0) {
                $summary .= "It took {$hours} hours and {$minutes} minutes to resolve your issue.";
            } else {
                $summary .= "It took {$minutes} minutes to resolve your issue.";
            }
        }

        if ($ticket->resolution_notes) {
            $summary .= " Resolution details: " . substr($ticket->resolution_notes, 0, 100);
            if (strlen($ticket->resolution_notes) > 100) {
                $summary .= '...';
            }
        }

        return $summary;
    }

    /**
     * Notify Admin Helpdesk when a teknisi requests reassignment
     *
     * @param Ticket $ticket The ticket for reassignment request
     * @param Teknisi $teknisi The teknisi requesting reassignment
     * @param string $reason The reason for reassignment request
     * @param string|null $suggestedTeknisiNip Optional suggested teknisi NIP
     * @return array Array with 'success' boolean and 'notifications_created' count or 'errors'
     */
    public function notifyReassignmentRequested(Ticket $ticket, Teknisi $teknisi, string $reason, ?string $suggestedTeknisiNip = null): array
    {
        try {
            // Get all active Admin Helpdesk users
            $adminHelpdesks = AdminHelpdesk::active()->get();
            $notificationsCreated = 0;

            // Get suggested teknisi info if provided
            $suggestedTeknisiName = null;
            if ($suggestedTeknisiNip) {
                $suggestedTeknisi = Teknisi::where('nip', $suggestedTeknisiNip)->first();
                $suggestedTeknisiName = $suggestedTeknisi ? $suggestedTeknisi->name : null;
            }

            foreach ($adminHelpdesks as $admin) {
                $message = "Teknisi {$teknisi->name} has requested reassignment for ticket #{$ticket->ticket_number}. Reason: {$reason}";
                if ($suggestedTeknisiName) {
                    $message .= " Suggested teknisi: {$suggestedTeknisiName}.";
                }

                $notificationData = [
                    'type' => 'reassignment_requested',
                    'title' => 'Reassignment Request',
                    'message' => $message,
                    'priority' => Notification::PRIORITY_HIGH,
                    'channel' => Notification::CHANNEL_DATABASE,
                    'status' => Notification::STATUS_PENDING,
                    'action_url' => "/admin/tickets-management/{$ticket->id}",
                    'icon' => 'user-switch',
                    'ticket_id' => $ticket->id,
                    'user_nip' => $admin->nip,
                    'triggered_by_type' => Teknisi::class,
                    'triggered_by_nip' => $teknisi->nip,
                    'data' => [
                        'ticket_number' => $ticket->ticket_number,
                        'ticket_title' => $ticket->title,
                        'ticket_priority' => $ticket->priority,
                        'requesting_teknisi_nip' => $teknisi->nip,
                        'requesting_teknisi_name' => $teknisi->name,
                        'reason' => $reason,
                        'suggested_teknisi_nip' => $suggestedTeknisiNip,
                        'suggested_teknisi_name' => $suggestedTeknisiName,
                        'requested_at' => now()->toISOString(),
                    ],
                ];

                $result = $this->createNotification($notificationData);
                if ($result['success']) {
                    $notificationsCreated++;
                }
            }

            return [
                'success' => true,
                'notifications_created' => $notificationsCreated,
                'admins_notified' => $adminHelpdesks->count(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to notify admins about reassignment request: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Create notifications for all users of a specific type
     *
     * @param array $notificationData The base notification data
     * @param string $recipientType Type of recipients ('users', 'teknisi', 'admin-helpdesk', 'admin-aplikasi')
     * @return array Array with 'success' boolean and 'notifications_created' count or 'errors'
     */
    public function createNotificationForRecipients(array $notificationData, string $recipientType): array
    {
        try {
            $notificationsCreated = 0;
            $errors = [];

            switch ($recipientType) {
                case 'users':
                    $recipients = \App\Models\User::active()->get();
                    foreach ($recipients as $recipient) {
                        $data = $notificationData;
                        $data['user_nip'] = $recipient->nip;
                        $result = $this->createNotification($data);
                        if ($result['success']) {
                            $notificationsCreated++;
                        } else {
                            $errors[] = $result['errors'];
                        }
                    }
                    break;

                case 'teknisi':
                    $recipients = \App\Models\Teknisi::active()->get();
                    foreach ($recipients as $recipient) {
                        $data = $notificationData;
                        $data['teknisi_nip'] = $recipient->nip;
                        $result = $this->createNotification($data);
                        if ($result['success']) {
                            $notificationsCreated++;
                        } else {
                            $errors[] = $result['errors'];
                        }
                    }
                    break;

                case 'admin-helpdesk':
                    $recipients = \App\Models\AdminHelpdesk::active()->get();
                    foreach ($recipients as $recipient) {
                        $data = $notificationData;
                        $data['user_nip'] = $recipient->nip; // AdminHelpdesk uses nip field like User
                        $result = $this->createNotification($data);
                        if ($result['success']) {
                            $notificationsCreated++;
                        } else {
                            $errors[] = $result['errors'];
                        }
                    }
                    break;

                case 'admin-aplikasi':
                    $recipients = \App\Models\AdminAplikasi::active()->get();
                    foreach ($recipients as $recipient) {
                        $data = $notificationData;
                        $data['user_nip'] = $recipient->nip;
                        $result = $this->createNotification($data);
                        if ($result['success']) {
                            $notificationsCreated++;
                        } else {
                            $errors[] = $result['errors'];
                        }
                    }
                    break;

                default:
                    return [
                        'success' => false,
                        'errors' => ['Invalid recipient type: ' . $recipientType],
                    ];
            }

            return [
                'success' => true,
                'notifications_created' => $notificationsCreated,
                'errors' => $errors,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to create notifications for recipients: ' . $e->getMessage()],
            ];
        }
    }
}