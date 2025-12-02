<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;

class UserController extends Controller
{
    /**
     * Get tickets for user API.
     */
    public function tickets(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Get filter parameters
        $filters = $request->only([
            'status', 'priority', 'aplikasi_id', 'kategori_masalah_id',
            'search', 'sort_by', 'sort_direction', 'date_from', 'date_to'
        ]);

        // Set default sorting
        if (!isset($filters['sort_by'])) {
            $filters['sort_by'] = 'created_at';
            $filters['sort_direction'] = 'desc';
        }

        // Get tickets created by this user
        $tickets = $this->getUserTickets($user, $filters, 20);

        // Format tickets for API response
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
                'resolved_at' => $ticket->resolved_at,
                'is_overdue' => $ticket->is_overdue,
                'is_escalated' => $ticket->is_escalated,
                'sla_status' => $ticket->sla_status,
                'time_elapsed' => $ticket->time_elapsed,
                'resolution_time_minutes' => $ticket->resolution_time_minutes,
                'user_rating' => $ticket->user_rating,
                'solution' => $ticket->solution,
                'comments' => $ticket->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'message' => $comment->message,
                        'user_name' => $comment->user->name,
                        'is_internal' => $comment->is_internal,
                        'created_at' => $comment->created_at,
                        'formatted_created_at' => $comment->created_at->diffForHumans(),
                    ];
                }),
                'history' => $ticket->history->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'action' => $history->action,
                        'old_status' => $history->old_status,
                        'new_status' => $history->new_status,
                        'notes' => $history->notes,
                        'created_by' => $history->created_by,
                        'created_at' => $history->created_at,
                        'formatted_created_at' => $history->created_at->diffForHumans(),
                    ];
                }),
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

        return response()->json([
            'success' => true,
            'tickets' => $formattedPaginator,
            'filters' => $filters,
            'user_stats' => [
                'total_tickets' => $user->tickets()->count(),
                'open_tickets' => $user->tickets()->where('status', Ticket::STATUS_OPEN)->count(),
                'in_progress_tickets' => $user->tickets()->where('status', Ticket::STATUS_IN_PROGRESS)->count(),
                'resolved_tickets' => $user->tickets()->where('status', Ticket::STATUS_RESOLVED)->count(),
                'closed_tickets' => $user->tickets()->where('status', Ticket::STATUS_CLOSED)->count(),
                'avg_resolution_time' => $this->calculateUserAvgResolutionTime($user),
            ],
        ]);
    }

    /**
     * Create new ticket via API.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validate request data
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'required|in:' . implode(',', [
                Ticket::PRIORITY_LOW, Ticket::PRIORITY_MEDIUM,
                Ticket::PRIORITY_HIGH, Ticket::PRIORITY_URGENT
            ]),
            'aplikasi_id' => 'required|exists:aplikasis,id',
            'kategori_masalah_id' => 'required|exists:kategori_masalahs,id',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            // Verify application and category are active
            $aplikasi = Aplikasi::where('id', $request->aplikasi_id)
                ->where('status', 'active')
                ->first();

            if (!$aplikasi) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Selected application is not available'],
                ], 422);
            }

            $kategori = KategoriMasalah::where('id', $request->kategori_masalah_id)
                ->where('status', 'active')
                ->where('aplikasi_id', $request->aplikasi_id)
                ->first();

            if (!$kategori) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Selected category is not available for this application'],
                ], 422);
            }

            // Create new ticket
            $ticket = Ticket::create([
                'ticket_number' => $this->generateTicketNumber(),
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'status' => Ticket::STATUS_OPEN,
                'user_nip' => $user->nip,
                'aplikasi_id' => $request->aplikasi_id,
                'kategori_masalah_id' => $request->kategori_masalah_id,
                'source' => 'api',
            ]);

            // Handle file attachments if provided
            if ($request->hasFile('attachments')) {
                $this->handleAttachments($ticket, $request->file('attachments'));
            }

            // Auto-assign teknisi if possible
            $this->autoAssignTeknisi($ticket);

            return response()->json([
                'success' => true,
                'message' => 'Ticket created successfully',
                'ticket' => [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'aplikasi' => [
                        'id' => $aplikasi->id,
                        'name' => $aplikasi->name,
                        'code' => $aplikasi->code,
                    ],
                    'kategori_masalah' => [
                        'id' => $kategori->id,
                        'name' => $kategori->name,
                    ],
                    'assigned_teknisi' => $ticket->assignedTeknisi ? [
                        'nip' => $ticket->assignedTeknisi->nip,
                        'name' => $ticket->assignedTeknisi->name,
                    ] : null,
                    'created_at' => $ticket->created_at,
                    'formatted_created_at' => $ticket->created_at->diffForHumans(),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to create ticket: ' . $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Get tickets created by user with filtering.
     */
    private function getUserTickets(\App\Models\User $user, array $filters, int $perPage = 20)
    {
        $query = $user->tickets()
            ->with(['aplikasi', 'kategoriMasalah', 'assignedTeknisi', 'comments.user', 'history']);

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

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%")
                  ->orWhere('ticket_number', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        switch ($sortBy) {
            case 'priority':
                $query->orderByPriority($sortDirection);
                break;
            case 'status':
                $query->orderBy('status', $sortDirection);
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
     * Generate unique ticket number.
     */
    private function generateTicketNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(microtime()), 0, 4));

        return "TK-{$date}-{$random}";
    }

    /**
     * Handle file attachments for ticket.
     */
    private function handleAttachments(Ticket $ticket, array $attachments): void
    {
        foreach ($attachments as $file) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = time() . '_' . uniqid() . '.' . $extension;
            $path = $file->storeAs('tickets/' . $ticket->id, $fileName, 'public');

            // Create attachment record (you would need an attachments table for this)
            // For now, we'll just store the file
        }
    }

    /**
     * Auto-assign teknisi to ticket.
     */
    private function autoAssignTeknisi(Ticket $ticket): void
    {
        try {
            // Use the Teknisi model method to find best teknisi
            $bestTeknisi = \App\Models\Teknisi::findBestTeknisiForTicket($ticket);

            if ($bestTeknisi) {
                $ticket->assignToTeknisi($bestTeknisi->nip, 'system', 'Auto-assigned via API');
            }
        } catch (\Exception $e) {
            // Log error but don't fail ticket creation
            \Illuminate\Support\Facades\Log::error('Auto-assignment failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate user's average resolution time.
     */
    private function calculateUserAvgResolutionTime(\App\Models\User $user): ?float
    {
        $avgMinutes = $user->tickets()
            ->where('status', Ticket::STATUS_RESOLVED)
            ->whereNotNull('resolution_time_minutes')
            ->avg('resolution_time_minutes');

        return $avgMinutes ? round($avgMinutes / 60, 2) : null; // Convert to hours
    }
}