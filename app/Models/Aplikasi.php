<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class Aplikasi extends Model
{
    use HasFactory;

    // Application status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_DEPRECATED = 'deprecated';
    const STATUS_DEVELOPMENT = 'development';

    // Criticality levels
    const CRITICALITY_LOW = 'low';
    const CRITICALITY_MEDIUM = 'medium';
    const CRITICALITY_HIGH = 'high';
    const CRITICALITY_CRITICAL = 'critical';

    // Categories
    const CATEGORY_WEB = 'web';
    const CATEGORY_DESKTOP = 'desktop';
    const CATEGORY_MOBILE = 'mobile';
    const CATEGORY_SERVICE = 'service';
    const CATEGORY_DATABASE = 'database';
    const CATEGORY_NETWORK = 'network';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_BUSINESS = 'business';

    protected $fillable = [
        'name',
        'code',
        'description',
        'version',
        'status',
        'criticality',
        'category',
        'vendor',
        'contact_person',
        'contact_email',
        'contact_phone',
        'technical_documentation',
        'supported_os',
        'supported_browsers',
        'server_location',
        'backup_schedule',
        'notes',
        'icon',
        'sort_order',
        'admin_aplikasi_nip', // Admin aplikasi field
        'backup_admin_nip', // Backup admin field
        // Enhanced fields for Week 6-7 requirements
        'current_version',
        'latest_version',
        'release_date',
        'last_updated',
        'maintenance_window_start',
        'maintenance_window_end',
        'sla_hours',
        'monitoring_enabled',
        'health_check_url',
        'documentation_url',
        'source_code_url',
        'license_type',
        'max_users',
        'current_users',
        'uptime_percentage',
        'response_time_avg',
        'error_rate',
        'last_health_check',
        'health_status',
        'is_maintenance_mode',
        'maintenance_reason',
        'maintenance_start_time',
        'maintenance_end_time',
        'deprecation_date',
        'replacement_application_id',
        'business_owner',
        'technical_owner',
        'cost_center',
        'monthly_cost',
        'vendor_contract_expiry',
        'license_expiry',
        'security_classification',
        'backup_retention_days',
        'disaster_recovery_plan',
        'change_management_required',
        'approval_workflow_enabled',
    ];

    protected $casts = [
        'supported_os' => 'array',
        'supported_browsers' => 'array',
        'release_date' => 'datetime',
        'last_updated' => 'datetime',
        'maintenance_window_start' => 'datetime',
        'maintenance_window_end' => 'datetime',
        'last_health_check' => 'datetime',
        'is_maintenance_mode' => 'boolean',
        'monitoring_enabled' => 'boolean',
        'change_management_required' => 'boolean',
        'approval_workflow_enabled' => 'boolean',
        'maintenance_start_time' => 'datetime',
        'maintenance_end_time' => 'datetime',
        'deprecation_date' => 'datetime',
        'vendor_contract_expiry' => 'datetime',
        'license_expiry' => 'datetime',
        'uptime_percentage' => 'decimal:2',
        'response_time_avg' => 'decimal:2',
        'error_rate' => 'decimal:4',
        'monthly_cost' => 'decimal:2',
        'max_users' => 'integer',
        'current_users' => 'integer',
        'sla_hours' => 'integer',
        'backup_retention_days' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get kategori masalah for this aplikasi
     */
    public function kategoriMasalahs(): HasMany
    {
        return $this->hasMany(KategoriMasalah::class, 'aplikasi_id');
    }

    /**
     * Get tickets for this aplikasi
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'aplikasi_id');
    }

    /**
     * Get teknisi who are experts in this aplikasi
     */
    public function expertTeknisis(): BelongsToMany
    {
        return $this->belongsToMany(Teknisi::class, 'teknisi_aplikasi_expertise', 'aplikasi_id', 'teknisi_nip')
                    ->withPivot('expertise_level', 'certified_at', 'notes')
                    ->withTimestamps();
    }

    /**
     * Get teknisi assigned to this aplikasi
     */
    public function assignedTeknis(): BelongsToMany
    {
        return $this->belongsToMany(Teknisi::class, 'teknisi_aplikasi_assignments', 'aplikasi_id', 'teknisi_nip')
                    ->withPivot('assigned_by_nip', 'assigned_at', 'notes')
                    ->withTimestamps();
    }

    /**
     * Get admin aplikasi who manage this application (legacy relationship)
     */
    public function adminAplikasis(): BelongsToMany
    {
        return $this->belongsToMany(AdminAplikasi::class, 'admin_aplikasi_applications', 'aplikasi_id', 'admin_aplikasi_nip')
                    ->withPivot('role', 'permissions', 'assigned_at')
                    ->withTimestamps();
    }

    /**
     * Get the primary admin aplikasi (AdminAplikasi model only) for this application
     */
    public function adminAplikasi(): HasOne
    {
        return $this->hasOne(AdminAplikasi::class, 'nip', 'admin_aplikasi_nip');
    }

    /**
     * Get the primary admin (either AdminHelpdesk or AdminAplikasi)
     */
    public function getPrimaryAdmin()
    {
        if (!$this->admin_aplikasi_nip) {
            return null;
        }

        // Try to find in admin_helpdesks first
        $adminHelpdesk = AdminHelpdesk::where('nip', $this->admin_aplikasi_nip)->first();
        if ($adminHelpdesk) {
            return $adminHelpdesk;
        }

        // If not found in admin_helpdesks, try admin_aplikasis
        return AdminAplikasi::where('nip', $this->admin_aplikasi_nip)->first();
    }

    /**
     * Get the backup admin aplikasi (AdminAplikasi model only) for this application
     */
    public function backupAdmin(): HasOne
    {
        return $this->hasOne(AdminAplikasi::class, 'nip', 'backup_admin_nip');
    }

    /**
     * Get the backup admin (either AdminHelpdesk or AdminAplikasi)
     */
    public function getBackupAdmin()
    {
        if (!$this->backup_admin_nip) {
            return null;
        }

        // Try to find in admin_helpdesks first
        $adminHelpdesk = AdminHelpdesk::where('nip', $this->backup_admin_nip)->first();
        if ($adminHelpdesk) {
            return $adminHelpdesk;
        }

        // If not found in admin_helpdesks, try admin_aplikasis
        return AdminAplikasi::where('nip', $this->backup_admin_nip)->first();
    }

    /**
     * Get the type of the primary admin
     */
    public function getPrimaryAdminType(): ?string
    {
        if (!$this->admin_aplikasi_nip) {
            return null;
        }

        // Check if exists in admin_helpdesks
        $adminHelpdesk = AdminHelpdesk::where('nip', $this->admin_aplikasi_nip)->exists();
        if ($adminHelpdesk) {
            return 'admin_helpdesk';
        }

        // Check if exists in admin_aplikasis
        $adminAplikasi = AdminAplikasi::where('nip', $this->admin_aplikasi_nip)->exists();
        if ($adminAplikasi) {
            return 'admin_aplikasi';
        }

        return null;
    }

    /**
     * Assign primary admin to this application
     */
    public function assignPrimaryAdmin(string $adminNip): bool
    {
        $this->admin_aplikasi_nip = $adminNip;
        return $this->save();
    }

    /**
     * Assign backup admin to this application
     */
    public function assignBackupAdmin(string $adminNip): bool
    {
        $this->backup_admin_nip = $adminNip;
        return $this->save();
    }

    /**
     * Get replacement application if this one is deprecated
     */
    public function replacementApplication(): HasOne
    {
        return $this->hasOne(Aplikasi::class, 'id', 'replacement_application_id');
    }

    /**
     * Get applications that this one replaces
     */
    public function replacedApplications(): HasMany
    {
        return $this->hasMany(Aplikasi::class, 'replacement_application_id', 'id');
    }

    /**
     * Get notifications for this aplikasi
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'aplikasi_id');
    }

    /**
     * Get reports for this aplikasi
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'aplikasi_id');
    }

    /**
     * Get child applications (if this is a parent application)
     */
    public function childApplications(): HasMany
    {
        return $this->hasMany(Aplikasi::class, 'parent_aplikasi_id');
    }

    /**
     * Get parent application (if this is a child application)
     */
    public function parentApplication(): HasOne
    {
        return $this->hasOne(Aplikasi::class, 'id', 'parent_aplikasi_id');
    }

    // ==================== APPLICATION MANAGEMENT ====================

    /**
     * Check if application is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if application is in maintenance mode
     */
    public function isInMaintenance(): bool
    {
        return $this->is_maintenance_mode || $this->status === self::STATUS_MAINTENANCE;
    }

    /**
     * Check if application is deprecated
     */
    public function isDeprecated(): bool
    {
        return $this->status === self::STATUS_DEPRECATED;
    }

    /**
     * Check if application is in development
     */
    public function isInDevelopment(): bool
    {
        return $this->status === self::STATUS_DEVELOPMENT;
    }

    /**
     * Enable maintenance mode
     */
    public function enableMaintenance(string $reason, ?Carbon $startTime = null, ?Carbon $endTime = null): bool
    {
        $this->is_maintenance_mode = true;
        $this->maintenance_reason = $reason;
        $this->maintenance_start_time = $startTime ?? Carbon::now();
        $this->maintenance_end_time = $endTime;

        if ($this->save()) {
            // Log maintenance mode activation
            \Illuminate\Support\Facades\Log::info("Maintenance mode enabled for application {$this->name}: {$reason}");
            return true;
        }

        return false;
    }

    /**
     * Disable maintenance mode
     */
    public function disableMaintenance(): bool
    {
        $this->is_maintenance_mode = false;
        $this->maintenance_reason = null;
        $this->maintenance_start_time = null;
        $this->maintenance_end_time = null;

        if ($this->save()) {
            // Log maintenance mode deactivation
            \Illuminate\Support\Facades\Log::info("Maintenance mode disabled for application {$this->name}");
            return true;
        }

        return false;
    }

    /**
     * Update application version
     */
    public function updateVersion(string $newVersion, ?Carbon $releaseDate = null): bool
    {
        $oldVersion = $this->current_version;
        $this->current_version = $newVersion;
        $this->latest_version = $newVersion;
        $this->release_date = $releaseDate ?? Carbon::now();
        $this->last_updated = Carbon::now();

        if ($this->save()) {
            // Log version update
            \Illuminate\Support\Facades\Log::info("Application {$this->name} updated from {$oldVersion} to {$newVersion}");
            return true;
        }

        return false;
    }

    /**
     * Schedule maintenance window
     */
    public function scheduleMaintenance(Carbon $startTime, Carbon $endTime, string $reason): bool
    {
        $this->maintenance_window_start = $startTime;
        $this->maintenance_window_end = $endTime;
        $this->maintenance_reason = $reason;

        if ($this->save()) {
            // Log maintenance scheduling
            \Illuminate\Support\Facades\Log::info("Maintenance scheduled for application {$this->name} from {$startTime} to {$endTime}");
            return true;
        }

        return false;
    }

    /**
     * Check if application is within maintenance window
     */
    public function isInMaintenanceWindow(): bool
    {
        if (!$this->maintenance_window_start || !$this->maintenance_window_end) {
            return false;
        }

        $now = Carbon::now();
        return $now->between($this->maintenance_window_start, $this->maintenance_window_end);
    }

    /**
     * Get next maintenance window
     */
    public function getNextMaintenanceWindow(): ?array
    {
        if (!$this->maintenance_window_start || !$this->maintenance_window_end) {
            return null;
        }

        $now = Carbon::now();

        // If currently in maintenance window, return current
        if ($this->isInMaintenanceWindow()) {
            return [
                'start' => $this->maintenance_window_start,
                'end' => $this->maintenance_window_end,
                'reason' => $this->maintenance_reason,
                'is_current' => true,
            ];
        }

        // If maintenance window is in the future, return it
        if ($this->maintenance_window_start->isFuture()) {
            return [
                'start' => $this->maintenance_window_start,
                'end' => $this->maintenance_window_end,
                'reason' => $this->maintenance_reason,
                'is_current' => false,
            ];
        }

        return null;
    }

    /**
     * Update application health metrics
     */
    public function updateHealthMetrics(array $metrics): bool
    {
        $this->uptime_percentage = $metrics['uptime_percentage'] ?? $this->uptime_percentage;
        $this->response_time_avg = $metrics['response_time_avg'] ?? $this->response_time_avg;
        $this->error_rate = $metrics['error_rate'] ?? $this->error_rate;
        $this->last_health_check = Carbon::now();
        $this->health_status = $metrics['health_status'] ?? $this->calculateHealthStatus();

        return $this->save();
    }

    /**
     * Calculate health status based on metrics
     */
    private function calculateHealthStatus(): string
    {
        if ($this->uptime_percentage >= 99.5 && $this->error_rate <= 0.01) {
            return 'excellent';
        } elseif ($this->uptime_percentage >= 99.0 && $this->error_rate <= 0.05) {
            return 'good';
        } elseif ($this->uptime_percentage >= 95.0 && $this->error_rate <= 0.1) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Update user count
     */
    public function updateUserCount(int $currentUsers): bool
    {
        $this->current_users = $currentUsers;
        return $this->save();
    }

    /**
     * Check if application is at capacity
     */
    public function isAtCapacity(): bool
    {
        if (!$this->max_users) {
            return false;
        }

        return $this->current_users >= $this->max_users;
    }

    /**
     * Get capacity utilization percentage
     */
    public function getCapacityUtilization(): float
    {
        if (!$this->max_users || $this->max_users === 0) {
            return 0.0;
        }

        return min(100.0, ($this->current_users / $this->max_users) * 100);
    }

    /**
     * Deprecate application
     */
    public function deprecate(?Carbon $deprecationDate = null, ?int $replacementApplicationId = null): bool
    {
        $this->status = self::STATUS_DEPRECATED;
        $this->deprecation_date = $deprecationDate ?? Carbon::now();
        $this->replacement_application_id = $replacementApplicationId;

        if ($this->save()) {
            // Log deprecation
            \Illuminate\Support\Facades\Log::info("Application {$this->name} has been deprecated");
            return true;
        }

        return false;
    }

    /**
     * Reactivate application
     */
    public function reactivate(): bool
    {
        $this->status = self::STATUS_ACTIVE;
        $this->deprecation_date = null;
        $this->replacement_application_id = null;

        if ($this->save()) {
            // Log reactivation
            \Illuminate\Support\Facades\Log::info("Application {$this->name} has been reactivated");
            return true;
        }

        return false;
    }

    /**
     * Get application age in days
     */
    public function getAgeInDays(): ?int
    {
        if (!$this->created_at) {
            return null;
        }

        return $this->created_at->diffInDays(Carbon::now());
    }

    /**
     * Check if application needs update (based on version age)
     */
    public function needsUpdate(int $maxDaysWithoutUpdate = 90): bool
    {
        if (!$this->last_updated) {
            return true;
        }

        return $this->last_updated->diffInDays(Carbon::now()) > $maxDaysWithoutUpdate;
    }

    /**
     * Get version information
     */
    public function getVersionInfo(): array
    {
        return [
            'current' => $this->current_version ?? $this->version,
            'latest' => $this->latest_version,
            'release_date' => $this->release_date,
            'last_updated' => $this->last_updated,
            'is_outdated' => $this->needsUpdate(),
        ];
    }

    // ==================== USAGE STATISTICS ====================

    /**
     * Get total ticket count for this application
     */
    public function getTotalTicketCount(): int
    {
        return $this->tickets()->count();
    }

    /**
     * Get ticket count by status
     */
    public function getTicketCountByStatus(string $status = null): int|array
    {
        if ($status) {
            return $this->tickets()->where('status', $status)->count();
        }

        return $this->tickets()
                   ->selectRaw('status, COUNT(*) as count')
                   ->groupBy('status')
                   ->pluck('count', 'status')
                   ->toArray();
    }

    /**
     * Get ticket count by priority
     */
    public function getTicketCountByPriority(): array
    {
        return $this->tickets()
                   ->selectRaw('priority, COUNT(*) as count')
                   ->groupBy('priority')
                   ->pluck('count', 'priority')
                   ->toArray();
    }

    /**
     * Get tickets created in date range
     */
    public function getTicketsInDateRange(Carbon $startDate, Carbon $endDate): int
    {
        return $this->tickets()
                   ->whereBetween('created_at', [$startDate, $endDate])
                   ->count();
    }

    /**
     * Get daily ticket volume for the last N days
     */
    public function getDailyTicketVolume(int $days = 30): array
    {
        $data = [];
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays($days);

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $count = $this->tickets()
                         ->whereDate('created_at', $date->toDateString())
                         ->count();

            $data[] = [
                'date' => $date->toDateString(),
                'count' => $count,
            ];
        }

        return $data;
    }

    /**
     * Get weekly ticket volume for the last N weeks
     */
    public function getWeeklyTicketVolume(int $weeks = 12): array
    {
        $data = [];
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subWeeks($weeks);

        for ($date = $startDate->copy(); $date <= $endDate; $date->addWeek()) {
            $weekStart = $date->copy()->startOfWeek();
            $weekEnd = $date->copy()->endOfWeek();

            $count = $this->tickets()
                         ->whereBetween('created_at', [$weekStart, $weekEnd])
                         ->count();

            $data[] = [
                'week' => $date->format('Y-W'),
                'start_date' => $weekStart->toDateString(),
                'end_date' => $weekEnd->toDateString(),
                'count' => $count,
            ];
        }

        return $data;
    }

    /**
     * Get monthly ticket volume for the last N months
     */
    public function getMonthlyTicketVolume(int $months = 12): array
    {
        $data = [];
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subMonths($months);

        for ($date = $startDate->copy(); $date <= $endDate; $date->addMonth()) {
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $count = $this->tickets()
                         ->whereBetween('created_at', [$monthStart, $monthEnd])
                         ->count();

            $data[] = [
                'month' => $date->format('Y-m'),
                'count' => $count,
            ];
        }

        return $data;
    }

    /**
     * Get average tickets per day
     */
    public function getAverageTicketsPerDay(int $days = 30): float
    {
        $totalTickets = $this->getTicketsInDateRange(
            Carbon::now()->subDays($days),
            Carbon::now()
        );

        return round($totalTickets / $days, 2);
    }

    /**
     * Get average resolution time for tickets
     */
    public function getAverageResolutionTime(): ?float
    {
        $resolvedTickets = $this->tickets()
                               ->where('status', Ticket::STATUS_RESOLVED)
                               ->whereNotNull('resolution_time_minutes')
                               ->get();

        if ($resolvedTickets->isEmpty()) {
            return null;
        }

        return round($resolvedTickets->avg('resolution_time_minutes'), 2);
    }

    /**
     * Get resolution rate (percentage)
     */
    public function getResolutionRate(): float
    {
        $totalTickets = $this->getTotalTicketCount();
        if ($totalTickets === 0) {
            return 0.0;
        }

        $resolvedTickets = $this->getTicketCountByStatus(Ticket::STATUS_RESOLVED);
        return round(($resolvedTickets / $totalTickets) * 100, 2);
    }

    /**
     * Get first response time average
     */
    public function getAverageFirstResponseTime(): ?float
    {
        $ticketsWithResponse = $this->tickets()
                                   ->whereNotNull('first_response_at')
                                   ->get();

        if ($ticketsWithResponse->isEmpty()) {
            return null;
        }

        $totalMinutes = $ticketsWithResponse->sum(function ($ticket) {
            return Carbon::parse($ticket->created_at)->diffInMinutes($ticket->first_response_at);
        });

        return round($totalMinutes / $ticketsWithResponse->count(), 2);
    }

    /**
     * Get popular categories for this application
     */
    public function getPopularCategories(int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->kategoriMasalahs()
                   ->withCount('tickets')
                   ->orderBy('tickets_count', 'desc')
                   ->limit($limit)
                   ->get();
    }

    /**
     * Get most active users for this application
     */
    public function getMostActiveUsers(int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->tickets()
                   ->selectRaw('user_nip, COUNT(*) as ticket_count')
                   ->with('user')
                   ->groupBy('user_nip')
                   ->orderBy('ticket_count', 'desc')
                   ->limit($limit)
                   ->get();
    }

    /**
     * Get most active teknisi for this application
     */
    public function getMostActiveTeknisi(int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->tickets()
                   ->selectRaw('assigned_teknisi_nip, COUNT(*) as ticket_count')
                   ->with('assignedTeknisi')
                   ->groupBy('assigned_teknisi_nip')
                   ->orderBy('ticket_count', 'desc')
                   ->limit($limit)
                   ->get();
    }

    /**
     * Get usage trends over time
     */
    public function getUsageTrends(int $days = 30): array
    {
        $dailyVolume = $this->getDailyTicketVolume($days);

        // Calculate trend (comparing first half with second half)
        $midPoint = intval(count($dailyVolume) / 2);
        $firstHalf = array_slice($dailyVolume, 0, $midPoint);
        $secondHalf = array_slice($dailyVolume, $midPoint);

        $firstHalfAvg = array_sum(array_column($firstHalf, 'count')) / count($firstHalf);
        $secondHalfAvg = array_sum(array_column($secondHalf, 'count')) / count($secondHalf);

        $trend = 'stable';
        if ($secondHalfAvg > $firstHalfAvg * 1.1) {
            $trend = 'increasing';
        } elseif ($secondHalfAvg < $firstHalfAvg * 0.9) {
            $trend = 'decreasing';
        }

        return [
            'trend' => $trend,
            'daily_volume' => $dailyVolume,
            'average_daily' => $this->getAverageTicketsPerDay($days),
            'total_tickets' => array_sum(array_column($dailyVolume, 'count')),
        ];
    }

    /**
     * Get application performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        return [
            'total_tickets' => $this->getTotalTicketCount(),
            'resolution_rate' => $this->getResolutionRate(),
            'avg_resolution_time' => $this->getAverageResolutionTime(),
            'avg_first_response_time' => $this->getAverageFirstResponseTime(),
            'ticket_distribution' => $this->getTicketCountByStatus(),
            'priority_distribution' => $this->getTicketCountByPriority(),
            'average_daily_volume' => $this->getAverageTicketsPerDay(),
            'health_status' => $this->health_status,
            'uptime_percentage' => $this->uptime_percentage,
            'current_users' => $this->current_users,
            'capacity_utilization' => $this->getCapacityUtilization(),
        ];
    }

    /**
     * Get comprehensive usage statistics
     */
    public function getUsageStatistics(int $days = 30): array
    {
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays($days);

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'days' => $days,
            ],
            'ticket_volume' => [
                'total' => $this->getTicketsInDateRange($startDate, $endDate),
                'daily_average' => $this->getAverageTicketsPerDay($days),
                'daily_breakdown' => $this->getDailyTicketVolume($days),
            ],
            'resolution_metrics' => [
                'resolution_rate' => $this->getResolutionRate(),
                'avg_resolution_time' => $this->getAverageResolutionTime(),
                'avg_first_response_time' => $this->getAverageFirstResponseTime(),
            ],
            'category_popularity' => $this->getPopularCategories(5)->map(function ($category) {
                return [
                    'name' => $category->name,
                    'ticket_count' => $category->tickets_count,
                ];
            }),
            'trends' => $this->getUsageTrends($days),
        ];
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
            self::STATUS_MAINTENANCE => 'warning',
            self::STATUS_DEPRECATED => 'danger',
            self::STATUS_DEVELOPMENT => 'info',
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
            self::STATUS_MAINTENANCE => 'Maintenance',
            self::STATUS_DEPRECATED => 'Deprecated',
            self::STATUS_DEVELOPMENT => 'Development',
            default => 'Unknown',
        };
    }

    /**
     * Get criticality badge color for UI
     */
    public function getCriticalityBadgeColorAttribute(): string
    {
        return match($this->criticality) {
            self::CRITICALITY_LOW => 'success',
            self::CRITICALITY_MEDIUM => 'info',
            self::CRITICALITY_HIGH => 'warning',
            self::CRITICALITY_CRITICAL => 'danger',
            default => 'light',
        };
    }

    /**
     * Get criticality label for UI
     */
    public function getCriticalityLabelAttribute(): string
    {
        return match($this->criticality) {
            self::CRITICALITY_LOW => 'Low',
            self::CRITICALITY_MEDIUM => 'Medium',
            self::CRITICALITY_HIGH => 'High',
            self::CRITICALITY_CRITICAL => 'Critical',
            default => 'Not Set',
        };
    }

    /**
     * Get category label for UI
     */
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            self::CATEGORY_WEB => 'Web Application',
            self::CATEGORY_DESKTOP => 'Desktop Application',
            self::CATEGORY_MOBILE => 'Mobile Application',
            self::CATEGORY_SERVICE => 'Service',
            self::CATEGORY_DATABASE => 'Database',
            self::CATEGORY_NETWORK => 'Network',
            self::CATEGORY_SECURITY => 'Security',
            self::CATEGORY_BUSINESS => 'Business Application',
            default => 'Not Categorized',
        };
    }

    /**
     * Get formatted version string
     */
    public function getFormattedVersionAttribute(): string
    {
        $version = $this->current_version ?? $this->version;
        if (!$version) {
            return 'No Version';
        }

        return 'v' . $version;
    }

    /**
     * Get formatted last updated date
     */
    public function getFormattedLastUpdatedAttribute(): string
    {
        if (!$this->last_updated) {
            return 'Never Updated';
        }

        return $this->last_updated->format('d M Y, H:i');
    }

    /**
     * Get formatted release date
     */
    public function getFormattedReleaseDateAttribute(): string
    {
        if (!$this->release_date) {
            return 'No Release Date';
        }

        return $this->release_date->format('d M Y');
    }

    /**
     * Get formatted maintenance window
     */
    public function getFormattedMaintenanceWindowAttribute(): string
    {
        if (!$this->maintenance_window_start || !$this->maintenance_window_end) {
            return 'No Scheduled Maintenance';
        }

        return $this->maintenance_window_start->format('d M Y H:i') . ' - ' .
               $this->maintenance_window_end->format('d M Y H:i');
    }

    /**
     * Get health status badge color
     */
    public function getHealthStatusBadgeColorAttribute(): string
    {
        return match($this->health_status) {
            'excellent' => 'success',
            'good' => 'info',
            'fair' => 'warning',
            'poor' => 'danger',
            default => 'light',
        };
    }

    /**
     * Get health status label
     */
    public function getHealthStatusLabelAttribute(): string
    {
        return match($this->health_status) {
            'excellent' => 'Excellent',
            'good' => 'Good',
            'fair' => 'Fair',
            'poor' => 'Poor',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted uptime percentage
     */
    public function getFormattedUptimeAttribute(): string
    {
        if ($this->uptime_percentage === null) {
            return 'Not Monitored';
        }

        return number_format((float) $this->uptime_percentage, 2) . '%';
    }

    /**
     * Get formatted response time
     */
    public function getFormattedResponseTimeAttribute(): string
    {
        if ($this->response_time_avg === null) {
            return 'Not Measured';
        }

        return number_format((float) $this->response_time_avg, 2) . 'ms';
    }

    /**
     * Get formatted error rate
     */
    public function getFormattedErrorRateAttribute(): string
    {
        if ($this->error_rate === null) {
            return 'Not Measured';
        }

        return number_format($this->error_rate * 100, 4) . '%';
    }

    /**
     * Get formatted capacity utilization
     */
    public function getFormattedCapacityUtilizationAttribute(): string
    {
        $utilization = $this->getCapacityUtilization();

        if ($this->max_users === null) {
            return 'No Limit Set';
        }

        return number_format($utilization, 1) . '% (' . $this->current_users . '/' . $this->max_users . ')';
    }

    /**
     * Get formatted monthly cost
     */
    public function getFormattedMonthlyCostAttribute(): string
    {
        if ($this->monthly_cost === null) {
            return 'Not Specified';
        }

        return 'Rp ' . number_format((float) $this->monthly_cost, 0, ',', '.');
    }

    /**
     * Get formatted SLA hours
     */
    public function getFormattedSlaHoursAttribute(): string
    {
        if (!$this->sla_hours) {
            return 'No SLA';
        }

        return $this->sla_hours . ' hours';
    }

    /**
     * Get application age formatted
     */
    public function getFormattedAgeAttribute(): string
    {
        $ageInDays = $this->getAgeInDays();

        if (!$ageInDays) {
            return 'Unknown';
        }

        if ($ageInDays < 30) {
            return $ageInDays . ' days';
        } elseif ($ageInDays < 365) {
            $months = intval($ageInDays / 30);
            return $months . ' month' . ($months > 1 ? 's' : '');
        } else {
            $years = intval($ageInDays / 365);
            return $years . ' year' . ($years > 1 ? 's' : '');
        }
    }

    /**
     * Get formatted vendor contract expiry
     */
    public function getFormattedVendorContractExpiryAttribute(): string
    {
        if (!$this->vendor_contract_expiry) {
            return 'No Contract';
        }

        if ($this->vendor_contract_expiry->isPast()) {
            return 'Expired ' . $this->vendor_contract_expiry->diffForHumans();
        }

        return 'Expires ' . $this->vendor_contract_expiry->format('d M Y');
    }

    /**
     * Get formatted license expiry
     */
    public function getFormattedLicenseExpiryAttribute(): string
    {
        if (!$this->license_expiry) {
            return 'No License';
        }

        if ($this->license_expiry->isPast()) {
            return 'Expired ' . $this->license_expiry->diffForHumans();
        }

        return 'Expires ' . $this->license_expiry->format('d M Y');
    }

    /**
     * Get maintenance mode badge
     */
    public function getMaintenanceModeBadgeAttribute(): string
    {
        if ($this->is_maintenance_mode) {
            return '<span class="badge badge-warning">Maintenance Mode</span>';
        }

        return '<span class="badge badge-success">Operational</span>';
    }

    /**
     * Get status indicator for dashboard
     */
    public function getStatusIndicatorAttribute(): array
    {
        return [
            'status' => $this->status,
            'label' => $this->status_label,
            'badge_color' => $this->status_badge_color,
            'is_operational' => $this->isActive() && !$this->isInMaintenance(),
            'health_status' => $this->health_status,
            'health_label' => $this->health_status_label,
            'health_badge_color' => $this->health_status_badge_color,
        ];
    }

    /**
     * Get formatted supported OS
     */
    public function getFormattedSupportedOsAttribute(): string
    {
        if (!$this->supported_os || empty($this->supported_os)) {
            return 'Not Specified';
        }

        return implode(', ', $this->supported_os);
    }

    /**
     * Get formatted supported browsers
     */
    public function getFormattedSupportedBrowsersAttribute(): string
    {
        if (!$this->supported_browsers || empty($this->supported_browsers)) {
            return 'Not Specified';
        }

        return implode(', ', $this->supported_browsers);
    }

    /**
     * Get application summary for dashboard
     */
    public function getDashboardSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'version' => $this->formatted_version,
            'status' => $this->status_indicator,
            'criticality' => [
                'level' => $this->criticality,
                'label' => $this->criticality_label,
                'badge_color' => $this->criticality_badge_color,
            ],
            'category' => $this->category_label,
            'ticket_count' => $this->getTotalTicketCount(),
            'health_metrics' => [
                'uptime' => $this->formatted_uptime,
                'response_time' => $this->formatted_response_time,
                'error_rate' => $this->formatted_error_rate,
            ],
            'capacity' => [
                'utilization' => $this->formatted_capacity_utilization,
                'current_users' => $this->current_users,
                'max_users' => $this->max_users,
            ],
            'last_updated' => $this->formatted_last_updated,
            'is_maintenance_mode' => $this->is_maintenance_mode,
        ];
    }

    /**
     * Get application icon with fallback
     */
    public function getApplicationIconAttribute(): string
    {
        if ($this->icon) {
            return asset('storage/' . $this->icon);
        }

        // Default icon based on category
        return match($this->category) {
            self::CATEGORY_WEB => 'fas fa-globe',
            self::CATEGORY_DESKTOP => 'fas fa-desktop',
            self::CATEGORY_MOBILE => 'fas fa-mobile-alt',
            self::CATEGORY_SERVICE => 'fas fa-cog',
            self::CATEGORY_DATABASE => 'fas fa-database',
            self::CATEGORY_NETWORK => 'fas fa-network-wired',
            self::CATEGORY_SECURITY => 'fas fa-shield-alt',
            self::CATEGORY_BUSINESS => 'fas fa-building',
            default => 'fas fa-cube',
        };
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Perform comprehensive health check
     */
    public function performHealthCheck(): array
    {
        $issues = [];
        $warnings = [];
        $status = 'healthy';

        // Check if application is active
        if (!$this->isActive()) {
            $issues[] = 'Application is not active';
            $status = 'unhealthy';
        }

        // Check maintenance mode
        if ($this->isInMaintenance()) {
            $warnings[] = 'Application is in maintenance mode';
            $status = 'maintenance';
        }

        // Check uptime
        if ($this->uptime_percentage !== null && $this->uptime_percentage < 99.0) {
            $issues[] = 'Uptime below 99%: ' . $this->formatted_uptime;
            $status = 'degraded';
        }

        // Check response time
        if ($this->response_time_avg !== null && $this->response_time_avg > 1000) {
            $warnings[] = 'Response time above 1000ms: ' . $this->formatted_response_time;
            if ($status === 'healthy') {
                $status = 'degraded';
            }
        }

        // Check error rate
        if ($this->error_rate !== null && $this->error_rate > 0.05) {
            $issues[] = 'Error rate above 5%: ' . $this->formatted_error_rate;
            $status = 'unhealthy';
        }

        // Check capacity
        if ($this->isAtCapacity()) {
            $warnings[] = 'Application is at capacity';
            if ($status === 'healthy') {
                $status = 'degraded';
            }
        }

        // Check version age
        if ($this->needsUpdate(90)) {
            $warnings[] = 'Application version may need updating';
        }

        // Check contract/license expiry
        if ($this->vendor_contract_expiry && $this->vendor_contract_expiry->isPast()) {
            $issues[] = 'Vendor contract has expired';
            $status = 'unhealthy';
        }

        if ($this->license_expiry && $this->license_expiry->isPast()) {
            $issues[] = 'License has expired';
            $status = 'unhealthy';
        }

        // Check upcoming contract/license expiry (30 days)
        if ($this->vendor_contract_expiry && $this->vendor_contract_expiry->isBetween(Carbon::now(), Carbon::now()->addDays(30))) {
            $warnings[] = 'Vendor contract expires soon: ' . $this->formatted_vendor_contract_expiry;
        }

        if ($this->license_expiry && $this->license_expiry->isBetween(Carbon::now(), Carbon::now()->addDays(30))) {
            $warnings[] = 'License expires soon: ' . $this->formatted_license_expiry;
        }

        // Check SLA compliance
        if (!$this->isSlaCompliant()) {
            $issues[] = 'SLA compliance issues detected';
            $status = 'unhealthy';
        }

        // Update health status
        $this->health_status = $status;
        $this->last_health_check = Carbon::now();
        $this->save();

        return [
            'status' => $status,
            'issues' => $issues,
            'warnings' => $warnings,
            'checked_at' => Carbon::now(),
        ];
    }

    /**
     * Check SLA compliance for recent tickets
     */
    public function isSlaCompliant(int $days = 30): bool
    {
        if (!$this->sla_hours) {
            return true; // No SLA defined
        }

        $recentTickets = $this->tickets()
                             ->where('created_at', '>=', Carbon::now()->subDays($days))
                             ->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
                             ->get();

        if ($recentTickets->isEmpty()) {
            return true; // No tickets to measure
        }

        $slaBreachCount = 0;
        foreach ($recentTickets as $ticket) {
            $slaDeadline = $ticket->created_at->copy()->addHours($this->sla_hours);
            if ($ticket->resolved_at && $ticket->resolved_at->isAfter($slaDeadline)) {
                $slaBreachCount++;
            }
        }

        $slaComplianceRate = (($recentTickets->count() - $slaBreachCount) / $recentTickets->count()) * 100;
        return $slaComplianceRate >= 95; // 95% compliance threshold
    }

    /**
     * Get SLA compliance rate
     */
    public function getSlaComplianceRate(int $days = 30): float
    {
        if (!$this->sla_hours) {
            return 100.0; // No SLA defined, consider 100% compliant
        }

        $recentTickets = $this->tickets()
                             ->where('created_at', '>=', Carbon::now()->subDays($days))
                             ->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
                             ->get();

        if ($recentTickets->isEmpty()) {
            return 100.0; // No tickets to measure
        }

        $slaBreachCount = 0;
        foreach ($recentTickets as $ticket) {
            $slaDeadline = $ticket->created_at->copy()->addHours($this->sla_hours);
            if ($ticket->resolved_at && $ticket->resolved_at->isAfter($slaDeadline)) {
                $slaBreachCount++;
            }
        }

        return round((($recentTickets->count() - $slaBreachCount) / $recentTickets->count()) * 100, 2);
    }

    /**
     * Calculate application priority score based on various factors
     */
    public function calculatePriorityScore(): float
    {
        $score = 0;

        // Criticality weight (40%)
        $criticalityScore = match($this->criticality) {
            self::CRITICALITY_CRITICAL => 100,
            self::CRITICALITY_HIGH => 75,
            self::CRITICALITY_MEDIUM => 50,
            self::CRITICALITY_LOW => 25,
            default => 0,
        };
        $score += ($criticalityScore / 100) * 40;

        // Usage weight (30%)
        $ticketVolume = $this->getTotalTicketCount();
        $usageScore = min(100, ($ticketVolume / 100) * 10); // Scale based on ticket volume
        $score += ($usageScore / 100) * 30;

        // Health weight (20%)
        $healthScore = match($this->health_status) {
            'excellent' => 100,
            'good' => 80,
            'fair' => 60,
            'poor' => 40,
            default => 50,
        };
        $score += ($healthScore / 100) * 20;

        // Age weight (10%) - newer applications get slightly higher priority
        if ($this->created_at) {
            $ageInDays = $this->getAgeInDays();
            $ageScore = max(0, 100 - (($ageInDays / 365) * 20)); // Slight penalty for very old apps
            $score += ($ageScore / 100) * 10;
        }

        return round($score, 2);
    }

    /**
     * Get business impact assessment
     */
    public function getBusinessImpactAssessment(): array
    {
        $userImpact = $this->current_users ?? 0;
        $ticketVolume = $this->getTotalTicketCount();
        $criticality = $this->criticality;

        // Calculate impact level
        $impactLevel = 'low';
        if ($criticality === self::CRITICALITY_CRITICAL || $userImpact > 1000) {
            $impactLevel = 'critical';
        } elseif ($criticality === self::CRITICALITY_HIGH || $userImpact > 500 || $ticketVolume > 100) {
            $impactLevel = 'high';
        } elseif ($criticality === self::CRITICALITY_MEDIUM || $userImpact > 100 || $ticketVolume > 50) {
            $impactLevel = 'medium';
        }

        return [
            'impact_level' => $impactLevel,
            'user_count' => $userImpact,
            'ticket_volume' => $ticketVolume,
            'criticality' => $criticality,
            'priority_score' => $this->calculatePriorityScore(),
            'monthly_cost' => $this->monthly_cost,
            'cost_effectiveness' => $this->monthly_cost ? round($userImpact / $this->monthly_cost, 2) : 0,
        ];
    }

    /**
     * Check if application needs attention
     */
    public function needsAttention(): bool
    {
        $reasons = [];

        if (!$this->isActive()) {
            $reasons[] = 'inactive';
        }

        if ($this->isInMaintenance()) {
            $reasons[] = 'maintenance';
        }

        if ($this->health_status === 'poor') {
            $reasons[] = 'poor_health';
        }

        if ($this->uptime_percentage !== null && $this->uptime_percentage < 99.0) {
            $reasons[] = 'low_uptime';
        }

        if ($this->error_rate !== null && $this->error_rate > 0.05) {
            $reasons[] = 'high_error_rate';
        }

        if ($this->isAtCapacity()) {
            $reasons[] = 'at_capacity';
        }

        if ($this->needsUpdate(60)) {
            $reasons[] = 'needs_update';
        }

        if ($this->vendor_contract_expiry && $this->vendor_contract_expiry->isPast()) {
            $reasons[] = 'expired_contract';
        }

        if ($this->license_expiry && $this->license_expiry->isPast()) {
            $reasons[] = 'expired_license';
        }

        if (!$this->isSlaCompliant()) {
            $reasons[] = 'sla_non_compliant';
        }

        return !empty($reasons);
    }

    /**
     * Get attention reasons
     */
    public function getAttentionReasons(): array
    {
        $reasons = [];

        if (!$this->isActive()) {
            $reasons[] = [
                'type' => 'status',
                'message' => 'Application is not active',
                'severity' => 'warning',
            ];
        }

        if ($this->isInMaintenance()) {
            $reasons[] = [
                'type' => 'maintenance',
                'message' => 'Application is in maintenance mode',
                'severity' => 'info',
            ];
        }

        if ($this->health_status === 'poor') {
            $reasons[] = [
                'type' => 'health',
                'message' => 'Application health is poor',
                'severity' => 'danger',
            ];
        }

        if ($this->uptime_percentage !== null && $this->uptime_percentage < 99.0) {
            $reasons[] = [
                'type' => 'uptime',
                'message' => 'Uptime below 99%: ' . $this->formatted_uptime,
                'severity' => 'warning',
            ];
        }

        if ($this->error_rate !== null && $this->error_rate > 0.05) {
            $reasons[] = [
                'type' => 'error_rate',
                'message' => 'Error rate above 5%: ' . $this->formatted_error_rate,
                'severity' => 'danger',
            ];
        }

        if ($this->isAtCapacity()) {
            $reasons[] = [
                'type' => 'capacity',
                'message' => 'Application is at capacity',
                'severity' => 'warning',
            ];
        }

        if ($this->needsUpdate(60)) {
            $reasons[] = [
                'type' => 'update',
                'message' => 'Application version may need updating',
                'severity' => 'info',
            ];
        }

        if ($this->vendor_contract_expiry && $this->vendor_contract_expiry->isPast()) {
            $reasons[] = [
                'type' => 'contract',
                'message' => 'Vendor contract has expired',
                'severity' => 'danger',
            ];
        }

        if ($this->license_expiry && $this->license_expiry->isPast()) {
            $reasons[] = [
                'type' => 'license',
                'message' => 'License has expired',
                'severity' => 'danger',
            ];
        }

        if (!$this->isSlaCompliant()) {
            $reasons[] = [
                'type' => 'sla',
                'message' => 'SLA compliance issues detected',
                'severity' => 'warning',
            ];
        }

        return $reasons;
    }

    /**
     * Get maintenance recommendations
     */
    public function getMaintenanceRecommendations(): array
    {
        $recommendations = [];

        // Version update recommendations
        if ($this->needsUpdate(90)) {
            $recommendations[] = [
                'type' => 'version_update',
                'priority' => 'medium',
                'message' => 'Consider updating application version',
                'action' => 'Schedule version update',
            ];
        }

        // Health monitoring recommendations
        if (!$this->monitoring_enabled) {
            $recommendations[] = [
                'type' => 'monitoring',
                'priority' => 'high',
                'message' => 'Enable application monitoring',
                'action' => 'Configure health monitoring',
            ];
        }

        // Backup recommendations
        if (!$this->backup_schedule) {
            $recommendations[] = [
                'type' => 'backup',
                'priority' => 'high',
                'message' => 'Configure backup schedule',
                'action' => 'Set up automated backups',
            ];
        }

        // Documentation recommendations
        if (!$this->technical_documentation) {
            $recommendations[] = [
                'type' => 'documentation',
                'priority' => 'medium',
                'message' => 'Add technical documentation',
                'action' => 'Create/update technical documentation',
            ];
        }

        // Contract/license expiry recommendations
        if ($this->vendor_contract_expiry && $this->vendor_contract_expiry->isBetween(Carbon::now(), Carbon::now()->addDays(60))) {
            $recommendations[] = [
                'type' => 'contract_renewal',
                'priority' => 'high',
                'message' => 'Vendor contract expires soon',
                'action' => 'Initiate contract renewal process',
            ];
        }

        if ($this->license_expiry && $this->license_expiry->isBetween(Carbon::now(), Carbon::now()->addDays(60))) {
            $recommendations[] = [
                'type' => 'license_renewal',
                'priority' => 'high',
                'message' => 'License expires soon',
                'action' => 'Initiate license renewal process',
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate operational cost efficiency
     */
    public function getOperationalEfficiency(): array
    {
        $totalTickets = $this->getTotalTicketCount();
        $monthlyCost = $this->monthly_cost ?? 0;
        $userCount = $this->current_users ?? 0;

        return [
            'cost_per_ticket' => $totalTickets > 0 ? round($monthlyCost / $totalTickets, 2) : 0,
            'cost_per_user' => $userCount > 0 ? round($monthlyCost / $userCount, 2) : 0,
            'ticket_resolution_rate' => $this->getResolutionRate(),
            'average_resolution_time' => $this->getAverageResolutionTime(),
            'roi_score' => $this->calculateROI(),
        ];
    }

    /**
     * Calculate ROI based on cost vs performance
     */
    private function calculateROI(): float
    {
        if (!$this->monthly_cost || $this->monthly_cost === 0) {
            return 0.0;
        }

        $performanceScore = $this->calculatePriorityScore();
        $costEfficiency = 100 - (($this->monthly_cost / 10000) * 10); // Scale based on cost

        return round(($performanceScore + $costEfficiency) / 2, 2);
    }

    /**
     * Get application risk assessment
     */
    public function getRiskAssessment(): array
    {
        $risks = [];

        // High risk factors
        if ($this->criticality === self::CRITICALITY_CRITICAL) {
            $risks[] = [
                'factor' => 'criticality',
                'level' => 'high',
                'description' => 'Application has critical business impact',
            ];
        }

        if ($this->vendor_contract_expiry && $this->vendor_contract_expiry->isPast()) {
            $risks[] = [
                'factor' => 'contract',
                'level' => 'high',
                'description' => 'Vendor contract has expired',
            ];
        }

        if ($this->license_expiry && $this->license_expiry->isPast()) {
            $risks[] = [
                'factor' => 'license',
                'level' => 'high',
                'description' => 'License has expired',
            ];
        }

        if ($this->health_status === 'poor') {
            $risks[] = [
                'factor' => 'health',
                'level' => 'high',
                'description' => 'Application health is poor',
            ];
        }

        // Medium risk factors
        if ($this->uptime_percentage !== null && $this->uptime_percentage < 99.5) {
            $risks[] = [
                'factor' => 'uptime',
                'level' => 'medium',
                'description' => 'Uptime below 99.5%',
            ];
        }

        if ($this->error_rate !== null && $this->error_rate > 0.01) {
            $risks[] = [
                'factor' => 'error_rate',
                'level' => 'medium',
                'description' => 'Error rate above 1%',
            ];
        }

        if ($this->isAtCapacity()) {
            $risks[] = [
                'factor' => 'capacity',
                'level' => 'medium',
                'description' => 'Application is at capacity',
            ];
        }

        $overallRisk = 'low';
        if (count(array_filter($risks, fn($r) => $r['level'] === 'high')) > 0) {
            $overallRisk = 'high';
        } elseif (count(array_filter($risks, fn($r) => $r['level'] === 'medium')) > 1) {
            $overallRisk = 'medium';
        }

        return [
            'overall_risk' => $overallRisk,
            'risk_factors' => $risks,
            'risk_score' => $this->calculateRiskScore($risks),
        ];
    }

    /**
     * Calculate risk score
     */
    private function calculateRiskScore(array $risks): float
    {
        $score = 0;
        foreach ($risks as $risk) {
            $score += match($risk['level']) {
                'high' => 30,
                'medium' => 15,
                'low' => 5,
                default => 0,
            };
        }

        return min(100, $score);
    }

    // ==================== QUERY SCOPES ====================

    /**
     * Scope for active applications
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for inactive applications
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Scope for applications in maintenance
     */
    public function scopeInMaintenance(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_MAINTENANCE)
              ->orWhere('is_maintenance_mode', true);
        });
    }

    /**
     * Scope for operational applications (active and not in maintenance)
     */
    public function scopeOperational(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('is_maintenance_mode', false);
    }

    /**
     * Scope for deprecated applications
     */
    public function scopeDeprecated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DEPRECATED);
    }

    /**
     * Scope for applications in development
     */
    public function scopeInDevelopment(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DEVELOPMENT);
    }

    /**
     * Scope for applications by criticality
     */
    public function scopeByCriticality(Builder $query, string $criticality): Builder
    {
        return $query->where('criticality', $criticality);
    }

    /**
     * Scope for critical applications
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->whereIn('criticality', [self::CRITICALITY_CRITICAL, self::CRITICALITY_HIGH]);
    }

    /**
     * Scope for applications by category
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for web applications
     */
    public function scopeWebApplications(Builder $query): Builder
    {
        return $query->where('category', self::CATEGORY_WEB);
    }

    /**
     * Scope for desktop applications
     */
    public function scopeDesktopApplications(Builder $query): Builder
    {
        return $query->where('category', self::CATEGORY_DESKTOP);
    }

    /**
     * Scope for mobile applications
     */
    public function scopeMobileApplications(Builder $query): Builder
    {
        return $query->where('category', self::CATEGORY_MOBILE);
    }

    /**
     * Scope for business applications
     */
    public function scopeBusinessApplications(Builder $query): Builder
    {
        return $query->where('category', self::CATEGORY_BUSINESS);
    }

    /**
     * Scope for applications needing attention
     */
    public function scopeNeedingAttention(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', '!=', self::STATUS_ACTIVE)
              ->orWhere('is_maintenance_mode', true)
              ->orWhere('health_status', 'poor')
              ->orWhere(function ($subQ) {
                  $subQ->whereNotNull('uptime_percentage')
                       ->where('uptime_percentage', '<', 99.0);
              })
              ->orWhere(function ($subQ) {
                  $subQ->whereNotNull('error_rate')
                       ->where('error_rate', '>', 0.05);
              })
              ->orWhere(function ($subQ) {
                  $subQ->whereNotNull('vendor_contract_expiry')
                       ->where('vendor_contract_expiry', '<', Carbon::now());
              })
              ->orWhere(function ($subQ) {
                  $subQ->whereNotNull('license_expiry')
                       ->where('license_expiry', '<', Carbon::now());
              });
        });
    }

    /**
     * Scope for healthy applications
     */
    public function scopeHealthy(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('is_maintenance_mode', false)
                    ->where(function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->whereNotNull('uptime_percentage')
                                 ->where('uptime_percentage', '>=', 99.5);
                        })->orWhereNull('uptime_percentage');
                    })
                    ->where(function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->whereNotNull('error_rate')
                                 ->where('error_rate', '<=', 0.01);
                        })->orWhereNull('error_rate');
                    })
                    ->where('health_status', '!=', 'poor');
    }

    /**
     * Scope for applications with high ticket volume
     */
    public function scopeHighTicketVolume(Builder $query, int $minTickets = 100): Builder
    {
        return $query->whereHas('tickets', function ($q) use ($minTickets) {
            $q->selectRaw('count(*) as ticket_count')
              ->groupBy('aplikasi_id')
              ->having('ticket_count', '>=', $minTickets);
        });
    }

    /**
     * Scope for popular applications (by user count)
     */
    public function scopePopular(Builder $query, int $minUsers = 100): Builder
    {
        return $query->where('current_users', '>=', $minUsers);
    }

    /**
     * Scope for applications by vendor
     */
    public function scopeByVendor(Builder $query, string $vendor): Builder
    {
        return $query->where('vendor', $vendor);
    }

    /**
     * Scope for applications needing updates
     */
    public function scopeNeedingUpdates(Builder $query, int $maxDaysWithoutUpdate = 90): Builder
    {
        return $query->where(function ($q) use ($maxDaysWithoutUpdate) {
            $q->whereNull('last_updated')
              ->orWhere('last_updated', '<', Carbon::now()->subDays($maxDaysWithoutUpdate));
        });
    }

    /**
     * Scope for applications with expiring contracts
     */
    public function scopeContractsExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('vendor_contract_expiry')
                    ->where('vendor_contract_expiry', '<=', Carbon::now()->addDays($days))
                    ->where('vendor_contract_expiry', '>=', Carbon::now());
    }

    /**
     * Scope for applications with expiring licenses
     */
    public function scopeLicensesExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('license_expiry')
                    ->where('license_expiry', '<=', Carbon::now()->addDays($days))
                    ->where('license_expiry', '>=', Carbon::now());
    }

    /**
     * Scope for applications by health status
     */
    public function scopeByHealthStatus(Builder $query, string $healthStatus): Builder
    {
        return $query->where('health_status', $healthStatus);
    }

    /**
     * Scope for applications at capacity
     */
    public function scopeAtCapacity(Builder $query): Builder
    {
        return $query->whereNotNull('max_users')
                    ->whereNotNull('current_users')
                    ->whereRaw('current_users >= max_users');
    }

    /**
     * Scope for applications under capacity
     */
    public function scopeUnderCapacity(Builder $query, float $utilizationThreshold = 80.0): Builder
    {
        return $query->whereNotNull('max_users')
                    ->whereNotNull('current_users')
                    ->whereRaw('current_users < (max_users * ? / 100)', [$utilizationThreshold]);
    }

    /**
     * Scope for applications by business owner
     */
    public function scopeByBusinessOwner(Builder $query, string $owner): Builder
    {
        return $query->where('business_owner', $owner);
    }

    /**
     * Scope for applications by technical owner
     */
    public function scopeByTechnicalOwner(Builder $query, string $owner): Builder
    {
        return $query->where('technical_owner', $owner);
    }

    /**
     * Scope for applications by cost center
     */
    public function scopeByCostCenter(Builder $query, string $costCenter): Builder
    {
        return $query->where('cost_center', $costCenter);
    }

    /**
     * Scope for applications by monthly cost range
     */
    public function scopeByMonthlyCostRange(Builder $query, float $minCost, ?float $maxCost = null): Builder
    {
        $query->where('monthly_cost', '>=', $minCost);
        if ($maxCost) {
            $query->where('monthly_cost', '<=', $maxCost);
        }
        return $query;
    }

    /**
     * Scope for expensive applications
     */
    public function scopeExpensive(Builder $query, float $minMonthlyCost = 1000): Builder
    {
        return $query->where('monthly_cost', '>=', $minMonthlyCost);
    }

    /**
     * Scope for applications with monitoring enabled
     */
    public function scopeWithMonitoring(Builder $query): Builder
    {
        return $query->where('monitoring_enabled', true);
    }

    /**
     * Scope for applications without monitoring
     */
    public function scopeWithoutMonitoring(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('monitoring_enabled', false)
              ->orWhereNull('monitoring_enabled');
        });
    }

    /**
     * Scope for applications with backup schedule
     */
    public function scopeWithBackupSchedule(Builder $query): Builder
    {
        return $query->whereNotNull('backup_schedule');
    }

    /**
     * Scope for applications without backup schedule
     */
    public function scopeWithoutBackupSchedule(Builder $query): Builder
    {
        return $query->whereNull('backup_schedule');
    }

    /**
     * Scope for applications by age
     */
    public function scopeByAge(Builder $query, int $minDays, ?int $maxDays = null): Builder
    {
        $query->where('created_at', '<=', Carbon::now()->subDays($minDays));
        if ($maxDays) {
            $query->where('created_at', '>=', Carbon::now()->subDays($maxDays));
        }
        return $query;
    }

    /**
     * Scope for new applications
     */
    public function scopeNewApplications(Builder $query, int $maxAgeDays = 30): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($maxAgeDays));
    }

    /**
     * Scope for old applications
     */
    public function scopeOldApplications(Builder $query, int $minAgeDays = 365): Builder
    {
        return $query->where('created_at', '<=', Carbon::now()->subDays($minAgeDays));
    }

    /**
     * Scope for applications by priority score
     */
    public function scopeByPriorityScore(Builder $query, float $minScore, ?float $maxScore = null): Builder
    {
        // This would require a more complex query to calculate priority score
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for high priority applications
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('criticality', [self::CRITICALITY_CRITICAL, self::CRITICALITY_HIGH])
                    ->orWhere(function ($q) {
                        $q->whereNotNull('current_users')
                          ->where('current_users', '>=', 500);
                    });
    }

    /**
     * Scope for applications with SLA
     */
    public function scopeWithSla(Builder $query): Builder
    {
        return $query->whereNotNull('sla_hours');
    }

    /**
     * Scope for applications without SLA
     */
    public function scopeWithoutSla(Builder $query): Builder
    {
        return $query->whereNull('sla_hours');
    }

    /**
     * Scope for applications by SLA hours
     */
    public function scopeBySlaHours(Builder $query, int $minHours, ?int $maxHours = null): Builder
    {
        $query->where('sla_hours', '>=', $minHours);
        if ($maxHours) {
            $query->where('sla_hours', '<=', $maxHours);
        }
        return $query;
    }

    /**
     * Scope for applications with SLA compliance issues
     */
    public function scopeSlaNonCompliant(Builder $query, int $days = 30): Builder
    {
        // This would need a more complex query to check SLA compliance
        return $query->whereNotNull('sla_hours');
    }

    /**
     * Scope for applications by search term
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('vendor', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for applications by teknisi expertise
     */
    public function scopeExpertiseByTeknisi(Builder $query, string $teknisiNip): Builder
    {
        return $query->whereHas('expertTeknisis', function ($q) use ($teknisiNip) {
            $q->where('teknisi_nip', $teknisiNip);
        });
    }

    /**
     * Scope for applications managed by admin
     */
    public function scopeManagedByAdmin(Builder $query, string $adminNip): Builder
    {
        return $query->whereHas('adminAplikasis', function ($q) use ($adminNip) {
            $q->where('admin_aplikasi_nip', $adminNip);
        });
    }

    /**
     * Scope for applications with replacement
     */
    public function scopeWithReplacement(Builder $query): Builder
    {
        return $query->whereNotNull('replacement_application_id');
    }

    /**
     * Scope for applications that are replacements
     */
    public function scopeIsReplacement(Builder $query): Builder
    {
        return $query->whereHas('replacedApplications');
    }

    /**
     * Scope for applications by license type
     */
    public function scopeByLicenseType(Builder $query, string $licenseType): Builder
    {
        return $query->where('license_type', $licenseType);
    }

    /**
     * Scope for open source applications
     */
    public function scopeOpenSource(Builder $query): Builder
    {
        return $query->whereIn('license_type', ['opensource', 'mit', 'gpl', 'apache']);
    }

    /**
     * Scope for commercial applications
     */
    public function scopeCommercial(Builder $query): Builder
    {
        return $query->where('license_type', 'commercial');
    }

    /**
     * Scope for applications with security classification
     */
    public function scopeBySecurityClassification(Builder $query, string $classification): Builder
    {
        return $query->where('security_classification', $classification);
    }

    /**
     * Scope for applications with disaster recovery plan
     */
    public function scopeWithDisasterRecovery(Builder $query): Builder
    {
        return $query->whereNotNull('disaster_recovery_plan');
    }

    /**
     * Scope for applications requiring change management
     */
    public function scopeRequiresChangeManagement(Builder $query): Builder
    {
        return $query->where('change_management_required', true);
    }

    /**
     * Scope for applications with approval workflow
     */
    public function scopeWithApprovalWorkflow(Builder $query): Builder
    {
        return $query->where('approval_workflow_enabled', true);
    }

    /**
     * Scope for applications by sort order
     */
    public function scopeOrdered(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('sort_order', $direction)->orderBy('name', $direction);
    }

    /**
     * Scope for applications by recent activity
     */
    public function scopeRecentlyUpdated(Builder $query, int $days = 7): Builder
    {
        return $query->where('last_updated', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope for applications with high error rate
     */
    public function scopeHighErrorRate(Builder $query, float $maxErrorRate = 0.05): Builder
    {
        return $query->whereNotNull('error_rate')
                    ->where('error_rate', '>', $maxErrorRate);
    }

    /**
     * Scope for applications with low uptime
     */
    public function scopeLowUptime(Builder $query, float $minUptime = 99.0): Builder
    {
        return $query->whereNotNull('uptime_percentage')
                    ->where('uptime_percentage', '<', $minUptime);
    }

    /**
     * Scope for applications by response time
     */
    public function scopeByResponseTime(Builder $query, float $maxResponseTime): Builder
    {
        return $query->whereNotNull('response_time_avg')
                    ->where('response_time_avg', '<=', $maxResponseTime);
    }

    /**
     * Scope for applications with slow response time
     */
    public function scopeSlowResponse(Builder $query, float $maxResponseTime = 500): Builder
    {
        return $query->whereNotNull('response_time_avg')
                    ->where('response_time_avg', '>', $maxResponseTime);
    }

    // ==================== CATEGORY INTEGRATION ====================

    /**
     * Get categories with ticket statistics
     */
    public function getCategoriesWithStats(): \Illuminate\Support\Collection
    {
        return $this->kategoriMasalahs()
                   ->withCount(['tickets', 'tickets as open_tickets_count' => function ($query) {
                       $query->where('status', Ticket::STATUS_OPEN);
                   }])
                   ->with(['tickets' => function ($query) {
                       $query->selectRaw('kategori_masalah_id, AVG(resolution_time_minutes) as avg_resolution_time')
                             ->where('status', Ticket::STATUS_RESOLVED)
                             ->groupBy('kategori_masalah_id');
                   }])
                   ->get()
                   ->map(function ($category) {
                       $avgResolutionTime = $category->tickets->first()->avg_resolution_time ?? null;
                       return [
                           'id' => $category->id,
                           'name' => $category->name,
                           'description' => $category->description,
                           'priority' => $category->priority,
                           'total_tickets' => $category->tickets_count,
                           'open_tickets' => $category->open_tickets_count,
                           'resolution_rate' => $category->tickets_count > 0 ?
                               (($category->tickets_count - $category->open_tickets_count) / $category->tickets_count) * 100 : 0,
                           'avg_resolution_time' => $avgResolutionTime,
                           'estimated_resolution_time' => $category->estimated_resolution_time,
                           'requires_attachment' => $category->requires_attachment,
                           'sort_order' => $category->sort_order,
                       ];
                   });
    }

    /**
     * Get most common categories by ticket volume
     */
    public function getMostCommonCategories(int $limit = 5): \Illuminate\Support\Collection
    {
        return $this->kategoriMasalahs()
                   ->withCount('tickets')
                   ->orderBy('tickets_count', 'desc')
                   ->limit($limit)
                   ->get()
                   ->map(function ($category) {
                       return [
                           'name' => $category->name,
                           'ticket_count' => $category->tickets_count,
                           'percentage' => $this->getTotalTicketCount() > 0 ?
                               round(($category->tickets_count / $this->getTotalTicketCount()) * 100, 2) : 0,
                       ];
                   });
    }

    /**
     * Get category performance analysis
     */
    public function getCategoryPerformanceAnalysis(): array
    {
        $categories = $this->kategoriMasalahs()
                          ->withCount(['tickets', 'tickets as resolved_tickets_count' => function ($query) {
                              $query->where('status', Ticket::STATUS_RESOLVED);
                          }])
                          ->with(['tickets' => function ($query) {
                              $query->where('status', Ticket::STATUS_RESOLVED)
                                    ->selectRaw('kategori_masalah_id, AVG(resolution_time_minutes) as avg_time, AVG(user_rating) as avg_rating')
                                    ->groupBy('kategori_masalah_id');
                          }])
                          ->get();

        $totalTickets = $this->getTotalTicketCount();

        return $categories->map(function ($category) use ($totalTickets) {
            $performance = $category->tickets->first();

            return [
                'category_name' => $category->name,
                'ticket_volume' => $category->tickets_count,
                'volume_percentage' => $totalTickets > 0 ? round(($category->tickets_count / $totalTickets) * 100, 2) : 0,
                'resolution_rate' => $category->tickets_count > 0 ?
                    round(($category->resolved_tickets_count / $category->tickets_count) * 100, 2) : 0,
                'avg_resolution_time' => $performance->avg_time ?? null,
                'avg_user_rating' => $performance->avg_rating ?? null,
                'estimated_time' => $category->estimated_resolution_time,
                'performance_vs_estimate' => $category->estimated_resolution_time && $performance->avg_time ?
                    round(($performance->avg_time / $category->estimated_resolution_time) * 100, 2) : null,
            ];
        })->toArray();
    }

    /**
     * Get category trends over time
     */
    public function getCategoryTrends(int $days = 30): array
    {
        $categories = $this->kategoriMasalahs()
                          ->withCount(['tickets' => function ($query) use ($days) {
                              $query->where('created_at', '>=', Carbon::now()->subDays($days));
                          }])
                          ->having('tickets_count', '>', 0)
                          ->orderBy('tickets_count', 'desc')
                          ->get();

        return [
            'period_days' => $days,
            'total_tickets_in_period' => $this->getTicketsInDateRange(
                Carbon::now()->subDays($days),
                Carbon::now()
            ),
            'categories' => $categories->map(function ($category) {
                return [
                    'name' => $category->name,
                    'ticket_count' => $category->tickets_count,
                    'priority' => $category->priority,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get category distribution for pie chart
     */
    public function getCategoryDistribution(): array
    {
        $categories = $this->kategoriMasalahs()
                          ->withCount('tickets')
                          ->orderBy('tickets_count', 'desc')
                          ->get();

        return $categories->map(function ($category) {
            return [
                'name' => $category->name,
                'value' => $category->tickets_count,
                'percentage' => $this->getTotalTicketCount() > 0 ?
                    round(($category->tickets_count / $this->getTotalTicketCount()) * 100, 2) : 0,
                'color' => $this->getCategoryColor($category->priority),
            ];
        })->toArray();
    }

    /**
     * Get color for category based on priority
     */
    private function getCategoryColor(string $priority): string
    {
        return match($priority) {
            'urgent' => '#dc3545',
            'high' => '#fd7e14',
            'medium' => '#20c997',
            'low' => '#6c757d',
            default => '#007bff',
        };
    }

    /**
     * Get category efficiency metrics
     */
    public function getCategoryEfficiencyMetrics(): array
    {
        $categories = $this->kategoriMasalahs()
                          ->with(['tickets' => function ($query) {
                              $query->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
                                    ->selectRaw('kategori_masalah_id,
                                               COUNT(*) as total_resolved,
                                               AVG(resolution_time_minutes) as avg_resolution_time,
                                               AVG(user_rating) as avg_rating')
                                    ->groupBy('kategori_masalah_id');
                          }])
                          ->get();

        return $categories->map(function ($category) {
            $stats = $category->tickets->first();

            return [
                'category_name' => $category->name,
                'total_resolved' => $stats->total_resolved ?? 0,
                'avg_resolution_time' => $stats->avg_resolution_time ?? null,
                'avg_rating' => $stats->avg_rating ?? null,
                'efficiency_score' => $this->calculateCategoryEfficiencyScore(
                    $stats->avg_resolution_time ?? 0,
                    $category->estimated_resolution_time ?? 0,
                    $stats->avg_rating ?? 0
                ),
            ];
        })->toArray();
    }

    /**
     * Calculate category efficiency score
     */
    private function calculateCategoryEfficiencyScore(float $actualTime, float $estimatedTime, float $rating): float
    {
        if ($estimatedTime === 0) {
            return 0;
        }

        // Time efficiency (50% weight)
        $timeEfficiency = max(0, 100 - (($actualTime / $estimatedTime) * 100));

        // Rating efficiency (50% weight)
        $ratingEfficiency = ($rating / 5) * 100;

        return round(($timeEfficiency + $ratingEfficiency) / 2, 2);
    }

    /**
     * Get category SLA compliance
     */
    public function getCategorySlaCompliance(): array
    {
        if (!$this->sla_hours) {
            return [];
        }

        $categories = $this->kategoriMasalahs()
                          ->with(['tickets' => function ($query) {
                              $query->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
                                    ->selectRaw('kategori_masalah_id,
                                               COUNT(*) as total_tickets,
                                               COUNT(CASE WHEN resolved_at <= DATE_ADD(created_at, INTERVAL ? HOUR) THEN 1 END) as sla_compliant')
                                    ->groupBy('kategori_masalah_id')
                                    ->setBindings([$this->sla_hours]);
                          }])
                          ->get();

        return $categories->map(function ($category) {
            $stats = $category->tickets->first();

            if (!$stats || $stats->total_tickets === 0) {
                return [
                    'category_name' => $category->name,
                    'total_tickets' => 0,
                    'sla_compliant' => 0,
                    'compliance_rate' => 0,
                ];
            }

            return [
                'category_name' => $category->name,
                'total_tickets' => $stats->total_tickets,
                'sla_compliant' => $stats->sla_compliant,
                'compliance_rate' => round(($stats->sla_compliant / $stats->total_tickets) * 100, 2),
            ];
        })->toArray();
    }

    /**
     * Get category recommendations for improvement
     */
    public function getCategoryImprovementRecommendations(): array
    {
        $recommendations = [];
        $categories = $this->getCategoriesWithStats();

        foreach ($categories as $category) {
            // High volume categories need more attention
            if ($category['total_tickets'] > 50) {
                $recommendations[] = [
                    'category' => $category['name'],
                    'issue' => 'High ticket volume',
                    'recommendation' => 'Consider creating specialized documentation or training for this category',
                    'priority' => 'medium',
                ];
            }

            // Low resolution rate categories
            if ($category['resolution_rate'] < 70) {
                $recommendations[] = [
                    'category' => $category['name'],
                    'issue' => 'Low resolution rate',
                    'recommendation' => 'Review resolution process and provide additional training to teknisi',
                    'priority' => 'high',
                ];
            }

            // Long resolution time categories
            if ($category['avg_resolution_time'] && $category['estimated_resolution_time'] &&
                $category['avg_resolution_time'] > $category['estimated_resolution_time'] * 1.5) {
                $recommendations[] = [
                    'category' => $category['name'],
                    'issue' => 'Resolution time exceeds estimate',
                    'recommendation' => 'Review category complexity and adjust estimated resolution time',
                    'priority' => 'medium',
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Get category-based ticket patterns
     */
    public function getCategoryTicketPatterns(): array
    {
        $patterns = [];

        // Get tickets by category and time patterns
        $categories = $this->kategoriMasalahs()
                          ->with(['tickets' => function ($query) {
                              $query->selectRaw('kategori_masalah_id,
                                               HOUR(created_at) as hour,
                                               DAYOFWEEK(created_at) as day_of_week,
                                               COUNT(*) as count')
                                    ->where('created_at', '>=', Carbon::now()->subDays(90))
                                    ->groupBy('kategori_masalah_id', 'hour', 'day_of_week');
                          }])
                          ->get();

        foreach ($categories as $category) {
            $hourlyPattern = [];
            $dailyPattern = [];

            foreach ($category->tickets as $pattern) {
                $hourlyPattern[$pattern->hour] = ($hourlyPattern[$pattern->hour] ?? 0) + $pattern->count;
                $dailyPattern[$pattern->day_of_week] = ($dailyPattern[$pattern->day_of_week] ?? 0) + $pattern->count;
            }

            if (!empty($hourlyPattern) || !empty($dailyPattern)) {
                $patterns[] = [
                    'category_name' => $category->name,
                    'hourly_pattern' => $hourlyPattern,
                    'daily_pattern' => $dailyPattern,
                    'peak_hour' => !empty($hourlyPattern) ? array_keys($hourlyPattern)[array_search(max($hourlyPattern), $hourlyPattern)] : null,
                    'peak_day' => !empty($dailyPattern) ? array_keys($dailyPattern)[array_search(max($dailyPattern), $dailyPattern)] : null,
                ];
            }
        }

        return $patterns;
    }

    /**
     * Get comprehensive category analytics
     */
    public function getCategoryAnalytics(): array
    {
        return [
            'summary' => [
                'total_categories' => $this->kategoriMasalahs()->count(),
                'total_tickets' => $this->getTotalTicketCount(),
                'categories_with_tickets' => $this->kategoriMasalahs()->has('tickets')->count(),
            ],
            'distribution' => $this->getCategoryDistribution(),
            'performance' => $this->getCategoryPerformanceAnalysis(),
            'trends' => $this->getCategoryTrends(),
            'sla_compliance' => $this->getCategorySlaCompliance(),
            'patterns' => $this->getCategoryTicketPatterns(),
            'recommendations' => $this->getCategoryImprovementRecommendations(),
            'most_common' => $this->getMostCommonCategories(),
        ];
    }

    /**
     * Sync categories with predefined templates
     */
    public function syncWithCategoryTemplates(array $templates): bool
    {
        $created = 0;
        $updated = 0;

        foreach ($templates as $template) {
            $category = $this->kategoriMasalahs()
                           ->where('name', $template['name'])
                           ->first();

            if (!$category) {
                $category = new KategoriMasalah($template);
                $category->aplikasi_id = $this->id;
                if ($category->save()) {
                    $created++;
                }
            } else {
                $category->update($template);
                $updated++;
            }
        }

        \Illuminate\Support\Facades\Log::info(
            "Synced categories for application {$this->name}: {$created} created, {$updated} updated"
        );

        return true;
    }

    /**
     * Get category health score
     */
    public function getCategoryHealthScore(): float
    {
        $categories = $this->getCategoriesWithStats();
        if ($categories->isEmpty()) {
            return 100.0;
        }

        $totalScore = 0;
        $categoryCount = 0;

        foreach ($categories as $category) {
            $score = 100;

            // Penalize for high open ticket count
            if ($category['open_tickets'] > 10) {
                $score -= 20;
            }

            // Penalize for low resolution rate
            if ($category['resolution_rate'] < 80) {
                $score -= 30;
            }

            // Penalize for long resolution times
            if ($category['avg_resolution_time'] && $category['estimated_resolution_time'] &&
                $category['avg_resolution_time'] > $category['estimated_resolution_time'] * 1.5) {
                $score -= 25;
            }

            $totalScore += max(0, $score);
            $categoryCount++;
        }

        return round($totalScore / $categoryCount, 2);
    }

    /**
     * Get category utilization report
     */
    public function getCategoryUtilizationReport(): array
    {
        $categories = $this->kategoriMasalahs()
                          ->withCount(['tickets' => function ($query) {
                              $query->where('created_at', '>=', Carbon::now()->subDays(30));
                          }])
                          ->get();

        $totalRecentTickets = $categories->sum('tickets_count');

        return [
            'period' => 'Last 30 days',
            'total_tickets' => $totalRecentTickets,
            'categories' => $categories->map(function ($category) use ($totalRecentTickets) {
                return [
                    'name' => $category->name,
                    'ticket_count' => $category->tickets_count,
                    'utilization_percentage' => $totalRecentTickets > 0 ?
                        round(($category->tickets_count / $totalRecentTickets) * 100, 2) : 0,
                    'is_active' => $category->tickets_count > 0,
                ];
            })->sortByDesc('ticket_count')->values()->toArray(),
        ];
    }
}