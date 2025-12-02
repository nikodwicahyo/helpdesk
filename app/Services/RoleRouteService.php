<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;

class RoleRouteService
{
    /**
     * Get the dashboard route for a given role
     *
     * @param string $role
     * @return string
     */
    public static function getDashboardRoute(string $role): string
    {
        $routes = config('roles.dashboard_routes', []);
        return $routes[$role] ?? config('roles.default_dashboard', 'user.dashboard');
    }

    /**
     * Get the URL for a role's dashboard
     *
     * @param string $role
     * @return string
     */
    public static function getDashboardUrl(string $role): string
    {
        $routeName = self::getDashboardRoute($role);
        return route($routeName);
    }

    /**
     * Redirect user to appropriate dashboard based on role
     *
     * @param string $role
     * @return \Illuminate\Http\RedirectResponse
     */
    public static function redirectToDashboard(string $role)
    {
        return redirect()->route(self::getDashboardRoute($role));
    }

    /**
     * Get all available dashboard routes
     *
     * @return array
     */
    public static function getAllDashboardRoutes(): array
    {
        return config('roles.dashboard_routes', []);
    }

    /**
     * Get role information
     *
     * @param string $role
     * @return array|null
     */
    public static function getRoleInfo(string $role): ?array
    {
        $roles = config('roles.roles', []);
        return $roles[$role] ?? null;
    }

    /**
     * Check if a role exists in the configuration
     *
     * @param string $role
     * @return bool
     */
    public static function isValidRole(string $role): bool
    {
        return array_key_exists($role, config('roles.dashboard_routes', []));
    }

    /**
     * Get all available roles
     *
     * @return array
     */
    public static function getAllRoles(): array
    {
        return array_keys(config('roles.dashboard_routes', []));
    }

    /**
     * Get role display name
     *
     * @param string $role
     * @return string
     */
    public static function getRoleDisplayName(string $role): string
    {
        $roleInfo = self::getRoleInfo($role);
        return $roleInfo['name'] ?? ucfirst(str_replace('_', ' ', $role));
    }

    /**
     * Get role level (for hierarchy checks)
     *
     * @param string $role
     * @return int
     */
    public static function getRoleLevel(string $role): int
    {
        $roleInfo = self::getRoleInfo($role);
        return $roleInfo['level'] ?? 0;
    }

    /**
     * Check if one role has higher or equal level than another
     *
     * @param string $role1
     * @param string $role2
     * @return bool
     */
    public static function hasRoleLevelOrHigher(string $role1, string $role2): bool
    {
        return self::getRoleLevel($role1) >= self::getRoleLevel($role2);
    }
}