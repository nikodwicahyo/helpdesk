<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Ticket;
use App\Models\User;
use App\Models\AdminHelpdesk;
use App\Models\Teknisi;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use Carbon\Carbon;

class CalculateDashboardMetrics implements ShouldQueue
{
    use Queueable;

    protected $dashboardType;
    protected $userId;
    protected $startDate;
    protected $endDate;

    /**
     * Create a new job instance.
     */
    public function __construct(string $dashboardType, ?int $userId = null, ?string $startDate = null, ?string $endDate = null)
    {
        $this->dashboardType = $dashboardType;
        $this->userId = $userId;
        $this->startDate = $startDate ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $this->endDate = $endDate ?? Carbon::now()->format('Y-m-d');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        switch ($this->dashboardType) {
            case 'admin':
                $this->calculateAdminMetrics();
                break;
            case 'teknisi':
                $this->calculateTeknisiMetrics();
                break;
            case 'user':
                $this->calculateUserMetrics();
                break;
        }
    }

    /**
     * Calculate admin dashboard metrics.
     */
    private function calculateAdminMetrics(): void
    {
        $stats = [
            'tickets' => [
                'total' => Ticket::count(),
                'open' => Ticket::where('status', Ticket::STATUS_OPEN)->count(),
                'in_progress' => Ticket::where('status', Ticket::STATUS_IN_PROGRESS)->count(),
                'waiting_response' => Ticket::where('status', Ticket::STATUS_WAITING_RESPONSE)->count(),
                'resolved' => Ticket::where('status', Ticket::STATUS_RESOLVED)->count(),
                'closed' => Ticket::where('status', Ticket::STATUS_CLOSED)->count(),
                'overdue' => Ticket::overdue()->count(),
                'escalated' => Ticket::escalated()->count(),
                'unassigned' => Ticket::unassigned()->count(),
            ],
            'users' => [
                'total' => User::count(),
                'active' => User::active()->count(),
                'inactive' => User::where('status', 'inactive')->count(),
            ],
            'teknisi' => [
                'total' => Teknisi::count(),
                'active' => Teknisi::active()->count(),
                'inactive' => Teknisi::where('status', 'inactive')->count(),
            ],
            'applications' => [
                'total' => Aplikasi::count(),
                'active' => Aplikasi::active()->count(),
            ],
            'categories' => [
                'total' => KategoriMasalah::count(),
                'active' => KategoriMasalah::active()->count(),
            ],
            'tickets_today' => (int) Ticket::whereDate('created_at', Carbon::today())->count(),
            'unassigned_tickets' => (int) Ticket::unassigned()->count(),
            'in_progress_tickets' => (int) Ticket::where('status', Ticket::STATUS_IN_PROGRESS)->count(),
            'resolved_today' => (int) Ticket::where('status', Ticket::STATUS_RESOLVED)
                ->whereDate('resolved_at', Carbon::today())->count(),
            'avg_resolution_time' => (float) (Ticket::where('status', Ticket::STATUS_RESOLVED)
                ->whereNotNull('resolution_time_minutes')
                ->whereDate('resolved_at', '>=', Carbon::now()->subDays(30))
                ->avg('resolution_time_minutes') ?? 0),
        ];

        Cache::put('admin_dashboard_stats', $stats, 300);
    }

    /**
     * Calculate teknisi dashboard metrics.
     */
    private function calculateTeknisiMetrics(): void
    {
        if (!$this->userId) return;

        $teknisi = Teknisi::find($this->userId);
        if (!$teknisi) return;

        $startDate = Carbon::parse($this->startDate)->startOfDay();
        $endDate = Carbon::parse($this->endDate)->endOfDay();

        // Workload stats
        $workloadStats = $this->getTeknisiWorkloadStats($teknisi->nip, $startDate, $endDate);
        Cache::put("teknisi_workload_{$teknisi->nip}_{$this->startDate}_{$this->endDate}", $workloadStats, 300);

        // Performance metrics
        $performanceMetrics = $this->getPerformanceMetrics($teknisi->nip, $startDate, $endDate);
        Cache::put("teknisi_performance_{$teknisi->nip}_{$this->startDate}_{$this->endDate}", $performanceMetrics, 300);

        // Application expertise
        $applicationExpertise = $this->getApplicationExpertise($teknisi->nip, $startDate, $endDate);
        Cache::put("teknisi_application_expertise_{$teknisi->nip}_{$this->startDate}_{$this->endDate}", $applicationExpertise, 300);
    }

    /**
     * Calculate user dashboard metrics.
     */
    private function calculateUserMetrics(): void
    {
        if (!$this->userId) return;

        $user = User::find($this->userId);
        if (!$user) return;

        // User stats
        $stats = $this->getUserDashboardStats($user);
        Cache::put("user_dashboard_stats_{$user->id}", $stats, 300);

        // Chart stats
        $chartStats = $this->getUserChartStats($user);
        Cache::put("user_chart_stats_{$user->id}", $chartStats, 300);
    }

    /**
     * Get teknisi workload stats (extracted from controller).
     */
    private function getTeknisiWorkloadStats(string $teknisiNip, Carbon $startDate, Carbon $endDate): array
    {
        $totalAssigned = Ticket::where('assigned_teknisi_nip', $teknisiNip)->count();
        $currentlyAssigned = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING_RESPONSE])
            ->count();
        $resolvedThisPeriod = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->count();

        $estimatedCapacity = 20;
        $workloadPercentage = min(100, ($currentlyAssigned / $estimatedCapacity) * 100);

        return [
            'total_assigned_ever' => $totalAssigned,
            'currently_assigned' => $currentlyAssigned,
            'resolved_this_period' => $resolvedThisPeriod,
            'overdue_tickets' => Ticket::where('assigned_teknisi_nip', $teknisiNip)->overdue()->count(),
            'workload_percentage' => round($workloadPercentage, 1),
            'workload_status' => $workloadPercentage > 90 ? 'overloaded' : ($workloadPercentage > 70 ? 'high' : 'normal'),
            'resolution_rate' => $totalAssigned > 0
                ? round(($resolvedThisPeriod / max(1, min($totalAssigned, $resolvedThisPeriod + $currentlyAssigned))) * 100, 1)
                : 0,
            'avg_resolution_time_hours' => Ticket::where('assigned_teknisi_nip', $teknisiNip)
                ->where('status', Ticket::STATUS_RESOLVED)
                ->whereBetween('resolved_at', [$startDate, $endDate])
                ->whereNotNull('resolution_time_minutes')
                ->selectRaw('AVG(resolution_time_minutes) / 60 as avg_hours')
                ->value('avg_hours') ?? 0,
        ];
    }

    /**
     * Get performance metrics (extracted from controller).
     */
    private function getPerformanceMetrics(string $teknisiNip, Carbon $startDate, Carbon $endDate): array
    {
        $resolvedTickets = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->get();

        $totalResolved = $resolvedTickets->count();
        $avgRating = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereNotNull('user_rating')
            ->whereBetween('resolved_at', [$startDate, $endDate])
            ->avg('user_rating') ?? 0;

        return [
            'total_resolved' => $totalResolved,
            'average_rating' => round($avgRating, 1),
            'avg_first_response_time_minutes' => Ticket::where('assigned_teknisi_nip', $teknisiNip)
                ->whereNotNull('first_response_at')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_minutes')
                ->value('avg_minutes') ?? 0,
        ];
    }

    /**
     * Get application expertise (extracted from controller).
     */
    private function getApplicationExpertise(string $teknisiNip, Carbon $startDate, Carbon $endDate): array
    {
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
     * Get user dashboard stats (extracted from controller).
     */
    private function getUserDashboardStats($user): array
    {
        if (!$user instanceof User) {
            return [
                'total_tickets' => 0,
                'active_tickets' => 0,
                'resolved_tickets' => 0,
                'closed_tickets' => 0,
                'resolution_rate' => 0,
                'avg_resolution_time' => null,
                'tickets_this_month' => 0,
                'unread_notifications' => 0,
            ];
        }

        $ticketStats = $user->tickets()
            ->selectRaw('
                COUNT(*) as total_tickets,
                COUNT(CASE WHEN status IN (?, ?, ?) THEN 1 END) as active_tickets,
                COUNT(CASE WHEN status = ? AND resolved_at >= ? THEN 1 END) as resolved_tickets,
                COUNT(CASE WHEN status = ? AND closed_at >= ? THEN 1 END) as closed_tickets,
                COUNT(CASE WHEN status IN (?, ?) THEN 1 END) as resolved_total,
                AVG(CASE WHEN status = ? AND resolution_time_minutes IS NOT NULL THEN resolution_time_minutes END) as avg_resolution_time,
                COUNT(CASE WHEN MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 END) as tickets_this_month
            ', [
                Ticket::STATUS_OPEN,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_WAITING_RESPONSE,
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
        $resolvedTotal = $ticketStats->resolved_total;
        $resolutionRate = $totalTickets > 0 ? round(($resolvedTotal / $totalTickets) * 100, 2) : 0;

        return [
            'total_tickets' => $totalTickets,
            'active_tickets' => $ticketStats->active_tickets,
            'resolved_tickets' => $ticketStats->resolved_tickets,
            'closed_tickets' => $ticketStats->closed_tickets,
            'resolution_rate' => $resolutionRate,
            'avg_resolution_time' => $ticketStats->avg_resolution_time ? round($ticketStats->avg_resolution_time, 2) : null,
            'tickets_this_month' => $ticketStats->tickets_this_month,
            'unread_notifications' => $user->unread_notifications_count ?? 0,
        ];
    }

    /**
     * Get user chart stats (extracted from controller).
     */
    private function getUserChartStats($user): array
    {
        if (!$user instanceof User) {
            return [
                'status_distribution' => [],
                'priority_distribution' => [],
                'monthly_trend' => [],
                'application_stats' => [],
                'resolution_trend' => [],
                'weekly_activity' => [],
            ];
        }

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

        $weeklyActivity = $user->tickets()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        return [
            'status_distribution' => $statusStats,
            'priority_distribution' => $priorityStats,
            'monthly_trend' => $monthlyStats,
            'application_stats' => $applicationStats,
            'resolution_trend' => $resolutionTrend,
            'weekly_activity' => $weeklyActivity,
        ];
    }
}
