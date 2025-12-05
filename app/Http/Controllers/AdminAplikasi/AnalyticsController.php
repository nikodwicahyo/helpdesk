<?php

namespace App\Http\Controllers\AdminAplikasi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use App\Models\Ticket;
use App\Models\AdminAplikasi;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\Teknisi;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Display the analytics dashboard for admin aplikasi.
     */
    public function index(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            return redirect()->route('login')->withErrors(['Access denied. Invalid session.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        if (!$admin) {
            return redirect()->route('login')->withErrors(['Admin not found.']);
        }

        // Get period parameter
        $period = $request->get('period', 30);
        $selectedAppId = $request->get('aplikasi_id');
        
        $startDate = Carbon::now()->subDays($period)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Get managed application IDs
        $managedAppIds = $this->getManagedAppIds($admin);

        // Filter by selected application if provided
        $appIds = $selectedAppId && in_array($selectedAppId, $managedAppIds) 
            ? [$selectedAppId] 
            : $managedAppIds;

        // Get analytics data
        $overview = $this->getOverviewStats($appIds, $startDate, $endDate);
        $ticketTrends = $this->getTicketTrends($appIds, $startDate, $endDate);
        $statusDistribution = $this->getStatusDistribution($appIds);
        $priorityDistribution = $this->getPriorityDistribution($appIds);
        $applicationStats = $this->getApplicationStats($appIds, $startDate, $endDate);
        $categoryStats = $this->getCategoryStats($appIds, $startDate, $endDate);
        $teknisiPerformance = $this->getTeknisiPerformance($appIds, $startDate, $endDate);

        // Get managed applications for filter dropdown
        $applications = Aplikasi::whereIn('id', $managedAppIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('AdminAplikasi/Analytics', [
            'analytics' => [
                // KPI Cards
                'total_tickets' => $overview['total_tickets'],
                'resolved_tickets' => $overview['resolved_tickets'],
                'resolution_rate' => $overview['resolution_rate'],
                'avg_resolution_time' => $overview['avg_resolution_time'],
                'open_tickets' => $overview['open_tickets'],
                
                // Trends
                'tickets_trend' => $overview['tickets_trend'],
                'resolution_trend' => $overview['resolution_trend'],
                
                // Ticket Trends Chart
                'ticket_trends' => $ticketTrends,
                
                // Status Distribution Chart
                'status_distribution' => $statusDistribution,
                
                // Priority Distribution Chart
                'priority_distribution' => $priorityDistribution,
                
                // Application Performance
                'application_stats' => $applicationStats,
                
                // Category Performance
                'category_stats' => $categoryStats,
                
                // Teknisi Performance
                'teknisi_performance' => $teknisiPerformance,
            ],
            'applications' => $applications,
            'filters' => [
                'period' => $period,
                'aplikasi_id' => $selectedAppId,
            ],
            'period' => $period,
        ]);
    }

    /**
     * Export analytics data.
     */
    public function export(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        if (!$admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }

        $managedAppIds = $this->getManagedAppIds($admin);
        $selectedAppId = $request->get('aplikasi_id');

        // Filter by selected application if provided
        $appIds = $selectedAppId && in_array($selectedAppId, $managedAppIds) 
            ? [$selectedAppId] 
            : $managedAppIds;

        $period = $request->get('period', 30);
        $startDate = Carbon::now()->subDays($period)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Get overview stats
        $overview = $this->getOverviewStats($appIds, $startDate, $endDate);

        // Get applications with stats
        $applications = Aplikasi::whereIn('id', $appIds)
            ->withCount([
                'tickets',
                'tickets as period_tickets_count' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                },
                'tickets as open_tickets_count' => function ($query) {
                    $query->whereIn('status', ['open', 'assigned', 'in_progress']);
                },
                'tickets as resolved_tickets_count' => function ($query) {
                    $query->where('status', Ticket::STATUS_RESOLVED);
                },
            ])
            ->get();

        // Get category stats
        $categories = KategoriMasalah::whereIn('aplikasi_id', $appIds)
            ->with('aplikasi:id,name')
            ->withCount([
                'tickets',
                'tickets as period_tickets_count' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                },
            ])
            ->orderBy('tickets_count', 'desc')
            ->get();

        // Get teknisi performance
        $teknisiNips = Ticket::whereIn('aplikasi_id', $appIds)
            ->whereNotNull('assigned_teknisi_nip')
            ->distinct()
            ->pluck('assigned_teknisi_nip')
            ->toArray();

        $teknisis = [];
        if (!empty($teknisiNips)) {
            $teknisis = Teknisi::whereIn('nip', $teknisiNips)
                ->withCount([
                    'assignedTickets as total_tickets' => function ($query) use ($appIds) {
                        $query->whereIn('aplikasi_id', $appIds);
                    },
                    'assignedTickets as resolved_tickets' => function ($query) use ($appIds) {
                        $query->whereIn('aplikasi_id', $appIds)
                            ->where('status', Ticket::STATUS_RESOLVED);
                    },
                ])
                ->get();
        }

        $filename = 'analytics_export_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($overview, $applications, $categories, $teknisis, $startDate, $endDate, $period) {
            if (ob_get_level()) ob_end_clean();
            $file = fopen('php://output', 'w');

            // Report header
            fputcsv($file, ['Analytics Report - Admin Aplikasi']);
            fputcsv($file, ['Generated', date('d M Y H:i:s')]);
            fputcsv($file, ['Period', $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y') . ' (' . $period . ' days)']);
            fputcsv($file, []);

            // Overview Summary
            fputcsv($file, ['=== OVERVIEW SUMMARY ===']);
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Tickets (Period)', $overview['total_tickets']]);
            fputcsv($file, ['Resolved Tickets', $overview['resolved_tickets']]);
            fputcsv($file, ['Open Tickets', $overview['open_tickets']]);
            fputcsv($file, ['Resolution Rate', $overview['resolution_rate'] . '%']);
            fputcsv($file, ['Avg Resolution Time', $overview['avg_resolution_time'] . ' hours']);
            fputcsv($file, []);

            // Application Performance
            fputcsv($file, ['=== APPLICATION PERFORMANCE ===']);
            fputcsv($file, ['Application', 'Code', 'Total Tickets', 'Period Tickets', 'Open', 'Resolved', 'Resolution Rate']);

            foreach ($applications as $app) {
                $resolutionRate = $app->tickets_count > 0 
                    ? round(($app->resolved_tickets_count / $app->tickets_count) * 100, 1) 
                    : 0;

                fputcsv($file, [
                    $app->name,
                    $app->code,
                    $app->tickets_count,
                    $app->period_tickets_count,
                    $app->open_tickets_count,
                    $app->resolved_tickets_count,
                    $resolutionRate . '%',
                ]);
            }
            fputcsv($file, []);

            // Category Performance
            fputcsv($file, ['=== CATEGORY PERFORMANCE ===']);
            fputcsv($file, ['Category', 'Application', 'Total Tickets', 'Period Tickets']);

            foreach ($categories as $cat) {
                fputcsv($file, [
                    $cat->name,
                    $cat->aplikasi ? $cat->aplikasi->name : 'Unknown',
                    $cat->tickets_count,
                    $cat->period_tickets_count,
                ]);
            }
            fputcsv($file, []);

            // Teknisi Performance
            fputcsv($file, ['=== TEKNISI PERFORMANCE ===']);
            fputcsv($file, ['NIP', 'Name', 'Total Tickets', 'Resolved Tickets', 'Resolution Rate', 'Rating']);

            foreach ($teknisis as $teknisi) {
                $resRate = $teknisi->total_tickets > 0 
                    ? round(($teknisi->resolved_tickets / $teknisi->total_tickets) * 100, 1) 
                    : 0;

                fputcsv($file, [
                    $teknisi->nip,
                    $teknisi->name,
                    $teknisi->total_tickets,
                    $teknisi->resolved_tickets,
                    $resRate . '%',
                    $teknisi->rating ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get managed application IDs for this admin.
     * Always use database relationship (admin_aplikasi_nip and backup_admin_nip) as the source of truth.
     */
    private function getManagedAppIds(AdminAplikasi $admin): array
    {
        return Aplikasi::where(function($q) use ($admin) {
            $q->where('admin_aplikasi_nip', $admin->nip)
              ->orWhere('backup_admin_nip', $admin->nip);
        })->pluck('id')->toArray();
    }

    /**
     * Get overview statistics.
     */
    private function getOverviewStats(array $appIds, Carbon $startDate, Carbon $endDate): array
    {
        if (empty($appIds)) {
            return [
                'total_tickets' => 0,
                'resolved_tickets' => 0,
                'open_tickets' => 0,
                'resolution_rate' => 0,
                'avg_resolution_time' => 0,
                'tickets_trend' => 0,
                'resolution_trend' => 0,
            ];
        }

        $totalTickets = Ticket::whereIn('aplikasi_id', $appIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $resolvedTickets = Ticket::whereIn('aplikasi_id', $appIds)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->count();

        $openTickets = Ticket::whereIn('aplikasi_id', $appIds)
            ->whereIn('status', ['open', 'assigned', 'in_progress'])
            ->count();

        $resolutionRate = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 1) : 0;

        $avgResolutionMinutes = Ticket::whereIn('aplikasi_id', $appIds)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereNotNull('resolution_time_minutes')
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->avg('resolution_time_minutes') ?? 0;

        // Calculate trends (compare to previous period)
        $previousStartDate = $startDate->copy()->subDays($startDate->diffInDays($endDate));
        $previousEndDate = $startDate->copy()->subDay();

        $previousTickets = Ticket::whereIn('aplikasi_id', $appIds)
            ->whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->count();

        $previousResolved = Ticket::whereIn('aplikasi_id', $appIds)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$previousStartDate, $previousEndDate])
            ->count();

        $ticketsTrend = $previousTickets > 0 
            ? round((($totalTickets - $previousTickets) / $previousTickets) * 100, 1) 
            : ($totalTickets > 0 ? 100 : 0);

        $resolutionTrend = $previousResolved > 0 
            ? round((($resolvedTickets - $previousResolved) / $previousResolved) * 100, 1) 
            : ($resolvedTickets > 0 ? 100 : 0);

        return [
            'total_tickets' => $totalTickets,
            'resolved_tickets' => $resolvedTickets,
            'open_tickets' => $openTickets,
            'resolution_rate' => $resolutionRate,
            'avg_resolution_time' => round($avgResolutionMinutes / 60, 1), // Convert to hours
            'tickets_trend' => $ticketsTrend,
            'resolution_trend' => $resolutionTrend,
        ];
    }

    /**
     * Get ticket trends for chart.
     */
    private function getTicketTrends(array $appIds, Carbon $startDate, Carbon $endDate): array
    {
        if (empty($appIds)) {
            return ['labels' => [], 'datasets' => []];
        }

        $labels = [];
        $createdData = [];
        $resolvedData = [];

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $labels[] = $currentDate->format('M d');

            $created = Ticket::whereIn('aplikasi_id', $appIds)
                ->whereDate('created_at', $currentDate)
                ->count();

            $resolved = Ticket::whereIn('aplikasi_id', $appIds)
                ->where('status', Ticket::STATUS_RESOLVED)
                ->whereDate('resolved_at', $currentDate)
                ->count();

            $createdData[] = $created;
            $resolvedData[] = $resolved;

            $currentDate->addDay();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Created',
                    'data' => $createdData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Resolved',
                    'data' => $resolvedData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
            ],
        ];
    }

    /**
     * Get status distribution for chart.
     */
    private function getStatusDistribution(array $appIds): array
    {
        if (empty($appIds)) {
            return ['labels' => [], 'datasets' => []];
        }

        $distribution = Ticket::whereIn('aplikasi_id', $appIds)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statusColors = [
            'open' => '#fbbf24',
            'assigned' => '#60a5fa',
            'in_progress' => '#818cf8',
            'waiting_response' => '#fb923c',
            'resolved' => '#34d399',
            'closed' => '#9ca3af',
        ];

        $labels = [];
        $data = [];
        $backgroundColor = [];

        foreach ($distribution as $status => $count) {
            $labels[] = ucfirst(str_replace('_', ' ', $status));
            $data[] = $count;
            $backgroundColor[] = $statusColors[$status] ?? '#6b7280';
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $backgroundColor,
                ],
            ],
        ];
    }

    /**
     * Get priority distribution for chart.
     */
    private function getPriorityDistribution(array $appIds): array
    {
        if (empty($appIds)) {
            return ['labels' => [], 'datasets' => []];
        }

        $distribution = Ticket::whereIn('aplikasi_id', $appIds)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $priorityColors = [
            'low' => '#10b981',
            'medium' => '#3b82f6',
            'high' => '#f59e0b',
            'urgent' => '#ef4444',
        ];

        $labels = [];
        $data = [];
        $backgroundColor = [];

        foreach ($distribution as $priority => $count) {
            $labels[] = ucfirst($priority);
            $data[] = $count;
            $backgroundColor[] = $priorityColors[$priority] ?? '#6b7280';
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Tickets',
                    'data' => $data,
                    'backgroundColor' => $backgroundColor,
                ],
            ],
        ];
    }

    /**
     * Get application statistics.
     */
    private function getApplicationStats(array $appIds, Carbon $startDate, Carbon $endDate): array
    {
        if (empty($appIds)) {
            return [];
        }

        return Aplikasi::whereIn('id', $appIds)
            ->withCount([
                'tickets',
                'tickets as period_tickets' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                },
                'tickets as resolved_tickets' => function ($query) {
                    $query->where('status', Ticket::STATUS_RESOLVED);
                },
                'tickets as open_tickets' => function ($query) {
                    $query->whereIn('status', ['open', 'assigned', 'in_progress']);
                },
            ])
            ->orderBy('tickets_count', 'desc')
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'name' => $app->name,
                    'code' => $app->code,
                    'total_tickets' => $app->tickets_count,
                    'period_tickets' => $app->period_tickets,
                    'resolved_tickets' => $app->resolved_tickets,
                    'open_tickets' => $app->open_tickets,
                    'resolution_rate' => $app->tickets_count > 0 
                        ? round(($app->resolved_tickets / $app->tickets_count) * 100, 1) 
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get category statistics.
     */
    private function getCategoryStats(array $appIds, Carbon $startDate, Carbon $endDate): array
    {
        if (empty($appIds)) {
            return [];
        }

        return KategoriMasalah::whereIn('aplikasi_id', $appIds)
            ->with('aplikasi:id,name,code')
            ->withCount([
                'tickets',
                'tickets as period_tickets' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                },
            ])
            ->orderBy('tickets_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'aplikasi' => $category->aplikasi ? $category->aplikasi->name : 'Unknown',
                    'total_tickets' => $category->tickets_count,
                    'period_tickets' => $category->period_tickets,
                ];
            })
            ->toArray();
    }

    /**
     * Get teknisi performance for managed applications.
     */
    private function getTeknisiPerformance(array $appIds, Carbon $startDate, Carbon $endDate): array
    {
        if (empty($appIds)) {
            return [];
        }

        // Get teknisi who have handled tickets for these applications
        $teknisiNips = Ticket::whereIn('aplikasi_id', $appIds)
            ->whereNotNull('assigned_teknisi_nip')
            ->distinct()
            ->pluck('assigned_teknisi_nip')
            ->toArray();

        if (empty($teknisiNips)) {
            return [];
        }

        return Teknisi::whereIn('nip', $teknisiNips)
            ->withCount([
                'assignedTickets as total_tickets' => function ($query) use ($appIds) {
                    $query->whereIn('aplikasi_id', $appIds);
                },
                'assignedTickets as resolved_tickets' => function ($query) use ($appIds) {
                    $query->whereIn('aplikasi_id', $appIds)
                        ->where('status', Ticket::STATUS_RESOLVED);
                },
                'assignedTickets as period_resolved' => function ($query) use ($appIds, $startDate, $endDate) {
                    $query->whereIn('aplikasi_id', $appIds)
                        ->where('status', Ticket::STATUS_RESOLVED)
                        ->whereBetween('resolved_at', [$startDate, $endDate]);
                },
            ])
            ->orderBy('period_resolved', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($teknisi) {
                return [
                    'nip' => $teknisi->nip,
                    'name' => $teknisi->name,
                    'total_tickets' => $teknisi->total_tickets,
                    'resolved_tickets' => $teknisi->resolved_tickets,
                    'period_resolved' => $teknisi->period_resolved,
                    'rating' => $teknisi->rating ?? 0,
                    'resolution_rate' => $teknisi->total_tickets > 0 
                        ? round(($teknisi->resolved_tickets / $teknisi->total_tickets) * 100, 1) 
                        : 0,
                ];
            })
            ->toArray();
    }
}
