<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use App\Services\NotificationService;
use App\Models\Notification;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get unread notifications for the authenticated user.
     * Supports pagination, filtering, and sorting.
     */
    public function getUnread(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'errors' => ['Authentication required']
            ], 401);
        }

        // Get filter parameters
        $filters = $request->only([
            'type', 'priority', 'status', 'is_read', 'ticket_id',
            'search', 'sort_by', 'sort_direction', 'urgent', 'recent'
        ]);

        // Set defaults
        if (!isset($filters['sort_by'])) {
            $filters['sort_by'] = 'created_at';
            $filters['sort_direction'] = 'desc';
        }

        if (!isset($filters['is_read'])) {
            $filters['is_read'] = false; // Get unread notifications by default
        }

        // Set pagination
        $perPage = min($request->get('per_page', 15), 50); // Max 50 per page

        try {
            // Get notifications using service
            $notifications = $this->notificationService->getUserNotifications(
                $user->nip,
                $filters,
                $perPage
            );

            // Format notifications for API response
            $formattedNotifications = collect($notifications->items())->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'type_label' => $notification->type_label,
                    'type_icon' => $notification->type_icon,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'priority' => $notification->priority,
                    'priority_label' => $notification->priority_label,
                    'priority_badge_color' => $notification->priority_badge_color,
                    'status' => $notification->status,
                    'is_read' => $notification->isRead(),
                    'is_unread' => $notification->isUnread(),
                    'action_url' => $notification->action_url,
                    'icon' => $notification->icon,
                    'data' => $notification->data,
                    'created_at' => $notification->created_at,
                    'formatted_created_at' => $notification->formatted_created_at,
                    'time_elapsed' => $notification->time_elapsed,
                    'ticket' => $notification->ticket ? [
                        'id' => $notification->ticket->id,
                        'ticket_number' => $notification->ticket->ticket_number,
                        'title' => $notification->ticket->title,
                        'status' => $notification->ticket->status,
                        'priority' => $notification->ticket->priority,
                    ] : null,
                    'triggered_by' => $notification->triggeredBy ? [
                        'nip' => $notification->triggeredBy->nip,
                        'name' => $notification->triggeredBy->name,
                        'type' => class_basename($notification->triggeredBy),
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'notifications' => $formattedNotifications,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem(),
                ],
                'filters' => $filters,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to retrieve notifications: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'errors' => ['Authentication required']
            ], 401);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|integer|exists:notifications,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            // Mark notification as read using service
            $result = $this->notificationService->markAsRead(
                $request->notification_id,
                $user->nip
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'errors' => $result['errors'],
                ], 403);
            }

            // Format notification for response
            $notification = [
                'id' => $result['notification']->id,
                'type' => $result['notification']->type,
                'type_label' => $result['notification']->type_label,
                'title' => $result['notification']->title,
                'message' => $result['notification']->message,
                'priority' => $result['notification']->priority,
                'priority_label' => $result['notification']->priority_label,
                'is_read' => $result['notification']->isRead(),
                'read_at' => $result['notification']->read_at,
                'formatted_read_at' => $result['notification']->formatted_read_at,
                'action_url' => $result['notification']->action_url,
                'created_at' => $result['notification']->created_at,
                'formatted_created_at' => $result['notification']->formatted_created_at,
            ];

            return response()->json([
                'success' => true,
                'notification' => $notification,
                'message' => 'Notification marked as read successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to mark notification as read: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Mark all notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'errors' => ['Authentication required']
            ], 401);
        }

        // Optional user type parameter (for teknisi vs regular user)
        $userType = $request->get('user_type', 'user');

        try {
            // Mark all notifications as read using service
            $result = $this->notificationService->markAllAsRead(
                $user->nip,
                $userType
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'errors' => $result['errors'],
                ], 404);
            }

            return response()->json([
                'success' => true,
                'marked_count' => $result['marked_count'],
                'message' => "Successfully marked {$result['marked_count']} notifications as read",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to mark all notifications as read: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Get notification statistics for the authenticated user.
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'errors' => ['Authentication required']
            ], 401);
        }

        $userType = $request->get('user_type', 'user');

        try {
            $statsResult = $this->notificationService->getNotificationStats($user->nip, $userType);
            $unreadResult = $this->notificationService->getUnreadCount($user->nip, $userType);

            if (!$statsResult['success'] || !$unreadResult['success']) {
                return response()->json([
                    'success' => false,
                    'errors' => array_merge(
                        $statsResult['errors'] ?? [],
                        $unreadResult['errors'] ?? []
                    ),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'stats' => array_merge($statsResult['stats'], [
                    'unread_count' => $unreadResult['count']
                ]),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to retrieve notification statistics: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Display the notifications management page for admin.
     */
    public function index(Request $request)
    {
        $adminHelpdesk = Auth::guard('admin_helpdesk')->user();
        if (!$adminHelpdesk) {
            return redirect()->route('login');
        }

        try {
            // Get notifications with filters (removed triggeredBy relationship to avoid errors)
            $query = Notification::with(['ticket'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('status')) {
                if ($request->status === 'read') {
                    $query->whereNotNull('read_at');
                } elseif ($request->status === 'unread') {
                    $query->whereNull('read_at');
                }
            }

            if ($request->filled('recipient_type') && $request->recipient_type !== 'all') {
                $recipientTypeMap = [
                    'users' => \App\Models\User::class,
                    'teknisi' => \App\Models\Teknisi::class,
                    'admin-helpdesk' => \App\Models\AdminHelpdesk::class,
                    'admin-aplikasi' => \App\Models\AdminAplikasi::class,
                ];
                
                if (isset($recipientTypeMap[$request->recipient_type])) {
                    $query->where('notifiable_type', $recipientTypeMap[$request->recipient_type]);
                }
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%");
                });
            }

            $notifications = $query->paginate(20);

            // Format notifications while preserving paginator structure
            $formattedData = $notifications->getCollection()->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'recipient_type' => $this->getRecipientTypeForApi($notification->notifiable_type),
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->toISOString(),
                    'triggered_by' => $notification->triggeredBy ? [
                        'nip' => $notification->triggeredBy->nip,
                        'name' => $notification->triggeredBy->name,
                        'type' => class_basename($notification->triggeredBy),
                    ] : null,
                ];
            });

            // Create a paginator-like structure with formatted data
            $formattedNotifications = new \Illuminate\Pagination\LengthAwarePaginator(
                $formattedData,
                $notifications->total(),
                $notifications->perPage(),
                $notifications->currentPage(),
                [
                    'path' => $notifications->path(),
                    'pageName' => 'page',
                ]
            );

            // Get statistics
            $stats = [
                'total' => Notification::count(),
                'read' => Notification::whereNotNull('read_at')->count(),
                'unread' => Notification::whereNull('read_at')->count(),
                'today' => Notification::whereDate('created_at', today())->count(),
            ];

            return Inertia::render('AdminHelpdesk/Notifications', [
                'notifications' => $formattedNotifications,
                'stats' => $stats,
                'filters' => $request->only(['type', 'status', 'recipient_type', 'search']),
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to load notifications: ' . $e->getMessage()]);
        }
    }

    /**
     * Store a new notification.
     */
    public function store(Request $request)
    {
        $adminHelpdesk = Auth::guard('admin_helpdesk')->user();
        if (!$adminHelpdesk) {
            return response()->json(['success' => false, 'errors' => ['Authentication required']], 401);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:announcement,system,ticket,user',
            'recipient_type' => 'required|in:all,users,teknisi,admin-helpdesk,admin-aplikasi',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $result = $this->notificationService->createNotificationForRecipients([
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'triggered_by_type' => 'AdminHelpdesk',
                'triggered_by_nip' => $adminHelpdesk->nip,
                'data' => [
                    'created_by' => $adminHelpdesk->name ?? $adminHelpdesk->nip,
                    'created_at' => now()->toISOString(),
                ],
            ], $request->recipient_type);

            if ($result['success']) {
                return response()->json([
                    'success' => true, 
                    'notifications_created' => $result['notifications_created'],
                    'message' => 'Notifications created successfully for ' . $request->recipient_type
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'errors' => $result['errors']
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to create notification: ' . $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Mark a notification as read (web route).
     */
    public function markAsReadWeb($id)
    {
        $adminHelpdesk = Auth::guard('admin_helpdesk')->user();
        if (!$adminHelpdesk) {
            return redirect()->route('login');
        }

        try {
            $notification = Notification::findOrFail($id);
            $notification->update(['read_at' => now()]);

            return back()->with('success', 'Notification marked as read');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to mark notification as read']);
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead()
    {
        $adminHelpdesk = Auth::guard('admin_helpdesk')->user();
        if (!$adminHelpdesk) {
            return redirect()->route('login');
        }

        try {
            Notification::whereNull('read_at')->update(['read_at' => now()]);

            return back()->with('success', 'All notifications marked as read');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to mark all notifications as read']);
        }
    }

    /**
     * Delete a notification.
     */
    public function destroy($id)
    {
        $adminHelpdesk = Auth::guard('admin_helpdesk')->user();
        if (!$adminHelpdesk) {
            return redirect()->route('login');
        }

        try {
            $notification = Notification::findOrFail($id);
            $notification->delete();

            return back()->with('success', 'Notification deleted successfully');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete notification']);
        }
    }

    /**
     * Convert notifiable_type class name to API format for recipient_type
     */
    private function getRecipientTypeForApi($notifiableType)
    {
        $recipientTypeMap = [
            \App\Models\User::class => 'users',
            \App\Models\Teknisi::class => 'teknisi',
            \App\Models\AdminHelpdesk::class => 'admin-helpdesk',
            \App\Models\AdminAplikasi::class => 'admin-aplikasi',
        ];
        
        return $recipientTypeMap[$notifiableType] ?? $notifiableType;
    }
}