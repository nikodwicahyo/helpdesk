<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class AdminHelpdesk extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'nip';
    public $incrementing = false;
    protected $keyType = 'string';

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    // Permission levels
    const PERMISSION_VIEW_ONLY = 'view_only';
    const PERMISSION_TICKET_MANAGEMENT = 'ticket_management';
    const PERMISSION_USER_MANAGEMENT = 'user_management';
    const PERMISSION_SYSTEM_ADMIN = 'system_admin';
    const PERMISSION_REPORT_VIEWER = 'report_viewer';

    protected $fillable = [
        'nip',
        'name',
        'email',
        'phone',
        'department',
        'position',
        'status',
        'role',
        'permissions',
        'specialization',
        'email_verified_at',
        'password',
        'login_attempts',
        'locked_until',
        'performance_metrics',
        'managed_departments',
        'expertise_areas',
        'certifications',
        'experience_years',
        'workload_capacity',
        'current_workload',
        'supervisor_nip',
        'team_id',
        'shift_schedule',
        'is_supervisor',
        'max_tickets_per_day',
        'auto_assign_enabled',
        'notification_preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'locked_until' => 'datetime',
        'permissions' => 'array',
        'performance_metrics' => 'array',
        'managed_departments' => 'array',
        'expertise_areas' => 'array',
        'certifications' => 'array',
        'shift_schedule' => 'array',
        'notification_preferences' => 'array',
        'is_supervisor' => 'boolean',
        'auto_assign_enabled' => 'boolean',
        'password' => 'hashed',
        'workload_capacity' => 'integer',
        'current_workload' => 'integer',
        'max_tickets_per_day' => 'integer',
        'experience_years' => 'integer',
        'login_attempts' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get tickets assigned by this admin
     */
    public function ticketsAssigned(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_by_nip', 'nip');
    }

    /**
     * Get users managed by this admin
     */
    public function managedUsers(): HasMany
    {
        return $this->hasMany(User::class, 'managed_by_nip', 'nip');
    }

    /**
     * Get teknisi supervised by this admin
     */
    public function supervisedTeknisi(): HasMany
    {
        return $this->hasMany(Teknisi::class, 'supervisor_nip', 'nip');
    }

    /**
     * Get supervisor (if this admin has one)
     */
    public function supervisor(): HasOne
    {
        return $this->hasOne(AdminHelpdesk::class, 'nip', 'supervisor_nip');
    }

    /**
     * Get team members (if this admin is a supervisor)
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(AdminHelpdesk::class, 'supervisor_nip', 'nip');
    }

    /**
     * Get applications this admin has access to
     */
    public function accessibleApplications(): BelongsToMany
    {
        return $this->belongsToMany(Aplikasi::class, 'admin_helpdesk_applications', 'admin_nip', 'aplikasi_id')
                    ->withPivot('access_level', 'permissions', 'granted_at')
                    ->withTimestamps();
    }

        /**
     * Get notifications for this admin using polymorphic relationship
     */
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Get reports created by this admin
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'created_by_nip', 'nip');
    }

    /**
     * Get ticket history records created by this admin
     */
    public function ticketHistory(): HasMany
    {
        return $this->hasMany(TicketHistory::class, 'user_nip', 'nip');
    }

    /**
     * Get performance metrics summary
     */
    public function getPerformanceMetricsAttribute(): array
    {
        $metrics = $this->performance_metrics ?? [];

        return [
            'total_tickets_assigned' => $this->getTotalTicketsAssigned(),
            'active_tickets' => $this->getActiveTicketsCount(),
            'resolved_today' => $this->getResolvedTodayCount(),
            'avg_resolution_time' => $this->getAverageResolutionTime(),
            'workload_utilization' => $this->getWorkloadMetrics()['utilization_percentage'] ?? 0,
            'team_resolution_rate' => $this->getTeamPerformance()['resolution_rate'] ?? 0,
            'calculated_at' => $metrics['calculated_at'] ?? Carbon::now(),
        ];
    }

    // ==================== ROLE MANAGEMENT ====================

    /**
     * Check if admin has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->permissions) {
            return false;
        }

        return in_array($permission, $this->permissions) ||
               in_array(self::PERMISSION_SYSTEM_ADMIN, $this->permissions);
    }

    /**
     * Check if admin can manage tickets
     */
    public function canManageTickets(): bool
    {
        return $this->hasPermission(self::PERMISSION_TICKET_MANAGEMENT) ||
               $this->hasPermission(self::PERMISSION_SYSTEM_ADMIN);
    }

    /**
     * Check if admin can manage users
     */
    public function canManageUsers(): bool
    {
        return $this->hasPermission(self::PERMISSION_USER_MANAGEMENT) ||
               $this->hasPermission(self::PERMISSION_SYSTEM_ADMIN);
    }

    /**
     * Check if admin can view reports
     */
    public function canViewReports(): bool
    {
        return $this->hasPermission(self::PERMISSION_REPORT_VIEWER) ||
               $this->hasPermission(self::PERMISSION_SYSTEM_ADMIN);
    }

    /**
     * Check if admin is system administrator
     */
    public function isSystemAdmin(): bool
    {
        return $this->hasPermission(self::PERMISSION_SYSTEM_ADMIN);
    }

    /**
     * Check if admin is supervisor
     */
    public function isSupervisor(): bool
    {
        return $this->is_supervisor && $this->teamMembers()->count() > 0;
    }

    /**
     * Get admin's role level
     */
    public function getRoleLevel(): int
    {
        return match(true) {
            $this->isSystemAdmin() => 4,
            $this->isSupervisor() => 3,
            $this->canManageUsers() => 2,
            $this->canManageTickets() => 1,
            default => 0,
        };
    }

    /**
     * Grant permission to admin
     */
    public function grantPermission(string $permission): bool
    {
        if ($this->hasPermission($permission)) {
            return true;
        }

        $permissions = $this->permissions ?? [];
        $permissions[] = $permission;
        $this->permissions = array_unique($permissions);

        return $this->save();
    }

    /**
     * Revoke permission from admin
     */
    public function revokePermission(string $permission): bool
    {
        if (!$this->hasPermission($permission)) {
            return true;
        }

        $permissions = array_filter($this->permissions ?? [], fn($p) => $p !== $permission);
        $this->permissions = array_values($permissions);

        return $this->save();
    }

    // ==================== DASHBOARD ANALYTICS ====================

    /**
     * Get dashboard statistics
     */
    public function getDashboardStatsAttribute(): array
    {
        return [
            'total_tickets_assigned' => $this->getTotalTicketsAssigned(),
            'active_tickets' => $this->getActiveTicketsCount(),
            'resolved_today' => $this->getResolvedTodayCount(),
            'average_resolution_time' => $this->getAverageResolutionTime(),
            'team_performance' => $this->getTeamPerformance(),
            'department_overview' => $this->getDepartmentOverview(),
            'workload_metrics' => $this->getWorkloadMetrics(),
            'recent_activity' => $this->getRecentActivity(),
        ];
    }

    /**
     * Get total tickets assigned by this admin
     */
    public function getTotalTicketsAssigned(): int
    {
        return $this->ticketsAssigned()->count();
    }

    /**
     * Get active tickets count
     */
    public function getActiveTicketsCount(): int
    {
        return $this->ticketsAssigned()
                   ->whereIn('status', ['open', 'in_progress', 'waiting_response'])
                   ->count();
    }

    /**
     * Get tickets resolved today
     */
    public function getResolvedTodayCount(): int
    {
        return $this->ticketsAssigned()
                   ->where('status', 'resolved')
                   ->whereDate('resolved_at', Carbon::today())
                   ->count();
    }

    /**
     * Get average resolution time in minutes
     */
    public function getAverageResolutionTime(): ?float
    {
        $resolvedTickets = $this->ticketsAssigned()
                               ->where('status', 'resolved')
                               ->whereNotNull('resolution_time_minutes')
                               ->get();

        return $resolvedTickets->isEmpty() ? null : round($resolvedTickets->avg('resolution_time_minutes'), 2);
    }

    /**
     * Get team performance metrics
     */
    public function getTeamPerformance(): array
    {
        if (!$this->isSupervisor()) {
            return [];
        }

        $teamMembers = $this->teamMembers()->get();
        $totalTickets = 0;
        $resolvedTickets = 0;

        foreach ($teamMembers as $member) {
            $totalTickets += $member->getTotalTicketsAssigned();
            $resolvedTickets += $member->ticketsAssigned()
                                     ->where('status', 'resolved')
                                     ->count();
        }

        return [
            'team_size' => $teamMembers->count(),
            'total_tickets_handled' => $totalTickets,
            'total_resolved' => $resolvedTickets,
            'resolution_rate' => $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 2) : 0,
        ];
    }

    /**
     * Get department overview
     */
    public function getDepartmentOverview(): array
    {
        $department = $this->department;
        if (!$department) {
            return [];
        }

        return [
            'department' => $department,
            'total_users' => User::where('department', $department)->count(),
            'active_tickets' => Ticket::whereHas('user', function ($q) use ($department) {
                $q->where('department', $department);
            })->whereIn('status', ['open', 'in_progress'])->count(),
            'resolved_this_month' => Ticket::whereHas('user', function ($q) use ($department) {
                $q->where('department', $department);
            })->where('status', 'resolved')
             ->whereMonth('resolved_at', Carbon::now()->month)->count(),
        ];
    }

    /**
     * Get workload metrics
     */
    public function getWorkloadMetrics(): array
    {
        $maxCapacity = $this->workload_capacity ?? 50;
        $currentWorkload = $this->current_workload ?? $this->getActiveTicketsCount();

        return [
            'current_workload' => $currentWorkload,
            'max_capacity' => $maxCapacity,
            'utilization_percentage' => $maxCapacity > 0 ? round(($currentWorkload / $maxCapacity) * 100, 2) : 0,
            'available_capacity' => max(0, $maxCapacity - $currentWorkload),
            'is_overloaded' => $currentWorkload > $maxCapacity,
        ];
    }

    /**
     * Get recent activity summary
     */
    public function getRecentActivity(): array
    {
        return [
            'tickets_assigned_today' => $this->ticketsAssigned()
                                           ->whereDate('created_at', Carbon::today())
                                           ->count(),
            'tickets_resolved_today' => $this->getResolvedTodayCount(),
            'last_login' => $this->last_login_at?->diffForHumans(),
            'notifications_unread' => $this->notifications()->where('is_read', false)->count(),
        ];
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Assign ticket to teknisi
     */
    public function assignTicketToTeknisi(Ticket $ticket, string $teknisiNip, string $notes = null): bool
    {
        if (!$this->canManageTickets()) {
            return false;
        }

        $assigned = $ticket->assignToTeknisi($teknisiNip, $this->nip, $notes);

        if ($assigned) {
            $this->updateWorkload();
            $this->logActivity('ticket_assigned', [
                'ticket_id' => $ticket->id,
                'teknisi_nip' => $teknisiNip,
                'notes' => $notes,
            ]);
        }

        return $assigned;
    }

    /**
     * Update current workload
     */
    public function updateWorkload(): void
    {
        $this->current_workload = $this->getActiveTicketsCount();
        $this->save();
    }

    /**
     * Check if admin can accept more assignments
     */
    public function canAcceptMoreAssignments(): bool
    {
        $maxCapacity = $this->workload_capacity ?? 50;
        return $this->getActiveTicketsCount() < $maxCapacity;
    }

    /**
     * Get available teknisi for assignment
     */
    public function getAvailableTeknisi(): \Illuminate\Database\Eloquent\Collection
    {
        return Teknisi::available()
                     ->where(function ($query) {
                         // Filter by managed departments if applicable
                         if ($this->managed_departments) {
                             $query->whereIn('department', $this->managed_departments);
                         }
                     })
                     ->get();
    }

    /**
     * Auto-assign ticket using intelligent algorithm
     */
    public function autoAssignTicket(Ticket $ticket): ?Teknisi
    {
        if (!$this->canManageTickets() || !$this->auto_assign_enabled) {
            return null;
        }

        $availableTeknisi = $this->getAvailableTeknisi();

        if ($availableTeknisi->isEmpty()) {
            return null;
        }

        // Find best teknisi based on workload and expertise
        $bestTeknisi = null;
        $bestScore = -1;

        foreach ($availableTeknisi as $teknisi) {
            $score = $teknisi->calculateAssignmentScore($ticket);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTeknisi = $teknisi;
            }
        }

        if ($bestTeknisi && $ticket->assignToTeknisi($bestTeknisi->nip, $this->nip, 'Auto-assigned by system')) {
            $this->updateWorkload();
            $bestTeknisi->updateWorkloadScore();

            $this->logActivity('auto_assignment', [
                'ticket_id' => $ticket->id,
                'teknisi_nip' => $bestTeknisi->nip,
                'score' => $bestScore,
            ]);

            return $bestTeknisi;
        }

        return null;
    }

    /**
     * Generate performance report
     */
    public function generatePerformanceReport(Carbon $startDate, Carbon $endDate): array
    {
        $tickets = $this->ticketsAssigned()
                       ->whereBetween('created_at', [$startDate, $endDate])
                       ->get();

        $resolvedTickets = $tickets->where('status', 'resolved');

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'tickets_assigned' => $tickets->count(),
            'tickets_resolved' => $resolvedTickets->count(),
            'resolution_rate' => $tickets->count() > 0 ? round(($resolvedTickets->count() / $tickets->count()) * 100, 2) : 0,
            'avg_resolution_time' => $resolvedTickets->count() > 0 ? $resolvedTickets->avg('resolution_time_minutes') : null,
            'department_breakdown' => $this->getDepartmentBreakdown($tickets),
            'priority_breakdown' => $this->getPriorityBreakdown($tickets),
        ];
    }

    /**
     * Get department breakdown for tickets
     */
    private function getDepartmentBreakdown($tickets): array
    {
        return $tickets->groupBy('user.department')
                      ->map(function ($deptTickets) {
                          return [
                              'count' => $deptTickets->count(),
                              'resolved' => $deptTickets->where('status', 'resolved')->count(),
                          ];
                      })
                      ->toArray();
    }

    /**
     * Get priority breakdown for tickets
     */
    private function getPriorityBreakdown($tickets): array
    {
        return $tickets->groupBy('priority')
                      ->map(function ($priorityTickets) {
                          return [
                              'count' => $priorityTickets->count(),
                              'resolved' => $priorityTickets->where('status', 'resolved')->count(),
                          ];
                      })
                      ->toArray();
    }

    /**
     * Log admin activity
     */
    private function logActivity(string $action, array $metadata = []): void
    {
        \Illuminate\Support\Facades\Log::info("Admin {$this->nip} performed action: {$action}", [
            'admin_nip' => $this->nip,
            'action' => $action,
            'metadata' => $metadata,
            'timestamp' => Carbon::now(),
        ]);
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Get status badge color for UI
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'secondary',
            self::STATUS_SUSPENDED => 'danger',
            default => 'light',
        };
    }

    /**
     * Get status label for UI
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_SUSPENDED => 'Suspended',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted last login
     */
    public function getFormattedLastLoginAttribute(): string
    {
        if (!$this->last_login_at) {
            return 'Never';
        }

        return $this->last_login_at->diffForHumans();
    }

    /**
     * Get workload status badge
     */
    public function getWorkloadStatusBadgeAttribute(): string
    {
        $metrics = $this->workload_metrics;

        if ($metrics['is_overloaded'] ?? false) {
            return '<span class="badge badge-danger">Overloaded</span>';
        }

        $percentage = $metrics['utilization_percentage'] ?? 0;
        if ($percentage >= 80) {
            return '<span class="badge badge-warning">High Load</span>';
        }

        return '<span class="badge badge-success">Normal Load</span>';
    }

    /**
     * Get role badge for UI
     */
    public function getRoleBadgeAttribute(): string
    {
        $level = $this->getRoleLevel();

        return match($level) {
            4 => '<span class="badge badge-danger">System Admin</span>',
            3 => '<span class="badge badge-warning">Supervisor</span>',
            2 => '<span class="badge badge-info">Manager</span>',
            1 => '<span class="badge badge-primary">Staff</span>',
            default => '<span class="badge badge-light">Viewer</span>',
        };
    }

    /**
     * Get formatted permissions
     */
    public function getFormattedPermissionsAttribute(): string
    {
        if (!$this->permissions || empty($this->permissions)) {
            return 'No permissions';
        }

        $labels = [
            self::PERMISSION_VIEW_ONLY => 'View Only',
            self::PERMISSION_TICKET_MANAGEMENT => 'Ticket Management',
            self::PERMISSION_USER_MANAGEMENT => 'User Management',
            self::PERMISSION_SYSTEM_ADMIN => 'System Admin',
            self::PERMISSION_REPORT_VIEWER => 'Report Viewer',
        ];

        return implode(', ', array_map(fn($p) => $labels[$p] ?? $p, $this->permissions));
    }

    /**
     * Get profile completeness percentage
     */
    public function getProfileCompletenessAttribute(): float
    {
        $fields = [
            'name', 'email', 'phone', 'department', 'position',
            'specialization', 'expertise_areas', 'certifications'
        ];

        $completedFields = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completedFields++;
            }
        }

        return round(($completedFields / count($fields)) * 100, 1);
    }

    /**
     * Get formatted experience
     */
    public function getFormattedExperienceAttribute(): string
    {
        if (!$this->experience_years) {
            return 'Not specified';
        }

        return $this->experience_years . ' years';
    }

    /**
     * Get admin summary for dashboard
     */
    public function getAdminSummaryAttribute(): array
    {
        return [
            'nip' => $this->nip,
            'name' => $this->name,
            'department' => $this->department,
            'position' => $this->position,
            'status' => [
                'value' => $this->status,
                'label' => $this->status_label,
                'badge_color' => $this->status_badge_color,
            ],
            'role' => [
                'level' => $this->getRoleLevel(),
                'badge' => $this->role_badge,
                'permissions' => $this->formatted_permissions,
            ],
            'workload' => $this->workload_metrics,
            'performance' => [
                'total_assigned' => $this->getTotalTicketsAssigned(),
                'active_tickets' => $this->getActiveTicketsCount(),
                'avg_resolution_time' => $this->getAverageResolutionTime(),
            ],
            'last_login' => $this->formatted_last_login,
            'profile_completeness' => $this->profile_completeness,
        ];
    }

    // ==================== QUERY SCOPES ====================

    /**
     * Scope for active admins
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for admins by department
     */
    public function scopeByDepartment(Builder $query, string $department): Builder
    {
        return $query->where('department', $department);
    }

    /**
     * Scope for supervisors
     */
    public function scopeSupervisors(Builder $query): Builder
    {
        return $query->where('is_supervisor', true);
    }

    /**
     * Scope for system admins
     */
    public function scopeSystemAdmins(Builder $query): Builder
    {
        return $query->whereJsonContains('permissions', self::PERMISSION_SYSTEM_ADMIN);
    }

    /**
     * Scope for admins who can manage tickets
     */
    public function scopeTicketManagers(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereJsonContains('permissions', self::PERMISSION_TICKET_MANAGEMENT)
              ->orWhereJsonContains('permissions', self::PERMISSION_SYSTEM_ADMIN);
        });
    }

    /**
     * Scope for admins by role level
     */
    public function scopeByRoleLevel(Builder $query, int $minLevel): Builder
    {
        return $query->whereHas('admins', function ($q) use ($minLevel) {
            // This would need a more complex implementation based on permissions
            $q->where('status', self::STATUS_ACTIVE);
        });
    }

    /**
     * Scope for available admins (not overloaded)
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where(function ($q) {
                        $q->whereNull('workload_capacity')
                          ->orWhereRaw('current_workload < workload_capacity');
                    });
    }

    /**
     * Scope for overloaded admins
     */
    public function scopeOverloaded(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->whereNotNull('workload_capacity')
                    ->whereRaw('current_workload >= workload_capacity');
    }

    /**
     * Scope for recently active admins
     */
    public function scopeRecentlyActive(Builder $query, int $hours = 24): Builder
    {
        return $query->where('last_login_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * Scope for admins with specific permission
     */
    public function scopeWithPermission(Builder $query, string $permission): Builder
    {
        return $query->whereJsonContains('permissions', $permission);
    }

    /**
     * Scope for search by name, email, or NIP
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('nip', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for admins by experience level
     */
    public function scopeByExperience(Builder $query, int $minYears): Builder
    {
        return $query->where('experience_years', '>=', $minYears);
    }

    /**
     * Scope for experienced admins
     */
    public function scopeExperienced(Builder $query, int $minYears = 3): Builder
    {
        return $query->where('experience_years', '>=', $minYears);
    }

    /**
     * Scope for admins in team
     */
    public function scopeInTeam(Builder $query, string $supervisorNip): Builder
    {
        return $query->where('supervisor_nip', $supervisorNip);
    }

    /**
     * Scope for admins by specialization
     */
    public function scopeBySpecialization(Builder $query, string $specialization): Builder
    {
        return $query->where('specialization', $specialization);
    }

    /**
     * Scope for admins with auto-assign enabled
     */
    public function scopeAutoAssignEnabled(Builder $query): Builder
    {
        return $query->where('auto_assign_enabled', true);
    }

    /**
     * Scope for admins by managed departments
     */
    public function scopeManagesDepartment(Builder $query, string $department): Builder
    {
        return $query->whereJsonContains('managed_departments', $department);
    }

    /**
     * Get user initials (2 characters max)
     */
    public function getInitialsAttribute(): string
    {
        $name = trim($this->name);
        if (empty($name)) {
            return 'A'; // Default for Admin
        }

        // Split name by spaces and get first letter of first two parts
        $words = explode(' ', $name);
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(mb_substr($word, 0, 1));
            }
        }

        return empty($initials) ? 'A' : substr($initials, 0, 2);
    }

    /**
     * Get user initials with styling for frontend display
     */
    public function getFormattedInitialsAttribute(): array
    {
        $initials = $this->initials;

        // Generate background color based on name hash for consistency
        $colors = [
            'bg-red-500', 'bg-rose-500', 'bg-pink-500', 'bg-fuchsia-500',
            'bg-purple-500', 'bg-violet-500', 'bg-indigo-500', 'bg-blue-500'
        ];

        $colorIndex = crc32($this->name) % count($colors);
        $backgroundColor = $colors[$colorIndex];

        return [
            'text' => $initials,
            'background_color' => $backgroundColor,
            'text_color' => 'text-white',
        ];
    }

    }