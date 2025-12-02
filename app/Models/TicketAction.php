<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class TicketAction extends Model
{
    // Action type constants
    const ACTION_CREATED = 'created';
    const ACTION_ASSIGNED = 'assigned';
    const ACTION_STATUS_CHANGED = 'status_changed';
    const ACTION_PRIORITY_CHANGED = 'priority_changed';
    const ACTION_COMMENTED = 'commented';
    const ACTION_RESOLVED = 'resolved';
    const ACTION_CLOSED = 'closed';
    const ACTION_REOPENED = 'reopened';
    const ACTION_REASSIGNED = 'reassigned';
    const ACTION_ESCALATED = 'escalated';
    const ACTION_ATTACHMENT_ADDED = 'attachment_added';
    const ACTION_FIRST_RESPONSE = 'first_response';

    protected $fillable = [
        'ticket_id',
        'actor_nip',
        'actor_type',
        'action_type',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the ticket this action belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the actor (polymorphic)
     * This can be a User, Teknisi, AdminHelpdesk, or AdminAplikasi
     */
    public function actor()
    {
        // Based on actor_type, return appropriate relationship
        return match($this->actor_type) {
            'user' => User::where('nip', $this->actor_nip)->first(),
            'teknisi' => Teknisi::where('nip', $this->actor_nip)->first(),
            'admin_helpdesk' => AdminHelpdesk::where('nip', $this->actor_nip)->first(),
            'admin_aplikasi' => AdminAplikasi::where('nip', $this->actor_nip)->first(),
            default => null,
        };
    }

    // ==================== SCOPES ====================

    /**
     * Scope for filtering by ticket
     */
    public function scopeForTicket(Builder $query, int $ticketId): Builder
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Scope for filtering by action type
     */
    public function scopeByActionType(Builder $query, string $actionType): Builder
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope for filtering by actor
     */
    public function scopeByActor(Builder $query, string $actorNip): Builder
    {
        return $query->where('actor_nip', $actorNip);
    }

    /**
     * Scope for recent actions
     */
    public function scopeRecent(Builder $query, int $limit = 50): Builder
    {
        return $query->latest()->limit($limit);
    }

    // ==================== METHODS ====================

    /**
     * Create a new ticket action
     */
    public static function log(
        int $ticketId,
        string $actorNip,
        string $actorType,
        string $actionType,
        string $description,
        array $metadata = []
    ): self {
        return self::create([
            'ticket_id' => $ticketId,
            'actor_nip' => $actorNip,
            'actor_type' => $actorType,
            'action_type' => $actionType,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get formatted created date
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('d M Y, H:i:s');
    }

    /**
     * Get human-readable timestamp
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get action type icon
     */
    public function getActionIconAttribute(): string
    {
        return match($this->action_type) {
            self::ACTION_CREATED => 'plus-circle',
            self::ACTION_ASSIGNED => 'user-plus',
            self::ACTION_STATUS_CHANGED => 'refresh',
            self::ACTION_COMMENTED => 'message-square',
            self::ACTION_RESOLVED => 'check-circle',
            self::ACTION_CLOSED => 'x-circle',
            self::ACTION_REOPENED => 'rotate-ccw',
            self::ACTION_ESCALATED => 'alert-triangle',
            default => 'activity',
        };
    }

    /**
     * Get action type color
     */
    public function getActionColorAttribute(): string
    {
        return match($this->action_type) {
            self::ACTION_CREATED => 'blue',
            self::ACTION_ASSIGNED => 'indigo',
            self::ACTION_STATUS_CHANGED => 'purple',
            self::ACTION_COMMENTED => 'gray',
            self::ACTION_RESOLVED => 'green',
            self::ACTION_CLOSED => 'red',
            self::ACTION_REOPENED => 'orange',
            self::ACTION_ESCALATED => 'yellow',
            default => 'gray',
        };
    }
}
