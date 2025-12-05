<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use App\Models\Ticket;
use App\Models\Teknisi;
use App\Models\User;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use Carbon\Carbon;

class TeknisiDashboardController extends Controller
{
    /**
     * Display technician dashboard with workload statistics and assigned tickets.
     */
    public function index(Request $request)
    {
        // Use AuthService as the single source for authentication
        $authService = app(\App\Services\AuthService::class);
        $user = $authService->getCurrentAuthenticatedUser();
        $userRole = $authService->getCurrentUserRole();

        if (!$user || $userRole !== 'teknisi') {
            return redirect()->route('login')->withErrors(['Access denied. Invalid session.']);
        }

        $teknisi = Teknisi::where('nip', $user->nip)->first();

        if (!$teknisi) {
            return redirect()->route('login')->withErrors(['Teknisi not found.']);
        }

        // Get date range for filtering (default: last 30 days)
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        // Personal workload statistics with caching
        $workloadStats = Cache::remember("teknisi_workload_{$teknisi->nip}_{$startDate}_{$endDate}", 300, function () use ($teknisi, $startDate, $endDate) {
            return $this->getTeknisiWorkloadStats($teknisi->nip, $startDate, $endDate);
        });

        // Assigned tickets overview - clear old cache and get fresh data
        Cache::forget("teknisi_assigned_tickets_{$teknisi->nip}");
        $assignedTickets = Cache::remember("teknisi_assigned_tickets_v2_{$teknisi->nip}", 300, function () use ($teknisi) {
            return $this->getAssignedTicketsOverview($teknisi->nip);
        });

        // Recent activity (tickets worked on) with caching
        $recentActivity = Cache::remember("teknisi_recent_activity_{$teknisi->nip}", 300, function () use ($teknisi) {
            return $this->getRecentActivity($teknisi->nip, 10);
        });

        // Performance metrics with caching
        $performanceMetrics = Cache::remember("teknisi_performance_{$teknisi->nip}_{$startDate}_{$endDate}", 300, function () use ($teknisi, $startDate, $endDate) {
            return $this->getPerformanceMetrics($teknisi->nip, $startDate, $endDate);
        });

        // Application expertise (tickets handled by application) with caching
        $applicationExpertise = Cache::remember("teknisi_application_expertise_{$teknisi->nip}_{$startDate}_{$endDate}", 300, function () use ($teknisi, $startDate, $endDate) {
            return $this->getApplicationExpertise($teknisi->nip, $startDate, $endDate);
        });

        // Priority distribution of current tickets with caching
        $priorityDistribution = Cache::remember("teknisi_priority_distribution_{$teknisi->nip}", 300, function () use ($teknisi) {
            return $this->getPriorityDistribution($teknisi->nip);
        });

        // SLA compliance for assigned tickets with caching
        $slaCompliance = Cache::remember("teknisi_sla_compliance_{$teknisi->nip}_{$startDate}_{$endDate}", 300, function () use ($teknisi, $startDate, $endDate) {
            return $this->getSlaCompliance($teknisi->nip, $startDate, $endDate);
        });

        // Upcoming deadlines with caching
        $upcomingDeadlines = Cache::remember("teknisi_upcoming_deadlines_{$teknisi->nip}", 300, function () use ($teknisi) {
            return $this->getUpcomingDeadlines($teknisi->nip);
        });

        // Daily/weekly workload trend with caching
        $workloadTrend = Cache::remember("teknisi_workload_trend_{$teknisi->nip}_{$startDate}_{$endDate}", 300, function () use ($teknisi, $startDate, $endDate) {
            return $this->getWorkloadTrend($teknisi->nip, $startDate, $endDate);
        });

        // Get current tickets for the kanban board with eager loading
        // Note: Use 'name' in select, but access as 'nama_lengkap' via accessor
        $myTicketsQuery = Ticket::where('assigned_teknisi_nip', $teknisi->nip)
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING_USER, Ticket::STATUS_WAITING_ADMIN, Ticket::STATUS_RESOLVED])
            ->with(['user:nip,name', 'aplikasi:id,name'])
            ->latest('updated_at')
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'priority_label' => $ticket->priority_label,
                    'user' => [
                        'nama_lengkap' => $ticket->user?->name ?? 'Unknown',
                    ],
                    'application' => [
                        'name' => $ticket->aplikasi?->name ?? 'Unknown',
                    ],
                    'formatted_created_at' => $ticket->created_at ? $ticket->created_at->format('M d, Y H:i') : '',
                    'formatted_resolved_at' => $ticket->resolved_at ? $ticket->resolved_at->format('M d, Y H:i') : '',
                    'time_elapsed' => $ticket->created_at ? $ticket->created_at->diffForHumans() : '',
                    'rating' => $ticket->user_rating,
                ];
            });

        $myTickets = $myTicketsQuery->toArray();

        // Calculate trend for resolved tickets today
        $resolvedToday = Ticket::where('assigned_teknisi_nip', $teknisi->nip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereDate('resolved_at', Carbon::today())
            ->count();

        $resolvedYesterday = Ticket::where('assigned_teknisi_nip', $teknisi->nip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereDate('resolved_at', Carbon::yesterday())
            ->count();

        // Calculate trend - if yesterday was 0, show 0 for no change or 100 if today has tickets
        if ($resolvedYesterday > 0) {
            $resolvedTodayTrend = (($resolvedToday - $resolvedYesterday) / $resolvedYesterday) * 100;
        } else {
            $resolvedTodayTrend = $resolvedToday > 0 ? 100 : 0;
        }

        // Prepare stats object for the Vue component
        $stats = [
            'assigned_tickets' => $assignedTickets['assigned'] ?? 0, // Count both open + assigned status
            'myAssignedTickets' => $assignedTickets['assigned'] ?? 0, // For Vue component compatibility
            'in_progress_tickets' => $assignedTickets['in_progress'] ?? 0,
            'resolved_tickets' => $assignedTickets['resolved'] ?? 0, // Total resolved tickets
            'resolved_today' => $resolvedToday,
            'resolved_today_trend' => round($resolvedTodayTrend, 1),
            'avg_rating' => round($performanceMetrics['average_rating'] ?? 0, 1),
            'avg_resolution_time' => round($workloadStats['avg_resolution_time_hours'] ?? 0, 1), // Average resolution time in hours
            'waiting_response_tickets' => $assignedTickets['waiting_response'] ?? 0,
            'total_resolved_period' => $workloadStats['resolved_this_period'] ?? 0,
            'urgent_tickets' => $assignedTickets['urgent'] ?? 0,
            'overdue_tickets' => $assignedTickets['overdue'] ?? 0,
        ];

        // Prepare performance object
        $performance = [
            'resolution_rate' => $workloadStats['resolution_rate'] ?? 0,
            'avg_response_time' => round(($performanceMetrics['avg_first_response_time_minutes'] ?? 0) / 60, 1), // First response time
            'avg_resolution_time' => round($workloadStats['avg_resolution_time_hours'] ?? 0, 1), // Resolution time
            'tickets_this_week' => Ticket::where('assigned_teknisi_nip', $teknisi->nip)
                ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->count(),
            'avg_rating' => $performanceMetrics['average_rating'] ?? 0,
        ];

        // Prepare specializations based on application expertise
        $specializations = collect($applicationExpertise)->map(function ($app) {
            $resolvedCount = $app['resolved_count'];
            $totalCount = $app['ticket_count'];
            $successRate = $totalCount > 0 ? ($resolvedCount / $totalCount) * 100 : 0;

            return [
                'name' => $app['application']['name'],
                'level' => min(100, round($successRate)),
            ];
        })->toArray();

        // Get recent feedback from resolved tickets with ratings and eager loading
        $recentFeedback = Ticket::where('assigned_teknisi_nip', $teknisi->nip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereNotNull('user_rating')
            ->with('user:nip,name')
            ->latest('resolved_at')
            ->limit(5)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'user_name' => $ticket->user?->name ?? 'Anonymous',
                    'rating' => $ticket->user_rating,
                    'feedback' => 'Thank you for the quick resolution!', // Default feedback, could be enhanced with actual feedback if available
                    'formatted_created_at' => $ticket->resolved_at->format('M d, Y'),
                ];
            })
            ->toArray();

        return Inertia::render('Teknisi/Dashboard', [
            'stats' => $stats,
            'myTickets' => $myTickets,
            'performance' => $performance,
            'specializations' => $specializations,
            'recentFeedback' => $recentFeedback,
            'dateRange' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Get technician workload statistics.
     */
    private function getTeknisiWorkloadStats(string $teknisiNip, string $startDate, string $endDate): array
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $totalAssigned = Ticket::where('assigned_teknisi_nip', $teknisiNip)->count();
        $currentlyAssigned = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING_USER, Ticket::STATUS_WAITING_ADMIN])
            ->count();
        $resolvedThisPeriod = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->count();
        $overdueTickets = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->overdue()
            ->count();

        // Calculate workload percentage (based on active tickets vs capacity)
        $estimatedCapacity = 20; // Assume 20 tickets is full capacity
        $workloadPercentage = min(100, ($currentlyAssigned / $estimatedCapacity) * 100);

        return [
            'total_assigned_ever' => $totalAssigned,
            'currently_assigned' => $currentlyAssigned,
            'resolved_this_period' => $resolvedThisPeriod,
            'overdue_tickets' => $overdueTickets,
            'workload_percentage' => round($workloadPercentage, 1),
            'workload_status' => $workloadPercentage > 90 ? 'overloaded' : ($workloadPercentage > 70 ? 'high' : 'normal'),

            // Resolution rate
            'resolution_rate' => $totalAssigned > 0
                ? round(($resolvedThisPeriod / max(1, min($totalAssigned, $resolvedThisPeriod + $currentlyAssigned))) * 100, 1)
                : 0,

            // Average resolution time for this period
            'avg_resolution_time_hours' => Ticket::where('assigned_teknisi_nip', $teknisiNip)
                ->where('status', Ticket::STATUS_RESOLVED)
                ->whereBetween('resolved_at', [$startDate, $endDate])
                ->whereNotNull('resolution_time_minutes')
                ->selectRaw('AVG(resolution_time_minutes) / 60 as avg_hours')
                ->value('avg_hours') ?? 0,
        ];
    }

    /**
     * Get assigned tickets overview.
     */
    private function getAssignedTicketsOverview(string $teknisiNip): array
    {
        // Count individual statuses
        $openCount = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_OPEN)
            ->count();
        $assignedStatusCount = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_ASSIGNED)
            ->count();
        $inProgressCount = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_IN_PROGRESS)
            ->count();
        $waitingResponseCount = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->whereIn('status', [Ticket::STATUS_WAITING_USER, Ticket::STATUS_WAITING_ADMIN])
            ->count();
        $resolvedCount = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->count();

        return [
            // "Assigned" = all active tickets (open + assigned status + in_progress)
            // This matches TicketHandlingController's assigned_tickets count
            'assigned' => $openCount + $assignedStatusCount + $inProgressCount,
            'open' => $openCount,
            'assigned_status' => $assignedStatusCount,
            'in_progress' => $inProgressCount,
            'waiting_response' => $waitingResponseCount,
            'resolved' => $resolvedCount,
            'urgent' => Ticket::where('assigned_teknisi_nip', $teknisiNip)
                ->where('priority', Ticket::PRIORITY_URGENT)
                ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS])
                ->count(),
            'overdue' => Ticket::where('assigned_teknisi_nip', $teknisiNip)
                ->overdue()
                ->count(),
            'needing_response' => Ticket::where('assigned_teknisi_nip', $teknisiNip)
                ->whereNull('first_response_at')
                ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS])
                ->count(),
        ];
    }

    /**
     * Get recent activity for the technician.
     */
    private function getRecentActivity(string $teknisiNip, int $limit = 10): array
    {
        $activities = [];

        // Recent tickets worked on
        $recentTickets = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->with(['user', 'aplikasi'])
            ->latest('updated_at')
            ->limit($limit)
            ->get();

        foreach ($recentTickets as $ticket) {
            $activities[] = [
                'type' => 'ticket_updated',
                'title' => "Updated ticket #{$ticket->ticket_number}",
                'description' => $ticket->title,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'user' => $ticket->user ? $ticket->user->name : 'Unknown',
                'application' => $ticket->aplikasi ? $ticket->aplikasi->name : 'Unknown',
                'date' => $ticket->updated_at,
                'formatted_date' => $ticket->updated_at->diffForHumans(),
                'icon' => 'ticket',
                'color' => 'blue',
            ];
        }

        // Sort by date (newest first)
        usort($activities, function ($a, $b) {
            return $b['date']->timestamp - $a['date']->timestamp;
        });

        return array_slice($activities, 0, $limit);
    }

    /**
     * Get performance metrics for the technician.
     */
    private function getPerformanceMetrics(string $teknisiNip, string $startDate, string $endDate): array
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $resolvedTickets = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->get();

        $totalResolved = $resolvedTickets->count();

        // Calculate average rating
        $avgRating = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereNotNull('user_rating')
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->avg('user_rating') ?? 0;

        // Calculate first response time average
        $avgFirstResponseTime = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->whereNotNull('first_response_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_minutes')
            ->value('avg_minutes') ?? 0;

        return [
            'total_resolved' => $totalResolved,
            'average_rating' => round($avgRating, 1),
            'avg_first_response_time_minutes' => round($avgFirstResponseTime),
            'resolution_trend' => $this->calculateResolutionTrend($teknisiNip, $startDate, $endDate),
            'rating_trend' => $this->calculateRatingTrend($teknisiNip, $startDate, $endDate),

            // Achievements/badges
            'achievements' => [
                'resolved_urgent_tickets' => Ticket::where('assigned_teknisi_nip', $teknisiNip)
                    ->where('priority', Ticket::PRIORITY_URGENT)
                    ->where('status', Ticket::STATUS_RESOLVED)
                    ->whereBetween('resolved_at', [$startDate, $endDate])
                    ->count(),
                'perfect_ratings' => Ticket::where('assigned_teknisi_nip', $teknisiNip)
                    ->where('user_rating', 5)
                    ->whereBetween('resolved_at', [$startDate, $endDate])
                    ->count(),
                'fast_responses' => Ticket::where('assigned_teknisi_nip', $teknisiNip)
                    ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, first_response_at) <= 30')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count(),
            ],
        ];
    }

    /**
     * Get application expertise statistics.
     */
    private function getApplicationExpertise(string $teknisiNip, string $startDate, string $endDate): array
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        return Ticket::select('aplikasi_id', DB::raw('count(*) as ticket_count'))
            ->where('assigned_teknisi_nip', $teknisiNip)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('aplikasi:id,name,code')
            ->groupBy('aplikasi_id')
            ->orderBy('ticket_count', 'desc')
            ->get()
            ->map(function ($ticket) use ($teknisiNip) {
                return [
                    'application' => [
                        'id' => $ticket->aplikasi->id,
                        'name' => $ticket->aplikasi->name,
                        'code' => $ticket->aplikasi->code,
                    ],
                    'ticket_count' => $ticket->ticket_count,
                    'resolved_count' => Ticket::where('assigned_teknisi_nip', $teknisiNip)
                        ->where('aplikasi_id', $ticket->aplikasi_id)
                        ->where('status', Ticket::STATUS_RESOLVED)
                        ->count(),
                ];
            })
            ->toArray();
    }

    /**
     * Get priority distribution of current tickets.
     */
    private function getPriorityDistribution(string $teknisiNip): array
    {
        $tickets = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING_USER, Ticket::STATUS_WAITING_ADMIN])
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get()
            ->pluck('count', 'priority')
            ->toArray();

        return [
            'urgent' => $tickets[Ticket::PRIORITY_URGENT] ?? 0,
            'high' => $tickets[Ticket::PRIORITY_HIGH] ?? 0,
            'medium' => $tickets[Ticket::PRIORITY_MEDIUM] ?? 0,
            'low' => $tickets[Ticket::PRIORITY_LOW] ?? 0,
            'total' => array_sum($tickets),
        ];
    }

    /**
     * Get SLA compliance statistics for the technician.
     */
    private function getSlaCompliance(string $teknisiNip, string $startDate, string $endDate): array
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $totalResolved = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->count();

        $withinSla = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->withinSla()
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->count();

        return [
            'total_resolved' => $totalResolved,
            'within_sla' => $withinSla,
            'sla_breached' => $totalResolved - $withinSla,
            'sla_compliance_rate' => $totalResolved > 0 ? round(($withinSla / $totalResolved) * 100, 1) : 0,

            // Priority-wise SLA performance
            'priority_sla_performance' => Ticket::select('priority', DB::raw('count(*) as total'))
                ->where('assigned_teknisi_nip', $teknisiNip)
                ->where('status', Ticket::STATUS_RESOLVED)
                ->whereBetween('resolved_at', [$startDate, $endDate])
                ->groupBy('priority')
                ->get()
                ->mapWithKeys(function ($item) use ($teknisiNip, $startDate, $endDate) {
                    $withinSlaCount = Ticket::where('assigned_teknisi_nip', $teknisiNip)
                        ->where('priority', $item->priority)
                        ->withinSla()
                        ->where('status', Ticket::STATUS_RESOLVED)
                        ->whereBetween('resolved_at', [$startDate, $endDate])
                        ->count();

                    return [
                        $item->priority => [
                            'total' => $item->total,
                            'within_sla' => $withinSlaCount,
                            'compliance_rate' => $item->total > 0 ? round(($withinSlaCount / $item->total) * 100, 1) : 0,
                        ]
                    ];
                })
                ->toArray(),
        ];
    }

    /**
     * Get upcoming deadlines for assigned tickets.
     */
    private function getUpcomingDeadlines(string $teknisiNip, int $limit = 5): array
    {
        return Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->whereNotNull('due_date')
            ->where('due_date', '>', Carbon::now())
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
            ->with(['user', 'aplikasi'])
            ->orderBy('due_date', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'priority' => $ticket->priority,
                    'priority_label' => $ticket->priority_label,
                    'user' => $ticket->user ? $ticket->user->name : 'Unknown',
                    'application' => $ticket->aplikasi ? $ticket->aplikasi->name : 'Unknown',
                    'due_date' => $ticket->due_date,
                    'formatted_due_date' => $ticket->formatted_due_date,
                    'days_until_due' => $ticket->days_until_due,
                    'is_urgent' => $ticket->priority === Ticket::PRIORITY_URGENT,
                ];
            })
            ->toArray();
    }

    /**
     * Get workload trend over time.
     */
    private function getWorkloadTrend(string $teknisiNip, string $startDate, string $endDate): array
    {
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $trend = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $assignedCount = Ticket::where('assigned_teknisi_nip', $teknisiNip)
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();

            $resolvedCount = Ticket::where('assigned_teknisi_nip', $teknisiNip)
                ->where('status', Ticket::STATUS_RESOLVED)
                ->whereBetween('resolved_at', [$dayStart, $dayEnd])
                ->count();

            $trend[] = [
                'date' => $currentDate->format('Y-m-d'),
                'assigned' => $assignedCount,
                'resolved' => $resolvedCount,
                'net_change' => $assignedCount - $resolvedCount,
            ];

            $currentDate->addDay();
        }

        return $trend;
    }

    /**
     * Calculate resolution trend (improving/declining performance).
     */
    private function calculateResolutionTrend(string $teknisiNip, Carbon $startDate, Carbon $endDate): string
    {
        $midPoint = $startDate->copy()->addDays($startDate->diffInDays($endDate) / 2);

        $firstHalf = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $midPoint])
            ->count();

        $secondHalf = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$midPoint, $endDate])
            ->count();

        if ($secondHalf > $firstHalf) {
            return 'improving';
        } elseif ($secondHalf < $firstHalf) {
            return 'declining';
        }

        return 'stable';
    }

    /**
     * Calculate rating trend.
     */
    private function calculateRatingTrend(string $teknisiNip, Carbon $startDate, Carbon $endDate): string
    {
        $midPoint = $startDate->copy()->addDays($startDate->diffInDays($endDate) / 2);

        $firstHalfAvg = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->whereNotNull('user_rating')
            ->whereBetween('resolved_at', [$startDate, $midPoint])
            ->avg('user_rating') ?? 0;

        $secondHalfAvg = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->whereNotNull('user_rating')
            ->whereBetween('resolved_at', [$midPoint, $endDate])
            ->avg('user_rating') ?? 0;

        if ($secondHalfAvg > $firstHalfAvg + 0.2) {
            return 'improving';
        } elseif ($secondHalfAvg < $firstHalfAvg - 0.2) {
            return 'declining';
        }

        return 'stable';
    }

    /**
     * Get dashboard statistics for API consumption.
     */
    public function getStats(Request $request)
    {
        $teknisi = Auth::user();
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        $stats = [
            'workload' => $this->getTeknisiWorkloadStats($teknisi->nip, $startDate, $endDate),
            'performance' => $this->getPerformanceMetrics($teknisi->nip, $startDate, $endDate),
            'sla' => $this->getSlaCompliance($teknisi->nip, $startDate, $endDate),
            'assignments' => $this->getAssignedTicketsOverview($teknisi->nip),
        ];

        return response()->json($stats);
    }
}