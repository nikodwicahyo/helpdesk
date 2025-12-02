<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\AdminHelpdesk;
use App\Models\Teknisi;
use App\Services\TicketService;

class TicketController extends Controller
{
    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * Get tickets for admin API.
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
            'tickets' => $formattedPaginator,
            'filters' => $filters,
        ]);
    }

    /**
     * Assign ticket to teknisi via API.
     */
    public function assign(Request $request, $ticketId)
    {
        $admin = Auth::user();

        // Validate request
        $validator = Validator::make($request->all(), [
            'teknisi_nip' => 'required|string|exists:teknisis,nip',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
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
                return response()->json([
                    'success' => false,
                    'errors' => ['Failed to assign ticket'],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Ticket assigned successfully',
                'ticket' => [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'assigned_teknisi' => [
                        'nip' => $teknisi->nip,
                        'name' => $teknisi->name,
                        'department' => $teknisi->department,
                    ],
                    'status' => $ticket->status,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Ticket or technician not found'],
            ], 404);
        }
    }

    /**
     * Get filtered tickets with advanced filtering.
     */
    private function getFilteredTickets(array $filters, int $perPage = 20)
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
     * Refresh tickets for admin helpdesk.
     */
    public function refreshTickets(Request $request)
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
     * Refresh tickets for general users.
     */
    public function refresh(Request $request)
    {
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
     * Get tickets created by user with filtering (helper method).
     */
    private function getUserTickets($user, array $filters, int $perPage = 20)
    {
        $query = $user->tickets()
            ->with(['aplikasi', 'kategoriMasalah', 'assignedTeknisi']);

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
}