<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    // Action constants for type safety and IDE autocomplete
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';
    const ACTION_ASSIGNED = 'assigned';
    const ACTION_UNASSIGNED = 'unassigned';
    const ACTION_REASSIGNED = 'reassigned';
    const ACTION_RESOLVED = 'resolved';
    const ACTION_CLOSED = 'closed';
    const ACTION_REOPENED = 'reopened';
    const ACTION_ESCALATED = 'escalated';
    const ACTION_COMMENTED = 'commented';
    const ACTION_STATUS_CHANGED = 'status_changed';
    const ACTION_PRIORITY_CHANGED = 'priority_changed';
    const ACTION_BULK_ASSIGNED = 'bulk_assigned';
    const ACTION_BULK_UPDATED = 'bulk_updated';
    const ACTION_BULK_DELETED = 'bulk_deleted';
    const ACTION_BULK_ACTIVATED = 'bulk_activated';
    const ACTION_BULK_DEACTIVATED = 'bulk_deactivated';
    const ACTION_PROFILE_UPDATED = 'profile_updated';
    const ACTION_PASSWORD_CHANGED = 'password_changed';
    const ACTION_EMAIL_CHANGED = 'email_changed';
    const ACTION_ACCOUNT_LOCKED = 'account_locked';
    const ACTION_ACCOUNT_UNLOCKED = 'account_unlocked';
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_LOGIN_FAILED = 'login_failed';
    const ACTION_EXPORTED = 'exported';
    const ACTION_IMPORTED = 'imported';
    const ACTION_REPORT_GENERATED = 'report_generated';
    const ACTION_SETTING_CHANGED = 'setting_changed';
    const ACTION_CONFIG_CHANGED = 'config_changed';

    protected $fillable = [
        'action',
        'entity_type',
        'entity_id',
        'actor_type',
        'actor_id',
        'actor_name',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
        'route_name',
        'http_method',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false; // Only created_at is used

    /**
     * Get the entity that was logged.
     */
    public function entity(): MorphTo
    {
        return $this->morphTo('entity');
    }

    /**
     * Get the actor who performed the action.
     */
    public function actor(): MorphTo
    {
        return $this->morphTo('actor');
    }

    /**
     * Scope to filter by action.
     */
    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by entity type.
     */
    public function scopeEntityType($query, $type)
    {
        return $query->where('entity_type', $type);
    }

    /**
     * Scope to filter by actor type.
     */
    public function scopeActorType($query, $type)
    {
        return $query->where('actor_type', $type);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $start, $end = null)
    {
        $query->whereDate('created_at', '>=', $start);

        if ($end) {
            $query->whereDate('created_at', '<=', $end);
        }

        return $query;
    }

    /**
     * Scope to get recent logs.
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get logs for a specific entity.
     */
    public function scopeForEntity($query, $entity)
    {
        if (!is_object($entity) || !method_exists($entity, 'getMorphClass')) {
            return $query;
        }

        return $query->where('entity_type', $entity->getMorphClass())
                   ->where('entity_id', $entity->getKey());
    }

    /**
     * Scope to get logs by a specific actor.
     */
    public function scopeByActor($query, $actor)
    {
        if (!is_object($actor) || !method_exists($actor, 'getMorphClass')) {
            return $query;
        }

        return $query->where('actor_type', $actor->getMorphClass())
                   ->where('actor_id', $actor->getKey());
    }

    /**
     * Create a new audit log entry.
     */
    public static function createLog($action, $entity, $actor = null, array $metadata = [])
    {
        $entityType = null;
        $entityId = null;

        if (is_object($entity)) {
            $entityType = $entity->getMorphClass();
            $entityId = $entity->getKey();
        }

        $actorType = null;
        $actorId = null;
        $actorName = 'System';

        if ($actor) {
            $actorType = $actor->getMorphClass();
            $actorId = $actor->getKey();
            $actorName = $actor->name ?? $actor->username ?? 'Unknown';
        }

        $request = request();
        $ipAddress = $request?->ip();
        $userAgent = $request?->userAgent();
        $routeName = $request?->route()?->getName();
        $httpMethod = $request?->method();

        return static::create([
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_name' => $actorName,
            'metadata' => !empty($metadata) ? $metadata : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'route_name' => $routeName,
            'http_method' => $httpMethod,
            'created_at' => now(),
        ]);
    }

    /**
     * Get entity history.
     */
    public static function getEntityHistory($entity)
    {
        if (!is_object($entity)) {
            return collect([]);
        }

        return static::forEntity($entity)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get user activity.
     */
    public static function getUserActivity($actor, $days = 30)
    {
        if (!is_object($actor)) {
            return collect([]);
        }

        return static::byActor($actor)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get formatted created at timestamp.
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    /**
     * Get formatted action label.
     */
    public function getActionLabelAttribute()
    {
        $actionMap = [
            self::ACTION_CREATED => 'Created',
            self::ACTION_UPDATED => 'Updated',
            self::ACTION_DELETED => 'Deleted',
            self::ACTION_ASSIGNED => 'Assigned',
            self::ACTION_UNASSIGNED => 'Unassigned',
            self::ACTION_REASSIGNED => 'Reassigned',
            self::ACTION_RESOLVED => 'Resolved',
            self::ACTION_CLOSED => 'Closed',
            self::ACTION_REOPENED => 'Reopened',
            self::ACTION_ESCALATED => 'Escalated',
            self::ACTION_COMMENTED => 'Commented',
            self::ACTION_STATUS_CHANGED => 'Status Changed',
            self::ACTION_PRIORITY_CHANGED => 'Priority Changed',
            self::ACTION_BULK_ASSIGNED => 'Bulk Assigned',
            self::ACTION_BULK_UPDATED => 'Bulk Updated',
            self::ACTION_BULK_DELETED => 'Bulk Deleted',
            self::ACTION_BULK_ACTIVATED => 'Bulk Activated',
            self::ACTION_BULK_DEACTIVATED => 'Bulk Deactivated',
            self::ACTION_PROFILE_UPDATED => 'Profile Updated',
            self::ACTION_PASSWORD_CHANGED => 'Password Changed',
            self::ACTION_EMAIL_CHANGED => 'Email Changed',
            self::ACTION_ACCOUNT_LOCKED => 'Account Locked',
            self::ACTION_ACCOUNT_UNLOCKED => 'Account Unlocked',
            self::ACTION_LOGIN => 'Login',
            self::ACTION_LOGOUT => 'Logout',
            self::ACTION_LOGIN_FAILED => 'Login Failed',
            self::ACTION_EXPORTED => 'Exported',
            self::ACTION_IMPORTED => 'Imported',
            self::ACTION_REPORT_GENERATED => 'Report Generated',
            self::ACTION_SETTING_CHANGED => 'Setting Changed',
            self::ACTION_CONFIG_CHANGED => 'Configuration Changed',
        ];

        return $actionMap[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    /**
     * Get a human-readable description.
     */
    public function getDescription()
    {
        if ($this->description) {
            return $this->description;
        }

        $actorName = $this->actor_name ?? 'System';
        $action = $this->getActionLabelAttribute();
        $entityType = $this->entity_type ? class_basename($this->entity_type) : '';

        $description = "{$actorName} {$action}";

        if ($entityType) {
            $description .= " {$entityType}";
        }

        if ($this->entity_id) {
            $description .= " #{$this->entity_id}";
        }

        return $description;
    }

    /**
     * Get metadata as array with fallback.
     */
    public function getMetadataArray()
    {
        if (is_string($this->metadata)) {
            return json_decode($this->metadata, true) ?? [];
        }

        return $this->metadata ?? [];
    }

    /**
     * Get specific metadata value.
     */
    public function getMetadataValue($key, $default = null)
    {
        $metadata = $this->getMetadataArray();
        return $metadata[$key] ?? $default;
    }

    /**
     * Check if this is a critical action.
     */
    public function isCriticalAction()
    {
        $criticalActions = [
            'deleted',
            'status_changed',
            'priority_changed',
            'assigned',
            'unassigned',
        ];

        return in_array($this->action, $criticalActions);
    }

    /**
     * Get the CSS class for the action badge.
     */
    public function getActionBadgeClass()
    {
        return match ($this->action) {
            self::ACTION_CREATED => 'bg-green-100 text-green-800',
            self::ACTION_UPDATED, self::ACTION_PROFILE_UPDATED => 'bg-blue-100 text-blue-800',
            self::ACTION_DELETED => 'bg-red-100 text-red-800',
            self::ACTION_ASSIGNED, self::ACTION_REASSIGNED => 'bg-purple-100 text-purple-800',
            self::ACTION_UNASSIGNED => 'bg-yellow-100 text-yellow-800',
            self::ACTION_RESOLVED => 'bg-emerald-100 text-emerald-800',
            self::ACTION_CLOSED => 'bg-gray-100 text-gray-800',
            self::ACTION_REOPENED => 'bg-orange-100 text-orange-800',
            self::ACTION_ESCALATED => 'bg-red-200 text-red-900',
            self::ACTION_COMMENTED => 'bg-indigo-100 text-indigo-800',
            self::ACTION_STATUS_CHANGED, self::ACTION_PRIORITY_CHANGED => 'bg-amber-100 text-amber-800',
            self::ACTION_LOGIN, self::ACTION_LOGOUT => 'bg-cyan-100 text-cyan-800',
            self::ACTION_LOGIN_FAILED => 'bg-red-200 text-red-800',
            self::ACTION_EXPORTED, self::ACTION_IMPORTED => 'bg-teal-100 text-teal-800',
            self::ACTION_PASSWORD_CHANGED, self::ACTION_EMAIL_CHANGED => 'bg-amber-100 text-amber-800',
            self::ACTION_ACCOUNT_LOCKED => 'bg-red-200 text-red-900',
            self::ACTION_ACCOUNT_UNLOCKED => 'bg-green-200 text-green-900',
            self::ACTION_BULK_ASSIGNED, self::ACTION_BULK_UPDATED => 'bg-purple-100 text-purple-800',
            self::ACTION_BULK_DELETED => 'bg-red-100 text-red-800',
            self::ACTION_BULK_ACTIVATED => 'bg-green-100 text-green-800',
            self::ACTION_BULK_DEACTIVATED => 'bg-gray-100 text-gray-800',
            self::ACTION_REPORT_GENERATED => 'bg-indigo-100 text-indigo-800',
            self::ACTION_SETTING_CHANGED, self::ACTION_CONFIG_CHANGED => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get the CSS class for the HTTP method badge.
     */
    public function getHttpMethodBadgeClass()
    {
        return match (strtoupper($this->http_method)) {
            'GET' => 'bg-green-100 text-green-800',
            'POST' => 'bg-blue-100 text-blue-800',
            'PUT', 'PATCH' => 'bg-yellow-100 text-yellow-800',
            'DELETE' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Convert to array for API responses.
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'action_label' => $this->getActionLabelAttribute(),
            'formatted_created_at' => $this->getFormattedCreatedAtAttribute(),
            'description' => $this->getDescription(),
            'is_critical' => $this->isCriticalAction(),
            'action_badge_class' => $this->getActionBadgeClass(),
            'http_method_badge_class' => $this->getHttpMethodBadgeClass(),
            'metadata_array' => $this->getMetadataArray(),
        ]);
    }
}