<?php

namespace App\Http\Controllers\AdminHelpdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display admin helpdesk notifications page.
     */
    public function index(Request $request)
    {
        $user = Auth::guard('admin_helpdesk')->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Get filter parameters
        $filters = $request->only([
            'type', 'priority', 'status', 'is_read', 'ticket_id',
            'search', 'sort_by', 'sort_direction', 'urgent', 'recent',
            'recipient'
        ]);

        // Handle special status mapping for UI (read/unread to is_read)
        if (isset($filters['status'])) {
            if ($filters['status'] === 'read') {
                $filters['is_read'] = true;
                unset($filters['status']);
            } elseif ($filters['status'] === 'unread') {
                $filters['is_read'] = false;
                unset($filters['status']);
            }
        }

        // Set defaults
        if (!isset($filters['sort_by'])) {
            $filters['sort_by'] = 'created_at';
            $filters['sort_direction'] = 'desc';
        }

        // Set pagination
        $perPage = min($request->get('per_page', 20), 50);

        // Get admin helpdesk notifications
        $notifications = $this->notificationService->getAllNotifications($filters, $perPage);

        // Calculate statistics
        $stats = [
            'total' => $notifications->total(),
            'unread' => $this->notificationService->getAllNotifications(['is_read' => false], 1)->total(),
            'read' => $this->notificationService->getAllNotifications(['is_read' => true], 1)->total(),
            'today' => $this->notificationService->getAllNotifications(['date' => now()->format('Y-m-d')], 1)->total(),
        ];

        return Inertia::render('AdminHelpdesk/Notifications', [
            'notifications' => $notifications,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }
}