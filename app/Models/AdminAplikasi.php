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
use App\Models\Aplikasi;
use App\Models\Ticket;
use App\Models\Report;

class AdminAplikasi extends Authenticatable
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
    const PERMISSION_APPLICATION_MANAGEMENT = 'application_management';
    const PERMISSION_USER_MANAGEMENT = 'user_management';
    const PERMISSION_SYSTEM_ADMIN = 'system_admin';
    const PERMISSION_REPORT_VIEWER = 'report_viewer';
    const PERMISSION_TECHNICAL_SUPPORT = 'technical_support';

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
        'managed_applications',
        'technical_expertise',
        'email_verified_at',
        'password',
        'login_attempts',
        'locked_until',
        'performance_metrics',
        'expertise_areas',
        'certifications',
        'experience_years',
        'specializations',
        'supervisor_nip',
        'team_id',
        'is_lead',
        'max_applications_managed',
        'current_applications_count',
        'notification_preferences',
        'shift_schedule',
        'on_call_schedule',
        'technical_certifications',
        'vendor_relationships',
        'project_management_skills',
        'budget_responsibility',
        'security_clearance_level',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
        'permissions' => 'array',
        'managed_applications' => 'array',
        'performance_metrics' => 'array',
        'expertise_areas' => 'array',
        'certifications' => 'array',
        'specializations' => 'array',
        'shift_schedule' => 'array',
        'on_call_schedule' => 'array',
        'notification_preferences' => 'array',
        'technical_certifications' => 'array',
        'vendor_relationships' => 'array',
        'is_lead' => 'boolean',
        'budget_responsibility' => 'boolean',
        'password' => 'hashed',
        'max_applications_managed' => 'integer',
        'current_applications_count' => 'integer',
        'experience_years' => 'integer',
        'login_attempts' => 'integer',
        'security_clearance_level' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get applications managed by this admin
     */
    public function managedApplications(): BelongsToMany
    {
        return $this->belongsToMany(Aplikasi::class, 'admin_aplikasi_applications', 'admin_nip', 'aplikasi_id')
                    ->withPivot('role', 'permissions', 'assigned_at', 'access_level')
                    ->withTimestamps();
    }

    /**
     * Get users this admin supports
     */
    public function supportedUsers(): HasMany
    {
        return $this->hasMany(User::class, 'supported_by_nip', 'nip');
    }

    /**
     * Get teknisi this admin coordinates with
     */
    public function coordinatedTeknisi(): BelongsToMany
    {
        return $this->belongsToMany(Teknisi::class, 'admin_aplikasi_teknisi_coordination', 'admin_nip', 'teknisi_nip')
                    ->withPivot('coordination_level', 'project_id', 'notes')
                    ->withTimestamps();
    }

    /**
     * Get supervisor (if this admin has one)
     */
    public function supervisor(): HasOne
    {
        return $this->hasOne(AdminAplikasi::class, 'nip', 'supervisor_nip');
    }

    /**
     * Get team members (if this admin is a lead)
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(AdminAplikasi::class, 'supervisor_nip', 'nip');
    }

    /**
     * Get tickets related to managed applications
     */
    public function applicationTickets()
    {
        return Ticket::whereIn('aplikasi_id', $this->managed_applications ?? []);
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
     * Get vendors this admin manages
     */
    public function managedVendors()
    {
        if (!$this->vendor_relationships) {
            return collect();
        }

        return collect($this->vendor_relationships)->keys();
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
     * Check if admin can manage applications
     */
    public function canManageApplications(): bool
    {
        return $this->hasPermission(self::PERMISSION_APPLICATION_MANAGEMENT) ||
               $this->hasPermission(self::PERMISSION_SYSTEM_ADMIN);
    }

    /**
     * Check if admin can provide technical support
     */
    public function canProvideTechnicalSupport(): bool
    {
        return $this->hasPermission(self::PERMISSION_TECHNICAL_SUPPORT) ||
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
     * Check if admin is team lead
     */
    public function isTeamLead(): bool
    {
        return $this->is_lead && $this->teamMembers()->count() > 0;
    }

    /**
     * Get admin's role level
     */
    public function getRoleLevel(): int
    {
        return match(true) {
            $this->isSystemAdmin() => 4,
            $this->isTeamLead() => 3,
            $this->canManageApplications() => 2,
            $this->canProvideTechnicalSupport() => 1,
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
            'applications_managed' => $this->getManagedApplicationsCount(),
            'total_tickets' => $this->getTotalApplicationTickets(),
            'critical_applications' => $this->getCriticalApplicationsCount(),
            'system_health_score' => $this->getSystemHealthScore(),
            'vendor_relations' => $this->getVendorRelationsSummary(),
            'team_performance' => $this->getTeamPerformance(),
            'recent_activity' => $this->getRecentActivity(),
            'budget_utilization' => $this->getBudgetUtilization(),
        ];
    }

    /**
     * Get count of managed applications
     */
    public function getManagedApplicationsCount(): int
    {
        return $this->managed_applications ? count($this->managed_applications) : 0;
    }

    /**
     * Get total tickets for managed applications
     */
    public function getTotalApplicationTickets(): int
    {
        if (!$this->managed_applications) {
            return 0;
        }

        return Aplikasi::whereIn('id', $this->managed_applications)
                      ->withCount('tickets')
                      ->get()
                      ->sum('tickets_count');
    }

    /**
     * Get critical applications count
     */
    public function getCriticalApplicationsCount(): int
    {
        if (!$this->managed_applications) {
            return 0;
        }

        return Aplikasi::whereIn('id', $this->managed_applications)
                      ->whereIn('criticality', ['critical', 'high'])
                      ->count();
    }

    /**
     * Get system health score based on managed applications
     */
    public function getSystemHealthScore(): float
    {
        if (!$this->managed_applications) {
            return 100.0;
        }

        $applications = Aplikasi::whereIn('id', $this->managed_applications)->get();
        $totalScore = 0;

        foreach ($applications as $app) {
            $healthScore = match($app->health_status) {
                'excellent' => 100,
                'good' => 80,
                'fair' => 60,
                'poor' => 40,
                default => 50,
            };
            $totalScore += $healthScore;
        }

        return $applications->count() > 0 ? round($totalScore / $applications->count(), 2) : 100.0;
    }

    /**
     * Get vendor relations summary
     */
    public function getVendorRelationsSummary(): array
    {
        if (!$this->vendor_relationships) {
            return [];
        }

        $expiringContracts = 0;
        $activeContracts = 0;

        foreach ($this->vendor_relationships as $vendor) {
            if (isset($vendor['contract_expiry'])) {
                $expiry = Carbon::parse($vendor['contract_expiry']);
                if ($expiry->isPast()) {
                    $expiringContracts++;
                } elseif ($expiry->isBetween(Carbon::now(), Carbon::now()->addDays(30))) {
                    $expiringContracts++;
                } else {
                    $activeContracts++;
                }
            }
        }

        return [
            'total_vendors' => count($this->vendor_relationships),
            'active_contracts' => $activeContracts,
            'expiring_soon' => $expiringContracts,
        ];
    }

    /**
     * Get team performance metrics
     */
    public function getTeamPerformance(): array
    {
        if (!$this->isTeamLead()) {
            return [];
        }

        $teamMembers = $this->teamMembers()->get();
        $totalApplications = 0;
        $healthyApplications = 0;

        foreach ($teamMembers as $member) {
            $managedCount = $member->getManagedApplicationsCount();
            $totalApplications += $managedCount;

            if ($member->getSystemHealthScore() >= 80) {
                $healthyApplications += $managedCount;
            }
        }

        return [
            'team_size' => $teamMembers->count(),
            'total_applications_managed' => $totalApplications,
            'healthy_applications' => $healthyApplications,
            'health_rate' => $totalApplications > 0 ? round(($healthyApplications / $totalApplications) * 100, 2) : 0,
        ];
    }

    /**
     * Get recent activity summary
     */
    public function getRecentActivity(): array
    {
        return [
            'applications_updated_today' => $this->getApplicationsUpdatedToday(),
            'tickets_resolved_today' => $this->getTicketsResolvedToday(),
            'last_login' => $this->last_login_at?->diffForHumans(),
            'notifications_unread' => $this->notifications()->where('is_read', false)->count(),
        ];
    }

    /**
     * Get applications updated today
     */
    private function getApplicationsUpdatedToday(): int
    {
        if (!$this->managed_applications) {
            return 0;
        }

        return Aplikasi::whereIn('id', $this->managed_applications)
                      ->whereDate('updated_at', Carbon::today())
                      ->count();
    }

    /**
     * Get tickets resolved today for managed applications
     */
    private function getTicketsResolvedToday(): int
    {
        if (!$this->managed_applications) {
            return 0;
        }

        return Ticket::whereIn('aplikasi_id', $this->managed_applications)
                    ->where('status', 'resolved')
                    ->whereDate('resolved_at', Carbon::today())
                    ->count();
    }

    /**
     * Get budget utilization
     */
    public function getBudgetUtilization(): array
    {
        if (!$this->budget_responsibility) {
            return [];
        }

        // This would need actual budget data - placeholder for now
        return [
            'monthly_budget' => 0,
            'spent_this_month' => 0,
            'utilization_percentage' => 0,
            'remaining_budget' => 0,
        ];
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Assign application to manage
     */
    public function assignApplication(int $aplikasiId, string $role = 'manager', array $permissions = []): bool
    {
        if (!$this->canManageApplications()) {
            return false;
        }

        $currentApplications = $this->managed_applications ?? [];
        if (count($currentApplications) >= ($this->max_applications_managed ?? 10)) {
            return false;
        }

        if (!in_array($aplikasiId, $currentApplications)) {
            $currentApplications[] = $aplikasiId;
            $this->managed_applications = $currentApplications;
        }

        // Update pivot data if using many-to-many relationship
        $this->managedApplications()->syncWithoutDetaching([
            $aplikasiId => [
                'role' => $role,
                'permissions' => $permissions,
                'assigned_at' => Carbon::now(),
            ]
        ]);

        $this->updateApplicationCount();
        return $this->save();
    }

    /**
     * Remove application from management
     */
    public function removeApplication(int $aplikasiId): bool
    {
        if (!$this->canManageApplications()) {
            return false;
        }

        $currentApplications = $this->managed_applications ?? [];
        $this->managed_applications = array_filter($currentApplications, fn($id) => $id != $aplikasiId);

        // Remove from pivot
        $this->managedApplications()->detach($aplikasiId);

        $this->updateApplicationCount();
        return $this->save();
    }

    /**
     * Update current application count
     */
    public function updateApplicationCount(): void
    {
        $this->current_applications_count = $this->getManagedApplicationsCount();
        $this->save();
    }

    /**
     * Check if admin can manage more applications
     */
    public function canManageMoreApplications(): bool
    {
        $maxApps = $this->max_applications_managed ?? 10;
        return $this->getManagedApplicationsCount() < $maxApps;
    }

    /**
     * Get applications needing attention
     */
    public function getApplicationsNeedingAttention(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->managed_applications) {
            return collect();
        }

        return Aplikasi::whereIn('id', $this->managed_applications)
                      ->needingAttention()
                      ->get();
    }

    /**
     * Perform health check on managed applications
     */
    public function performHealthCheckOnApplications(): array
    {
        $results = [];
        $applications = $this->getApplicationsNeedingAttention();

        foreach ($applications as $application) {
            $healthCheck = $application->performHealthCheck();
            $results[] = [
                'application_id' => $application->id,
                'application_name' => $application->name,
                'health_status' => $healthCheck['status'],
                'issues' => $healthCheck['issues'],
                'warnings' => $healthCheck['warnings'],
            ];
        }

        return $results;
    }

    /**
     * Generate application management report
     */
    public function generateApplicationReport(Carbon $startDate, Carbon $endDate): array
    {
        $applications = Aplikasi::whereIn('id', $this->managed_applications ?? [])->get();

        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'applications_managed' => $applications->count(),
            'total_tickets' => 0,
            'applications_by_status' => [],
            'applications_by_criticality' => [],
            'health_summary' => [],
        ];

        foreach ($applications as $app) {
            $tickets = $app->tickets()->whereBetween('created_at', [$startDate, $endDate])->count();
            $report['total_tickets'] += $tickets;

            // Status breakdown
            $status = $app->status;
            $report['applications_by_status'][$status] = ($report['applications_by_status'][$status] ?? 0) + 1;

            // Criticality breakdown
            $criticality = $app->criticality;
            $report['applications_by_criticality'][$criticality] = ($report['applications_by_criticality'][$criticality] ?? 0) + 1;

            // Health summary
            $health = $app->health_status;
            $report['health_summary'][$health] = ($report['health_summary'][$health] ?? 0) + 1;
        }

        return $report;
    }

    /**
     * Coordinate with teknisi for application issue
     */
    public function coordinateWithTeknisi(int $aplikasiId, string $teknisiNip, string $issue, array $requirements = []): bool
    {
        if (!$this->canManageApplications()) {
            return false;
        }

        // Log coordination
        $this->logActivity('teknisi_coordination', [
            'aplikasi_id' => $aplikasiId,
            'teknisi_nip' => $teknisiNip,
            'issue' => $issue,
            'requirements' => $requirements,
        ]);

        // Update teknisi coordination relationship
        $this->coordinatedTeknisi()->syncWithoutDetaching([
            $teknisiNip => [
                'coordination_level' => 'active',
                'project_id' => 'APP_' . $aplikasiId,
                'notes' => $issue,
            ]
        ]);

        return true;
    }

    /**
     * Manage vendor relationship
     */
    public function manageVendorRelationship(string $vendorName, array $relationshipData): bool
    {
        $vendors = $this->vendor_relationships ?? [];
        $vendors[$vendorName] = array_merge($relationshipData, [
            'managed_by' => $this->nip,
            'last_updated' => Carbon::now(),
        ]);

        $this->vendor_relationships = $vendors;
        return $this->save();
    }

    /**
     * Log admin activity
     */
    private function logActivity(string $action, array $metadata = []): void
    {
        \Illuminate\Support\Facades\Log::info("Admin Aplikasi {$this->nip} performed action: {$action}", [
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
     * Get role badge for UI
     */
    public function getRoleBadgeAttribute(): string
    {
        $level = $this->getRoleLevel();

        return match($level) {
            4 => '<span class="badge badge-danger">System Admin</span>',
            3 => '<span class="badge badge-warning">Team Lead</span>',
            2 => '<span class="badge badge-info">Application Manager</span>',
            1 => '<span class="badge badge-primary">Technical Support</span>',
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
            self::PERMISSION_APPLICATION_MANAGEMENT => 'Application Management',
            self::PERMISSION_USER_MANAGEMENT => 'User Management',
            self::PERMISSION_SYSTEM_ADMIN => 'System Admin',
            self::PERMISSION_REPORT_VIEWER => 'Report Viewer',
            self::PERMISSION_TECHNICAL_SUPPORT => 'Technical Support',
        ];

        return implode(', ', array_map(fn($p) => $labels[$p] ?? $p, $this->permissions));
    }

    /**
     * Get formatted technical expertise
     */
    public function getFormattedTechnicalExpertiseAttribute(): string
    {
        if (!$this->technical_expertise || empty($this->technical_expertise)) {
            return 'No expertise specified';
        }

        return implode(', ', (array) $this->technical_expertise);
    }

    /**
     * Get formatted managed applications
     */
    public function getFormattedManagedApplicationsAttribute(): string
    {
        if (!$this->managed_applications || empty($this->managed_applications)) {
            return 'No applications managed';
        }

        $applications = Aplikasi::whereIn('id', $this->managed_applications)->pluck('name')->toArray();
        return implode(', ', $applications);
    }

    /**
     * Get profile completeness percentage
     */
    public function getProfileCompletenessAttribute(): float
    {
        $fields = [
            'name', 'email', 'phone', 'department', 'position',
            'technical_expertise', 'expertise_areas', 'certifications'
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
            'applications' => [
                'managed_count' => $this->getManagedApplicationsCount(),
                'max_capacity' => $this->max_applications_managed ?? 10,
                'critical_count' => $this->getCriticalApplicationsCount(),
            ],
            'performance' => [
                'system_health_score' => $this->getSystemHealthScore(),
                'total_tickets' => $this->getTotalApplicationTickets(),
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
     * Scope for team leads
     */
    public function scopeTeamLeads(Builder $query): Builder
    {
        return $query->where('is_lead', true);
    }

    /**
     * Scope for system admins
     */
    public function scopeSystemAdmins(Builder $query): Builder
    {
        return $query->whereJsonContains('permissions', self::PERMISSION_SYSTEM_ADMIN);
    }

    /**
     * Scope for application managers
     */
    public function scopeApplicationManagers(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereJsonContains('permissions', self::PERMISSION_APPLICATION_MANAGEMENT)
              ->orWhereJsonContains('permissions', self::PERMISSION_SYSTEM_ADMIN);
        });
    }

    /**
     * Scope for technical support admins
     */
    public function scopeTechnicalSupport(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereJsonContains('permissions', self::PERMISSION_TECHNICAL_SUPPORT)
              ->orWhereJsonContains('permissions', self::PERMISSION_SYSTEM_ADMIN);
        });
    }

    /**
     * Scope for admins managing specific application
     */
    public function scopeManagingApplication(Builder $query, int $aplikasiId): Builder
    {
        return $query->whereJsonContains('managed_applications', $aplikasiId);
    }

    /**
     * Scope for admins with available capacity
     */
    public function scopeWithAvailableCapacity(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where(function ($q) {
                        $q->whereNull('max_applications_managed')
                          ->orWhereRaw('current_applications_count < max_applications_managed');
                    });
    }

    /**
     * Scope for admins at capacity
     */
    public function scopeAtCapacity(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->whereNotNull('max_applications_managed')
                    ->whereRaw('current_applications_count >= max_applications_managed');
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
     * Scope for admins by technical expertise
     */
    public function scopeByTechnicalExpertise(Builder $query, string $expertise): Builder
    {
        return $query->whereJsonContains('technical_expertise', $expertise);
    }

    /**
     * Scope for admins with certifications
     */
    public function scopeWithCertifications(Builder $query): Builder
    {
        return $query->whereNotNull('certifications')
                    ->where('certifications', '!=', '[]');
    }

    /**
     * Scope for admins with budget responsibility
     */
    public function scopeWithBudgetResponsibility(Builder $query): Builder
    {
        return $query->where('budget_responsibility', true);
    }

    /**
     * Scope for admins by security clearance
     */
    public function scopeBySecurityClearance(Builder $query, int $minLevel): Builder
    {
        return $query->where('security_clearance_level', '>=', $minLevel);
    }

    /**
     * Scope for on-call admins
     */
    public function scopeOnCall(Builder $query): Builder
    {
        return $query->whereNotNull('on_call_schedule')
                    ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for admins managing critical applications
     */
    public function scopeManagingCriticalApplications(Builder $query): Builder
    {
        return $query->whereHas('managedApplications', function ($q) {
            $q->whereIn('criticality', ['critical', 'high']);
        });
    }

    /**
     * Scope for admins with vendor relationships
     */
    public function scopeWithVendorRelationships(Builder $query): Builder
    {
        return $query->whereNotNull('vendor_relationships')
                    ->where('vendor_relationships', '!=', '[]');
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
            'bg-emerald-500', 'bg-green-500', 'bg-teal-500', 'bg-cyan-500',
            'bg-sky-500', 'bg-blue-500', 'bg-indigo-500', 'bg-violet-500'
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