<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\Ticket;
use App\Models\TicketDraft;
use App\Models\TicketHistory;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\User;
use App\Services\TicketService;
use App\Services\AuthService;
use App\Services\SystemSettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * Display a listing of the user's tickets with pagination.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $filters = $request->only([
            'status', 'priority', 'aplikasi_id', 'kategori_masalah_id',
            'search', 'sort_by', 'sort_direction'
        ]);

        // Set default sorting
        if (!isset($filters['sort_by'])) {
            $filters['sort_by'] = 'created_at';
            $filters['sort_direction'] = 'desc';
        }

        // Get paginated tickets
        if (!$user) {
            $tickets = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        } else {
            $tickets = $this->ticketService->getUserTickets($user->nip, $filters, 15);
        }

        // Format tickets for frontend
        $formattedTickets = collect($tickets->items())->map(function ($ticket) {
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
                'formatted_updated_at' => $ticket->formatted_updated_at,
                'is_overdue' => $ticket->is_overdue,
            ];
        });

        // Create paginator with formatted tickets
        $formattedPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $formattedTickets,
            $tickets->total(),
            $tickets->perPage(),
            $tickets->currentPage(),
            ['path' => $tickets->path(), 'pageName' => 'page']
        );

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        // Calculate stats for the user
        $stats = [
            'total_tickets' => $user ? Ticket::where('user_nip', $user->nip)->count() : 0,
            'open_tickets' => $user ? Ticket::where('user_nip', $user->nip)->where('status', Ticket::STATUS_OPEN)->count() : 0,
            'in_progress_tickets' => $user ? Ticket::where('user_nip', $user->nip)->where('status', Ticket::STATUS_IN_PROGRESS)->count() : 0,
            'resolved_tickets' => $user ? Ticket::where('user_nip', $user->nip)->where('status', Ticket::STATUS_RESOLVED)->count() : 0,
        ];

        return Inertia::render('User/TicketList', [
            'tickets' => $formattedPaginator,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new ticket.
     */
    public function create()
    {
        $creationData = $this->ticketService->getTicketCreationData();

        return Inertia::render('User/TicketCreate', [
            'applications' => $creationData['applications'],
            'categories' => $creationData['categories'],
        ]);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validate request data
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'aplikasi_id' => 'required|exists:aplikasis,id',
            'kategori_masalah_id' => 'required|exists:kategori_masalahs,id',
            'priority' => 'required|in:' . implode(',', [
                Ticket::PRIORITY_LOW, Ticket::PRIORITY_MEDIUM,
                Ticket::PRIORITY_HIGH, Ticket::PRIORITY_URGENT
            ]),
            'location' => 'nullable|string|max:255',
            'device_info' => 'nullable|string',
            'ip_address' => 'nullable|ip',
            'files.*' => 'nullable|' . SystemSettingsService::getFileValidationString(),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Prepare ticket data
        $ticketData = [
            'user_nip' => $user->nip,
            'title' => $request->title,
            'description' => $request->description,
            'aplikasi_id' => $request->aplikasi_id,
            'kategori_masalah_id' => $request->kategori_masalah_id,
            'priority' => $request->priority,
            'status' => Ticket::STATUS_OPEN,
            'location' => $request->location,
            'device_info' => $request->device_info,
            'ip_address' => $request->ip_address ?? $request->ip(),
        ];

        // Handle file uploads
        $files = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files');
        }

        // Create ticket using service
        $result = $this->ticketService->createTicket($ticketData, $files);

        if (!$result['success']) {
            return back()
                ->withErrors($result['errors'])
                ->withInput();
        }

        return redirect()->route('user.tickets.show', $result['ticket']->id)
            ->with('success', 'Ticket created successfully');
    }

    /**
     * Display the specified ticket with comments and history.
     */
    public function show(Request $request, $id)
    {
        $user = Auth::user();

        $result = $this->ticketService->getTicketDetails($id, $user->nip);

        if (!$result['success']) {
            return redirect()->route('user.tickets.index')
                ->withErrors($result['errors']);
        }

        return Inertia::render('User/TicketDetail', [
            'ticket' => $result['ticket'],
            'comments' => $result['comments'],
            'history' => $result['history'],
            'focus' => $request->input('focus'),
        ]);
    }

    /**
     * Show the form for editing the specified ticket.
     * Users can only edit their own tickets and only if status is 'open' or 'assigned'.
     */
    public function edit($id)
    {
        $user = Auth::user();

        // Get ticket
        $ticket = Ticket::with(['aplikasi', 'kategoriMasalah'])->findOrFail($id);

        // Check ownership
        if ($ticket->user_nip !== $user->nip) {
            return redirect()->route('user.tickets.index')
                ->withErrors(['error' => 'You do not have permission to edit this ticket.']);
        }

        // Check if ticket can be edited (only open or assigned status)
        if (!in_array($ticket->status, [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED])) {
            return redirect()->route('user.tickets.show', $id)
                ->withErrors(['error' => 'This ticket cannot be edited in its current status.']);
        }

        // Get creation data for dropdowns
        $creationData = $this->ticketService->getTicketCreationData();

        // Format ticket for editing
        $ticketData = [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'title' => $ticket->title,
            'description' => $ticket->description,
            'aplikasi_id' => $ticket->aplikasi_id,
            'kategori_masalah_id' => $ticket->kategori_masalah_id,
            'priority' => $ticket->priority,
            'location' => $ticket->location,
            'status' => $ticket->status,
            'attachments' => $ticket->attachments ?? [],
            'aplikasi' => $ticket->aplikasi ? [
                'id' => $ticket->aplikasi->id,
                'name' => $ticket->aplikasi->name,
            ] : null,
            'kategori_masalah' => $ticket->kategoriMasalah ? [
                'id' => $ticket->kategoriMasalah->id,
                'name' => $ticket->kategoriMasalah->name,
            ] : null,
        ];

        return Inertia::render('User/TicketEdit', [
            'ticket' => $ticketData,
            'applications' => $creationData['applications'],
            'categories' => $creationData['categories'],
        ]);
    }

    /**
     * Update the specified ticket in storage.
     * Users can only update their own tickets and only if status is 'open' or 'assigned'.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // Get ticket
        $ticket = Ticket::findOrFail($id);

        // Check ownership
        if ($ticket->user_nip !== $user->nip) {
            return back()->withErrors(['error' => 'You do not have permission to update this ticket.']);
        }

        // Check if ticket can be updated (only open or assigned status)
        if (!in_array($ticket->status, [Ticket::STATUS_OPEN, Ticket::STATUS_ASSIGNED])) {
            return back()->withErrors(['error' => 'This ticket cannot be updated in its current status.']);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'aplikasi_id' => 'required|exists:aplikasis,id',
            'kategori_masalah_id' => 'required|exists:kategori_masalahs,id',
            'priority' => 'required|in:' . implode(',', [
                Ticket::PRIORITY_LOW, Ticket::PRIORITY_MEDIUM,
                Ticket::PRIORITY_HIGH, Ticket::PRIORITY_URGENT
            ]),
            'location' => 'nullable|string|max:255',
            'files.*' => 'nullable|' . SystemSettingsService::getFileValidationString(),
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Store old values for history
            $oldValues = [
                'title' => $ticket->title,
                'description' => $ticket->description,
                'aplikasi_id' => $ticket->aplikasi_id,
                'kategori_masalah_id' => $ticket->kategori_masalah_id,
                'priority' => $ticket->priority,
                'location' => $ticket->location,
            ];

            // Update ticket fields
            $ticket->title = $request->title;
            $ticket->description = $request->description;
            $ticket->aplikasi_id = $request->aplikasi_id;
            $ticket->kategori_masalah_id = $request->kategori_masalah_id;
            $ticket->priority = $request->priority;
            $ticket->location = $request->location;

            // Handle new file uploads
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $uploadResult = $this->ticketService->handleFileUploads($files, $ticket->ticket_number);

                if (!empty($uploadResult['errors'])) {
                    DB::rollBack();
                    return back()->withErrors($uploadResult['errors'])->withInput();
                }

                // Append new files to existing attachments
                $existingAttachments = $ticket->attachments ?? [];
                $newAttachments = array_merge($existingAttachments, $uploadResult['uploaded_files']);
                $ticket->attachments = $newAttachments;
            }

            $ticket->save();

            // Log changes in history
            $changes = [];
            foreach ($oldValues as $field => $oldValue) {
                if ($ticket->$field != $oldValue) {
                    $changes[] = [
                        'field' => $field,
                        'old' => $oldValue,
                        'new' => $ticket->$field,
                    ];
                }
            }

            if (!empty($changes)) {
                TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'actor_type' => 'user',
                    'actor_id' => $user->id,
                    'action' => 'updated',
                    'old_value' => json_encode($oldValues),
                    'new_value' => json_encode($request->only(array_keys($oldValues))),
                    'description' => 'Ticket updated by user: ' . implode(', ', array_column($changes, 'field')),
                ]);
            }

            DB::commit();

            return redirect()->route('user.tickets.show', $ticket->id)
                ->with('success', 'Ticket updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update ticket: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Add a comment to the specified ticket.
     */
    public function addComment(Request $request, $id)
    {
        $user = Auth::user();

        // Validate request
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
            'files.*' => 'nullable|' . SystemSettingsService::getFileValidationString(),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        // Handle file uploads
        $files = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files');
        }

        // Add comment using service
        $result = $this->ticketService->addComment(
            $id,
            $request->comment,
            $files,
            $user->nip
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'errors' => $result['errors'],
            ], 422);
        }

        // Format comment for frontend
        $commenter = $result['comment']->commenter;
        $comment = [
            'id' => $result['comment']->id,
            'comment' => $result['comment']->comment,
            'user' => [
                'nip' => $commenter ? $commenter->nip : null,
                'name' => $commenter ? $commenter->name : 'Unknown User',
                'role' => $result['comment']->commenter_role ?? 'Unknown',
            ],
            'attachments' => $result['comment']->attachments ?? [],
            'created_at' => $result['comment']->created_at,
            'formatted_created_at' => $result['comment']->created_at->diffForHumans(),
        ];

        return response()->json([
            'success' => true,
            'comment' => $comment,
            'uploaded_files' => $result['uploaded_files'],
        ]);
    }

    /**
     * Update a comment.
     */
    public function updateComment(Request $request, $ticketId, $commentId)
    {
        $user = Auth::user();

        // Validate request
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $result = $this->ticketService->updateComment($commentId, $request->comment, $user->nip);

        if (!$result['success']) {
            return back()->withErrors($result['errors']);
        }

        return back()->with('success', 'Comment updated successfully');
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(Request $request, $ticketId, $commentId)
    {
        $user = Auth::user();

        $result = $this->ticketService->deleteComment($commentId, $user->nip);

        if (!$result['success']) {
            return back()->withErrors($result['errors']);
        }

        return back()->with('success', 'Comment deleted successfully');
    }

    /**
     * Close the specified ticket.
     */
    public function close(Request $request, $id)
    {
        $user = Auth::user();

        // Validate request
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        // Close ticket using service
        $result = $this->ticketService->closeTicket(
            $id,
            $user->nip,
            $request->reason
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'errors' => $result['errors'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'ticket' => $result['ticket'],
        ]);
    }

    /**
     * Get filter options for tickets listing.
     */
    private function getFilterOptions(): array
    {
        return [
            'statuses' => [
                ['value' => Ticket::STATUS_OPEN, 'label' => 'Open'],
                ['value' => Ticket::STATUS_IN_PROGRESS, 'label' => 'In Progress'],
                ['value' => Ticket::STATUS_WAITING_RESPONSE, 'label' => 'Waiting Response'],
                ['value' => Ticket::STATUS_RESOLVED, 'label' => 'Resolved'],
                ['value' => Ticket::STATUS_CLOSED, 'label' => 'Closed'],
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
     * Get ticket statistics for dashboard widgets.
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();

        // Use AuthService to get user role
        $authService = app(AuthService::class);
        $userRole = $authService->getUserRole($user);

        // Only regular users have tickets they created
        if ($user instanceof User) {
            $stats = [
                'total' => $user->tickets()->count(),
                'open' => $user->tickets()->where('status', Ticket::STATUS_OPEN)->count(),
                'in_progress' => $user->tickets()->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
                'waiting_response' => $user->tickets()->where('status', Ticket::STATUS_WAITING_RESPONSE)->count(),
                'resolved' => $user->tickets()->where('status', Ticket::STATUS_RESOLVED)->count(),
                'closed' => $user->tickets()->where('status', Ticket::STATUS_CLOSED)->count(),
                'overdue' => $user->tickets()
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', Carbon::now())
                    ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
                    ->count(),
            ];
        } else {
            // Other user types don't have tickets they created
            $stats = [
                'total' => 0,
                'open' => 0,
                'in_progress' => 0,
                'waiting_response' => 0,
                'resolved' => 0,
                'closed' => 0,
                'overdue' => 0,
            ];
        }

        return response()->json($stats);
    }

    /**
     * Download ticket attachment.
     */
    public function downloadAttachment($ticketId, $filename)
    {
        $user = Auth::user();

        try {
            $ticket = Ticket::where('id', $ticketId)
                ->where('user_nip', $user->nip)
                ->firstOrFail();

            $attachmentPath = "tickets/{$ticket->ticket_number}/{$filename}";

            if (!Storage::disk('public')->exists($attachmentPath)) {
                abort(404, 'File not found');
            }

            return response()->download(storage_path('app/public/' . $attachmentPath));

        } catch (\Exception $e) {
            abort(404, 'File not found');
        }
    }

    /**
     * Rate a resolved ticket.
     */
    public function rateTicket(Request $request, $id)
    {
        $user = Auth::user();

        // Validate request
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
        ]);

        try {
            $ticket = Ticket::where('id', $id)
                ->where('user_nip', $user->nip)
                ->where('status', Ticket::STATUS_RESOLVED)
                ->firstOrFail();

            // Update ticket rating
            $ticket->update([
                'user_rating' => $request->rating,
                'user_feedback' => $request->feedback,
            ]);

            // Update teknisi rating if assigned
            if ($ticket->assignedTeknisi) {
                $ticket->assignedTeknisi->recordTicketPerformance($ticket);
            }

            return redirect()->back()->with('success', 'Thank you for your feedback!');

        } catch (\Exception $e) {
            Log::error('Failed to submit rating', [
                'ticket_id' => $id,
                'user_nip' => $user->nip,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->withErrors(['rating' => 'Failed to submit rating. Please try again.']);
        }
    }

    /**
     * Save ticket draft.
     */
    public function saveDraft(Request $request)
    {
        $user = Auth::user();

        // Validate request (using English field names)
        $validator = Validator::make($request->all(), [
            'aplikasi_id' => 'nullable|exists:aplikasis,id',
            'kategori_masalah_id' => 'nullable|exists:kategori_masalahs,id',
            'title' => 'nullable|string|max:200',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'location' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            Log::error('Draft validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            // Convert empty strings to null for database compatibility
            $draftData = [
                'user_nip' => $user->nip,
                'aplikasi_id' => $request->aplikasi_id ?: null,
                'kategori_masalah_id' => $request->kategori_masalah_id ?: null,
                'title' => $request->title ?: null,
                'description' => $request->description ?: null,
                'priority' => $request->priority ?? 'medium',
                'location' => $request->location ?: null,
                'draft_data' => json_encode($request->only([
                    'aplikasi_id', 'kategori_masalah_id', 'title', 'description', 'priority', 'location'
                ])),
                'expires_at' => now()->addDays(7),
            ];

            // Save/update draft using updateOrCreate
            $draft = TicketDraft::updateOrCreate(
                ['user_nip' => $user->nip],
                $draftData
            );

            return response()->json([
                'success' => true,
                'message' => 'Draft saved successfully',
                'draft_id' => $draft->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to save draft', [
                'error' => $e->getMessage(),
                'user_nip' => $user->nip ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'errors' => ['Failed to save draft: ' . $e->getMessage()],
            ], 422);
        }
    }

    /**
     * Load ticket draft.
     */
    public function loadDraft(Request $request)
    {
        $user = Auth::user();

        try {
            $draft = TicketDraft::forUser($user->nip)
                ->active()
                ->first();

            if (!$draft) {
                return response()->json([
                    'success' => true,
                    'draft' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'draft' => $draft->toFormData(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to load draft'],
            ], 422);
        }
    }

    /**
     * Delete ticket draft.
     */
    public function deleteDraft(Request $request)
    {
        $user = Auth::user();

        try {
            $deleted = TicketDraft::forUser($user->nip)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Draft deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to delete draft'],
            ], 422);
        }
    }

    /**
     * Download all attachments for a ticket as a ZIP file.
     */
    public function downloadAllAttachments($id)
    {
        $user = Auth::user();
        $result = $this->ticketService->getTicketDetails($id, $user->nip);

        if (!$result['success']) {
            return back()->withErrors($result['errors']);
        }

        $ticket = $result['ticket'];
        $files = [];

        // Collect ticket attachments
        foreach ($ticket->attachments as $attachment) {
            $path = storage_path('app/public/' . $attachment->file_path);
            if (file_exists($path)) {
                $files[] = [
                    'path' => $path,
                    'name' => 'ticket_' . $attachment->file_name
                ];
            }
        }

        // Collect comment attachments
        foreach ($ticket->comments as $comment) {
            foreach ($comment->attachments as $attachment) {
                $path = storage_path('app/public/' . $attachment->file_path);
                if (file_exists($path)) {
                    $files[] = [
                        'path' => $path,
                        'name' => 'comment_' . $comment->id . '_' . $attachment->file_name
                    ];
                }
            }
        }

        if (empty($files)) {
            return back()->with('error', 'No attachments found for this ticket.');
        }

        // Create ZIP
        $zipFileName = 'ticket_' . $ticket->ticket_number . '_attachments.zip';
        $zipPath = storage_path('app/public/temp/' . $zipFileName);
        
        // Ensure temp directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($files as $file) {
                $zip->addFile($file['path'], $file['name']);
            }
            $zip->close();
        } else {
            return back()->with('error', 'Failed to create ZIP file.');
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    /**
     * Export user tickets.
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        try {
            // Get filter parameters
            $filters = $request->only([
                'status', 'priority', 'aplikasi_id', 'kategori_masalah_id',
                'search', 'date_from', 'date_to'
            ]);

            // Get filtered tickets for user
            $result = $this->ticketService->getUserTickets($user->nip, $filters, 1000);
            $tickets = $result->getCollection();

            // Generate filename with date
            $filename = 'my-tickets-' . Carbon::now()->format('Y-m-d') . '.xlsx';

            // Create and return Excel export
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\UserTicketsExport($tickets, $user),
                $filename
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to export tickets'],
            ], 422);
        }
    }

    /**
     * Get user's tickets for API consumption.
     */
    public function apiIndex(Request $request)
    {
        $user = Auth::user();

        $filters = $request->only([
            'status', 'priority', 'aplikasi_id', 'kategori_masalah_id',
            'search', 'sort_by', 'sort_direction'
        ]);

        $tickets = $this->ticketService->getUserTickets($user->nip, $filters, $request->get('per_page', 15));

        return response()->json([
            'tickets' => $tickets->items(),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
                'last_page' => $tickets->lastPage(),
            ],
        ]);
    }
}