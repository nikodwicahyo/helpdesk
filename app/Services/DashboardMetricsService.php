<?php

namespace App\Services;


use App\Models\Ticket;
use App\Models\User;
use App\Models\Teknisi;
use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardMetricsService
{
    /**
     * Cache TTL in seconds (5 minutes)
     */
    const CACHE_TTL = 300;

    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'dashboard_metrics_';

    /**
     * Calculate dashboard metrics for a role
     *
     * @param string $role
     * @param int|null $userId
     * @return array
     */
    public function calculateMetrics(string $role, ?int $userId = null): array
    {
        $cacheKey = $this->getCacheKey($role, $userId);

        // Try to get from cache first
        $metrics = Cache::get($cacheKey);
        if ($metrics) {
            return $metrics;
        }

        // Calculate metrics based on role
        switch ($role) {
            case 'admin_helpdesk':
                $metrics = $this->calculateAdminHelpdeskMetrics();
                break;
            case 'teknisi':
                $metrics = $this->calculateTeknisiMetrics($userId);
                break;
            case 'user':
                $metrics = $this->calculateUserMetrics($userId);
                break;
            case 'admin_aplikasi':
                $metrics = $this->calculateAdminAplikasiMetrics();
                break;
            default:
                $metrics = $this->calculateDefaultMetrics();
        }

        // Cache the metrics
        Cache::put($cacheKey, $metrics, self::CACHE_TTL);

        // Removed real-time broadcasting - metrics updates handled via HTTP polling
        // $this->broadcastMetricsUpdate($role, $metrics, 'metrics.calculated', $userId);

        return $metrics;
    }

    /**
     * Handle metrics update (broadcasting removed - using HTTP polling)
     *
     * @param string $role
     * @param array $metrics
     * @param string $source
     * @param int|null $userId
     * @return void
     */
    public function broadcastMetricsUpdate(string $role, array $metrics, string $source = 'system', ?int $userId = null): void
    {
        // Removed real-time broadcasting - metrics updates handled via HTTP polling
        // DashboardMetricsUpdated event dispatching has been removed

        // Invalidate cache when metrics change
        $this->invalidateCache($role, $userId);
    }

    /**
     * Calculate metrics delta between old and new metrics
     *
     * @param array $oldMetrics
     * @param array $newMetrics
     * @return array
     */
    public function getMetricsDelta(array $oldMetrics, array $newMetrics): array
    {
        $delta = [];

        foreach ($newMetrics as $key => $newValue) {
            $oldValue = $oldMetrics[$key] ?? 0;

            if (is_numeric($newValue) && is_numeric($oldValue)) {
                $change = $newValue - $oldValue;
                $trend = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable');
                $percentage = $oldValue > 0 ? ($change / $oldValue) * 100 : 0;

                $delta[$key] = [
                    'value' => $newValue,
                    'change' => $change,
                    'percentage_change' => $percentage,
                    'trend' => $trend,
                ];
            } else {
                $delta[$key] = [
                    'value' => $newValue,
                    'change' => 'updated',
                    'trend' => 'stable',
                ];
            }
        }

        return $delta;
    }

    /**
     * Get cached metrics if available
     *
     * @param string $role
     * @param int|null $userId
     * @return array|null
     */
    public function getCachedMetrics(string $role, ?int $userId = null): ?array
    {
        $cacheKey = $this->getCacheKey($role, $userId);
        return Cache::get($cacheKey);
    }

    /**
     * Invalidate cache for specific role/user
     *
     * @param string $role
     * @param int|null $userId
     * @return void
     */
    public function invalidateCache(string $role, ?int $userId = null): void
    {
        $cacheKey = $this->getCacheKey($role, $userId);
        Cache::forget($cacheKey);
    }

    /**
     * Calculate admin helpdesk metrics
     *
     * @return array
     */
    protected function calculateAdminHelpdeskMetrics(): array
    {
        $now = Carbon::now();
        $today = $now->startOfDay();
        $weekStart = $now->startOfWeek();
        $monthStart = $now->startOfMonth();

        // Use Redis cache for expensive calculations
        $cacheKey = 'admin_helpdesk_metrics_' . $now->format('Y-m-d-H');
        $cachedMetrics = Cache::get($cacheKey);

        if ($cachedMetrics) {
            return $cachedMetrics;
        }

        // Optimized queries using proper indexes
        $metrics = [
            'total_tickets' => Ticket::count(),
            'open_tickets' => Ticket::whereIn('status', ['open', 'assigned'])->count(),
            'in_progress_tickets' => Ticket::where('status', 'in_progress')->count(),
            'resolved_tickets' => Ticket::where('status', 'resolved')->count(),
            'closed_tickets' => Ticket::where('status', 'closed')->count(),
            'urgent_tickets' => Ticket::where('priority', 'urgent')->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
            'overdue_tickets' => Ticket::where('due_date', '<', $now)->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
            'today_tickets' => Ticket::where('created_at', '>=', $today)->count(),
            'weekly_tickets' => Ticket::where('created_at', '>=', $weekStart)->count(),
            'monthly_tickets' => Ticket::where('created_at', '>=', $monthStart)->count(),
            'avg_resolution_time' => $this->calculateAverageResolutionTime(),
            'sla_compliance' => $this->calculateSLACompliance(),
            'active_teknisi' => Teknisi::where('is_active', true)->count(),
            'pending_assignments' => Ticket::where('status', 'open')->count(),
            'unresolved_tickets_24h' => Ticket::where('created_at', '<', $now->subDay())->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
        ];

        // Cache for 1 hour
        Cache::put($cacheKey, $metrics, 3600);

        return $metrics;
    }

    /**
     * Calculate teknisi metrics
     *
     * @param int|null $teknisiId
     * @return array
     */
    protected function calculateTeknisiMetrics(?int $teknisiId): array
    {
        $query = Ticket::query();

        if ($teknisiId) {
            $query->where('assigned_teknisi_nip', $teknisiId);
        }

        $now = Carbon::now();
        $today = $now->startOfDay();

        return [
            'assigned_tickets' => $query->whereIn('status', ['assigned', 'in_progress'])->count(),
            'completed_tickets' => $query->where('status', 'resolved')->count(),
            'pending_tickets' => $query->where('status', 'assigned')->count(),
            'in_progress_tickets' => $query->where('status', 'in_progress')->count(),
            'today_completed' => $query->where('status', 'resolved')->whereDate('updated_at', $today)->count(),
            'avg_resolution_time' => $this->calculateAverageResolutionTime($teknisiId),
            'performance_rating' => $this->calculatePerformanceRating($teknisiId),
            'sla_compliance' => $this->calculateSLACompliance($teknisiId),
            'workload' => $this->calculateWorkload($teknisiId),
        ];
    }

    /**
     * Calculate user metrics
     *
     * @param int|null $userId
     * @return array
     */
    protected function calculateUserMetrics(?int $userId): array
    {
        $query = Ticket::query();

        if ($userId) {
            $query->where('user_nip', $userId);
        }

        $now = Carbon::now();
        $today = $now->startOfDay();

        return [
            'total_tickets' => $query->count(),
            'open_tickets' => $query->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
            'resolved_tickets' => $query->where('status', 'resolved')->count(),
            'closed_tickets' => $query->where('status', 'closed')->count(),
            'today_tickets' => $query->whereDate('created_at', $today)->count(),
            'avg_resolution_time' => $this->calculateAverageResolutionTimeForUser($userId),
            'satisfaction_rating' => $this->calculateSatisfactionRating($userId),
        ];
    }

    /**
     * Calculate admin aplikasi metrics
     *
     * @return array
     */
    protected function calculateAdminAplikasiMetrics(): array
    {
        return [
            'total_applications' => DB::table('aplikasis')->count(),
            'active_applications' => DB::table('aplikasis')->where('status', 'active')->count(),
            'maintenance_applications' => DB::table('aplikasis')->where('status', 'maintenance')->count(),
            'deprecated_applications' => DB::table('aplikasis')->where('status', 'deprecated')->count(),
            'total_categories' => DB::table('kategori_masalahs')->count(),
            'tickets_per_application' => $this->getTicketsPerApplication(),
            'top_categories' => $this->getTopCategories(),
        ];
    }

    /**
     * Calculate default metrics for unknown roles
     *
     * @return array
     */
    protected function calculateDefaultMetrics(): array
    {
        return [
            'total_tickets' => Ticket::count(),
            'open_tickets' => Ticket::whereIn('status', ['open', 'assigned'])->count(),
            'resolved_tickets' => Ticket::where('status', 'resolved')->count(),
        ];
    }

    /**
     * Calculate average resolution time
     *
     * @param int|null $teknisiId
     * @return float
     */
    protected function calculateAverageResolutionTime(?int $teknisiId = null): float
    {
        $cacheKey = 'avg_resolution_time' . ($teknisiId ? "_teknisi_{$teknisiId}" : '');
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        // Optimized query using raw SQL for better performance
        $query = DB::table('tickets')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_resolution_time')
            ->whereNotNull('resolved_at')
            ->whereNotNull('created_at')
            ->where('status', 'resolved');

        if ($teknisiId) {
            $query->where('assigned_teknisi_nip', $teknisiId);
        }

        $result = $query->first();
        $avgTime = $result->avg_resolution_time ? round($result->avg_resolution_time, 2) : 0.0;

        // Cache for 30 minutes
        Cache::put($cacheKey, $avgTime, 1800);

        return $avgTime;
    }

    /**
     * Calculate SLA compliance percentage
     *
     * @param int|null $teknisiId
     * @return float
     */
    protected function calculateSLACompliance(?int $teknisiId = null): float
    {
        $cacheKey = 'sla_compliance' . ($teknisiId ? "_teknisi_{$teknisiId}" : '');
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        // Optimized query using raw SQL
        $query = DB::table('tickets')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN resolved_at <= due_date THEN 1 ELSE 0 END) as compliant')
            ->whereNotNull('due_date')
            ->where('status', 'resolved');

        if ($teknisiId) {
            $query->where('assigned_teknisi_nip', $teknisiId);
        }

        $result = $query->first();
        $totalTickets = $result->total ?? 0;
        $compliantTickets = $result->compliant ?? 0;

        $compliance = $totalTickets > 0 ? round(($compliantTickets / $totalTickets) * 100, 2) : 100.0;

        // Cache for 30 minutes
        Cache::put($cacheKey, $compliance, 1800);

        return $compliance;
    }

    /**
     * Calculate performance rating for teknisi
     *
     * @param int|null $teknisiId
     * @return float
     */
    protected function calculatePerformanceRating(?int $teknisiId = null): float
    {
        $query = Ticket::where('status', 'resolved')
            ->whereNotNull('user_rating');

        if ($teknisiId) {
            $query->where('assigned_teknisi_nip', $teknisiId);
        }

        $ratings = $query->pluck('user_rating');

        if ($ratings->isEmpty()) {
            return 0.0;
        }

        return round($ratings->avg(), 2);
    }

    /**
     * Calculate workload for teknisi
     *
     * @param int|null $teknisiId
     * @return int
     */
    protected function calculateWorkload(?int $teknisiId): int
    {
        if (!$teknisiId) {
            return 0;
        }

        return Ticket::where('assigned_teknisi_nip', $teknisiId)
            ->whereIn('status', ['assigned', 'in_progress'])
            ->count();
    }

    /**
     * Calculate average resolution time for user
     *
     * @param int|null $userId
     * @return float
     */
    protected function calculateAverageResolutionTimeForUser(?int $userId): float
    {
        if (!$userId) {
            return 0;
        }

        $tickets = Ticket::where('user_nip', $userId)
            ->whereNotNull('resolved_at')
            ->get(['created_at', 'resolved_at']);

        if ($tickets->isEmpty()) {
            return 0;
        }

        $totalMinutes = $tickets->sum(function ($ticket) {
            return $ticket->created_at->diffInMinutes($ticket->resolved_at);
        });

        return round($totalMinutes / $tickets->count(), 2);
    }

    /**
     * Calculate satisfaction rating for user
     *
     * @param int|null $userId
     * @return float
     */
    protected function calculateSatisfactionRating(?int $userId): float
    {
        if (!$userId) {
            return 0;
        }

        $ratings = Ticket::where('user_nip', $userId)
            ->where('status', 'resolved')
            ->whereNotNull('user_rating')
            ->pluck('user_rating');

        if ($ratings->isEmpty()) {
            return 0;
        }

        return round($ratings->avg(), 2);
    }

    /**
     * Get tickets per application
     *
     * @return array
     */
    protected function getTicketsPerApplication(): array
    {
        $cacheKey = 'tickets_per_application';
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return $cached;
        }

        $result = DB::table('tickets')
            ->select(
                'aplikasis.id',
                'aplikasis.name as nama_aplikasi',
                'aplikasis.code as kode_aplikasi',
                DB::raw('count(*) as ticket_count'),
                DB::raw('count(DISTINCT kategori_masalahs.id) as categories_count')
            )
            ->leftJoin('aplikasis', 'tickets.aplikasi_id', '=', 'aplikasis.id')
            ->leftJoin('kategori_masalahs', 'tickets.kategori_masalah_id', '=', 'kategori_masalahs.id')
            ->groupBy('aplikasis.id', 'aplikasis.name', 'aplikasis.code')
            ->orderBy('ticket_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        // Cache for 15 minutes
        Cache::put($cacheKey, $result, 900);

        return $result;
    }

    /**
     * Get top categories
     *
     * @return array
     */
    protected function getTopCategories(): array
    {
        $cacheKey = 'top_categories';
        $cached = Cache::get($cacheKey);

        if ($cached) {
            return $cached;
        }

        $result = DB::table('tickets')
            ->select(
                'kategori_masalahs.id',
                'kategori_masalahs.name as nama_kategori',
                'kategori_masalahs.aplikasi_id',
                DB::raw('count(*) as ticket_count')
            )
            ->leftJoin('kategori_masalahs', 'tickets.kategori_masalah_id', '=', 'kategori_masalahs.id')
            ->groupBy('kategori_masalahs.id', 'kategori_masalahs.name', 'kategori_masalahs.aplikasi_id')
            ->orderBy('ticket_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();

        // Cache for 15 minutes
        Cache::put($cacheKey, $result, 900);

        return $result;
    }

    /**
     * Generate cache key
     *
     * @param string $role
     * @param int|null $userId
     * @return string
     */
    protected function getCacheKey(string $role, ?int $userId = null): string
    {
        $key = self::CACHE_PREFIX . $role;
        if ($userId) {
            $key .= '_' . $userId;
        }
        return $key;
    }

    /**
     * Determine if metrics should be broadcast based on significance of change
     *
     * @param string $role
     * @param array $newMetrics
     * @return bool
     */
    protected function shouldBroadcastMetrics(string $role, array $newMetrics): bool
    {
        $oldMetrics = $this->getCachedMetrics($role);

        if (!$oldMetrics) {
            return true; // Always broadcast if no cached data
        }

        foreach ($newMetrics as $key => $newValue) {
            $oldValue = $oldMetrics[$key] ?? 0;

            if (is_numeric($newValue) && is_numeric($oldValue) && $oldValue > 0) {
                $percentageChange = abs(($newValue - $oldValue) / $oldValue) * 100;

                // Broadcast if change is more than 5%
                if ($percentageChange > 5) {
                    return true;
                }
            }
        }

        return false;
    }
}
