<?php

namespace App\Http\Controllers\AdminHelpdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Ticket;
use App\Models\User;
use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AdminHelpdeskDashboardController extends Controller
{
    protected $analyticsController;

    public function __construct()
    {
        $this->analyticsController = new AnalyticsController();
    }
    /**
     * Display the dashboard.
     */
    public function index()
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_tickets' => 0,
                'open_tickets' => 0,
                'in_progress_tickets' => 0,
                'resolved_tickets' => 0,
            ],
        ]);
    }

    /**
     * Display the admin dashboard with comprehensive system overview.
     */
    public function admin(Request $request)
    {
        $admin = Auth::guard('web')->user();

        // Debug logging for auth check
        if (app()->isLocal()) {
            Log::debug('Admin dashboard accessed', [
                'web_guard_check' => Auth::guard('web')->check(),
                'user_nip' => $admin?->nip,
                'session_id' => session()->getId(),
                'url' => request()->fullUrl()
            ]);
        }

        // Get system-wide statistics with caching (consistent with Analytics)
        $cacheKey = 'admin_dashboard_stats_' . Carbon::today()->format('Y-m-d');
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        
        $stats = Cache::remember($cacheKey, 300, function () use ($startDate, $endDate) {
            // Calculate comprehensive stats
            $totalTickets = Ticket::count();
            $openTickets = Ticket::where('status', Ticket::STATUS_OPEN)->count();
            $assignedTickets = Ticket::where('status', Ticket::STATUS_ASSIGNED)->count();
            $inProgressTickets = Ticket::where('status', Ticket::STATUS_IN_PROGRESS)->count();
            $waitingResponseTickets = Ticket::where('status', Ticket::STATUS_WAITING_RESPONSE)->count();
            $resolvedTickets = Ticket::where('status', Ticket::STATUS_RESOLVED)->count();
            $closedTickets = Ticket::where('status', Ticket::STATUS_CLOSED)->count();
            
            $ticketsToday = Ticket::whereDate('created_at', Carbon::today())->count();
            $ticketsYesterday = Ticket::whereDate('created_at', Carbon::yesterday())->count();
            $resolvedToday = Ticket::where('status', Ticket::STATUS_RESOLVED)
                ->whereDate('resolved_at', Carbon::today())->count();
            $resolvedYesterday = Ticket::where('status', Ticket::STATUS_RESOLVED)
                ->whereDate('resolved_at', Carbon::yesterday())->count();
            
            $unassignedTickets = Ticket::whereNull('assigned_teknisi_nip')
                ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED])
                ->count();
                
            $overdueTickets = Ticket::whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS])
                ->where('created_at', '<', Carbon::now()->subHours(24))
                ->count();
                
            $urgentTickets = Ticket::where('priority', Ticket::PRIORITY_URGENT)
                ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS])
                ->count();
            
            // Calculate average resolution time
            $avgResolutionMinutes = Ticket::where('status', Ticket::STATUS_RESOLVED)
                ->whereNotNull('resolution_time_minutes')
                ->whereBetween('resolved_at', [$startDate, $endDate])
                ->avg('resolution_time_minutes') ?? 0;
            
            // Calculate SLA compliance
            $totalResolved = Ticket::where('status', Ticket::STATUS_RESOLVED)
                ->whereBetween('resolved_at', [$startDate, $endDate])
                ->count();
                
            $withinSla = Ticket::where('status', Ticket::STATUS_RESOLVED)
                ->whereBetween('resolved_at', [$startDate, $endDate])
                ->whereNotNull('resolution_time_minutes')
                ->where('resolution_time_minutes', '<=', 480) // 8 hours SLA
                ->count();
            
            return [
                // Main ticket stats
                'total_tickets' => (int) $totalTickets,
                'tickets_today' => (int) $ticketsToday,
                'unassigned_tickets' => (int) $unassignedTickets,
                'in_progress_tickets' => (int) $inProgressTickets,
                'resolved_today' => (int) $resolvedToday,
                'overdue_tickets' => (int) $overdueTickets,
                'urgent_tickets' => (int) $urgentTickets,
                'open_tickets' => (int) $openTickets,
                
                // Detailed breakdown
                'tickets' => [
                    'total' => (int) $totalTickets,
                    'open' => (int) $openTickets,
                    'assigned' => (int) $assignedTickets,
                    'in_progress' => (int) $inProgressTickets,
                    'waiting_response' => (int) $waitingResponseTickets,
                    'resolved' => (int) $resolvedTickets,
                    'closed' => (int) $closedTickets,
                    'overdue' => (int) $overdueTickets,
                    'escalated' => 0, // Calculate if needed
                    'unassigned' => (int) $unassignedTickets,
                ],
                
                // System resources - Count all users across all role tables
                'users' => [
                    'total' => (int) (User::count() + AdminHelpdesk::count() + AdminAplikasi::count() + Teknisi::count()),
                    'active' => (int) (
                        User::where('status', 'active')->count() +
                        AdminHelpdesk::where('status', 'active')->count() +
                        AdminAplikasi::where('status', 'active')->count() +
                        Teknisi::where('status', 'active')->count()
                    ),
                    'inactive' => (int) (
                        User::where('status', 'inactive')->count() +
                        AdminHelpdesk::where('status', 'inactive')->count() +
                        AdminAplikasi::where('status', 'inactive')->count() +
                        Teknisi::where('status', 'inactive')->count()
                    ),
                    'by_role' => [
                        'regular_users' => (int) User::count(),
                        'admin_helpdesk' => (int) AdminHelpdesk::count(),
                        'admin_aplikasi' => (int) AdminAplikasi::count(),
                        'teknisi' => (int) Teknisi::count(),
                    ],
                ],
                'teknisi' => [
                    'total' => (int) Teknisi::count(),
                    'active' => (int) Teknisi::where('status', 'active')->count(),
                    'inactive' => (int) Teknisi::where('status', 'inactive')->count(),
                ],
                'applications' => [
                    'total' => (int) Aplikasi::count(),
                    'active' => (int) Aplikasi::active()->count(),
                ],
                'categories' => [
                    'total' => (int) KategoriMasalah::count(),
                    'active' => (int) KategoriMasalah::active()->count(),
                ],
                
                // Performance metrics (consistent with Analytics)
                'avg_resolution_time' => round($avgResolutionMinutes / 60, 1), // Convert to hours
                'avg_resolution_time_minutes' => round($avgResolutionMinutes, 0),
                'resolution_rate' => $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 1) : 0,
                'sla_compliance' => $totalResolved > 0 ? round(($withinSla / $totalResolved) * 100, 1) : 0,
                
                // Trends
                'tickets_today_trend' => $this->calculateTrend($ticketsToday, $ticketsYesterday),
                'resolved_today_trend' => $this->calculateTrend($resolvedToday, $resolvedYesterday),
            ];
        });

        // Get recent activity data with pagination (no caching for real-time updates)
        $recentActivity = $this->getRecentActivity($request->get('activity_page', 1), $request->get('activity_per_page', 20));
        
        // Debug logging in local environment
        if (app()->isLocal()) {
            Log::debug('Dashboard recent activities count: ' . count($recentActivity));
        }

        // Get priority breakdown with caching
        $priorityBreakdown = Cache::remember('admin_dashboard_priority_breakdown_' . Carbon::today()->format('Y-m-d'), 300, function () {
            return Ticket::select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->get()
                ->pluck('count', 'priority')
                ->toArray();
        });

        // Get status trends for the last 30 days with caching
        $statusTrends = Cache::remember('admin_dashboard_status_trends_' . Carbon::today()->format('Y-m-d'), 300, function () {
            return $this->getStatusTrends();
        });

        // Get top applications by ticket count with pagination
        $topApplicationsPaginator = Cache::remember('admin_dashboard_top_applications', 300, function () use ($request) {
            return Aplikasi::select('id', 'name', 'code')
                ->withCount('tickets')
                ->orderBy('tickets_count', 'desc')
                ->paginate($request->get('applications_per_page', 10));
        });
        $topApplications = $topApplicationsPaginator->map(function ($app) {
            return [
                'id' => $app->id,
                'name' => $app->name,
                'code' => $app->code,
                'ticket_count' => $app->tickets_count,
            ];
        });

        // Get teknisi performance with pagination and caching
        $teknisiPerformancePaginator = Cache::remember('admin_dashboard_teknisi_performance', 300, function () use ($request) {
            return Teknisi::select('nip', 'name', 'rating', 'department', 'skill_level')
                ->withCount([
                    'assignedTickets as resolved_count' => function ($query) {
                        $query->where('status', Ticket::STATUS_RESOLVED)
                               ->whereDate('resolved_at', '>=', Carbon::now()->subDays(30));
                    }
                ])
                ->withCount([
                    'assignedTickets as total_assigned' => function ($query) {
                        $query->whereDate('created_at', '>=', Carbon::now()->subDays(30));
                    }
                ])
                ->having('total_assigned', '>', 0)
                ->orderBy('resolved_count', 'desc')
                ->paginate($request->get('teknisi_per_page', 10));
        });
        $teknisiPerformance = $teknisiPerformancePaginator->map(function ($teknisi) {
            return [
                'nip' => $teknisi->nip,
                'name' => $teknisi->name,
                'rating_avg' => (float) ($teknisi->rating ?? 0),
                'keahlian' => $teknisi->department ?? 'General',
                'level_teknisi' => $teknisi->skill_level ?? 'junior',
                'resolved_count' => $teknisi->resolved_count,
                'total_assigned' => $teknisi->total_assigned,
                'resolution_rate' => (float) ($teknisi->total_assigned > 0
                    ? round(($teknisi->resolved_count / $teknisi->total_assigned) * 100, 1)
                    : 0),
            ];
        });

        // Get SLA compliance data with caching
        $slaCompliance = Cache::remember('admin_dashboard_sla_compliance_' . Carbon::today()->format('Y-m-d'), 300, function () {
            return $this->getSlaCompliance();
        });

        // Get status distribution for Vue component with caching
        $statusDistribution = Cache::remember('admin_dashboard_status_distribution_' . Carbon::today()->format('Y-m-d'), 300, function () {
            return Ticket::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();
        });

        // Get unassigned tickets with pagination and eager loading
        $unassignedTicketsPaginator = Ticket::unassigned()
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED])
            ->with(['user:nip,name,email', 'aplikasi:id,name', 'kategoriMasalah:id,name'])
            ->latest()
            ->paginate($request->get('unassigned_per_page', 10));
        
        $unassignedTickets = $unassignedTicketsPaginator->getCollection()->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'priority' => $ticket->priority,
                'priority_label' => $ticket->priority_label,
                'status' => $ticket->status,
                'status_label' => $ticket->status_label,
                'user' => $ticket->user ? [
                    'nip' => $ticket->user->nip,
                    'name' => $ticket->user->name,
                    'email' => $ticket->user->email ?? null,
                ] : null,
                'aplikasi' => $ticket->aplikasi ? [
                    'id' => $ticket->aplikasi->id,
                    'name' => $ticket->aplikasi->name,
                ] : null,
                'kategori' => $ticket->kategoriMasalah ? [
                    'id' => $ticket->kategoriMasalah->id,
                    'name' => $ticket->kategoriMasalah->name,
                ] : null,
                'created_at' => $ticket->created_at->toISOString(),
                'formatted_created_at' => $ticket->created_at->diffForHumans(),
                'is_overdue' => $ticket->is_overdue,
                'days_old' => $ticket->created_at->diffInDays(Carbon::now()),
            ];
        })->values();

        // Get available teknisi with pagination and eager loading
        $availableTeknisiPaginator = Teknisi::active()
            ->withCount('activeTickets')
            ->paginate($request->get('teknisi_available_per_page', 20));
        $availableTeknisi = $availableTeknisiPaginator->map(function ($teknisi) {
            return [
                'id' => $teknisi->nip,
                'name' => $teknisi->name,
                'active_tickets_count' => (int) $teknisi->active_tickets_count,
                'max_concurrent_tickets' => (int) ($teknisi->max_concurrent_tickets ?? 10),
            ];
        });

        // Get system health data with caching
        $systemHealth = Cache::remember('admin_dashboard_system_health_' . Carbon::today()->format('Y-m-d'), 300, function () use ($stats) {
            return [
                'avg_response_time' => (float) (Ticket::whereNotNull('first_response_at')
                    ->whereDate('created_at', '>=', Carbon::now()->subDays(30))
                    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, first_response_at)) as avg_hours')
                    ->value('avg_hours') ?? 0),
                'resolution_rate' => (float) ($stats['tickets']['total'] > 0
                    ? round(($stats['tickets']['resolved'] / $stats['tickets']['total']) * 100, 1)
                    : 0),
                'user_satisfaction' => (float) (Ticket::whereNotNull('user_rating')
                    ->whereDate('resolved_at', '>=', Carbon::now()->subDays(30))
                    ->avg('user_rating') ?? 0),
                'active_users' => (int) (
                    User::where('status', 'active')->count() +
                    AdminHelpdesk::where('status', 'active')->count() +
                    AdminAplikasi::where('status', 'active')->count() +
                    Teknisi::where('status', 'active')->count()
                ),
            ];
        });

        // Get quick stats (enhanced)
        $quickStats = [
            'total_users' => (int) $stats['users']['total'],
            'active_users' => (int) $stats['users']['active'],
            'total_teknisi' => (int) $stats['teknisi']['total'],
            'active_teknisi' => (int) $stats['teknisi']['active'],
            'total_applications' => (int) $stats['applications']['total'],
            'active_applications' => (int) $stats['applications']['active'],
            'total_categories' => (int) $stats['categories']['total'],
            'active_categories' => (int) $stats['categories']['active'],
            // Additional dashboard metrics
            'avg_response_time' => round($stats['avg_resolution_time'], 1),
            'resolution_rate' => (float) $stats['resolution_rate'],
            'sla_compliance' => (float) $stats['sla_compliance'],
        ];

        // Get chart data for frontend with caching
        $chartData = Cache::remember('admin_dashboard_chart_data_' . Carbon::today()->format('Y-m-d'), 300, function () {
            return $this->getChartData();
        });

        $response = Inertia::render('AdminHelpdesk/Dashboard', [
            'stats' => $stats,
            'statusDistribution' => $statusDistribution,
            'unassignedTickets' => $unassignedTickets->all(),
            'availableTeknisi' => $availableTeknisi->all(),
            'recentActivity' => $recentActivity,
            'teknisiPerformance' => $teknisiPerformance->all(),
            'systemHealth' => $systemHealth,
            'quickStats' => $quickStats,
            'priorityBreakdown' => $priorityBreakdown,
            'statusTrends' => $statusTrends,
            'topApplications' => $topApplications->all(),
            'slaCompliance' => $slaCompliance,
            'chartData' => $chartData,
            'pagination' => [
                'unassigned_tickets' => [
                    'current_page' => $unassignedTicketsPaginator->currentPage(),
                    'last_page' => $unassignedTicketsPaginator->lastPage(),
                    'per_page' => $unassignedTicketsPaginator->perPage(),
                    'total' => $unassignedTicketsPaginator->total(),
                    'from' => $unassignedTicketsPaginator->firstItem(),
                    'to' => $unassignedTicketsPaginator->lastItem(),
                ],
                'available_teknisi' => [
                    'current_page' => $availableTeknisiPaginator->currentPage(),
                    'last_page' => $availableTeknisiPaginator->lastPage(),
                    'per_page' => $availableTeknisiPaginator->perPage(),
                    'total' => $availableTeknisiPaginator->total(),
                ],
                'teknisi_performance' => [
                    'current_page' => $teknisiPerformancePaginator->currentPage(),
                    'last_page' => $teknisiPerformancePaginator->lastPage(),
                    'per_page' => $teknisiPerformancePaginator->perPage(),
                    'total' => $teknisiPerformancePaginator->total(),
                ],
                'top_applications' => [
                    'current_page' => $topApplicationsPaginator->currentPage(),
                    'last_page' => $topApplicationsPaginator->lastPage(),
                    'per_page' => $topApplicationsPaginator->perPage(),
                    'total' => $topApplicationsPaginator->total(),
                ],
            ],
        ]);

        // Debug logging for Inertia response
        if (app()->isLocal()) {
            Log::debug('Admin dashboard: About to return Inertia response', [
                'component' => 'AdminHelpdesk/Dashboard',
                'data_keys' => array_keys($stats),
                'url' => request()->fullUrl(),
            ]);
        }

        return $response;
    }

    /**
     * Get recent system activity with pagination from audit logs.
     */
    private function getRecentActivity(int $page = 1, int $perPage = 20): array
    {
        // Query audit logs directly for recent activities
        $auditLogs = \App\Models\AuditLog::orderBy('created_at', 'desc')
            ->limit($perPage)
            ->get();

        $activities = [];

        foreach ($auditLogs as $log) {
            // Map audit log actions to activity types and colors
            $activityType = $this->mapActionToType($log->action, $log->entity_type);
            $activityColor = $this->mapActionToColor($activityType);

            $activities[] = [
                'id' => $log->id,
                'type' => $activityType,
                'action' => $log->action,
                'description' => $log->description ?? $this->generateDescription($log),
                'user_name' => $log->actor_name ?? 'System',
                'actor_type' => $log->actor_type ?? 'System',
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'created_at' => $log->created_at,
                'formatted_created_at' => $log->created_at->diffForHumans(),
                'color' => $activityColor,
            ];
        }

        return $activities;
    }

    /**
     * Map audit log action to activity type for frontend.
     */
    private function mapActionToType(string $action, ?string $entityType = null): string
    {
        // Map to frontend-expected types
        return match($action) {
            'created' => $entityType === 'Ticket' ? 'ticket_created' : ($entityType === 'User' || str_contains($entityType ?? '', 'User') || $entityType === 'AdminHelpdesk' || $entityType === 'AdminAplikasi' || $entityType === 'Teknisi' ? 'user_created' : 'system_update'),
            'updated' => 'system_update',
            'deleted' => 'system_update',
            'assigned' => 'ticket_assigned',
            'resolved' => 'ticket_resolved',
            'closed' => 'ticket_resolved',
            'login' => 'system_update',
            'logout' => 'system_update',
            'commented' => 'ticket_created',
            'status_changed' => 'system_update',
            default => 'system_update',
        };
    }

    /**
     * Map audit log action to color CSS class for frontend.
     */
    private function mapActionToColor(string $type): string
    {
        // Return Tailwind CSS classes that match frontend expectations
        return match($type) {
            'ticket_created' => 'bg-blue-500',
            'ticket_assigned' => 'bg-indigo-500',
            'ticket_resolved' => 'bg-green-500',
            'user_created' => 'bg-purple-500',
            'system_update' => 'bg-gray-500',
            default => 'bg-gray-500',
        };
    }

    /**
     * Generate description from audit log if not present.
     */
    private function generateDescription(\App\Models\AuditLog $log): string
    {
        $action = ucfirst($log->action);
        $entityType = $log->entity_type ?? 'item';
        $actor = $log->actor_name ?? 'System';

        return "{$actor} {$action} {$entityType}";
    }

    /**
     * Get status trends for the last 30 days.
     */
    private function getStatusTrends(): array
    {
        $trends = [];
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(30);

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'created' => Ticket::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'resolved' => Ticket::where('status', Ticket::STATUS_RESOLVED)
                    ->whereBetween('resolved_at', [$dayStart, $dayEnd])->count(),
                'closed' => Ticket::where('status', Ticket::STATUS_CLOSED)
                    ->whereBetween('updated_at', [$dayStart, $dayEnd])->count(),
            ];

            $currentDate->addDay();
        }

        return $trends;
    }

    /**
     * Get SLA compliance data.
     */
    private function getSlaCompliance(): array
    {
        $totalResolved = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereDate('resolved_at', '>=', Carbon::now()->subDays(30))
            ->count();

        $withinSla = Ticket::withinSla()
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereDate('resolved_at', '>=', Carbon::now()->subDays(30))
            ->count();

        return [
            'total_resolved' => (int) $totalResolved,
            'within_sla' => (int) $withinSla,
            'sla_breached' => (int) ($totalResolved - $withinSla),
            'compliance_rate' => (float) ($totalResolved > 0 ? round(($withinSla / $totalResolved) * 100, 1) : 0),
        ];
    }

    /**
     * Calculate trend percentage between today and yesterday.
     */
    private function calculateTrend(int $today, int $yesterday): array
    {
        if ($yesterday === 0) {
            return [
                'value' => $today > 0 ? 100 : 0,
                'direction' => $today > 0 ? 'up' : 'neutral'
            ];
        }

        $percentage = (($today - $yesterday) / $yesterday) * 100;

        return [
            'value' => round(abs($percentage), 1),
            'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'neutral')
        ];
    }

    /**
     * Get chart data for dashboard visualizations (consistent with Analytics).
     */
    private function getChartData(): array
    {
        // Ticket trend data (last 7 days) - consistent with Analytics
        $ticketTrend = [
            'labels' => [],
            'datasets' => [
                [
                    'label' => 'Created',
                    'data' => [],
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Resolved',
                    'data' => [],
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ]
            ]
        ];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $created = Ticket::whereDate('created_at', $date)->count();
            $resolved = Ticket::where('status', Ticket::STATUS_RESOLVED)
                ->whereDate('resolved_at', $date)->count();

            $ticketTrend['labels'][] = $date->format('M d');
            $ticketTrend['datasets'][0]['data'][] = (int) $created;
            $ticketTrend['datasets'][1]['data'][] = (int) $resolved;
        }

        // Priority distribution - Chart.js format
        $priorityQuery = Ticket::selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->get();
        
        $priorityData = [
            'labels' => [],
            'datasets' => [
                [
                    'data' => [],
                    'backgroundColor' => [],
                ]
            ]
        ];
        
        foreach ($priorityQuery as $item) {
            $priorityData['labels'][] = ucfirst($item->priority);
            $priorityData['datasets'][0]['data'][] = (int) $item->count;
            $priorityData['datasets'][0]['backgroundColor'][] = match($item->priority) {
                'urgent' => '#ef4444',
                'high' => '#f97316',
                'medium' => '#3b82f6',
                'low' => '#10b981',
                default => '#6b7280',
            };
        }

        // Status distribution - Chart.js format
        $statusQuery = Ticket::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();
        
        $statusData = [
            'labels' => [],
            'datasets' => [
                [
                    'data' => [],
                    'backgroundColor' => [],
                ]
            ]
        ];
        
        foreach ($statusQuery as $item) {
            $statusData['labels'][] = ucfirst(str_replace('_', ' ', $item->status));
            $statusData['datasets'][0]['data'][] = (int) $item->count;
            $statusData['datasets'][0]['backgroundColor'][] = match($item->status) {
                'open' => '#fbbf24',
                'assigned' => '#60a5fa',
                'in_progress' => '#818cf8',
                'waiting_response' => '#fb923c',
                'resolved' => '#34d399',
                'closed' => '#9ca3af',
                default => '#6b7280',
            };
        }

        // Application performance (top 10) - Bar chart format
        $applicationData = Aplikasi::select('id', 'name', 'code')
            ->withCount('tickets')
            ->orderBy('tickets_count', 'desc')
            ->limit(10)
            ->get();
        
        $applications = [
            'labels' => $applicationData->pluck('code')->toArray(),
            'datasets' => [
                [
                    'label' => 'Tickets',
                    'data' => $applicationData->pluck('tickets_count')->map(fn($c) => (int)$c)->toArray(),
                    'backgroundColor' => '#6366f1',
                ]
            ]
        ];

        // Teknisi workload (top 10) - Bar chart format
        $teknisiQuery = Teknisi::select('nip', 'name')
            ->withCount(['assignedTickets as active_count' => function ($query) {
                $query->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING_RESPONSE, Ticket::STATUS_ASSIGNED]);
            }])
            ->having('active_count', '>', 0)
            ->orderBy('active_count', 'desc')
            ->limit(10)
            ->get();
            
        $teknisiWorkload = [
            'labels' => $teknisiQuery->pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => 'Active Tickets',
                    'data' => $teknisiQuery->pluck('active_count')->map(fn($c) => (int)$c)->toArray(),
                    'backgroundColor' => '#8b5cf6',
                ]
            ]
        ];

        return [
            'ticketTrend' => $ticketTrend,
            'priorityData' => $priorityData,
            'statusData' => $statusData,
            'applicationData' => $applications,
            'teknisiWorkload' => $teknisiWorkload,
        ];
    }

    /**
     * Refresh critical dashboard statistics
     */
    public function refreshStats(Request $request)
    {
        $admin = Auth::guard('web')->user();

        if (!$admin) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            // Clear today's cache keys to force refresh
            $todayKey = Carbon::today()->format('Y-m-d');
            Cache::forget("admin_dashboard_stats_{$todayKey}");
            Cache::forget("admin_dashboard_priority_breakdown_{$todayKey}");
            Cache::forget("admin_dashboard_status_trends_{$todayKey}");
            Cache::forget("admin_dashboard_system_health_{$todayKey}");
            Cache::forget("admin_dashboard_status_distribution_{$todayKey}");

            // Recalculate critical stats
            $stats = [
                'total_tickets' => (int) Ticket::count(),
                'tickets_today' => (int) Ticket::whereDate('created_at', Carbon::today())->count(),
                'unassigned_tickets' => (int) Ticket::whereNull('assigned_teknisi_nip')
                    ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED])
                    ->count(),
                'in_progress_tickets' => (int) Ticket::where('status', Ticket::STATUS_IN_PROGRESS)->count(),
                'resolved_today' => (int) Ticket::where('status', Ticket::STATUS_RESOLVED)
                    ->whereDate('resolved_at', Carbon::today())->count(),
                'open_tickets' => (int) Ticket::whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED])->count(),
                'overdue_tickets' => (int) Ticket::overdue()->count(),
                'urgent_tickets' => (int) Ticket::where('priority', Ticket::PRIORITY_URGENT)
                    ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS])
                    ->count(),
            ];

            // Calculate trends
            $stats['tickets_today_trend'] = $this->calculateTrend(
                Ticket::whereDate('created_at', Carbon::today())->count(),
                Ticket::whereDate('created_at', Carbon::yesterday())->count()
            );

            $stats['resolved_today_trend'] = $this->calculateTrend(
                Ticket::where('status', Ticket::STATUS_RESOLVED)
                    ->whereDate('resolved_at', Carbon::today())->count(),
                Ticket::where('status', Ticket::STATUS_RESOLVED)
                    ->whereDate('resolved_at', Carbon::yesterday())->count()
            );

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'timestamp' => now()->toISOString(),
                'cache_buster' => uniqid(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to refresh dashboard stats',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
