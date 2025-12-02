<?php

namespace App\Services;

use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RoleValidationService
{
    private AuthService $authService;

    /**
     * Role hierarchy definition (higher number = more permissions)
     */
    private const ROLE_HIERARCHY = [
        'user' => 1,
        'teknisi' => 2,
        'admin_aplikasi' => 3,
        'admin_helpdesk' => 4,
    ];

    /**
     * Permission mappings for each role
     */
    private const ROLE_PERMISSIONS = [
        'admin_helpdesk' => [
            'manage_tickets',
            'assign_tickets',
            'view_reports',
            'manage_users',
            'system_settings',
            'manage_applications',
            'assign_teknisi',
            'manage_categories',
            'view_own_tickets',
            'add_ticket_comments',
            'create_tickets',
        ],
        'admin_aplikasi' => [
            'manage_applications',
            'assign_teknisi',
            'view_reports',
            'manage_categories',
            'view_own_tickets',
            'add_ticket_comments',
            'create_tickets',
            'view_assigned_tickets',
            'update_ticket_status',
            'view_knowledge_base',
        ],
        'teknisi' => [
            'view_assigned_tickets',
            'update_ticket_status',
            'add_ticket_comments',
            'view_knowledge_base',
            'view_own_tickets',
            'create_tickets',
        ],
        'user' => [
            'create_tickets',
            'view_own_tickets',
            'add_ticket_comments',
        ],
    ];

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Validate if user has specific permission with unified logic
     *
     * @param mixed $user
     * @param string $permission
     * @return bool
     */
    public function hasPermission($user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        // Get user role using unified method
        $role = $this->getUserRole($user);

        // Check role-based permissions
        if ($this->hasRolePermission($role, $permission)) {
            return true;
        }

        // Check user-specific permissions if available
        if ($this->hasUserSpecificPermission($user, $permission)) {
            return true;
        }

        // Check model-specific permissions for advanced users
        if ($this->hasModelSpecificPermission($user, $permission)) {
            return true;
        }

        return false;
    }

    /**
     * Get user role with unified logic
     *
     * @param mixed $user
     * @return string
     */
    public function getUserRole($user): string
    {
        // First try to get role from database field (most reliable)
        if (isset($user->role) && !empty($user->role)) {
            return $user->role;
        }

        // Use AuthService as fallback
        return $this->authService->getUserRole($user);
    }

    /**
     * Check if role has specific permission
     *
     * @param string $role
     * @param string $permission
     * @return bool
     */
    private function hasRolePermission(string $role, string $permission): bool
    {
        $rolePermissions = self::ROLE_PERMISSIONS[$role] ?? [];

        return in_array($permission, $rolePermissions);
    }

    /**
     * Check user-specific permissions (for users with custom permissions)
     *
     * @param mixed $user
     * @param string $permission
     * @return bool
     */
    private function hasUserSpecificPermission($user, string $permission): bool
    {
        // Check if user has custom permissions array
        if (isset($user->permissions) && is_array($user->permissions)) {
            return in_array($permission, $user->permissions);
        }

        // Check if user has permissions JSON field
        if (isset($user->permissions) && is_string($user->permissions)) {
            $permissions = json_decode($user->permissions, true);
            if (is_array($permissions)) {
                return in_array($permission, $permissions);
            }
        }

        return false;
    }

    /**
     * Check model-specific permissions for advanced role validation
     *
     * @param mixed $user
     * @param string $permission
     * @return bool
     */
    private function hasModelSpecificPermission($user, string $permission): bool
    {
        try {
            // Handle AdminHelpdesk specific permissions
            if ($user instanceof AdminHelpdesk) {
                return $this->checkAdminHelpdeskPermissions($user, $permission);
            }

            // Handle AdminAplikasi specific permissions
            if ($user instanceof AdminAplikasi) {
                return $this->checkAdminAplikasiPermissions($user, $permission);
            }

            // Handle Teknisi specific permissions
            if ($user instanceof Teknisi) {
                return $this->checkTeknisiPermissions($user, $permission);
            }

            // Handle User specific permissions
            if ($user instanceof User) {
                return $this->checkUserPermissions($user, $permission);
            }

        } catch (\Exception $e) {
            Log::warning('Error checking model-specific permissions', [
                'user_type' => get_class($user),
                'permission' => $permission,
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Check AdminHelpdesk specific permissions
     */
    private function checkAdminHelpdeskPermissions(AdminHelpdesk $user, string $permission): bool
    {
        // System admin has all permissions
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Check specific permission mappings
        $permissionMap = [
            'manage_tickets' => 'canManageTickets',
            'assign_tickets' => 'canManageTickets', // Admins who can manage can also assign
            'manage_users' => 'canManageUsers',
            'view_reports' => 'canViewReports',
            'system_settings' => 'isSystemAdmin',
        ];

        $method = $permissionMap[$permission] ?? null;
        if ($method && method_exists($user, $method)) {
            return $user->$method();
        }

        return false;
    }

    /**
     * Check AdminAplikasi specific permissions
     */
    private function checkAdminAplikasiPermissions(AdminAplikasi $user, string $permission): bool
    {
        // System admin has all permissions
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Check specific permission mappings
        $permissionMap = [
            'manage_applications' => 'canManageApplications',
            'assign_teknisi' => 'canManageApplications', // Admins who can manage can also assign
            'view_reports' => 'canViewReports',
            'manage_categories' => 'canManageApplications',
            'provide_technical_support' => 'canProvideTechnicalSupport',
        ];

        $method = $permissionMap[$permission] ?? null;
        if ($method && method_exists($user, $method)) {
            return $user->$method();
        }

        return false;
    }

    /**
     * Check Teknisi specific permissions
     */
    private function checkTeknisiPermissions(Teknisi $user, string $permission): bool
    {
        // Check if teknisi is available and active
        if ($user->status !== 'active') {
            return false;
        }

        // Check specific permission mappings
        $permissionMap = [
            'view_assigned_tickets' => fn() => true, // All active teknisi can view assigned tickets
            'update_ticket_status' => fn() => $user->isAvailable(),
            'add_ticket_comments' => fn() => true, // All teknisi can add comments
            'view_knowledge_base' => fn() => true, // All teknisi can view knowledge base
        ];

        $check = $permissionMap[$permission] ?? null;
        if ($check && is_callable($check)) {
            return $check();
        }

        return false;
    }

    /**
     * Check User specific permissions
     */
    private function checkUserPermissions(User $user, string $permission): bool
    {
        // Check if user is active
        if (($user->status ?? 'active') !== 'active') {
            return false;
        }

        // Check specific permission mappings
        $permissionMap = [
            'create_tickets' => fn() => true, // All active users can create tickets
            'view_own_tickets' => fn() => true, // All active users can view own tickets
            'add_ticket_comments' => fn() => true, // All active users can add comments
        ];

        $check = $permissionMap[$permission] ?? null;
        if ($check && is_callable($check)) {
            return $check();
        }

        return false;
    }

    /**
     * Validate if user has any of the specified permissions
     *
     * @param mixed $user
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission($user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate if user has all of the specified permissions
     *
     * @param mixed $user
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions($user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user role has sufficient level for action
     *
     * @param mixed $user
     * @param string $minimumRole
     * @return bool
     */
    public function hasMinimumRole($user, string $minimumRole): bool
    {
        $userRole = $this->getUserRole($user);
        $userLevel = self::ROLE_HIERARCHY[$userRole] ?? 0;
        $minimumLevel = self::ROLE_HIERARCHY[$minimumRole] ?? 0;

        return $userLevel >= $minimumLevel;
    }

    /**
     * Get all permissions for a user
     *
     * @param mixed $user
     * @return array
     */
    public function getUserPermissions($user): array
    {
        if (!$user) {
            return [];
        }

        $role = $this->getUserRole($user);
        $permissions = self::ROLE_PERMISSIONS[$role] ?? [];

        // Merge with user-specific permissions
        if ($this->hasUserSpecificPermission($user, 'custom_permissions')) {
            $permissions = array_merge($permissions, $this->getUserSpecificPermissions($user));
        }

        return array_unique($permissions);
    }

    /**
     * Get user-specific permissions from user model
     *
     * @param mixed $user
     * @return array
     */
    private function getUserSpecificPermissions($user): array
    {
        $permissions = [];

        // Check if user has custom permissions array
        if (isset($user->permissions) && is_array($user->permissions)) {
            $permissions = $user->permissions;
        }

        // Check if user has permissions JSON field
        if (isset($user->permissions) && is_string($user->permissions)) {
            $decoded = json_decode($user->permissions, true);
            if (is_array($decoded)) {
                $permissions = array_merge($permissions, $decoded);
            }
        }

        return $permissions;
    }

    /**
     * Validate role transition (for role changes)
     *
     * @param string $currentRole
     * @param string $newRole
     * @return bool
     */
    public function canTransitionToRole(string $currentRole, string $newRole): bool
    {
        // Define valid role transitions
        $validTransitions = [
            'user' => ['teknisi', 'admin_aplikasi', 'admin_helpdesk'],
            'teknisi' => ['admin_aplikasi', 'admin_helpdesk'],
            'admin_aplikasi' => ['admin_helpdesk'],
            'admin_helpdesk' => [], // Highest level, no transitions allowed
        ];

        $allowedTransitions = $validTransitions[$currentRole] ?? [];
        return in_array($newRole, $allowedTransitions);
    }

    /**
     * Get role hierarchy level
     *
     * @param string $role
     * @return int
     */
    public function getRoleLevel(string $role): int
    {
        return self::ROLE_HIERARCHY[$role] ?? 0;
    }

    /**
     * Check if role is administrative
     *
     * @param string $role
     * @return bool
     */
    public function isAdministrativeRole(string $role): bool
    {
        return in_array($role, ['admin_helpdesk', 'admin_aplikasi']);
    }

    /**
     * Check if role is technical
     *
     * @param string $role
     * @return bool
     */
    public function isTechnicalRole(string $role): bool
    {
        return in_array($role, ['teknisi']);
    }

    /**
     * Check if role is end-user
     *
     * @param string $role
     * @return bool
     */
    public function isEndUserRole(string $role): bool
    {
        return in_array($role, ['user']);
    }

    /**
     * Validate permission for resource action
     *
     * @param mixed $user
     * @param string $resource
     * @param string $action
     * @return bool
     */
    public function canAccessResource($user, string $resource, string $action = 'view'): bool
    {
        $permission = "{$action}_{$resource}";

        // Handle special cases
        if ($resource === 'tickets') {
            return $this->canAccessTickets($user, $action);
        }

        if ($resource === 'applications') {
            return $this->canAccessApplications($user, $action);
        }

        if ($resource === 'reports') {
            return $this->canAccessReports($user, $action);
        }

        if ($resource === 'users') {
            return $this->canAccessUsers($user, $action);
        }

        // Default permission check
        return $this->hasPermission($user, $permission);
    }

    /**
     * Check ticket access permissions
     */
    private function canAccessTickets($user, string $action): bool
    {
        $permissions = [
            'view' => ['view_own_tickets', 'view_assigned_tickets', 'manage_tickets'],
            'create' => ['create_tickets'],
            'edit' => ['update_ticket_status', 'manage_tickets'],
            'delete' => ['manage_tickets'],
            'assign' => ['assign_tickets', 'manage_tickets'],
        ];

        $requiredPermissions = $permissions[$action] ?? [];
        return $this->hasAnyPermission($user, $requiredPermissions);
    }

    /**
     * Check application access permissions
     */
    private function canAccessApplications($user, string $action): bool
    {
        $permissions = [
            'view' => ['manage_applications', 'view_reports'],
            'create' => ['manage_applications'],
            'edit' => ['manage_applications'],
            'delete' => ['manage_applications'],
            'assign' => ['assign_teknisi', 'manage_applications'],
        ];

        $requiredPermissions = $permissions[$action] ?? [];
        return $this->hasAnyPermission($user, $requiredPermissions);
    }

    /**
     * Check report access permissions
     */
    private function canAccessReports($user, string $action): bool
    {
        $permissions = [
            'view' => ['view_reports'],
            'create' => ['view_reports'], // Can create if can view
            'edit' => ['view_reports'],
            'delete' => ['manage_users'], // Only admins can delete reports
        ];

        $requiredPermissions = $permissions[$action] ?? [];
        return $this->hasAnyPermission($user, $requiredPermissions);
    }

    /**
     * Check user management permissions
     */
    private function canAccessUsers($user, string $action): bool
    {
        $permissions = [
            'view' => ['manage_users'],
            'create' => ['manage_users'],
            'edit' => ['manage_users'],
            'delete' => ['manage_users'],
        ];

        $requiredPermissions = $permissions[$action] ?? [];
        return $this->hasAnyPermission($user, $requiredPermissions);
    }
}