<?php

namespace App\Http\Controllers\AdminHelpdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\Ticket;
use App\Models\User;
use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Display the analytics dashboard for admin users.
     */
    public function index(Request $request)
    {
        // Get period parameter (in days)
        $period = $request->get('period', 30);
        $trendPeriod = $request->get('trend_period', 'monthly');
        $performanceMetric = $request->get('performance_metric', 'tickets_resolved');
        
        $startDate = Carbon::now()->subDays($period)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Get overview analytics
        $overview = $this->getOverviewAnalytics($startDate, $endDate);
        $ticketAnalytics = $this->getTicketAnalytics($startDate, $endDate);
        $trendAnalytics = $this->getTrendAnalytics($startDate, $endDate, $trendPeriod);
        $teknisiAnalytics = $this->getTeknisiAnalytics($startDate, $endDate);
        $applicationAnalytics = $this->getApplicationAnalytics($startDate, $endDate);
        $performanceAnalytics = $this->getPerformanceAnalytics($startDate, $endDate);

        // Format data for frontend (match the structure expected by Analytics.vue)
        $analytics = [
            // KPI Cards
            'total_tickets' => $overview['total_tickets'],
            'resolution_rate' => $overview['resolution_rate'],
            'avg_response_time' => round($performanceAnalytics['system_performance']['avg_first_response_time'] / 60 ?? 0, 1),
            'sla_compliance' => $ticketAnalytics['sla_performance']['within_sla_percentage'] ?? 0,
            'tickets_trend' => 0, // Calculate trend
            'resolution_trend' => 0,
            'response_time_trend' => 0,
            'sla_trend' => 0,

            // Ticket Trends - Format for Line Chart
            'ticket_trends' => [
                'labels' => collect($trendAnalytics['daily_trends'])->pluck('date')->map(function($date) {
                    return Carbon::parse($date)->format('M d');
                })->toArray(),
                'datasets' => [
                    [
                        'label' => 'Created',
                        'data' => collect($trendAnalytics['daily_trends'])->pluck('tickets_created')->toArray(),
                        'borderColor' => '#3b82f6',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'fill' => true,
                    ],
                    [
                        'label' => 'Resolved',
                        'data' => collect($trendAnalytics['daily_trends'])->pluck('tickets_resolved')->toArray(),
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => true,
                    ]
                ]
            ],

            // Status Distribution - Format for Pie Chart
            'status_distribution' => [
                'labels' => array_map('ucfirst', array_keys($trendAnalytics['status_distribution'])),
                'datasets' => [
                    [
                        'data' => array_values($trendAnalytics['status_distribution']),
                        'backgroundColor' => [
                            '#fbbf24', // open
                            '#60a5fa', // assigned
                            '#818cf8', // in_progress
                            '#fb923c', // waiting_response
                            '#34d399', // resolved
                            '#9ca3af', // closed
                        ],
                    ]
                ]
            ],

            // Status Breakdown (for legend)
            'status_breakdown' => $this->formatStatusBreakdown($trendAnalytics['status_distribution']),

            // Priority Analysis - Format for Bar Chart
            'priority_analysis' => [
                'labels' => array_map('ucfirst', array_keys($trendAnalytics['priority_distribution'])),
                'datasets' => [
                    [
                        'label' => 'Tickets',
                        'data' => array_values($trendAnalytics['priority_distribution']),
                        'backgroundColor' => [
                            '#10b981', // low
                            '#3b82f6', // medium
                            '#f59e0b', // high
                            '#ef4444', // urgent
                        ],
                    ]
                ]
            ],

            // Application Usage
            'application_usage' => collect($trendAnalytics['application_breakdown'])->map(function($app, $index) use ($trendAnalytics) {
                $total = collect($trendAnalytics['application_breakdown'])->sum('count');
                return [
                    'id' => $index,
                    'name' => $app['name'] ?? 'Unknown',
                    'ticket_count' => $app['count'] ?? 0,
                    'percentage' => $total > 0 ? round(($app['count'] / $total) * 100, 1) : 0,
                ];
            })->values()->toArray(),

            // Teknisi Performance - Format for Bar Chart (dynamic based on metric)
            'teknisi_performance' => $this->formatTeknisiPerformance($teknisiAnalytics['top_performers'], $performanceMetric),

            // SLA Metrics
            'sla_compliance' => $ticketAnalytics['sla_performance']['within_sla_percentage'] ?? 0,
            'sla_on_time' => $ticketAnalytics['sla_performance']['within_sla'] ?? 0,
            'sla_breached' => $ticketAnalytics['sla_performance']['sla_breached'] ?? 0,
            'sla_at_risk' => 0,

            // Response Times
            'first_response_time' => round($performanceAnalytics['system_performance']['avg_first_response_time'] / 60 ?? 0, 1),
            'resolution_time_avg' => round($ticketAnalytics['resolution_times']['average'] / 60 ?? 0, 1),
            'handle_time_avg' => round($ticketAnalytics['resolution_times']['average'] / 60 ?? 0, 1),

            // Satisfaction Metrics
            'avg_satisfaction' => $performanceAnalytics['system_performance']['customer_satisfaction'] ?? 0,
            'satisfaction_breakdown' => $this->formatSatisfactionBreakdown($ticketAnalytics['satisfaction_ratings'] ?? []),
        ];

        // Real-time metrics
        $realTimeMetrics = [
            'active_tickets' => Ticket::whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
            'queue_length' => Ticket::where('status', 'open')->count(),
            'available_teknisi' => Teknisi::active()->count(),
            'avg_wait_time' => 15, // Calculate from open tickets
        ];

        return Inertia::render('AdminHelpdesk/Analytics', [
            'analytics' => $analytics,
            'realTimeMetrics' => $realTimeMetrics,
        ]);
    }

    /**
     * Export analytics data.
     */
    public function export(Request $request)
    {
        // Check authentication
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_helpdesk') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get period parameter
        $period = $request->get('period', 30);
        $startDate = Carbon::now()->subDays($period)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Get analytics data
        $overview = $this->getOverviewAnalytics($startDate, $endDate);
        $ticketAnalytics = $this->getTicketAnalytics($startDate, $endDate);
        $trendAnalytics = $this->getTrendAnalytics($startDate, $endDate);

        $filename = 'analytics_export_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $callback = function () use ($overview, $ticketAnalytics, $trendAnalytics, $startDate, $endDate) {
            if (ob_get_level()) {
                ob_end_clean();
            }

            $file = fopen('php://output', 'w');

            // Overview section
            fputcsv($file, ['Analytics Report']);
            fputcsv($file, ['Period', $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d')]);
            fputcsv($file, ['Generated', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []);

            // KPIs
            fputcsv($file, ['Key Performance Indicators']);
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Tickets', $overview['total_tickets']]);
            fputcsv($file, ['Resolved Tickets', $overview['resolved_tickets']]);
            fputcsv($file, ['Open Tickets', $overview['open_tickets']]);
            fputcsv($file, ['In Progress Tickets', $overview['in_progress_tickets']]);
            fputcsv($file, ['Resolution Rate (%)', $overview['resolution_rate']]);
            fputcsv($file, []);

            // Status Distribution
            fputcsv($file, ['Status Distribution']);
            fputcsv($file, ['Status', 'Count']);
            foreach ($trendAnalytics['status_distribution'] as $status => $count) {
                fputcsv($file, [ucfirst($status), $count]);
            }
            fputcsv($file, []);

            // Priority Distribution
            fputcsv($file, ['Priority Distribution']);
            fputcsv($file, ['Priority', 'Count']);
            foreach ($trendAnalytics['priority_distribution'] as $priority => $count) {
                fputcsv($file, [ucfirst($priority), $count]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get overview analytics.
     */
    private function getOverviewAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $totalTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])->count();
        $resolvedTickets = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])->count();
        $openTickets = Ticket::where('status', Ticket::STATUS_OPEN)
            ->whereBetween('created_at', [$startDate, $endDate])->count();
        $inProgressTickets = Ticket::where('status', Ticket::STATUS_IN_PROGRESS)
            ->whereBetween('created_at', [$startDate, $endDate])->count();
        $resolvedToday = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereDate('resolved_at', Carbon::today())->count();

        return [
            'total_tickets' => $totalTickets,
            'resolved_tickets' => $resolvedTickets,
            'open_tickets' => $openTickets,
            'in_progress_tickets' => $inProgressTickets,
            'resolved_today' => $resolvedToday,
            'resolution_rate' => $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 1) : 0,
            'total_users' => User::count(),
            'active_users' => User::active()->count(),
            'total_teknisi' => Teknisi::count(),
            'active_teknisi' => Teknisi::active()->count(),
            'total_applications' => Aplikasi::count(),
            'active_applications' => Aplikasi::active()->count(),
            'total_categories' => KategoriMasalah::count(),
            'active_categories' => KategoriMasalah::active()->count(),
        ];
    }

    /**
     * Get ticket analytics.
     */
    private function getTicketAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->with(['aplikasi', 'kategoriMasalah', 'user', 'assignedTeknisi'])
            ->get();

        $statusQuery = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $priorityQuery = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get();

        // Get satisfaction ratings
        $satisfactionQuery = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->whereNotNull('user_rating')
            ->select('user_rating', DB::raw('count(*) as count'))
            ->groupBy('user_rating')
            ->get();

        // Calculate SLA performance
        $totalResolved = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->count();

        $withinSla = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->whereNotNull('resolution_time_minutes')
            ->where('resolution_time_minutes', '<=', 480) // 8 hours SLA
            ->count();

        $slaBreach = $totalResolved - $withinSla;

        return [
            'by_status' => $statusQuery->isNotEmpty() ? $statusQuery->pluck('count', 'status')->toArray() : [],
            'by_priority' => $priorityQuery->isNotEmpty() ? $priorityQuery->pluck('count', 'priority')->toArray() : [],
            'by_application' => $tickets->isNotEmpty() ? $tickets->groupBy('aplikasi.name')->map->count()->toArray() : [],
            'by_category' => $tickets->isNotEmpty() ? $tickets->groupBy('kategoriMasalah.name')->map->count()->toArray() : [],
            'resolution_times' => [
                'average' => $tickets->where('status', Ticket::STATUS_RESOLVED)
                    ->whereNotNull('resolution_time_minutes')
                    ->avg('resolution_time_minutes') ?? 0,
                'median' => $this->calculateMedianResolutionTime($startDate, $endDate),
                'fastest' => $tickets->where('status', Ticket::STATUS_RESOLVED)
                    ->whereNotNull('resolution_time_minutes')
                    ->min('resolution_time_minutes') ?? 0,
                'slowest' => $tickets->where('status', Ticket::STATUS_RESOLVED)
                    ->whereNotNull('resolution_time_minutes')
                    ->max('resolution_time_minutes') ?? 0,
            ],
            'sla_performance' => [
                'within_sla' => $withinSla,
                'sla_breached' => $slaBreach,
                'within_sla_percentage' => $totalResolved > 0 ? round(($withinSla / $totalResolved) * 100, 1) : 0,
            ],
            'satisfaction_ratings' => $satisfactionQuery->isNotEmpty() ? $satisfactionQuery->pluck('count', 'user_rating')->toArray() : [],
        ];
    }

    /**
     * Get user analytics.
     */
    private function getUserAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $departmentQuery = User::select('department', DB::raw('count(*) as count'))
            ->whereNotNull('department')
            ->groupBy('department')
            ->orderBy('count', 'desc')
            ->get();

        $satisfactionQuery = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->whereNotNull('user_rating')
            ->select('user_rating', DB::raw('count(*) as count'))
            ->groupBy('user_rating')
            ->get();

        return [
            'by_department' => $departmentQuery->isNotEmpty() ? $departmentQuery->mapWithKeys(function ($item) {
                return [$item->department => $item->count];
            })->toArray() : [],
            'activity_levels' => [
                'highly_active' => User::whereHas('tickets', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }, '>=', 5)->count(),
                'moderately_active' => User::whereHas('tickets', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }, '=', 2)->count(),
                'low_activity' => User::whereHas('tickets', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }, '=', 1)->count(),
            ],
            'new_users' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
            'satisfaction_ratings' => $satisfactionQuery->isNotEmpty() ? $satisfactionQuery->pluck('count', 'user_rating')->toArray() : [],
        ];
    }

    /**
     * Get teknisi analytics.
     */
    private function getTeknisiAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $teknisi = Teknisi::active()->get();

        $topPerformers = Teknisi::topRated(5)->get();

        return [
            'by_department' => $teknisi->isNotEmpty() ? $teknisi->groupBy('department')->mapWithKeys(function ($group, $department) {
                return [$department => $group->count()];
            })->toArray() : [],
            'by_skill_level' => $teknisi->isNotEmpty() ? $teknisi->groupBy('skill_level')->mapWithKeys(function ($group, $skill_level) {
                return [$skill_level => $group->count()];
            })->toArray() : [],
            'performance_distribution' => [
                'excellent' => $teknisi->where('rating', '>=', 4.5)->count(),
                'good' => $teknisi->whereBetween('rating', [3.5, 4.4])->count(),
                'average' => $teknisi->whereBetween('rating', [2.5, 3.4])->count(),
                'needs_improvement' => $teknisi->where('rating', '<', 2.5)->count(),
            ],
            'workload_distribution' => Teknisi::getWorkloadDistribution(),
            'top_performers' => $topPerformers->isNotEmpty() ? $topPerformers->map(function ($tek) use ($startDate, $endDate) {
                $resolvedTickets = $tek->resolvedTickets()
                    ->whereBetween('resolved_at', [$startDate, $endDate])
                    ->get();

                $avgResolutionTime = $resolvedTickets->whereNotNull('resolution_time_minutes')
                    ->avg('resolution_time_minutes');

                $totalAssigned = $tek->assignedTickets()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                $slaCompliant = $resolvedTickets->where('resolution_time_minutes', '<=', 480)->count();

                return [
                    'nip' => $tek->nip,
                    'name' => $tek->name,
                    'rating' => $tek->rating ?? 0,
                    'tickets_resolved' => $resolvedTickets->count(),
                    'avg_resolution_time' => $avgResolutionTime ? round($avgResolutionTime / 60, 1) : 0,
                    'satisfaction_rate' => $tek->rating ? round($tek->rating * 20, 1) : 0, // Convert 5-star to percentage
                    'sla_compliance' => $totalAssigned > 0 ? round(($slaCompliant / $totalAssigned) * 100, 1) : 0,
                ];
            })->toArray() : [],
        ];
    }

    /**
     * Get application analytics.
     */
    private function getApplicationAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $applications = Aplikasi::active()->get();

        return [
            'ticket_distribution' => $applications->map(function ($app) use ($startDate, $endDate) {
                $ticketCount = $app->tickets()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                return [
                    'name' => $app->name,
                    'code' => $app->code,
                    'ticket_count' => $ticketCount,
                ];
            })->sortByDesc('ticket_count')->take(10)->values()->toArray(),
            'resolution_performance' => $applications->map(function ($app) use ($startDate, $endDate) {
                $tickets = $app->tickets()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();

                $resolvedTickets = $tickets->where('status', Ticket::STATUS_RESOLVED);

                return [
                    'name' => $app->name,
                    'total_tickets' => $tickets->count(),
                    'resolved_tickets' => $resolvedTickets->count(),
                    'resolution_rate' => $tickets->count() > 0 ? round(($resolvedTickets->count() / $tickets->count()) * 100, 1) : 0,
                    'avg_resolution_time' => $resolvedTickets->whereNotNull('resolution_time_minutes')->avg('resolution_time_minutes'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Get performance analytics.
     */
    private function getPerformanceAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        // Get top users by ticket count
        $topUsers = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->select('user_nip', DB::raw('count(*) as ticket_count'))
            ->groupBy('user_nip')
            ->with('user:name,email') // Using the relationship via NIP
            ->orderBy('ticket_count', 'desc')
            ->limit(10)
            ->get();

        $topUsersFormatted = [];
        foreach($topUsers as $userTicket) {
            $userName = $userTicket->user ? $userTicket->user->name : 'NIP: ' . $userTicket->user_nip;
            $topUsersFormatted[$userName] = $userTicket->ticket_count;
        }

        // Get top teknisi performance
        $topTeknisi = Teknisi::with(['assignedTickets' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function($teknisi) {
                $assignedCount = $teknisi->assignedTickets->count();
                $resolvedCount = $teknisi->assignedTickets->where('status', Ticket::STATUS_RESOLVED)->count();
                $resolutionRate = $assignedCount > 0 ? ($resolvedCount / $assignedCount) * 100 : 0;

                return [
                    'name' => $teknisi->name,
                    'total_assigned' => $assignedCount,
                    'resolved' => $resolvedCount,
                    'resolution_rate' => $resolutionRate,
                ];
            })
            ->sortByDesc('resolved')
            ->take(10)
            ->values()
            ->toArray();

        return [
            'top_users' => $topUsersFormatted,
            'top_teknisi' => $topTeknisi,
            'system_performance' => [
                'avg_first_response_time' => $this->calculateAverageFirstResponseTime($startDate, $endDate),
                'avg_resolution_time' => $this->calculateAverageResolutionTime($startDate, $endDate),
                'customer_satisfaction' => $this->calculateAverageCustomerSatisfaction($startDate, $endDate),
                'reopen_rate' => $this->calculateReopenRate($startDate, $endDate),
            ],
            'efficiency_metrics' => [
                'tickets_per_teknisi' => $this->calculateTicketsPerTeknisi($startDate, $endDate),
                'resolution_efficiency' => $this->calculateResolutionEfficiency($startDate, $endDate),
                'resource_utilization' => $this->calculateResourceUtilization($startDate, $endDate),
            ],
        ];
    }

    /**
     * Get trend analytics.
     */
    private function getTrendAnalytics(Carbon $startDate, Carbon $endDate, string $period = 'monthly'): array
    {
        $trends = [];
        
        // Determine grouping based on period
        switch ($period) {
            case 'daily':
                $trends = $this->getDailyTrends($startDate, $endDate);
                break;
            case 'weekly':
                $trends = $this->getWeeklyTrends($startDate, $endDate);
                break;
            case 'monthly':
            default:
                $trends = $this->getMonthlyTrends($startDate, $endDate);
                break;
        }

        // Get status distribution
        $statusQuery = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();
        $statusDistribution = $statusQuery->isNotEmpty() ? $statusQuery->pluck('count', 'status')->toArray() : [];

        // Get priority distribution
        $priorityQuery = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get();
        $priorityDistribution = $priorityQuery->isNotEmpty() ? $priorityQuery->pluck('count', 'priority')->toArray() : [];

        // Get application breakdown
        $appTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->with('aplikasi')
            ->get();
        
        // Create array of objects with application name and count for frontend
        $applicationBreakdown = $appTickets->groupBy('aplikasi.name')
            ->map(function ($group, $appName) {
                return [
                    'name' => $appName,
                    'count' => $group->count()
                ];
            })
            ->sortByDesc('count')
            ->take(5)
            ->values() // Re-index the collection to ensure it's a numerically indexed array
            ->toArray();

        return [
            'daily_trends' => $trends,
            'status_distribution' => $statusDistribution,
            'priority_distribution' => $priorityDistribution,
            'application_breakdown' => $applicationBreakdown,
            'growth_metrics' => $this->calculateGrowthMetrics($startDate, $endDate),
            'seasonal_patterns' => $this->analyzeSeasonalPatterns($startDate, $endDate),
        ];
    }

    /**
     * Calculate median resolution time.
     */
    private function calculateMedianResolutionTime(Carbon $startDate, Carbon $endDate): ?float
    {
        $resolutionTimes = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->whereNotNull('resolution_time_minutes')
            ->pluck('resolution_time_minutes')
            ->sort()
            ->values();

        $count = $resolutionTimes->count();

        if ($count === 0) {
            return null;
        }

        $middle = $count / 2;

        if (is_int($middle)) {
            return $resolutionTimes->get($middle - 1);
        } else {
            return ($resolutionTimes->get(floor($middle) - 1) + $resolutionTimes->get(ceil($middle) - 1)) / 2;
        }
    }

    /**
     * Calculate average first response time.
     */
    private function calculateAverageFirstResponseTime(Carbon $startDate, Carbon $endDate): ?float
    {
        $tickets = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('first_response_at')
            ->get();

        if ($tickets->isEmpty()) {
            return null;
        }

        $totalMinutes = $tickets->sum(function ($ticket) {
            return Carbon::parse($ticket->created_at)->diffInMinutes($ticket->first_response_at);
        });

        return round($totalMinutes / $tickets->count(), 2);
    }

    /**
     * Calculate average resolution time.
     */
    private function calculateAverageResolutionTime(Carbon $startDate, Carbon $endDate): ?float
    {
        $avgMinutes = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->whereNotNull('resolution_time_minutes')
            ->avg('resolution_time_minutes');

        return $avgMinutes ? round($avgMinutes, 2) : null;
    }

    /**
     * Calculate average customer satisfaction.
     */
    private function calculateAverageCustomerSatisfaction(Carbon $startDate, Carbon $endDate): ?float
    {
        $avgRating = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->whereNotNull('user_rating')
            ->avg('user_rating');

        return $avgRating ? round($avgRating, 2) : null;
    }

    /**
     * Calculate reopen rate.
     */
    private function calculateReopenRate(Carbon $startDate, Carbon $endDate): float
    {
        $resolvedTickets = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->count();

        $reopenedTickets = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->whereHas('history', function ($query) {
                $query->where('action', 'reopened');
            })
            ->count();

        return $resolvedTickets > 0 ? round(($reopenedTickets / $resolvedTickets) * 100, 2) : 0;
    }

    /**
     * Calculate tickets per teknisi.
     */
    private function calculateTicketsPerTeknisi(Carbon $startDate, Carbon $endDate): array
    {
        $teknisi = Teknisi::active()->get();

        return $teknisi->map(function ($tek) use ($startDate, $endDate) {
            $ticketCount = $tek->assignedTickets()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            return [
                'nip' => $tek->nip,
                'name' => $tek->name,
                'ticket_count' => $ticketCount,
            ];
        })->toArray();
    }

    /**
     * Calculate resolution efficiency.
     */
    private function calculateResolutionEfficiency(Carbon $startDate, Carbon $endDate): array
    {
        $totalTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])->count();
        $resolvedTickets = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])->count();

        return [
            'total_tickets' => $totalTickets,
            'resolved_tickets' => $resolvedTickets,
            'efficiency_rate' => $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 1) : 0,
        ];
    }

    /**
     * Calculate resource utilization.
     */
    private function calculateResourceUtilization(Carbon $startDate, Carbon $endDate): array
    {
        $teknisi = Teknisi::active()->get();
        $totalCapacity = $teknisi->sum('max_concurrent_tickets');
        $currentWorkload = $teknisi->sum(function ($tek) {
            return $tek->getCurrentWorkload();
        });

        return [
            'total_capacity' => $totalCapacity,
            'current_workload' => $currentWorkload,
            'utilization_rate' => $totalCapacity > 0 ? round(($currentWorkload / $totalCapacity) * 100, 1) : 0,
        ];
    }

    /**
     * Calculate growth metrics.
     */
    private function calculateGrowthMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $currentPeriodTickets = Ticket::whereBetween('created_at', [$startDate, $endDate])->count();
        $previousPeriodStart = $startDate->copy()->subDays($endDate->diffInDays($startDate));
        $previousPeriodTickets = Ticket::whereBetween('created_at', [$previousPeriodStart, $startDate])->count();

        $ticketGrowth = $previousPeriodTickets > 0 ?
            round((($currentPeriodTickets - $previousPeriodTickets) / $previousPeriodTickets) * 100, 1) : 0;

        return [
            'ticket_growth' => $ticketGrowth,
            'current_period' => $currentPeriodTickets,
            'previous_period' => $previousPeriodTickets,
        ];
    }

    /**
     * Analyze seasonal patterns.
     */
    private function analyzeSeasonalPatterns(Carbon $startDate, Carbon $endDate): array
    {
        // This is a simplified analysis - in practice, you'd use more sophisticated algorithms
        $patterns = [];

        // Analyze by day of week
        $ticketsByDay = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DAYOFWEEK(created_at) as day'), DB::raw('count(*) as count'))
            ->groupBy('day')
            ->get()
            ->pluck('count', 'day')
            ->toArray();

        $patterns['day_of_week'] = $ticketsByDay;

        // Analyze by hour (if you have hourly data)
        $ticketsByHour = Ticket::whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        $patterns['hour_of_day'] = $ticketsByHour;

        return $patterns;
    }

    /**
     * Format status breakdown for frontend display.
     */
    private function formatStatusBreakdown(array $statusDistribution): array
    {
        $total = array_sum($statusDistribution);
        $statusLabels = [
            'open' => 'Open',
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'waiting_response' => 'Waiting Response',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
        ];

        $breakdown = [];
        foreach ($statusDistribution as $status => $count) {
            $breakdown[] = [
                'status' => $status,
                'status_label' => $statusLabels[$status] ?? ucfirst($status),
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return $breakdown;
    }

    /**
     * Format satisfaction breakdown for frontend display.
     */
    private function formatSatisfactionBreakdown(array $satisfactionRatings): array
    {
        $breakdown = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = $satisfactionRatings[$i] ?? 0;
            $total = array_sum($satisfactionRatings);
            $breakdown[] = [
                'stars' => $i,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }

        return $breakdown;
    }

    /**
     * Get daily trends.
     */
    private function getDailyTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'tickets_created' => Ticket::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'tickets_resolved' => Ticket::where('status', Ticket::STATUS_RESOLVED)
                    ->whereBetween('resolved_at', [$dayStart, $dayEnd])->count(),
                'new_users' => User::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
            ];

            $currentDate->addDay();
        }

        return $trends;
    }

    /**
     * Get weekly trends.
     */
    private function getWeeklyTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $currentDate = $startDate->copy()->startOfWeek();

        while ($currentDate <= $endDate) {
            $weekStart = $currentDate->copy()->startOfWeek();
            $weekEnd = $currentDate->copy()->endOfWeek();

            // Ensure we don't go beyond the end date
            if ($weekEnd > $endDate) {
                $weekEnd = $endDate->copy();
            }

            $trends[] = [
                'date' => $weekStart->format('Y-m-d'),
                'tickets_created' => Ticket::whereBetween('created_at', [$weekStart, $weekEnd])->count(),
                'tickets_resolved' => Ticket::where('status', Ticket::STATUS_RESOLVED)
                    ->whereBetween('resolved_at', [$weekStart, $weekEnd])->count(),
                'new_users' => User::whereBetween('created_at', [$weekStart, $weekEnd])->count(),
            ];

            $currentDate->addWeek();
        }

        return $trends;
    }

    /**
     * Get monthly trends.
     */
    private function getMonthlyTrends(Carbon $startDate, Carbon $endDate): array
    {
        $trends = [];
        $currentDate = $startDate->copy()->startOfMonth();

        while ($currentDate <= $endDate) {
            $monthStart = $currentDate->copy()->startOfMonth();
            $monthEnd = $currentDate->copy()->endOfMonth();

            // Ensure we don't go beyond the end date
            if ($monthEnd > $endDate) {
                $monthEnd = $endDate->copy();
            }

            $trends[] = [
                'date' => $monthStart->format('Y-m-d'),
                'tickets_created' => Ticket::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
                'tickets_resolved' => Ticket::where('status', Ticket::STATUS_RESOLVED)
                    ->whereBetween('resolved_at', [$monthStart, $monthEnd])->count(),
                'new_users' => User::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            ];

            $currentDate->addMonth();
        }

        return $trends;
    }

    /**
     * Format teknisi performance based on selected metric.
     */
    private function formatTeknisiPerformance(array $topPerformers, string $metric): array
    {
        $labels = collect($topPerformers)->pluck('name')->toArray();
        
        $metricConfig = [
            'tickets_resolved' => [
                'label' => 'Tickets Resolved',
                'field' => 'tickets_resolved',
                'color' => '#6366f1',
            ],
            'avg_resolution_time' => [
                'label' => 'Avg Resolution Time (hours)',
                'field' => 'avg_resolution_time',
                'color' => '#f59e0b',
            ],
            'satisfaction_rate' => [
                'label' => 'Satisfaction Rate (%)',
                'field' => 'satisfaction_rate',
                'color' => '#10b981',
            ],
            'sla_compliance' => [
                'label' => 'SLA Compliance (%)',
                'field' => 'sla_compliance',
                'color' => '#8b5cf6',
            ],
        ];

        $config = $metricConfig[$metric] ?? $metricConfig['tickets_resolved'];
        
        // Get data based on metric
        $data = collect($topPerformers)->map(function($performer) use ($metric) {
            switch ($metric) {
                case 'avg_resolution_time':
                    return $performer['avg_resolution_time'] ?? 0;
                case 'satisfaction_rate':
                    return $performer['satisfaction_rate'] ?? 0;
                case 'sla_compliance':
                    return $performer['sla_compliance'] ?? 0;
                case 'tickets_resolved':
                default:
                    return $performer['tickets_resolved'] ?? 0;
            }
        })->toArray();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $config['label'],
                    'data' => $data,
                    'backgroundColor' => $config['color'],
                ]
            ]
        ];
    }
}
