<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class TicketHistory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ticket_history';

    // Action constants for consistency
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_STATUS_CHANGED = 'status_changed';
    const ACTION_ASSIGNED = 'assigned';
    const ACTION_UNASSIGNED = 'unassigned';
    const ACTION_PRIORITY_CHANGED = 'priority_changed';
    const ACTION_ESCALATED = 'escalated';
    const ACTION_COMMENTED = 'commented';
    const ACTION_RESOLVED = 'resolved';
    const ACTION_CLOSED = 'closed';
    const ACTION_REOPENED = 'reopened';
    const ACTION_DELETED = 'deleted';
    const ACTION_VIEWED = 'viewed';
    const ACTION_DOWNLOADED = 'downloaded';
    const ACTION_ARCHIVED = 'archived';
    const ACTION_RESTORED = 'restored';

    // Field categories for better organization
    const FIELD_CATEGORY_BASIC = 'basic';
    const FIELD_CATEGORY_STATUS = 'status';
    const FIELD_CATEGORY_ASSIGNMENT = 'assignment';
    const FIELD_CATEGORY_PRIORITY = 'priority';
    const FIELD_CATEGORY_CONTENT = 'content';
    const FIELD_CATEGORY_SYSTEM = 'system';

    protected $fillable = [
        'ticket_id',
        'action',
        'performed_by_nip',
        'performed_by_type',
        'field_name',
        'old_value',
        'new_value',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
        'change_reason',
        'category',
        'severity',
        'is_automated',
        'related_record_id',
        'related_record_type',
        'session_id',
        'request_id',
        'expires_at',
        'is_sensitive',
        'compliance_flags',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_automated' => 'boolean',
        'is_sensitive' => 'boolean',
        'compliance_flags' => 'array',
        'expires_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the ticket this history record belongs to
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Get the user who performed the action (if type is 'user')
     */
    public function performedByUser()
    {
        return $this->belongsTo(User::class, 'performed_by_nip', 'nip');
    }

    /**
     * Get the teknisi who performed the action (if type is 'teknisi')
     */
    public function performedByTeknisi()
    {
        return $this->belongsTo(Teknisi::class, 'performed_by_nip', 'nip');
    }

    /**
     * Get the admin helpdesk who performed the action (if type is 'admin_helpdesk')
     */
    public function performedByAdminHelpdesk()
    {
        return $this->belongsTo(AdminHelpdesk::class, 'performed_by_nip', 'nip');
    }

    /**
     * Get the admin aplikasi who performed the action (if type is 'admin_aplikasi')
     */
    public function performedByAdminAplikasi()
    {
        return $this->belongsTo(AdminAplikasi::class, 'performed_by_nip', 'nip');
    }

    /**
     * Polymorphic relationship to the performer
     */
    public function performedBy(): MorphTo
    {
        return $this->morphTo('performed_by', 'performed_by_type', 'performed_by_nip');
    }

    /**
     * Get related record if this history entry references another record
     */
    public function relatedRecord(): MorphTo
    {
        return $this->morphTo('related_record', 'related_record_type', 'related_record_id');
    }

    // ==================== HISTORY TRACKING ====================

    /**
     * Create a history record for ticket creation
     */
    public static function logTicketCreated(Ticket $ticket, string $performedByNip, string $performedByType): self
    {
        return self::create([
            'ticket_id' => $ticket->id,
            'action' => self::ACTION_CREATED,
            'performed_by_nip' => $performedByNip,
            'performed_by_type' => $performedByType,
            'description' => "Ticket created: {$ticket->title}",
            'metadata' => [
                'ticket_number' => $ticket->ticket_number,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
            'category' => self::FIELD_CATEGORY_BASIC,
            'severity' => 'low',
        ]);
    }

    /**
     * Create a history record for field changes
     */
    public static function logFieldChange(
        Ticket $ticket,
        string $fieldName,
        $oldValue,
        $newValue,
        string $performedByNip,
        string $performedByType,
        string $changeReason = null
    ): self {
        return self::create([
            'ticket_id' => $ticket->id,
            'action' => self::ACTION_UPDATED,
            'performed_by_nip' => $performedByNip,
            'performed_by_type' => $performedByType,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'description' => self::generateChangeDescription($fieldName, $oldValue, $newValue),
            'change_reason' => $changeReason,
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
            'category' => self::getFieldCategory($fieldName),
            'severity' => self::getChangeSeverity($fieldName),
        ]);
    }

    /**
     * Create a history record for status changes
     */
    public static function logStatusChange(
        Ticket $ticket,
        string $oldStatus,
        string $newStatus,
        string $performedByNip,
        string $performedByType,
        string $changeReason = null
    ): self {
        return self::create([
            'ticket_id' => $ticket->id,
            'action' => self::ACTION_STATUS_CHANGED,
            'performed_by_nip' => $performedByNip,
            'performed_by_type' => $performedByType,
            'field_name' => 'status',
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
            'description' => "Status changed from '{$oldStatus}' to '{$newStatus}'",
            'change_reason' => $changeReason,
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
            'category' => self::FIELD_CATEGORY_STATUS,
            'severity' => 'medium',
        ]);
    }

    /**
     * Create a history record for ticket assignment
     */
    public static function logTicketAssignment(
        Ticket $ticket,
        ?string $oldTeknisiNip,
        string $newTeknisiNip,
        string $performedByNip,
        string $performedByType,
        string $notes = null
    ): self {
        return self::create([
            'ticket_id' => $ticket->id,
            'action' => $oldTeknisiNip ? self::ACTION_ASSIGNED : self::ACTION_ASSIGNED,
            'performed_by_nip' => $performedByNip,
            'performed_by_type' => $performedByType,
            'field_name' => 'assigned_teknisi_nip',
            'old_value' => $oldTeknisiNip,
            'new_value' => $newTeknisiNip,
            'description' => $oldTeknisiNip
                ? "Ticket reassigned from {$oldTeknisiNip} to {$newTeknisiNip}"
                : "Ticket assigned to {$newTeknisiNip}",
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'notes' => $notes,
            ],
            'category' => self::FIELD_CATEGORY_ASSIGNMENT,
            'severity' => 'medium',
        ]);
    }

    /**
     * Create a history record for priority changes
     */
    public static function logPriorityChange(
        Ticket $ticket,
        string $oldPriority,
        string $newPriority,
        string $performedByNip,
        string $performedByType,
        string $changeReason = null
    ): self {
        return self::create([
            'ticket_id' => $ticket->id,
            'action' => self::ACTION_PRIORITY_CHANGED,
            'performed_by_nip' => $performedByNip,
            'performed_by_type' => $performedByType,
            'field_name' => 'priority',
            'old_value' => $oldPriority,
            'new_value' => $newPriority,
            'description' => "Priority changed from '{$oldPriority}' to '{$newPriority}'",
            'change_reason' => $changeReason,
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
            'category' => self::FIELD_CATEGORY_PRIORITY,
            'severity' => 'medium',
        ]);
    }

    /**
     * Create a history record for ticket comments
     */
    public static function logTicketComment(
        Ticket $ticket,
        string $performedByNip,
        string $performedByType,
        string $comment = null
    ): self {
        return self::create([
            'ticket_id' => $ticket->id,
            'action' => self::ACTION_COMMENTED,
            'performed_by_nip' => $performedByNip,
            'performed_by_type' => $performedByType,
            'description' => 'Comment added to ticket',
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'comment_preview' => $comment ? substr($comment, 0, 100) : null,
            ],
            'category' => self::FIELD_CATEGORY_CONTENT,
            'severity' => 'low',
        ]);
    }

    /**
     * Create a history record for ticket viewing
     */
    public static function logTicketView(
        Ticket $ticket,
        string $performedByNip,
        string $performedByType
    ): self {
        return self::create([
            'ticket_id' => $ticket->id,
            'action' => self::ACTION_VIEWED,
            'performed_by_nip' => $performedByNip,
            'performed_by_type' => $performedByType,
            'description' => 'Ticket viewed',
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
            'category' => self::FIELD_CATEGORY_SYSTEM,
            'severity' => 'low',
        ]);
    }

    // ==================== CHANGE DETECTION ====================

    /**
     * Generate human-readable description of a field change
     */
    private static function generateChangeDescription(string $fieldName, $oldValue, $newValue): string
    {
        $fieldLabels = [
            'title' => 'Title',
            'description' => 'Description',
            'priority' => 'Priority',
            'status' => 'Status',
            'assigned_teknisi_nip' => 'Assigned Teknisi',
            'resolution_notes' => 'Resolution Notes',
            'user_rating' => 'User Rating',
        ];

        $fieldLabel = $fieldLabels[$fieldName] ?? ucfirst($fieldName);

        if ($oldValue === null) {
            return "{$fieldLabel} set to: {$newValue}";
        }

        if ($newValue === null) {
            return "{$fieldLabel} cleared (was: {$oldValue})";
        }

        return "{$fieldLabel} changed from '{$oldValue}' to '{$newValue}'";
    }

    /**
     * Get field category for organization
     */
    private static function getFieldCategory(string $fieldName): string
    {
        $categories = [
            'title' => self::FIELD_CATEGORY_BASIC,
            'description' => self::FIELD_CATEGORY_CONTENT,
            'status' => self::FIELD_CATEGORY_STATUS,
            'priority' => self::FIELD_CATEGORY_PRIORITY,
            'assigned_teknisi_nip' => self::FIELD_CATEGORY_ASSIGNMENT,
            'assigned_by_nip' => self::FIELD_CATEGORY_ASSIGNMENT,
            'resolution_notes' => self::FIELD_CATEGORY_CONTENT,
            'user_rating' => self::FIELD_CATEGORY_CONTENT,
        ];

        return $categories[$fieldName] ?? self::FIELD_CATEGORY_BASIC;
    }

    /**
     * Get change severity level
     */
    private static function getChangeSeverity(string $fieldName): string
    {
        $highSeverityFields = ['status', 'priority', 'assigned_teknisi_nip'];
        $mediumSeverityFields = ['title', 'description', 'resolution_notes'];

        if (in_array($fieldName, $highSeverityFields)) {
            return 'high';
        }

        if (in_array($fieldName, $mediumSeverityFields)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Detect significant changes that need special attention
     */
    public function isSignificantChange(): bool
    {
        $significantActions = [
            self::ACTION_STATUS_CHANGED,
            self::ACTION_PRIORITY_CHANGED,
            self::ACTION_ASSIGNED,
            self::ACTION_ESCALATED,
        ];

        return in_array($this->action, $significantActions) || $this->severity === 'high';
    }

    /**
     * Get change impact level
     */
    public function getChangeImpact(): string
    {
        if ($this->isSignificantChange()) {
            return 'high';
        }

        return match($this->severity) {
            'medium' => 'medium',
            'low' => 'low',
            default => 'low',
        };
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Get formatted action label
     */
    public function getActionLabelAttribute(): string
    {
        $labels = [
            self::ACTION_CREATED => 'Created',
            self::ACTION_UPDATED => 'Updated',
            self::ACTION_STATUS_CHANGED => 'Status Changed',
            self::ACTION_ASSIGNED => 'Assigned',
            self::ACTION_UNASSIGNED => 'Unassigned',
            self::ACTION_PRIORITY_CHANGED => 'Priority Changed',
            self::ACTION_ESCALATED => 'Escalated',
            self::ACTION_COMMENTED => 'Commented',
            self::ACTION_RESOLVED => 'Resolved',
            self::ACTION_CLOSED => 'Closed',
            self::ACTION_REOPENED => 'Reopened',
            self::ACTION_VIEWED => 'Viewed',
            self::ACTION_DOWNLOADED => 'Downloaded',
        ];

        return $labels[$this->action] ?? ucfirst($this->action);
    }

    /**
     * Get action badge color for UI
     */
    public function getActionBadgeColorAttribute(): string
    {
        return match($this->action) {
            self::ACTION_CREATED => 'success',
            self::ACTION_STATUS_CHANGED => 'info',
            self::ACTION_ASSIGNED => 'primary',
            self::ACTION_PRIORITY_CHANGED => 'warning',
            self::ACTION_ESCALATED => 'danger',
            self::ACTION_RESOLVED => 'success',
            self::ACTION_CLOSED => 'secondary',
            self::ACTION_COMMENTED => 'info',
            default => 'light',
        };
    }

    /**
     * Get performer display name
     */
    public function getPerformerNameAttribute(): string
    {
        $performer = $this->performedBy;

        if (!$performer) {
            return 'Unknown';
        }

        return match($this->performed_by_type) {
            'user' => $performer->name ?? 'Unknown User',
            'teknisi' => $performer->name ?? 'Unknown Teknisi',
            'admin_helpdesk' => $performer->name ?? 'Unknown Admin',
            'admin_aplikasi' => $performer->name ?? 'Unknown Admin',
            'system' => 'System',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted field name
     */
    public function getFormattedFieldNameAttribute(): string
    {
        if (!$this->field_name) {
            return 'N/A';
        }

        $fieldLabels = [
            'title' => 'Title',
            'description' => 'Description',
            'priority' => 'Priority',
            'status' => 'Status',
            'assigned_teknisi_nip' => 'Assigned Teknisi',
            'assigned_by_nip' => 'Assigned By',
            'resolution_notes' => 'Resolution Notes',
            'user_rating' => 'User Rating',
            'due_date' => 'Due Date',
        ];

        return $fieldLabels[$this->field_name] ?? ucfirst(str_replace('_', ' ', $this->field_name));
    }

    /**
     * Get formatted old value
     */
    public function getFormattedOldValueAttribute(): string
    {
        return $this->formatValue($this->old_value);
    }

    /**
     * Get formatted new value
     */
    public function getFormattedNewValueAttribute(): string
    {
        return $this->formatValue($this->new_value);
    }

    /**
     * Format value for display
     */
    private function formatValue($value): string
    {
        if ($value === null) {
            return 'Not set';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Get formatted timestamp
     */
    public function getFormattedTimestampAttribute(): string
    {
        return $this->created_at->format('d M Y, H:i:s');
    }

    /**
     * Get relative time (e.g., "2 hours ago")
     */
    public function getRelativeTimeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get severity badge color
     */
    public function getSeverityBadgeColorAttribute(): string
    {
        return match($this->severity) {
            'high' => 'danger',
            'medium' => 'warning',
            'low' => 'info',
            default => 'light',
        };
    }

    /**
     * Get category label
     */
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            self::FIELD_CATEGORY_BASIC => 'Basic',
            self::FIELD_CATEGORY_STATUS => 'Status',
            self::FIELD_CATEGORY_ASSIGNMENT => 'Assignment',
            self::FIELD_CATEGORY_PRIORITY => 'Priority',
            self::FIELD_CATEGORY_CONTENT => 'Content',
            self::FIELD_CATEGORY_SYSTEM => 'System',
            default => 'Other',
        };
    }

    /**
     * Get change summary for display
     */
    public function getChangeSummaryAttribute(): array
    {
        return [
            'action' => $this->action_label,
            'field' => $this->formatted_field_name,
            'old_value' => $this->formatted_old_value,
            'new_value' => $this->formatted_new_value,
            'performer' => $this->performer_name,
            'timestamp' => $this->formatted_timestamp,
            'reason' => $this->change_reason,
            'impact' => $this->getChangeImpact(),
        ];
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Get ticket history statistics
     */
    public static function getTicketHistoryStats(int $ticketId): array
    {
        $history = self::where('ticket_id', $ticketId)->get();

        return [
            'total_changes' => $history->count(),
            'significant_changes' => $history->where('severity', 'high')->count(),
            'last_activity' => $history->max('created_at'),
            'unique_performers' => $history->pluck('performed_by_nip')->unique()->count(),
            'changes_by_category' => $history->groupBy('category')->map->count(),
            'changes_by_action' => $history->groupBy('action')->map->count(),
        ];
    }

    /**
     * Get user activity summary
     */
    public static function getUserActivitySummary(string $userNip, string $userType, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $query = self::where('performed_by_nip', $userNip)
                    ->where('performed_by_type', $userType);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $activities = $query->get();

        return [
            'total_actions' => $activities->count(),
            'tickets_affected' => $activities->pluck('ticket_id')->unique()->count(),
            'actions_by_type' => $activities->groupBy('action')->map->count(),
            'significant_changes' => $activities->where('severity', 'high')->count(),
            'last_activity' => $activities->max('created_at'),
        ];
    }

    /**
     * Detect patterns in ticket changes
     */
    public static function detectChangePatterns(int $ticketId, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        $history = self::where('ticket_id', $ticketId)
                      ->where('created_at', '>=', $startDate)
                      ->orderBy('created_at')
                      ->get();

        $patterns = [];
        $statusChanges = $history->where('action', self::ACTION_STATUS_CHANGED);
        $assignments = $history->whereIn('action', [self::ACTION_ASSIGNED, self::ACTION_UNASSIGNED]);

        // Detect frequent status changes
        if ($statusChanges->count() > 5) {
            $patterns[] = [
                'type' => 'frequent_status_changes',
                'description' => 'Ticket status changed frequently',
                'severity' => 'medium',
                'count' => $statusChanges->count(),
            ];
        }

        // Detect frequent reassignments
        if ($assignments->count() > 3) {
            $patterns[] = [
                'type' => 'frequent_reassignments',
                'description' => 'Ticket reassigned multiple times',
                'severity' => 'high',
                'count' => $assignments->count(),
            ];
        }

        // Detect long resolution time
        $createdRecord = $history->where('action', self::ACTION_CREATED)->first();
        $resolvedRecord = $history->where('action', self::ACTION_RESOLVED)->first();

        if ($createdRecord && $resolvedRecord) {
            $resolutionTime = $createdRecord->created_at->diffInHours($resolvedRecord->created_at);
            if ($resolutionTime > 72) { // More than 3 days
                $patterns[] = [
                    'type' => 'long_resolution_time',
                    'description' => 'Ticket took longer than usual to resolve',
                    'severity' => 'medium',
                    'hours' => $resolutionTime,
                ];
            }
        }

        return $patterns;
    }

    /**
     * Generate compliance report for audit purposes
     */
    public static function generateComplianceReport(Carbon $startDate, Carbon $endDate): array
    {
        $history = self::whereBetween('created_at', [$startDate, $endDate])->get();

        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_records' => $history->count(),
                'unique_tickets' => $history->pluck('ticket_id')->unique()->count(),
                'unique_users' => $history->pluck('performed_by_nip')->unique()->count(),
            ],
            'actions_breakdown' => $history->groupBy('action')->map(function ($actions) {
                return [
                    'count' => $actions->count(),
                    'percentage' => 0, // Will be calculated below
                ];
            }),
            'sensitive_changes' => $history->where('is_sensitive', true)->count(),
            'automated_actions' => $history->where('is_automated', true)->count(),
            'high_severity_changes' => $history->where('severity', 'high')->count(),
        ];

        // Calculate percentages
        $totalActions = $report['summary']['total_records'];
        foreach ($report['actions_breakdown'] as $action => $data) {
            $report['actions_breakdown'][$action]['percentage'] = $totalActions > 0
                ? round(($data['count'] / $totalActions) * 100, 2)
                : 0;
        }

        return $report;
    }

    /**
     * Archive old history records for compliance
     */
    public static function archiveOldRecords(int $retentionDays = 2555): int // 7 years default
    {
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        return self::where('created_at', '<', $cutoffDate)
                  ->where('is_sensitive', false)
                  ->update([
                      'metadata->archived_at' => Carbon::now(),
                      'expires_at' => Carbon::now()->addYears(3), // Keep archived for 3 more years
                  ]);
    }

    /**
     * Get history records requiring attention
     */
    public static function getRecordsRequiringAttention(): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('severity', 'high')
                  ->where('created_at', '>=', Carbon::now()->subDays(7))
                  ->with('ticket')
                  ->orderBy('created_at', 'desc')
                  ->get();
    }

    // ==================== QUERY SCOPES ====================

    /**
     * Scope for specific action
     */
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for actions by performer
     */
    public function scopeByPerformer(Builder $query, string $performerNip, string $performerType): Builder
    {
        return $query->where('performed_by_nip', $performerNip)
                    ->where('performed_by_type', $performerType);
    }

    /**
     * Scope for actions by performer type
     */
    public function scopeByPerformerType(Builder $query, string $performerType): Builder
    {
        return $query->where('performed_by_type', $performerType);
    }

    /**
     * Scope for specific field changes
     */
    public function scopeByField(Builder $query, string $fieldName): Builder
    {
        return $query->where('field_name', $fieldName);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for today
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope for this week
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * Scope for this month
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
    }

    /**
     * Scope for high severity changes
     */
    public function scopeHighSeverity(Builder $query): Builder
    {
        return $query->where('severity', 'high');
    }

    /**
     * Scope for significant changes
     */
    public function scopeSignificant(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('severity', 'high')
              ->orWhereIn('action', [
                  self::ACTION_STATUS_CHANGED,
                  self::ACTION_PRIORITY_CHANGED,
                  self::ACTION_ASSIGNED,
                  self::ACTION_ESCALATED,
              ]);
        });
    }

    /**
     * Scope for sensitive changes
     */
    public function scopeSensitive(Builder $query): Builder
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Scope for automated actions
     */
    public function scopeAutomated(Builder $query): Builder
    {
        return $query->where('is_automated', true);
    }

    /**
     * Scope for manual actions
     */
    public function scopeManual(Builder $query): Builder
    {
        return $query->where('is_automated', false);
    }

    /**
     * Scope for specific ticket
     */
    public function scopeForTicket(Builder $query, int $ticketId): Builder
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Scope for tickets by user
     */
    public function scopeForUserTickets(Builder $query, string $userNip): Builder
    {
        return $query->whereHas('ticket', function ($q) use ($userNip) {
            $q->where('user_nip', $userNip);
        });
    }

    /**
     * Scope for tickets by teknisi
     */
    public function scopeForTeknisiTickets(Builder $query, string $teknisiNip): Builder
    {
        return $query->whereHas('ticket', function ($q) use ($teknisiNip) {
            $q->where('assigned_teknisi_nip', $teknisiNip);
        });
    }

    /**
     * Scope for specific category
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for changes with reasons
     */
    public function scopeWithReasons(Builder $query): Builder
    {
        return $query->whereNotNull('change_reason');
    }

    /**
     * Scope for changes without reasons
     */
    public function scopeWithoutReasons(Builder $query): Builder
    {
        return $query->whereNull('change_reason');
    }

    /**
     * Scope for recent changes
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * Scope for old records (for cleanup)
     */
    public function scopeOldRecords(Builder $query, int $days = 90): Builder
    {
        return $query->where('created_at', '<', Carbon::now()->subDays($days));
    }

    /**
     * Scope for search by description
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('description', 'like', "%{$search}%");
    }

    /**
     * Scope for IP address
     */
    public function scopeByIpAddress(Builder $query, string $ipAddress): Builder
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope for session tracking
     */
    public function scopeBySession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope for records expiring soon
     */
    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', Carbon::now()->addDays($days));
    }

    /**
     * Scope for compliance flagged records
     */
    public function scopeComplianceFlagged(Builder $query): Builder
    {
        return $query->whereNotNull('compliance_flags')
                    ->where('compliance_flags', '!=', '[]');
    }

    // ==================== COMPLIANCE FEATURES ====================

    /**
     * Mark record as sensitive for compliance
     */
    public function markAsSensitive(array $complianceFlags = []): bool
    {
        $this->is_sensitive = true;
        $this->compliance_flags = $complianceFlags;
        $this->expires_at = Carbon::now()->addYears(7); // Keep sensitive data for 7 years

        return $this->save();
    }

    /**
     * Mark record for automated processing
     */
    public function markAsAutomated(string $processName = null): bool
    {
        $this->is_automated = true;
        $this->metadata = array_merge($this->metadata ?? [], [
            'automated_process' => $processName,
            'processed_at' => Carbon::now(),
        ]);

        return $this->save();
    }

    /**
     * Set retention period for compliance
     */
    public function setRetentionPeriod(int $years): bool
    {
        $this->expires_at = Carbon::now()->addYears($years);
        return $this->save();
    }

    /**
     * Check if record is expired for cleanup
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Anonymize record for privacy compliance
     */
    public function anonymize(): bool
    {
        if (!$this->is_sensitive) {
            return false;
        }

        $this->performed_by_nip = 'ANONYMIZED';
        $this->ip_address = '0.0.0.0';
        $this->user_agent = 'ANONYMIZED';
        $this->metadata = array_merge($this->metadata ?? [], [
            'anonymized_at' => Carbon::now(),
        ]);

        return $this->save();
    }

    /**
     * Get compliance status
     */
    public function getComplianceStatusAttribute(): array
    {
        return [
            'is_sensitive' => $this->is_sensitive,
            'is_automated' => $this->is_automated,
            'is_expired' => $this->isExpired(),
            'expires_at' => $this->expires_at,
            'compliance_flags' => $this->compliance_flags ?? [],
            'retention_period' => $this->expires_at ? Carbon::parse($this->created_at)->diffInDays($this->expires_at) : null,
        ];
    }

    /**
     * Generate audit trail for compliance reporting
     */
    public static function generateAuditTrail(int $ticketId, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $query = self::forTicket($ticketId)->with('ticket');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $records = $query->orderBy('created_at')->get();

        return [
            'ticket_id' => $ticketId,
            'period' => [
                'start' => $startDate?->format('Y-m-d H:i:s'),
                'end' => $endDate?->format('Y-m-d H:i:s'),
            ],
            'total_records' => $records->count(),
            'records' => $records->map(function ($record) {
                return [
                    'id' => $record->id,
                    'timestamp' => $record->created_at->toISOString(),
                    'action' => $record->action,
                    'performer' => [
                        'nip' => $record->performed_by_nip,
                        'type' => $record->performed_by_type,
                        'name' => $record->performer_name,
                    ],
                    'field_changed' => $record->field_name,
                    'old_value' => $record->old_value,
                    'new_value' => $record->new_value,
                    'description' => $record->description,
                    'ip_address' => $record->ip_address,
                    'compliance_status' => $record->compliance_status,
                ];
            }),
        ];
    }
}