<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use App\Models\Notification;
use App\Services\NotificationService;

class SharedNotificationController extends Controller
{
    /**
     * Get managed application IDs for an Admin Aplikasi
     */
    private function getManagedAppIds($adminAplikasiNip): array
    {
        $adminAplikasi = \App\Models\AdminAplikasi::where('nip', $adminAplikasiNip)->first();
        if (!$adminAplikasi) {
            return [];
        }

        $managedAppIds = [];
        
        // Method 1: From managed_applications JSON field
        if ($adminAplikasi->managed_applications && is_array($adminAplikasi->managed_applications)) {
            $managedAppIds = $adminAplikasi->managed_applications;
        }
        
        // Method 2: Get applications where this admin is assigned as admin_aplikasi_nip
        $directAppIds = \App\Models\Aplikasi::where('admin_aplikasi_nip', $adminAplikasiNip)
            ->pluck('id')
            ->toArray();
        
        // Method 3: Get applications where this admin is assigned as backup_admin_nip
        $backupAppIds = \App\Models\Aplikasi::where('backup_admin_nip', $adminAplikasiNip)
            ->pluck('id')
            ->toArray();
        
        // Combine all sources
        $managedAppIds = array_unique(array_merge($managedAppIds, $directAppIds, $backupAppIds));

        return $managedAppIds;
    }
    
    /**
     * Get notification details for modal display.
     */
    public function getNotificationDetails($id)
    {
        $user = Auth::user();
        if (!$user && session('user_session.nip')) {
            // For unified session authentication
            $userSession = session('user_session');
            $user = (object) [
                'nip' => $userSession['nip'],
                'role' => $userSession['user_role']
            ];
        }

        $role = $user->role ?? app(\App\Services\AuthService::class)->getUserRole($user);

        // Find notification based on user role
        if ($role === 'admin_helpdesk') {
            // Admin Helpdesk can see any notification
            $notification = Notification::findOrFail($id);
        } elseif ($role === 'admin_aplikasi') {
            // Admin Aplikasi can only see notifications related to their managed applications
            $managedAppIds = $this->getManagedAppIds($user->nip);

            $adminAplikasi = \App\Models\AdminAplikasi::where('nip', $user->nip)->first();
            
            $notification = Notification::where('id', $id)
                ->where(function($q) use ($managedAppIds, $adminAplikasi) {
                    if (!empty($managedAppIds)) {
                        $q->whereHas('ticket', function($ticketQuery) use ($managedAppIds) {
                            $ticketQuery->whereIn('aplikasi_id', $managedAppIds);
                        });
                    }
                    if ($adminAplikasi) {
                        $q->orWhere(function($directQuery) use ($adminAplikasi) {
                            $directQuery->where('notifiable_type', get_class($adminAplikasi))
                                        ->where('notifiable_id', $adminAplikasi->nip);
                        });
                    }
                })
                ->firstOrFail();
        } else {
            // Regular users can only see their own notifications
            $notification = Notification::where('notifiable_type', get_class($user))
                                       ->where('notifiable_id', $user->getKey())
                                       ->where('id', $id)
                                       ->firstOrFail();
        }

        // Auto mark as sent and read if needed (using model methods)
        if (!$notification->sent_at) {
            $notification->markAsSent();
        }
        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        // Get related notifications (same type for admin, user's notifications for regular users)
        $relatedNotifications = Notification::query();

        if ($role === 'admin_helpdesk') {
            $relatedNotifications = $relatedNotifications->where('type', $notification->type)
                                                      ->where('id', '!=', $notification->id)
                                                      ->latest()
                                                      ->limit(5)
                                                      ->get();
        } elseif ($role === 'admin_aplikasi') {
            // Admin Aplikasi gets related notifications from their managed applications
            $relatedNotifications = $relatedNotifications->where('type', $notification->type)
                                                      ->where('id', '!=', $notification->id)
                                                      ->where(function($q) use ($managedAppIds, $adminAplikasi) {
                                                          if (!empty($managedAppIds)) {
                                                              $q->whereHas('ticket', function($ticketQuery) use ($managedAppIds) {
                                                                  $ticketQuery->whereIn('aplikasi_id', $managedAppIds);
                                                              });
                                                          }
                                                          if ($adminAplikasi) {
                                                              $q->orWhere(function($directQuery) use ($adminAplikasi) {
                                                                  $directQuery->where('notifiable_type', get_class($adminAplikasi))
                                                                              ->where('notifiable_id', $adminAplikasi->nip);
                                                              });
                                                          }
                                                      })
                                                      ->latest()
                                                      ->limit(5)
                                                      ->get();
        } else {
            $relatedNotifications = $relatedNotifications->where('notifiable_type', get_class($user))
                                                       ->where('notifiable_id', $user->getKey())
                                                       ->where('id', '!=', $notification->id)
                                                       ->latest()
                                                       ->limit(5)
                                                       ->get();
        }

        return response()->json([
            'notification' => $notification->getApiDataAttribute(),
            'relatedNotifications' => $relatedNotifications->map(fn($n) => $n->getApiDataAttribute()),
        ]);
    }

    /**
     * Display notifications page.
     */
    public function index()
    {
        // Support session-based authentication for admin helpdesk
        $user = Auth::user();
        if (!$user && session('user_session.nip')) {
            // For unified session authentication
            $userSession = session('user_session');
            $user = (object) [
                'nip' => $userSession['nip'],
                'role' => $userSession['user_role']
            ];
        }

        $role = $user->role ?? app(\App\Services\AuthService::class)->getUserRole($user);

        // Use NotificationService to get proper user notifications
        $notificationService = app(\App\Services\NotificationService::class);

        if ($role === 'admin_helpdesk') {
            // Admin Helpdesk sees ALL notifications
            $notifications = $notificationService->getAllNotifications(request()->all(), 20);

            // Format notifications for frontend - use items() method to access paginated data
            $formattedNotifications = collect($notifications->items())->map(function ($notification) {
                return $notification->getApiDataAttribute();
            });
            
            // Create new paginator with formatted data
            $notifications = new \Illuminate\Pagination\LengthAwarePaginator(
                $formattedNotifications,
                $notifications->total(),
                $notifications->perPage(),
                $notifications->currentPage(),
                ['path' => request()->url(), 'pageName' => 'page']
            );

            // Add statistics for admin helpdesk (all notifications)
            $statistics = [
                'total' => Notification::count(),
                'unread' => Notification::whereNull('read_at')->count(),
                'read' => Notification::whereNotNull('read_at')->count(),
                'today' => Notification::whereDate('created_at', today())->count(),
            ];

            return Inertia::render('Notifications/Index', [
                'notifications' => $notifications,
                'statistics' => $statistics,
                'user' => $user,
                'role' => $role,
                'unreadCount' => $statistics['unread'],
                'filters' => request()->all(),
            ]);
        } elseif ($role === 'admin_aplikasi') {
            // Admin Aplikasi sees only notifications related to their managed applications
            $filters = [
                'type' => request('type'),
                'is_read' => request()->has('is_read') ? request('is_read') : null,
                'sort_by' => 'created_at',
                'sort_direction' => 'desc',
            ];

            $notifications = $notificationService->getAdminAplikasiNotifications(
                $user->nip,
                $filters,
                20
            );

            // Format notifications for frontend
            $formattedNotifications = collect($notifications->items())->map(function ($notification) {
                return $notification->getApiDataAttribute();
            });
            
            // Create new paginator with formatted data
            $notifications = new \Illuminate\Pagination\LengthAwarePaginator(
                $formattedNotifications,
                $notifications->total(),
                $notifications->perPage(),
                $notifications->currentPage(),
                ['path' => request()->url(), 'pageName' => 'page']
            );

            // Get managed application IDs for statistics
            $managedAppIds = $this->getManagedAppIds($user->nip);

            // Calculate statistics for Admin Aplikasi (only their managed applications)
            $adminAplikasi = \App\Models\AdminAplikasi::where('nip', $user->nip)->first();
            $notificationQuery = Notification::query()
                ->where(function($q) use ($managedAppIds, $adminAplikasi) {
                    if (!empty($managedAppIds)) {
                        $q->whereHas('ticket', function($ticketQuery) use ($managedAppIds) {
                            $ticketQuery->whereIn('aplikasi_id', $managedAppIds);
                        });
                    }
                    if ($adminAplikasi) {
                        $q->orWhere(function($directQuery) use ($adminAplikasi) {
                            $directQuery->where('notifiable_type', get_class($adminAplikasi))
                                        ->where('notifiable_id', $adminAplikasi->nip);
                        });
                    }
                });

            $statistics = [
                'total' => (clone $notificationQuery)->count(),
                'unread' => (clone $notificationQuery)->whereNull('read_at')->count(),
                'read' => (clone $notificationQuery)->whereNotNull('read_at')->count(),
                'today' => (clone $notificationQuery)->whereDate('created_at', today())->count(),
            ];

            return Inertia::render('Notifications/Index', [
                'notifications' => $notifications,
                'statistics' => $statistics,
                'user' => $user,
                'role' => $role,
                'unreadCount' => $statistics['unread'],
                'filters' => request()->all(),
            ]);
        } else {
            // Regular users and teknisi see their own notifications
            $filters = [
                'per_page' => 20,
                'sort_by' => 'created_at',
                'sort_direction' => 'desc',
                'type' => request('type'),
                'is_read' => request()->has('is_read') ? request('is_read') : null,
            ];

            // Use appropriate method based on role
            if ($role === 'teknisi') {
                $notifications = $notificationService->getTeknisiNotifications(
                    $user->nip,
                    $filters,
                    20
                );
            } else {
                $notifications = $notificationService->getUserNotifications(
                    $user->nip,
                    $filters,
                    20
                );
            }

            // Format notifications for frontend - use items() method to access paginated data
            $formattedNotifications = collect($notifications->items())->map(function ($notification) {
                return $notification->getApiDataAttribute();
            });
            
            // Create new paginator with formatted data
            $notifications = new \Illuminate\Pagination\LengthAwarePaginator(
                $formattedNotifications,
                $notifications->total(),
                $notifications->perPage(),
                $notifications->currentPage(),
                ['path' => request()->url(), 'pageName' => 'page']
            );

            // Get unread count
            $unreadResult = $notificationService->getUnreadCount($user->nip, $role === 'teknisi' ? 'teknisi' : 'user');
            $unreadCount = $unreadResult['success'] ? $unreadResult['count'] : 0;

            return Inertia::render('Notifications/Index', [
                'notifications' => $notifications,
                'user' => $user,
                'role' => $role,
                'unreadCount' => $unreadCount,
                'filters' => request()->all(),
            ]);
        }
    }

    /**
     * Create a new notification from admin panel.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|string|in:info,warning,success,error',
            'recipient_type' => 'required|string|in:users,admin_helpdesks,admin_aplikasis,teknisis,all',
        ]);

        try {
            // Get NotificationService instance (non-static call)
            $notificationService = app(\App\Services\NotificationService::class);
            
            // Create notification data array
            $notificationData = [
                'title' => $request->title,
                'message' => $request->message,
                'type' => $request->type,
                'priority' => 'medium',
                'channel' => 'database',
                'status' => 'pending',
            ];

            // Call the service method correctly with proper parameters
            $result = $notificationService->createNotificationForRecipients(
                $notificationData,
                $request->recipient_type
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => "Notification created successfully for {$result['notifications_created']} recipients.",
                    'count' => $result['notifications_created']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create notification: ' . implode(', ', $result['errors'])
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread notifications count.
     */
    private function getUnreadCount($user): int
    {
        return Notification::where('notifiable_type', get_class($user))
                           ->where('notifiable_id', $user->getKey())
                           ->whereNull('read_at')
                           ->count();
    }

    /**
     * Mark notification as read (web route).
     */
    public function markAsReadWeb(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user && session('user_session.nip')) {
            // For unified session authentication
            $userSession = session('user_session');
            $user = (object) [
                'nip' => $userSession['nip'],
                'role' => $userSession['user_role']
            ];
        }

        $role = $user->role ?? app(\App\Services\AuthService::class)->getUserRole($user);

        if ($role === 'admin_helpdesk') {
            // Admin Helpdesk can mark any notification as read
            $notification = Notification::findOrFail($id);
            $notification->markAsRead();
        } elseif ($role === 'admin_aplikasi') {
            // Admin Aplikasi can only mark notifications related to their managed applications
            $managedAppIds = $this->getManagedAppIds($user->nip);

            $adminAplikasi = \App\Models\AdminAplikasi::where('nip', $user->nip)->first();
            
            $notification = Notification::where('id', $id)
                ->where(function($q) use ($managedAppIds, $adminAplikasi) {
                    if (!empty($managedAppIds)) {
                        $q->whereHas('ticket', function($ticketQuery) use ($managedAppIds) {
                            $ticketQuery->whereIn('aplikasi_id', $managedAppIds);
                        });
                    }
                    if ($adminAplikasi) {
                        $q->orWhere(function($directQuery) use ($adminAplikasi) {
                            $directQuery->where('notifiable_type', get_class($adminAplikasi))
                                        ->where('notifiable_id', $adminAplikasi->nip);
                        });
                    }
                })
                ->first();

            if (!$notification) {
                return redirect()->back()->with('error', 'Notification not found');
            }

            $notification->markAsRead();
        } else {
            // Regular users can only mark their own notifications
            $notification = Notification::where('notifiable_type', get_class($user))
                                        ->where('notifiable_id', $user->getKey())
                                        ->where('id', $id)
                                        ->first();

            if (!$notification) {
                return redirect()->back()->with('error', 'Notification not found');
            }

            $notification->markAsRead();
        }

        return redirect()->back()->with('success', 'Notification marked as read');
    }

    /**
     * Mark notification as read (API route).
     */
    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();

        $notification = Notification::where('notifiable_type', get_class($user))
                                     ->where('notifiable_id', $user->getKey())
                                     ->where('id', $id)
                                     ->first();

        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $notification->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request)
    {
        $user = Auth::user();
        if (!$user && session('user_session.nip')) {
            // For unified session authentication
            $userSession = session('user_session');
            $user = (object) [
                'nip' => $userSession['nip'],
                'role' => $userSession['user_role']
            ];
        }

        $role = $user->role ?? app(\App\Services\AuthService::class)->getUserRole($user);

        if ($role === 'admin_helpdesk') {
            // Admin Helpdesk can mark all notifications as read
            $unreadIds = Notification::whereNull('read_at')->pluck('id')->toArray();
            Notification::batchMarkAsRead($unreadIds);
        } elseif ($role === 'admin_aplikasi') {
            // Admin Aplikasi can only mark notifications related to their managed applications
            $managedAppIds = $this->getManagedAppIds($user->nip);

            $adminAplikasi = \App\Models\AdminAplikasi::where('nip', $user->nip)->first();
            
            $unreadIds = Notification::whereNull('read_at')
                ->where(function($q) use ($managedAppIds, $adminAplikasi) {
                    if (!empty($managedAppIds)) {
                        $q->whereHas('ticket', function($ticketQuery) use ($managedAppIds) {
                            $ticketQuery->whereIn('aplikasi_id', $managedAppIds);
                        });
                    }
                    if ($adminAplikasi) {
                        $q->orWhere(function($directQuery) use ($adminAplikasi) {
                            $directQuery->where('notifiable_type', get_class($adminAplikasi))
                                        ->where('notifiable_id', $adminAplikasi->nip);
                        });
                    }
                })
                ->pluck('id')
                ->toArray();
            Notification::batchMarkAsRead($unreadIds);
        } else {
            // Regular users can only mark their own notifications
            $unreadIds = Notification::where('notifiable_type', get_class($user))
                                   ->where('notifiable_id', $user->getKey())
                                   ->whereNull('read_at')
                                   ->pluck('id')
                                   ->toArray();
            Notification::batchMarkAsRead($unreadIds);
        }

        return redirect()->back()->with('success', 'All notifications marked as read');
    }

    /**
     * Delete notification.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user && session('user_session.nip')) {
            // For unified session authentication
            $userSession = session('user_session');
            $user = (object) [
                'nip' => $userSession['nip'],
                'role' => $userSession['user_role']
            ];
        }

        $role = $user->role ?? app(\App\Services\AuthService::class)->getUserRole($user);

        if ($role === 'admin_helpdesk') {
            // Admin Helpdesk can delete any notification
            $notification = Notification::findOrFail($id);
        } elseif ($role === 'admin_aplikasi') {
            // Admin Aplikasi can only delete notifications related to their managed applications
            $managedAppIds = $this->getManagedAppIds($user->nip);

            $adminAplikasi = \App\Models\AdminAplikasi::where('nip', $user->nip)->first();
            
            $notification = Notification::where('id', $id)
                ->where(function($q) use ($managedAppIds, $adminAplikasi) {
                    if (!empty($managedAppIds)) {
                        $q->whereHas('ticket', function($ticketQuery) use ($managedAppIds) {
                            $ticketQuery->whereIn('aplikasi_id', $managedAppIds);
                        });
                    }
                    if ($adminAplikasi) {
                        $q->orWhere(function($directQuery) use ($adminAplikasi) {
                            $directQuery->where('notifiable_type', get_class($adminAplikasi))
                                        ->where('notifiable_id', $adminAplikasi->nip);
                        });
                    }
                })
                ->first();

            if (!$notification) {
                return redirect()->back()->with('error', 'Notification not found');
            }
        } else {
            // Regular users can only delete their own notifications
            $notification = Notification::where('notifiable_type', get_class($user))
                                        ->where('notifiable_id', $user->getKey())
                                        ->where('id', $id)
                                        ->first();

            if (!$notification) {
                return redirect()->back()->with('error', 'Notification not found');
            }
        }

        $notification->delete();

        return redirect()->back()->with('success', 'Notification deleted successfully');
    }
}