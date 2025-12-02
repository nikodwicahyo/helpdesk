<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketAction;
use App\Models\Teknisi;
use App\Models\User;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Services\TicketService;
use App\Services\AuthService;
use Carbon\Carbon;

class TicketHandlingController extends Controller
{
    protected $ticketService;
    protected $authService;

    public function __construct(TicketService $ticketService, AuthService $authService)
    {
        $this->ticketService = $ticketService;
        $this->authService = $authService;
    }

    /**
     * Get the authenticated teknisi user
     * Returns Teknisi model instance or null
     */
    protected function getAuthenticatedTeknisi(): ?Teknisi
    {
        $user = $this->authService->getCurrentAuthenticatedUser();
        $userRole = $this->authService->getCurrentUserRole();

        if (!$user || $userRole !== 'teknisi') {
            return null;
        }

        // Always fetch fresh Teknisi instance from database to ensure correct NIP
        return Teknisi::where('nip', $user->nip)->first();
    }

    /**
     * Return a JSON response that bypasses Inertia middleware
     */
    protected function jsonResponse($data, $status = 200)
    {
        return response()->json($data, $status)
            ->header('X-Inertia', 'false')
            ->header('Content-Type', 'application/json');
    }

    /**
     * Index method - redirects to myTickets (for /teknisi/ticket-handling route)
     */
    public function index(Request $request)
    {
        return $this->myTickets($request);
    }

    /**
     * Display assigned tickets for the current technician.
     */
    public function myTickets(Request $request)
    {
        $teknisi = $this->getAuthenticatedTeknisi();

        if (!$teknisi) {
            return redirect()->route('login')->withErrors(['Access denied. Teknisi authentication required.']);
        }

        // Get filter parameters
        $filters = $request->only([
            'status', 'priority', 'aplikasi_id', 'kategori_masalah_id',
            'search', 'sort_by', 'sort_direction', 'is_overdue',
            'is_escalated', 'sla_status'
        ]);

        // Set default sorting
        if (!isset($filters['sort_by'])) {
            $filters['sort_by'] = 'priority';
            $filters['sort_direction'] = 'desc';
        }

        // Get paginated assigned tickets with safe eager loading
        $tickets = $this->getMyFilteredTickets($teknisi->nip, $filters, 15);

        // Format tickets for frontend with null safety
        $formattedTickets = $tickets->getCollection()->map(function ($ticket) {
            return [
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
                'user' => $ticket->user ? [
                    'nip' => $ticket->user->nip,
                    'name' => $ticket->user->name,
                    'nama_lengkap' => $ticket->user->name,
                    'email' => $ticket->user->email ?? '',
                    'department' => $ticket->user->department,
                    'phone' => $ticket->user->phone,
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
                'created_at' => $ticket->created_at,
                'formatted_created_at' => $ticket->formatted_created_at,
                'updated_at' => $ticket->updated_at,
                'formatted_updated_at' => $ticket->formatted_updated_at,
                'first_response_at' => $ticket->first_response_at,
                'resolved_at' => $ticket->resolved_at,
                'due_date' => $ticket->due_date,
                'formatted_due_date' => $ticket->formatted_due_date,
                'is_overdue' => $ticket->is_overdue,
                'is_escalated' => $ticket->is_escalated,
                'sla_status' => $ticket->sla_status,
                'time_elapsed' => $ticket->time_elapsed,
                'resolution_time_formatted' => $ticket->formatted_resolution_time,
                'attachments' => $ticket->attachments ?? [],
                'comments_count' => $ticket->comments()->count(),
                'can_update_status' => true,
                'can_resolve' => in_array($ticket->status, [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING_RESPONSE]),
                'can_reassign' => true,
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
        $filterOptions = $this->getMyTicketsFilterOptions();

        // Get quick stats
        $quickStats = $this->getMyTicketsQuickStats($teknisi->nip);

        return Inertia::render('Teknisi/TicketHandling', [
            'tickets' => $formattedPaginator,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'quickStats' => $quickStats,
        ]);
    }

    /**
     * Update ticket status.
     */
    public function updateStatus(Request $request, $ticketId)
    {
        $teknisi = $this->getAuthenticatedTeknisi();

        if (!$teknisi) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => ['Authentication required'],
            ], 401);
        }

        // Define all valid statuses that teknisi can set
        $allowedStatuses = [
            Ticket::STATUS_IN_PROGRESS,      // 'in_progress'
            Ticket::STATUS_WAITING_USER,     // 'waiting_user'
            Ticket::STATUS_WAITING_ADMIN,    // 'waiting_admin'
            Ticket::STATUS_RESOLVED,         // 'resolved'
        ];

        // Validate request
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:' . implode(',', $allowedStatuses),
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            Log::warning('Ticket status update validation failed', [
                'ticket_id' => $ticketId,
                'teknisi_nip' => $teknisi->nip,
                'requested_status' => $request->status,
                'allowed_statuses' => $allowedStatuses,
                'errors' => $validator->errors()->all(),
            ]);
            
            return $this->jsonResponse([
                'success' => false,
                'errors' => $validator->errors()->all(),
                'debug' => [
                    'requested_status' => $request->status,
                    'allowed_statuses' => $allowedStatuses,
                ],
            ], 422);
        }

        try {
            $ticket = Ticket::where('id', $ticketId)
                ->where('assigned_teknisi_nip', $teknisi->nip)
                ->firstOrFail();

            // Check if status transition is valid
            if (!$ticket->canTransitionTo($request->status)) {
                Log::warning('Invalid ticket status transition', [
                    'ticket_id' => $ticketId,
                    'current_status' => $ticket->status,
                    'requested_status' => $request->status,
                    'teknisi_nip' => $teknisi->nip,
                ]);
                
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => ["Cannot transition from '{$ticket->status}' to '{$request->status}'"],
                    'current_status' => $ticket->status,
                ], 422);
            }

            // Mark first response if transitioning from open to in_progress
            if ($ticket->status === Ticket::STATUS_OPEN && $request->status === Ticket::STATUS_IN_PROGRESS) {
                $ticket->markFirstResponse();
            }

            // Transition ticket status
            $result = $ticket->transitionTo($request->status, $teknisi->nip, $request->notes);

            if (!$result) {
                return $this->jsonResponse([
                    'success' => false,
                    'errors' => ['Failed to update ticket status'],
                ], 422);
            }

            // Return plain JSON response for AJAX requests
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Ticket status updated successfully',
                'ticket' => [
                    'id' => $ticket->id,
                    'status' => $ticket->status,
                    'status_label' => $ticket->status_label,
                    'status_badge_color' => $ticket->status_badge_color,
                    'updated_at' => $ticket->updated_at,
                    'formatted_updated_at' => $ticket->updated_at->diffForHumans(),
                ],
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => ['Ticket not found or access denied'],
            ], 404);
        }
    }

    /**
     * Add a comment to a ticket (teknisi can comment on their assigned tickets)
     */
    public function addComment(Request $request, $ticketId)
    {
        $teknisi = $this->getAuthenticatedTeknisi();

        if (!$teknisi) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => ['Authentication required'],
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:2000',
            'is_internal' => 'boolean',
            'type' => 'nullable|string|in:comment,status_update,technical,resolution',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            // Verify ticket is assigned to this teknisi
            $ticket = Ticket::where('id', $ticketId)
                ->where('assigned_teknisi_nip', $teknisi->nip)
                ->firstOrFail();

            // Create comment
            $comment = TicketComment::create([
                'ticket_id' => $ticket->id,
                'commenter_nip' => $teknisi->nip,
                'commenter_type' => 'App\\Models\\Teknisi',
                'comment' => $request->comment,
                'is_internal' => $request->is_internal ?? false,
                'type' => $request->type ?? 'comment',
            ]);

            // Log action
            TicketAction::log(
                $ticket->id,
                $teknisi->nip,
                'teknisi',
                'comment_added',
                'Teknisi added a comment'
            );

            // Load commenter relationship
            $comment->load('commenter');
            $commenter = $comment->commenter;

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Comment added successfully',
                'comment' => [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'is_internal' => $comment->is_internal,
                    'type' => $comment->type,
                    'created_at' => $comment->created_at->toISOString(),
                    'formatted_created_at' => $comment->created_at->diffForHumans(),
                    'user' => [
                        'nip' => $commenter->nip ?? $teknisi->nip,
                        'name' => $commenter->name ?? $teknisi->name,
                        'role' => 'teknisi',
                        'role_label' => 'Teknisi',
                    ],
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => ['Ticket not found or you do not have permission to comment on it'],
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error adding comment', [
                'ticket_id' => $ticketId,
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);

            return $this->jsonResponse([
                'success' => false,
                'errors' => ['Failed to add comment'],
            ], 500);
        }
    }

    /**
     * Resolve a ticket with technical notes and solution.
     */
    public function resolve(Request $request, $ticketId)
    {
        $teknisi = $this->getAuthenticatedTeknisi();

        if (!$teknisi) {
            return response()->json([
                'success' => false,
                'errors' => ['Authentication required'],
            ], 401);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'resolution_notes' => 'required|string|max:2000',
            'technical_notes' => 'nullable|string|max:1000',
            'solution_summary' => 'required|string|max:500',
            'files.*' => 'nullable|file|max:2048|mimes:jpeg,png,gif,webp,pdf,doc,docx,txt',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            $ticket = Ticket::where('id', $ticketId)
                ->where('assigned_teknisi_nip', $teknisi->nip)
                ->whereIn('status', [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING_RESPONSE])
                ->firstOrFail();

            // Handle file uploads
            $files = [];
            $uploadedFiles = [];
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store("tickets/{$ticket->ticket_number}/resolution", 'public');
                    $files[] = $path;
                    $uploadedFiles[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            }

            // Update ticket with resolution details
            $ticket->resolution_notes = $request->resolution_notes;

            // Add technical notes as comment if provided
            if ($request->filled('technical_notes')) {
                $technicalNoteRequest = new Request([
                    'technical_note' => $request->technical_notes,
                    'files' => $files,
                ]);
                $this->addTechnicalNote($technicalNoteRequest, $ticket->id);
            }

            // Resolve the ticket (this sets resolved_at)
            $result = $ticket->transitionTo(Ticket::STATUS_RESOLVED, $teknisi->nip,
                "Resolved: {$request->solution_summary}");

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Failed to resolve ticket'],
                ], 422);
            }

            // Calculate and save resolution time AFTER resolved_at is set
            $ticket->resolution_time_minutes = $ticket->calculateResolutionTime();
            $ticket->save();

            return response()->json([
                'success' => true,
                'message' => 'Ticket resolved successfully',
                'ticket' => [
                    'id' => $ticket->id,
                    'status' => $ticket->status,
                    'status_label' => $ticket->status_label,
                    'resolved_at' => $ticket->resolved_at,
                    'formatted_resolved_at' => $ticket->formatted_resolved_at,
                    'resolution_time_formatted' => $ticket->formatted_resolution_time,
                ],
                'uploaded_files' => $uploadedFiles,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Ticket not found or access denied'],
            ], 404);
        }
    }

    /**
     * Reassign ticket to another technician.
     */
    public function reassign(Request $request, $ticketId)
    {
        $teknisi = $this->getAuthenticatedTeknisi();

        if (!$teknisi) {
            return response()->json([
                'success' => false,
                'errors' => ['Authentication required'],
            ], 401);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'new_teknisi_nip' => 'required|string|exists:teknisis,nip|different:' . $teknisi->nip,
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            $ticket = Ticket::where('id', $ticketId)
                ->where('assigned_teknisi_nip', $teknisi->nip)
                ->firstOrFail();

            $newTeknisi = Teknisi::where('nip', $request->new_teknisi_nip)->firstOrFail();

            // Unassign from current teknisi and assign to new one
            $oldTeknisiNip = $ticket->assigned_teknisi_nip;
            $ticket->assigned_teknisi_nip = $newTeknisi->nip;

            if ($ticket->save()) {
                // Create history record
                $ticket->createHistoryRecord(
                    $ticket->status,
                    $ticket->status,
                    $teknisi->nip,
                    "Reassigned from {$oldTeknisiNip} to {$newTeknisi->nip}: {$request->reason}",
                    'reassignment'
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Ticket reassigned successfully',
                    'ticket' => [
                        'id' => $ticket->id,
                        'assigned_teknisi' => [
                            'nip' => $newTeknisi->nip,
                            'name' => $newTeknisi->name,
                        ],
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'errors' => ['Failed to reassign ticket'],
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Ticket not found or access denied'],
            ], 404);
        }
    }

    /**
     * Add technical note to a ticket.
     */
    public function addTechnicalNote(Request $request, $ticketId)
    {
        $teknisi = $this->getAuthenticatedTeknisi();

        if (!$teknisi) {
            return response()->json([
                'success' => false,
                'errors' => ['Authentication required'],
            ], 401);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'technical_note' => 'required|string|max:1000',
            'is_internal' => 'boolean',
            'files.*' => 'nullable|file|max:2048|mimes:jpeg,png,gif,webp,pdf,doc,docx,txt',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        return $this->addTechnicalNoteToTicket($ticketId, $request->technical_note,
            $teknisi->nip, $request->boolean('is_internal', true), $request->file('files'));
    }

    /**
     * Add technical note to ticket (internal method).
     */
    private function addTechnicalNoteToTicket($ticketId, string $note, string $teknisiNip,
        bool $isInternal = true, $files = null): \Illuminate\Http\JsonResponse
    {
        try {
            $ticket = Ticket::where('id', $ticketId)
                ->where('assigned_teknisi_nip', $teknisiNip)
                ->firstOrFail();

            // Handle file uploads
            $attachments = [];
            $uploadedFiles = [];

            if ($files) {
                foreach ($files as $file) {
                    $path = $file->store("tickets/{$ticket->ticket_number}/technical", 'public');
                    $attachments[] = $path;
                    $uploadedFiles[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            }

            // Create technical comment
            $comment = TicketComment::create([
                'ticket_id' => $ticket->id,
                'commenter_nip' => $teknisiNip,
                'commenter_type' => Teknisi::class,
                'comment' => $note,
                'is_internal' => $isInternal,
                'type' => 'technical',
                'attachments' => $attachments,
            ]);

            // Update ticket's updated_at timestamp
            $ticket->touch();

            return response()->json([
                'success' => true,
                'message' => 'Technical note added successfully',
                'comment' => [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'is_internal' => $comment->is_internal,
                    'comment_type' => $comment->comment_type,
                    'attachments' => $comment->attachments ?? [],
                    'created_at' => $comment->created_at,
                    'formatted_created_at' => $comment->created_at->diffForHumans(),
                    'user' => [
                        'nip' => $comment->user->nip,
                        'name' => $comment->user->name,
                        'role' => $comment->user->getUserRole(),
                    ],
                ],
                'uploaded_files' => $uploadedFiles,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Ticket not found or access denied'],
            ], 404);
        }
    }

    /**
     * Get filtered tickets assigned to the current technician.
     */
    private function getMyFilteredTickets(string $teknisiNip, array $filters, int $perPage = 15)
    {
        // Use safe eager loading - only load if relationships exist
        $query = Ticket::query()
            ->where('assigned_teknisi_nip', $teknisiNip);
        
        // Eager load relationships safely
        try {
            $query->with(['user', 'aplikasi', 'kategoriMasalah']);
        } catch (\Exception $e) {
            Log::error('Error with eager loading in getMyFilteredTickets', [
                'error' => $e->getMessage(),
                'teknisi_nip' => $teknisiNip
            ]);
            // Continue without eager loading if there's an error
        }

        // Apply filters with safety checks
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

        if (!empty($filters['search'])) {
            try {
                $query->search($filters['search']);
            } catch (\Exception $e) {
                // Fallback to basic search if Scout search fails
                $query->where(function($q) use ($filters) {
                    $search = $filters['search'];
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('ticket_number', 'like', "%{$search}%");
                });
            }
        }

        if (!empty($filters['is_overdue'])) {
            if ($filters['is_overdue'] === 'true' || $filters['is_overdue'] === true) {
                try {
                    $query->overdue();
                } catch (\Exception $e) {
                    // Fallback: manually check overdue
                    $query->whereNotNull('due_date')
                          ->where('due_date', '<', Carbon::now())
                          ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);
                }
            }
        }

        if (!empty($filters['is_escalated'])) {
            if ($filters['is_escalated'] === 'true' || $filters['is_escalated'] === true) {
                $query->where('is_escalated', true);
            }
        }

        if (!empty($filters['sla_status'])) {
            try {
                if ($filters['sla_status'] === 'breached') {
                    $query->slaBreached();
                } elseif ($filters['sla_status'] === 'within_sla') {
                    $query->withinSla();
                }
            } catch (\Exception $e) {
                Log::error('Error applying SLA filter', ['error' => $e->getMessage()]);
                // Continue without SLA filter if there's an error
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
     * Get filter options for my tickets.
     */
    private function getMyTicketsFilterOptions(): array
    {
        return [
            'statuses' => [
                ['value' => Ticket::STATUS_OPEN, 'label' => 'Open'],
                ['value' => Ticket::STATUS_IN_PROGRESS, 'label' => 'In Progress'],
                ['value' => Ticket::STATUS_WAITING_RESPONSE, 'label' => 'Waiting Response'],
                ['value' => Ticket::STATUS_RESOLVED, 'label' => 'Resolved'],
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
        ];
    }

    /**
     * Get quick statistics for my tickets.
     */
    private function getMyTicketsQuickStats(string $teknisiNip): array
    {
        $assignedCount = Ticket::where('assigned_teknisi_nip', $teknisiNip)->count();
        $openCount = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_OPEN)->count();
        $inProgressCount = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_IN_PROGRESS)->count();
        
        // Get resolved today and yesterday for trend calculation
        $resolvedToday = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereDate('resolved_at', Carbon::today())->count();
        
        $resolvedYesterday = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereDate('resolved_at', Carbon::yesterday())->count();
        
        // Calculate trend
        $resolvedTodayTrend = 0;
        if ($resolvedYesterday > 0) {
            $resolvedTodayTrend = (($resolvedToday - $resolvedYesterday) / $resolvedYesterday) * 100;
        } elseif ($resolvedToday > 0) {
            $resolvedTodayTrend = 100;
        }
        
        // Calculate average resolution time (in hours) for the last 7 days with fallback to last 30 days
        $avgResolutionTime = Ticket::where('assigned_teknisi_nip', $teknisiNip)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->where('resolved_at', '>=', Carbon::now()->subDays(7))
            ->whereNotNull('resolution_time_minutes')
            ->avg('resolution_time_minutes');
        
        // If no data in last 7 days, try last 30 days
        if (!$avgResolutionTime) {
            $avgResolutionTime = Ticket::where('assigned_teknisi_nip', $teknisiNip)
                ->where('status', Ticket::STATUS_RESOLVED)
                ->where('resolved_at', '>=', Carbon::now()->subDays(30))
                ->whereNotNull('resolution_time_minutes')
                ->avg('resolution_time_minutes');
        }
        
        return [
            'total_assigned' => $assignedCount,
            'assigned_tickets' => $openCount + $inProgressCount, // Combined open + in progress
            'open_tickets' => $openCount,
            'in_progress_tickets' => $inProgressCount,
            'waiting_response_tickets' => Ticket::where('assigned_teknisi_nip', $teknisiNip)
                ->where('status', Ticket::STATUS_WAITING_RESPONSE)->count(),
            'resolved_today' => $resolvedToday,
            'resolved_today_trend' => round($resolvedTodayTrend, 1),
            'avg_resolution_time' => $avgResolutionTime ? round($avgResolutionTime / 60, 1) : 0, // Convert to hours
            'overdue_tickets' => Ticket::where('assigned_teknisi_nip', $teknisiNip)
                ->overdue()->count(),
            'urgent_tickets' => Ticket::where('assigned_teknisi_nip', $teknisiNip)
                ->where('priority', Ticket::PRIORITY_URGENT)
                ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
                ->count(),
        ];
    }

    /**
     * Get ticket details for teknisi view (Full Page).
     */
    public function show($ticketId)
    {
        $teknisi = $this->getAuthenticatedTeknisi();

        if (!$teknisi) {
            return redirect()->route('login')->withErrors(['Access denied. Teknisi authentication required.']);
        }

        try {
            // Only allow viewing tickets assigned to this teknisi with safe eager loading
            $ticket = Ticket::where('id', $ticketId)
                ->where('assigned_teknisi_nip', $teknisi->nip)
                ->firstOrFail();
            
            // Load relationships separately to handle errors gracefully
            try {
                $ticket->load(['user', 'aplikasi', 'kategoriMasalah', 'assignedTeknisi']);
            } catch (\Exception $e) {
                Log::error('Error loading ticket relationships', [
                    'ticket_id' => $ticketId,
                    'error' => $e->getMessage()
                ]);
            }
            
            // Load comments separately
            try {
                $ticket->load(['comments' => function($q) {
                    $q->latest();
                }]);
            } catch (\Exception $e) {
                Log::error('Error loading ticket comments', [
                    'ticket_id' => $ticketId,
                    'error' => $e->getMessage()
                ]);
            }

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
                'user' => $ticket->user ? [
                    'nip' => $ticket->user->nip,
                    'name' => $ticket->user->name,
                    'department' => $ticket->user->department,
                    'phone' => $ticket->user->phone,
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
                'created_at' => $ticket->created_at,
                'formatted_created_at' => $ticket->formatted_created_at,
                'updated_at' => $ticket->updated_at,
                'formatted_updated_at' => $ticket->formatted_updated_at,
                'first_response_at' => $ticket->first_response_at,
                'resolved_at' => $ticket->resolved_at,
                'due_date' => $ticket->due_date,
                'formatted_due_date' => $ticket->formatted_due_date,
                'is_overdue' => $ticket->is_overdue,
                'is_escalated' => $ticket->is_escalated,
                'sla_status' => $ticket->sla_status,
                'time_elapsed' => $ticket->time_elapsed,
                'attachments' => $ticket->attachments ?? [],
                'resolution_notes' => $ticket->resolution_notes,
                'can_update_status' => true,
                'can_resolve' => in_array($ticket->status, [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING_RESPONSE]),
                'can_reassign' => true,
            ];

            // Format comments with proper commenter relationship and null safety
            $formattedComments = collect($ticket->comments ?? [])->map(function ($comment) {
                $commenter = null;
                try {
                    $commenter = $comment->commenter;
                } catch (\Exception $e) {
                    Log::warning('Error loading commenter for comment', [
                        'comment_id' => $comment->id ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
                
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment ?? '',
                    'is_internal' => $comment->is_internal ?? false,
                    'type' => $comment->type ?? 'comment',
                    'attachments' => $comment->attachments ?? [],
                    'created_at' => $comment->created_at ? $comment->created_at->toISOString() : null,
                    'formatted_created_at' => $comment->created_at ? $comment->created_at->diffForHumans() : 'Just now',
                    'user' => $commenter ? [
                        'nip' => $commenter->nip ?? 'N/A',
                        'name' => $commenter->name ?? 'Unknown User',
                        'role' => $this->getCommenterRole($comment->commenter_type ?? ''),
                        'role_label' => $this->getCommenterRoleLabel($comment->commenter_type ?? ''),
                    ] : [
                        'nip' => 'N/A',
                        'name' => 'System',
                        'role' => 'system',
                        'role_label' => 'System',
                    ],
                ];
            });

            // Get timeline for the ticket
            $timeline = $this->getTimelineData($ticketId);

            return Inertia::render('Teknisi/TicketDetail', [
                'ticket' => $formattedTicket,
                'comments' => $formattedComments,
                'timeline' => $timeline,
                'canUpdateStatus' => true,
                'canAddComment' => true,
                'canResolve' => in_array($ticket->status, [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING_RESPONSE]),
            ]);

        } catch (\Exception $e) {
            return redirect()->route('teknisi.tickets.index')
                ->withErrors(['Ticket not found or access denied']);
        }
    }

    /**
     * Get ticket details via API (for modals/AJAX).
     */
    public function getTicketDetails($ticketId)
    {
        $teknisi = $this->getAuthenticatedTeknisi();

        if (!$teknisi) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => ['Authentication required'],
            ], 401);
        }

        try {
            // Only allow viewing tickets assigned to this teknisi
            $ticket = Ticket::where('id', $ticketId)
                ->where('assigned_teknisi_nip', $teknisi->nip)
                ->with(['user', 'aplikasi', 'kategoriMasalah', 'comments.commenter'])
                ->firstOrFail();

            // Format comments
            $formattedComments = $ticket->comments->map(function ($comment) {
                $commenter = $comment->commenter;
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'is_internal' => $comment->is_internal ?? false,
                    'created_at' => $comment->created_at,
                    'formatted_created_at' => $comment->created_at->diffForHumans(),
                    'user' => $commenter ? [
                        'nip' => $commenter->nip ?? 'N/A',
                        'name' => $commenter->name ?? 'Unknown User',
                        'role' => $this->getCommenterRole($comment->commenter_type),
                    ] : [
                        'nip' => 'N/A',
                        'name' => 'Unknown User',
                        'role' => 'unknown',
                    ],
                ];
            });

            return $this->jsonResponse([
                'success' => true,
                'ticket' => [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'description' => $ticket->description,
                    'status' => $ticket->status,
                    'status_label' => $ticket->status_label,
                    'priority' => $ticket->priority,
                    'priority_label' => $ticket->priority_label,
                    'user' => $ticket->user ? [
                        'name' => $ticket->user->name,
                        'department' => $ticket->user->department ?? 'N/A',
                        'phone' => $ticket->user->phone ?? 'N/A',
                    ] : null,
                    'aplikasi' => $ticket->aplikasi ? $ticket->aplikasi->name : 'N/A',
                    'kategori_masalah' => $ticket->kategoriMasalah ? $ticket->kategoriMasalah->name : 'N/A',
                    'created_at' => $ticket->formatted_created_at ?? $ticket->created_at->format('Y-m-d H:i'),
                    'due_date' => $ticket->formatted_due_date ?? 'Not set',
                    'attachments' => $ticket->attachments ?? [],
                    'comments' => $formattedComments,
                    'comments_count' => $formattedComments->count(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Ticket not found for teknisi', [
                'ticket_id' => $ticketId,
                'teknisi_nip' => $teknisi->nip,
            ]);
            
            return $this->jsonResponse([
                'success' => false,
                'errors' => ['Ticket not found or you do not have access to view it'],
                'message' => 'Ticket #' . $ticketId . ' not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error loading ticket details', [
                'ticket_id' => $ticketId,
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->jsonResponse([
                'success' => false,
                'errors' => ['Failed to load ticket details'],
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get ticket timeline (using TicketAction model) - Optimized and safe
     */
    public function getTicketTimeline($ticketId)
    {
        $teknisi = $this->getAuthenticatedTeknisi();

        if (!$teknisi) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => ['Authentication required'],
            ], 401);
        }

        try {
            // Only allow viewing timeline for tickets assigned to this teknisi
            $ticket = Ticket::where('id', $ticketId)
                ->where('assigned_teknisi_nip', $teknisi->nip)
                ->firstOrFail();

            $actions = TicketAction::where('ticket_id', $ticketId)
                ->latest()
                ->limit(50)
                ->get();

            $timeline = $actions->map(function ($action) {
                // Safely get actor information without eager loading
                $actorName = 'System';
                $actorNip = 'system';
                $actorType = 'system';
                
                try {
                    $actor = $action->actor();
                    if ($actor) {
                        $actorName = $actor->name ?? $actor->nama_lengkap ?? 'Unknown';
                        $actorNip = $actor->nip ?? 'unknown';
                        $actorType = $action->actor_type ?? 'system';
                    }
                } catch (\Exception $e) {
                    Log::debug('Could not load actor for timeline action', [
                        'action_id' => $action->id,
                        'actor_nip' => $action->actor_nip ?? 'unknown',
                    ]);
                }
                
                return [
                    'id' => $action->id,
                    'action_type' => $action->action_type ?? 'system_action',
                    'description' => $action->description ?? 'Action performed',
                    'metadata' => $action->metadata ?? null,
                    'created_at' => $action->created_at ? $action->created_at->toISOString() : now()->toISOString(),
                    'formatted_created_at' => $action->created_at ? $action->created_at->diffForHumans() : 'Just now',
                    'icon' => $this->getActionIcon($action->action_type ?? ''),
                    'color' => $this->getActionColor($action->action_type ?? ''),
                    'actor' => [
                        'nip' => $actorNip,
                        'name' => $actorName,
                        'type' => $actorType,
                    ],
                ];
            });

            return $this->jsonResponse([
                'success' => true,
                'timeline' => $timeline,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Ticket timeline not found for teknisi', [
                'ticket_id' => $ticketId,
                'teknisi_nip' => $teknisi->nip,
            ]);
            
            return $this->jsonResponse([
                'success' => false,
                'errors' => ['Ticket not found or you do not have access to view it'],
                'timeline' => [],
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error loading ticket timeline', [
                'ticket_id' => $ticketId,
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->jsonResponse([
                'success' => false,
                'errors' => ['Failed to load timeline'],
                'timeline' => [],
            ], 500);
        }
    }

    /**
     * Mark first response for a ticket.
     */
    public function markFirstResponse($ticketId)
    {
        $teknisi = $this->getAuthenticatedTeknisi();

        if (!$teknisi) {
            return $this->jsonResponse([
                'success' => false,
                'errors' => ['Authentication required'],
            ], 401);
        }

        try {
            $ticket = Ticket::where('id', $ticketId)
                ->where('assigned_teknisi_nip', $teknisi->nip)
                ->whereNull('first_response_at')
                ->firstOrFail();

            $ticket->markFirstResponse();

            // Log the action
            TicketAction::log(
                $ticket->id,
                $teknisi->nip,
                'teknisi',
                TicketAction::ACTION_FIRST_RESPONSE,
                "Teknisi {$teknisi->name} provided first response",
                ['response_time_minutes' => $ticket->created_at->diffInMinutes($ticket->first_response_at)]
            );

            return response()->json([
                'success' => true,
                'message' => 'First response marked successfully',
                'first_response_at' => $ticket->first_response_at,
                'formatted_first_response_at' => $ticket->first_response_at->diffForHumans(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Ticket not found or first response already marked'],
            ], 404);
        }
    }

    /**
     * Request reassignment of a ticket.
     */
    /**
     * Request reassignment of a ticket.
     */
    public function requestReassignment(Request $request, Ticket $ticket)
    {
        $teknisi = $this->getAuthenticatedTeknisi();

        if (!$teknisi) {
            return response()->json([
                'success' => false,
                'errors' => ['Authentication required'],
            ], 401);
        }

        // Check if ticket is assigned to this teknisi
        if ($ticket->assigned_teknisi_nip !== $teknisi->nip) {
            return response()->json([
                'success' => false,
                'errors' => ['You are not authorized to request reassignment for this ticket'],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
            'suggested_teknisi_nip' => 'nullable|string|exists:teknisis,nip',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            // Create reassignment request note
            $noteText = "Reassignment requested by {$teknisi->name}.\nReason: {$request->reason}";
            if ($request->filled('suggested_teknisi_nip')) {
                $suggestedTeknisi = Teknisi::where('nip', $request->suggested_teknisi_nip)->first();
                if ($suggestedTeknisi) {
                    $noteText .= "\nSuggested: {$suggestedTeknisi->name}";
                } else {
                    $noteText .= "\nSuggested teknisi NIP: {$request->suggested_teknisi_nip} (not found)";
                }
            }

            // Add internal note
            TicketComment::create([
                'ticket_id' => $ticket->id,
                'commenter_nip' => $teknisi->nip,
                'commenter_type' => Teknisi::class,
                'comment' => $noteText,
                'is_internal' => true,
                'type' => 'reassignment_request',
            ]);

            // Log action
            TicketAction::log(
                $ticket->id,
                $teknisi->nip,
                'teknisi',
                'reassignment_requested',
                "Reassignment requested: {$request->reason}",
                ['suggested_teknisi_nip' => $request->suggested_teknisi_nip]
            );

            // Notify admin_helpdesk about reassignment request
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->notifyReassignmentRequested(
                $ticket,
                $teknisi,
                $request->reason,
                $request->suggested_teknisi_nip
            );

            return response()->json([
                'success' => true,
                'message' => 'Reassignment request submitted successfully. An admin will review it shortly.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error submitting reassignment request', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'errors' => ['Failed to submit request. Please try again.'],
            ], 500);
        }
    }

    /**
     * Upload solution documentation.
     */
    public function uploadSolutionDoc(Request $request, $ticketId)
    {
        $teknisi = $this->getAuthenticatedTeknisi();

        if (!$teknisi) {
            return response()->json([
                'success' => false,
                'errors' => ['Authentication required'],
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'files.*' => 'required|file|max:2048|mimes:jpeg,png,gif,webp,pdf,doc,docx,txt',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            $ticket = Ticket::where('id', $ticketId)
                ->where('assigned_teknisi_nip', $teknisi->nip)
                ->firstOrFail();

            $uploadedFiles = [];
            $filePaths = [];

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store("tickets/{$ticket->ticket_number}/solution", 'public');
                    $filePaths[] = $path;
                    $uploadedFiles[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            }

            // Add comment with files
            TicketComment::create([
                'ticket_id' => $ticket->id,
                'commenter_nip' => $teknisi->nip,
                'commenter_type' => Teknisi::class,
                'comment' => $request->description ?? 'Solution documentation uploaded',
                'is_internal' => false,
                'type' => 'solution_doc',
                'attachments' => $filePaths,
            ]);

            // Log action
            TicketAction::log(
                $ticket->id,
                $teknisi->nip,
                'teknisi',
                TicketAction::ACTION_ATTACHMENT_ADDED,
                "Uploaded " . count($uploadedFiles) . " solution document(s)",
                ['file_count' => count($uploadedFiles), 'files' => $uploadedFiles]
            );

            return response()->json([
                'success' => true,
                'message' => 'Solution documentation uploaded successfully',
                'uploaded_files' => $uploadedFiles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Ticket not found or access denied'],
            ], 404);
        }
    }

    /**
     * Get human-readable role from commenter type
     */
    private function getCommenterRole($commenterType)
    {
        return match($commenterType) {
            'App\\Models\\User' => 'user',
            'App\\Models\\Teknisi' => 'teknisi',
            'App\\Models\\AdminHelpdesk' => 'admin_helpdesk',
            'App\\Models\\AdminAplikasi' => 'admin_aplikasi',
            default => 'unknown',
        };
    }

    /**
     * Get human-readable role label from commenter type
     */
    private function getCommenterRoleLabel($commenterType)
    {
        return match($commenterType) {
            'App\\Models\\User' => 'Pegawai',
            'App\\Models\\Teknisi' => 'Teknisi',
            'App\\Models\\AdminHelpdesk' => 'Admin Helpdesk',
            'App\\Models\\AdminAplikasi' => 'Admin Aplikasi',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted timeline data for ticket with safe actor loading
     */
    private function getTimelineData($ticketId)
    {
        try {
            $actions = TicketAction::where('ticket_id', $ticketId)
                ->orderBy('created_at', 'asc')
                ->get();

            return $actions->map(function ($action) {
                // Safely get actor information
                $actorName = 'System';
                $actorType = 'system';
                
                try {
                    $actor = $action->actor();
                    if ($actor) {
                        $actorName = $actor->name ?? $actor->nama_lengkap ?? 'Unknown';
                        $actorType = $this->getCommenterRole($action->actor_type ?? '');
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not load actor for action', [
                        'action_id' => $action->id,
                        'actor_nip' => $action->actor_nip ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
                
                return [
                    'id' => $action->id,
                    'action' => $action->action_type ?? 'unknown',
                    'description' => $action->description ?? 'No description',
                    'icon' => $this->getActionIcon($action->action_type ?? ''),
                    'color' => $this->getActionColor($action->action_type ?? ''),
                    'created_at' => $action->created_at ? $action->created_at->toISOString() : null,
                    'formatted_created_at' => $action->created_at ? $action->created_at->diffForHumans() : 'Just now',
                    'actor' => [
                        'name' => $actorName,
                        'type' => $actorType,
                    ],
                    'metadata' => $action->metadata ?? [],
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading timeline', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get icon for action type with comprehensive mapping
     */
    private function getActionIcon($action)
    {
        return match($action) {
            'created' => 'plus-circle',
            'status_changed' => 'refresh',
            'assigned' => 'user-plus',
            'reassigned' => 'users',
            'comment_added' => 'message-square',
            'commented' => 'message-square',
            'resolved' => 'check-circle',
            'closed' => 'x-circle',
            'reopened' => 'rotate-ccw',
            'escalated' => 'alert-triangle',
            'priority_changed' => 'flag',
            'attachment_added' => 'paperclip',
            'first_response' => 'clock',
            default => 'circle',
        };
    }

    /**
     * Get color for action type with comprehensive mapping
     */
    private function getActionColor($action)
    {
        return match($action) {
            'created' => 'blue',
            'status_changed' => 'yellow',
            'assigned' => 'purple',
            'reassigned' => 'indigo',
            'comment_added' => 'gray',
            'commented' => 'gray',
            'resolved' => 'green',
            'closed' => 'red',
            'reopened' => 'orange',
            'escalated' => 'red',
            'priority_changed' => 'orange',
            'attachment_added' => 'blue',
            'first_response' => 'green',
            default => 'gray',
        };
    }
}
