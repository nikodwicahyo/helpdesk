<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\TicketHistory;
use App\Models\Ticket;
use Carbon\Carbon;

class TicketHistoryController extends Controller
{
    /**
     * Display ticket history for the authenticated user's tickets.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get user's ticket IDs
        $userTicketIds = Ticket::where('user_nip', $user->nip)->pluck('id');

        // Build query for ticket history
        $query = TicketHistory::whereIn('ticket_id', $userTicketIds)
            ->with([
                'ticket:id,ticket_number,title',
                'performedByUser:nip,name',
                'performedByTeknisi:nip,name',
                'performedByAdminHelpdesk:nip,name',
                'performedByAdminAplikasi:nip,name'
            ])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('ticket_id')) {
            $query->where('ticket_id', $request->ticket_id);
        }

        if ($request->filled('action_type')) {
            $query->where('action', $request->action_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhereHas('ticket', function ($tq) use ($search) {
                      $tq->where('ticket_number', 'like', "%{$search}%");
                  });
            });
        }

        // Paginate results
        $history = $query->paginate($request->get('per_page', 15));

        // Format history for frontend
        $formattedHistory = $history->through(function ($item) {
            // Get the performer based on type
            $performer = null;
            switch ($item->performed_by_type) {
                case 'user':
                    $performer = $item->performedByUser;
                    break;
                case 'teknisi':
                    $performer = $item->performedByTeknisi;
                    break;
                case 'admin_helpdesk':
                    $performer = $item->performedByAdminHelpdesk;
                    break;
                case 'admin_aplikasi':
                    $performer = $item->performedByAdminAplikasi;
                    break;
            }

            return [
                'id' => $item->id,
                'ticket' => [
                    'id' => $item->ticket->id,
                    'ticket_number' => $item->ticket->ticket_number,
                    'title' => $item->ticket->title,
                ],
                'action' => $item->action,
                'action_label' => $this->getActionLabel($item->action),
                'actor_type' => $item->performed_by_type,
                'actor' => $performer ? [
                    'name' => $performer->name ?? 'Unknown',
                    'type' => $item->performed_by_type,
                ] : null,
                'old_value' => $item->old_value,
                'new_value' => $item->new_value,
                'description' => $item->description,
                'created_at' => $item->created_at,
                'formatted_created_at' => $item->created_at->format('d M Y H:i'),
                'time_ago' => $item->created_at->diffForHumans(),
            ];
        });

        // Get user's tickets for filter dropdown
        $userTickets = Ticket::where('user_nip', $user->nip)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'ticket_number', 'title'])
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'label' => $ticket->ticket_number . ' - ' . $ticket->title,
                ];
            });

        // Get available action types
        $actionTypes = [
            ['value' => 'created', 'label' => 'Ticket Created'],
            ['value' => 'status_changed', 'label' => 'Status Changed'],
            ['value' => 'priority_changed', 'label' => 'Priority Changed'],
            ['value' => 'assigned', 'label' => 'Assigned to Teknisi'],
            ['value' => 'comment_added', 'label' => 'Comment Added'],
            ['value' => 'resolved', 'label' => 'Resolved'],
            ['value' => 'closed', 'label' => 'Closed'],
            ['value' => 'reopened', 'label' => 'Reopened'],
        ];

        return Inertia::render('User/TicketHistory', [
            'history' => $formattedHistory,
            'userTickets' => $userTickets,
            'actionTypes' => $actionTypes,
            'filters' => $request->only(['ticket_id', 'action_type', 'date_from', 'date_to', 'search']),
        ]);
    }

    /**
     * Export ticket history to Excel.
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        // Get user's ticket IDs
        $userTicketIds = Ticket::where('user_nip', $user->nip)->pluck('id');

        // Build query for ticket history
        $query = TicketHistory::whereIn('ticket_id', $userTicketIds)
            ->with([
                'ticket:id,ticket_number,title',
                'performedByUser:nip,name',
                'performedByTeknisi:nip,name',
                'performedByAdminHelpdesk:nip,name',
                'performedByAdminAplikasi:nip,name'
            ])
            ->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->filled('ticket_id')) {
            $query->where('ticket_id', $request->ticket_id);
        }

        if ($request->filled('action_type')) {
            $query->where('action', $request->action_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%");
            });
        }

        $history = $query->get();

        // Generate filename
        $filename = 'ticket-history-' . Carbon::now()->format('Y-m-d-His') . '.xlsx';

        // Return Excel export
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\TicketHistoryExport($history, $user),
            $filename
        );
    }

    /**
     * Get human-readable label for action type.
     */
    private function getActionLabel(string $action): string
    {
        $labels = [
            'created' => 'Ticket Created',
            'status_changed' => 'Status Changed',
            'priority_changed' => 'Priority Changed',
            'assigned' => 'Assigned to Teknisi',
            'comment_added' => 'Comment Added',
            'resolved' => 'Resolved',
            'closed' => 'Closed',
            'reopened' => 'Reopened',
            'updated' => 'Updated',
        ];

        return $labels[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }
}
