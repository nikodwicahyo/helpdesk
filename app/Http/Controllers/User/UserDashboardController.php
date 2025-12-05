<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use App\Models\Ticket;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\User;
use App\Services\AuthService;
use Carbon\Carbon;

class UserDashboardController extends Controller
{
    /**
     * Display user dashboard with statistics and ticket overview.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get user's dashboard statistics with caching
        $stats = Cache::remember("user_dashboard_stats_{$user->id}", 300, function () use ($user) {
            return $this->getUserDashboardStats($user);
        });

        // Get recent tickets with pagination and caching
        $recentTickets = Cache::remember("user_recent_tickets_{$user->id}", 300, function () use ($user, $request) {
            return $this->getRecentTickets($user, $request->get('recent_tickets_limit', 5));
        });

        // Get upcoming deadlines with caching
        $upcomingDeadlines = Cache::remember("user_upcoming_deadlines_{$user->id}", 300, function () use ($user, $request) {
            return $this->getUpcomingDeadlines($user, $request->get('upcoming_deadlines_limit', 5));
        });

        // Get application usage statistics with caching
        $applicationStats = Cache::remember("user_application_stats_{$user->id}", 300, function () use ($user) {
            return $this->getApplicationUsageStats($user);
        });

        // Get notifications with caching
        $notifications = Cache::remember("user_recent_notifications_{$user->id}", 300, function () use ($user, $request) {
            return $this->getRecentNotifications($user, $request->get('notifications_limit', 5));
        });

        return Inertia::render('User/Dashboard', [
            'stats' => $stats,
            'recentTickets' => $recentTickets,
            'upcomingDeadlines' => $upcomingDeadlines,
            'applicationStats' => $applicationStats,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Get comprehensive dashboard statistics for the user.
     */
    private function getUserDashboardStats($user): array
    {
        // Use AuthService to get user role
        $authService = app(AuthService::class);
        $userRole = $authService->getUserRole($user);

        // Handle different user types
        if ($user instanceof User) {
            // Regular users have tickets they created - use single query with eager loading
            // Active tickets include: open, assigned, in_progress, waiting_user, waiting_admin
            $ticketStats = $user->tickets()
                ->selectRaw('
                    COUNT(*) as total_tickets,
                    COUNT(CASE WHEN status IN (?, ?, ?, ?, ?) THEN 1 END) as active_tickets,
                    COUNT(CASE WHEN status = ? AND resolved_at >= ? THEN 1 END) as resolved_tickets,
                    COUNT(CASE WHEN status = ? AND closed_at >= ? THEN 1 END) as closed_tickets,
                    COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as resolved_total,
                    AVG(CASE WHEN status = ? AND resolution_time_minutes IS NOT NULL THEN resolution_time_minutes END) as avg_resolution_time,
                    COUNT(CASE WHEN MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 END) as tickets_this_month
                ', [
                    Ticket::STATUS_OPEN,
                    Ticket::STATUS_ASSIGNED,
                    Ticket::STATUS_IN_PROGRESS,
                    Ticket::STATUS_WAITING_USER,
                    Ticket::STATUS_WAITING_ADMIN,
                    Ticket::STATUS_RESOLVED,
                    Carbon::now()->subDays(30)->toDateString(),
                    Ticket::STATUS_CLOSED,
                    Carbon::now()->subDays(30)->toDateString(),
                    Ticket::STATUS_RESOLVED,
                    Ticket::STATUS_CLOSED,
                    Ticket::STATUS_RESOLVED,
                    Carbon::now()->month,
                    Carbon::now()->year
                ])
                ->first();

            $totalTickets = $ticketStats->total_tickets;
            $activeTickets = $ticketStats->active_tickets;
            $resolvedTickets = $ticketStats->resolved_tickets;
            $closedTickets = $ticketStats->closed_tickets;
            $resolvedTotal = $ticketStats->resolved_total;
            $resolutionRate = $totalTickets > 0 ? round(($resolvedTotal / $totalTickets) * 100, 2) : 0;
            $avgResolutionTime = $ticketStats->avg_resolution_time;
            $ticketsThisMonth = $ticketStats->tickets_this_month;
        } else {
            // Other user types (Teknisi, AdminHelpdesk, AdminAplikasi) don't create tickets
            $totalTickets = 0;
            $activeTickets = 0;
            $resolvedTickets = 0;
            $closedTickets = 0;
            $resolutionRate = 0;
            $avgResolutionTime = null;
            $ticketsThisMonth = 0;
        }

        return [
            'total_tickets' => $totalTickets,
            'active_tickets' => $activeTickets,
            'resolved_tickets' => $resolvedTickets,
            'closed_tickets' => $closedTickets,
            'resolution_rate' => $resolutionRate,
            'avg_resolution_time' => $avgResolutionTime ? round($avgResolutionTime, 2) : null,
            'tickets_this_month' => $ticketsThisMonth,
            'unread_notifications' => $user->unread_notifications_count ?? 0,
        ];
    }

    /**
     * Get user's recent tickets for display.
     */
    private function getRecentTickets($user, int $limit = 5): array
    {
        // Use AuthService to get user role
        $authService = app(AuthService::class);
        $userRole = $authService->getUserRole($user);

        // Only regular users have tickets they created
        if ($user instanceof User) {
            return $user->tickets()
                ->with([
                    'aplikasi:id,name,code',
                    'kategoriMasalah:id,name',
                    'assignedTeknisi:nip,name'
                ])
                ->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'title' => $ticket->title,
                        'status' => $ticket->status,
                        'status_label' => $ticket->status_label,
                        'status_badge_color' => $ticket->status_badge_color,
                        'priority' => $ticket->priority,
                        'priority_label' => $ticket->priority_label,
                        'priority_badge_color' => $ticket->priority_badge_color,
                        'aplikasi' => $ticket->aplikasi ? [
                            'id' => $ticket->aplikasi->id,
                            'name' => $ticket->aplikasi->name,
                            'code' => $ticket->aplikasi->code,
                        ] : null,
                        'kategori_masalah' => $ticket->kategoriMasalah ? [
                            'id' => $ticket->kategoriMasalah->id,
                            'name' => $ticket->kategoriMasalah->name,
                        ] : null,
                        'assigned_teknisi' => $ticket->assignedTeknisi ? [
                            'nip' => $ticket->assignedTeknisi->nip,
                            'name' => $ticket->assignedTeknisi->name,
                        ] : null,
                        'created_at' => $ticket->created_at,
                        'formatted_created_at' => $ticket->formatted_created_at,
                        'updated_at' => $ticket->updated_at,
                        'is_overdue' => $ticket->is_overdue,
                        'time_elapsed' => $ticket->time_elapsed,
                    ];
                })
                ->toArray();
        } else {
            return [];
        }
    }
    /**
     * Get application usage statistics for the user.
     */
    private function getApplicationUsageStats($user): array
    {
        // Only regular users have tickets they created
        if ($user instanceof User) {
            $applicationStats = $user->tickets()
                ->selectRaw('aplikasi_id, COUNT(*) as ticket_count')
                ->with('aplikasi:id,name,code,icon')
                ->whereNotNull('aplikasi_id')
                ->groupBy('aplikasi_id')
                ->orderBy('ticket_count', 'desc')
                ->limit(5)
                ->get();

            return $applicationStats->map(function ($stat) {
                return [
                    'aplikasi' => [
                        'id' => $stat->aplikasi->id,
                        'name' => $stat->aplikasi->name,
                        'code' => $stat->aplikasi->code,
                        'icon' => $stat->aplikasi->icon,
                    ],
                    'ticket_count' => $stat->ticket_count,
                    'percentage' => 0, // Will be calculated if total is provided
                ];
            })->toArray();
        }

        // Other user types don't have tickets they created
        return [];
    }

    /**
     * Get recent notifications for the user.
     */
    private function getRecentNotifications($user, int $limit = 5): array
    {
        if (!$user) {
            return [];
        }
        
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at,
                    'formatted_created_at' => $notification->created_at?->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Get upcoming deadlines for user's tickets.
     */
    private function getUpcomingDeadlines($user, int $limit = 5): array
    {
        // Only regular users have tickets they created
        if ($user instanceof User) {
            return $user->tickets()
                ->with(['aplikasi:id,name'])
                ->whereNotNull('due_date')
                ->where('due_date', '>', Carbon::now())
                ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING_RESPONSE])
                ->orderBy('due_date', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'title' => $ticket->title,
                        'due_date' => $ticket->due_date,
                        'formatted_due_date' => $ticket->formatted_due_date,
                        'days_until_due' => $ticket->days_until_due,
                        'priority' => $ticket->priority,
                        'priority_badge_color' => $ticket->priority_badge_color,
                        'aplikasi' => $ticket->aplikasi ? [
                            'name' => $ticket->aplikasi->name,
                        ] : null,
                    ];
                })
                ->toArray();
        }

        // Other user types don't have tickets they created
        return [];
    }

    /**
     * Get dashboard statistics for charts.
     */
    public function getDashboardStats(Request $request)
    {
        $user = Auth::user();

        // Use AuthService to get user role
        $authService = app(AuthService::class);
        $userRole = $authService->getUserRole($user);

        // Only regular users have tickets they created
        if ($user instanceof User) {
            // Use caching for expensive chart calculations
            $cacheKey = "user_chart_stats_{$user->id}";
            $chartStats = Cache::remember($cacheKey, 300, function () use ($user) {
                $statusStats = $user->tickets()
                    ->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->get()
                    ->pluck('count', 'status')
                    ->toArray();

                $priorityStats = $user->tickets()
                    ->selectRaw('priority, COUNT(*) as count')
                    ->groupBy('priority')
                    ->get()
                    ->pluck('count', 'priority')
                    ->toArray();

                $monthlyStats = $user->tickets()
                    ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                    ->where('created_at', '>=', Carbon::now()->subMonths(6))
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get()
                    ->pluck('count', 'month')
                    ->toArray();

                // Get application usage stats for charts with eager loading
                $applicationStats = $user->tickets()
                    ->selectRaw('aplikasi_id, COUNT(*) as ticket_count')
                    ->with('aplikasi:id,name,code')
                    ->whereNotNull('aplikasi_id')
                    ->groupBy('aplikasi_id')
                    ->orderBy('ticket_count', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($stat) {
                        return [
                            'name' => $stat->aplikasi->name,
                            'code' => $stat->aplikasi->code,
                            'count' => $stat->ticket_count,
                        ];
                    })
                    ->toArray();

                // Get resolution time trend
                $resolutionTrend = $user->tickets()
                    ->where('status', Ticket::STATUS_RESOLVED)
                    ->whereNotNull('resolution_time_minutes')
                    ->selectRaw('DATE_FORMAT(resolved_at, "%Y-%m-%d") as date, AVG(resolution_time_minutes) as avg_time')
                    ->where('resolved_at', '>=', Carbon::now()->subDays(30))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->pluck('avg_time', 'date')
                    ->toArray();

                // Get weekly activity - optimized with single query
                // Generate last 7 days with zero counts as default
                $last7Days = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i)->format('Y-m-d');
                    $last7Days[$date] = 0;
                }
                
                // Get actual ticket counts - use DATE_FORMAT for consistent string format
                $actualCounts = $user->tickets()
                    ->selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d") as date, COUNT(*) as count')
                    ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->pluck('count', 'date')
                    ->map(fn($count) => (int) $count)
                    ->toArray();
                
                // Merge actual counts with default zeros (actual counts overwrite zeros)
                $weeklyActivity = array_merge($last7Days, $actualCounts);

                return [
                    'status_distribution' => $statusStats,
                    'priority_distribution' => $priorityStats,
                    'monthly_trend' => $monthlyStats,
                    'application_stats' => $applicationStats,
                    'resolution_trend' => $resolutionTrend,
                    'weekly_activity' => $weeklyActivity,
                ];
            });
        } else {
            // Other user types don't have tickets they created
            $chartStats = [
                'status_distribution' => [],
                'priority_distribution' => [],
                'monthly_trend' => [],
                'application_stats' => [],
                'resolution_trend' => [],
                'weekly_activity' => [],
            ];
        }

        return response()->json($chartStats);
    }

    /**
     * Get user's activity summary.
     */
    public function getActivitySummary(Request $request)
    {
        $user = Auth::user();
        $days = $request->get('days', 30);

        $startDate = Carbon::now()->subDays($days);

        // Use AuthService to get user role
        $authService = app(AuthService::class);
        $userRole = $authService->getUserRole($user);

        // Safely get user nip if user is not null
        $userNip = $user ? $user->nip : null;

        // Only regular users have tickets they created
        if ($user instanceof User) {
            $activity = [
                'tickets_created' => $user->tickets()
                    ->where('created_at', '>=', $startDate)
                    ->count(),
                'tickets_resolved' => $user->tickets()
                    ->where('status', Ticket::STATUS_RESOLVED)
                    ->where('resolved_at', '>=', $startDate)
                    ->count(),
                'comments_added' => $userNip
                    ? \App\Models\TicketComment::where('user_nip', $userNip)
                        ->where('created_at', '>=', $startDate)
                        ->count()
                    : 0,
                'applications_used' => $user->tickets()
                    ->where('created_at', '>=', $startDate)
                    ->distinct('aplikasi_id')
                    ->count('aplikasi_id'),
            ];
        } else {
            // Other user types don't create tickets, but they might add comments
            $activity = [
                'tickets_created' => 0,
                'tickets_resolved' => 0,
                'comments_added' => $userNip
                    ? \App\Models\TicketComment::where('user_nip', $userNip)
                        ->where('created_at', '>=', $startDate)
                        ->count()
                    : 0,
                'applications_used' => 0,
            ];
        }

        return response()->json($activity);
    }
}
