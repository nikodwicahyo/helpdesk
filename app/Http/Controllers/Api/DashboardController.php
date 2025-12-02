<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Notification;
use App\Models\User;
use App\Models\Teknisi;
use App\Models\AdminHelpdesk;
use App\Services\DashboardMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * @var DashboardMetricsService
     */
    protected $metricsService;

    /**
     * Constructor
     */
    public function __construct(DashboardMetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    /**
     * Get dashboard statistics for the authenticated user
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            $stats = [
                // Basic ticket counts
                'totalTickets' => $this->getTotalTickets($user),
                'openTickets' => $this->getOpenTickets($user),
                'inProgressTickets' => $this->getInProgressTickets($user),
                'resolvedTickets' => $this->getResolvedTickets($user),
                'closedTickets' => $this->getClosedTickets($user),

                // Priority and urgency
                'urgentTickets' => $this->getUrgentTickets($user),
                'overdueTickets' => $this->getOverdueTickets($user),

                // Time-based counts
                'todayTickets' => $this->getTodayTickets($user),
                'weeklyTickets' => $this->getWeeklyTickets($user),
                'monthlyTickets' => $this->getMonthlyTickets($user),

                // Performance metrics
                'avgResolutionTime' => $this->getAverageResolutionTime($user),
                'slaCompliance' => $this->getSLACompliance($user),

                // User-specific metrics
                'myTickets' => $this->getMyTickets($user),
                'myAssignedTickets' => $this->getMyAssignedTickets($user),
                'unreadNotifications' => $this->getUnreadNotifications($user),

                // System metrics (for admins)
                'activeTeknisi' => $this->getActiveTeknisi($user),
                'pendingAssignments' => $this->getPendingAssignments($user),

                // Additional stats based on role
                'roleSpecific' => $this->getRoleSpecificStats($user),

                // Metadata
                'lastUpdated' => now()->toISOString(),
                'userRole' => $this->getUserRole($user),
                'refreshInterval' => 30000, // 30 seconds
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch dashboard stats',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get total tickets count based on user role
     */
    private function getTotalTickets($user)
    {
        $query = Ticket::query();

        switch ($this->getUserRole($user)) {
            case 'admin_helpdesk':
                // Admin can see all tickets
                break;
            case 'teknisi':
                // Teknisi can see all tickets (for queue visibility)
                break;
            case 'admin_aplikasi':
                // Aplikasi admin can see tickets related to their applications
                if ($user->aplikasis) {
                    $query->whereIn('aplikasi_id', $user->aplikasis->pluck('id'));
                }
                break;
            case 'user':
            default:
                // Regular users can only see their own tickets
                $query->where('user_nip', $user->nip);
                break;
        }

        return $query->count();
    }

    /**
     * Get open tickets count
     */
    private function getOpenTickets($user)
    {
        return $this->getTicketsByStatus($user, 'open');
    }

    /**
     * Get in-progress tickets count
     */
    private function getInProgressTickets($user)
    {
        return $this->getTicketsByStatus($user, ['assigned', 'in_progress']);
    }

    /**
     * Get resolved tickets count
     */
    private function getResolvedTickets($user)
    {
        return $this->getTicketsByStatus($user, 'resolved');
    }

    /**
     * Get closed tickets count
     */
    private function getClosedTickets($user)
    {
        return $this->getTicketsByStatus($user, 'closed');
    }

    /**
     * Get tickets by status
     */
    private function getTicketsByStatus($user, $status)
    {
        $query = Ticket::query();

        if (is_array($status)) {
            $query->whereIn('status', $status);
        } else {
            $query->where('status', $status);
        }

        // Apply role-based filtering
        switch ($this->getUserRole($user)) {
            case 'admin_helpdesk':
                // Admin can see all
                break;
            case 'teknisi':
                // Teknisi can see all assigned tickets and unassigned tickets
                $query->where(function($q) use ($user) {
                    $q->whereNull('assigned_teknisi_nip')
                      ->orWhere('assigned_teknisi_nip', $user->nip);
                });
                break;
            case 'admin_aplikasi':
                if ($user->aplikasis) {
                    $query->whereIn('aplikasi_id', $user->aplikasis->pluck('id'));
                }
                break;
            case 'user':
            default:
                $query->where('user_nip', $user->nip);
                break;
        }

        return $query->count();
    }

    /**
     * Get urgent tickets count
     */
    private function getUrgentTickets($user)
    {
        $query = Ticket::where('priority', 'urgent');

        // Apply role-based filtering
        $this->applyRoleFilter($query, $user);

        return $query->count();
    }

    /**
     * Get overdue tickets count
     */
    private function getOverdueTickets($user)
    {
        $query = Ticket::where('created_at', '<', now()->subHours(24))
                       ->whereNotIn('status', ['resolved', 'closed']);

        // Apply role-based filtering
        $this->applyRoleFilter($query, $user);

        return $query->count();
    }

    /**
     * Get today's tickets count
     */
    private function getTodayTickets($user)
    {
        $query = Ticket::whereDate('created_at', today());

        // Apply role-based filtering
        $this->applyRoleFilter($query, $user);

        return $query->count();
    }

    /**
     * Get weekly tickets count
     */
    private function getWeeklyTickets($user)
    {
        $query = Ticket::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);

        // Apply role-based filtering
        $this->applyRoleFilter($query, $user);

        return $query->count();
    }

    /**
     * Get monthly tickets count
     */
    private function getMonthlyTickets($user)
    {
        $query = Ticket::whereMonth('created_at', now()->month)
                       ->whereYear('created_at', now()->year);

        // Apply role-based filtering
        $this->applyRoleFilter($query, $user);

        return $query->count();
    }

    /**
     * Get average resolution time in minutes
     */
    private function getAverageResolutionTime($user)
    {
        $query = Ticket::whereNotNull('resolved_at')
                       ->whereNotNull('created_at');

        // Apply role-based filtering
        $this->applyRoleFilter($query, $user);

        $tickets = $query->get(['created_at', 'resolved_at']);

        if ($tickets->isEmpty()) {
            return 0;
        }

        $totalMinutes = $tickets->sum(function ($ticket) {
            return $ticket->created_at->diffInMinutes($ticket->resolved_at);
        });

        return round($totalMinutes / $tickets->count(), 2);
    }

    /**
     * Get SLA compliance percentage
     */
    private function getSLACompliance($user)
    {
        $query = Ticket::whereNotNull('resolved_at')
                       ->whereNotNull('created_at');

        // Apply role-based filtering
        $this->applyRoleFilter($query, $user);

        $tickets = $query->get(['created_at', 'resolved_at', 'priority']);

        if ($tickets->isEmpty()) {
            return 0;
        }

        $slaThresholds = [
            'urgent' => 4 * 60,      // 4 hours
            'high' => 8 * 60,        // 8 hours
            'medium' => 24 * 60,     // 24 hours
            'low' => 72 * 60,        // 72 hours
        ];

        $compliantCount = 0;

        foreach ($tickets as $ticket) {
            $threshold = $slaThresholds[$ticket->priority] ?? 24 * 60;
            $resolutionTime = $ticket->created_at->diffInMinutes($ticket->resolved_at);

            if ($resolutionTime <= $threshold) {
                $compliantCount++;
            }
        }

        return round(($compliantCount / $tickets->count()) * 100, 2);
    }

    /**
     * Get user's own tickets
     */
    private function getMyTickets($user)
    {
        return Ticket::where('user_nip', $user->nip)->count();
    }

    /**
     * Get tickets assigned to the user (for teknisi)
     */
    private function getMyAssignedTickets($user)
    {
        if ($this->getUserRole($user) === 'teknisi') {
            return Ticket::where('assigned_teknisi_nip', $user->nip)
                        ->whereNotIn('status', ['resolved', 'closed'])
                        ->count();
        }

        return 0;
    }

    /**
     * Get unread notifications count
     */
    private function getUnreadNotifications($user)
    {
        return Notification::where('notifiable_type', get_class($user))
                          ->where('notifiable_id', $user->id)
                          ->whereNull('read_at')
                          ->count();
    }

    /**
     * Get active teknisi count (for admins)
     */
    private function getActiveTeknisi($user)
    {
        if (in_array($this->getUserRole($user), ['admin_helpdesk', 'admin_aplikasi'])) {
            return Teknisi::where('is_active', true)->count();
        }

        return 0;
    }

    /**
     * Get pending assignments count (for admins)
     */
    private function getPendingAssignments($user)
    {
        if (in_array($this->getUserRole($user), ['admin_helpdesk', 'admin_aplikasi'])) {
            return Ticket::where('status', 'open')->count();
        }

        return 0;
    }

    /**
     * Get role-specific statistics
     */
    private function getRoleSpecificStats($user)
    {
        $role = $this->getUserRole($user);

        switch ($role) {
            case 'admin_helpdesk':
                return $this->getAdminHelpdeskStats($user);
            case 'teknisi':
                return $this->getTeknisiStats($user);
            case 'admin_aplikasi':
                return $this->getAdminAplikasiStats($user);
            case 'user':
            default:
                return $this->getUserStats($user);
        }
    }

    /**
     * Get admin helpdesk specific stats
     */
    private function getAdminHelpdeskStats($user)
    {
        return [
            'totalUsers' => User::where('is_active', true)->count(),
            'totalTeknisi' => Teknisi::where('is_active', true)->count(),
            'ticketsByApplication' => $this->getTicketsByApplication(),
            'ticketsByCategory' => $this->getTicketsByCategory(),
            'ticketsByPriority' => $this->getTicketsByPriority(),
        ];
    }

    /**
     * Get teknisi specific stats
     */
    private function getTeknisiStats($user)
    {
        return [
            'assignedToMe' => Ticket::where('assigned_teknisi_nip', $user->nip)
                                  ->whereNotIn('status', ['resolved', 'closed'])
                                  ->count(),
            'resolvedByMe' => Ticket::where('assigned_teknisi_nip', $user->nip)
                                  ->where('status', 'resolved')
                                  ->count(),
            'myAvgResolutionTime' => $this->getTeknisiAvgResolutionTime($user),
            'mySLACompliance' => $this->getTeknisiSLACompliance($user),
        ];
    }

    /**
     * Get admin aplikasi specific stats
     */
    private function getAdminAplikasiStats($user)
    {
        $stats = [
            'managedApplications' => $user->aplikasis ? $user->aplikasis->count() : 0,
        ];

        if ($user->aplikasis) {
            $stats['applicationTickets'] = Ticket::whereIn('aplikasi_id', $user->aplikasis->pluck('id'))->count();
            $applicationCategories = DB::table('tickets')
                ->join('kategori_masalahs', 'tickets.kategori_masalah_id', '=', 'kategori_masalahs.id')
                ->whereIn('tickets.aplikasi_id', $user->aplikasis->pluck('id'))
                ->select('kategori_masalahs.name as nama_kategori', DB::raw('count(*) as count'))
                ->groupBy('kategori_masalahs.name')
                ->get();

            $stats['categoryBreakdown'] = $applicationCategories->pluck('count', 'nama_kategori');
        }

        return $stats;
    }

    /**
     * Get user specific stats
     */
    private function getUserStats($user)
    {
        return [
            'myOpenTickets' => Ticket::where('user_nip', $user->nip)
                                  ->where('status', 'open')
                                  ->count(),
            'myResolvedTickets' => Ticket::where('user_nip', $user->nip)
                                     ->where('status', 'resolved')
                                     ->count(),
            'myClosedTickets' => Ticket::where('user_nip', $user->nip)
                                    ->where('status', 'closed')
                                    ->count(),
        ];
    }

    /**
     * Helper method to apply role-based filtering
     */
    private function applyRoleFilter($query, $user)
    {
        switch ($this->getUserRole($user)) {
            case 'admin_helpdesk':
                // Admin can see all
                break;
            case 'teknisi':
                // Teknisi can see all tickets (for queue visibility)
                break;
            case 'admin_aplikasi':
                if ($user->aplikasis) {
                    $query->whereIn('aplikasi_id', $user->aplikasis->pluck('id'));
                }
                break;
            case 'user':
            default:
                $query->where('user_nip', $user->nip);
                break;
        }
    }

    /**
     * Helper method to get user role
     */
    private function getUserRole($user)
    {
        if ($user instanceof AdminHelpdesk) {
            return 'admin_helpdesk';
        } elseif ($user instanceof Teknisi) {
            return 'teknisi';
        } elseif (method_exists($user, 'isAdminAplikasi') && $user->isAdminAplikasi()) {
            return 'admin_aplikasi';
        } else {
            return 'user';
        }
    }

    /**
     * Get tickets by application breakdown
     */
    private function getTicketsByApplication()
    {
        return DB::table('tickets')
            ->join('aplikasis', 'tickets.aplikasi_id', '=', 'aplikasis.id')
            ->select('aplikasis.name as nama_aplikasi', DB::raw('count(*) as count'))
            ->groupBy('aplikasis.name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'nama_aplikasi');
    }

    /**
     * Get tickets by category breakdown
     */
    private function getTicketsByCategory()
    {
        return DB::table('tickets')
            ->join('kategori_masalahs', 'tickets.kategori_masalah_id', '=', 'kategori_masalahs.id')
            ->select('kategori_masalahs.name as nama_kategori', DB::raw('count(*) as count'))
            ->groupBy('kategori_masalahs.name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'nama_kategori');
    }

    /**
     * Get tickets by priority breakdown
     */
    private function getTicketsByPriority()
    {
        return DB::table('tickets')
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->orderBy('count', 'desc')
            ->pluck('count', 'priority');
    }

    /**
     * Get teknisi's average resolution time
     */
    private function getTeknisiAvgResolutionTime($user)
    {
        $tickets = Ticket::where('assigned_teknisi_nip', $user->nip)
                        ->whereNotNull('resolved_at')
                        ->whereNotNull('created_at')
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
     * Get teknisi's SLA compliance
     */
    private function getTeknisiSLACompliance($user)
    {
        $tickets = Ticket::where('assigned_teknisi_nip', $user->nip)
                        ->whereNotNull('resolved_at')
                        ->whereNotNull('created_at')
                        ->get(['created_at', 'resolved_at', 'priority']);

        if ($tickets->isEmpty()) {
            return 0;
        }

        $slaThresholds = [
            'urgent' => 4 * 60,
            'high' => 8 * 60,
            'medium' => 24 * 60,
            'low' => 72 * 60,
        ];

        $compliantCount = 0;

        foreach ($tickets as $ticket) {
            $threshold = $slaThresholds[$ticket->priority] ?? 24 * 60;
            $resolutionTime = $ticket->created_at->diffInMinutes($ticket->resolved_at);

            if ($resolutionTime <= $threshold) {
                $compliantCount++;
            }
        }

        return round(($compliantCount / $tickets->count()) * 100, 2);
    }

  
    /**
     * Get dashboard metrics using the new DashboardMetricsService
     */
    public function getDashboardMetrics(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            $role = $this->getUserRole($user);
            $userId = $user->getKey();

            // Use the DashboardMetricsService to calculate metrics
            $metrics = $this->metricsService->calculateMetrics($role, $userId);

            // Add additional context
            $metrics['user_role'] = $role;
            $metrics['user_id'] = $userId;
            $metrics['last_updated'] = now()->toISOString();
            $metrics['cache_ttl'] = DashboardMetricsService::CACHE_TTL;

            return response()->json([
                'success' => true,
                'metrics' => $metrics,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch dashboard metrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh dashboard metrics (invalidate cache and recalculate)
     */
    public function refreshMetrics(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            $role = $this->getUserRole($user);
            $userId = $user->getKey();

            // Invalidate cache for this user/role
            $this->metricsService->invalidateCache($role, $userId);

            // Recalculate metrics
            $metrics = $this->metricsService->calculateMetrics($role, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Dashboard metrics refreshed successfully',
                'metrics' => $metrics,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to refresh dashboard metrics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh stats for admin helpdesk.
     */
    public function refreshStats(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            $stats = [
                // Basic ticket counts
                'totalTickets' => $this->getTotalTickets($user),
                'openTickets' => $this->getOpenTickets($user),
                'inProgressTickets' => $this->getInProgressTickets($user),
                'resolvedTickets' => $this->getResolvedTickets($user),
                'closedTickets' => $this->getClosedTickets($user),

                // Priority and urgency
                'urgentTickets' => $this->getUrgentTickets($user),
                'overdueTickets' => $this->getOverdueTickets($user),

                // Time-based counts
                'todayTickets' => $this->getTodayTickets($user),
                'weeklyTickets' => $this->getWeeklyTickets($user),
                'monthlyTickets' => $this->getMonthlyTickets($user),

                // Performance metrics
                'avgResolutionTime' => $this->getAverageResolutionTime($user),
                'slaCompliance' => $this->getSLACompliance($user),

                // User-specific metrics
                'myTickets' => $this->getMyTickets($user),
                'myAssignedTickets' => $this->getMyAssignedTickets($user),
                'unreadNotifications' => $this->getUnreadNotifications($user),

                // System metrics (for admins)
                'activeTeknisi' => $this->getActiveTeknisi($user),
                'pendingAssignments' => $this->getPendingAssignments($user),

                // Additional stats based on role
                'roleSpecific' => $this->getRoleSpecificStats($user),

                // Metadata
                'lastUpdated' => now()->toISOString(),
                'userRole' => $this->getUserRole($user),
                'refreshInterval' => 30000, // 30 seconds
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
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

    /**
     * Refresh users for admin helpdesk.
     */
    public function refreshUsers(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        try {
            // Get user counts by role
            $userStats = [
                'totalUsers' => \App\Models\User::where('is_active', true)->count(),
                'totalTeknisi' => \App\Models\Teknisi::where('is_active', true)->count(),
                'totalAdminHelpdesk' => \App\Models\AdminHelpdesk::where('is_active', true)->count(),
                'totalAdminAplikasi' => \App\Models\AdminAplikasi::where('is_active', true)->count(),
            ];

            // Get recent users
            $recentUsers = \App\Models\User::latest()
                ->limit(10)
                ->get(['nip', 'name', 'department', 'created_at']);

            // Get recent teknisi
            $recentTeknisi = \App\Models\Teknisi::latest()
                ->limit(10)
                ->get(['nip', 'name', 'department', 'created_at']);

            return response()->json([
                'success' => true,
                'data' => [
                    'userStats' => $userStats,
                    'recentUsers' => $recentUsers,
                    'recentTeknisi' => $recentTeknisi,
                ],
                'timestamp' => now()->toISOString(),
                'cache_buster' => uniqid(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to refresh users data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's tickets count for admin dashboard polling
     */
    public function getTicketsToday(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $role = $this->getUserRole($user);
        if ($role !== 'admin_helpdesk') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();

            $todayCount = Ticket::whereDate('created_at', $today)->count();
            $yesterdayCount = Ticket::whereDate('created_at', $yesterday)->count();

            $trend = $yesterdayCount > 0 ? (($todayCount - $yesterdayCount) / $yesterdayCount) * 100 : 0;

            return response()->json([
                'success' => true,
                'count' => $todayCount,
                'trend' => round($trend, 1),
                'direction' => $trend > 0 ? 'up' : ($trend < 0 ? 'down' : 'neutral'),
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch tickets today',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unassigned tickets count for admin dashboard polling
     */
    public function getUnassignedTickets(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $role = $this->getUserRole($user);
        if ($role !== 'admin_helpdesk') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $count = Ticket::whereNull('assigned_teknisi_nip')
                ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED])
                ->count();

            return response()->json([
                'success' => true,
                'count' => $count,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch unassigned tickets',
                'message' => $e->getMessage()
            ], 500);
        }
    }


}
