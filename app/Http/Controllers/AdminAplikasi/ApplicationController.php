<?php

namespace App\Http\Controllers\AdminAplikasi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\Ticket;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use App\Services\AuditLogService;
use Carbon\Carbon;

class ApplicationController extends Controller
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Check if request expects JSON response (API/AJAX calls, NOT Inertia).
     * Inertia requests have X-Inertia header and should receive Inertia responses.
     */
    private function wantsJson(Request $request): bool
    {
        // Inertia requests have X-Inertia header - they should NOT get JSON response
        if ($request->header('X-Inertia')) {
            return false;
        }
        
        // API routes should always return JSON
        if ($request->is('api/*')) {
            return true;
        }
        
        // AJAX requests without Inertia header should return JSON
        return $request->ajax() || 
               $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Redirect to dashboard (alias for dashboard route).
     */
    public function dashboard()
    {
        return app(DashboardController::class)->index(request());
    }

    /**
     * Display a listing of applications managed by this admin.
     */
    public function index(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Invalid session.',
                ], 401);
            }
            return redirect()->route('login')->withErrors(['Access denied. Invalid session.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        if (!$admin) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found.',
                ], 404);
            }
            return redirect()->route('login')->withErrors(['Admin not found.']);
        }

        // Get filter parameters
        $filters = $request->only(['status', 'search', 'sort_by', 'sort_direction']);

        // Set default sorting
        if (!isset($filters['sort_by'])) {
            $filters['sort_by'] = 'name';
            $filters['sort_direction'] = 'asc';
        }

        // Get applications (scoped to this admin)
        $applications = $this->getFilteredApplications($admin, $filters, 15);

        // Format applications for frontend
        $formattedApplications = $applications->getCollection()->map(function ($application) {
            // Use eager loaded counts
            $totalTickets = $application->tickets_count ?? 0;
            $totalCategories = $application->kategori_masalahs_count ?? 0;
            
            return [
                'id' => $application->id,
                'name' => $application->name,
                'code' => $application->code,
                'description' => $application->description,
                'version' => $application->current_version ?? $application->version,
                'current_version' => $application->current_version,
                'status' => $application->status,
                'status_badge' => $application->status_badge_color,
                'category' => $application->category,
                'category_label' => $application->category_label,
                'total_tickets' => $totalTickets,
                'ticket_count' => $totalTickets,
                'open_tickets' => $application->tickets()->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
                'resolved_tickets' => $application->tickets()->where('status', Ticket::STATUS_RESOLVED)->count(),
                'total_categories' => $totalCategories,
                'category_count' => $totalCategories,
                'assigned_teknisi_count' => $application->assignedTeknis()->count(),
                'created_at' => $application->created_at,
                'formatted_created_at' => $application->created_at->format('d M Y'),
            ];
        });

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        // Get statistics for overview
        $stats = $this->getApplicationStats($admin);

        // Get teknisi for assignment
        $teknisis = Teknisi::active()->orderBy('name')->get(['nip', 'name', 'department']);

        // Create pagination object with formatted data
        $paginatedApplications = new \Illuminate\Pagination\LengthAwarePaginator(
            $formattedApplications,
            $applications->total(),
            $applications->perPage(),
            $applications->currentPage(),
            ['path' => $applications->path(), 'pageName' => 'page']
        );

        // Return JSON for API/AJAX requests, Inertia for page visits
        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'applications' => [
                        'data' => $formattedApplications,
                        'current_page' => $applications->currentPage(),
                        'last_page' => $applications->lastPage(),
                        'per_page' => $applications->perPage(),
                        'total' => $applications->total(),
                    ],
                    'filters' => $filters,
                    'filterOptions' => $filterOptions,
                    'stats' => $stats,
                    'teknisis' => $teknisis,
                ],
            ]);
        }

        return Inertia::render('AdminAplikasi/AplikasiManagement', [
            'applications' => $paginatedApplications,
            'stats' => $stats,
            'teknisis' => $teknisis,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
        ]);
    }

    /**
     * Refresh applications data (API endpoint for AJAX calls).
     */
    public function refreshApplications(Request $request)
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
        $filters = $request->only(['status', 'search', 'sort_by', 'sort_direction']);

        // Set default sorting
        if (!isset($filters['sort_by'])) {
            $filters['sort_by'] = 'name';
            $filters['sort_direction'] = 'asc';
        }

        // Get applications (scoped to this admin)
        $applications = $this->getFilteredApplications($admin, $filters, 15);

        // Format applications for frontend
        $formattedApplications = $applications->getCollection()->map(function ($application) {
            $totalTickets = $application->tickets_count ?? 0;
            $totalCategories = $application->kategori_masalahs_count ?? 0;
            
            return [
                'id' => $application->id,
                'name' => $application->name,
                'code' => $application->code,
                'description' => $application->description,
                'version' => $application->current_version ?? $application->version,
                'current_version' => $application->current_version,
                'status' => $application->status,
                'status_badge' => $application->status_badge_color,
                'category' => $application->category,
                'category_label' => $application->category_label,
                'total_tickets' => $totalTickets,
                'ticket_count' => $totalTickets,
                'open_tickets' => $application->tickets()->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
                'resolved_tickets' => $application->tickets()->where('status', Ticket::STATUS_RESOLVED)->count(),
                'total_categories' => $totalCategories,
                'category_count' => $totalCategories,
                'assigned_teknisi_count' => $application->assignedTeknis()->count(),
                'created_at' => $application->created_at,
                'formatted_created_at' => $application->created_at->format('d M Y'),
            ];
        });

        // Get statistics for overview
        $stats = $this->getApplicationStats($admin);

        // Get teknisi for assignment
        $teknisis = Teknisi::active()->orderBy('name')->get(['nip', 'name', 'department']);

        return response()->json([
            'success' => true,
            'data' => [
                'applications' => [
                    'data' => $formattedApplications,
                    'current_page' => $applications->currentPage(),
                    'last_page' => $applications->lastPage(),
                    'per_page' => $applications->perPage(),
                    'total' => $applications->total(),
                ],
                'filters' => $filters,
                'filterOptions' => $this->getFilterOptions(),
                'stats' => $stats,
                'teknisis' => $teknisis,
            ],
        ]);
    }

    /**
     * Show the form for creating a new application.
     */
    public function create(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied.',
                ], 401);
            }
            return redirect()->route('login')->withErrors(['Access denied.']);
        }

        $filterOptions = $this->getFilterOptions();

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'mode' => 'create',
                    'applications' => ['data' => [], 'total' => 0],
                    'filterOptions' => $filterOptions,
                    'stats' => [],
                ],
            ]);
        }

        return Inertia::render('AdminAplikasi/AplikasiManagement', [
            'applications' => ['data' => [], 'total' => 0],
            'stats' => [],
            'teknisis' => Teknisi::active()->orderBy('name')->get(['nip', 'name', 'department']),
            'filters' => [],
            'filterOptions' => $filterOptions,
            'mode' => 'create',
        ]);
    }

    /**
     * Store a newly created application.
     */
    public function store(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Invalid session.',
                ], 401);
            }
            return back()->withErrors(['Access denied. Invalid session.']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:aplikasis,code',
            'description' => 'nullable|string|max:1000',
            'version' => 'nullable|string|max:20',
            'status' => 'required|string|in:active,inactive,maintenance,deprecated',
            'category' => 'nullable|string|in:web,desktop,mobile,service,database,network,security,business',
        ]);

        if ($validator->fails()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $application = Aplikasi::create([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'version' => $request->version ?? '1.0.0',
                'status' => $request->status,
                'category' => $request->category,
                'admin_aplikasi_nip' => $nip,
            ]);

            // Update admin's managed applications
            $admin = AdminAplikasi::where('nip', $nip)->first();
            if ($admin) {
                $managedApps = $admin->managed_applications ?? [];
                $managedApps[] = $application->id;
                $admin->managed_applications = array_unique($managedApps);
                $admin->save();
            }

            // Log the creation
            $this->auditLogService->log(
                'created',
                $application,
                $admin,
                [
                    'application_name' => $application->name,
                    'application_code' => $application->code,
                    'created_by' => $nip,
                    'created_by_role' => $userRole,
                ]
            );

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Application created successfully.',
                    'data' => $application,
                ], 201);
            }

            return back()->with('success', 'Application created successfully.');

        } catch (\Exception $e) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create application: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['Failed to create application: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified application.
     */
    public function show(Request $request, $id)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied.',
                ], 401);
            }
            return redirect()->route('login')->withErrors(['Access denied.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        
        // Check if admin has access to this application
        if (!$this->hasAccessToApplication($admin, $id)) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this application.',
                ], 403);
            }
            return redirect()->route('admin-aplikasi.applications.index')
                ->withErrors(['You do not have access to this application.']);
        }

        $application = Aplikasi::with([
            'kategoriMasalahs',
            'assignedTeknis',
            'tickets' => function ($query) {
                $query->latest()->limit(10);
            }
        ])->findOrFail($id);

        // Get ticket statistics
        $ticketStats = [
            'total' => $application->tickets()->count(),
            'open' => $application->tickets()->where('status', Ticket::STATUS_OPEN)->count(),
            'in_progress' => $application->tickets()->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
            'resolved' => $application->tickets()->where('status', Ticket::STATUS_RESOLVED)->count(),
            'closed' => $application->tickets()->where('status', Ticket::STATUS_CLOSED)->count(),
        ];

        // Get categories with ticket counts
        $categories = $application->kategoriMasalahs()
            ->withCount('tickets')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'status' => $category->status,
                    'tickets_count' => $category->tickets_count,
                ];
            });

        // Get recent tickets
        $recentTickets = $application->tickets()
            ->with(['user', 'kategoriMasalah', 'assignedTeknisi'])
            ->latest()
            ->limit(10)
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
                    'assigned_teknisi' => $ticket->assignedTeknisi ? [
                        'nip' => $ticket->assignedTeknisi->nip,
                        'name' => $ticket->assignedTeknisi->name,
                    ] : null,
                    'created_at' => $ticket->created_at->format('d M Y H:i'),
                ];
            });

        // Get health metrics
        $healthMetrics = [
            'health_status' => $application->health_status,
            'uptime_percentage' => $application->uptime_percentage,
            'response_time_avg' => $application->response_time_avg,
            'error_rate' => $application->error_rate,
            'last_health_check' => $application->last_health_check,
        ];

        // Application data formatted
        $applicationData = [
            'id' => $application->id,
            'name' => $application->name,
            'code' => $application->code,
            'description' => $application->description,
            'version' => $application->version,
            'current_version' => $application->current_version,
            'status' => $application->status,
            'status_label' => $application->status_label,
            'category' => $application->category,
            'category_label' => $application->category_label,
            'criticality' => $application->criticality,
            'is_maintenance_mode' => $application->is_maintenance_mode,
            'current_users' => $application->current_users,
            'max_users' => $application->max_users,
            'last_updated' => $application->last_updated,
            'created_at' => $application->created_at->format('d M Y'),
        ];

        // Return JSON for API/AJAX requests
        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'application' => $applicationData,
                    'ticketStats' => $ticketStats,
                    'categories' => $categories,
                    'recentTickets' => $recentTickets,
                    'healthMetrics' => $healthMetrics,
                ],
            ]);
        }

        // Return Inertia response for page visits
        return Inertia::render('AdminAplikasi/ApplicationDetail', [
            'application' => $applicationData,
            'ticketStats' => $ticketStats,
            'categories' => $categories,
            'recentTickets' => $recentTickets,
            'healthMetrics' => $healthMetrics,
        ]);
    }

    /**
     * Show the form for editing the specified application.
     */
    public function edit(Request $request, $id)
    {
        return $this->show($request, $id);
    }

    /**
     * Update the specified application.
     */
    public function update(Request $request, $id)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Invalid session.',
                ], 401);
            }
            return back()->withErrors(['Access denied. Invalid session.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        
        // Check if admin has access to this application
        if (!$this->hasAccessToApplication($admin, $id)) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this application.',
                ], 403);
            }
            return back()->withErrors(['You do not have access to this application.']);
        }

        $application = Aplikasi::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:aplikasis,code,' . $id,
            'description' => 'nullable|string|max:1000',
            'version' => 'nullable|string|max:20',
            'status' => 'required|string|in:active,inactive,maintenance,deprecated',
            'category' => 'nullable|string|in:web,desktop,mobile,service,database,network,security,business',
        ]);

        if ($validator->fails()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $oldData = $application->toArray();

            $application->update([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'version' => $request->version ?? $application->version,
                'status' => $request->status,
                'category' => $request->category ?? $application->category,
            ]);

            // Log the update
            $this->auditLogService->log(
                'updated',
                $application,
                $admin,
                [
                    'application_name' => $application->name,
                    'old_data' => $oldData,
                    'new_data' => $application->toArray(),
                    'updated_by' => $nip,
                ]
            );

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Application updated successfully.',
                    'data' => $application,
                ]);
            }

            return back()->with('success', 'Application updated successfully.');

        } catch (\Exception $e) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update application: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['Failed to update application: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified application.
     */
    public function destroy(Request $request, $id)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Invalid session.',
                ], 401);
            }
            return back()->withErrors(['Access denied. Invalid session.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        
        // Check if admin has access to this application
        if (!$this->hasAccessToApplication($admin, $id)) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this application.',
                ], 403);
            }
            return back()->withErrors(['You do not have access to this application.']);
        }

        $application = Aplikasi::findOrFail($id);

        // Check if application has tickets
        $ticketCount = $application->tickets()->count();
        if ($ticketCount > 0) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete application. It has {$ticketCount} associated tickets.",
                ], 422);
            }
            return back()->withErrors(["Cannot delete application. It has {$ticketCount} associated tickets."]);
        }

        try {
            // Log the deletion
            $this->auditLogService->log(
                'deleted',
                $application,
                $admin,
                [
                    'application_name' => $application->name,
                    'application_code' => $application->code,
                    'deleted_by' => $nip,
                ]
            );

            // Remove from admin's managed applications
            if ($admin) {
                $managedApps = $admin->managed_applications ?? [];
                $managedApps = array_filter($managedApps, fn($appId) => $appId != $id);
                $admin->managed_applications = array_values($managedApps);
                $admin->save();
            }

            $application->delete();

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Application deleted successfully.',
                ]);
            }

            return redirect()->route('admin-aplikasi.applications.index')
                ->with('success', 'Application deleted successfully.');

        } catch (\Exception $e) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete application: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['Failed to delete application: ' . $e->getMessage()]);
        }
    }

    /**
     * Assign teknisi to application.
     */
    public function assignTeknisi(Request $request, $id)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied.',
                ], 401);
            }
            return back()->withErrors(['Access denied.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        
        if (!$this->hasAccessToApplication($admin, $id)) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this application.',
                ], 403);
            }
            return back()->withErrors(['You do not have access to this application.']);
        }

        $validator = Validator::make($request->all(), [
            'teknisi_nip' => 'required|exists:teknisis,nip',
        ]);

        if ($validator->fails()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $application = Aplikasi::findOrFail($id);
            
            // Add teknisi to application (using pivot table)
            $application->assignedTeknis()->syncWithoutDetaching([
                $request->teknisi_nip => [
                    'assigned_by_nip' => $nip,
                    'assigned_at' => Carbon::now(),
                ]
            ]);

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Teknisi assigned successfully.',
                ]);
            }

            return back()->with('success', 'Teknisi assigned successfully.');

        } catch (\Exception $e) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to assign teknisi: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['Failed to assign teknisi: ' . $e->getMessage()]);
        }
    }

    /**
     * Export applications data.
     */
    public function export(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin not found.',
            ], 404);
        }

        $filters = $request->only(['status', 'search']);

        $applications = $this->getFilteredApplications($admin, $filters, 1000)->getCollection();

        $filename = 'applications_export_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($applications) {
            if (ob_get_level()) ob_end_clean();
            $file = fopen('php://output', 'w');

            fputcsv($file, ['ID', 'Name', 'Code', 'Version', 'Status', 'Category', 'Total Tickets', 'Categories', 'Created At']);

            foreach ($applications as $app) {
                fputcsv($file, [
                    $app['id'],
                    $app['name'],
                    $app['code'],
                    $app['version'] ?? 'N/A',
                    $app['status'],
                    $app['category_label'] ?? 'N/A',
                    $app['total_tickets'] ?? 0,
                    $app['total_categories'] ?? 0,
                    $app['formatted_created_at'] ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Toggle maintenance mode for application.
     */
    public function toggleMaintenance(Request $request, $id)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied.',
                ], 401);
            }
            return back()->withErrors(['Access denied.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        
        if (!$this->hasAccessToApplication($admin, $id)) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this application.',
                ], 403);
            }
            return back()->withErrors(['You do not have access to this application.']);
        }

        $application = Aplikasi::findOrFail($id);

        $enable = $request->input('enable', !$application->is_maintenance_mode);
        
        try {
            if ($enable) {
                $reason = $request->input('reason', 'Scheduled maintenance');
                $application->enableMaintenance($reason);
                $message = 'Maintenance mode enabled successfully.';
            } else {
                $application->disableMaintenance();
                $message = 'Maintenance mode disabled successfully.';
            }

            // Log the action
            $this->auditLogService->log(
                $enable ? 'maintenance_enabled' : 'maintenance_disabled',
                $application,
                $admin,
                [
                    'application_name' => $application->name,
                    'reason' => $request->input('reason'),
                    'toggled_by' => $nip,
                ]
            );

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to toggle maintenance mode: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['Failed to toggle maintenance mode: ' . $e->getMessage()]);
        }
    }

    /**
     * Perform health check on application.
     */
    public function healthCheck(Request $request, $id)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied.',
                ], 401);
            }
            return back()->withErrors(['Access denied.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        
        if (!$this->hasAccessToApplication($admin, $id)) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this application.',
                ], 403);
            }
            return back()->withErrors(['You do not have access to this application.']);
        }

        try {
            $application = Aplikasi::findOrFail($id);
            $healthCheckResults = $application->performHealthCheck();

            // Log the health check
            $this->auditLogService->log(
                'health_check_performed',
                $application,
                $admin,
                [
                    'application_name' => $application->name,
                    'health_status' => $healthCheckResults['status'],
                    'issues_count' => count($healthCheckResults['issues']),
                    'warnings_count' => count($healthCheckResults['warnings']),
                    'performed_by' => $nip,
                ]
            );

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Health check completed successfully.',
                    'data' => $healthCheckResults,
                ]);
            }

            return back()->with([
                'success' => 'Health check completed successfully.',
                'healthCheckResults' => $healthCheckResults,
            ]);

        } catch (\Exception $e) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to perform health check: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['Failed to perform health check: ' . $e->getMessage()]);
        }
    }

    /**
     * Perform bulk operations on applications.
     */
    public function bulkAction(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied.',
                ], 401);
            }
            return back()->withErrors(['Access denied.']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();

        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,maintenance_enable,maintenance_disable,delete',
            'application_ids' => 'required|array|min:1',
            'application_ids.*' => 'integer|exists:aplikasis,id',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()->all(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // Verify admin has access to all selected applications
        $applications = Aplikasi::whereIn('id', $request->application_ids)->get();
        foreach ($applications as $app) {
            if (!$this->hasAccessToApplication($admin, $app->id)) {
                if ($this->wantsJson($request)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have access to all selected applications.',
                    ], 403);
                }
                return back()->withErrors(['You do not have access to all selected applications.']);
            }
        }

        try {
            $action = $request->action;
            $affectedCount = 0;
            $message = '';

            switch ($action) {
                case 'activate':
                    Aplikasi::whereIn('id', $request->application_ids)->update(['status' => 'active']);
                    $affectedCount = count($request->application_ids);
                    $message = "Activated {$affectedCount} applications.";
                    break;

                case 'deactivate':
                    Aplikasi::whereIn('id', $request->application_ids)->update(['status' => 'inactive']);
                    $affectedCount = count($request->application_ids);
                    $message = "Deactivated {$affectedCount} applications.";
                    break;

                case 'maintenance_enable':
                    $reason = $request->input('reason', 'Bulk maintenance operation');
                    foreach ($applications as $app) {
                        $app->enableMaintenance($reason);
                    }
                    $affectedCount = count($request->application_ids);
                    $message = "Enabled maintenance for {$affectedCount} applications.";
                    break;

                case 'maintenance_disable':
                    foreach ($applications as $app) {
                        $app->disableMaintenance();
                    }
                    $affectedCount = count($request->application_ids);
                    $message = "Disabled maintenance for {$affectedCount} applications.";
                    break;

                case 'delete':
                    $appsWithTickets = Aplikasi::whereIn('id', $request->application_ids)
                        ->whereHas('tickets')
                        ->pluck('id')
                        ->toArray();

                    if (!empty($appsWithTickets)) {
                        if ($this->wantsJson($request)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Cannot delete applications with existing tickets.',
                            ], 422);
                        }
                        return back()->withErrors(['Cannot delete applications with existing tickets.']);
                    }

                    Aplikasi::whereIn('id', $request->application_ids)->delete();
                    $affectedCount = count($request->application_ids);
                    $message = "Deleted {$affectedCount} applications.";
                    break;
            }

            // Log bulk action
            $this->auditLogService->log(
                'bulk_action',
                null,
                $admin,
                [
                    'action' => $action,
                    'application_ids' => $request->application_ids,
                    'affected_count' => $affectedCount,
                    'performed_by' => $nip,
                ]
            );

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'affected_count' => $affectedCount,
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to perform bulk action: ' . $e->getMessage(),
                ], 500);
            }
            return back()->withErrors(['Failed to perform bulk action: ' . $e->getMessage()]);
        }
    }

    /**
     * Export applications to PDF.
     */
    public function exportPdf(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_aplikasi') {
            return redirect()->route('login')->withErrors(['Access denied']);
        }

        $admin = AdminAplikasi::where('nip', $nip)->first();
        if (!$admin) {
            return redirect()->route('login')->withErrors(['Admin not found']);
        }

        // Get managed application IDs
        $managedAppIds = is_array($admin->managed_applications) 
            ? $admin->managed_applications 
            : json_decode($admin->managed_applications ?? '[]', true);

        // Build query
        $query = Aplikasi::whereIn('id', $managedAppIds)
            ->withCount(['kategoriMasalahs as total_categories', 'tickets as total_tickets', 'assignedTeknis'])
            ->with(['kategoriMasalahs']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('code', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        $applications = $query->get();

        // Prepare PDF data
        $filters = $request->only(['status', 'search']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.applications', [
            'applications' => $applications,
            'admin' => $admin,
            'generated_at' => now()->format('d M Y H:i'),
            'filters' => $filters,
        ]);

        return $pdf->download('applications_' . date('Y-m-d_H-i-s') . '.pdf');
    }

    /**
     * Check if admin has access to application.
     */
    private function hasAccessToApplication(AdminAplikasi $admin, $applicationId): bool
    {
        // Check if admin is assigned as primary or backup admin
        $application = Aplikasi::find($applicationId);
        if ($application && ($application->admin_aplikasi_nip === $admin->nip || $application->backup_admin_nip === $admin->nip)) {
            return true;
        }

        return false;
    }

    /**
     * Get filtered applications with scoping.
     */
    private function getFilteredApplications(AdminAplikasi $admin, array $filters, int $perPage = 15)
    {
        $query = Aplikasi::query();

        // Scope to admin's assigned applications only
        $query->where(function($q) use ($admin) {
            $q->where('admin_aplikasi_nip', $admin->nip)
              ->orWhere('backup_admin_nip', $admin->nip);
        });

        // Load counts efficiently
        $query->withCount(['tickets', 'kategoriMasalahs']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('code', 'like', "%{$filters['search']}%")
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
     * Get filter options.
     */
    private function getFilterOptions(): array
    {
        return [
            'statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
                ['value' => 'maintenance', 'label' => 'Maintenance'],
                ['value' => 'deprecated', 'label' => 'Deprecated'],
            ],
            'categories' => [
                ['value' => 'web', 'label' => 'Web Application'],
                ['value' => 'desktop', 'label' => 'Desktop Application'],
                ['value' => 'mobile', 'label' => 'Mobile Application'],
                ['value' => 'service', 'label' => 'Service'],
                ['value' => 'database', 'label' => 'Database'],
                ['value' => 'network', 'label' => 'Network'],
                ['value' => 'security', 'label' => 'Security'],
                ['value' => 'business', 'label' => 'Business Application'],
            ],
        ];
    }

    /**
     * Get application statistics for this admin.
     */
    private function getApplicationStats(AdminAplikasi $admin): array
    {
        $query = Aplikasi::where(function($q) use ($admin) {
            $q->where('admin_aplikasi_nip', $admin->nip)
              ->orWhere('backup_admin_nip', $admin->nip);
        });

        $totalApps = (clone $query)->count();
        $activeApps = (clone $query)->where('status', 'active')->count();

        // Get app IDs for ticket queries
        $appIds = (clone $query)->pluck('id')->toArray();

        $totalTickets = !empty($appIds) ? Ticket::whereIn('aplikasi_id', $appIds)->count() : 0;
        $totalCategories = !empty($appIds) ? KategoriMasalah::whereIn('aplikasi_id', $appIds)->count() : 0;

        return [
            'total_applications' => $totalApps,
            'active_applications' => $activeApps,
            'total_tickets' => $totalTickets,
            'total_categories' => $totalCategories,
        ];
    }
}
