<?php

namespace App\Http\Controllers\AdminHelpdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\Ticket;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use App\Services\AuditLogService;

class ApplicationManagementController extends Controller
{
    protected $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Display a listing of all applications for admin helpdesk oversight.
     */
    public function index(Request $request)
    {
        $nip = session('user_session.nip');
        $userRole = session('user_session.user_role');

        if (!$nip || $userRole !== 'admin_helpdesk') {
            return redirect()->route('login')->withErrors(['Access denied. Invalid session.']);
        }

        // Get filter parameters
        $filters = $request->only(['status', 'search', 'admin_aplikasi_nip', 'sort_by', 'sort_direction']);

        // Set default sorting
        if (!isset($filters['sort_by'])) {
            $filters['sort_by'] = 'name';
            $filters['sort_direction'] = 'asc';
        }

        // Get all applications (admin helpdesk can see all)
        $applications = $this->getFilteredApplications($filters, 15);

        // Format applications for frontend
        $formattedApplications = $applications->getCollection()->map(function ($application) {
            return [
                'id' => $application->id,
                'name' => $application->name,
                'code' => $application->code,
                'description' => $application->description,
                'version' => $application->version,
                'status' => $application->status,
                'status_badge' => $application->status_badge,
                'admin_aplikasi' => $application->adminAplikasi ? [
                    'nip' => $application->adminAplikasi->nip,
                    'name' => $application->adminAplikasi->name,
                    'email' => $application->adminAplikasi->email,
                ] : null,
                'backup_admin' => $application->backupAdmin ? [
                    'nip' => $application->backupAdmin->nip,
                    'name' => $application->backupAdmin->name,
                    'email' => $application->backupAdmin->email,
                ] : null,
                'total_tickets' => $application->tickets()->count(),
                'open_tickets' => $application->tickets()->where('status', Ticket::STATUS_OPEN)->count(),
                'in_progress_tickets' => $application->tickets()->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
                'resolved_tickets' => $application->tickets()->where('status', Ticket::STATUS_RESOLVED)->count(),
                'total_categories' => $application->kategoriMasalahs()->count(),
                'assigned_teknisi_count' => $application->assignedTeknis()->count(),
                'created_at' => $application->created_at,
                'formatted_created_at' => $application->created_at->format('d M Y'),
                'last_ticket_activity' => $application->tickets()->latest('updated_at')->first()?->updated_at?->format('d M Y H:i'),
            ];
        });

        // Create new paginator with formatted data
        $formattedPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $formattedApplications,
            $applications->total(),
            $applications->perPage(),
            $applications->currentPage(),
            ['path' => $applications->path(), 'pageName' => 'page']
        );

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        // Get statistics for overview
        $stats = $this->getApplicationStats();

        return Inertia::render('AdminHelpdesk/ApplicationManagement', [
            'applications' => $formattedPaginator,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'stats' => $stats,
        ]);
    }

    /**
     * Display the specified application details.
     */
    public function show($id)
    {
        try {
            $application = Aplikasi::with([
                'adminAplikasi',
                'backupAdmin',
                'assignedTeknis',
                'kategoriMasalahs',
                'tickets' => function ($query) {
                    $query->latest()->limit(10);
                }
            ])->findOrFail($id);

            // Get detailed statistics for this application
            $ticketStats = $this->getApplicationTicketStats($application);
            $categories = $application->kategoriMasalahs()->withCount('tickets')->get();
            $assignedTeknisis = $application->assignedTeknis;

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
                        'kategori_masalah' => $ticket->kategoriMasalah ? [
                            'name' => $ticket->kategoriMasalah->name,
                        ] : null,
                        'assigned_teknisi' => $ticket->assignedTeknisi ? [
                            'nip' => $ticket->assignedTeknisi->nip,
                            'name' => $ticket->assignedTeknisi->name,
                        ] : null,
                        'created_at' => $ticket->created_at,
                        'formatted_created_at' => $ticket->formatted_created_at,
                    ];
                });

            return Inertia::render('AdminHelpdesk/ApplicationManagement', [
                'applications' => [
                    'data' => [[
                        'id' => $application->id,
                        'name' => $application->name,
                        'code' => $application->code,
                        'description' => $application->description,
                        'version' => $application->version,
                        'status' => $application->status,
                        'status_badge' => $application->status_badge,
                        'admin_aplikasi' => $application->adminAplikasi ? [
                            'nip' => $application->adminAplikasi->nip,
                            'name' => $application->adminAplikasi->name,
                            'email' => $application->adminAplikasi->email,
                        ] : null,
                        'backup_admin' => $application->backupAdmin ? [
                            'nip' => $application->backupAdmin->nip,
                            'name' => $application->backupAdmin->name,
                            'email' => $application->backupAdmin->email,
                        ] : null,
                        'total_tickets' => $application->tickets()->count(),
                        'open_tickets' => $application->tickets()->where('status', Ticket::STATUS_OPEN)->count(),
                        'in_progress_tickets' => $application->tickets()->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
                        'resolved_tickets' => $application->tickets()->where('status', Ticket::STATUS_RESOLVED)->count(),
                        'total_categories' => $application->kategoriMasalahs()->count(),
                        'assigned_teknisi_count' => $application->assignedTeknis()->count(),
                        'created_at' => $application->created_at,
                        'formatted_created_at' => $application->created_at->format('d M Y'),
                        'last_ticket_activity' => $application->tickets()->latest('updated_at')->first()?->updated_at?->format('d M Y H:i'),
                    ]],
                    'links' => [],
                    'from' => 1,
                    'to' => 1,
                    'total' => 1,
                    'per_page' => 1,
                    'current_page' => 1,
                    'last_page' => 1,
                ],
                'application' => [
                    'id' => $application->id,
                    'name' => $application->name,
                    'code' => $application->code,
                    'description' => $application->description,
                    'version' => $application->version,
                    'status' => $application->status,
                    'status_badge' => $application->status_badge,
                    'admin_aplikasi' => $application->adminAplikasi ? [
                        'nip' => $application->adminAplikasi->nip,
                        'name' => $application->adminAplikasi->name,
                        'email' => $application->adminAplikasi->email,
                    ] : null,
                    'backup_admin' => $application->backupAdmin ? [
                        'nip' => $application->backupAdmin->nip,
                        'name' => $application->backupAdmin->name,
                        'email' => $application->backupAdmin->email,
                    ] : null,
                    'created_at' => $application->created_at,
                    'formatted_created_at' => $application->created_at->format('d M Y'),
                ],
                'filters' => [],
                'filterOptions' => $this->getFilterOptions(),
                'stats' => $this->getApplicationStats(),
                'ticketStats' => $ticketStats,
                'categories' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'status' => $category->status,
                        'tickets_count' => $category->tickets_count,
                    ];
                }),
                'assignedTeknisis' => $assignedTeknisis->map(function ($teknisi) {
                    return [
                        'nip' => $teknisi->nip,
                        'name' => $teknisi->name,
                        'department' => $teknisi->department,
                        'keahlian' => $teknisi->keahlian,
                    ];
                }),
                'recentTickets' => $recentTickets,
            ]);

        } catch (\Exception $e) {
            return redirect()->route('admin.applications.index')
                ->withErrors(['Application not found']);
        }
    }

    /**
     * Toggle application status (activate/deactivate).
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $nip = session('user_session.nip');
            $userRole = session('user_session.user_role');

            if (!$nip || $userRole !== 'admin_helpdesk') {
                return response()->json([
                    'success' => false,
                    'errors' => ['Access denied. Invalid session.'],
                ], 403);
            }

            $application = Aplikasi::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'reason' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()->all(),
                ], 422);
            }

            // Toggle status
            $oldStatus = $application->status;
            $newStatus = $application->status === 'active' ? 'inactive' : 'active';
            $application->update([
                'status' => $newStatus,
            ]);

            // Get the admin who made the change
            $admin = AdminAplikasi::where('nip', $nip)->first();

            // Log the status change using AuditLogService
            $this->auditLogService->log(
                'status_changed',
                $application,
                $admin,
                [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'application_name' => $application->name,
                    'application_code' => $application->code,
                    'application_id' => $application->id,
                    'changed_by' => $admin ? $admin->name : 'Unknown Admin',
                    'changed_by_nip' => $nip,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => "Application status changed to {$newStatus}",
                'new_status' => $newStatus,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to update application status'],
            ], 404);
        }
    }

    /**
     * Export applications data.
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

        $filters = $request->only(['status', 'admin_aplikasi_nip', 'search', 'sort_by', 'sort_direction']);

        $query = Aplikasi::with(['adminAplikasi', 'backupAdmin'])
            ->withCount([
                'tickets',
                'tickets as open_tickets_count' => function ($query) {
                    $query->whereIn('status', ['open', 'assigned', 'in_progress']);
                },
                'tickets as resolved_tickets_count' => function ($query) {
                    $query->where('status', 'resolved');
                },
                'kategoriMasalahs',
                'assignedTeknis'
            ]);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['admin_aplikasi_nip'])) {
            $query->where('admin_aplikasi_nip', $filters['admin_aplikasi_nip']);
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

        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            case 'code':
                $query->orderBy('code', $sortDirection);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortDirection);
                break;
            case 'total_tickets':
                $query->orderBy('tickets_count', $sortDirection);
                break;
            case 'status':
                $query->orderBy('status', $sortDirection);
                break;
            default:
                $query->orderBy('name', $sortDirection);
                break;
        }

        $applications = $query->get();
        $recordCount = $applications->count();
        
        // Log the export operation
        AuditLogService::logDataExported('Aplikasi', $recordCount, $filters);

        $filename = 'applications_export_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => '0',
            'Pragma' => 'public',
        ];

        $callback = function () use ($applications) {
            // Clear any existing output buffers to prevent HTML contamination
            if (ob_get_level()) {
                ob_end_clean();
            }

            $file = fopen('php://output', 'w');

            // CSV header
            fputcsv($file, [
                'ID', 'Application Name', 'Application Code', 'Version', 'Status',
                'Admin Aplikasi', 'Admin NIP', 'Admin Email',
                'Backup Admin', 'Backup Email', 'Total Tickets',
                'Open Tickets', 'Resolved Tickets', 'Categories',
                'Assigned Teknisi', 'Description', 'Created At'
            ]);

            // CSV data
            foreach ($applications as $app) {
                fputcsv($file, [
                    $app->id,
                    $app->name ?? '',
                    $app->code ?? '',
                    $app->version ?? 'N/A',
                    ucfirst($app->status ?? ''),
                    $app->adminAplikasi?->name ?? 'Not assigned',
                    $app->adminAplikasi?->nip ?? 'N/A',
                    $app->adminAplikasi?->email ?? 'N/A',
                    $app->backupAdmin?->name ?? 'Not assigned',
                    $app->backupAdmin?->email ?? 'N/A',
                    $app->tickets_count ?? 0,
                    $app->open_tickets_count ?? 0,
                    $app->resolved_tickets_count ?? 0,
                    $app->kategori_masalahs_count ?? 0,
                    $app->assigned_teknis_count ?? 0,
                    strip_tags(str_replace(["\r", "\n"], ' ', $app->description ?? 'N/A')),
                    $app->created_at?->format('Y-m-d H:i:s') ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get filtered applications with advanced filtering.
     */
    private function getFilteredApplications(array $filters, int $perPage = 15)
    {
        $query = Aplikasi::with(['adminAplikasi', 'backupAdmin']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['admin_aplikasi_nip'])) {
            $query->where('admin_aplikasi_nip', $filters['admin_aplikasi_nip']);
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

        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            case 'code':
                $query->orderBy('code', $sortDirection);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortDirection);
                break;
            case 'total_tickets':
                $query->withCount('tickets')->orderBy('tickets_count', $sortDirection);
                break;
            case 'status':
                $query->orderBy('status', $sortDirection);
                break;
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        return $query->paginate($perPage);
    }

    /**
     * Get filter options for applications listing.
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
            'admin_aplikasis' => AdminAplikasi::orderBy('name')
                ->get(['nip', 'name', 'email'])
                ->map(function ($admin) {
                    return [
                        'value' => $admin->nip,
                        'label' => $admin->name . ' (' . $admin->nip . ')',
                        'email' => $admin->email,
                    ];
                }),
        ];
    }

    /**
     * Get application statistics for overview.
     */
    private function getApplicationStats(): array
    {
        $totalApps = Aplikasi::count();
        $activeApps = Aplikasi::where('status', 'active')->count();
        $inactiveApps = Aplikasi::where('status', 'inactive')->count();
        $maintenanceApps = Aplikasi::where('status', 'maintenance')->count();
        $deprecatedApps = Aplikasi::where('status', 'deprecated')->count();

        // Get tickets stats
        $totalTickets = Ticket::count();
        $openTickets = Ticket::where('status', Ticket::STATUS_OPEN)->count();
        $resolvedTickets = Ticket::where('status', Ticket::STATUS_RESOLVED)->count();

        return [
            'total_applications' => $totalApps,
            'active_applications' => $activeApps,
            'inactive_applications' => $inactiveApps,
            'maintenance_applications' => $maintenanceApps,
            'deprecated_applications' => $deprecatedApps,
            'total_tickets' => $totalTickets,
            'open_tickets' => $openTickets,
            'resolved_tickets' => $resolvedTickets,
            'resolution_rate' => $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 1) : 0,
        ];
    }

    /**
     * Get detailed ticket statistics for a specific application.
     */
    private function getApplicationTicketStats(Aplikasi $application): array
    {
        $tickets = $application->tickets();

        $totalTickets = $tickets->count();
        $openTickets = $tickets->where('status', Ticket::STATUS_OPEN)->count();
        $inProgressTickets = $tickets->where('status', Ticket::STATUS_IN_PROGRESS)->count();
        $resolvedTickets = $tickets->where('status', Ticket::STATUS_RESOLVED)->count();
        $closedTickets = $tickets->where('status', Ticket::STATUS_CLOSED)->count();

        // Calculate average resolution time
        $avgResolutionTime = $tickets
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereNotNull('resolution_time_minutes')
            ->avg('resolution_time_minutes');

        // Get tickets by priority
        $ticketsByPriority = $tickets->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        return [
            'total_tickets' => $totalTickets,
            'open_tickets' => $openTickets,
            'in_progress_tickets' => $inProgressTickets,
            'resolved_tickets' => $resolvedTickets,
            'closed_tickets' => $closedTickets,
            'avg_resolution_time_hours' => $avgResolutionTime ? round($avgResolutionTime / 60, 2) : null,
            'tickets_by_priority' => $ticketsByPriority,
        ];
    }

    /**
     * Store a newly created application.
     */
    public function store(Request $request)
    {
        try {
            $nip = session('user_session.nip');
            $userRole = session('user_session.user_role');

            if (!$nip || $userRole !== 'admin_helpdesk') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Invalid session.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:aplikasis,code',
                'description' => 'nullable|string|max:1000',
                'version' => 'nullable|string|max:20',
                'status' => 'required|string|in:active,inactive,maintenance,deprecated',
                'admin_aplikasi_nip' => 'sometimes|string|nullable|exists:admin_aplikasis,nip',
                'backup_admin_nip' => 'sometimes|string|nullable|exists:admin_aplikasis,nip',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $application = Aplikasi::create([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'version' => $request->version ?? '1.0.0',
                'status' => $request->status,
                'admin_aplikasi_nip' => $request->admin_aplikasi_nip ?: null,
                'backup_admin_nip' => $request->backup_admin_nip ?: null,
            ]);

            // Log the creation using AuditLogService
            $this->auditLogService->log(
                'created',
                $application,
                null,
                [
                    'application_name' => $application->name,
                    'application_code' => $application->code,
                    'application_id' => $application->id,
                    'created_by' => $nip,
                    'created_by_role' => $userRole,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Application created successfully',
                'data' => $application,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create application: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified application.
     */
    public function update(Request $request, $id)
    {
        try {
            $nip = session('user_session.nip');
            $userRole = session('user_session.user_role');

            if (!$nip || $userRole !== 'admin_helpdesk') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Invalid session.',
                ], 403);
            }

            $application = Aplikasi::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:aplikasis,code,' . $id,
                'description' => 'nullable|string|max:1000',
                'version' => 'nullable|string|max:20',
                'status' => 'required|string|in:active,inactive,maintenance,deprecated',
                'admin_aplikasi_nip' => 'sometimes|string|nullable|exists:admin_aplikasis,nip',
                'backup_admin_nip' => 'sometimes|string|nullable|exists:admin_aplikasis,nip',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $oldData = $application->toArray();

            $application->update([
                'name' => $request->name,
                'code' => $request->code,
                'description' => $request->description,
                'version' => $request->version ?? $application->version,
                'status' => $request->status,
                'admin_aplikasi_nip' => $request->admin_aplikasi_nip ?: null,
                'backup_admin_nip' => $request->backup_admin_nip ?: null,
            ]);

            // Log the update using AuditLogService
            $this->auditLogService->log(
                'updated',
                $application,
                null,
                [
                    'application_name' => $application->name,
                    'application_code' => $application->code,
                    'application_id' => $application->id,
                    'old_data' => $oldData,
                    'new_data' => $application->toArray(),
                    'updated_by' => $nip,
                    'updated_by_role' => $userRole,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Application updated successfully',
                'data' => $application,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update application: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified application.
     */
    public function destroy($id)
    {
        try {
            $nip = session('user_session.nip');
            $userRole = session('user_session.user_role');

            if (!$nip || $userRole !== 'admin_helpdesk') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Invalid session.',
                ], 403);
            }

            $application = Aplikasi::findOrFail($id);

            // Check if application has tickets
            $ticketCount = $application->tickets()->count();
            if ($ticketCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete application. It has {$ticketCount} associated tickets.",
                ], 400);
            }

            // Log the deletion using AuditLogService
            $this->auditLogService->log(
                'deleted',
                $application,
                null,
                [
                    'application_name' => $application->name,
                    'application_code' => $application->code,
                    'application_id' => $application->id,
                    'deleted_by' => $nip,
                    'deleted_by_role' => $userRole,
                ]
            );

            $application->delete();

            return response()->json([
                'success' => true,
                'message' => 'Application deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete application: ' . $e->getMessage(),
            ], 500);
        }
    }
}