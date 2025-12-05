<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TicketService
{
    /**
     * Generate unique ticket number with TKT-YYYYMMDD-XXXX format.
     */
    public function generateTicketNumber(): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = "TKT-{$date}";

        // Get the last ticket number for today with lock to prevent race conditions
        $lastTicket = Ticket::where('ticket_number', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->orderBy('ticket_number', 'desc')
            ->first();

        if ($lastTicket) {
            // Extract the sequence number and increment
            $lastSequence = intval(substr($lastTicket->ticket_number, -4));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        $ticketNumber = $prefix . '-' . str_pad($newSequence, 4, '0', STR_PAD_LEFT);

        // Ensure uniqueness (extra safety check)
        $attempts = 0;
        $maxAttempts = 10;

        while (Ticket::where('ticket_number', $ticketNumber)->exists() && $attempts < $maxAttempts) {
            $newSequence++;
            $ticketNumber = $prefix . '-' . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
            $attempts++;
        }

        if ($attempts >= $maxAttempts) {
            // If we still can't find a unique number, use timestamp as fallback
            $ticketNumber = $prefix . '-' . date('His') . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
        }

        return $ticketNumber;
    }

    /**
     * Handle file uploads for tickets.
     */
    public function handleFileUploads(array $files, string $ticketNumber): array
    {
        $uploadedFiles = [];
        $errors = [];

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            // Validate file
            $validation = $this->validateUploadedFile($file);
            if (!$validation['valid']) {
                $errors[] = 'File ' . ($index + 1) . ': ' . implode(', ', $validation['errors']);
                continue;
            }

            try {
                // Generate unique filename
                $extension = $file->getClientOriginalExtension();
                $filename = "{$ticketNumber}_" . uniqid() . ".{$extension}";
                $path = "tickets/{$ticketNumber}/{$filename}";

                // Store file
                $storedPath = Storage::disk('public')->putFileAs(
                    "tickets/{$ticketNumber}",
                    $file,
                    $filename
                );

                if ($storedPath) {
                    $uploadedFiles[] = [
                        'original_name' => $file->getClientOriginalName(),
                        'filename' => $filename,
                        'path' => $storedPath,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_at' => Carbon::now(),
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = 'File ' . ($index + 1) . ': Failed to upload file - ' . $e->getMessage();
            }
        }

        return [
            'uploaded_files' => $uploadedFiles,
            'errors' => $errors,
        ];
    }

    /**
     * Validate uploaded file.
     */
    private function validateUploadedFile(UploadedFile $file): array
    {
        $errors = [];

        // Check file size (max 2MB)
        if ($file->getSize() > 2 * 1024 * 1024) {
            $errors[] = 'File size must be less than 2MB';
        }

        // Check file type
        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'File type not allowed';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate ticket data.
     */
    public function validateTicketData(array $data, bool $isUpdate = false): array
    {
        $rules = $isUpdate ? Ticket::updateRules() : Ticket::rules();

        // Custom validation for user permissions
        if (isset($data['user_nip'])) {
            $authService = app(AuthService::class);
            $currentUser = \Illuminate\Support\Facades\Auth::user();

            // Users can only create tickets for themselves
            if ($data['user_nip'] !== $currentUser->nip) {
                if (!$currentUser->canManageTickets()) {
                    return [
                        'valid' => false,
                        'errors' => ['You can only create tickets for yourself'],
                    ];
                }
            }
        }

        $validator = Validator::make($data, $rules);

        return [
            'valid' => !$validator->fails(),
            'errors' => $validator->errors()->all(),
        ];
    }

    /**
     * Create new ticket with all related operations.
     */
    public function createTicket(array $data, ?array $files = null): array
    {
        try {
            DB::beginTransaction();

            // Generate ticket number FIRST
            $ticketNumber = $this->generateTicketNumber();
            $data['ticket_number'] = $ticketNumber;

            // Now validate data (including the generated ticket_number)
            $validation = $this->validateTicketData($data);
            if (!$validation['valid']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'errors' => $validation['errors'],
                ];
            }

            // Handle file uploads if provided
            $uploadedFiles = [];
            if ($files && !empty($files)) {
                $uploadResult = $this->handleFileUploads($files, $ticketNumber);
                $uploadedFiles = $uploadResult['uploaded_files'];

                if (!empty($uploadResult['errors'])) {
                    return [
                        'success' => false,
                        'errors' => $uploadResult['errors'],
                    ];
                }
            }

            // Store attachments as JSON
            if (!empty($uploadedFiles)) {
                $data['attachments'] = json_encode($uploadedFiles);
            }

            // Create ticket
            $ticket = Ticket::create($data);

            // Create initial history record
            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'action' => 'created',
                'performed_by_nip' => $ticket->user_nip,
                'performed_by_type' => 'user',
                'field_name' => 'status',
                'old_value' => null,
                'new_value' => $ticket->status,
                'description' => 'Ticket created',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'ticket_number' => $ticket->ticket_number,
                    'priority' => $ticket->priority,
                ]),
            ]);

            // Log to audit logs
            \App\Services\AuditLogService::logTicketCreated($ticket);

            DB::commit();

            return [
                'success' => true,
                'ticket' => $ticket->load(['user', 'aplikasi', 'kategoriMasalah']),
                'uploaded_files' => $uploadedFiles,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'errors' => ['Failed to create ticket: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Add comment to ticket.
     */
    public function addComment(int|string $ticketId, string $comment, ?array $files = null, ?string $userNip = null): array
    {
        try {
            $ticket = Ticket::findOrFail($ticketId);
            $userNip = $userNip ?? \Illuminate\Support\Facades\Auth::user()->nip;

            DB::beginTransaction();

            // Handle file uploads if provided
            $uploadedFiles = [];
            if ($files && !empty($files)) {
                $uploadResult = $this->handleFileUploads($files, $ticket->ticket_number);
                $uploadedFiles = $uploadResult['uploaded_files'];

                if (!empty($uploadResult['errors'])) {
                    return [
                        'success' => false,
                        'errors' => $uploadResult['errors'],
                    ];
                }
            }

            // Create comment
            $commentData = [
                'ticket_id' => $ticketId,
                'commenter_nip' => $userNip,
                'commenter_type' => 'App\Models\User',
                'comment' => $comment,
            ];

            if (!empty($uploadedFiles)) {
                $commentData['attachments'] = json_encode($uploadedFiles);
            }

            $ticketComment = TicketComment::create($commentData);

            // Update ticket's updated_at timestamp
            $ticket->touch();

            // Create history record for comment
            TicketHistory::create([
                'ticket_id' => $ticket->id,
                'action' => 'commented',
                'performed_by_nip' => $userNip,
                'performed_by_type' => 'user',
                'description' => 'Comment added to ticket',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'comment_id' => $ticketComment->id,
                ]),
            ]);

            // Log to audit logs
            \App\Services\AuditLogService::logCommentAdded($ticket, $ticketComment);

            DB::commit();

            return [
                'success' => true,
                'comment' => $ticketComment->load(['commenter']),
                'uploaded_files' => $uploadedFiles,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'errors' => ['Failed to add comment: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Update comment.
     */
    public function updateComment(int|string $commentId, string $content, string $userNip): array
    {
        try {
            $comment = TicketComment::findOrFail($commentId);

            // Check if user can edit this comment
            if (!$comment->canBeEditedBy(\App\Models\User::where('nip', $userNip)->first())) {
                return [
                    'success' => false,
                    'errors' => ['You do not have permission to edit this comment'],
                ];
            }

            DB::beginTransaction();

            $comment->edit($content, \App\Models\User::where('nip', $userNip)->first());

            // Log to history
            TicketHistory::create([
                'ticket_id' => $comment->ticket_id,
                'action' => 'comment_updated',
                'performed_by_nip' => $userNip,
                'performed_by_type' => 'user',
                'description' => 'Comment updated',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'comment_id' => $comment->id,
                ]),
            ]);

            DB::commit();

            return [
                'success' => true,
                'comment' => $comment->load(['user']),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'errors' => ['Failed to update comment: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Delete comment.
     */
    public function deleteComment(int|string $commentId, string $userNip): array
    {
        try {
            $comment = TicketComment::findOrFail($commentId);

            // Check if user can delete this comment (same rule as edit for now)
            if (!$comment->canBeEditedBy(\App\Models\User::where('nip', $userNip)->first())) {
                return [
                    'success' => false,
                    'errors' => ['You do not have permission to delete this comment'],
                ];
            }

            DB::beginTransaction();

            $ticketId = $comment->ticket_id;
            $comment->delete();

            // Log to history
            TicketHistory::create([
                'ticket_id' => $ticketId,
                'action' => 'comment_deleted',
                'performed_by_nip' => $userNip,
                'performed_by_type' => 'user',
                'description' => 'Comment deleted',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'comment_id' => $commentId,
                ]),
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Comment deleted successfully',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'errors' => ['Failed to delete comment: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Close ticket (user can only close their own resolved tickets).
     */
    public function closeTicket(int|string $ticketId, string $userNip, string $reason = null): array
    {
        try {
            $ticket = Ticket::findOrFail($ticketId);

            // Check if user can close this ticket
            if ($ticket->user_nip !== $userNip && !\Illuminate\Support\Facades\Auth::user()->canManageTickets()) {
                return [
                    'success' => false,
                    'errors' => ['You can only close your own tickets'],
                ];
            }

            // Check if ticket can be closed
            if (!$ticket->canTransitionTo(Ticket::STATUS_CLOSED)) {
                return [
                    'success' => false,
                    'errors' => ['Ticket cannot be closed in its current status'],
                ];
            }

            DB::beginTransaction();

            // Close the ticket
            $closed = $ticket->transitionTo(Ticket::STATUS_CLOSED, $userNip, $reason);

            if (!$closed) {
                throw new \Exception('Failed to close ticket');
            }

            DB::commit();

            return [
                'success' => true,
                'ticket' => $ticket->load(['user', 'aplikasi', 'kategoriMasalah', 'assignedTeknisi']),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'errors' => ['Failed to close ticket: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Update ticket status with proper validation and history tracking.
     */
    public function updateTicketStatus(int|string $ticketId, string $newStatus, string $userNip, string $notes = null): array
    {
        try {
            $ticket = Ticket::findOrFail($ticketId);

            // Check if status transition is valid
            if (!$ticket->canTransitionTo($newStatus)) {
                return [
                    'success' => false,
                    'errors' => ['Invalid status transition'],
                ];
            }

            DB::beginTransaction();

            // Update status
            $updated = $ticket->transitionTo($newStatus, $userNip, $notes);

            if (!$updated) {
                throw new \Exception('Failed to update ticket status');
            }

            DB::commit();

            return [
                'success' => true,
                'ticket' => $ticket->load(['user', 'aplikasi', 'kategoriMasalah', 'assignedTeknisi']),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'errors' => ['Failed to update ticket status: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Get tickets with pagination and filters for user.
     */
    public function getUserTickets(string $userNip, array $filters = [], int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Ticket::where('user_nip', $userNip)
            ->with(['aplikasi', 'kategoriMasalah', 'assignedTeknisi']);

        // Apply filters
        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority']) && $filters['priority']) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['aplikasi_id']) && $filters['aplikasi_id']) {
            $query->where('aplikasi_id', $filters['aplikasi_id']);
        }

        if (isset($filters['kategori_masalah_id']) && $filters['kategori_masalah_id']) {
            $query->where('kategori_masalah_id', $filters['kategori_masalah_id']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $query->search($filters['search']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        if ($sortBy === 'priority') {
            $query->orderByPriority($sortDirection);
        } elseif ($sortBy === 'due_date') {
            $query->orderByDueDate($sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get ticket details with comments and history.
     */
    public function getTicketDetails(int|string $ticketId, string $userNip): array
    {
        try {
            $ticket = Ticket::with([
                'user',
                'aplikasi',
                'kategoriMasalah',
                'assignedTeknisi',
                'comments.commenter',
                'history' => function ($query) {
                    $query->with([
                        'performedByUser',
                        'performedByTeknisi',
                        'performedByAdminHelpdesk',
                        'performedByAdminAplikasi'
                    ])->orderBy('created_at', 'desc');
                }
            ])->findOrFail($ticketId);

            // Check if user can view this ticket
            if ($ticket->user_nip !== $userNip && !\Illuminate\Support\Facades\Auth::user()->canManageTickets()) {
                return [
                    'success' => false,
                    'errors' => ['You do not have permission to view this ticket'],
                ];
            }

            // Increment view count
            $ticket->increment('view_count');

            // Format data for frontend
            $ticketData = [
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
                'aplikasi' => $ticket->aplikasi ? [
                    'id' => $ticket->aplikasi->id,
                    'name' => $ticket->aplikasi->name,
                    'code' => $ticket->aplikasi->code,
                    'icon' => $ticket->aplikasi->icon,
                ] : null,
                'kategori_masalah' => $ticket->kategoriMasalah ? [
                    'id' => $ticket->kategoriMasalah->id,
                    'name' => $ticket->kategoriMasalah->name,
                    'description' => $ticket->kategoriMasalah->description,
                ] : null,
                'assigned_teknisi' => $ticket->assignedTeknisi ? [
                    'nip' => $ticket->assignedTeknisi->nip,
                    'name' => $ticket->assignedTeknisi->name,
                    'department' => $ticket->assignedTeknisi->department,
                ] : null,
                'attachments' => $ticket->attachments ?? [],
                'location' => $ticket->location,
                'device_info' => $ticket->device_info,
                'ip_address' => $ticket->ip_address,
                'created_at' => $ticket->created_at,
                'formatted_created_at' => $ticket->formatted_created_at,
                'updated_at' => $ticket->updated_at,
                'formatted_updated_at' => $ticket->formatted_updated_at,
                'resolved_at' => $ticket->resolved_at,
                'formatted_resolved_at' => $ticket->formatted_resolved_at,
                'closed_at' => $ticket->closed_at,
                'formatted_closed_at' => $ticket->formatted_closed_at,
                'due_date' => $ticket->due_date,
                'formatted_due_date' => $ticket->formatted_due_date,
                'is_overdue' => $ticket->is_overdue,
                'is_within_sla' => $ticket->is_within_sla,
                'sla_status' => $ticket->sla_status,
                'time_elapsed' => $ticket->time_elapsed,
                'resolution_time' => $ticket->formatted_resolution_time,
                'view_count' => $ticket->view_count,
                'rating' => $ticket->user_rating,
                'feedback' => $ticket->user_feedback,
            ];

            // Format comments
            $comments = $ticket->comments->map(function ($comment) {
                $commenter = $comment->commenter;
                $commenterName = $commenter ? $commenter->name : 'Unknown User';
                $commenterType = $comment->commenter_type ?? 'user';
                // Convert class name to role type if needed
                if (is_string($commenterType) && str_contains($commenterType, '\\')) {
                    $commenterType = strtolower(class_basename($commenterType));
                    $commenterType = str_replace('admin', 'admin_', $commenterType);
                }
                
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'commenter_name' => $commenterName,
                    'commenter_type' => $commenterType,
                    'user' => [
                        'nip' => $commenter ? $commenter->nip : null,
                        'name' => $commenterName,
                        'role' => $commenterType,
                    ],
                    'attachments' => $comment->attachments ?? [],
                    'created_at' => $comment->created_at,
                    'formatted_created_at' => $comment->created_at ? $comment->created_at->diffForHumans() : null,
                ];
            });

            // Format history - get performer based on type
            $history = $ticket->history->map(function ($historyItem) {
                // Get performer based on performed_by_type
                $performer = null;
                switch ($historyItem->performed_by_type) {
                    case 'user':
                        $performer = $historyItem->performedByUser;
                        break;
                    case 'teknisi':
                        $performer = $historyItem->performedByTeknisi;
                        break;
                    case 'admin_helpdesk':
                        $performer = $historyItem->performedByAdminHelpdesk;
                        break;
                    case 'admin_aplikasi':
                        $performer = $historyItem->performedByAdminAplikasi;
                        break;
                }
                
                return [
                    'id' => $historyItem->id,
                    'old_value' => $historyItem->old_value,
                    'new_value' => $historyItem->new_value,
                    'action' => $historyItem->action,
                    'description' => $historyItem->description ?? $historyItem->action_label,
                    'field_name' => $historyItem->field_name,
                    'user' => [
                        'nip' => $performer ? $performer->nip : $historyItem->performed_by_nip,
                        'name' => $performer ? $performer->name : 'System',
                        'role' => $historyItem->performed_by_type ?? 'system',
                    ],
                    'created_at' => $historyItem->created_at,
                    'formatted_created_at' => $historyItem->created_at ? $historyItem->created_at->diffForHumans() : null,
                ];
            });

            return [
                'success' => true,
                'ticket' => $ticketData,
                'comments' => $comments,
                'history' => $history,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Failed to load ticket details: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Get available applications and categories for ticket creation.
     */
    public function getTicketCreationData(): array
    {
        $applications = \App\Models\Aplikasi::active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'icon']);

        $categories = \App\Models\KategoriMasalah::active()
            ->with('aplikasi:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'aplikasi_id', 'priority', 'description']);

        return [
            'applications' => $applications,
            'categories' => $categories,
        ];
    }
}
