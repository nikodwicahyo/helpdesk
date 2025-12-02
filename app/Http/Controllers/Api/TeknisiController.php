<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Ticket;

class TeknisiController extends Controller
{
    /**
     * Get tickets for teknisi API.
     */
    public function tickets(Request $request)
    {
        /** @var \App\Models\Teknisi $teknisi */
        $teknisi = Auth::user();

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

        // Get tickets assigned to this teknisi
        $tickets = $this->getTeknisiTickets($teknisi, $filters, 20);

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
                'user' => $ticket->user ? [
                    'nip' => $ticket->user->nip,
                    'name' => $ticket->user->name,
                    'department' => $ticket->user->department,
                    'email' => $ticket->user->email,
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
                'first_response_at' => $ticket->first_response_at,
                'resolved_at' => $ticket->resolved_at,
                'is_overdue' => $ticket->is_overdue,
                'is_escalated' => $ticket->is_escalated,
                'sla_status' => $ticket->sla_status,
                'time_elapsed' => $ticket->time_elapsed,
                'resolution_time_minutes' => $ticket->resolution_time_minutes,
                'comments' => $ticket->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'message' => $comment->message,
                        'user_name' => $comment->user->name,
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
            'teknisi_stats' => [
                'total_assigned' => $teknisi->assignedTickets()->count(),
                'active_tickets' => $teknisi->getCurrentWorkload(),
                'resolved_this_month' => $teknisi->resolvedTickets()
                    ->whereMonth('resolved_at', now()->month)
                    ->count(),
                'avg_resolution_time' => $teknisi->getAverageResolutionTime(),
                'current_rating' => $teknisi->rating,
            ],
        ]);
    }

    /**
     * Update ticket status via API.
     */
    public function update(Request $request, $ticketId)
    {
        $teknisi = Auth::user();

        // Validate request
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:' . implode(',', [
                Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING_RESPONSE,
                Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED
            ]),
            'notes' => 'nullable|string|max:1000',
            'solution' => 'required_if:status,' . Ticket::STATUS_RESOLVED . '|nullable|string|max:2000',
            'technical_notes' => 'nullable|string|max:1000',
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

            $oldStatus = $ticket->status;
            $newStatus = $request->status;

            // Update ticket status
            $result = $ticket->transitionTo($newStatus, $teknisi->nip, $request->notes);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'errors' => ['Invalid status transition'],
                ], 422);
            }

            // Add solution if provided and status is resolved
            if ($newStatus === Ticket::STATUS_RESOLVED && $request->filled('solution')) {
                $ticket->solution = $request->solution;
                $ticket->save();
            }

            // Add technical notes if provided
            if ($request->filled('technical_notes')) {
                $ticket->comments()->create([
                    'ticket_id' => $ticket->id,
                    'user_nip' => $teknisi->nip,
                    'message' => $request->technical_notes,
                    'is_internal' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Ticket updated successfully',
                'ticket' => [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'status_label' => $ticket->status_label,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'updated_at' => $ticket->updated_at,
                    'formatted_updated_at' => $ticket->updated_at->diffForHumans(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Ticket not found or access denied'],
            ], 404);
        }
    }

    /**
     * Get tickets assigned to teknisi with filtering.
     */
    private function getTeknisiTickets(\App\Models\Teknisi $teknisi, array $filters, int $perPage = 20)
    {
        $query = $teknisi->assignedTickets()
            ->with(['user', 'aplikasi', 'kategoriMasalah', 'comments.user', 'history']);

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
     * Refresh assigned tickets for teknisi.
     */
    public function refreshAssignedTickets(Request $request)
    {
        /** @var \App\Models\Teknisi $teknisi */
        $teknisi = Auth::user();

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

        // Get tickets assigned to this teknisi
        $tickets = $this->getTeknisiTickets($teknisi, $filters, 20);

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
                'user' => $ticket->user ? [
                    'nip' => $ticket->user->nip,
                    'name' => $ticket->user->name,
                    'department' => $ticket->user->department,
                    'email' => $ticket->user->email,
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
                'first_response_at' => $ticket->first_response_at,
                'resolved_at' => $ticket->resolved_at,
                'is_overdue' => $ticket->is_overdue,
                'is_escalated' => $ticket->is_escalated,
                'sla_status' => $ticket->sla_status,
                'time_elapsed' => $ticket->time_elapsed,
                'resolution_time_minutes' => $ticket->resolution_time_minutes,
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
            'data' => $formattedPaginator,
            'timestamp' => now()->toISOString(),
            'cache_buster' => uniqid(),
        ]);
    }

    /**
     * Get available teknisis for reassignment suggestion.
     */
    public function getAvailableTeknisis(Request $request)
    {
        /** @var \App\Models\Teknisi $currentTeknisi */
        $currentTeknisi = Auth::user();

        try {
            // Get all active teknisis except current one
            $teknisis = \App\Models\Teknisi::where('status', 'active')
                ->where('nip', '!=', $currentTeknisi->nip)
                ->orderBy('name')
                ->get()
                ->map(function ($teknisi) {
                    return [
                        'nip' => $teknisi->nip,
                        'name' => $teknisi->name,
                        'email' => $teknisi->email,
                        'department' => $teknisi->department ?? $teknisi->unit_kerja ?? 'N/A',
                        'specialization' => $teknisi->specialization ?? null,
                        'current_workload' => $teknisi->getCurrentWorkload(),
                        'rating' => $teknisi->rating,
                    ];
                });

            return response()->json([
                'success' => true,
                'teknisis' => $teknisis,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load available teknisis',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh workload for teknisi.
     */
    public function refreshWorkload(Request $request)
    {
        /** @var \App\Models\Teknisi $teknisi */
        $teknisi = Auth::user();

        try {
            $workloadStats = [
                'total_assigned' => $teknisi->assignedTickets()->count(),
                'active_tickets' => $teknisi->getCurrentWorkload(),
                'resolved_this_month' => $teknisi->resolvedTickets()
                    ->whereMonth('resolved_at', now()->month)
                    ->count(),
                'avg_resolution_time' => $teknisi->getAverageResolutionTime(),
                'current_rating' => $teknisi->rating,
            ];

            // Get priority breakdown
            $priorityBreakdown = $teknisi->assignedTickets()
                ->whereNotIn('status', ['resolved', 'closed'])
                ->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority');

            // Get status breakdown
            $statusBreakdown = $teknisi->assignedTickets()
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            return response()->json([
                'success' => true,
                'data' => [
                    'workloadStats' => $workloadStats,
                    'priorityBreakdown' => $priorityBreakdown,
                    'statusBreakdown' => $statusBreakdown,
                ],
                'timestamp' => now()->toISOString(),
                'cache_buster' => uniqid(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to refresh workload data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}