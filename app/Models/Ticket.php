<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Laravel\Scout\Searchable;

class Ticket extends Model
{
    use HasFactory, Searchable;

    // Status constants
    const STATUS_OPEN = 'open';
    const STATUS_ASSIGNED = 'assigned';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_WAITING_USER = 'waiting_user';
    const STATUS_WAITING_ADMIN = 'waiting_admin';
    const STATUS_WAITING_RESPONSE = 'waiting_user'; // For compatibility
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';
    const STATUS_CANCELLED = 'cancelled';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Valid status transitions - Step-by-step workflow (matching database enum values)
    const VALID_STATUS_TRANSITIONS = [
        self::STATUS_OPEN => [self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED], // From Open: Assign or start working or cancel
        self::STATUS_ASSIGNED => [self::STATUS_IN_PROGRESS, self::STATUS_OPEN, self::STATUS_CANCELLED], // From Assigned: Start working, reassign, or cancel
        self::STATUS_IN_PROGRESS => [self::STATUS_WAITING_USER, self::STATUS_WAITING_ADMIN, self::STATUS_RESOLVED, self::STATUS_CANCELLED], // From In Progress: Wait for user/admin, resolve, or cancel
        self::STATUS_WAITING_USER => [self::STATUS_IN_PROGRESS, self::STATUS_RESOLVED, self::STATUS_CANCELLED], // From Waiting User: Continue work, resolve, or cancel
        self::STATUS_WAITING_ADMIN => [self::STATUS_IN_PROGRESS, self::STATUS_RESOLVED, self::STATUS_CANCELLED], // From Waiting Admin: Continue work, resolve, or cancel
        self::STATUS_RESOLVED => [self::STATUS_CLOSED], // From Resolved: Only can close
        self::STATUS_CLOSED => [self::STATUS_OPEN], // From Closed: Can reopen if needed
        self::STATUS_CANCELLED => [self::STATUS_OPEN], // From Cancelled: Can reopen if needed
    ];

    // Priority levels with SLA hours
    const PRIORITY_SLA_HOURS = [
        self::PRIORITY_LOW => 72,      // 3 days
        self::PRIORITY_MEDIUM => 48,   // 2 days
        self::PRIORITY_HIGH => 24,     // 1 day
        self::PRIORITY_URGENT => 8,   // 8 hours
    ];

    protected $fillable = [
        'ticket_number',
        'user_nip',
        'aplikasi_id',
        'kategori_masalah_id',
        'assigned_teknisi_nip',
        'assigned_by_nip',
        'title',
        'description',
        'priority',
        'status',
        'attachments',
        'screenshot',
        'location',
        'device_info',
        'ip_address',
        'resolution_notes',
        'resolution_time_minutes',
        'user_rating',
        'user_feedback',
        'due_date',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'metadata',
        'is_escalated',
        'escalation_reason',
        'view_count',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'is_escalated' => 'boolean',
        'due_date' => 'datetime',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Validation rules
    public static function rules(): array
    {
        return [
            'ticket_number' => 'required|string|unique:tickets',
            'user_nip' => 'required|string|exists:users,nip',
            'aplikasi_id' => 'required|exists:aplikasis,id',
            'kategori_masalah_id' => 'required|exists:kategori_masalahs,id',
            'assigned_teknisi_nip' => 'nullable|string|exists:teknisis,nip',
            'assigned_by_nip' => ['nullable', 'string', new \App\Rules\ValidAdminNip()],
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:' . implode(',', [self::PRIORITY_LOW, self::PRIORITY_MEDIUM, self::PRIORITY_HIGH, self::PRIORITY_URGENT]),
            'status' => 'required|in:' . implode(',', [self::STATUS_OPEN, self::STATUS_ASSIGNED, self::STATUS_IN_PROGRESS, self::STATUS_WAITING_USER, self::STATUS_WAITING_ADMIN, self::STATUS_RESOLVED, self::STATUS_CLOSED, self::STATUS_CANCELLED]),
            'attachments' => 'nullable|array',
            'screenshot' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'device_info' => 'nullable|string',
            'ip_address' => 'nullable|ip',
            'resolution_notes' => 'nullable|string',
            'resolution_time_minutes' => 'nullable|integer|min:0',
            'user_rating' => 'nullable|integer|min:1|max:5',
            'user_feedback' => 'nullable|string',
            'due_date' => 'nullable|date|after:now',
            'is_escalated' => 'boolean',
            'escalation_reason' => 'nullable|string',
        ];
    }

    // Validation rules for updates
    public static function updateRules(): array
    {
        $rules = self::rules();
        $rules['ticket_number'] = 'required|string|unique:tickets,ticket_number,' . request()->route('ticket')?->id;
        return $rules;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_nip', 'nip');
    }

    public function aplikasi()
    {
        return $this->belongsTo(Aplikasi::class, 'aplikasi_id');
    }

    public function kategoriMasalah()
    {
        return $this->belongsTo(KategoriMasalah::class, 'kategori_masalah_id');
    }

    public function assignedTeknisi()
    {
        return $this->belongsTo(Teknisi::class, 'assigned_teknisi_nip', 'nip');
    }

    public function assignedBy()
    {
        return $this->belongsTo(AdminHelpdesk::class, 'assigned_by_nip', 'nip');
    }

    /**
     * Get the assigned admin (either AdminHelpdesk or AdminAplikasi)
     */
    public function getAssignedAdmin()
    {
        // Try to find in admin_helpdesks first
        $adminHelpdesk = AdminHelpdesk::where('nip', $this->assigned_by_nip)->first();
        if ($adminHelpdesk) {
            return $adminHelpdesk;
        }

        // If not found in admin_helpdesks, try admin_aplikasis
        return AdminAplikasi::where('nip', $this->assigned_by_nip)->first();
    }

    /**
     * Get the type of the assigned admin
     */
    public function getAssignedAdminType(): ?string
    {
        if (!$this->assigned_by_nip) {
            return null;
        }

        // Check if exists in admin_helpdesks
        $adminHelpdesk = AdminHelpdesk::where('nip', $this->assigned_by_nip)->exists();
        if ($adminHelpdesk) {
            return 'admin_helpdesk';
        }

        // Check if exists in admin_aplikasis
        $adminAplikasi = AdminAplikasi::where('nip', $this->assigned_by_nip)->exists();
        if ($adminAplikasi) {
            return 'admin_aplikasi';
        }

        return null;
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class, 'ticket_id');
    }

    public function history()
    {
        return $this->hasMany(TicketHistory::class, 'ticket_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'ticket_id');
    }

    // ==================== BUSINESS LOGIC METHODS ====================

    /**
     * Check if status transition is valid
     */
    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::VALID_STATUS_TRANSITIONS[$this->status] ?? []);
    }

    /**
     * Transition ticket to new status
     */
    public function transitionTo(string $newStatus, string $userNip = null, string $notes = null): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus = $this->status;
        $this->status = $newStatus;

        // Set appropriate timestamps
        switch ($newStatus) {
            case self::STATUS_RESOLVED:
                $this->resolved_at = Carbon::now();
                break;
            case self::STATUS_CLOSED:
                $this->closed_at = Carbon::now();
                break;
        }

        if ($this->save()) {
            // Create history record
            $this->createHistoryRecord($oldStatus, $newStatus, $userNip, $notes);
            return true;
        }

        return false;
    }

    /**
     * Assign ticket to teknisi
     */
    public function assignToTeknisi(string $teknisiNip, string $assignedByNip, string $notes = null): bool
    {
        $this->assigned_teknisi_nip = $teknisiNip;
        $this->assigned_by_nip = $assignedByNip;

        if ($this->save()) {
            $this->createHistoryRecord(
                $this->status,
                $this->status,
                $assignedByNip,
                "Assigned to teknisi: {$notes}",
                'assignment'
            );
            return true;
        }

        return false;
    }

    /**
     * Unassign ticket from teknisi
     */
    public function unassignTeknisi(string $unassignedByNip, string $reason = null): bool
    {
        $oldTeknisiNip = $this->assigned_teknisi_nip;
        $this->assigned_teknisi_nip = null;
        $this->assigned_by_nip = $unassignedByNip;

        if ($this->save()) {
            $this->createHistoryRecord(
                $this->status,
                $this->status,
                $unassignedByNip,
                "Unassigned from teknisi {$oldTeknisiNip}: {$reason}",
                'unassignment'
            );
            return true;
        }

        return false;
    }

    /**
     * Escalate ticket
     */
    public function escalate(string $reason, string $escalatedByNip): bool
    {
        $this->is_escalated = true;
        $this->escalation_reason = $reason;

        if ($this->save()) {
            $this->createHistoryRecord(
                $this->status,
                $this->status,
                $escalatedByNip,
                "Escalated: {$reason}",
                'escalation'
            );
            return true;
        }

        return false;
    }

    /**
     * Add first response timestamp
     */
    public function markFirstResponse(): void
    {
        if (!$this->first_response_at) {
            $this->first_response_at = Carbon::now();
            $this->save();
        }
    }

    /**
     * Calculate resolution time in minutes
     */
    public function calculateResolutionTime(): ?int
    {
        if (!$this->resolved_at || !$this->created_at) {
            return null;
        }

        return $this->created_at->diffInMinutes($this->resolved_at);
    }

    /**
     * Update resolution time
     */
    public function updateResolutionTime(): void
    {
        $this->resolution_time_minutes = $this->calculateResolutionTime();
        $this->save();
    }

    /**
     * Check if ticket needs escalation
     */
    public function needsEscalation(): bool
    {
        if ($this->is_escalated) {
            return false;
        }

        $slaHours = self::PRIORITY_SLA_HOURS[$this->priority] ?? 72;
        $escalationThreshold = $this->created_at->copy()->addHours($slaHours * 0.75); // 75% of SLA

        return $escalationThreshold->isPast() && !in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Get SLA deadline
     */
    public function getSlaDeadline(): Carbon
    {
        $slaHours = self::PRIORITY_SLA_HOURS[$this->priority] ?? 72;
        return $this->created_at->copy()->addHours($slaHours);
    }

    /**
     * Get time remaining until SLA breach
     */
    public function getTimeUntilSlaBreach(): ?string
    {
        $deadline = $this->getSlaDeadline();

        if ($deadline->isPast()) {
            return 'SLA breached';
        }

        return $deadline->diffForHumans(null, true);
    }

    /**
     * Create history record
     */
    protected function createHistoryRecord(string $oldStatus, string $newStatus, ?string $userNip, ?string $notes = null, string $actionType = 'status_change'): void
    {
        // Determine the performer type based on NIP
        $performedByType = 'system';
        if ($userNip) {
            if (AdminHelpdesk::where('nip', $userNip)->exists()) {
                $performedByType = 'admin_helpdesk';
            } elseif (AdminAplikasi::where('nip', $userNip)->exists()) {
                $performedByType = 'admin_aplikasi';
            } elseif (Teknisi::where('nip', $userNip)->exists()) {
                $performedByType = 'teknisi';
            } elseif (User::where('nip', $userNip)->exists()) {
                $performedByType = 'user';
            }
        }

        TicketHistory::create([
            'ticket_id' => $this->id,
            'performed_by_nip' => $userNip,
            'performed_by_type' => $performedByType,
            'action' => $actionType,
            'field_name' => 'status',
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
            'description' => $notes ?: "Status changed from {$oldStatus} to {$newStatus}",
            'metadata' => json_encode([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Get status badge color for UI
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_OPEN => 'yellow',
            self::STATUS_ASSIGNED => 'blue',
            self::STATUS_IN_PROGRESS => 'indigo',
            self::STATUS_WAITING_USER => 'orange',
            self::STATUS_WAITING_ADMIN => 'purple',
            self::STATUS_WAITING_RESPONSE => 'orange', // For compatibility
            self::STATUS_RESOLVED => 'green',
            self::STATUS_CLOSED => 'gray',
            self::STATUS_CANCELLED => 'red',
            default => 'gray',
        };
    }

    /**
     * Get priority badge color for UI
     */
    public function getPriorityBadgeColorAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'gray',
            self::PRIORITY_MEDIUM => 'blue',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_URGENT => 'red',
            default => 'gray',
        };
    }

    /**
     * Get formatted created date
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('Y-m-d H:i');
    }

    /**
     * Get formatted update date
     */
    public function getFormattedUpdatedAtAttribute(): string
    {
        return $this->updated_at->format('Y-m-d H:i');
    }

    /**
     * Get formatted due date
     */
    public function getFormattedDueDateAttribute(): string
    {
        if (!$this->due_date) {
            return 'No due date';
        }
        return $this->due_date->format('Y-m-d H:i');
    }

    /**
     * Get formatted resolution date
     */
    public function getFormattedResolvedAtAttribute(): string
    {
        if (!$this->resolved_at) {
            return 'Not resolved';
        }
        return $this->resolved_at->format('Y-m-d H:i');
    }

    /**
     * Get formatted closed date
     */
    public function getFormattedClosedAtAttribute(): string
    {
        if (!$this->closed_at) {
            return 'Not closed';
        }
        return $this->closed_at->format('Y-m-d H:i');
    }

    /**
     * Get time elapsed since creation
     */
    public function getTimeElapsedAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get resolution time formatted
     */
    public function getFormattedResolutionTimeAttribute(): string
    {
        if (!$this->resolution_time_minutes) {
            return 'Not resolved';
        }

        $hours = floor($this->resolution_time_minutes / 60);
        $minutes = $this->resolution_time_minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
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
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_OPEN => 'Open',
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_WAITING_USER => 'Waiting User',
            self::STATUS_WAITING_ADMIN => 'Waiting Admin',
            self::STATUS_WAITING_RESPONSE => 'Waiting Response', // For compatibility
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }

    /**
     * Check if ticket is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->due_date) {
            return false;
        }
        return $this->due_date->isPast() && !in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Check if ticket is within SLA
     */
    public function getIsWithinSlaAttribute(): bool
    {
        $slaHours = self::PRIORITY_SLA_HOURS[$this->priority] ?? 72;
        $slaDeadline = $this->created_at->copy()->addHours($slaHours);
        return $slaDeadline->isFuture() || in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Get SLA status
     */
    public function getSlaStatusAttribute(): string
    {
        if ($this->is_within_sla) {
            return 'within_sla';
        }
        return 'breached';
    }

    /**
     * Get days until due
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }

        return max(0, Carbon::now()->diffInDays($this->due_date, false));
    }

    // ==================== QUERY SCOPES ====================

    /**
     * Scope for open tickets
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope for in progress tickets
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope for waiting response tickets
     */
    public function scopeWaitingResponse(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_WAITING_USER, self::STATUS_WAITING_ADMIN]);
    }

    /**
     * Scope for waiting user tickets
     */
    public function scopeWaitingUser(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_WAITING_USER);
    }

    /**
     * Scope for waiting admin tickets
     */
    public function scopeWaitingAdmin(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_WAITING_ADMIN);
    }

    /**
     * Scope for cancelled tickets
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope for resolved tickets
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope for closed tickets
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * Scope for active tickets (not closed or cancelled)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_CLOSED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope for urgent priority tickets
     */
    public function scopeUrgent(Builder $query): Builder
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }

    /**
     * Scope for high priority tickets
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Scope for overdue tickets
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
                    ->where('due_date', '<', Carbon::now())
                    ->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    /**
     * Scope for tickets within SLA
     */
    public function scopeWithinSla(Builder $query): Builder
    {
        $slaHours = self::PRIORITY_SLA_HOURS;
        return $query->where(function ($q) use ($slaHours) {
            foreach ($slaHours as $priority => $hours) {
                $q->orWhere(function ($subQ) use ($priority, $hours) {
                    $subQ->where('priority', $priority)
                         ->where(function ($timeQ) use ($hours) {
                             $timeQ->where(function ($statusQ) {
                                 $statusQ->whereIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
                             })->orWhere('created_at', '>', Carbon::now()->subHours($hours));
                         });
                });
            }
        });
    }

    /**
     * Scope for tickets breaching SLA
     */
    public function scopeSlaBreached(Builder $query): Builder
    {
        $slaHours = self::PRIORITY_SLA_HOURS;
        return $query->where(function ($q) use ($slaHours) {
            foreach ($slaHours as $priority => $hours) {
                $q->orWhere(function ($subQ) use ($priority, $hours) {
                    $subQ->where('priority', $priority)
                         ->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED])
                         ->where('created_at', '<=', Carbon::now()->subHours($hours));
                });
            }
        });
    }

    /**
     * Scope for escalated tickets
     */
    public function scopeEscalated(Builder $query): Builder
    {
        return $query->where('is_escalated', true);
    }

    /**
     * Scope for tickets by user
     */
    public function scopeByUser(Builder $query, string $userNip): Builder
    {
        return $query->where('user_nip', $userNip);
    }

    /**
     * Scope for tickets by teknisi
     */
    public function scopeByTeknisi(Builder $query, string $teknisiNip): Builder
    {
        return $query->where('assigned_teknisi_nip', $teknisiNip);
    }

    /**
     * Scope for tickets by aplikasi
     */
    public function scopeByAplikasi(Builder $query, int $aplikasiId): Builder
    {
        return $query->where('aplikasi_id', $aplikasiId);
    }

    /**
     * Scope for tickets by kategori masalah
     */
    public function scopeByKategoriMasalah(Builder $query, int $kategoriId): Builder
    {
        return $query->where('kategori_masalah_id', $kategoriId);
    }

    /**
     * Scope for tickets by priority
     */
    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for tickets by status
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for tickets created today
     */
    public function scopeCreatedToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope for tickets created this week
     */
    public function scopeCreatedThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * Scope for tickets created this month
     */
    public function scopeCreatedThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
    }

    /**
     * Scope for tickets with attachments
     */
    public function scopeWithAttachments(Builder $query): Builder
    {
        return $query->whereNotNull('attachments')->where('attachments', '!=', '[]');
    }

    /**
     * Scope for tickets without teknisi assigned
     */
    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_teknisi_nip');
    }

    /**
     * Scope for tickets assigned to teknisi
     */
    public function scopeAssigned(Builder $query): Builder
    {
        return $query->whereNotNull('assigned_teknisi_nip');
    }

    /**
     * Scope for tickets needing escalation
     */
    public function scopeNeedingEscalation(Builder $query): Builder
    {
        return $query->where('is_escalated', false)
                    ->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED])
                    ->where(function ($q) {
                        $slaHours = self::PRIORITY_SLA_HOURS;
                        foreach ($slaHours as $priority => $hours) {
                            $q->orWhere(function ($subQ) use ($priority, $hours) {
                                $subQ->where('priority', $priority)
                                     ->where('created_at', '<=', Carbon::now()->subHours($hours * 0.75));
                            });
                        }
                    });
    }

    /**
     * Scope for tickets with first response pending
     */
    public function scopeFirstResponsePending(Builder $query): Builder
    {
        return $query->whereNull('first_response_at')
                    ->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Scope for tickets with rating
     */
    public function scopeWithRating(Builder $query): Builder
    {
        return $query->whereNotNull('user_rating');
    }

    /**
     * Scope for tickets without rating
     */
    public function scopeWithoutRating(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED])
                    ->whereNull('user_rating');
    }

    /**
     * Scope for search by title or description
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('ticket_number', 'like', "%{$search}%");
        });
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
     * Scope for ordering by due date
     */
    public function scopeOrderByDueDate(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('due_date', $direction);
    }

    // ==================== LARAVEL SCOUT SEARCH FUNCTIONALITY ====================

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        // Load relationships for better search performance
        $this->load(['user', 'aplikasi', 'kategoriMasalah', 'assignedTeknisi']);

        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'title' => $this->title,
            'description' => strip_tags($this->description ?? ''),
            'priority' => $this->priority,
            'status' => $this->status,
            'user_nip' => $this->user_nip,
            'user_name' => $this->user?->name,
            'user_email' => $this->user?->email,
            'aplikasi_id' => $this->aplikasi_id,
            'aplikasi_name' => $this->aplikasi?->name,
            'kategori_masalah_id' => $this->kategori_masalah_id,
            'kategori_name' => $this->kategoriMasalah?->name,
            'assigned_teknisi_nip' => $this->assigned_teknisi_nip,
            'assigned_teknisi_name' => $this->assignedTeknisi?->name,
            'location' => $this->location,
            'device_info' => $this->device_info,
            'ip_address' => $this->ip_address,
            'resolution_notes' => $this->resolution_notes ? strip_tags($this->resolution_notes) : null,
            'is_escalated' => $this->is_escalated,
            'escalation_reason' => $this->escalation_reason,
            'created_at' => $this->created_at?->timestamp,
            'updated_at' => $this->updated_at?->timestamp,
            'due_date' => $this->due_date?->timestamp,
            'resolved_at' => $this->resolved_at?->timestamp,
            'closed_at' => $this->closed_at?->timestamp,

            // Include comment content for search
            'comments_content' => $this->comments()
                ->where('is_internal', false)
                ->pluck('comment')
                ->map(fn($content) => strip_tags($content ?? ''))
                ->implode(' '),

            // Search tags for better matching
            'search_tags' => implode(' ', array_filter([
                $this->ticket_number,
                $this->title,
                $this->description,
                $this->user?->name,
                $this->user?->email,
                $this->aplikasi?->name,
                $this->kategoriMasalah?->name,
                $this->assignedTeknisi?->name,
                $this->location,
                $this->device_info,
                $this->priority,
                $this->status,
            ])),
        ];
    }

    /**
     * Determine if the model should be searchable.
     *
     * @return bool
     */
    public function shouldBeSearchable(): bool
    {
        // Only index tickets that are not soft deleted
        return true;
    }

    /**
     * Get the Scout engine name for the model.
     *
     * @return string
     */
    public function searchableAs(): string
    {
        return 'helpdesk_tickets_index';
    }

    /**
     * Modify the query used to retrieve models when making all of the models searchable.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function makeAllSearchableUsing($query)
    {
        return $query->with([
            'user',
            'aplikasi',
            'kategoriMasalah',
            'assignedTeknisi',
            'comments' => fn($q) => $q->where('is_internal', false)
        ]);
    }
}