<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get the authenticated user (supports both Auth and session-based auth)
     */
    protected function getAuthenticatedUser()
    {
        // Try standard Auth first
        $user = Auth::guard('web')->user();
        if ($user) {
            return $user;
        }

        // Check for session-based authentication data (unified session only)
        $userSession = session('user_session', []);
        $sessionNip = $userSession['nip'] ?? null;
        $sessionRole = $userSession['user_role'] ?? null;

        if ($sessionNip && $sessionRole) {
            return (object) [
                'nip' => $sessionNip,
                'role' => $sessionRole
            ];
        }

        return null;
    }

    /**
     * Get unread notifications (for polling)
     */
    public function getUnread(Request $request)
    {
        // Get authenticated user (supports both Auth and session-based auth)
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            // For unauthenticated requests, return empty response in expected format
            // This allows the frontend to handle the state gracefully
            return response()->json([
                'success' => true,
                'message' => 'No active session',
                'data' => [
                    'notifications' => [],
                    'unread_count' => 0,
                    'total_count' => 0,
                ]
            ]);
        }

        try {
            $limit = min($request->get('limit', 50), 100);
            $unreadOnly = $request->get('unread_only', true);

            // Determine user type for proper querying
            $userType = 'user';
            if (isset($user->role)) {
                switch ($user->role) {
                    case 'teknisi':
                        $userType = 'teknisi';
                        break;
                    case 'admin_helpdesk':
                    case 'admin_aplikasi':
                    case 'user':
                        $userType = 'user'; // These all use the User query method
                        break;
                }
            }

            $filters = [
                'is_read' => $unreadOnly ? 0 : null,
                'per_page' => $limit,
                'sort_by' => 'created_at',
                'sort_direction' => 'desc'
            ];

            // Use the correct method based on user type
            if ($userType === 'teknisi') {
                $notifications = $this->notificationService->getTeknisiNotifications(
                    $user->nip,
                    $filters,
                    $limit
                );
            } else {
                $notifications = $this->notificationService->getUserNotifications(
                    $user->nip,
                    $filters,
                    $limit
                );
            }

            $formattedNotifications = collect($notifications->items())->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'type_label' => $notification->type_label,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'priority' => $notification->priority,
                    'priority_label' => $notification->priority_label,
                    'is_read' => $notification->isRead(),
                    'action_url' => $notification->action_url,
                    'created_at' => $notification->created_at,
                    'formatted_created_at' => $notification->formatted_created_at,
                    'time_elapsed' => $notification->time_elapsed,
                    'triggered_by' => $notification->getSafeTriggeredBy(),
                ];
            });

            $unreadCount = $formattedNotifications->where('is_read', false)->count();

            // Return in the format expected by the polling composable (nested structure)
            return response()->json([
                'success' => true,
                'message' => 'Notifications retrieved successfully',
                'data' => [
                    'notifications' => $formattedNotifications,
                    'unread_count' => $unreadCount,
                    'total_count' => $formattedNotifications->count(),
                ]
            ]);

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Notification polling error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user' => $user
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications',
                'data' => [
                    'notifications' => [],
                    'unread_count' => 0,
                    'total_count' => 0,
                ]
            ]);
        }
    }

    /**
     * Get user notifications
     */
    public function index(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return $this->errorResponse('Authentication required', [], 401);
        }

        try {
            $filters = $request->only([
                'type', 'priority', 'status', 'is_read', 'ticket_id',
                'search', 'sort_by', 'sort_direction', 'urgent', 'recent'
            ]);

            $perPage = min($request->get('per_page', 15), 50);

            // Use the correct method based on user type
            $userType = 'user';
            if (isset($user->role)) {
                switch ($user->role) {
                    case 'teknisi':
                        $userType = 'teknisi';
                        break;
                    default:
                        $userType = 'user';
                        break;
                }
            }

            if ($userType === 'teknisi') {
                $notifications = $this->notificationService->getTeknisiNotifications(
                    $user->nip,
                    $filters,
                    $perPage
                );
            } else {
                $notifications = $this->notificationService->getUserNotifications(
                    $user->nip,
                    $filters,
                    $perPage
                );
            }

            $formattedNotifications = collect($notifications->items())->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'type_label' => $notification->type_label,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'priority' => $notification->priority,
                    'priority_label' => $notification->priority_label,
                    'is_read' => $notification->isRead(),
                    'action_url' => $notification->action_url,
                    'created_at' => $notification->created_at,
                    'formatted_created_at' => $notification->formatted_created_at,
                    'time_elapsed' => $notification->time_elapsed,
                    'triggered_by' => $notification->getSafeTriggeredBy(),
                ];
            });

            return $this->successResponse([
                'notifications' => $formattedNotifications,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage(),
                ],
                'filters' => $filters,
            ], 'Notifications retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve notifications', [$e->getMessage()], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return $this->errorResponse('Authentication required', [], 401);
        }

        try {
            // Allow admins to mark any notification as read
            if (isset($user->role) && in_array($user->role, ['admin_helpdesk', 'admin_aplikasi'])) {
                $notification = \App\Models\Notification::findOrFail($id);
                $notification->markAsRead();
                
                return $this->successResponse([
                    'notification' => [
                        'id' => $notification->id,
                        'is_read' => true,
                        'read_at' => $notification->read_at,
                    ]
                ], 'Notification marked as read');
            }

            $result = $this->notificationService->markAsRead($id, $user->nip);

            if (!$result['success']) {
                return $this->errorResponse('Failed to mark notification as read', $result['errors'], 403);
            }

            return $this->successResponse([
                'notification' => [
                    'id' => $result['notification']->id,
                    'is_read' => $result['notification']->isRead(),
                    'read_at' => $result['notification']->read_at,
                ]
            ], 'Notification marked as read');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to mark notification as read', [$e->getMessage()], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return $this->errorResponse('Authentication required', [], 401);
        }

        try {
            $userType = $request->get('user_type', 'user');
            $result = $this->notificationService->markAllAsRead($user->nip, $userType);

            if (!$result['success']) {
                return $this->errorResponse('Failed to mark all notifications as read', $result['errors'], 404);
            }

            return $this->successResponse([
                'marked_count' => $result['marked_count']
            ], "Successfully marked {$result['marked_count']} notifications as read");

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to mark all notifications as read', [$e->getMessage()], 500);
        }
    }

    /**
     * Get notification statistics
     */
    public function stats(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return $this->errorResponse('Authentication required', [], 401);
        }

        try {
            $userType = $request->get('user_type', 'user');
            $statsResult = $this->notificationService->getNotificationStats($user->nip, $userType);
            $unreadResult = $this->notificationService->getUnreadCount($user->nip, $userType);

            if (!$statsResult['success'] || !$unreadResult['success']) {
                return $this->errorResponse('Failed to retrieve notification statistics',
                    array_merge($statsResult['errors'] ?? [], $unreadResult['errors'] ?? []), 404);
            }

            return $this->successResponse(array_merge($statsResult['stats'], [
                'unread_count' => $unreadResult['count']
            ]), 'Notification statistics retrieved');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve notification statistics', [$e->getMessage()], 500);
        }
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return $this->errorResponse('Authentication required', [], 401);
        }

        try {
            // Allow admins to delete any notification
            if (isset($user->role) && in_array($user->role, ['admin_helpdesk', 'admin_aplikasi'])) {
                $notification = \App\Models\Notification::find($id);
                if (!$notification) {
                    return $this->errorResponse('Notification not found', [], 404);
                }
                $notification->delete();
                return $this->successResponse([], 'Notification deleted successfully');
            }

            // Use service to delete for regular users, which handles authorization check
            $result = $this->notificationService->deleteNotification($id, $user->nip);

            if (!$result['success']) {
                // Determine appropriate error code
                $code = 500;
                if (isset($result['error_type'])) {
                    if ($result['error_type'] === 'not_found') $code = 404;
                    if ($result['error_type'] === 'unauthorized') $code = 403;
                }
                return $this->errorResponse($result['message'] ?? 'Failed to delete notification', [], $code);
            }

            return $this->successResponse([], 'Notification deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete notification', [$e->getMessage()], 500);
        }
    }

    /**
     * Bulk mark as read
     */
    public function bulkMarkAsRead(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return $this->errorResponse('Authentication required', [], 401);
        }

        $ids = $request->input('notification_ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->errorResponse('Invalid notification IDs', [], 422);
        }

        try {
            $count = 0;
            foreach ($ids as $id) {
                $result = $this->notificationService->markAsRead($id, $user->nip);
                if ($result['success']) {
                    $count++;
                }
            }

            return $this->successResponse(['marked_count' => $count], "Marked {$count} notifications as read");
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to bulk mark notifications', [$e->getMessage()], 500);
        }
    }

    /**
     * Get recent notifications (simple list for bell)
     */
    public function getRecent(Request $request)
    {
        // Re-use getUnread logic but maybe with different parameters if needed
        // For now, alias to getUnread as it serves the bell's purpose
        return $this->getUnread($request);
    }

    /**
     * Get notification details
     */
    public function details(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return $this->errorResponse('Authentication required', [], 401);
        }

        try {
            $notification = \App\Models\Notification::find($id);
            
            if (!$notification) {
                return $this->errorResponse('Notification not found', [], 404);
            }

            // Authorization check
            $authorized = false;
            if (isset($user->role) && in_array($user->role, ['admin_helpdesk', 'admin_aplikasi'])) {
                $authorized = true;
            } else {
                // Safe check matching service logic
                if ($notification->notifiable_id == $user->nip) {
                    $authorized = true;
                }
            }

            if (!$authorized) {
                return $this->errorResponse('Unauthorized to view this notification', [], 403);
            }

            // Auto-mark as sent if needed (fix for "Sent: N/A")
            if (!$notification->sent_at) {
                $notification->markAsSent();
            }

            // Use the model's accessor for consistent data format
            // This ensures 'triggered_by' and 'sent_at' are included correctly via getApiDataAttribute
            $formattedNotification = $notification->getApiDataAttribute();

            // Get related notifications (e.g. same ticket)
            $relatedNotifications = [];
            if (isset($notification->data['ticket_id'])) {
                $ticketId = $notification->data['ticket_id'];
                $query = \App\Models\Notification::where('data->ticket_id', $ticketId)
                    ->where('id', '!=', $id)
                    ->latest()
                    ->limit(5);
                
                // For non-admins, restrict related to their own
                if (!isset($user->role) || !in_array($user->role, ['admin_helpdesk', 'admin_aplikasi'])) {
                    $query->where('notifiable_id', $user->nip);
                }
                    
                $related = $query->get();
                
                $relatedNotifications = $related->map(function($n) {
                    return $n->getApiDataAttribute();
                });
            }

            return $this->successResponse([
                'notification' => $formattedNotification,
                'relatedNotifications' => $relatedNotifications
            ], 'Notification details retrieved');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve notification details', [$e->getMessage()], 500);
        }
    }
}