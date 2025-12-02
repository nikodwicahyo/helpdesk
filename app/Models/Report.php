<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class Report extends Model
{
    use HasFactory;

    // Report type constants
    const TYPE_PERFORMANCE = 'performance';
    const TYPE_USAGE = 'usage';
    const TYPE_SLA = 'sla';
    const TYPE_COMPLIANCE = 'compliance';
    const TYPE_TICKET_ANALYSIS = 'ticket_analysis';
    const TYPE_USER_ACTIVITY = 'user_activity';
    const TYPE_TEKNISI_PERFORMANCE = 'teknisi_performance';
    const TYPE_APPLICATION_HEALTH = 'application_health';
    const TYPE_SYSTEM_OVERVIEW = 'system_overview';
    const TYPE_CUSTOM = 'custom';

    // Report status constants
    const STATUS_PENDING = 'pending';
    const STATUS_GENERATING = 'generating';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_SCHEDULED = 'scheduled';

    // Period type constants
    const PERIOD_DAILY = 'daily';
    const PERIOD_WEEKLY = 'weekly';
    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_QUARTERLY = 'quarterly';
    const PERIOD_YEARLY = 'yearly';
    const PERIOD_CUSTOM = 'custom';

    // Schedule frequency constants
    const SCHEDULE_DAILY = 'daily';
    const SCHEDULE_WEEKLY = 'weekly';
    const SCHEDULE_MONTHLY = 'monthly';
    const SCHEDULE_QUARTERLY = 'quarterly';

    // File format constants
    const FORMAT_PDF = 'pdf';
    const FORMAT_EXCEL = 'excel';
    const FORMAT_CSV = 'csv';
    const FORMAT_JSON = 'json';

    // Visibility constants
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PRIVATE = 'private';
    const VISIBILITY_ROLE_BASED = 'role_based';

    protected $fillable = [
        'report_type',
        'title',
        'description',
        'report_date',
        'period_type',
        'period_start',
        'period_end',
        'generated_by_nip',
        'generated_by_type',
        'data',
        'filters',
        'parameters',
        'sql_query',
        'status',
        'record_count',
        'execution_time_seconds',
        'error_message',
        'file_path',
        'file_format',
        'is_scheduled',
        'schedule_frequency',
        'schedule_time',
        'next_run_at',
        'recipients',
        'visibility',
        'allowed_roles',
        'is_automated',
        'last_generated_at',
        'generation_count',
        'metadata',
    ];

    protected $casts = [
        'report_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'data' => 'array',
        'filters' => 'array',
        'parameters' => 'array',
        'is_scheduled' => 'boolean',
        'is_automated' => 'boolean',
        'schedule_time' => 'datetime',
        'next_run_at' => 'datetime',
        'last_generated_at' => 'datetime',
        'recipients' => 'array',
        'allowed_roles' => 'array',
        'metadata' => 'array',
        'generation_count' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user who generated this report
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_nip', 'nip');
    }

    /**
     * Get related reports (for report templates or series)
     */
    public function relatedReports(): HasMany
    {
        return $this->hasMany(Report::class, 'report_template_id');
    }

    /**
     * Get report template if this is a generated report
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_template_id');
    }

    /**
     * Get report executions for scheduled reports
     */
    public function executions(): HasMany
    {
        return $this->hasMany(Report::class, 'parent_report_id');
    }

    /**
     * Get parent report for executions
     */
    public function parentReport(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'parent_report_id');
    }

    // ==================== REPORT TYPES ====================

    /**
     * Get all available report types
     */
    public static function getReportTypes(): array
    {
        return [
            self::TYPE_PERFORMANCE => 'Performance Report',
            self::TYPE_USAGE => 'Usage Report',
            self::TYPE_SLA => 'SLA Report',
            self::TYPE_COMPLIANCE => 'Compliance Report',
            self::TYPE_TICKET_ANALYSIS => 'Ticket Analysis',
            self::TYPE_USER_ACTIVITY => 'User Activity Report',
            self::TYPE_TEKNISI_PERFORMANCE => 'Teknisi Performance Report',
            self::TYPE_APPLICATION_HEALTH => 'Application Health Report',
            self::TYPE_SYSTEM_OVERVIEW => 'System Overview',
            self::TYPE_CUSTOM => 'Custom Report',
        ];
    }

    /**
     * Get report type label
     */
    public function getReportTypeLabelAttribute(): string
    {
        return self::getReportTypes()[$this->report_type] ?? 'Unknown';
    }

    /**
     * Get report type badge color for UI
     */
    public function getReportTypeBadgeColorAttribute(): string
    {
        return match($this->report_type) {
            self::TYPE_PERFORMANCE => 'info',
            self::TYPE_USAGE => 'success',
            self::TYPE_SLA => 'warning',
            self::TYPE_COMPLIANCE => 'danger',
            self::TYPE_TICKET_ANALYSIS => 'primary',
            self::TYPE_USER_ACTIVITY => 'secondary',
            self::TYPE_TEKNISI_PERFORMANCE => 'info',
            self::TYPE_APPLICATION_HEALTH => 'success',
            self::TYPE_SYSTEM_OVERVIEW => 'primary',
            self::TYPE_CUSTOM => 'light',
            default => 'light',
        };
    }

    // ==================== DATA AGGREGATION METHODS ====================

    /**
     * Generate performance report data
     */
    public function generatePerformanceReport(): array
    {
        $startDate = $this->period_start ?? Carbon::now()->subDays(30);
        $endDate = $this->period_end ?? Carbon::now();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'ticket_metrics' => $this->getTicketMetrics($startDate, $endDate),
            'teknisi_metrics' => $this->getTeknisiMetrics($startDate, $endDate),
            'application_metrics' => $this->getApplicationMetrics($startDate, $endDate),
            'system_overview' => $this->getSystemOverview($startDate, $endDate),
        ];
    }

    /**
     * Generate usage report data
     */
    public function generateUsageReport(): array
    {
        $startDate = $this->period_start ?? Carbon::now()->subDays(30);
        $endDate = $this->period_end ?? Carbon::now();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'user_activity' => $this->getUserActivityMetrics($startDate, $endDate),
            'application_usage' => $this->getApplicationUsageMetrics($startDate, $endDate),
            'category_usage' => $this->getCategoryUsageMetrics($startDate, $endDate),
            'trends' => $this->getUsageTrends($startDate, $endDate),
        ];
    }

    /**
     * Generate SLA report data
     */
    public function generateSlaReport(): array
    {
        $startDate = $this->period_start ?? Carbon::now()->subDays(30);
        $endDate = $this->period_end ?? Carbon::now();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'sla_compliance' => $this->getSlaComplianceMetrics($startDate, $endDate),
            'breach_analysis' => $this->getSlaBreachAnalysis($startDate, $endDate),
            'application_sla' => $this->getApplicationSlaMetrics($startDate, $endDate),
            'teknisi_sla' => $this->getTeknisiSlaMetrics($startDate, $endDate),
        ];
    }

    /**
     * Generate compliance report data
     */
    public function generateComplianceReport(): array
    {
        $startDate = $this->period_start ?? Carbon::now()->subDays(30);
        $endDate = $this->period_end ?? Carbon::now();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'policy_compliance' => $this->getPolicyComplianceMetrics($startDate, $endDate),
            'security_compliance' => $this->getSecurityComplianceMetrics($startDate, $endDate),
            'audit_trail' => $this->getAuditTrailMetrics($startDate, $endDate),
            'violations' => $this->getComplianceViolations($startDate, $endDate),
        ];
    }

    /**
     * Generate ticket analysis report data
     */
    public function generateTicketAnalysisReport(): array
    {
        $startDate = $this->period_start ?? Carbon::now()->subDays(30);
        $endDate = $this->period_end ?? Carbon::now();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'ticket_volume' => $this->getTicketVolumeAnalysis($startDate, $endDate),
            'resolution_analysis' => $this->getResolutionAnalysis($startDate, $endDate),
            'priority_analysis' => $this->getPriorityAnalysis($startDate, $endDate),
            'category_analysis' => $this->getCategoryAnalysis($startDate, $endDate),
        ];
    }

    /**
     * Generate user activity report data
     */
    public function generateUserActivityReport(): array
    {
        $startDate = $this->period_start ?? Carbon::now()->subDays(30);
        $endDate = $this->period_end ?? Carbon::now();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'user_engagement' => $this->getUserEngagementMetrics($startDate, $endDate),
            'activity_patterns' => $this->getActivityPatterns($startDate, $endDate),
            'department_analysis' => $this->getDepartmentAnalysis($startDate, $endDate),
            'satisfaction_metrics' => $this->getSatisfactionMetrics($startDate, $endDate),
        ];
    }

    /**
     * Generate teknisi performance report data
     */
    public function generateTeknisiPerformanceReport(): array
    {
        $startDate = $this->period_start ?? Carbon::now()->subDays(30);
        $endDate = $this->period_end ?? Carbon::now();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'individual_performance' => $this->getIndividualTeknisiPerformance($startDate, $endDate),
            'team_performance' => $this->getTeamPerformanceMetrics($startDate, $endDate),
            'workload_analysis' => $this->getWorkloadAnalysis($startDate, $endDate),
            'skill_utilization' => $this->getSkillUtilizationMetrics($startDate, $endDate),
        ];
    }

    /**
     * Generate application health report data
     */
    public function generateApplicationHealthReport(): array
    {
        $startDate = $this->period_start ?? Carbon::now()->subDays(30);
        $endDate = $this->period_end ?? Carbon::now();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'health_overview' => $this->getApplicationHealthOverview($startDate, $endDate),
            'performance_metrics' => $this->getApplicationPerformanceMetrics($startDate, $endDate),
            'issue_analysis' => $this->getApplicationIssueAnalysis($startDate, $endDate),
            'recommendations' => $this->getApplicationRecommendations($startDate, $endDate),
        ];
    }

    /**
     * Generate system overview report data
     */
    public function generateSystemOverviewReport(): array
    {
        $startDate = $this->period_start ?? Carbon::now()->subDays(30);
        $endDate = $this->period_end ?? Carbon::now();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'system_health' => $this->getSystemHealthMetrics(),
            'resource_utilization' => $this->getResourceUtilizationMetrics(),
            'security_overview' => $this->getSecurityOverviewMetrics($startDate, $endDate),
            'capacity_planning' => $this->getCapacityPlanningMetrics(),
        ];
    }

    /**
     * Generate custom report data based on SQL query
     */
    public function generateCustomReport(): array
    {
        if (!$this->sql_query) {
            return ['error' => 'No SQL query defined for custom report'];
        }

        try {
            $results = DB::select($this->sql_query);
            return [
                'query_results' => $results,
                'record_count' => count($results),
                'columns' => $results ? array_keys((array) $results[0]) : [],
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Query execution failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    // ==================== METRIC COLLECTION METHODS ====================

    /**
     * Get ticket metrics for performance reports
     */
    private function getTicketMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'total_tickets' => $tickets->count(),
            'resolved_tickets' => $tickets->where('status', Ticket::STATUS_RESOLVED)->count(),
            'avg_resolution_time' => $tickets->whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes'),
            'sla_compliance_rate' => $this->calculateSlaComplianceRate($tickets),
            'priority_distribution' => $tickets->groupBy('priority')->map->count(),
            'status_distribution' => $tickets->groupBy('status')->map->count(),
        ];
    }

    /**
     * Get teknisi metrics for performance reports
     */
    private function getTeknisiMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $teknisi = Teknisi::where('status', Teknisi::STATUS_ACTIVE)->get();

        return [
            'total_teknisi' => $teknisi->count(),
            'avg_rating' => $teknisi->whereNotNull('rating')->avg('rating'),
            'total_tickets_handled' => $teknisi->sum('ticket_count'),
            'avg_resolution_rate' => $teknisi->avg(function ($tek) {
                return $tek->getResolutionRate();
            }),
            'workload_distribution' => Teknisi::getWorkloadDistribution(),
        ];
    }

    /**
     * Get application metrics for performance reports
     */
    private function getApplicationMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $applications = Aplikasi::where('status', Aplikasi::STATUS_ACTIVE)->get();

        return [
            'total_applications' => $applications->count(),
            'healthy_applications' => $applications->where('health_status', '!=', 'poor')->count(),
            'avg_uptime' => $applications->whereNotNull('uptime_percentage')->avg('uptime_percentage'),
            'total_tickets' => $applications->sum(function ($app) {
                return $app->getTotalTicketCount();
            }),
            'needing_attention' => $applications->filter->needsAttention()->count(),
        ];
    }

    /**
     * Get system overview metrics
     */
    private function getSystemOverview(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total_users' => User::where('status', 'active')->count(),
            'total_tickets' => Ticket::whereBetween('created_at', [$startDate, $endDate])->count(),
            'system_uptime' => $this->calculateSystemUptime(),
            'avg_response_time' => $this->calculateAvgResponseTime($startDate, $endDate),
            'error_rate' => $this->calculateErrorRate($startDate, $endDate),
        ];
    }

    /**
     * Get user activity metrics
     */
    private function getUserActivityMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $users = User::where('status', 'active')->get();

        return [
            'active_users' => $users->count(),
            'users_with_tickets' => $users->filter(function ($user) {
                return $user->tickets()->count() > 0;
            })->count(),
            'avg_tickets_per_user' => $users->avg(function ($user) {
                return $user->tickets()->count();
            }),
            'department_distribution' => $users->groupBy('department')->map->count(),
        ];
    }

    /**
     * Get application usage metrics
     */
    private function getApplicationUsageMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $applications = Aplikasi::withCount(['tickets' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])->get();

        return [
            'most_used_applications' => $applications->sortByDesc('tickets_count')->take(10)->map(function ($app) {
                return [
                    'name' => $app->name,
                    'ticket_count' => $app->tickets_count,
                ];
            }),
            'usage_by_category' => $applications->groupBy('category')->map(function ($apps) {
                return $apps->sum('tickets_count');
            }),
        ];
    }

    /**
     * Get category usage metrics
     */
    private function getCategoryUsageMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $categories = KategoriMasalah::withCount(['tickets' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])->get();

        return [
            'most_common_categories' => $categories->sortByDesc('tickets_count')->take(10)->map(function ($cat) {
                return [
                    'name' => $cat->name,
                    'ticket_count' => $cat->tickets_count,
                ];
            }),
            'category_resolution_times' => $categories->map(function ($cat) {
                return [
                    'name' => $cat->name,
                    'avg_resolution_time' => $cat->tickets->avg('resolution_time_minutes'),
                ];
            }),
        ];
    }

    /**
     * Get usage trends
     */
    private function getUsageTrends(Carbon $startDate, Carbon $endDate): array
    {
        $dailyTickets = Ticket::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                              ->whereBetween('created_at', [$startDate, $endDate])
                              ->groupBy('date')
                              ->orderBy('date')
                              ->get();

        return [
            'daily_volume' => $dailyTickets->pluck('count', 'date'),
            'trend_direction' => $this->calculateTrend($dailyTickets->pluck('count')),
            'peak_usage_day' => $dailyTickets->sortByDesc('count')->first()?->date,
        ];
    }

    /**
     * Get SLA compliance metrics
     */
    private function getSlaComplianceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
                         ->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
                         ->get();

        $totalTickets = $tickets->count();
        $slaCompliant = 0;

        foreach ($tickets as $ticket) {
            $slaDeadline = $ticket->created_at->copy()->addHours(Ticket::PRIORITY_SLA_HOURS[$ticket->priority] ?? 72);
            if ($ticket->resolved_at && $ticket->resolved_at->lte($slaDeadline)) {
                $slaCompliant++;
            }
        }

        return [
            'total_tickets' => $totalTickets,
            'sla_compliant' => $slaCompliant,
            'compliance_rate' => $totalTickets > 0 ? round(($slaCompliant / $totalTickets) * 100, 2) : 0,
            'by_priority' => $this->getSlaComplianceByPriority($tickets),
        ];
    }

    /**
     * Get SLA breach analysis
     */
    private function getSlaBreachAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $breachedTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
                                ->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
                                ->get()
                                ->filter(function ($ticket) {
                                    $slaDeadline = $ticket->created_at->copy()->addHours(Ticket::PRIORITY_SLA_HOURS[$ticket->priority] ?? 72);
                                    return $ticket->resolved_at && $ticket->resolved_at->gt($slaDeadline);
                                });

        return [
            'total_breaches' => $breachedTickets->count(),
            'avg_breach_time' => $breachedTickets->avg(function ($ticket) {
                $slaDeadline = $ticket->created_at->copy()->addHours(Ticket::PRIORITY_SLA_HOURS[$ticket->priority] ?? 72);
                return $ticket->resolved_at->diffInMinutes($slaDeadline);
            }),
            'by_priority' => $breachedTickets->groupBy('priority')->map->count(),
        ];
    }

    /**
     * Get application SLA metrics
     */
    private function getApplicationSlaMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $applications = Aplikasi::with(['tickets' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                  ->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);
        }])->get();

        return $applications->map(function ($app) {
            $tickets = $app->tickets;
            $totalTickets = $tickets->count();
            $slaCompliant = 0;

            foreach ($tickets as $ticket) {
                $slaHours = $app->sla_hours ?? (Ticket::PRIORITY_SLA_HOURS[$ticket->priority] ?? 72);
                $slaDeadline = $ticket->created_at->copy()->addHours($slaHours);
                if ($ticket->resolved_at && $ticket->resolved_at->lte($slaDeadline)) {
                    $slaCompliant++;
                }
            }

            return [
                'application_name' => $app->name,
                'total_tickets' => $totalTickets,
                'sla_compliant' => $slaCompliant,
                'compliance_rate' => $totalTickets > 0 ? round(($slaCompliant / $totalTickets) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Get teknisi SLA metrics
     */
    private function getTeknisiSlaMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $teknisi = Teknisi::with(['assignedTickets' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                  ->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);
        }])->get();

        return $teknisi->map(function ($tek) {
            $tickets = $tek->assignedTickets;
            $totalTickets = $tickets->count();
            $slaCompliant = 0;

            foreach ($tickets as $ticket) {
                $slaHours = Ticket::PRIORITY_SLA_HOURS[$ticket->priority] ?? 72;
                $slaDeadline = $ticket->created_at->copy()->addHours($slaHours);
                if ($ticket->resolved_at && $ticket->resolved_at->lte($slaDeadline)) {
                    $slaCompliant++;
                }
            }

            return [
                'teknisi_name' => $tek->name,
                'total_tickets' => $totalTickets,
                'sla_compliant' => $slaCompliant,
                'compliance_rate' => $totalTickets > 0 ? round(($slaCompliant / $totalTickets) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Get ticket volume analysis
     */
    private function getTicketVolumeAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'total_volume' => $tickets->count(),
            'daily_average' => $tickets->count() / max(1, $startDate->diffInDays($endDate)),
            'peak_day' => $tickets->groupBy(function ($ticket) {
                return $ticket->created_at->toDateString();
            })->sortByDesc(function ($tickets) {
                return $tickets->count();
            })->keys()->first(),
            'by_priority' => $tickets->groupBy('priority')->map->count(),
            'by_status' => $tickets->groupBy('status')->map->count(),
        ];
    }

    /**
     * Get resolution analysis
     */
    private function getResolutionAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $resolvedTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
                                ->where('status', Ticket::STATUS_RESOLVED)
                                ->get();

        return [
            'total_resolved' => $resolvedTickets->count(),
            'avg_resolution_time' => $resolvedTickets->whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes'),
            'resolution_rate' => $this->calculateResolutionRate($startDate, $endDate),
            'by_teknisi' => $resolvedTickets->groupBy('assigned_teknisi_nip')->map->count(),
            'by_application' => $resolvedTickets->groupBy('aplikasi_id')->map->count(),
        ];
    }

    /**
     * Get priority analysis
     */
    private function getPriorityAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'distribution' => $tickets->groupBy('priority')->map(function ($tickets) {
                return [
                    'count' => $tickets->count(),
                    'percentage' => round(($tickets->count() / max(1, $tickets->count())) * 100, 2),
                ];
            }),
            'resolution_times' => $tickets->groupBy('priority')->map(function ($tickets) {
                return $tickets->whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes');
            }),
        ];
    }

    /**
     * Get category analysis
     */
    private function getCategoryAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
                        ->with('kategoriMasalah')
                        ->get();

        return [
            'by_category' => $tickets->groupBy('kategori_masalah_id')->map(function ($tickets) {
                $category = $tickets->first()->kategoriMasalah;
                return [
                    'name' => $category?->name,
                    'count' => $tickets->count(),
                    'avg_resolution_time' => $tickets->whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes'),
                ];
            }),
            'trending_categories' => $this->getTrendingCategories($startDate, $endDate),
        ];
    }

    /**
     * Get user engagement metrics
     */
    private function getUserEngagementMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $users = User::where('status', 'active')->get();

        return [
            'total_active_users' => $users->count(),
            'users_with_activity' => $users->filter(function ($user) use ($startDate, $endDate) {
                return $user->tickets()->whereBetween('created_at', [$startDate, $endDate])->exists();
            })->count(),
            'avg_tickets_per_user' => $users->avg(function ($user) use ($startDate, $endDate) {
                return $user->tickets()->whereBetween('created_at', [$startDate, $endDate])->count();
            }),
        ];
    }

    /**
     * Get activity patterns
     */
    private function getActivityPatterns(Carbon $startDate, Carbon $endDate): array
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'by_hour' => $tickets->groupBy(function ($ticket) {
                return $ticket->created_at->hour;
            })->map->count(),
            'by_day' => $tickets->groupBy(function ($ticket) {
                return $ticket->created_at->dayOfWeek;
            })->map->count(),
            'by_month' => $tickets->groupBy(function ($ticket) {
                return $ticket->created_at->month;
            })->map->count(),
        ];
    }

    /**
     * Get department analysis
     */
    private function getDepartmentAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $users = User::where('status', 'active')
                    ->whereNotNull('department')
                    ->with(['tickets' => function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }])
                    ->get();

        return [
            'by_department' => $users->groupBy('department')->map(function ($users) use ($startDate, $endDate) {
                $totalTickets = $users->sum(function ($user) {
                    return $user->tickets->count();
                });

                return [
                    'user_count' => $users->count(),
                    'ticket_count' => $totalTickets,
                    'avg_tickets_per_user' => $users->count() > 0 ? round($totalTickets / $users->count(), 2) : 0,
                ];
            }),
        ];
    }

    /**
     * Get satisfaction metrics
     */
    private function getSatisfactionMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $ratedTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
                              ->where('status', Ticket::STATUS_RESOLVED)
                              ->whereNotNull('user_rating')
                              ->get();

        return [
            'total_rated_tickets' => $ratedTickets->count(),
            'avg_rating' => $ratedTickets->avg('user_rating'),
            'rating_distribution' => $ratedTickets->groupBy('user_rating')->map->count(),
        ];
    }

    /**
     * Get individual teknisi performance
     */
    private function getIndividualTeknisiPerformance(Carbon $startDate, Carbon $endDate): array
    {
        $teknisi = Teknisi::with(['assignedTickets' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])->get();

        return $teknisi->map(function ($tek) use ($startDate, $endDate) {
            $tickets = $tek->assignedTickets;
            $resolvedTickets = $tickets->where('status', Ticket::STATUS_RESOLVED);

            return [
                'name' => $tek->name,
                'nip' => $tek->nip,
                'total_assigned' => $tickets->count(),
                'total_resolved' => $resolvedTickets->count(),
                'resolution_rate' => $tickets->count() > 0 ? round(($resolvedTickets->count() / $tickets->count()) * 100, 2) : 0,
                'avg_resolution_time' => $resolvedTickets->whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes'),
                'avg_rating' => $resolvedTickets->whereNotNull('user_rating')->avg('user_rating'),
                'current_workload' => $tek->getCurrentWorkload(),
            ];
        })->toArray();
    }

    /**
     * Get team performance metrics
     */
    private function getTeamPerformanceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $teknisi = Teknisi::where('status', Teknisi::STATUS_ACTIVE)->get();

        return [
            'total_teknisi' => $teknisi->count(),
            'avg_resolution_rate' => $teknisi->avg(function ($tek) {
                return $tek->getResolutionRate();
            }),
            'avg_rating' => $teknisi->whereNotNull('rating')->avg('rating'),
            'total_tickets_handled' => $teknisi->sum('ticket_count'),
            'workload_distribution' => Teknisi::getWorkloadDistribution(),
        ];
    }

    /**
     * Get workload analysis
     */
    private function getWorkloadAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $teknisi = Teknisi::where('status', Teknisi::STATUS_ACTIVE)->get();

        return [
            'current_distribution' => $teknisi->map(function ($tek) {
                return [
                    'name' => $tek->name,
                    'current_workload' => $tek->getCurrentWorkload(),
                    'max_capacity' => $tek->max_concurrent_tickets ?? Teknisi::WORKLOAD_MODERATE,
                    'utilization_percentage' => $tek->getWorkloadPercentage(),
                ];
            }),
            'overloaded_teknisi' => $teknisi->filter->isBusy()->count(),
            'available_capacity' => $teknisi->sum->getAvailableCapacity(),
        ];
    }

    /**
     * Get skill utilization metrics
     */
    private function getSkillUtilizationMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $teknisi = Teknisi::with('expertApplications', 'expertCategories')->get();

        return [
            'skill_coverage' => $teknisi->flatMap->expertApplications->pluck('id')->unique()->count(),
            'category_coverage' => $teknisi->flatMap->expertCategories->pluck('id')->unique()->count(),
            'certification_count' => $teknisi->sum(function ($tek) {
                return count($tek->certifications ?? []);
            }),
        ];
    }

    /**
     * Get application health overview
     */
    private function getApplicationHealthOverview(Carbon $startDate, Carbon $endDate): array
    {
        $applications = Aplikasi::where('status', Aplikasi::STATUS_ACTIVE)->get();

        return [
            'total_applications' => $applications->count(),
            'healthy_applications' => $applications->whereIn('health_status', ['excellent', 'good'])->count(),
            'applications_with_issues' => $applications->whereIn('health_status', ['fair', 'poor'])->count(),
            'applications_in_maintenance' => $applications->where('is_maintenance_mode', true)->count(),
            'avg_uptime' => $applications->whereNotNull('uptime_percentage')->avg('uptime_percentage'),
        ];
    }

    /**
     * Get application performance metrics
     */
    private function getApplicationPerformanceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $applications = Aplikasi::with(['tickets' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])->get();

        return $applications->map(function ($app) use ($startDate, $endDate) {
            $tickets = $app->tickets;

            return [
                'name' => $app->name,
                'total_tickets' => $tickets->count(),
                'avg_resolution_time' => $tickets->whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes'),
                'uptime_percentage' => $app->uptime_percentage,
                'health_status' => $app->health_status,
                'current_users' => $app->current_users,
            ];
        })->toArray();
    }

    /**
     * Get application issue analysis
     */
    private function getApplicationIssueAnalysis(Carbon $startDate, Carbon $endDate): array
    {
        $applications = Aplikasi::where('status', Aplikasi::STATUS_ACTIVE)->get();

        return [
            'applications_needing_attention' => $applications->filter->needsAttention()->map(function ($app) {
                return [
                    'name' => $app->name,
                    'attention_reasons' => $app->getAttentionReasons(),
                ];
            }),
            'contract_expiring_soon' => $applications->filter(function ($app) {
                return $app->vendor_contract_expiry && $app->vendor_contract_expiry->isBetween(Carbon::now(), Carbon::now()->addDays(30));
            })->count(),
            'license_expiring_soon' => $applications->filter(function ($app) {
                return $app->license_expiry && $app->license_expiry->isBetween(Carbon::now(), Carbon::now()->addDays(30));
            })->count(),
        ];
    }

    /**
     * Get application recommendations
     */
    private function getApplicationRecommendations(Carbon $startDate, Carbon $endDate): array
    {
        $applications = Aplikasi::where('status', Aplikasi::STATUS_ACTIVE)->get();

        return $applications->flatMap->getMaintenanceRecommendations()->toArray();
    }

    /**
     * Get system health metrics
     */
    private function getSystemHealthMetrics(): array
    {
        return [
            'total_users' => User::where('status', 'active')->count(),
            'active_tickets' => Ticket::whereIn('status', ['open', 'in_progress'])->count(),
            'system_load' => $this->calculateSystemLoad(),
            'database_performance' => $this->getDatabasePerformanceMetrics(),
        ];
    }

    /**
     * Get resource utilization metrics
     */
    private function getResourceUtilizationMetrics(): array
    {
        return [
            'storage_usage' => $this->getStorageUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_usage' => $this->getCpuUsage(),
        ];
    }

    /**
     * Get security overview metrics
     */
    private function getSecurityOverviewMetrics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'failed_login_attempts' => $this->getFailedLoginAttempts($startDate, $endDate),
            'security_violations' => $this->getSecurityViolations($startDate, $endDate),
            'password_changes' => $this->getPasswordChanges($startDate, $endDate),
        ];
    }

    /**
     * Get capacity planning metrics
     */
    private function getCapacityPlanningMetrics(): array
    {
        return [
            'user_growth_rate' => $this->calculateUserGrowthRate(),
            'ticket_volume_trend' => $this->calculateTicketVolumeTrend(),
            'resource_projections' => $this->getResourceProjections(),
        ];
    }

    /**
     * Get policy compliance metrics
     */
    private function getPolicyComplianceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'sla_compliance' => $this->getSlaComplianceMetrics($startDate, $endDate),
            'response_time_compliance' => $this->getResponseTimeCompliance($startDate, $endDate),
            'resolution_time_compliance' => $this->getResolutionTimeCompliance($startDate, $endDate),
        ];
    }

    /**
     * Get security compliance metrics
     */
    private function getSecurityComplianceMetrics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'password_policy_compliance' => $this->getPasswordPolicyCompliance(),
            'access_control_compliance' => $this->getAccessControlCompliance(),
            'audit_compliance' => $this->getAuditCompliance($startDate, $endDate),
        ];
    }

    /**
     * Get audit trail metrics
     */
    private function getAuditTrailMetrics(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total_audit_events' => TicketHistory::whereBetween('created_at', [$startDate, $endDate])->count(),
            'events_by_type' => TicketHistory::whereBetween('created_at', [$startDate, $endDate])
                                           ->groupBy('action_type')
                                           ->selectRaw('action_type, COUNT(*) as count')
                                           ->pluck('count', 'action_type'),
        ];
    }

    /**
     * Get compliance violations
     */
    private function getComplianceViolations(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'sla_violations' => $this->getSlaBreachAnalysis($startDate, $endDate),
            'policy_violations' => $this->getPolicyViolations($startDate, $endDate),
            'security_violations' => $this->getSecurityViolations($startDate, $endDate),
        ];
    }

    /**
     * Get trending categories
     */
    private function getTrendingCategories(Carbon $startDate, Carbon $endDate): array
    {
        $categories = KategoriMasalah::withCount(['tickets' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])->get();

        return $categories->sortByDesc('tickets_count')->take(5)->map(function ($cat) {
            return [
                'name' => $cat->name,
                'ticket_count' => $cat->tickets_count,
            ];
        })->toArray();
    }

    // ==================== HELPER METHODS ====================

    /**
     * Calculate SLA compliance rate for tickets
     */
    private function calculateSlaComplianceRate($tickets): float
    {
        if ($tickets->isEmpty()) {
            return 0.0;
        }

        $compliant = 0;
        foreach ($tickets as $ticket) {
            if ($ticket->status === Ticket::STATUS_RESOLVED || $ticket->status === Ticket::STATUS_CLOSED) {
                $slaHours = Ticket::PRIORITY_SLA_HOURS[$ticket->priority] ?? 72;
                $slaDeadline = $ticket->created_at->copy()->addHours($slaHours);
                if ($ticket->resolved_at && $ticket->resolved_at->lte($slaDeadline)) {
                    $compliant++;
                }
            }
        }

        return round(($compliant / $tickets->count()) * 100, 2);
    }

    /**
     * Calculate resolution rate
     */
    private function calculateResolutionRate(Carbon $startDate, Carbon $endDate): float
    {
        $totalTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])->count();
        $resolvedTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
                                ->where('status', Ticket::STATUS_RESOLVED)
                                ->count();

        return $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 2) : 0.0;
    }

    /**
     * Calculate trend direction
     */
    private function calculateTrend($data): string
    {
        if (count($data) < 2) {
            return 'stable';
        }

        $firstHalf = array_slice($data, 0, intval(count($data) / 2));
        $secondHalf = array_slice($data, intval(count($data) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        if ($secondAvg > $firstAvg * 1.1) {
            return 'increasing';
        } elseif ($secondAvg < $firstAvg * 0.9) {
            return 'decreasing';
        }

        return 'stable';
    }

    /**
     * Calculate system uptime
     */
    private function calculateSystemUptime(): float
    {
        // This would typically integrate with monitoring systems
        // For now, return a placeholder
        return 99.5;
    }

    /**
     * Calculate average response time
     */
    private function calculateAvgResponseTime(Carbon $startDate, Carbon $endDate): ?float
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
                        ->whereNotNull('first_response_at')
                        ->get();

        if ($tickets->isEmpty()) {
            return null;
        }

        $totalMinutes = $tickets->sum(function ($ticket) {
            return $ticket->created_at->diffInMinutes($ticket->first_response_at);
        });

        return round($totalMinutes / $tickets->count(), 2);
    }

    /**
     * Calculate error rate
     */
    private function calculateErrorRate(Carbon $startDate, Carbon $endDate): float
    {
        // This would typically integrate with error logging systems
        // For now, return a placeholder
        return 0.01;
    }

    /**
     * Calculate system load
     */
    private function calculateSystemLoad(): float
    {
        // This would typically integrate with system monitoring
        // For now, return a placeholder
        return 45.0;
    }

    /**
     * Get database performance metrics
     */
    private function getDatabasePerformanceMetrics(): array
    {
        return [
            'connection_count' => DB::select('SELECT COUNT(*) as count FROM information_schema.processlist')[0]->count ?? 0,
            'query_performance' => 'good', // Placeholder
        ];
    }

    /**
     * Get storage usage
     */
    private function getStorageUsage(): array
    {
        return [
            'total_space' => '100GB', // Placeholder
            'used_space' => '45GB',   // Placeholder
            'free_space' => '55GB',   // Placeholder
        ];
    }

    /**
     * Get memory usage
     */
    private function getMemoryUsage(): array
    {
        return [
            'total_memory' => '8GB',  // Placeholder
            'used_memory' => '3.2GB', // Placeholder
            'free_memory' => '4.8GB', // Placeholder
        ];
    }

    /**
     * Get CPU usage
     */
    private function getCpuUsage(): array
    {
        return [
            'cpu_count' => 4,         // Placeholder
            'avg_load' => 1.2,        // Placeholder
        ];
    }

    /**
     * Get failed login attempts
     */
    private function getFailedLoginAttempts(Carbon $startDate, Carbon $endDate): int
    {
        // This would query authentication logs
        return 0; // Placeholder
    }

    /**
     * Get security violations
     */
    private function getSecurityViolations(Carbon $startDate, Carbon $endDate): int
    {
        // This would query security logs
        return 0; // Placeholder
    }

    /**
     * Get password changes
     */
    private function getPasswordChanges(Carbon $startDate, Carbon $endDate): int
    {
        // This would query user activity logs
        return 0; // Placeholder
    }

    /**
     * Calculate user growth rate
     */
    private function calculateUserGrowthRate(): float
    {
        // Calculate based on user registration trends
        return 5.2; // Placeholder
    }

    /**
     * Calculate ticket volume trend
     */
    private function calculateTicketVolumeTrend(): string
    {
        // Calculate trend based on historical data
        return 'increasing'; // Placeholder
    }

    /**
     * Get resource projections
     */
    private function getResourceProjections(): array
    {
        return [
            'projected_user_growth' => 10, // Placeholder
            'projected_ticket_increase' => 15, // Placeholder
        ];
    }

    /**
     * Get response time compliance
     */
    private function getResponseTimeCompliance(Carbon $startDate, Carbon $endDate): array
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
                        ->whereNotNull('first_response_at')
                        ->get();

        return [
            'avg_first_response_time' => $this->calculateAvgResponseTime($startDate, $endDate),
            'within_24h' => $tickets->filter(function ($ticket) {
                return $ticket->created_at->diffInHours($ticket->first_response_at) <= 24;
            })->count(),
        ];
    }

    /**
     * Get resolution time compliance
     */
    private function getResolutionTimeCompliance(Carbon $startDate, Carbon $endDate): array
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
                        ->where('status', Ticket::STATUS_RESOLVED)
                        ->get();

        return [
            'avg_resolution_time' => $tickets->whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes'),
            'within_sla' => $tickets->filter(function ($ticket) {
                $slaHours = Ticket::PRIORITY_SLA_HOURS[$ticket->priority] ?? 72;
                $slaDeadline = $ticket->created_at->copy()->addHours($slaHours);
                return $ticket->resolved_at && $ticket->resolved_at->lte($slaDeadline);
            })->count(),
        ];
    }

    /**
     * Get password policy compliance
     */
    private function getPasswordPolicyCompliance(): array
    {
        return [
            'strong_passwords' => 95, // Placeholder percentage
            'regular_changes' => 80,  // Placeholder percentage
        ];
    }

    /**
     * Get access control compliance
     */
    private function getAccessControlCompliance(): array
    {
        return [
            'proper_role_assignment' => 98, // Placeholder percentage
            'least_privilege' => 85,        // Placeholder percentage
        ];
    }

    /**
     * Get audit compliance
     */
    private function getAuditCompliance(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'audit_trail_completeness' => 99, // Placeholder percentage
            'regular_audits' => 90,           // Placeholder percentage
        ];
    }

    /**
     * Get policy violations
     */
    private function getPolicyViolations(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'unauthorized_access' => 0, // Placeholder
            'policy_breaches' => 2,     // Placeholder
        ];
    }

    /**
     * Get SLA compliance by priority
     */
    private function getSlaComplianceByPriority($tickets): array
    {
        $byPriority = [];

        foreach (Ticket::PRIORITY_SLA_HOURS as $priority => $slaHours) {
            $priorityTickets = $tickets->where('priority', $priority);
            $compliant = 0;

            foreach ($priorityTickets as $ticket) {
                if ($ticket->status === Ticket::STATUS_RESOLVED || $ticket->status === Ticket::STATUS_CLOSED) {
                    $slaDeadline = $ticket->created_at->copy()->addHours($slaHours);
                    if ($ticket->resolved_at && $ticket->resolved_at->lte($slaDeadline)) {
                        $compliant++;
                    }
                }
            }

            $byPriority[$priority] = [
                'total' => $priorityTickets->count(),
                'compliant' => $compliant,
                'rate' => $priorityTickets->count() > 0 ? round(($compliant / $priorityTickets->count()) * 100, 2) : 0,
            ];
        }

        return $byPriority;
    }
}