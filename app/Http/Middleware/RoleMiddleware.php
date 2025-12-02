<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use App\Services\RoleRouteService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request - SIMPLIFIED
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        // Use custom auth system - get user from session using AuthService
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            Log::warning('RoleMiddleware: Unauthenticated access attempt', [
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);

            return $this->handleUnauthenticated($request);
        }

        // Parse allowed roles (support multiple roles separated by |)
        $allowedRoles = array_map('trim', explode('|', $roles));

        // Get role directly from authenticated user via AuthService
        $userRole = $this->authService->getUserRole($user);

        // Check if user has required role
        if (!$this->hasRequiredRole($userRole, $allowedRoles)) {
            Log::warning('RoleMiddleware: Insufficient permissions', [
                'nip' => $user->nip,
                'user_role' => $userRole,
                'required_roles' => $allowedRoles,
                'url' => $request->fullUrl(),
            ]);

            return $this->handleInsufficientPermissions($request, $user, $userRole, $allowedRoles);
        }

        // Update session activity metadata for analytics (non-authoritative)
        session(['_last_activity' => time()]);

        Log::info('RoleMiddleware: Access granted', [
            'nip' => $user->nip,
            'role' => $userRole,
            'url' => $request->fullUrl(),
        ]);

        return $next($request);
    }

    /**
     * Get authenticated user using web guard as primary source
     * Only uses session reconstruction as last resort and immediately re-authenticates
     *
     * @return mixed|null
     */
    private function getAuthenticatedUser()
    {
        // Primary source: web guard - return early if authenticated
        if (Auth::guard('web')->check()) {
            return Auth::guard('web')->user();
        }

        // Last resort: session reconstruction only if guard is not authenticated
        $userSession = session('user_session');
        if ($userSession && isset($userSession['user_id'])) {
            $user = $this->authService->getUserById($userSession['user_id']);
            if ($user && isset($user->status) && $user->status === 'active') {
                // Re-login to normalize future requests
                try {
                    Auth::guard('web')->login($user, false);
                    return $user;
                } catch (\Exception $e) {
                    Log::error('RoleMiddleware: Failed to re-authenticate user', [
                        'error' => $e->getMessage(),
                        'user_nip' => $user->nip ?? 'unknown',
                    ]);
                }
            }
        }

        return null;
    }

    /**
     * Handle unauthenticated requests
     *
     * @param Request $request
     * @return Response
     */
    private function handleUnauthenticated(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'UNAUTHENTICATED'
            ], 401);
        }

        return redirect()->route('login');
    }

    
    /**
     * Check if user has required role
     *
     * @param string $userRole
     * @param array $allowedRoles
     * @return bool
     */
    private function hasRequiredRole(string $userRole, array $allowedRoles): bool
    {
        return in_array($userRole, $allowedRoles);
    }

    /**
     * Handle insufficient permissions
     *
     * @param Request $request
     * @param mixed $user
     * @param string $userRole
     * @param array $allowedRoles
     * @return Response
     */
    private function handleInsufficientPermissions(Request $request, $user, string $userRole, array $allowedRoles): Response
    {
        // Log unauthorized access attempt
        $this->logUnauthorizedAccess($request, $user, $userRole, $allowedRoles);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'error_code' => 'INSUFFICIENT_PERMISSIONS',
                'required_roles' => $allowedRoles,
                'user_role' => $userRole
            ], 403);
        }

        // Redirect based on user role
        return $this->getRoleBasedRedirect($userRole);
    }

    /**
     * Get role-based redirect URL for unauthorized users
     */
    private function getRoleBasedRedirect(string $userRole): Response
    {
        return RoleRouteService::redirectToDashboard($userRole);
    }

    /**
     * Log unauthorized access attempts
     */
    private function logUnauthorizedAccess(Request $request, $user, string $userRole, array $allowedRoles): void
    {
        Log::warning('Unauthorized access attempt', [
            'nip' => $user->nip,
            'user_role' => $userRole,
            'required_roles' => $allowedRoles,
            'route' => $request->route() ? $request->route()->getName() : 'unknown',
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }

  }