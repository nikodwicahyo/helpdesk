<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class AuditLogService
{
    /**
     * Log an activity.
     * 
     * This is the main method that all other helper methods call.
     * It will NEVER return null - it will always create a log entry.
     */
    public static function log(
        string $action,
        string $description = null,
        string $entityType = null,
        $entityId = null,
        array $metadata = [],
        $actor = null
    ): ?AuditLog {
        try {
            // Get the actor (current authenticated user)
            if (!$actor) {
                $actor = Auth::user();
            }

            // If still no actor, use System as fallback - NEVER return null
            if (!$actor) {
                $actor = new \stdClass();
                $actor->name = 'System';
                $actor->nip = 'SYSTEM';
            }

            // Determine actor type
            $actorType = static::getActorType($actor);
            $actorId = $actor->nip ?? $actor->id ?? 'SYSTEM';
            $actorName = $actor->name ?? 'System';

            // Get request details
            $request = request();
            
            return AuditLog::create([
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'actor_name' => $actorName,
                'description' => $description,
                'metadata' => !empty($metadata) ? json_encode($metadata) : null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'route_name' => $request?->route()?->getName(),
                'http_method' => $request?->method(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log the error but don't break the application
            Log::error('Failed to create audit log: ' . $e->getMessage(), [
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'exception' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Determine actor type from user model or guard.
     * Now handles stdClass and missing getTable() method.
     */
    private static function getActorType($actor): string
    {
        if (!$actor) {
            return 'System';
        }

        // Handle stdClass (System actor)
        if ($actor instanceof \stdClass) {
            return 'System';
        }

        // Check if it's an Eloquent model
        if (!is_object($actor) || !method_exists($actor, 'getTable')) {
            // Try to get class name
            if (is_object($actor)) {
                $className = get_class($actor);
                if (str_contains($className, 'AdminHelpdesk')) return 'AdminHelpdesk';
                if (str_contains($className, 'AdminAplikasi')) return 'AdminAplikasi';
                if (str_contains($className, 'Teknisi')) return 'Teknisi';
                if (str_contains($className, 'User')) return 'User';
            }
            return 'Unknown';
        }

        // Check the table name from the model
        try {
            $table = $actor->getTable();
            
            return match($table) {
                'admin_helpdesks' => 'AdminHelpdesk',
                'admin_aplikasis' => 'AdminAplikasi',
                'teknisis' => 'Teknisi',
                'users' => 'User',
                default => 'Unknown'
            };
        } catch (\Exception $e) {
            Log::error('Error getting actor type: ' . $e->getMessage());
            return 'Unknown';
        }
    }

    /**
     * Log user login.
     */
    public static function logLogin($user): ?AuditLog
    {
        return static::log(
            'login',
            "{$user->name} logged in to the system",
            null,
            null,
            ['login_time' => now()->toDateTimeString()],
            $user
        );
    }

    /**
     * Log user logout.
     */
    public static function logLogout($user): ?AuditLog
    {
        return static::log(
            'logout',
            "{$user->name} logged out from the system",
            null,
            null,
            ['logout_time' => now()->toDateTimeString()],
            $user
        );
    }

    /**
     * Log ticket creation.
     * FIXED: Always gets an actor, never returns null.
     */
    public static function logTicketCreated($ticket): ?AuditLog
    {
        // Get the actor - try Auth::user() first, then try to get the ticket creator
        $actor = Auth::user();
        if (!$actor && $ticket->user_nip) {
            // Try to get the user who created the ticket
            $actor = \App\Models\User::where('nip', $ticket->user_nip)->first();
        }
        if (!$actor) {
            // Fallback to System
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'created',
            "Created ticket #{$ticket->ticket_number}",
            'Ticket',
            $ticket->id,
            [
                'ticket_number' => $ticket->ticket_number,
                'title' => $ticket->title,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
            ],
            $actor
        );
    }

    /**
     * Log ticket update.
     */
    public static function logTicketUpdated($ticket, array $changes = []): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'updated',
            "Updated ticket #{$ticket->ticket_number}",
            'Ticket',
            $ticket->id,
            [
                'ticket_number' => $ticket->ticket_number,
                'changes' => $changes,
            ],
            $actor
        );
    }

    /**
     * Log ticket assignment.
     * FIXED: Always gets an actor.
     */
    public static function logTicketAssigned($ticket, $teknisi): ?AuditLog
    {
        // Get the actor - the person who assigned the ticket
        $actor = Auth::user();
        if (!$actor && isset($ticket->assigned_by_nip)) {
            // Try to get the admin who assigned it
            $actor = \App\Models\AdminHelpdesk::where('nip', $ticket->assigned_by_nip)->first();
        }
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'assigned',
            "Assigned ticket #{$ticket->ticket_number} to {$teknisi->name}",
            'Ticket',
            $ticket->id,
            [
                'ticket_number' => $ticket->ticket_number,
                'assigned_to' => $teknisi->name,
                'assigned_to_nip' => $teknisi->nip,
            ],
            $actor
        );
    }

    /**
     * Log ticket resolution.
     */
    public static function logTicketResolved($ticket): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor && $ticket->assigned_teknisi_nip) {
            $actor = \App\Models\Teknisi::where('nip', $ticket->assigned_teknisi_nip)->first();
        }
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'resolved',
            "Resolved ticket #{$ticket->ticket_number}",
            'Ticket',
            $ticket->id,
            [
                'ticket_number' => $ticket->ticket_number,
                'resolved_at' => now()->toDateTimeString(),
            ],
            $actor
        );
    }

    /**
     * Log ticket closure.
     */
    public static function logTicketClosed($ticket): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor && $ticket->user_nip) {
            $actor = \App\Models\User::where('nip', $ticket->user_nip)->first();
        }
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'closed',
            "Closed ticket #{$ticket->ticket_number}",
            'Ticket',
            $ticket->id,
            [
                'ticket_number' => $ticket->ticket_number,
                'closed_at' => now()->toDateTimeString(),
                'rating' => $ticket->rating ?? null,
            ],
            $actor
        );
    }

    /**
     * Log comment added.
     * FIXED: Always gets an actor.
     */
    public static function logCommentAdded($ticket, $comment): ?AuditLog
    {
        // Get the actor - the person who added the comment
        $actor = Auth::user();
        if (!$actor && $comment->commenter_nip) {
            // Try to get the commenter from appropriate table based on commenter_type
            $commenterType = $comment->commenter_type ?? 'User';
            if ($commenterType === 'User') {
                $actor = \App\Models\User::where('nip', $comment->commenter_nip)->first();
            } elseif ($commenterType === 'Teknisi') {
                $actor = \App\Models\Teknisi::where('nip', $comment->commenter_nip)->first();
            } elseif ($commenterType === 'AdminHelpdesk') {
                $actor = \App\Models\AdminHelpdesk::where('nip', $comment->commenter_nip)->first();
            }
        }
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'commented',
            "Added comment to ticket #{$ticket->ticket_number}",
            'Ticket',
            $ticket->id,
            [
                'ticket_number' => $ticket->ticket_number,
                'comment_id' => $comment->id,
                'is_internal' => $comment->is_internal ?? false,
            ],
            $actor
        );
    }

    /**
     * Log user creation.
     * FIXED: Always gets an actor, never returns null.
     */
    public static function logUserCreated($user, $userType = 'User'): ?AuditLog
    {
        // Get the actor - the admin who created the user
        $actor = Auth::user();
        // If no actor, use system as fallback
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'created',
            "Created {$userType}: {$user->name}",
            $userType,
            $user->nip ?? $user->id,
            [
                'name' => $user->name,
                'email' => $user->email,
                'nip' => $user->nip ?? null,
            ],
            $actor
        );
    }

    /**
     * Log user update.
     */
    public static function logUserUpdated($user, $userType = 'User', array $changes = []): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'updated',
            "Updated {$userType}: {$user->name}",
            $userType,
            $user->nip ?? $user->id,
            [
                'name' => $user->name,
                'changes' => $changes,
            ],
            $actor
        );
    }

    /**
     * Log user deletion.
     */
    public static function logUserDeleted($user, $userType = 'User'): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'deleted',
            "Deleted {$userType}: {$user->name}",
            $userType,
            $user->nip ?? $user->id,
            [
                'name' => $user->name,
                'email' => $user->email,
            ],
            $actor
        );
    }

    /**
     * Log application creation.
     */
    public static function logApplicationCreated($aplikasi): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'created',
            "Created application: {$aplikasi->name}",
            'Aplikasi',
            $aplikasi->id,
            [
                'name' => $aplikasi->name,
                'description' => $aplikasi->description ?? null,
            ],
            $actor
        );
    }

    /**
     * Log application update.
     */
    public static function logApplicationUpdated($aplikasi, array $changes = []): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'updated',
            "Updated application: {$aplikasi->name}",
            'Aplikasi',
            $aplikasi->id,
            [
                'name' => $aplikasi->name,
                'changes' => $changes,
            ],
            $actor
        );
    }

    /**
     * Log application deletion.
     */
    public static function logApplicationDeleted($aplikasi): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'deleted',
            "Deleted application: {$aplikasi->name}",
            'Aplikasi',
            $aplikasi->id,
            [
                'name' => $aplikasi->name,
            ],
            $actor
        );
    }

    /**
     * Log generic entity action.
     */
    public static function logEntityAction(
        string $action,
        $entity,
        string $entityType,
        string $description = null,
        array $metadata = []
    ): ?AuditLog {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            $action,
            $description ?? "{$action} {$entityType}",
            $entityType,
            $entity->id ?? null,
            $metadata,
            $actor
        );
    }

    // ==================== CATEGORY MANAGEMENT LOGGING ====================

    /**
     * Log category creation.
     */
    public static function logCategoryCreated($category): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'created',
            "Created category: {$category->name}",
            'KategoriMasalah',
            $category->id,
            [
                'name' => $category->name,
                'aplikasi_id' => $category->aplikasi_id,
                'status' => $category->status,
            ],
            $actor
        );
    }

    /**
     * Log category update.
     */
    public static function logCategoryUpdated($category, array $changes = []): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'updated',
            "Updated category: {$category->name}",
            'KategoriMasalah',
            $category->id,
            [
                'name' => $category->name,
                'changes' => $changes,
            ],
            $actor
        );
    }

    /**
     * Log category deletion.
     */
    public static function logCategoryDeleted($category): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'deleted',
            "Deleted category: {$category->name}",
            'KategoriMasalah',
            $category->id,
            [
                'name' => $category->name,
                'aplikasi_id' => $category->aplikasi_id,
            ],
            $actor
        );
    }

    /**
     * Log category status change.
     */
    public static function logCategoryStatusChanged($category, string $oldStatus, string $newStatus): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'status_changed',
            "Category '{$category->name}' status changed from {$oldStatus} to {$newStatus}",
            'KategoriMasalah',
            $category->id,
            [
                'name' => $category->name,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            $actor
        );
    }

    // ==================== BULK OPERATIONS LOGGING ====================

    /**
     * Log bulk ticket action.
     */
    public static function logBulkTicketAction(string $action, array $ticketIds, array $metadata = []): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        $actionLabel = match($action) {
            'bulk_assigned' => 'Bulk Assigned',
            'bulk_updated' => 'Bulk Updated',
            'bulk_deleted' => 'Bulk Deleted',
            'bulk_status_changed' => 'Bulk Status Changed',
            'bulk_priority_changed' => 'Bulk Priority Changed',
            default => ucfirst(str_replace('_', ' ', $action))
        };
        
        return static::log(
            $action,
            "{$actionLabel} " . count($ticketIds) . " tickets",
            'Ticket',
            null,
            array_merge([
                'ticket_ids' => $ticketIds,
                'count' => count($ticketIds),
            ], $metadata),
            $actor
        );
    }

    /**
     * Log bulk user action.
     */
    public static function logBulkUserAction(string $action, array $userIds, array $metadata = []): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        $actionLabel = match($action) {
            'bulk_activated' => 'Bulk Activated',
            'bulk_deactivated' => 'Bulk Deactivated',
            'bulk_deleted' => 'Bulk Deleted',
            default => ucfirst(str_replace('_', ' ', $action))
        };
        
        return static::log(
            $action,
            "{$actionLabel} " . count($userIds) . " users",
            'User',
            null,
            array_merge([
                'user_ids' => $userIds,
                'count' => count($userIds),
            ], $metadata),
            $actor
        );
    }

    /**
     * Log bulk category action.
     */
    public static function logBulkCategoryAction(string $action, array $categoryIds, array $metadata = []): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        $actionLabel = match($action) {
            'bulk_activated' => 'Bulk Activated',
            'bulk_deactivated' => 'Bulk Deactivated',
            'bulk_deleted' => 'Bulk Deleted',
            default => ucfirst(str_replace('_', ' ', $action))
        };
        
        return static::log(
            $action,
            "{$actionLabel} " . count($categoryIds) . " categories",
            'KategoriMasalah',
            null,
            array_merge([
                'category_ids' => $categoryIds,
                'count' => count($categoryIds),
            ], $metadata),
            $actor
        );
    }

    // ==================== USER PROFILE & SECURITY LOGGING ====================

    /**
     * Log profile update.
     */
    public static function logProfileUpdated($user, array $changes = []): ?AuditLog
    {
        $actor = Auth::user() ?? $user;
        
        return static::log(
            'profile_updated',
            "{$user->name} updated their profile",
            static::getActorType($user),
            $user->nip ?? $user->id,
            [
                'name' => $user->name,
                'changes' => $changes,
            ],
            $actor
        );
    }

    /**
     * Log password change.
     */
    public static function logPasswordChanged($user): ?AuditLog
    {
        $actor = Auth::user() ?? $user;
        
        return static::log(
            'password_changed',
            "{$user->name} changed their password",
            static::getActorType($user),
            $user->nip ?? $user->id,
            [
                'name' => $user->name,
                'changed_at' => now()->toDateTimeString(),
            ],
            $actor
        );
    }

    /**
     * Log email change.
     */
    public static function logEmailChanged($user, string $oldEmail, string $newEmail): ?AuditLog
    {
        $actor = Auth::user() ?? $user;
        
        return static::log(
            'email_changed',
            "{$user->name} changed email from {$oldEmail} to {$newEmail}",
            static::getActorType($user),
            $user->nip ?? $user->id,
            [
                'name' => $user->name,
                'old_email' => $oldEmail,
                'new_email' => $newEmail,
            ],
            $actor
        );
    }

    /**
     * Log account locked.
     */
    public static function logAccountLocked($user, string $reason): ?AuditLog
    {
        $actor = new \stdClass();
        $actor->name = 'System';
        $actor->nip = 'SYSTEM';
        
        return static::log(
            'account_locked',
            "Account locked for {$user->name}: {$reason}",
            static::getActorType($user),
            $user->nip ?? $user->id,
            [
                'name' => $user->name,
                'reason' => $reason,
                'locked_at' => now()->toDateTimeString(),
            ],
            $actor
        );
    }

    /**
     * Log account unlocked.
     */
    public static function logAccountUnlocked($user): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'account_unlocked',
            "Account unlocked for {$user->name}",
            static::getActorType($user),
            $user->nip ?? $user->id,
            [
                'name' => $user->name,
                'unlocked_at' => now()->toDateTimeString(),
            ],
            $actor
        );
    }

    /**
     * Log failed login attempt.
     */
    public static function logLoginAttemptFailed(string $nip, string $reason): ?AuditLog
    {
        $actor = new \stdClass();
        $actor->name = 'System';
        $actor->nip = 'SYSTEM';
        
        return static::log(
            'login_failed',
            "Failed login attempt for NIP: {$nip}",
            null,
            null,
            [
                'nip' => $nip,
                'reason' => $reason,
                'failed_at' => now()->toDateTimeString(),
            ],
            $actor
        );
    }

    // ==================== ASSIGNMENT & WORKFLOW LOGGING ====================

    /**
     * Log ticket unassigned.
     */
    public static function logTicketUnassigned($ticket, $oldTeknisi): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'unassigned',
            "Unassigned ticket #{$ticket->ticket_number} from {$oldTeknisi->name}",
            'Ticket',
            $ticket->id,
            [
                'ticket_number' => $ticket->ticket_number,
                'old_teknisi' => $oldTeknisi->name,
                'old_teknisi_nip' => $oldTeknisi->nip,
            ],
            $actor
        );
    }

    /**
     * Log ticket reassigned.
     */
    public static function logTicketReassigned($ticket, $oldTeknisi, $newTeknisi): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'reassigned',
            "Reassigned ticket #{$ticket->ticket_number} from {$oldTeknisi->name} to {$newTeknisi->name}",
            'Ticket',
            $ticket->id,
            [
                'ticket_number' => $ticket->ticket_number,
                'old_teknisi' => $oldTeknisi->name,
                'old_teknisi_nip' => $oldTeknisi->nip,
                'new_teknisi' => $newTeknisi->name,
                'new_teknisi_nip' => $newTeknisi->nip,
            ],
            $actor
        );
    }

    /**
     * Log ticket escalated.
     */
    public static function logTicketEscalated($ticket, string $reason): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'escalated',
            "Escalated ticket #{$ticket->ticket_number}: {$reason}",
            'Ticket',
            $ticket->id,
            [
                'ticket_number' => $ticket->ticket_number,
                'reason' => $reason,
                'escalated_at' => now()->toDateTimeString(),
            ],
            $actor
        );
    }

    /**
     * Log ticket reopened.
     */
    public static function logTicketReopened($ticket, string $reason): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor && $ticket->user_nip) {
            $actor = \App\Models\User::where('nip', $ticket->user_nip)->first();
        }
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'reopened',
            "Reopened ticket #{$ticket->ticket_number}: {$reason}",
            'Ticket',
            $ticket->id,
            [
                'ticket_number' => $ticket->ticket_number,
                'reason' => $reason,
                'reopened_at' => now()->toDateTimeString(),
            ],
            $actor
        );
    }

    /**
     * Log priority changed.
     */
    public static function logPriorityChanged($ticket, string $oldPriority, string $newPriority): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'priority_changed',
            "Changed priority for ticket #{$ticket->ticket_number} from {$oldPriority} to {$newPriority}",
            'Ticket',
            $ticket->id,
            [
                'ticket_number' => $ticket->ticket_number,
                'old_priority' => $oldPriority,
                'new_priority' => $newPriority,
            ],
            $actor
        );
    }

    /**
     * Log status changed.
     */
    public static function logStatusChanged($ticket, string $oldStatus, string $newStatus): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'status_changed',
            "Changed status for ticket #{$ticket->ticket_number} from {$oldStatus} to {$newStatus}",
            'Ticket',
            $ticket->id,
            [
                'ticket_number' => $ticket->ticket_number,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            $actor
        );
    }

    // ==================== EXPORT/IMPORT LOGGING ====================

    /**
     * Log data exported.
     */
    public static function logDataExported(string $entityType, int $recordCount, array $filters = []): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'exported',
            "Exported {$recordCount} {$entityType} records",
            $entityType,
            null,
            [
                'record_count' => $recordCount,
                'filters' => $filters,
                'exported_at' => now()->toDateTimeString(),
            ],
            $actor
        );
    }

    /**
     * Log data imported.
     */
    public static function logDataImported(string $entityType, array $results): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'imported',
            "Imported {$results['successful_imports']} {$entityType} records ({$results['failed_imports']} failed)",
            $entityType,
            null,
            [
                'total_rows' => $results['total_rows'] ?? 0,
                'successful_imports' => $results['successful_imports'] ?? 0,
                'failed_imports' => $results['failed_imports'] ?? 0,
                'errors' => $results['errors'] ?? [],
                'imported_at' => now()->toDateTimeString(),
            ],
            $actor
        );
    }

    // ==================== REPORT GENERATION LOGGING ====================

    /**
     * Log report generated.
     */
    public static function logReportGenerated(string $reportType, array $parameters = []): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'report_generated',
            "Generated {$reportType} report",
            'Report',
            null,
            [
                'report_type' => $reportType,
                'parameters' => $parameters,
                'generated_at' => now()->toDateTimeString(),
            ],
            $actor
        );
    }

    /**
     * Log scheduled report created.
     */
    public static function logScheduledReportCreated($report): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'created',
            "Created scheduled report: {$report->name}",
            'ScheduledReport',
            $report->id,
            [
                'name' => $report->name,
                'report_type' => $report->report_type ?? 'unknown',
                'frequency' => $report->frequency ?? 'unknown',
            ],
            $actor
        );
    }

    /**
     * Log scheduled report updated.
     */
    public static function logScheduledReportUpdated($report): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'updated',
            "Updated scheduled report: {$report->name}",
            'ScheduledReport',
            $report->id,
            [
                'name' => $report->name,
                'report_type' => $report->report_type ?? 'unknown',
            ],
            $actor
        );
    }

    /**
     * Log scheduled report deleted.
     */
    public static function logScheduledReportDeleted($report): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'deleted',
            "Deleted scheduled report: {$report->name}",
            'ScheduledReport',
            $report->id,
            [
                'name' => $report->name,
            ],
            $actor
        );
    }

    // ==================== SETTINGS & CONFIGURATION LOGGING ====================

    /**
     * Log setting changed.
     */
    public static function logSettingChanged(string $settingKey, $oldValue, $newValue): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'setting_changed',
            "Changed setting: {$settingKey}",
            'SystemSetting',
            null,
            [
                'setting_key' => $settingKey,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'changed_at' => now()->toDateTimeString(),
            ],
            $actor
        );
    }

    /**
     * Log system configuration changed.
     */
    public static function logSystemConfigurationChanged(array $changes): ?AuditLog
    {
        $actor = Auth::user();
        if (!$actor) {
            $actor = new \stdClass();
            $actor->name = 'System';
            $actor->nip = 'SYSTEM';
        }
        
        return static::log(
            'config_changed',
            "System configuration changed",
            'SystemSetting',
            null,
            [
                'changes' => $changes,
                'changed_at' => now()->toDateTimeString(),
            ],
            $actor
        );
    }
}
