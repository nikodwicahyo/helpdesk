<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UserObserver
{
    protected $auditLogService;

    /**
     * Temporary storage for old values during update
     */
    private $tempOldValues = [];

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }
    /**
     * Handle the User "creating" event.
     * Accepts User, AdminHelpdesk, AdminAplikasi, or Teknisi models
     */
    public function creating($user): void
    {
        Log::info("Creating new user", [
            'nip' => $user->nip,
            'name' => $user->name,
            'email' => $user->email ?? null,
        ]);
    }

    /**
     * Handle the User "created" event.
     * Accepts User, AdminHelpdesk, AdminAplikasi, or Teknisi models
     */
    public function created($user): void
    {
        try {
            // Log user creation in audit log
            $entityType = get_class($user);
            $entityType = class_basename($entityType); // Get just the class name without namespace
            
            Log::info('UserObserver::created fired', [
                'entity_type' => $entityType,
                'nip' => $user->nip,
                'name' => $user->name,
                'email' => $user->email,
            ]);
            
            // Always log user creation - remove duplicate check as it can prevent legitimate logs
            // The audit log table has unique constraints to prevent actual duplicates
            $auditLog = $this->auditLogService->logUserCreated($user, $entityType);
            
            if ($auditLog) {
                Log::info('UserObserver: User creation logged to audit log', [
                    'entity_type' => $entityType,
                    'nip' => $user->nip,
                    'audit_log_id' => $auditLog->id,
                ]);
            } else {
                Log::warning('UserObserver: Failed to create audit log entry', [
                    'entity_type' => $entityType,
                    'nip' => $user->nip,
                ]);
            }

            // Notify admins about new user registration
            $this->notifyAdminsOfNewUser($user);

            // Create welcome notification for user
            $this->createWelcomeNotification($user);

            // Log user creation success
            Log::info("UserObserver: User creation process completed", [
                'nip' => $user->nip,
                'name' => $user->name,
                'entity_type' => $entityType,
            ]);

        } catch (\Exception $e) {
            Log::error("Error in UserObserver::created", [
                'nip' => $user->nip,
                'entity_type' => class_basename(get_class($user)),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle the User "updating" event.
     * Accepts User, AdminHelpdesk, AdminAplikasi, or Teknisi models
     */
    public function updating($user): void
    {
        // Store old values for comparison in memory only (not as model attributes)
        // This prevents trying to save non-existent fields to database
        // Only store values that exist on the model
        $this->tempOldValues = [];
        
        if ($user->hasAttribute('status')) {
            $this->tempOldValues['status'] = $user->getOriginal('status');
        }
        if ($user->hasAttribute('department')) {
            $this->tempOldValues['department'] = $user->getOriginal('department');
        }
        if ($user->hasAttribute('position')) {
            $this->tempOldValues['position'] = $user->getOriginal('position');
        }
        if ($user->hasAttribute('email')) {
            $this->tempOldValues['email'] = $user->getOriginal('email');
        }
    }

    /**
     * Handle the User "updated" event.
     * Accepts User, AdminHelpdesk, AdminAplikasi, or Teknisi models
     */
    public function updated($user): void
    {
        try {
            $changes = [];

            // Check status changes
            if ($user->wasChanged('status')) {
                $this->handleStatusChange($user, $changes);
            }

            // Check department changes
            if ($user->wasChanged('department')) {
                $this->handleDepartmentChange($user, $changes);
            }

            // Check position changes
            if ($user->wasChanged('position')) {
                $this->handlePositionChange($user, $changes);
            }

            // Check email changes
            if ($user->wasChanged('email')) {
                $this->handleEmailChange($user, $changes);
            }

            // Check profile updates
            if ($user->wasChanged(['name', 'phone', 'profile_photo'])) {
                $this->handleProfileUpdate($user, $changes);
            }

            // Check password changes
            if ($user->wasChanged('password')) {
                $this->handlePasswordChange($user, $changes);
            }

            // Check last login updates
            if ($user->wasChanged('last_login_at')) {
                $this->handleLoginActivity($user, $changes);
            }

            // Check account lockout
            if ($user->wasChanged('locked_until')) {
                $this->handleAccountLockout($user, $changes);
            }

            // Log significant changes
            if (!empty($changes)) {
                $this->logUserChanges($user, $changes);
                
                // Log user update in audit log
                $this->auditLogService->logUserUpdated($user, 'User', [
                    'changes' => $changes,
                    'changed_fields' => array_column($changes, 'type'),
                ]);
            }

            Log::info("User updated", [
                'nip' => $user->nip,
                'changes' => $changes,
            ]);

        } catch (\Exception $e) {
            Log::error("Error in UserObserver::updated", [
                'nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        } finally {
            // Clear temporary values to prevent memory issues
            $this->tempOldValues = [];
        }
    }

    /**
     * Handle the User "deleting" event.
     * Accepts User, AdminHelpdesk, AdminAplikasi, or Teknisi models
     */
    public function deleting($user): void
    {
        try {
            // Check for active tickets before deletion - only for users with tickets relationship
            $activeTickets = 0;
            if (method_exists($user, 'tickets')) {
                // Note: active() scope may not exist, so use where clause directly
                $activeTickets = $user->tickets()
                    ->whereIn('status', ['open', 'in_progress', 'pending'])
                    ->count();
            }

            if ($activeTickets > 0) {
                Log::warning("Attempting to delete user with active tickets", [
                    'nip' => $user->nip,
                    'active_tickets' => $activeTickets,
                ]);

                // Prevent deletion if user has active tickets
                throw new \Exception("Cannot delete user with {$activeTickets} active tickets");
            }

            Log::warning("User being deleted", [
                'nip' => $user->nip,
                'name' => $user->name,
            ]);

        } catch (\Exception $e) {
            Log::error("Error in UserObserver::deleting", [
                'nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw to prevent deletion
        }
    }

    /**
     * Handle the User "deleted" event.
     * Accepts User, AdminHelpdesk, AdminAplikasi, or Teknisi models
     */
    public function deleted($user): void
    {
        try {
            // Log user deletion in audit log
            $this->auditLogService->logUserDeleted($user, 'User');

            // Notify admins about user deletion
            $this->notifyAdminsOfUserDeletion($user);

            // Log user deletion - only count tickets if user has tickets relationship
            $totalTickets = 0;
            if (method_exists($user, 'tickets')) {
                $totalTickets = $user->tickets()->count();
            }

            Log::info("User deleted", [
                'nip' => $user->nip,
                'name' => $user->name,
                'total_tickets' => $totalTickets,
            ]);

        } catch (\Exception $e) {
            Log::error("Error in UserObserver::deleted", [
                'nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle status change
     */
    private function handleStatusChange($user, array &$changes): void
    {
        $oldStatus = $this->tempOldValues['status'] ?? null;
        $newStatus = $user->status;

        $changes[] = [
            'type' => 'status_change',
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
        ];

        // Create notification based on status change
        if ($oldStatus !== $newStatus) {
            $this->notifyStatusChange($user, $oldStatus, $newStatus);
        }

        // Handle specific status transitions
        switch ($newStatus) {
            case 'active':
                $this->handleUserActivated($user);
                break;
            case 'inactive':
                $this->handleUserDeactivated($user);
                break;
        }
    }

    /**
     * Handle department change
     */
    private function handleDepartmentChange($user, array &$changes): void
    {
        $oldDepartment = $this->tempOldValues['department'] ?? null;
        $newDepartment = $user->department;

        $changes[] = [
            'type' => 'department_change',
            'old_value' => $oldDepartment,
            'new_value' => $newDepartment,
        ];

        // Notify user of department change
        $this->notifyDepartmentChange($user, $oldDepartment, $newDepartment);
    }

    /**
     * Handle position change
     */
    private function handlePositionChange($user, array &$changes): void
    {
        $oldPosition = $this->tempOldValues['position'] ?? null;
        $newPosition = $user->position;

        $changes[] = [
            'type' => 'position_change',
            'old_value' => $oldPosition,
            'new_value' => $newPosition,
        ];

        // Notify user of position change
        $this->notifyPositionChange($user, $oldPosition, $newPosition);
    }

    /**
     * Handle email change
     */
    private function handleEmailChange($user, array &$changes): void
    {
        $oldEmail = $this->tempOldValues['email'] ?? null;
        $newEmail = $user->email;

        $changes[] = [
            'type' => 'email_change',
            'old_value' => $oldEmail,
            'new_value' => $newEmail,
        ];

        // Notify user of email change
        $this->notifyEmailChange($user, $oldEmail, $newEmail);
    }

    /**
     * Handle profile update
     */
    private function handleProfileUpdate($user, array &$changes): void
    {
        $changes[] = [
            'type' => 'profile_update',
            'old_value' => null,
            'new_value' => 'Profile information updated',
        ];

        // Notify user of profile update
        $this->notifyProfileUpdate($user);
    }

    /**
     * Handle password change
     */
    private function handlePasswordChange($user, array &$changes): void
    {
        $changes[] = [
            'type' => 'password_change',
            'old_value' => 'hidden',
            'new_value' => 'changed',
        ];

        // Notify user of password change
        $this->notifyPasswordChange($user);
    }

    /**
     * Handle login activity
     */
    private function handleLoginActivity($user, array &$changes): void
    {
        $changes[] = [
            'type' => 'login_activity',
            'old_value' => $user->getOriginal('last_login_at'),
            'new_value' => $user->last_login_at,
        ];

        // Check for suspicious login patterns
        $this->checkSuspiciousLogin($user);
    }

    /**
     * Handle account lockout
     */
    private function handleAccountLockout($user, array &$changes): void
    {
        $changes[] = [
            'type' => 'account_lockout',
            'old_value' => $user->getOriginal('locked_until'),
            'new_value' => $user->locked_until,
        ];

        // Notify user and admins of lockout
        $this->notifyAccountLockout($user);
    }

    /**
     * Handle user activated
     */
    private function handleUserActivated($user): void
    {
        // Create welcome back notification
        $this->createCustomNotification(
            $user,
            'Account Activated',
            'Your account has been activated. You can now access the helpdesk system.',
            Notification::PRIORITY_MEDIUM,
            '/dashboard',
            [
                'activation_date' => Carbon::now(),
                'account_status' => 'active',
            ]
        );
    }

    /**
     * Handle user deactivated
     */
    private function handleUserDeactivated($user): void
    {
        // Notify user of deactivation
        $this->createCustomNotification(
            $user,
            'Account Deactivated',
            'Your account has been deactivated. Please contact your administrator if you need access.',
            Notification::PRIORITY_HIGH,
            '/login',
            [
                'deactivation_date' => Carbon::now(),
                'account_status' => 'inactive',
            ]
        );
    }

    /**
     * Log user changes
     */
    private function logUserChanges($user, array $changes): void
    {
        foreach ($changes as $change) {
            Log::info("User change detected", [
                'nip' => $user->nip,
                'change_type' => $change['type'],
                'old_value' => $change['old_value'],
                'new_value' => $change['new_value'],
            ]);
        }
    }

    // ==================== NOTIFICATION METHODS ====================

    /**
     * Notify admins of new user
     */
    private function notifyAdminsOfNewUser($user): void
    {
        try {
            // Notify admin helpdesks
            $adminHelpdesks = AdminHelpdesk::active()->get();
            foreach ($adminHelpdesks as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'New User Registered',
                    "New user {$user->name} ({$user->nip}) has registered in the system",
                    Notification::PRIORITY_MEDIUM,
                    '/admin/users',
                    [
                        'user_nip' => $user->nip,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'user_department' => $user->department,
                        'registration_date' => Carbon::now(),
                    ]
                );
            }

            Log::info("New user notifications sent to admins", [
                'user_nip' => $user->nip,
                'admin_count' => $adminHelpdesks->count(),
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending new user notifications to admins", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create welcome notification for user
     */
    private function createWelcomeNotification($user): void
    {
        try {
            $this->createCustomNotification(
                $user,
                'Welcome to Helpdesk System',
                "Welcome {$user->name}! Your account has been created successfully. You can now create and track support tickets.",
                Notification::PRIORITY_HIGH,
                '/dashboard',
                [
                    'user_nip' => $user->nip,
                    'welcome_date' => Carbon::now(),
                    'getting_started_url' => '/help/getting-started',
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error creating welcome notification", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify status change
     */
    private function notifyStatusChange($user, string $oldStatus, string $newStatus): void
    {
        try {
            // Notify admins of status change
            $adminHelpdesks = AdminHelpdesk::active()->get();
            foreach ($adminHelpdesks as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'User Status Changed',
                    "User {$user->name} ({$user->nip}) status changed from {$oldStatus} to {$newStatus}",
                    Notification::PRIORITY_MEDIUM,
                    '/admin/users',
                    [
                        'user_nip' => $user->nip,
                        'user_name' => $user->name,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending status change notifications", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify department change
     */
    private function notifyDepartmentChange($user, ?string $oldDepartment, string $newDepartment): void
    {
        try {
            $this->createCustomNotification(
                $user,
                'Department Updated',
                "Your department has been updated from {$oldDepartment} to {$newDepartment}",
                Notification::PRIORITY_MEDIUM,
                '/profile',
                [
                    'old_department' => $oldDepartment,
                    'new_department' => $newDepartment,
                    'updated_at' => Carbon::now(),
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error sending department change notification", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify position change
     */
    private function notifyPositionChange($user, ?string $oldPosition, string $newPosition): void
    {
        try {
            $this->createCustomNotification(
                $user,
                'Position Updated',
                "Your position has been updated from {$oldPosition} to {$newPosition}",
                Notification::PRIORITY_MEDIUM,
                '/profile',
                [
                    'old_position' => $oldPosition,
                    'new_position' => $newPosition,
                    'updated_at' => Carbon::now(),
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error sending position change notification", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify email change
     */
    private function notifyEmailChange($user, string $oldEmail, string $newEmail): void
    {
        try {
            $this->createCustomNotification(
                $user,
                'Email Address Updated',
                "Your email address has been changed from {$oldEmail} to {$newEmail}",
                Notification::PRIORITY_HIGH,
                '/profile',
                [
                    'old_email' => $oldEmail,
                    'new_email' => $newEmail,
                    'updated_at' => Carbon::now(),
                    'verification_required' => true,
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error sending email change notification", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify profile update
     */
    private function notifyProfileUpdate($user): void
    {
        try {
            $this->createCustomNotification(
                $user,
                'Profile Updated',
                'Your profile information has been updated successfully',
                Notification::PRIORITY_LOW,
                '/profile',
                [
                    'updated_at' => Carbon::now(),
                    'profile_completeness' => method_exists($user, 'profile_completeness') ? $user->profile_completeness : null,
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error sending profile update notification", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify password change
     */
    private function notifyPasswordChange($user): void
    {
        try {
            $this->createCustomNotification(
                $user,
                'Password Changed',
                'Your password has been changed successfully',
                Notification::PRIORITY_HIGH,
                '/profile',
                [
                    'changed_at' => Carbon::now(),
                    'security_tip' => 'If you did not make this change, please contact support immediately',
                ]
            );

        } catch (\Exception $e) {
            Log::error("Error sending password change notification", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify account lockout
     */
    private function notifyAccountLockout($user): void
    {
        try {
            // Notify user
            $this->createCustomNotification(
                $user,
                'Account Temporarily Locked',
                'Your account has been locked due to multiple failed login attempts. It will be unlocked automatically after 30 minutes.',
                Notification::PRIORITY_URGENT,
                '/login',
                [
                    'locked_at' => Carbon::now(),
                    'locked_until' => $user->locked_until,
                    'unlock_time' => $user->locked_until?->diffForHumans(),
                ]
            );

            // Notify admins
            $adminHelpdesks = AdminHelpdesk::active()->get();
            foreach ($adminHelpdesks as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'User Account Locked',
                    "User {$user->name} ({$user->nip}) account has been locked due to security reasons",
                    Notification::PRIORITY_HIGH,
                    '/admin/users',
                    [
                        'user_nip' => $user->nip,
                        'user_name' => $user->name,
                        'locked_at' => Carbon::now(),
                        'locked_until' => $user->locked_until,
                        'login_attempts' => $user->login_attempts ?? 0,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending account lockout notifications", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify admins of user deletion
     */
    private function notifyAdminsOfUserDeletion($user): void
    {
        try {
            // Count tickets only if user has tickets relationship
            $totalTickets = 0;
            if (method_exists($user, 'tickets')) {
                $totalTickets = $user->tickets()->count();
            }

            $adminHelpdesks = AdminHelpdesk::active()->get();
            foreach ($adminHelpdesks as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'User Deleted',
                    "User {$user->name} ({$user->nip}) has been deleted from the system",
                    Notification::PRIORITY_HIGH,
                    '/admin/users',
                    [
                        'user_nip' => $user->nip,
                        'user_name' => $user->name,
                        'deleted_at' => Carbon::now(),
                        'total_tickets' => $totalTickets,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending user deletion notifications", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create custom notification
     */
    private function createCustomNotification($notifiable, string $title, string $message, string $priority, string $actionUrl, array $data = []): void
    {
        Notification::create([
            'type' => Notification::TYPE_SYSTEM_ANNOUNCEMENT,
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->getKey(),
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'channel' => Notification::CHANNEL_DATABASE,
            'status' => Notification::STATUS_PENDING,
            'action_url' => $actionUrl,
            'data' => $data,
        ]);
    }

    // ==================== SECURITY METHODS ====================

    /**
     * Check for suspicious login patterns
     */
    private function checkSuspiciousLogin($user): void
    {
        try {
            // Only check tickets for regular users who have the tickets relationship
            $recentLogins = 0;

            if (method_exists($user, 'tickets')) {
                $recentLogins = $user->tickets()
                    ->where('created_at', '>=', Carbon::now()->subMinutes(5))
                    ->count();
            }

            if ($recentLogins > 3) {
                Log::warning("Suspicious login activity detected", [
                    'user_nip' => $user->nip,
                    'recent_tickets' => $recentLogins,
                    'time_window' => '5 minutes',
                ]);

                // Notify admins of suspicious activity
                $this->notifySuspiciousActivity($user, $recentLogins);
            }

        } catch (\Exception $e) {
            Log::error("Error checking suspicious login", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify suspicious activity
     */
    private function notifySuspiciousActivity($user, int $activityCount): void
    {
        try {
            $adminHelpdesks = AdminHelpdesk::active()->get();
            foreach ($adminHelpdesks as $admin) {
                $this->createCustomNotification(
                    $admin,
                    'Suspicious User Activity',
                    "User {$user->name} ({$user->nip}) has unusual activity: {$activityCount} tickets created in 5 minutes",
                    Notification::PRIORITY_HIGH,
                    '/admin/users',
                    [
                        'user_nip' => $user->nip,
                        'user_name' => $user->name,
                        'activity_count' => $activityCount,
                        'time_window' => '5 minutes',
                        'detected_at' => Carbon::now(),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error("Error sending suspicious activity notifications", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ==================== ACTIVITY TRACKING ====================

    /**
     * Track user activity patterns
     */
    private function trackUserActivity($user): void
    {
        try {
            // Get user's activity statistics - only include ticket stats for users with tickets relationship
            $stats = [
                'total_tickets' => method_exists($user, 'tickets') ? $user->tickets()->count() : 0,
                'active_tickets' => method_exists($user, 'active_tickets_count') ? $user->active_tickets_count : 0,
                'resolved_tickets' => method_exists($user, 'resolved_tickets_count') ? $user->resolved_tickets_count : 0,
                'last_activity' => $user->last_login_at,
                'department' => $user->department,
                'position' => $user->position,
            ];

            // Log activity patterns for analysis
            Log::info("User activity tracked", [
                'user_nip' => $user->nip,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error("Error tracking user activity", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check user engagement level
     */
    private function checkUserEngagement($user): void
    {
        try {
            $daysSinceLastLogin = $user->last_login_at ?
                Carbon::now()->diffInDays($user->last_login_at) : null;

            // Check for inactive users (no login for 30+ days)
            if ($daysSinceLastLogin && $daysSinceLastLogin >= 30) {
                $this->handleInactiveUser($user, $daysSinceLastLogin);
            }

            // Check for highly active users (10+ tickets in 7 days) - only for users with tickets relationship
            $recentTickets = 0;
            if (method_exists($user, 'tickets')) {
                $recentTickets = $user->tickets()
                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                    ->count();
            }

            if ($recentTickets >= 10) {
                $this->handleHighlyActiveUser($user, $recentTickets);
            }

        } catch (\Exception $e) {
            Log::error("Error checking user engagement", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle inactive user
     */
    private function handleInactiveUser($user, int $daysInactive): void
    {
        try {
            // Send re-engagement notification
            $this->createCustomNotification(
                $user,
                'We Miss You!',
                "It's been {$daysInactive} days since your last login. Don't forget to check your tickets!",
                Notification::PRIORITY_MEDIUM,
                '/dashboard',
                [
                    'days_inactive' => $daysInactive,
                    'last_login' => $user->last_login_at,
                    'active_tickets' => method_exists($user, 'active_tickets_count') ? $user->active_tickets_count : 0,
                ]
            );

            Log::info("Inactive user notification sent", [
                'user_nip' => $user->nip,
                'days_inactive' => $daysInactive,
            ]);

        } catch (\Exception $e) {
            Log::error("Error handling inactive user", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle highly active user
     */
    private function handleHighlyActiveUser($user, int $recentTickets): void
    {
        try {
            // Send appreciation notification
            $this->createCustomNotification(
                $user,
                'Thank You for Being Active!',
                "You've created {$recentTickets} tickets this week. Thank you for using our helpdesk system!",
                Notification::PRIORITY_LOW,
                '/dashboard',
                [
                    'recent_tickets' => $recentTickets,
                    'week_start' => Carbon::now()->startOfWeek(),
                    'week_end' => Carbon::now()->endOfWeek(),
                ]
            );

            Log::info("Highly active user notification sent", [
                'user_nip' => $user->nip,
                'recent_tickets' => $recentTickets,
            ]);

        } catch (\Exception $e) {
            Log::error("Error handling highly active user", [
                'user_nip' => $user->nip,
                'error' => $e->getMessage(),
            ]);
        }
    }
}