<?php

namespace App\Http\Controllers\AdminHelpdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\KategoriMasalah;
use App\Models\Aplikasi;
use App\Models\Ticket;
use App\Models\AdminAplikasi;
use Carbon\Carbon;
use App\Services\AuditLogService;

class CategoriesManagementController extends Controller
{
    /**
     * Display a listing of all categories for admin helpdesk oversight.
     */
    public function index(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_helpdesk') {
            return redirect()->route('login')->withErrors(['Access denied. Invalid session.']);
        }

        // Get filter parameters
        $filters = $request->only(['aplikasi_id', 'status', 'search', 'sort_by', 'sort_direction']);

        // Set default sorting
        if (!isset($filters['sort_by'])) {
            $filters['sort_by'] = 'name';
            $filters['sort_direction'] = 'asc';
        }

        // Get all categories (admin helpdesk can see all)
        $categories = $this->getFilteredCategories($filters, 20);

        // Format categories for frontend
        $formattedCategories = $categories->getCollection()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'status' => $category->status,
                'status_badge' => $category->status_badge,
                'aplikasi_id' => $category->aplikasi_id,
                'aplikasi' => $category->aplikasi ? [
                    'id' => $category->aplikasi->id,
                    'name' => $category->aplikasi->name,
                    'code' => $category->aplikasi->code,
                    'status' => $category->aplikasi->status,
                ] : null,
                'admin_aplikasi' => $category->aplikasi?->adminAplikasi ? [
                    'nip' => $category->aplikasi->adminAplikasi->nip,
                    'name' => $category->aplikasi->adminAplikasi->name,
                ] : null,
                'total_tickets' => $category->tickets()->count(),
                'open_tickets' => $category->tickets()->where('status', Ticket::STATUS_OPEN)->count(),
                'in_progress_tickets' => $category->tickets()->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
                'resolved_tickets' => $category->tickets()->where('status', Ticket::STATUS_RESOLVED)->count(),
                'avg_resolution_time' => $this->calculateAvgResolutionTime($category),
                'created_at' => $category->created_at,
                'formatted_created_at' => $category->created_at->format('d M Y'),
                'last_ticket_activity' => $category->tickets()->latest('updated_at')->first()?->updated_at?->format('d M Y H:i'),
            ];
        });

        // Create new paginator with formatted data
        $formattedPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $formattedCategories,
            $categories->total(),
            $categories->perPage(),
            $categories->currentPage(),
            ['path' => $categories->path(), 'pageName' => 'page']
        );

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        // Get applications for modal dropdown
        $applications = Aplikasi::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // Get statistics for overview
        $stats = $this->getCategoriesStats();

        return Inertia::render('AdminHelpdesk/CategoriesManagement', [
            'categories' => $formattedPaginator,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'applications' => $applications,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_helpdesk') {
            return response()->json([
                'success' => false,
                'errors' => ['Access denied'],
            ], 403);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'aplikasi_id' => 'required|exists:aplikasis,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            // Create new category
            $category = KategoriMasalah::create([
                'name' => $request->name,
                'description' => $request->description,
                'aplikasi_id' => $request->aplikasi_id,
                'status' => $request->status,
            ]);

            // Load application data for response
            $category->load('aplikasi');

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'status' => $category->status,
                    'status_badge' => $category->status_badge,
                    'aplikasi' => [
                        'id' => $category->aplikasi->id,
                        'name' => $category->aplikasi->name,
                        'code' => $category->aplikasi->code,
                    ],
                    'created_at' => $category->created_at->format('d M Y'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to create category: ' . $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, $id)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_helpdesk') {
            return response()->json([
                'success' => false,
                'errors' => ['Access denied'],
            ], 403);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'aplikasi_id' => 'required|exists:aplikasis,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            $category = KategoriMasalah::findOrFail($id);

            // Update category data
            $category->update([
                'name' => $request->name,
                'description' => $request->description,
                'aplikasi_id' => $request->aplikasi_id,
                'status' => $request->status,
            ]);

            // Load application data for response
            $category->load('aplikasi');

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'status' => $category->status,
                    'status_badge' => $category->status_badge,
                    'aplikasi' => [
                        'id' => $category->aplikasi->id,
                        'name' => $category->aplikasi->name,
                        'code' => $category->aplikasi->code,
                    ],
                    'updated_at' => $category->updated_at->format('d M Y H:i'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Category not found or failed to update'],
            ], 404);
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy($id)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_helpdesk') {
            return response()->json([
                'success' => false,
                'errors' => ['Access denied'],
            ], 403);
        }

        try {
            $category = KategoriMasalah::findOrFail($id);

            // Check if category has tickets
            $ticketCount = $category->tickets()->count();
            if ($ticketCount > 0) {
                return response()->json([
                    'success' => false,
                    'errors' => ["Cannot delete category with {$ticketCount} existing tickets"],
                ], 422);
            }

            // Delete category
            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Category not found'],
            ], 404);
        }
    }

    /**
     * Perform bulk actions on categories.
     */
    public function bulkAction(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_helpdesk') {
            return response()->json([
                'success' => false,
                'errors' => ['Access denied'],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,delete',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'integer|exists:kategori_masalahs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            $action = $request->action;
            $categoryIds = $request->category_ids;
            $affectedCount = 0;

            switch ($action) {
                case 'activate':
                    KategoriMasalah::whereIn('id', $categoryIds)->update(['status' => 'active']);
                    $affectedCount = count($categoryIds);
                    $message = "Activated {$affectedCount} categories";
                    $auditAction = 'bulk_activated';
                    break;

                case 'deactivate':
                    KategoriMasalah::whereIn('id', $categoryIds)->update(['status' => 'inactive']);
                    $affectedCount = count($categoryIds);
                    $message = "Deactivated {$affectedCount} categories";
                    $auditAction = 'bulk_deactivated';
                    break;

                case 'delete':
                    // Check for categories with tickets
                    $categoriesWithTickets = KategoriMasalah::whereIn('id', $categoryIds)
                        ->whereHas('tickets')
                        ->pluck('id')
                        ->toArray();

                    if (!empty($categoriesWithTickets)) {
                        return response()->json([
                            'success' => false,
                            'errors' => ['Cannot delete categories with existing tickets'],
                        ], 422);
                    }

                    KategoriMasalah::whereIn('id', $categoryIds)->delete();
                    $affectedCount = count($categoryIds);
                    $message = "Deleted {$affectedCount} categories";
                    $auditAction = 'bulk_deleted';
                    break;
            }

            // Log bulk action
            AuditLogService::logBulkCategoryAction($auditAction, $categoryIds, [
                'action' => $action,
                'message' => $message,
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'affected_count' => $affectedCount,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to perform bulk action: ' . $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Export categories data.
     */
    public function export(Request $request)
    {
        // Check authentication
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_helpdesk') {
            // Return CSV error instead of HTML error page
            $filename = 'export_error_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () {
                if (ob_get_level()) {
                    ob_end_clean();
                }
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Error', 'Access denied - Invalid session or permissions']);
                fclose($file);
            };

            return response()->stream($callback, 403, $headers);
        }

        $filters = $request->only(['aplikasi_id', 'status', 'search']);

        $categories = KategoriMasalah::with(['aplikasi.adminAplikasi'])
            ->when(!empty($filters['aplikasi_id']), function ($query) use ($filters) {
                $query->where('aplikasi_id', $filters['aplikasi_id']);
            })
            ->when(!empty($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', "%{$filters['search']}%")
                      ->orWhere('description', 'like', "%{$filters['search']}%");
                });
            })
            ->orderBy('name')
            ->get();

        $recordCount = $categories->count();
        
        // Log the export operation
        AuditLogService::logDataExported('KategoriMasalah', $recordCount, $filters);
        
        $filename = 'categories_export_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $callback = function () use ($categories) {
            // Clear any existing output buffers to prevent HTML contamination
            if (ob_get_level()) {
                ob_end_clean();
            }

            $file = fopen('php://output', 'w');

            // CSV header
            fputcsv($file, [
                'ID', 'Name', 'Description', 'Status',
                'Application', 'Application Code', 'Admin Aplikasi',
                'Total Tickets', 'Open Tickets', 'Resolved Tickets',
                'Avg Resolution Time (Hours)', 'Created At'
            ]);

            // CSV data
            foreach ($categories as $category) {
                $avgResolutionTime = $this->calculateAvgResolutionTime($category);

                fputcsv($file, [
                    $category->id,
                    $category->name,
                    $category->description,
                    $category->status,
                    $category->aplikasi?->name,
                    $category->aplikasi?->code,
                    $category->aplikasi?->adminAplikasi?->name,
                    $category->tickets()->count(),
                    $category->tickets()->where('status', Ticket::STATUS_OPEN)->count(),
                    $category->tickets()->where('status', Ticket::STATUS_RESOLVED)->count(),
                    $avgResolutionTime,
                    $category->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get filtered categories with advanced filtering.
     */
    private function getFilteredCategories(array $filters, int $perPage = 20)
    {
        $query = KategoriMasalah::with(['aplikasi.adminAplikasi']);

        // Apply filters
        if (!empty($filters['aplikasi_id'])) {
            $query->where('aplikasi_id', $filters['aplikasi_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortDirection);
                break;
            case 'total_tickets':
                $query->withCount('tickets')->orderBy('tickets_count', $sortDirection);
                break;
            case 'application':
                $query->join('aplikasis', 'kategori_masalahs.aplikasi_id', '=', 'aplikasis.id')
                      ->orderBy('aplikasis.name', $sortDirection)
                      ->select('kategori_masalahs.*');
                break;
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        return $query->paginate($perPage);
    }

    /**
     * Get filter options for categories listing.
     */
    private function getFilterOptions(): array
    {
        return [
            'statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
            'applications' => Aplikasi::orderBy('name')
                ->with('adminAplikasi')
                ->get(['id', 'name', 'code', 'admin_aplikasi_nip'])
                ->map(function ($app) {
                    return [
                        'value' => $app->id,
                        'label' => $app->name . ' (' . $app->code . ')',
                        'admin_name' => $app->adminAplikasi?->name,
                    ];
                }),
        ];
    }

    /**
     * Get categories statistics for overview.
     */
    private function getCategoriesStats(): array
    {
        $totalCategories = KategoriMasalah::count();
        $activeCategories = KategoriMasalah::where('status', 'active')->count();
        $inactiveCategories = KategoriMasalah::where('status', 'inactive')->count();

        // Get application stats
        $totalApplications = Aplikasi::count();
        $activeApplications = Aplikasi::where('status', 'active')->count();

        // Get ticket stats by category
        $totalTickets = Ticket::count();
        $categoriesWithTickets = KategoriMasalah::whereHas('tickets')->count();

        return [
            'total_categories' => $totalCategories,
            'active_categories' => $activeCategories,
            'inactive_categories' => $inactiveCategories,
            'total_applications' => $totalApplications,
            'active_applications' => $activeApplications,
            'categories_with_tickets' => $categoriesWithTickets,
            'total_tickets' => $totalTickets,
        ];
    }

    /**
     * Calculate average resolution time for a category.
     */
    private function calculateAvgResolutionTime(KategoriMasalah $category): ?float
    {
        $avgMinutes = $category->tickets()
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereNotNull('resolution_time_minutes')
            ->avg('resolution_time_minutes');

        return $avgMinutes ? round($avgMinutes / 60, 2) : null; // Convert to hours
    }
}