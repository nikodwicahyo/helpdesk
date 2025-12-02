<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    // Notification Types
    const TYPE_TICKET_ASSIGNED = 'ticket_assigned';
    const TYPE_TICKET_STATUS_CHANGED = 'ticket_status_changed';
    const TYPE_TICKET_COMMENT_ADDED = 'ticket_comment_added';
    const TYPE_TICKET_ESCALATED = 'ticket_escalated';
    const TYPE_TICKET_DUE_SOON = 'ticket_due_soon';
    const TYPE_TICKET_OVERDUE = 'ticket_overdue';
    const TYPE_TICKET_RESOLVED = 'ticket_resolved';
    const TYPE_SYSTEM_ANNOUNCEMENT = 'system_announcement';
    const TYPE_USER_MENTIONED = 'user_mentioned';
    const TYPE_DEADLINE_REMINDER = 'deadline_reminder';

    // Notification Priorities
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Notification Channels
    const CHANNEL_DATABASE = 'database';
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_PUSH = 'push';
    const CHANNEL_WEBHOOK = 'webhook';

    // Notification Status
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_READ = 'read';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'ticket_id',
        'triggered_by_nip',
        'triggered_by_type',
        'title',
        'message',
        'data',
        'read_at',
        'sent_at',
        'delivered_at',
        'priority',
        'channel',
        'status',
        'scheduled_at',
        'retry_count',
        'error_message',
        'expires_at',
        'action_url',
        'icon',
        'group_id',
        'metadata',
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the notifiable entity that the notification belongs to (DISABLED - causing issues)
     * We'll use manual lookup instead to avoid morphTo relationship problems
     */
    // public function notifiable(): MorphTo
    // {
    //     return $this->morphTo();
    // }

    /**
     * Get the user who triggered this notification (DISABLED - causing issues)
     * We'll use getSafeTriggeredBy instead to avoid morphTo issues
     */
    // public function triggeredBy(): MorphTo
    // {
    //     return $this->morphTo('triggered_by');
    // }

    /**
     * Get safe triggered by information using NIP lookup
     * This bypasses the problematic morphTo relationship
     */
    public function getSafeTriggeredBy(): ?object
    {
        if (!$this->triggered_by_type || !$this->triggered_by_nip) {
            return null;
        }

        try {
            // Handle known user types by looking up via NIP
            switch (strtolower($this->triggered_by_type)) {
                case 'app\models\user':
                case 'user':
                    $user = User::where('nip', $this->triggered_by_nip)->first();
                    if ($user) {
                        return (object) [
                            'nip' => $user->nip,
                            'name' => $user->name,
                            'type' => 'User',
                        ];
                    }
                    break;

                case 'app\models\adminhelpdesk':
                case 'admin_helpdesk':
                    $user = AdminHelpdesk::where('nip', $this->triggered_by_nip)->first();
                    if ($user) {
                        return (object) [
                            'nip' => $user->nip,
                            'name' => $user->name,
                            'type' => 'AdminHelpdesk',
                        ];
                    }
                    break;

                case 'app\models\teknisi':
                case 'teknisi':
                    $user = Teknisi::where('nip', $this->triggered_by_nip)->first();
                    if ($user) {
                        return (object) [
                            'nip' => $user->nip,
                            'name' => $user->name,
                            'type' => 'Teknisi',
                        ];
                    }
                    break;

                case 'app\models\adminaplikasi':
                case 'admin_aplikasi':
                    $user = AdminAplikasi::where('nip', $this->triggered_by_nip)->first();
                    if ($user) {
                        return (object) [
                            'nip' => $user->nip,
                            'name' => $user->name,
                            'type' => 'AdminAplikasi',
                        ];
                    }
                    break;

                default:
                    // Handle system/unknown types
                    return (object) [
                        'nip' => null,
                        'name' => ucfirst($this->triggered_by_type) ?? 'System',
                        'type' => $this->triggered_by_type,
                    ];
            }
        } catch (\Exception $e) {
            // Handle any errors gracefully
            return (object) [
                'nip' => $this->triggered_by_nip,
                'name' => 'Unknown',
                'type' => $this->triggered_by_type,
            ];
        }

        return null;
    }

    /**
     * Get the ticket associated with this notification
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Get the user who should receive this notification (if notifiable is not a user)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notifiable_id')->where('notifiable_type', User::class);
    }

    /**
     * Get the teknisi who should receive this notification (if notifiable is not a teknisi)
     */
    public function teknisi(): BelongsTo
    {
        return $this->belongsTo(Teknisi::class, 'notifiable_id')->where('notifiable_type', Teknisi::class);
    }

    /**
     * Get notifications in the same group
     */
    public function groupedNotifications()
    {
        return $this->belongsToMany(self::class, 'notification_groups', 'notification_id', 'grouped_notification_id');
    }

    /**
     * Map class name to triggered_by_type enum value
     */
    public static function mapClassToTriggeredByType(string $className): ?string
    {
        $className = strtolower($className);
        if (str_contains($className, 'adminhelpdesk')) return 'admin_helpdesk';
        if (str_contains($className, 'adminaplikasi')) return 'admin_aplikasi';
        if (str_contains($className, 'teknisi')) return 'teknisi';
        if (str_contains($className, 'user')) return 'user';
        return 'system';
    }

    // ==================== NOTIFICATION TYPES ====================

    /**
     * Create a ticket assigned notification
     */
    public static function createTicketAssigned(Ticket $ticket, $notifiable, $triggeredBy = null): self
    {
        return self::create([
            'type' => self::TYPE_TICKET_ASSIGNED,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->getKey(),
            'ticket_id' => $ticket->id,
            'triggered_by_type' => $triggeredBy ? self::mapClassToTriggeredByType(get_class($triggeredBy)) : null,
            'triggered_by_nip' => $triggeredBy ? $triggeredBy->nip : null,
            'title' => 'Ticket Assigned',
            'message' => "Ticket #{$ticket->ticket_number} has been assigned to you",
            'priority' => self::PRIORITY_HIGH,
            'channel' => self::CHANNEL_DATABASE,
            'status' => self::STATUS_PENDING,
            'action_url' => "/tickets/{$ticket->id}",
            'icon' => 'assignment',
            'data' => [
                'ticket_number' => $ticket->ticket_number,
                'ticket_title' => $ticket->title,
                'priority' => $ticket->priority,
                'aplikasi' => $ticket->aplikasi->name ?? 'Unknown',
            ],
        ]);
    }

    /**
     * Create a ticket status changed notification
     */
    public static function createTicketStatusChanged(Ticket $ticket, string $oldStatus, $notifiable, $triggeredBy = null): self
    {
        return self::create([
            'type' => self::TYPE_TICKET_STATUS_CHANGED,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->getKey(),
            'ticket_id' => $ticket->id,
            'triggered_by_type' => $triggeredBy ? self::mapClassToTriggeredByType(get_class($triggeredBy)) : null,
            'triggered_by_nip' => $triggeredBy ? $triggeredBy->nip : null,
            'title' => 'Ticket Status Updated',
            'message' => "Ticket #{$ticket->ticket_number} status changed from {$oldStatus} to {$ticket->status}",
            'priority' => self::PRIORITY_MEDIUM,
            'channel' => self::CHANNEL_DATABASE,
            'status' => self::STATUS_PENDING,
            'action_url' => "/tickets/{$ticket->id}",
            'icon' => 'update',
            'data' => [
                'ticket_number' => $ticket->ticket_number,
                'old_status' => $oldStatus,
                'new_status' => $ticket->status,
                'ticket_title' => $ticket->title,
            ],
        ]);
    }

    /**
     * Create a ticket comment added notification
     */
    public static function createTicketCommentAdded(TicketComment $comment, $notifiable, $triggeredBy = null): self
    {
        return self::create([
            'type' => self::TYPE_TICKET_COMMENT_ADDED,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->getKey(),
            'ticket_id' => $comment->ticket_id,
            'triggered_by_type' => $triggeredBy ? self::mapClassToTriggeredByType(get_class($triggeredBy)) : null,
            'triggered_by_nip' => $triggeredBy ? $triggeredBy->nip : null,
            'title' => 'New Comment Added',
            'message' => "New comment added to ticket #{$comment->ticket->ticket_number}",
            'priority' => self::PRIORITY_MEDIUM,
            'channel' => self::CHANNEL_DATABASE,
            'status' => self::STATUS_PENDING,
            'action_url' => "/tickets/{$comment->ticket_id}#comment-{$comment->id}",
            'icon' => 'comment',
            'data' => [
                'ticket_number' => $comment->ticket->ticket_number,
                'comment_preview' => substr($comment->comment, 0, 100),
                'commenter_name' => $comment->user->name ?? 'Unknown',
            ],
        ]);
    }

    /**
     * Create a ticket due soon notification
     */
    public static function createTicketDueSoon(Ticket $ticket, $notifiable, $triggeredBy = null): self
    {
        return self::create([
            'type' => self::TYPE_TICKET_DUE_SOON,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->getKey(),
            'ticket_id' => $ticket->id,
            'triggered_by_type' => $triggeredBy ? self::mapClassToTriggeredByType(get_class($triggeredBy)) : null,
            'triggered_by_nip' => $triggeredBy ? $triggeredBy->nip : null,
            'title' => 'Ticket Due Soon',
            'message' => "Ticket #{$ticket->ticket_number} is due in " . $ticket->getTimeUntilSlaBreach(),
            'priority' => self::PRIORITY_HIGH,
            'channel' => self::CHANNEL_DATABASE,
            'status' => self::STATUS_PENDING,
            'action_url' => "/tickets/{$ticket->id}",
            'icon' => 'schedule',
            'data' => [
                'ticket_number' => $ticket->ticket_number,
                'due_date' => $ticket->due_date,
                'time_remaining' => $ticket->getTimeUntilSlaBreach(),
            ],
        ]);
    }

    /**
     * Create a ticket overdue notification
     */
    public static function createTicketOverdue(Ticket $ticket, $notifiable, $triggeredBy = null): self
    {
        return self::create([
            'type' => self::TYPE_TICKET_OVERDUE,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->getKey(),
            'ticket_id' => $ticket->id,
            'triggered_by_type' => $triggeredBy ? self::mapClassToTriggeredByType(get_class($triggeredBy)) : null,
            'triggered_by_nip' => $triggeredBy ? $triggeredBy->nip : null,
            'title' => 'Ticket Overdue',
            'message' => "Ticket #{$ticket->ticket_number} is now overdue",
            'priority' => self::PRIORITY_URGENT,
            'channel' => self::CHANNEL_DATABASE,
            'status' => self::STATUS_PENDING,
            'action_url' => "/tickets/{$ticket->id}",
            'icon' => 'warning',
            'data' => [
                'ticket_number' => $ticket->ticket_number,
                'overdue_since' => $ticket->due_date?->diffForHumans(),
                'days_overdue' => $ticket->getDaysUntilDueAttribute() ? abs($ticket->getDaysUntilDueAttribute()) : 0,
            ],
        ]);
    }

    // ==================== DELIVERY MANAGEMENT ====================

    /**
     * Mark notification as read
     */
    public function markAsRead(): bool
    {
        if (!$this->read_at) {
            $this->read_at = Carbon::now();
            $this->status = self::STATUS_READ;
            return $this->save();
        }
        return true;
    }

    /**
     * Mark notification as sent
     */
    public function markAsSent(): bool
    {
        if (!$this->sent_at) {
            $this->sent_at = Carbon::now();
            $this->status = self::STATUS_SENT;
            return $this->save();
        }
        return true;
    }

    /**
     * Mark notification as delivered
     */
    public function markAsDelivered(): bool
    {
        $this->delivered_at = Carbon::now();
        $this->status = self::STATUS_DELIVERED;
        return $this->save();
    }

    /**
     * Mark notification as failed
     */
    public function markAsFailed(string $error = null): bool
    {
        $this->retry_count = ($this->retry_count ?? 0) + 1;
        $this->error_message = $error;
        $this->status = self::STATUS_FAILED;
        return $this->save();
    }

    /**
     * Check if notification is read
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if notification is unread
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Check if notification is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if notification can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED &&
               ($this->retry_count ?? 0) < 3 &&
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Get priority badge color for UI
     */
    public function getPriorityBadgeColorAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'success',
            self::PRIORITY_MEDIUM => 'info',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_URGENT => 'danger',
            default => 'light',
        };
    }

    /**
     * Get priority label
     */
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
            default => 'Unknown',
        };
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_TICKET_ASSIGNED => 'Ticket Assigned',
            self::TYPE_TICKET_STATUS_CHANGED => 'Status Changed',
            self::TYPE_TICKET_COMMENT_ADDED => 'New Comment',
            self::TYPE_TICKET_ESCALATED => 'Ticket Escalated',
            self::TYPE_TICKET_DUE_SOON => 'Due Soon',
            self::TYPE_TICKET_OVERDUE => 'Overdue',
            self::TYPE_TICKET_RESOLVED => 'Resolved',
            self::TYPE_SYSTEM_ANNOUNCEMENT => 'Announcement',
            self::TYPE_USER_MENTIONED => 'Mentioned',
            self::TYPE_DEADLINE_REMINDER => 'Reminder',
            default => 'Notification',
        };
    }

    /**
     * Get type icon
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            self::TYPE_TICKET_ASSIGNED => 'user-plus',
            self::TYPE_TICKET_STATUS_CHANGED => 'refresh',
            self::TYPE_TICKET_COMMENT_ADDED => 'message-circle',
            self::TYPE_TICKET_ESCALATED => 'trending-up',
            self::TYPE_TICKET_DUE_SOON => 'clock',
            self::TYPE_TICKET_OVERDUE => 'alert-triangle',
            self::TYPE_TICKET_RESOLVED => 'check-circle',
            self::TYPE_SYSTEM_ANNOUNCEMENT => 'megaphone',
            self::TYPE_USER_MENTIONED => 'at-sign',
            self::TYPE_DEADLINE_REMINDER => 'bell',
            default => 'info',
        };
    }

    /**
     * Get formatted created date
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('d M Y, H:i');
    }

    /**
     * Get time elapsed since creation
     */
    public function getTimeElapsedAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get formatted read date
     */
    public function getFormattedReadAtAttribute(): string
    {
        if (!$this->read_at) {
            return 'Not read';
        }
        return $this->read_at->format('d M Y, H:i');
    }

    /**
     * Get status badge for UI
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => '<span class="badge badge-warning">Pending</span>',
            self::STATUS_SENT => '<span class="badge badge-info">Sent</span>',
            self::STATUS_DELIVERED => '<span class="badge badge-success">Delivered</span>',
            self::STATUS_READ => '<span class="badge badge-secondary">Read</span>',
            self::STATUS_FAILED => '<span class="badge badge-danger">Failed</span>',
            default => '<span class="badge badge-light">Unknown</span>',
        };
    }

    /**
     * Get channel badge for UI
     */
    public function getChannelBadgeAttribute(): string
    {
        return match($this->channel) {
            self::CHANNEL_DATABASE => '<span class="badge badge-primary">Database</span>',
            self::CHANNEL_EMAIL => '<span class="badge badge-secondary">Email</span>',
            self::CHANNEL_SMS => '<span class="badge badge-info">SMS</span>',
            self::CHANNEL_PUSH => '<span class="badge badge-warning">Push</span>',
            self::CHANNEL_WEBHOOK => '<span class="badge badge-dark">Webhook</span>',
            default => '<span class="badge badge-light">Unknown</span>',
        };
    }

    /**
     * Get notification summary for display
     */
    public function getSummaryAttribute(): string
    {
        $summary = $this->title;

        if ($this->ticket) {
            $summary .= " - Ticket #{$this->ticket->ticket_number}";
        }

        return $summary;
    }

    /**
     * Get notification data for API response
     */
    public function getApiDataAttribute(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'type_icon' => $this->type_icon,
            'title' => $this->title,
            'message' => $this->message,
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,
            'priority_badge_color' => $this->priority_badge_color,
            'status' => $this->status,
            'status_badge' => $this->status_badge,
            'channel' => $this->channel,
            'channel_badge' => $this->channel_badge,
            'is_read' => $this->isRead(),
            'is_unread' => $this->isUnread(),
            'is_expired' => $this->isExpired(),
            'action_url' => $this->action_url,
            'icon' => $this->icon,
            'data' => $this->data,
            'metadata' => $this->metadata,
            'notifiable_type' => $this->notifiable_type,
            'notifiable_id' => $this->notifiable_id,
            'triggered_by_type' => $this->triggered_by_type,
            'triggered_by_nip' => $this->triggered_by_nip,
            'sent_at' => $this->sent_at,
            'delivered_at' => $this->delivered_at,
            'scheduled_at' => $this->scheduled_at,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'formatted_created_at' => $this->formatted_created_at,
            'time_elapsed' => $this->time_elapsed,
            'read_at' => $this->read_at,
            'formatted_read_at' => $this->formatted_read_at,
            'ticket' => $this->ticket ? [
                'id' => $this->ticket->id,
                'ticket_number' => $this->ticket->ticket_number,
                'title' => $this->ticket->title,
                'status' => $this->ticket->status,
                'priority' => $this->ticket->priority,
            ] : null,
            'triggered_by' => $this->getSafeTriggeredBy(),
        ];
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Group similar notifications
     */
    public function groupWith(self $otherNotification): void
    {
        if ($this->id === $otherNotification->id) {
            return;
        }

        $groupId = min($this->id, $otherNotification->id);

        $this->update(['group_id' => $groupId]);
        $otherNotification->update(['group_id' => $groupId]);
    }

    /**
     * Get notifications in the same group
     */
    public function getGroupedNotifications()
    {
        if (!$this->group_id) {
            return collect([$this]);
        }

        return self::where('group_id', $this->group_id)->orderBy('created_at')->get();
    }

    /**
     * Batch mark notifications as read
     */
    public static function batchMarkAsRead(array $notificationIds): int
    {
        return self::whereIn('id', $notificationIds)
                  ->whereNull('read_at')
                  ->update([
                      'read_at' => Carbon::now(),
                      'status' => self::STATUS_READ,
                  ]);
    }

    /**
     * Clean up expired notifications
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', Carbon::now())
                  ->delete();
    }

    /**
     * Retry failed notifications
     */
    public static function retryFailed(): int
    {
        $failedNotifications = self::where('status', self::STATUS_FAILED)
                                  ->where('retry_count', '<', 3)
                                  ->where(function ($query) {
                                      $query->whereNull('expires_at')
                                            ->orWhere('expires_at', '>', Carbon::now());
                                  })
                                  ->get();

        $retried = 0;
        foreach ($failedNotifications as $notification) {
            if ($notification->canRetry()) {
                $notification->update([
                    'status' => self::STATUS_PENDING,
                    'error_message' => null,
                ]);
                $retried++;
            }
        }

        return $retried;
    }

    /**
     * Get notification statistics for a user
     */
    public static function getUserStats($user): array
    {
        $baseQuery = self::where('notifiable_type', get_class($user))
                        ->where('notifiable_id', $user->getKey());

        return [
            'total' => $baseQuery->count(),
            'unread' => $baseQuery->whereNull('read_at')->count(),
            'read' => $baseQuery->whereNotNull('read_at')->count(),
            'failed' => $baseQuery->where('status', self::STATUS_FAILED)->count(),
            'today' => $baseQuery->whereDate('created_at', Carbon::today())->count(),
            'this_week' => $baseQuery->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
        ];
    }

    // ==================== QUERY SCOPES ====================

    /**
     * Scope for unread notifications
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope for notifications by type
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for notifications by priority
     */
    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for notifications by user (using NIP or ID to support both creation patterns)
     */
    public function scopeByUser(Builder $query, $user): Builder
    {
        // Get both NIP and ID from the user model
        $nip = $user->nip ?? null;
        $id = $user->id ?? $user->getKey();
        
        if (!$nip && !$id) return $query->whereRaw('1 = 0'); // Return no results if no identifiers

        // Check both User and Admin types, supporting both NIP and ID lookup
        return $query->where(function($q) use ($nip, $id) {
            $q->where(function($subQ) use ($nip, $id) {
                $subQ->where('notifiable_type', User::class)
                     ->where(function($idQ) use ($nip, $id) {
                         if ($nip) $idQ->where('notifiable_id', $nip);
                         if ($id) $idQ->orWhere('notifiable_id', $id);
                     });
            })->orWhere(function($subQ) use ($nip, $id) {
                $subQ->whereIn('notifiable_type', [
                    \App\Models\AdminHelpdesk::class,
                    \App\Models\AdminAplikasi::class
                ])->where(function($idQ) use ($nip, $id) {
                    if ($nip) $idQ->where('notifiable_id', $nip);
                    if ($id) $idQ->orWhere('notifiable_id', $id);
                });
            });
        });
    }

    /**
     * Scope for notifications by teknisi (using NIP or ID to support both creation patterns)
     */
    public function scopeByTeknisi(Builder $query, $teknisi): Builder
    {
        // Get both NIP and ID from the teknisi model
        $nip = $teknisi->nip ?? null;
        $id = $teknisi->id ?? $teknisi->getKey();
        
        if (!$nip && !$id) return $query->whereRaw('1 = 0'); // Return no results if no identifiers

        return $query->where('notifiable_type', Teknisi::class)
                    ->where(function($q) use ($nip, $id) {
                        // Check both NIP and ID to support legacy and new notification creation patterns
                        if ($nip) {
                            $q->where('notifiable_id', $nip);
                        }
                        if ($id) {
                            $q->orWhere('notifiable_id', $id);
                        }
                    });
    }

    /**
     * Scope for notifications by ticket
     */
    public function scopeByTicket(Builder $query, int $ticketId): Builder
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Scope for notifications by channel
     */
    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope for notifications by status
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for urgent notifications
     */
    public function scopeUrgent(Builder $query): Builder
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }

    /**
     * Scope for high priority notifications
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Scope for failed notifications
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for pending notifications
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for notifications created today
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope for notifications created this week
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * Scope for expired notifications
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<', Carbon::now());
    }

    /**
     * Scope for notifications that can be retried
     */
    public function scopeCanRetry(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED)
                    ->where('retry_count', '<', 3)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', Carbon::now());
                    });
    }

    /**
     * REMOVED: Scope for real-time notifications
     * This was: return $query->where('is_real_time', true);
     */

    /**
     * Scope for grouped notifications
     */
    public function scopeGrouped(Builder $query): Builder
    {
        return $query->whereNotNull('group_id');
    }

    /**
     * Scope for ungrouped notifications
     */
    public function scopeUngrouped(Builder $query): Builder
    {
        return $query->whereNull('group_id');
    }

    /**
     * Scope for notifications by group
     */
    public function scopeByGroup(Builder $query, int $groupId): Builder
    {
        return $query->where('group_id', $groupId);
    }

    /**
     * Scope for search by title or message
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('message', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for notifications with specific data
     */
    public function scopeWhereData(Builder $query, string $key, $value): Builder
    {
        return $query->where("data->{$key}", $value);
    }

    /**
     * Scope for notifications triggered by user
     */
    public function scopeTriggeredBy(Builder $query, $user): Builder
    {
        return $query->where('triggered_by_type', get_class($user))
                    ->where('triggered_by_nip', $user->getKey());
    }

    /**
     * Scope for recent notifications
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * Scope for ordering by priority (urgent first)
     */
    public function scopeOrderByPriority(Builder $query, string $direction = 'desc'): Builder
    {
        $priorityOrder = [
            self::PRIORITY_URGENT => 4,
            self::PRIORITY_HIGH => 3,
            self::PRIORITY_MEDIUM => 2,
            self::PRIORITY_LOW => 1,
        ];

        return $query->orderByRaw("CASE
            WHEN priority = 'urgent' THEN 4
            WHEN priority = 'high' THEN 3
            WHEN priority = 'medium' THEN 2
            WHEN priority = 'low' THEN 1
            ELSE 0
        END {$direction}");
    }

    /**
     * Scope for ordering by read status (unread first)
     */
    public function scopeOrderByReadStatus(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderByRaw("CASE WHEN read_at IS NULL THEN 0 ELSE 1 END {$direction}")
                    ->orderBy('created_at', 'desc');
    }
}