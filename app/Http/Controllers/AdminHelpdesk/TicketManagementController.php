<?php

namespace App\Http\Controllers\AdminHelpdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Ticket;
use App\Models\User;
use App\Models\AdminHelpdesk;
use App\Models\Teknisi;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\TicketHistory;
use App\Models\Notification;
use App\Services\TicketService;
use App\Services\BulkActionNotificationService;
use App\Services\AuditLogService;
use Carbon\Carbon;

class TicketManagementController extends Controller
{
    protected $ticketService;
    protected $bulkActionNotificationService;

    public function __construct(TicketService $ticketService, BulkActionNotificationService $bulkActionNotificationService)
    {
        $this->ticketService = $ticketService;
        $this->bulkActionNotificationService = $bulkActionNotificationService;
    }

    /**
     * Display a specific ticket with full details.
     */
    public function show($id)
    {
        try {
            $admin = Auth::user();

            $ticket = Ticket::with([
                'user',
                'aplikasi',
                'kategoriMasalah',
                'assignedTeknisi',
                'comments' => function ($query) {
                    $query->with('commenter')->orderBy('created_at', 'desc');
                },
                'history' => function ($query) {
                    $query->with('performedBy')->orderBy('created_at', 'desc');
                }
            ])->findOrFail($id);

            // Format ticket data for frontend
            $formattedTicket = [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'status_label' => $ticket->status_label,
                'status_badge_color' => $ticket->status_badge_color,
                'priority' => $ticket->priority,
                'priority_label' => $ticket->priority_label,
                'priority_badge_color' => $ticket->priority_badge_color,
                'location' => $ticket->location,
                'device_info' => $ticket->device_info,
                'ip_address' => $ticket->ip_address,
                'user' => $ticket->user ? [
                    'nip' => $ticket->user->nip,
                    'name' => $ticket->user->name,
                    'department' => $ticket->user->department,
                    'email' => $ticket->user->email,
                ] : null,
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
                    'department' => $ticket->assignedTeknisi->department,
                ] : null,
                'created_at' => $ticket->created_at,
                'formatted_created_at' => $ticket->formatted_created_at,
                'updated_at' => $ticket->updated_at,
                'formatted_updated_at' => $ticket->formatted_updated_at,
                'resolved_at' => $ticket->resolved_at,
                'formatted_resolved_at' => $ticket->formatted_resolved_at,
                'is_overdue' => $ticket->is_overdue,
                'is_escalated' => $ticket->is_escalated,
                'sla_status' => $ticket->sla_status,
                'time_elapsed' => $ticket->time_elapsed,
                'resolution_time_minutes' => $ticket->resolution_time_minutes,
                'user_rating' => $ticket->user_rating,
                'user_feedback' => $ticket->user_feedback,
                'attachments' => $ticket->attachments ?? [],
            ];

            // Format comments
            $formattedComments = $ticket->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'is_internal' => $comment->is_internal,
                    'user' => $comment->commenter ? [
                        'nip' => $comment->commenter->nip,
                        'name' => $comment->commenter->name,
                        'role' => $this->getUserRole($comment->commenter),
                    ] : null,
                    'attachments' => $comment->attachments ?? [],
                    'created_at' => $comment->created_at,
                    'formatted_created_at' => $comment->created_at->diffForHumans(),
                ];
            });

            // Format history
            $formattedHistory = $ticket->history->map(function ($history) {
                return [
                    'id' => $history->id,
                    'old_status' => $history->old_status,
                    'new_status' => $history->new_status,
                    'notes' => $history->notes,
                    'action_type' => $history->action_type,
                    'user' => $history->performedBy ? [
                        'nip' => $history->performedBy->nip,
                        'name' => $history->performedBy->name,
                        'role' => $this->getUserRole($history->performedBy),
                    ] : null,
                    'created_at' => $history->created_at,
                    'formatted_created_at' => $history->created_at->diffForHumans(),
                ];
            });

            // Get available teknisi for reassignment
            $availableTeknisi = Teknisi::active()
                ->orderBy('name')
                ->get(['nip', 'name', 'department'])
                ->map(function ($tek) {
                    return [
                        'nip' => $tek->nip,
                        'name' => $tek->name,
                        'department' => $tek->department,
                    ];
                });

            return Inertia::render('AdminHelpdesk/TicketDetail', [
                'ticket' => $formattedTicket,
                'comments' => $formattedComments,
                'history' => $formattedHistory,
                'availableTeknisi' => $availableTeknisi,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in TicketManagementController@show: ' . $e->getMessage());
            return redirect()->route('admin.tickets-management.index')
                ->withErrors(['error' => 'Ticket not found']);
        }
    }

    /**
     * Display all tickets with advanced filtering and pagination.
     */
    public function index(Request $request)
    {
        $admin = Auth::user();

        // Get filter parameters
        $filters = $request->only([
            'status', 'priority', 'aplikasi_id', 'kategori_masalah_id',
            'assigned_teknisi_nip', 'user_nip', 'search', 'sort_by',
            'sort_direction', 'date_from', 'date_to', 'is_overdue',
            'is_escalated', 'is_assigned', 'sla_status'
        ]);

        // Set default sorting
        if (!isset($filters['sort_by'])) {
            $filters['sort_by'] = 'created_at';
            $filters['sort_direction'] = 'desc';
        }

        // Get paginated tickets with filters
        $tickets = $this->getFilteredTickets($filters, 20);

        // Format tickets for frontend
        $formattedTickets = $tickets->getCollection()->map(function ($ticket) {
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
                'user' => $ticket->user ? [
                    'nip' => $ticket->user->nip,
                    'name' => $ticket->user->name,
                    'department' => $ticket->user->department,
                ] : null,
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
                'is_escalated' => $ticket->is_escalated,
                'sla_status' => $ticket->sla_status,
                'time_elapsed' => $ticket->time_elapsed,
            ];
        });

        // Create new paginator with formatted data
        $formattedPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $formattedTickets,
            $tickets->total(),
            $tickets->perPage(),
            $tickets->currentPage(),
            ['path' => $tickets->path(), 'pageName' => 'page']
        );

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        // Get additional data for create ticket form
        $users = User::active()
            ->orderBy('name')
            ->get(['nip', 'name', 'department']);

        $applications = Aplikasi::active()
            ->with('kategoriMasalahs')
            ->orderBy('name')
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'name' => $app->name,
                    'code' => $app->code,
                    'kategori_masalahs' => $app->kategoriMasalahs->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                        ];
                    }),
                ];
            });

        $teknisis = Teknisi::active()
            ->orderBy('name')
            ->get(['nip', 'name', 'department'])
            ->map(function ($tek) {
                return [
                    'nip' => $tek->nip,
                    'name' => $tek->name,
                    'department' => $tek->department,
                    'expertise_aplikasis' => [], // Initialize empty for now
                ];
            });

        return Inertia::render('AdminHelpdesk/TicketManagement', [
            'tickets' => $formattedPaginator,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'users' => $users,
            'applications' => $applications,
            'teknisis' => $teknisis,
        ]);
    }

    /**
     * Assign a ticket to a technician.
     */
    public function assign(Request $request, $ticketId)
    {
        $admin = Auth::user();

        // Check if it's an AJAX request (for partial updates) or form submission
        $isAjax = $request->expectsJson();

        // Validate request
        $validator = Validator::make($request->all(), [
            'teknisi_nip' => 'required|string|exists:teknisis,nip',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()->all(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $ticket = Ticket::findOrFail($ticketId);
            $teknisi = Teknisi::where('nip', $request->teknisi_nip)->firstOrFail();

            // Assign ticket to teknisi
            $result = $ticket->assignToTeknisi(
                $teknisi->nip,
                $admin->nip,
                $request->notes
            );

            if (!$result) {
                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['Failed to assign ticket'],
                    ], 422);
                }
                return back()->withErrors(['error' => 'Failed to assign ticket'])->withInput();
            }

            // Log the assignment
            AuditLogService::logTicketAssigned($ticket, $teknisi);

            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket assigned successfully',
                    'ticket' => [
                        'id' => $ticket->id,
                        'assigned_teknisi' => [
                            'nip' => $teknisi->nip,
                            'name' => $teknisi->name,
                        ],
                    ],
                ]);
            }

            return back()->with('success', "Ticket {$ticket->ticket_number} assigned to {$teknisi->name} successfully");

        } catch (\Exception $e) {
            Log::error('Error in TicketManagementController@assign: ' . $e->getMessage());

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Ticket or technician not found'],
                ], 404);
            }
            return back()->withErrors(['error' => 'Ticket or technician not found'])->withInput();
        }
    }

    /**
     * Update ticket priority.
     */
    public function updatePriority(Request $request, $ticketId)
    {
        $admin = Auth::user();

        // Check if it's an AJAX request (for partial updates) or form submission
        $isAjax = $request->expectsJson();

        // Validate request
        $validator = Validator::make($request->all(), [
            'priority' => 'required|in:' . implode(',', [
                Ticket::PRIORITY_LOW, Ticket::PRIORITY_MEDIUM,
                Ticket::PRIORITY_HIGH, Ticket::PRIORITY_URGENT
            ]),
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()->all(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $ticket = Ticket::findOrFail($ticketId);

            // Update priority
            $oldPriority = $ticket->priority;
            $ticket->priority = $request->priority;

            if ($ticket->save()) {
                // Create history record
                $ticket->createHistoryRecord(
                    $ticket->status,
                    $ticket->status,
                    $admin->nip,
                    "Priority changed from {$oldPriority} to {$request->priority}: {$request->reason}",
                    'priority_change'
                );

                if ($isAjax) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Priority updated successfully',
                        'ticket' => [
                            'id' => $ticket->id,
                            'priority' => $ticket->priority,
                            'priority_label' => $ticket->priority_label,
                            'priority_badge_color' => $ticket->priority_badge_color,
                        ],
                    ]);
                }

                return back()->with('success', "Priority updated to {$ticket->priority_label} successfully");
            }

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Failed to update priority'],
                ], 422);
            }
            return back()->withErrors(['error' => 'Failed to update priority'])->withInput();

        } catch (\Exception $e) {
            Log::error('Error in TicketManagementController@updatePriority: ' . $e->getMessage());

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Ticket not found'],
                ], 404);
            }
            return back()->withErrors(['error' => 'Ticket not found'])->withInput();
        }
    }

    /**
     * Assign multiple tickets at once.
     */
    public function bulkAssign(Request $request)
    {
        $admin = Auth::user();

        // Validate request
        $validator = Validator::make($request->all(), [
            'ticket_ids' => 'required|array|min:1',
            'ticket_ids.*' => 'exists:tickets,id',
            'teknisi_nip' => 'required|string|exists:teknisis,nip',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors([
                'bulk_assign' => $validator->errors()->first()
            ])->with('error', $validator->errors()->first());
        }

        try {
            $teknisi = Teknisi::where('nip', $request->teknisi_nip)->firstOrFail();

            $successCount = 0;
            $errorCount = 0;
            $errorMessages = [];

            foreach ($request->ticket_ids as $ticketId) {
                try {
                    $ticket = Ticket::findOrFail($ticketId);

                    if ($ticket->assignToTeknisi($teknisi->nip, $admin->nip, $request->notes)) {
                        $successCount++;
                        // Log the assignment
                        AuditLogService::logTicketAssigned($ticket, $teknisi);
                    } else {
                        $errorCount++;
                        $errorMessages[] = "Failed to assign ticket {$ticket->ticket_number}";
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errorMessages[] = "Ticket ID {$ticketId} not found";
                }
            }

            // Prepare success message
            $message = "Successfully assigned {$successCount} ticket(s) to {$teknisi->name}";
            if ($errorCount > 0) {
                $message .= ". {$errorCount} ticket(s) failed.";
            }

            // Return back with success message
            if ($successCount > 0) {
                $flashData = ['success' => $message];
                if (!empty($errorMessages)) {
                    $flashData['warning'] = implode(', ', $errorMessages);
                }
                return back()->with($flashData);
            } else {
                return back()->withErrors([
                    'bulk_assign' => 'Failed to assign any tickets. ' . implode(', ', $errorMessages)
                ])->with('error', 'Failed to assign tickets');
            }

        } catch (\Exception $e) {
            Log::error('Bulk assign error: ' . $e->getMessage());
            return back()->withErrors([
                'bulk_assign' => 'Technician not found'
            ])->with('error', 'Technician not found');
        }
    }

    /**
     * Update multiple ticket statuses.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $admin = Auth::user();

        // Validate request
        $validator = Validator::make($request->all(), [
            'ticket_ids' => 'required|array|min:1',
            'ticket_ids.*' => 'exists:tickets,id',
            'status' => 'required|in:' . implode(',', [
                Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_WAITING_USER, Ticket::STATUS_WAITING_ADMIN, Ticket::STATUS_WAITING_RESPONSE,
                Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED,
                'waiting_response' // Accept frontend alias for waiting_user
            ]),
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($request->ticket_ids as $ticketId) {
            try {
                $ticket = Ticket::findOrFail($ticketId);

                if ($ticket->transitionTo($request->status, $admin->nip, $request->notes)) {
                    $results[] = [
                        'ticket_id' => $ticket->id,
                        'success' => true,
                        'ticket_number' => $ticket->ticket_number,
                    ];
                    $successCount++;
                } else {
                    $results[] = [
                        'ticket_id' => $ticket->id,
                        'success' => false,
                        'error' => 'Invalid status transition',
                    ];
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $results[] = [
                    'ticket_id' => $ticketId,
                    'success' => false,
                    'error' => 'Ticket not found',
                ];
                $errorCount++;
            }
        }

        return response()->json([
            'success' => $successCount > 0,
            'message' => "Updated {$successCount} tickets, {$errorCount} failed",
            'results' => $results,
            'summary' => [
                'total' => count($request->ticket_ids),
                'successful' => $successCount,
                'failed' => $errorCount,
            ],
        ]);
    }

    /**
     * Get system-wide ticket statistics.
     */
    public function getTicketStats(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $stats = [
            'overview' => [
                'total_tickets' => Ticket::count(),
                'open_tickets' => Ticket::where('status', Ticket::STATUS_OPEN)->count(),
                'in_progress_tickets' => Ticket::where('status', Ticket::STATUS_IN_PROGRESS)->count(),
                'resolved_tickets' => Ticket::where('status', Ticket::STATUS_RESOLVED)->count(),
                'closed_tickets' => Ticket::where('status', Ticket::STATUS_CLOSED)->count(),
                'overdue_tickets' => Ticket::overdue()->count(),
                'escalated_tickets' => Ticket::escalated()->count(),
                'unassigned_tickets' => Ticket::unassigned()->count(),
            ],

            'trends' => [
                'created_today' => Ticket::createdToday()->count(),
                'created_this_week' => Ticket::createdThisWeek()->count(),
                'created_this_month' => Ticket::createdThisMonth()->count(),
                'resolved_today' => Ticket::where('status', Ticket::STATUS_RESOLVED)
                    ->whereDate('resolved_at', Carbon::today())->count(),
                'resolved_this_week' => Ticket::where('status', Ticket::STATUS_RESOLVED)
                    ->whereBetween('resolved_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count(),
            ],

            'priority_breakdown' => Ticket::select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->get()
                ->pluck('count', 'priority')
                ->toArray(),

            'status_breakdown' => Ticket::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray(),

            'resolution_times' => [
                'average_resolution_time' => Ticket::where('status', Ticket::STATUS_RESOLVED)
                    ->whereNotNull('resolution_time_minutes')
                    ->whereBetween('resolved_at', [$startDate, $endDate])
                    ->selectRaw('AVG(resolution_time_minutes) / 60 as avg_hours')
                    ->value('avg_hours') ?? 0,

                'median_resolution_time' => Ticket::where('status', Ticket::STATUS_RESOLVED)
                    ->whereNotNull('resolution_time_minutes')
                    ->whereBetween('resolved_at', [$startDate, $endDate])
                    ->selectRaw('PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY resolution_time_minutes) / 60 as median_hours')
                    ->value('median_hours') ?? 0,
            ],

            'sla_performance' => [
                'within_sla' => Ticket::withinSla()
                    ->where('status', Ticket::STATUS_RESOLVED)
                    ->whereBetween('resolved_at', [$startDate, $endDate])
                    ->count(),
                'sla_breached' => Ticket::slaBreached()
                    ->where('status', Ticket::STATUS_RESOLVED)
                    ->whereBetween('resolved_at', [$startDate, $endDate])
                    ->count(),
            ],

            'teknisi_performance' => Teknisi::select('nip', 'name')
                ->withCount([
                    'assignedTickets as resolved_count' => function ($query) use ($startDate, $endDate) {
                        $query->where('status', Ticket::STATUS_RESOLVED)
                              ->whereBetween('resolved_at', [$startDate, $endDate]);
                    }
                ])
                ->withCount([
                    'assignedTickets as total_assigned' => function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                    }
                ])
                ->having('total_assigned', '>', 0)
                ->orderBy('resolved_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($teknisi) {
                    return [
                        'nip' => $teknisi->nip,
                        'name' => $teknisi->name,
                        'resolved_count' => $teknisi->resolved_count,
                        'total_assigned' => $teknisi->total_assigned,
                        'resolution_rate' => $teknisi->total_assigned > 0
                            ? round(($teknisi->resolved_count / $teknisi->total_assigned) * 100, 1)
                            : 0,
                    ];
                })
                ->toArray(),
        ];

        return response()->json($stats);
    }

    /**
     * Get filtered tickets with advanced filtering.
     */
    private function getFilteredTickets(array $filters, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Ticket::with(['user', 'aplikasi', 'kategoriMasalah', 'assignedTeknisi']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['aplikasi_id'])) {
            $query->where('aplikasi_id', $filters['aplikasi_id']);
        }

        if (!empty($filters['kategori_masalah_id'])) {
            $query->where('kategori_masalah_id', $filters['kategori_masalah_id']);
        }

        if (!empty($filters['assigned_teknisi_nip'])) {
            $query->where('assigned_teknisi_nip', $filters['assigned_teknisi_nip']);
        }

        if (!empty($filters['user_nip'])) {
            $query->where('user_nip', $filters['user_nip']);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['is_overdue'])) {
            if ($filters['is_overdue'] === 'true') {
                $query->whereHas('overdue');
            }
        }

        if (!empty($filters['is_escalated'])) {
            if ($filters['is_escalated'] === 'true') {
                $query->where('is_escalated', true);
            }
        }

        if (!empty($filters['is_assigned'])) {
            if ($filters['is_assigned'] === 'false') {
                $query->whereNull('assigned_teknisi_nip');
            } elseif ($filters['is_assigned'] === 'true') {
                $query->whereNotNull('assigned_teknisi_nip');
            }
        }

        if (!empty($filters['sla_status'])) {
            if ($filters['sla_status'] === 'breached') {
                $query->whereHas('slaBreached');
            } elseif ($filters['sla_status'] === 'within_sla') {
                $query->whereHas('withinSla');
            }
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        switch ($sortBy) {
            case 'priority':
                $query->orderByPriority($sortDirection);
                break;
            case 'due_date':
                $query->orderByDueDate($sortDirection);
                break;
            case 'updated_at':
                $query->orderBy('updated_at', $sortDirection);
                break;
            case 'created_at':
            default:
                $query->orderBy('created_at', $sortDirection);
                break;
        }

        return $query->paginate($perPage);
    }

    /**
     * Get filter options for the tickets listing.
     */
    private function getFilterOptions(): array
    {
        return [
            'statuses' => [
                ['value' => Ticket::STATUS_OPEN, 'label' => 'Open'],
                ['value' => Ticket::STATUS_ASSIGNED, 'label' => 'Assigned'],
                ['value' => Ticket::STATUS_IN_PROGRESS, 'label' => 'In Progress'],
                ['value' => Ticket::STATUS_WAITING_USER, 'label' => 'Waiting User'],
                ['value' => Ticket::STATUS_WAITING_ADMIN, 'label' => 'Waiting Admin'],
                ['value' => Ticket::STATUS_WAITING_RESPONSE, 'label' => 'Waiting Response'],
                ['value' => Ticket::STATUS_RESOLVED, 'label' => 'Resolved'],
                ['value' => Ticket::STATUS_CLOSED, 'label' => 'Closed'],
                ['value' => Ticket::STATUS_CANCELLED, 'label' => 'Cancelled'],
            ],
            'priorities' => [
                ['value' => Ticket::PRIORITY_LOW, 'label' => 'Low'],
                ['value' => Ticket::PRIORITY_MEDIUM, 'label' => 'Medium'],
                ['value' => Ticket::PRIORITY_HIGH, 'label' => 'High'],
                ['value' => Ticket::PRIORITY_URGENT, 'label' => 'Urgent'],
            ],
            'applications' => Aplikasi::active()
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(function ($app) {
                    return [
                        'value' => $app->id,
                        'label' => $app->name . ' (' . $app->code . ')',
                    ];
                }),
            'categories' => KategoriMasalah::active()
                ->with('aplikasi:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'aplikasi_id'])
                ->map(function ($cat) {
                    return [
                        'value' => $cat->id,
                        'label' => $cat->name . ($cat->aplikasi ? ' - ' . $cat->aplikasi->name : ''),
                    ];
                }),
            'teknisi' => Teknisi::active()
                ->orderBy('name')
                ->get(['nip', 'name'])
                ->map(function ($tek) {
                    return [
                        'value' => $tek->nip,
                        'label' => $tek->name . ' (' . $tek->nip . ')',
                    ];
                }),
            'users' => User::active()
                ->orderBy('name')
                ->get(['nip', 'name', 'department'])
                ->map(function ($user) {
                    return [
                        'value' => $user->nip,
                        'label' => $user->name . ' (' . $user->nip . ')' .
                                 ($user->department ? ' - ' . $user->department : ''),
                    ];
                }),
        ];
    }

    /**
     * Close a ticket.
     */
    public function close(Request $request, $id)
    {
        $admin = Auth::user();

        // Check if it's an AJAX request (for partial updates) or form submission
        $isAjax = $request->expectsJson();

        // Validate request
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:1000',
            'feedback' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()->all(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $ticket = Ticket::findOrFail($id);

            // Check if ticket can be closed
            if (!in_array($ticket->status, [
                Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_WAITING_RESPONSE, Ticket::STATUS_RESOLVED
            ])) {
                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['Ticket cannot be closed in current status'],
                    ], 400);
                }
                return back()->withErrors(['error' => 'Ticket cannot be closed in current status']);
            }

            // Close the ticket
            if ($ticket->transitionTo(Ticket::STATUS_CLOSED, $admin->nip, $request->reason)) {
                // Update resolved at timestamp if this is the first resolution
                if (!$ticket->resolved_at) {
                    $ticket->resolved_at = now();
                    $ticket->save();
                }

                // Add feedback if provided
                if ($request->filled('feedback')) {
                    $ticket->update(['admin_feedback' => $request->feedback]);
                }

                if ($isAjax) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Ticket closed successfully',
                        'ticket' => [
                            'id' => $ticket->id,
                            'status' => $ticket->status,
                            'status_label' => $ticket->status_label,
                            'status_badge_color' => $ticket->status_badge_color,
                            'resolved_at' => $ticket->resolved_at,
                        ],
                    ]);
                }

                return back()->with('success', "Ticket {$ticket->ticket_number} closed successfully");
            } else {
                if ($isAjax) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['Failed to close ticket'],
                    ], 422);
                }
                return back()->withErrors(['error' => 'Failed to close ticket']);
            }

        } catch (\Exception $e) {
            Log::error('Error in TicketManagementController@close: ' . $e->getMessage());

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Failed to close ticket'],
                ], 500);
            }
            return back()->withErrors(['error' => 'Failed to close ticket']);
        }
    }

    /**
     * Update a ticket (PUT/PATCH method).
     */
    public function update(Request $request, $id)
    {
        $admin = Auth::user();

        // Check if it's an AJAX request (for partial updates) or form submission
        $isAjax = $request->expectsJson();

        // Validate request
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|in:' . implode(',', [
                Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_WAITING_USER, Ticket::STATUS_WAITING_ADMIN, Ticket::STATUS_WAITING_RESPONSE,
                Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED,
                'waiting_response' // Accept frontend alias for waiting_user
            ]),
            'priority' => 'sometimes|required|in:' . implode(',', [
                Ticket::PRIORITY_LOW, Ticket::PRIORITY_MEDIUM,
                Ticket::PRIORITY_HIGH, Ticket::PRIORITY_URGENT
            ]),
            'assigned_teknisi_nip' => 'sometimes|nullable|string|exists:teknisis,nip',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()->all(),
                ], 422);
            }
            return redirect()->route('admin.tickets-management.show', $id)->withErrors($validator)->withInput();
        }

        try {
            $ticket = Ticket::findOrFail($id);
            $changes = [];

            // Convert frontend alias to backend status
            if ($request->has('status') && $request->status === 'waiting_response') {
                $request->merge(['status' => Ticket::STATUS_WAITING_USER]);
            }

            // Handle status change
            if ($request->has('status') && $request->status !== $ticket->status) {
                if ($ticket->transitionTo($request->status, $admin->nip, $request->notes)) {
                    $changes['status'] = [
                        'old' => $ticket->getOriginal('status'),
                        'new' => $request->status,
                        'new_label' => $ticket->status_label,
                    ];
                } else {
                    if ($isAjax) {
                        return response()->json([
                            'success' => false,
                            'errors' => ['Invalid status transition'],
                        ], 400);
                    }
                    return back()->withErrors(['error' => 'Invalid status transition'])->withInput();
                }
            }

            // Handle priority change
            if ($request->has('priority') && $request->priority !== $ticket->priority) {
                $oldPriority = $ticket->priority;
                $ticket->priority = $request->priority;
                if ($ticket->save()) {
                    $ticket->createHistoryRecord(
                        $ticket->status,
                        $ticket->status,
                        $admin->nip,
                        "Priority changed from {$oldPriority} to {$request->priority}: {$request->notes}",
                        'priority_change'
                    );
                    $changes['priority'] = [
                        'old' => $oldPriority,
                        'new' => $request->priority,
                        'new_label' => $ticket->priority_label,
                    ];
                }
            }

            // Handle assignment change
            if ($request->has('assigned_teknisi_nip')) {
                if ($request->assigned_teknisi_nip) {
                    $teknisi = Teknisi::where('nip', $request->assigned_teknisi_nip)->first();
                    if ($teknisi && $ticket->assignToTeknisi($teknisi->nip, $admin->nip, $request->notes)) {
                        $changes['assigned_teknisi'] = [
                            'old' => $ticket->assignedTeknisi ? $ticket->assignedTeknisi->name : 'Unassigned',
                            'new' => $teknisi->name,
                        ];
                    }
                } else {
                    // Unassign ticket
                    $oldTeknisi = $ticket->assignedTeknisi;
                    $ticket->assigned_teknisi_nip = null;
                    $ticket->assigned_at = null;
                    if ($ticket->save()) {
                        $ticket->createHistoryRecord(
                            $ticket->status,
                            $ticket->status,
                            $admin->nip,
                            "Ticket unassigned from " . ($oldTeknisi ? $oldTeknisi->name : 'unknown') . ": {$request->notes}",
                            'unassignment'
                        );
                        $changes['assigned_teknisi'] = [
                            'old' => $oldTeknisi ? $oldTeknisi->name : 'Unassigned',
                            'new' => 'Unassigned',
                        ];
                    }
                }
            }

            // Reload ticket with relationships
            $ticket->load(['user', 'aplikasi', 'kategoriMasalah', 'assignedTeknisi']);

            $responseData = [
                'ticket' => [
                    'id' => $ticket->id,
                    'status' => $ticket->status,
                    'status_label' => $ticket->status_label,
                    'status_badge_color' => $ticket->status_badge_color,
                    'priority' => $ticket->priority,
                    'priority_label' => $ticket->priority_label,
                    'priority_badge_color' => $ticket->priority_badge_color,
                    'assigned_teknisi' => $ticket->assignedTeknisi ? [
                        'nip' => $ticket->assignedTeknisi->nip,
                        'name' => $ticket->assignedTeknisi->name,
                    ] : null,
                    'updated_at' => $ticket->updated_at,
                ],
                'changes' => $changes,
            ];

            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket updated successfully',
                    'data' => $responseData,
                ]);
            }

            return redirect()->route('admin.tickets-management.show', $id)->with('success', 'Ticket updated successfully');

        } catch (\Exception $e) {
            Log::error('Error in TicketManagementController@update: ' . $e->getMessage());

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Failed to update ticket'],
                ], 500);
            }
            return redirect()->route('admin.tickets-management.show', $id)->withErrors(['error' => 'Failed to update ticket'])->withInput();
        }
    }

    /**
     * Update ticket status (POST method for modals).
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            Log::info('updateStatus called', [
                'ticket_id' => $id,
                'request_data' => $request->all(),
                'headers' => $request->headers->all(),
                'method' => $request->method(),
                'url' => $request->fullUrl()
            ]);

            $ticket = Ticket::findOrFail($id);

            // Get admin user first
            $admin = Auth::user();
            if (!$admin) {
                Log::error('No authenticated user found in updateStatus');
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            // Simplified validation for debugging
            $request->validate([
                'status' => 'required|string',
                'notes' => 'nullable|string|max:500',
            ]);

            Log::info('Validation passed, proceeding with status update');

            // Get validated data
            $validated = $request->only(['status', 'notes']);
            Log::info('Validated data', ['validated_data' => $validated]);

            // Convert frontend alias to backend status
            if ($validated['status'] === 'waiting_response') {
                $validated['status'] = Ticket::STATUS_WAITING_USER;
                Log::info('Converted waiting_response to waiting_user');
            }

            // Handle status change
            if ($validated['status'] !== $ticket->status) {
                Log::info('Attempting status transition', ['from' => $ticket->status, 'to' => $validated['status']]);

                $success = $ticket->transitionTo($validated['status'], $admin->nip, $validated['notes'] ?? '');

                if ($success) {
                    $freshTicket = $ticket->fresh();
                    Log::info('Status transition successful', ['new_status' => $freshTicket->status]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Ticket status updated successfully',
                        'data' => [
                            'id' => $freshTicket->id,
                            'status' => $freshTicket->status,
                            'status_label' => $freshTicket->status_label,
                            'status_badge_color' => $freshTicket->status_badge_color,
                        ],
                    ]);
                } else {
                    Log::error('Status transition failed', ['from' => $ticket->status, 'to' => $validated['status']]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid status transition',
                    ], 400);
                }
            } else {
                Log::info('No status change needed', ['current_status' => $ticket->status]);
                return response()->json([
                    'success' => true,
                    'message' => 'No status change needed',
                ]);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('ValidationException in updateStatus: ' . $e->getMessage(), ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors()),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error in TicketManagementController@updateStatus: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update ticket status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a ticket.
     */
    public function destroy($id)
    {
        $admin = Auth::user();

        try {
            $ticket = Ticket::findOrFail($id);

            // Only allow deletion of open/closed tickets for safety
            if (!in_array($ticket->status, [Ticket::STATUS_OPEN, Ticket::STATUS_CLOSED])) {
                return $this->errorResponse('Cannot delete ticket in current status. Only open or closed tickets can be deleted.', [], 400);
            }

            // Store ticket info for logging
            $ticketInfo = [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'title' => $ticket->title,
                'status' => $ticket->status,
            ];

            if ($ticket->delete()) {
                Log::info('Ticket deleted by admin', [
                    'admin_nip' => $admin->nip,
                    'ticket_info' => $ticketInfo,
                ]);

                return $this->successResponse([
                    'deleted_ticket' => $ticketInfo,
                ], 'Ticket deleted successfully');
            } else {
                return $this->errorResponse('Failed to delete ticket', [], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error in TicketManagementController@destroy: ' . $e->getMessage());
            return $this->errorResponse('Failed to delete ticket', [$e->getMessage()], 500);
        }
    }

    /**
     * Handle bulk actions on tickets.
     */
    public function bulkAction(Request $request)
    {
        $admin = Auth::user();

        // Check if it's an AJAX request (for partial updates) or form submission
        $isAjax = $request->expectsJson();

        // Validate request with conditional rules
        $rules = [
            'action' => 'required|in:assign,update_status,update_priority,delete,close',
            'ticket_ids' => 'required|array|min:1',
            'ticket_ids.*' => 'exists:tickets,id',
            'notes' => 'nullable|string|max:500',
        ];

        // Add conditional rules based on action
        if ($request->input('action') === 'assign') {
            $rules['teknisi_nip'] = 'required|string|exists:teknisis,nip';
        } elseif ($request->input('action') === 'update_status') {
            $rules['status'] = 'required|in:' . implode(',', [
                Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED, Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_WAITING_USER, Ticket::STATUS_WAITING_ADMIN, Ticket::STATUS_WAITING_RESPONSE,
                Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELLED,
                'waiting_response' // Accept frontend alias for waiting_user
            ]);
        } elseif ($request->input('action') === 'update_priority') {
            $rules['priority'] = 'required|in:' . implode(',', [
                Ticket::PRIORITY_LOW, Ticket::PRIORITY_MEDIUM,
                Ticket::PRIORITY_HIGH, Ticket::PRIORITY_URGENT
            ]);
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()->all(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // Convert frontend alias to backend status for bulk operations
        if ($request->input('action') === 'update_status' && $request->status === 'waiting_response') {
            $request->merge(['status' => Ticket::STATUS_WAITING_USER]);
        }

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        try {
            $processedTickets = [];
            $oldPriorities = []; // Track old priorities for notifications

            foreach ($request->ticket_ids as $ticketId) {
                try {
                    $ticket = Ticket::findOrFail($ticketId);

                    // Capture old values before any updates
                    if ($request->action === 'update_priority') {
                        $oldPriorities[$ticketId] = $ticket->priority;
                        Log::info("Captured old priority for ticket", [
                            'ticket_id' => $ticketId,
                            'old_priority' => $ticket->priority,
                            'old_priorities_array_size' => count($oldPriorities)
                        ]);
                    }

                    $processedTickets[] = $ticket;
                    $result = ['ticket_id' => $ticketId, 'ticket_number' => $ticket->ticket_number];

                    switch ($request->action) {
                        case 'assign':
                            $teknisi = Teknisi::where('nip', $request->teknisi_nip)->first();
                            $oldAssignedTo = $ticket->assigned_teknisi_nip;

                            if ($ticket->assignToTeknisi($teknisi->nip, $admin->nip, $request->notes)) {
                                $result['success'] = true;
                                $result['message'] = "Assigned to {$teknisi->name}";
                                $successCount++;
                            } else {
                                $result['success'] = false;
                                $result['error'] = 'Failed to assign ticket';
                                $errorCount++;
                            }
                            break;

                        case 'update_status':
                            $oldStatus = $ticket->status;
                            if ($ticket->transitionTo($request->status, $admin->nip, $request->notes)) {
                                $result['success'] = true;
                                $result['message'] = "Status updated to {$ticket->status_label}";
                                $successCount++;
                            } else {
                                $result['success'] = false;
                                $result['error'] = 'Invalid status transition';
                                $errorCount++;
                            }
                            break;

                        case 'update_priority':
                            $oldPriority = $oldPriorities[$ticketId] ?? 'unknown';
                            $ticket->priority = $request->priority;

                            Log::info("Updating ticket priority", [
                                'ticket_id' => $ticketId,
                                'ticket_number' => $ticket->ticket_number,
                                'old_priority' => $oldPriority,
                                'new_priority' => $request->priority,
                                'admin_nip' => $admin->nip
                            ]);

                            if ($ticket->save()) {
                                try {
                                    $ticket->createHistoryRecord(
                                        $ticket->status,
                                        $ticket->status,
                                        $admin->nip,
                                        "Priority changed from {$oldPriority} to {$request->priority}: {$request->notes}",
                                        'priority_change'
                                    );

                                    Log::info("Ticket priority updated successfully", [
                                        'ticket_id' => $ticketId,
                                        'new_priority' => $ticket->priority
                                    ]);

                                    $result['success'] = true;
                                    $result['message'] = "Priority updated to " . ($ticket->priority_label ?? $request->priority);
                                    $successCount++;
                                } catch (\Exception $historyException) {
                                    Log::error("Failed to create history record for priority change", [
                                        'ticket_id' => $ticketId,
                                        'error' => $historyException->getMessage(),
                                        'trace' => $historyException->getTraceAsString()
                                    ]);

                                    // Still count as success since the priority was updated
                                    $result['success'] = true;
                                    $result['message'] = "Priority updated to " . ($ticket->priority_label ?? $request->priority) . " (history record failed)";
                                    $successCount++;
                                }
                            } else {
                                Log::error("Failed to save ticket priority", [
                                    'ticket_id' => $ticketId,
                                    'old_priority' => $oldPriority,
                                    'new_priority' => $request->priority,
                                    'ticket_was_dirty' => $ticket->wasDirty(),
                                    'ticket_changes' => $ticket->getChanges()
                                ]);

                                $result['success'] = false;
                                $result['error'] = 'Failed to update priority';
                                $errorCount++;
                            }
                            break;

                        case 'close':
                            if ($ticket->transitionTo(Ticket::STATUS_CLOSED, $admin->nip, $request->notes)) {
                                $result['success'] = true;
                                $result['message'] = 'Ticket closed successfully';
                                $successCount++;
                            } else {
                                $result['success'] = false;
                                $result['error'] = 'Failed to close ticket';
                                $errorCount++;
                            }
                            break;

                        case 'delete':
                            // Only allow deletion of open/closed tickets
                            if (in_array($ticket->status, [Ticket::STATUS_OPEN, Ticket::STATUS_CLOSED])) {
                                if ($ticket->delete()) {
                                    $result['success'] = true;
                                    $result['message'] = 'Ticket deleted successfully';
                                    $successCount++;
                                } else {
                                    $result['success'] = false;
                                    $result['error'] = 'Failed to delete ticket';
                                    $errorCount++;
                                }
                            } else {
                                $result['success'] = false;
                                $result['error'] = 'Cannot delete ticket in current status';
                                $errorCount++;
                            }
                            break;

                        default:
                            $result['success'] = false;
                            $result['error'] = 'Unknown action: ' . $request->action;
                            $errorCount++;
                    }

                    $results[] = $result;

                    Log::info("Processed ticket", [
                        'ticket_id' => $ticketId,
                        'result_success' => $result['success'] ?? false,
                        'result_message' => $result['message'] ?? 'No message',
                        'result_error' => $result['error'] ?? null,
                        'running_success_count' => $successCount,
                        'running_error_count' => $errorCount
                    ]);

                } catch (\Exception $e) {
                    Log::error("Exception processing ticket", [
                        'ticket_id' => $ticketId,
                        'error' => $e->getMessage(),
                        'running_success_count' => $successCount,
                        'running_error_count' => $errorCount
                    ]);

                    $results[] = [
                        'ticket_id' => $ticketId,
                        'success' => false,
                        'error' => 'Ticket not found or error: ' . $e->getMessage(),
                    ];
                    $errorCount++;
                }
            }

            // Create notifications for successful bulk actions with enhanced error handling
            if ($successCount > 0 && !empty($processedTickets)) {
                Log::info("Attempting to create notifications", [
                    'success_count' => $successCount,
                    'processed_tickets_count' => count($processedTickets),
                    'action' => $request->action
                ]);

                try {
                    switch ($request->action) {
                        case 'update_status':
                            $oldStatus = $processedTickets[0]->getOriginal('status') ?: 'unknown';
                            $this->bulkActionNotificationService->executeBulkOperation(
                                function() use ($processedTickets, $oldStatus, $request, $admin) {
                                    $this->bulkActionNotificationService->createStatusChangeNotifications(
                                        $processedTickets,
                                        $oldStatus,
                                        $request->status,
                                        $admin->nip,
                                        $request->notes
                                    );
                                    return ['success' => true];
                                },
                                $processedTickets,
                                'status_change_notifications'
                            );
                            break;

                        case 'update_priority':
                            Log::info("Creating priority notifications", [
                                'old_priorities' => $oldPriorities,
                                'new_priority' => $request->priority,
                                'tickets_count' => count($processedTickets)
                            ]);

                            // Use the tracked old priorities for notifications
                            $this->bulkActionNotificationService->executeBulkOperation(
                                function() use ($processedTickets, $oldPriorities, $request, $admin) {
                                    $this->bulkActionNotificationService->createPriorityChangeNotifications(
                                        $processedTickets,
                                        $oldPriorities,
                                        $request->priority,
                                        $admin->nip,
                                        $request->notes
                                    );
                                    return ['success' => true];
                                },
                                $processedTickets,
                                'priority_change_notifications'
                            );
                            break;

                        case 'assign':
                            $this->bulkActionNotificationService->executeBulkOperation(
                                function() use ($processedTickets, $request, $admin) {
                                    $this->bulkActionNotificationService->createAssignmentNotifications(
                                        $processedTickets,
                                        $request->teknisi_nip,
                                        $admin->nip,
                                        $request->notes
                                    );
                                    return ['success' => true];
                                },
                                $processedTickets,
                                'assignment_notifications'
                            );
                            break;

                        case 'close':
                            $this->bulkActionNotificationService->executeBulkOperation(
                                function() use ($processedTickets, $admin, $request) {
                                    $this->bulkActionNotificationService->createClosureNotifications(
                                        $processedTickets,
                                        $admin->nip,
                                        $request->notes
                                    );
                                    return ['success' => true];
                                },
                                $processedTickets,
                                'closure_notifications'
                            );
                            break;
                    }

                    Log::info("Notifications created successfully");

                    // Invalidate affected user caches with comprehensive error handling
                    try {
                        $this->bulkActionNotificationService->invalidateAffectedUserCaches($processedTickets);
                    } catch (\Exception $e) {
                        Log::warning('Failed to invalidate caches after bulk action', [
                            'action' => $request->action,
                            'error' => $e->getMessage(),
                            'ticket_count' => count($processedTickets)
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to create bulk action notifications', [
                        'action' => $request->action,
                        'error' => $e->getMessage(),
                        'ticket_count' => count($processedTickets),
                        'stack_trace' => $e->getTraceAsString()
                    ]);
                    // Don't let notification failures affect the main operation success
                }
            }

            Log::info("Bulk action completed - Final count analysis", [
                'action' => $request->action,
                'total_tickets_requested' => count($request->ticket_ids),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'total_results' => count($results),
                'admin_nip' => $admin->nip,
                'final_success_value' => $successCount > 0,
                'successful_results' => array_filter($results, function($r) { return $r['success'] ?? false; }),
                'failed_results' => array_filter($results, function($r) { return !($r['success'] ?? false); })
            ]);

            $message = "Processed {$successCount} tickets successfully, {$errorCount} failed";

            if ($isAjax) {
                $response = [
                    'success' => $successCount > 0,
                    'message' => $message,
                    'results' => $results,
                    'summary' => [
                        'total' => count($request->ticket_ids),
                        'successful' => $successCount,
                        'failed' => $errorCount,
                    ],
                ];

                Log::info("Sending AJAX response for bulk action", $response);

                return response()->json($response);
            }

            if ($successCount > 0) {
                return back()->with('success', $message);
            } else {
                return back()->withErrors(['error' => 'No tickets were processed successfully']);
            }

        } catch (\Exception $e) {
            Log::error('Error in TicketManagementController@bulkAction: ' . $e->getMessage(), [
                'action' => $request->action,
                'ticket_ids' => $request->ticket_ids,
                'admin_nip' => $admin->nip,
                'trace' => $e->getTraceAsString()
            ]);

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Bulk action failed: ' . $e->getMessage()],
                ], 500);
            }
            return back()->withErrors(['error' => 'Bulk action failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Export tickets to Excel/CSV.
     */
    public function export(Request $request)
    {
        try {
            $admin = Auth::user();

            // Get filter parameters (same as index)
            $filters = $request->only([
                'status', 'priority', 'aplikasi_id', 'kategori_masalah_id',
                'assigned_teknisi_nip', 'user_nip', 'search', 'sort_by',
                'sort_direction', 'date_from', 'date_to', 'is_overdue',
                'is_escalated', 'is_assigned', 'sla_status'
            ]);

            // Set default sorting
            if (!isset($filters['sort_by'])) {
                $filters['sort_by'] = 'created_at';
                $filters['sort_direction'] = 'desc';
            }

            // Get filtered tickets (no pagination for export)
            $ticketsQuery = $this->getFilteredTickets($filters, 1000); // Max 1000 for export
            $tickets = $ticketsQuery->getCollection();

            // Format data for export
            $exportData = $tickets->map(function ($ticket) {
                return [
                    'Ticket Number' => $ticket->ticket_number,
                    'Title' => $ticket->title,
                    'Description' => strip_tags($ticket->description),
                    'Status' => $ticket->status_label,
                    'Priority' => $ticket->priority_label,
                    'User NIP' => $ticket->user ? $ticket->user->nip : '',
                    'User Name' => $ticket->user ? $ticket->user->name : '',
                    'User Department' => $ticket->user ? $ticket->user->department : '',
                    'Application' => $ticket->aplikasi ? $ticket->aplikasi->name : '',
                    'Category' => $ticket->kategoriMasalah ? $ticket->kategoriMasalah->name : '',
                    'Assigned Teknisi' => $ticket->assignedTeknisi ? $ticket->assignedTeknisi->name : '',
                    'Created At' => $ticket->created_at->format('Y-m-d H:i:s'),
                    'Updated At' => $ticket->updated_at->format('Y-m-d H:i:s'),
                    'Resolved At' => $ticket->resolved_at ? $ticket->resolved_at->format('Y-m-d H:i:s') : '',
                    'Resolution Time (minutes)' => $ticket->resolution_time_minutes ?? '',
                    'Is Overdue' => $ticket->is_overdue ? 'Yes' : 'No',
                    'SLA Status' => $ticket->sla_status ?? '',
                    'User Rating' => $ticket->user_rating ?? '',
                    'User Feedback' => $ticket->user_feedback ?? '',
                ];
            })->toArray();

            // Generate filename
            $filename = 'tickets_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

            // Set headers for CSV download
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            // Generate CSV
            $callback = function () use ($exportData) {
                $file = fopen('php://output', 'w');

                if (!empty($exportData)) {
                    // Add header row
                    fputcsv($file, array_keys($exportData[0]));

                    // Add data rows
                    foreach ($exportData as $row) {
                        fputcsv($file, $row);
                    }
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error in TicketManagementController@export: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to export tickets: ' . $e->getMessage()]);
        }
    }

    /**
     * Add comment to a ticket.
     */
    public function addComment(Request $request, $id)
    {
        $admin = Auth::user();

        // Validate request
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:2000',
            'is_internal' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()->all(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $ticket = Ticket::findOrFail($id);

            // Create comment
            $comment = $ticket->comments()->create([
                'comment' => $request->comment,
                'is_internal' => $request->boolean('is_internal', false),
                'commenter_nip' => $admin->nip,
                'commenter_type' => $this->getUserRoleType($admin),
            ]);

            // Create history record
            $ticket->createHistoryRecord(
                $ticket->status,
                $ticket->status,
                $admin->nip,
                "Comment added" . ($request->boolean('is_internal') ? ' (internal)' : ''),
                'comment'
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Comment added successfully',
                    'comment' => [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'is_internal' => $comment->is_internal,
                        'created_at' => $comment->created_at,
                        'formatted_created_at' => $comment->created_at->diffForHumans(),
                        'user' => [
                            'name' => $admin->name,
                            'role' => $this->getUserRole($admin),
                        ],
                    ],
                ]);
            }

            return back()->with('success', 'Comment added successfully');

        } catch (\Exception $e) {
            Log::error('Error in TicketManagementController@addComment: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Failed to add comment'],
                ], 500);
            }
            return back()->withErrors(['error' => 'Failed to add comment']);
        }
    }

    /**
     * Get comments for a ticket.
     */
    public function getComments(Request $request, $id)
    {
        try {
            $ticket = Ticket::findOrFail($id);

            $comments = $ticket->comments()
                ->with(['user' => function($query) {
                    $query->select('nip', 'name', 'email', 'avatar');
                }])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'is_internal' => $comment->is_internal,
                        'created_at' => $comment->created_at,
                        'formatted_created_at' => $comment->created_at->diffForHumans(),
                        'user' => [
                            'name' => $comment->user->name ?? 'Unknown',
                            'nip' => $comment->user_nip,
                        ],
                    ];
                });

            return response()->json([
                'success' => true,
                'comments' => $comments
            ]);
        } catch (\Exception $e) {
            Log::error('Error in TicketManagementController@getComments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load comments'
            ], 500);
        }
    }

    
    /**
     * Store a newly created ticket by admin helpdesk.
     */
    public function store(Request $request)
    {
        Log::info('=== TICKET STORE METHOD CALLED ===');
        Log::info('Request data:', $request->all());
        Log::info('Request method:', ['method' => $request->method()]);
        Log::info('Is Ajax:', ['ajax' => $request->ajax()]);
        Log::info('Is JSON:', ['json' => $request->expectsJson()]);
        Log::info('Is Inertia:', ['inertia' => $request->inertia()]);

        $admin = Auth::user();
        Log::info('Admin user:', ['nip' => $admin->nip, 'name' => $admin->name]);

        // Validate request data
        $validator = Validator::make($request->all(), [
            'user_nip' => 'required|string|exists:users,nip',
            'aplikasi_id' => 'required|exists:aplikasis,id',
            'kategori_masalah_id' => 'required|exists:kategori_masalahs,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_teknisi_nip' => 'nullable|string|exists:teknisis,nip',
            'location' => 'nullable|string|max:255',
            'device_info' => 'nullable|string',
            'attachments' => 'nullable|array',
            'screenshot' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed:', $validator->errors()->toArray());
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()->all(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        Log::info('Validation passed, proceeding with ticket creation');

        try {
            Log::info('Starting database transaction');
            DB::beginTransaction();

            // Generate ticket number
            $ticketNumber = $this->generateTicketNumber();
            Log::info('Generated ticket number:', ['ticket_number' => $ticketNumber]);

            // Get IP and device info
            $ipAddress = $request->ip();
            $userAgent = $request->userAgent();
            $deviceInfo = $request->device_info ?: $this->parseDeviceInfo($userAgent);

            // Prepare ticket data
            $ticketData = [
                'ticket_number' => $ticketNumber,
                'user_nip' => $request->user_nip,
                'aplikasi_id' => $request->aplikasi_id,
                'kategori_masalah_id' => $request->kategori_masalah_id,
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'status' => Ticket::STATUS_OPEN,
                'assigned_teknisi_nip' => !empty($request->assigned_teknisi_nip) ? $request->assigned_teknisi_nip : null,
                'assigned_by_nip' => !empty($request->assigned_teknisi_nip) ? $admin->nip : null,
                'location' => $request->location,
                'device_info' => $deviceInfo,
                'ip_address' => $ipAddress,
                'attachments' => $request->attachments,
                'screenshot' => $request->screenshot,
                'due_date' => $this->calculateDueDate($request->priority),
            ];

            // Add temporary metadata to help observer know who created the ticket
            $ticketData['temp_creation_context'] = [
                'created_by' => $admin->nip,
                'created_by_type' => 'admin_helpdesk',
                'assignment_notes' => $request->assigned_teknisi_nip ?
                    "Ticket created and assigned to teknisi {$request->assigned_teknisi_nip}" :
                    "Ticket created by admin helpdesk",
            ];

            Log::info('Creating ticket with data:', $ticketData);

            // Create the ticket
            $ticket = Ticket::create($ticketData);
            Log::info('Ticket created successfully:', ['ticket_id' => $ticket->id]);

            // Log ticket creation in audit log explicitly
            try {
                $auditLog = \App\Services\AuditLogService::log(
                    'created',
                    "Created ticket #{$ticket->ticket_number}: {$ticket->title}",
                    'Ticket',
                    $ticket->id,
                    [
                        'ticket_number' => $ticket->ticket_number,
                        'title' => $ticket->title,
                        'priority' => $ticket->priority,
                        'status' => $ticket->status,
                        'user_nip' => $ticket->user_nip,
                        'aplikasi_id' => $ticket->aplikasi_id,
                        'kategori_masalah_id' => $ticket->kategori_masalah_id,
                        'assigned_teknisi_nip' => $ticket->assigned_teknisi_nip,
                        'created_by_admin' => $admin->name,
                    ],
                    $admin
                );
                
                if ($auditLog) {
                    Log::info('✓ Ticket creation logged to audit log successfully', [
                        'audit_log_id' => $auditLog->id,
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                    ]);
                } else {
                    Log::error('✗ AuditLogService::log() returned null for ticket creation', [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('✗ Exception when logging ticket creation to audit log', [
                    'ticket_id' => $ticket->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            // If ticket is assigned to teknisi, update status to assigned
            if ($request->assigned_teknisi_nip) {
                Log::info('Updating ticket status to ASSIGNED');
                $ticket->status = Ticket::STATUS_ASSIGNED;
                $ticket->save();
                Log::info('Ticket status updated to assigned');
            }

            // Create initial history record using proper schema for TicketHistory
            Log::info('Creating ticket history record');
            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'performed_by_nip' => $admin->nip,
                'performed_by_type' => 'admin_helpdesk',
                'action' => 'created',
                'description' => $request->assigned_teknisi_nip ?
                    "Ticket created and assigned to teknisi {$request->assigned_teknisi_nip}" :
                    "Ticket created by admin helpdesk",
                'field_name' => 'ticket',
                'old_value' => null,
                'new_value' => $ticket->title,
                'metadata' => json_encode([
                    'created_by_role' => 'admin_helpdesk',
                    'assigned_teknisi_nip' => $request->assigned_teknisi_nip,
                ]),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
            Log::info('Ticket history created');

            // Create notification for user if not created by themselves
            if ($request->user_nip !== $admin->nip) {
                Log::info('Creating user notification');
                $user = User::where('nip', $request->user_nip)->first();
                if ($user) {
                    Notification::create([
                        'type' => 'ticket_created',
                        'notifiable_type' => User::class,
                        'notifiable_id' => $user->getKey(),
                        'ticket_id' => $ticket->id,
                        'triggered_by_type' => 'admin_helpdesk',
                        'triggered_by_nip' => $admin->nip,
                        'title' => 'New Ticket Created',
                        'message' => "Ticket {$ticketNumber} has been created on your behalf: {$request->title}",
                        'priority' => 'medium',
                        'channel' => 'database',
                        'status' => 'unread',
                        'data' => json_encode([
                            'ticket_number' => $ticketNumber,
                            'ticket_title' => $request->title,
                            'priority' => $request->priority,
                            'created_by_admin' => $admin->name,
                        ]),
                    ]);
                }
            }

            // Create notification for assigned teknisi
            if ($request->assigned_teknisi_nip) {
                Log::info('Creating teknisi notification');
                $teknisi = Teknisi::where('nip', $request->assigned_teknisi_nip)->first();
                if ($teknisi) {
                    Notification::create([
                        'type' => 'ticket_assigned',
                        'notifiable_type' => Teknisi::class,
                        'notifiable_id' => $teknisi->getKey(),
                        'ticket_id' => $ticket->id,
                        'triggered_by_type' => 'admin_helpdesk',
                        'triggered_by_nip' => $admin->nip,
                        'title' => 'New Ticket Assigned',
                        'message' => "Ticket {$ticketNumber} has been assigned to you: {$request->title}",
                        'priority' => 'high',
                        'channel' => 'database',
                        'status' => 'unread',
                        'data' => json_encode([
                            'ticket_number' => $ticketNumber,
                            'ticket_title' => $request->title,
                            'priority' => $request->priority,
                            'assigned_by' => $admin->name,
                        ]),
                    ]);
                }
            }

            Log::info('Committing database transaction');
            DB::commit();
            Log::info('Database transaction committed successfully');

            // Load relationships for response
            $ticket->load(['user', 'aplikasi', 'kategoriMasalah', 'assignedTeknisi']);

            Log::info('Preparing success response');
            if ($request->expectsJson()) {
                Log::info('Returning JSON response');
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket created successfully',
                    'ticket' => [
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'title' => $ticket->title,
                        'status' => $ticket->status,
                        'priority' => $ticket->priority,
                        'user' => $ticket->user ? [
                            'nip' => $ticket->user->nip,
                            'name' => $ticket->user->name,
                        ] : null,
                        'aplikasi' => $ticket->aplikasi ? [
                            'id' => $ticket->aplikasi->id,
                            'name' => $ticket->aplikasi->name,
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
                    ]
                ]);
            }

            Log::info('Returning redirect response');
            return redirect()
                ->route('admin.tickets-management.index')
                ->with('success', "Ticket {$ticketNumber} created successfully!");

              } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in TicketManagementController@store: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Failed to create ticket. Please try again. Error: ' . $e->getMessage()],
                ], 500);
            }

            return back()
                ->withErrors(['error' => 'Failed to create ticket. Please try again. Error: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Generate unique ticket number.
     */
    private function generateTicketNumber(): string
    {
        // Use the same ticket number generation as TicketService for consistency
        return $this->ticketService->generateTicketNumber();
    }

    /**
     * Calculate due date based on priority.
     */
    private function calculateDueDate(string $priority): Carbon
    {
        $slaHours = Ticket::PRIORITY_SLA_HOURS[$priority] ?? 48;
        return Carbon::now()->addHours($slaHours);
    }

    /**
     * Parse device info from user agent string.
     */
    private function parseDeviceInfo(string $userAgent): string
    {
        // Simple device info parsing - can be enhanced
        if (strpos($userAgent, 'Windows') !== false) {
            return 'Windows PC';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            return 'Mac';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            return 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            return 'Android Mobile';
        } elseif (strpos($userAgent, 'iOS') !== false || strpos($userAgent, 'iPhone') !== false) {
            return 'iOS Mobile';
        } else {
            return 'Unknown Device';
        }
    }

    /**
     * Get user role for display purposes.
     */
    private function getUserRole($user): string
    {
        if ($user instanceof \App\Models\AdminHelpdesk) {
            return 'Admin Helpdesk';
        } elseif ($user instanceof \App\Models\AdminAplikasi) {
            return 'Admin Aplikasi';
        } elseif ($user instanceof \App\Models\Teknisi) {
            return 'Teknisi';
        } elseif ($user instanceof \App\Models\User) {
            return 'User';
        } else {
            return 'Unknown';
        }
    }

    /**
     * Get user role type for database storage.
     */
    private function getUserRoleType($user): string
    {
        if ($user instanceof \App\Models\AdminHelpdesk) {
            return 'admin_helpdesk';
        } elseif ($user instanceof \App\Models\AdminAplikasi) {
            return 'admin_aplikasi';
        } elseif ($user instanceof \App\Models\Teknisi) {
            return 'teknisi';
        } elseif ($user instanceof \App\Models\User) {
            return 'user';
        } else {
            return 'unknown';
        }
    }
}
