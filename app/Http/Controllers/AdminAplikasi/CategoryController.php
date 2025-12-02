<?php

namespace App\Http\Controllers\AdminAplikasi;

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

class CategoryController extends Controller
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Check if request wants JSON response.
     * Returns false for Inertia requests (they should get Inertia responses).
     */
    private function wantsJson(Request $request): bool
    {
        // Inertia requests have X-Inertia header - they should NOT get JSON response
        if ($request->header('X-Inertia')) {
            return false;
        }
        
        return $request->wantsJson() || 
               $request->expectsJson() || 
               $request->ajax() ||
               $request->header('Accept') === 'application/json' ||
               $request->header('Content-Type') === 'application/json';
    }

    /**
     * Display a listing of categories for admin aplikasi.
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

        // Get filter parameters
        $filters = $request->only(['aplikasi_id', 'status', 'search', 'sort_by', 'sort_direction']);
        $selectedApplicationId = $request->get('aplikasi_id');

        // Set default sorting
        if (!isset($filters['sort_by'])) {
            $filters['sort_by'] = 'name';
            $filters['sort_direction'] = 'asc';
        }

        // Get applications managed by this admin
        $applications = $this->getManagedApplications($admin);

        // Get categories (scoped to managed applications)
        $categories = $this->getFilteredCategories($admin, $filters, 20);

        // Format categories for frontend
        $formattedCategories = $categories->getCollection()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'status' => $category->status,
                'default_priority' => $category->default_priority ?? 'medium',
                'aplikasi_id' => $category->aplikasi_id,
                'aplikasi' => $category->aplikasi ? [
                    'id' => $category->aplikasi->id,
                    'name' => $category->aplikasi->name,
                    'code' => $category->aplikasi->code,
                ] : null,
                'ticket_count' => $category->tickets_count ?? 0,
                'open_tickets' => $category->open_tickets_count ?? 0,
                'created_at' => $category->created_at,
                'formatted_created_at' => $category->created_at->format('d M Y'),
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

        // Get statistics
        $stats = $this->getCategoriesStats($admin, $selectedApplicationId);

        // Get selected application details if provided
        $selectedApplication = null;
        if ($selectedApplicationId) {
            $app = Aplikasi::withCount(['kategoriMasalahs', 'tickets'])->find($selectedApplicationId);
            if ($app && $this->hasAccessToApplication($admin, $selectedApplicationId)) {
                $selectedApplication = [
                    'id' => $app->id,
                    'name' => $app->name,
                    'code' => $app->code,
                    'description' => $app->description,
                    'status' => $app->status,
                    'category_count' => $app->kategori_masalahs_count ?? 0,
                    'ticket_count' => $app->tickets_count ?? 0,
                ];
            }
        }

        return Inertia::render('AdminAplikasi/KategoriManagement', [
            'categories' => $formattedPaginator,
            'applications' => $applications,
            'selectedApplicationId' => $selectedApplicationId,
            'selectedApplication' => $selectedApplication,
            'filters' => $filters,
            'stats' => [
                'total_categories' => $stats['total_categories'] ?? 0,
                'active_categories' => $stats['active_categories'] ?? 0,
                'total_tickets' => $stats['total_tickets'] ?? 0,
                'open_tickets' => $stats['open_tickets'] ?? 0,
            ],
        ]);
    }

    /**
     * Refresh categories data (API endpoint for AJAX calls).
     */
    public function refreshCategories(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Invalid session.',
            ], 401);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin not found.',
            ], 404);
        }

        // Get filter parameters
        $filters = $request->only(['aplikasi_id', 'status', 'search', 'sort_by', 'sort_direction']);
        $selectedApplicationId = $request->get('aplikasi_id');

        // Set default sorting
        if (!isset($filters['sort_by'])) {
            $filters['sort_by'] = 'name';
            $filters['sort_direction'] = 'asc';
        }

        // Get applications managed by this admin
        $applications = $this->getManagedApplications($admin);

        // Get categories (scoped to managed applications)
        $categories = $this->getFilteredCategories($admin, $filters, 20);

        // Format categories for frontend
        $formattedCategories = $categories->getCollection()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'status' => $category->status,
                'default_priority' => $category->default_priority ?? 'medium',
                'aplikasi_id' => $category->aplikasi_id,
                'aplikasi' => $category->aplikasi ? [
                    'id' => $category->aplikasi->id,
                    'name' => $category->aplikasi->name,
                    'code' => $category->aplikasi->code,
                ] : null,
                'ticket_count' => $category->tickets_count ?? 0,
                'open_tickets' => $category->open_tickets_count ?? 0,
                'created_at' => $category->created_at,
                'formatted_created_at' => $category->created_at->format('d M Y'),
            ];
        });

        // Get statistics
        $stats = $this->getCategoriesStats($admin, $selectedApplicationId);

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => [
                    'data' => $formattedCategories,
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                ],
                'applications' => $applications,
                'filters' => $filters,
                'stats' => [
                    'total_categories' => $stats['total_categories'] ?? 0,
                    'active_categories' => $stats['active_categories'] ?? 0,
                    'total_tickets' => $stats['total_tickets'] ?? 0,
                    'open_tickets' => $stats['open_tickets'] ?? 0,
                ],
            ],
        ]);
    }

    /**
     * Display the specified category.
     */
    public function show($id)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            return redirect()->route('login')->withErrors(['Access denied.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        $category = KategoriMasalah::with('aplikasi')->findOrFail($id);

        // Validate that admin has access to the category's application
        if (!$this->hasAccessToApplication($admin, $category->aplikasi_id)) {
            return redirect()->route('admin-aplikasi.categories.index')
                ->withErrors(['You do not have access to this category.']);
        }

        // Get performance metrics
        $performance = $category->getPerformanceMetrics();

        // Get ticket statistics by status
        $ticketStats = [
            'total' => $category->tickets()->count(),
            'open' => $category->tickets()->where('status', 'open')->count(),
            'in_progress' => $category->tickets()->where('status', 'in_progress')->count(),
            'resolved' => $category->tickets()->where('status', 'resolved')->count(),
            'closed' => $category->tickets()->where('status', 'closed')->count(),
        ];

        // Get recent tickets
        $recentTickets = $category->tickets()
            ->with(['user', 'assignedTeknisi'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'status_label' => $ticket->status_label,
                    'priority' => $ticket->priority,
                    'priority_label' => $ticket->priority_label,
                    'user' => $ticket->user ? [
                        'nip' => $ticket->user->nip,
                        'name' => $ticket->user->name,
                    ] : null,
                    'teknisi' => $ticket->assignedTeknisi ? [
                        'nip' => $ticket->assignedTeknisi->nip,
                        'name' => $ticket->assignedTeknisi->name,
                    ] : null,
                    'created_at' => $ticket->created_at->format('d M Y H:i'),
                ];
            });

        // Get expert teknisi
        $expertTeknisi = $category->expertTeknisis()
            ->get()
            ->map(function ($teknisi) {
                return [
                    'nip' => $teknisi->nip,
                    'name' => $teknisi->name,
                    'expertise_level' => $teknisi->pivot->expertise_level ?? null,
                    'success_rate' => $teknisi->pivot->success_rate ?? 0,
                    'avg_resolution_time' => $teknisi->pivot->avg_resolution_time ?? 0,
                ];
            });

        // Get recommendations
        $recommendations = $category->getRecommendations();

        return Inertia::render('AdminAplikasi/CategoryDetail', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'code' => $category->code ?? null,
                'status' => $category->status,
                'status_label' => $category->status_label,
                'default_priority' => $category->default_priority ?? 'medium',
                'estimated_resolution_time' => $category->estimated_resolution_time,
                'sla_hours' => $category->sla_hours,
                'aplikasi_id' => $category->aplikasi_id,
                'aplikasi' => [
                    'id' => $category->aplikasi->id,
                    'name' => $category->aplikasi->name,
                    'code' => $category->aplikasi->code,
                ],
                'created_at' => $category->created_at,
            ],
            'performance' => $performance,
            'ticketStats' => $ticketStats,
            'recentTickets' => $recentTickets,
            'expertTeknisi' => $expertTeknisi,
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            if ($this->wantsJson($request)) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 401);
            }
            return back()->withErrors(['Access denied.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();

        // Validate that admin has access to the application
        if (!$this->hasAccessToApplication($admin, $request->aplikasi_id)) {
            if ($this->wantsJson($request)) {
                return response()->json(['success' => false, 'message' => 'You do not have access to this application.'], 403);
            }
            return back()->withErrors(['You do not have access to this application.']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'aplikasi_id' => 'required|exists:aplikasis,id',
            'status' => 'required|in:active,inactive',
            'default_priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        if ($validator->fails()) {
            if ($this->wantsJson($request)) {
                return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $category = KategoriMasalah::create([
                'name' => $request->name,
                'description' => $request->description,
                'aplikasi_id' => $request->aplikasi_id,
                'status' => $request->status,
                'default_priority' => $request->default_priority ?? 'medium',
            ]);

            // Log the creation
            $this->auditLogService->log(
                'created',
                $category,
                $admin,
                [
                    'category_name' => $category->name,
                    'aplikasi_id' => $category->aplikasi_id,
                    'created_by' => $nip,
                ]
            );

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Category created successfully.',
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ]
                ]);
            }
            return back()->with('success', 'Category created successfully.');

        } catch (\Exception $e) {
            if ($this->wantsJson($request)) {
                return response()->json(['success' => false, 'message' => 'Failed to create category: ' . $e->getMessage()], 500);
            }
            return back()->withErrors(['Failed to create category: ' . $e->getMessage()]);
        }
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, $id)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            if ($this->wantsJson($request)) {
                return response()->json(['success' => false, 'message' => 'Access denied.'], 401);
            }
            return back()->withErrors(['Access denied.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        $category = KategoriMasalah::findOrFail($id);

        // Validate that admin has access to the category's application
        if (!$this->hasAccessToApplication($admin, $category->aplikasi_id)) {
            if ($this->wantsJson($request)) {
                return response()->json(['success' => false, 'message' => 'You do not have access to this category.'], 403);
            }
            return back()->withErrors(['You do not have access to this category.']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'aplikasi_id' => 'required|exists:aplikasis,id',
            'status' => 'required|in:active,inactive',
            'default_priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        if ($validator->fails()) {
            if ($this->wantsJson($request)) {
                return response()->json(['success' => false, 'errors' => $validator->errors()->all()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // Validate access to new application if changed
        if ($request->aplikasi_id != $category->aplikasi_id && !$this->hasAccessToApplication($admin, $request->aplikasi_id)) {
            if ($this->wantsJson($request)) {
                return response()->json(['success' => false, 'message' => 'You do not have access to the target application.'], 403);
            }
            return back()->withErrors(['You do not have access to the target application.']);
        }

        try {
            $oldData = $category->toArray();

            $category->update([
                'name' => $request->name,
                'description' => $request->description,
                'aplikasi_id' => $request->aplikasi_id,
                'status' => $request->status,
                'default_priority' => $request->default_priority ?? $category->default_priority,
            ]);

            // Log the update
            $this->auditLogService->log(
                'updated',
                $category,
                $admin,
                [
                    'category_name' => $category->name,
                    'old_data' => $oldData,
                    'updated_by' => $nip,
                ]
            );

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => true, 
                    'message' => 'Category updated successfully.',
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ]
                ]);
            }
            return back()->with('success', 'Category updated successfully.');

        } catch (\Exception $e) {
            if ($this->wantsJson($request)) {
                return response()->json(['success' => false, 'message' => 'Category not found or failed to update.'], 500);
            }
            return back()->withErrors(['Category not found or failed to update.']);
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy($id)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            return back()->withErrors(['Access denied.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();

        try {
            $category = KategoriMasalah::findOrFail($id);

            // Validate that admin has access to the category's application
            if (!$this->hasAccessToApplication($admin, $category->aplikasi_id)) {
                return back()->withErrors(['You do not have access to this category.']);
            }

            // Check if category has tickets
            $ticketCount = $category->tickets()->count();
            if ($ticketCount > 0) {
                return back()->withErrors(["Cannot delete category with {$ticketCount} existing tickets."]);
            }

            // Log the deletion
            $this->auditLogService->log(
                'deleted',
                $category,
                $admin,
                [
                    'category_name' => $category->name,
                    'aplikasi_id' => $category->aplikasi_id,
                    'deleted_by' => $nip,
                ]
            );

            $category->delete();

            return back()->with('success', 'Category deleted successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['Category not found.']);
        }
    }

    /**
     * Update category status.
     */
    public function updateStatus(Request $request, $id)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            return back()->withErrors(['Access denied.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        $category = KategoriMasalah::findOrFail($id);

        if (!$this->hasAccessToApplication($admin, $category->aplikasi_id)) {
            return back()->withErrors(['You do not have access to this category.']);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $category->update(['status' => $request->status]);

        return back()->with('success', 'Category status updated successfully.');
    }

    /**
     * Perform bulk actions on categories.
     */
    public function bulkAction(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            return back()->withErrors(['Access denied.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,delete',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'integer|exists:kategori_masalahs,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // Verify admin has access to all categories
        $categories = KategoriMasalah::whereIn('id', $request->category_ids)->get();
        foreach ($categories as $category) {
            if (!$this->hasAccessToApplication($admin, $category->aplikasi_id)) {
                return back()->withErrors(['You do not have access to all selected categories.']);
            }
        }

        try {
            $action = $request->action;
            $categoryIds = $request->category_ids;
            $affectedCount = 0;

            switch ($action) {
                case 'activate':
                    KategoriMasalah::whereIn('id', $categoryIds)->update(['status' => 'active']);
                    $affectedCount = count($categoryIds);
                    $message = "Activated {$affectedCount} categories.";
                    break;

                case 'deactivate':
                    KategoriMasalah::whereIn('id', $categoryIds)->update(['status' => 'inactive']);
                    $affectedCount = count($categoryIds);
                    $message = "Deactivated {$affectedCount} categories.";
                    break;

                case 'delete':
                    // Check for categories with tickets
                    $categoriesWithTickets = KategoriMasalah::whereIn('id', $categoryIds)
                        ->whereHas('tickets')
                        ->pluck('id')
                        ->toArray();

                    if (!empty($categoriesWithTickets)) {
                        return back()->withErrors(['Cannot delete categories with existing tickets.']);
                    }

                    KategoriMasalah::whereIn('id', $categoryIds)->delete();
                    $affectedCount = count($categoryIds);
                    $message = "Deleted {$affectedCount} categories.";
                    break;

                default:
                    $message = 'Unknown action.';
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->withErrors(['Failed to perform bulk action: ' . $e->getMessage()]);
        }
    }

    /**
     * Export categories data.
     */
    public function export(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            return redirect()->route('login')->withErrors(['Unauthorized.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        $filters = $request->only(['aplikasi_id', 'status', 'search']);

        $categories = $this->getFilteredCategories($admin, $filters, 1000)->getCollection();

        $filename = 'categories_export_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($categories) {
            if (ob_get_level()) ob_end_clean();
            $file = fopen('php://output', 'w');

            fputcsv($file, ['ID', 'Name', 'Description', 'Status', 'Application', 'Total Tickets', 'Created At']);

            foreach ($categories as $category) {
                fputcsv($file, [
                    $category['id'],
                    $category['name'],
                    $category['description'] ?? '',
                    $category['status'],
                    $category['aplikasi']['name'] ?? 'N/A',
                    $category['ticket_count'] ?? 0,
                    $category['formatted_created_at'] ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Check if admin has access to application.
     */
    private function hasAccessToApplication(AdminAplikasi $admin, $applicationId): bool
    {
        $application = Aplikasi::find($applicationId);
        if ($application && ($application->admin_aplikasi_nip === $admin->nip || $application->backup_admin_nip === $admin->nip)) {
            return true;
        }

        return false;
    }

    /**
     * Get applications assigned to this admin.
     */
    private function getManagedApplications(AdminAplikasi $admin): array
    {
        $query = Aplikasi::where(function($q) use ($admin) {
            $q->where('admin_aplikasi_nip', $admin->nip)
              ->orWhere('backup_admin_nip', $admin->nip);
        });

        return $query->orderBy('name')
            ->get(['id', 'name', 'code', 'status'])
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'name' => $app->name,
                    'code' => $app->code,
                    'status' => $app->status,
                ];
            })
            ->toArray();
    }

    /**
     * Get filtered categories scoped to admin's assigned applications.
     */
    private function getFilteredCategories(AdminAplikasi $admin, array $filters, int $perPage = 20)
    {
        // Get application IDs assigned to this admin
        $appIds = Aplikasi::where(function($q) use ($admin) {
            $q->where('admin_aplikasi_nip', $admin->nip)
              ->orWhere('backup_admin_nip', $admin->nip);
        })->pluck('id')->toArray();

        $query = KategoriMasalah::with('aplikasi')
            ->withCount([
                'tickets',
                'tickets as open_tickets_count' => function ($q) {
                    $q->whereIn('status', ['open', 'assigned', 'in_progress']);
                }
            ]);

        // Scope to admin's assigned applications
        $query->whereIn('aplikasi_id', $appIds);

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
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Get categories statistics.
     */
    private function getCategoriesStats(AdminAplikasi $admin, $selectedApplicationId = null): array
    {
        // Get app IDs from database directly (no more using managed_applications JSON field)
        $appIds = Aplikasi::where(function($q) use ($admin) {
            $q->where('admin_aplikasi_nip', $admin->nip)
              ->orWhere('backup_admin_nip', $admin->nip);
        })->pluck('id')->toArray();

        $categoryQuery = KategoriMasalah::whereIn('aplikasi_id', $appIds);
        $ticketQuery = Ticket::whereIn('aplikasi_id', $appIds);

        if ($selectedApplicationId) {
            $categoryQuery->where('aplikasi_id', $selectedApplicationId);
            $ticketQuery->where('aplikasi_id', $selectedApplicationId);
        }

        return [
            'total_categories' => (clone $categoryQuery)->count(),
            'active_categories' => (clone $categoryQuery)->where('status', 'active')->count(),
            'total_tickets' => (clone $ticketQuery)->count(),
            'open_tickets' => (clone $ticketQuery)->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
        ];
    }
}
