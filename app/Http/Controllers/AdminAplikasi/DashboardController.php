<?php

namespace App\Http\Controllers\AdminAplikasi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Ticket;
use App\Models\AdminAplikasi;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\Teknisi;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the admin aplikasi dashboard.
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

        // Get application IDs assigned to this admin from aplikasis table
        $assignedAppIds = Aplikasi::where(function($q) use ($admin) {
            $q->where('admin_aplikasi_nip', $admin->nip)
              ->orWhere('backup_admin_nip', $admin->nip);
        })->pluck('id')->toArray();

        // Cache key specific to this admin
        $cacheKey = 'admin_aplikasi_dashboard_' . $nip . '_' . Carbon::today()->format('Y-m-d');

        $stats = Cache::remember($cacheKey, 300, function () use ($assignedAppIds) {
            // Application statistics (scoped to assigned apps)
            $totalApplications = Aplikasi::whereIn('id', $assignedAppIds)->count();
            
            $activeApplications = Aplikasi::whereIn('id', $assignedAppIds)
                ->where('status', 'active')
                ->count();
                
            $maintenanceApplications = Aplikasi::whereIn('id', $assignedAppIds)
                ->where('status', 'maintenance')
                ->count();

            // Get app IDs for ticket queries (same as assignedAppIds)
            $appIds = $assignedAppIds;

            // Category statistics
            $totalCategories = KategoriMasalah::whereIn('aplikasi_id', $appIds)->count();
            $activeCategories = KategoriMasalah::whereIn('aplikasi_id', $appIds)->where('status', 'active')->count();

            // Ticket statistics for this month
            $ticketsThisMonth = Ticket::whereIn('aplikasi_id', $appIds)
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();

            $ticketsLastMonth = Ticket::whereIn('aplikasi_id', $appIds)
                ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->whereYear('created_at', Carbon::now()->subMonth()->year)
                ->count();

            // Ticket trends
            $ticketsTrend = $ticketsLastMonth > 0 
                ? round((($ticketsThisMonth - $ticketsLastMonth) / $ticketsLastMonth) * 100, 1) 
                : ($ticketsThisMonth > 0 ? 100 : 0);

            return [
                'total_applications' => (int) $totalApplications,
                'active_applications' => (int) $activeApplications,
                'maintenance_applications' => (int) $maintenanceApplications,
                'total_categories' => (int) $totalCategories,
                'active_categories' => (int) $activeCategories,
                'tickets_this_month' => (int) $ticketsThisMonth,
                'tickets_trend' => (float) $ticketsTrend,
                'applications_trend' => 0,
            ];
        });

        // Get applications list with stats
        $applications = $this->getApplicationsList($admin, $assignedAppIds);

        // Get recent categories
        $recentCategories = $this->getRecentCategories($admin, $assignedAppIds);

        // Get top applications by ticket count
        $topApplications = $this->getTopApplications($admin, $assignedAppIds);

        // Get assigned teknisi list
        $teknisiList = $this->getTeknisiList($admin, $assignedAppIds);

        // Get recent activity
        $recentActivity = $this->getRecentActivity($admin, $assignedAppIds);

        // Get system status
        $systemStatus = $this->getSystemStatus($admin, $assignedAppIds);

        return Inertia::render('AdminAplikasi/Dashboard', [
            'stats' => $stats,
            'applications' => $applications,
            'recentCategories' => $recentCategories,
            'topApplications' => $topApplications,
            'teknisiList' => $teknisiList,
            'recentActivity' => $recentActivity,
            'systemStatus' => $systemStatus,
            'admin' => [
                'nip' => $admin->nip,
                'name' => $admin->name,
                'email' => $admin->email,
                'department' => $admin->department,
            ],
        ]);
    }

    /**
     * Get applications list for this admin.
     */
    private function getApplicationsList(AdminAplikasi $admin, array $assignedAppIds): array
    {
        if (empty($assignedAppIds)) {
            return [];
        }

        $query = Aplikasi::withCount(['tickets', 'kategoriMasalahs'])
            ->whereIn('id', $assignedAppIds);

        return $query->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'nama_aplikasi' => $app->name,
                    'kode_aplikasi' => $app->code,
                    'deskripsi' => $app->description,
                    'status' => $app->status,
                    'status_label' => $app->status_label,
                    'kategori' => $app->category,
                    'kategori_label' => $app->category_label,
                    'tickets_count' => $app->tickets_count ?? 0,
                    'categories_count' => $app->kategori_masalahs_count ?? 0, // Standardized field name
                    'kategori_masalahs_count' => $app->kategori_masalahs_count ?? 0, // Keep backward compatibility
                ];
            })
            ->toArray();
    }

    /**
     * Get recent categories for assigned applications.
     */
    private function getRecentCategories(AdminAplikasi $admin, array $assignedAppIds): array
    {
        if (empty($assignedAppIds)) {
            return [];
        }

        return KategoriMasalah::with('aplikasi')
            ->whereIn('aplikasi_id', $assignedAppIds)
            ->withCount('tickets')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'nama_kategori' => $category->name,
                    'deskripsi' => $category->description,
                    'status' => $category->status,
                    'tickets_count' => $category->tickets_count,
                    'aplikasi' => [
                        'id' => $category->aplikasi->id ?? null,
                        'nama_aplikasi' => $category->aplikasi->name ?? 'Unknown',
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * Get top applications by ticket count.
     */
    private function getTopApplications(AdminAplikasi $admin, array $assignedAppIds): array
    {
        if (empty($assignedAppIds)) {
            return [];
        }

        $query = Aplikasi::withCount('tickets')
            ->whereIn('id', $assignedAppIds);

        return $query->orderBy('tickets_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'nama_aplikasi' => $app->name,
                    'kode_aplikasi' => $app->code,
                    'kategori_label' => $app->category_label,
                    'tickets_count' => $app->tickets_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get teknisi list assigned to managed applications.
     */
    private function getTeknisiList(AdminAplikasi $admin, array $assignedAppIds): array
    {
        if (empty($assignedAppIds)) {
            return [];
        }

        // Get teknisi who have handled tickets for these applications
        $teknisiNips = Ticket::whereIn('aplikasi_id', $assignedAppIds)
            ->whereNotNull('assigned_teknisi_nip')
            ->distinct()
            ->pluck('assigned_teknisi_nip')
            ->toArray();

        return Teknisi::whereIn('nip', $teknisiNips)
            ->where('status', 'active')
            ->withCount(['assignedTickets as active_tickets_count' => function ($query) use ($assignedAppIds) {
                $query->whereIn('aplikasi_id', $assignedAppIds)
                    ->whereIn('status', [Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS]);
            }])
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(function ($teknisi) {
                return [
                    'id' => $teknisi->nip,
                    'nama_lengkap' => $teknisi->name,
                    'keahlian' => $teknisi->department ?? $teknisi->keahlian ?? 'General',
                    'active_tickets_count' => $teknisi->active_tickets_count,
                    'rating' => $teknisi->rating ?? 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get recent activity for assigned applications.
     */
    private function getRecentActivity(AdminAplikasi $admin, array $assignedAppIds): array
    {
        if (empty($assignedAppIds)) {
            return [];
        }

        // Get recent tickets as activity
        $recentTickets = Ticket::whereIn('aplikasi_id', $assignedAppIds)
            ->with(['aplikasi', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $activities = [];
        foreach ($recentTickets as $ticket) {
            $activities[] = [
                'id' => $ticket->id,
                'type' => 'ticket_created',
                'description' => "New ticket '{$ticket->title}' created for " . ($ticket->aplikasi->name ?? 'Unknown App'),
                'formatted_created_at' => $ticket->created_at->diffForHumans(),
            ];
        }

        return $activities;
    }

    /**
     * Get system status for assigned applications.
     */
    private function getSystemStatus(AdminAplikasi $admin, array $assignedAppIds): array
    {
        if (empty($assignedAppIds)) {
            return [
                'total_applications' => 0,
                'online_applications' => 0,
                'maintenance_applications' => 0,
                'deprecated_applications' => 0,
            ];
        }

        $query = Aplikasi::whereIn('id', $assignedAppIds);

        $total = (clone $query)->count();
        $active = (clone $query)->where('status', 'active')->count();
        $maintenance = (clone $query)->where('status', 'maintenance')->count();
        $deprecated = (clone $query)->where('status', 'deprecated')->count();

        return [
            'total_applications' => $total,
            'online_applications' => $active,
            'maintenance_applications' => $maintenance,
            'deprecated_applications' => $deprecated,
        ];
    }

    /**
     * Refresh dashboard statistics.
     */
    public function refreshStats(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Clear cache for this admin
        $cacheKey = 'admin_aplikasi_dashboard_' . $nip . '_' . Carbon::today()->format('Y-m-d');
        Cache::forget($cacheKey);

        return response()->json([
            'success' => true, 
            'message' => 'Dashboard stats refreshed',
        ]);
    }
}
