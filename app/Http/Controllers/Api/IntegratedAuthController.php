<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class IntegratedAuthController extends Controller
{
    /**
     * Get comprehensive integrated authentication status
     * This endpoint integrates all auth layers for the application
     */
    public function getIntegratedAuth(Request $request): JsonResponse
    {
        try {
            // Get auth service
            $authService = app(AuthService::class);
            $user = $authService->getCurrentAuthenticatedUser();
            $role = $authService->getCurrentUserRole();

            // Get session info
            $sessionId = session()->getId();
            $sessionData = session()->all();

            // Get Laravel auth info
            $laravelAuth = [
                'check' => Auth::check(),
                'id' => Auth::id(),
                'user' => Auth::user(),
                'guard' => Auth::getDefaultDriver(),
                'web_guard_check' => Auth::guard('web')->check(),
                'web_guard_id' => Auth::guard('web')->id(),
                'web_guard_user' => Auth::guard('web')->user()
            ];

            // Get multi-table auth session data (using guard-based accessors)
            $userSession = session('user_session');
            $multiTableAuth = [
                'authenticated_user_id' => Auth::guard('web')->id() ?? $userSession['user_id'] ?? null,
                'authenticated_user_role' => $role,
                'authenticated_user_nip' => $user ? $user->nip : ($userSession['nip'] ?? null),
                'authenticated_user_name' => $user ? ($user->name ?? $user->nip) : ($userSession['user_name'] ?? null),
                'user_session' => $userSession,
                'login_time' => $userSession['login_time'] ?? null,
                'ip_address' => $userSession['ip_address'] ?? null
            ];

            // Determine final auth state
            $isAuthenticated = $user !== null;

            // Prepare user data for frontend
            $userData = null;
            if ($isAuthenticated) {
                $userData = [
                    'id' => $user->id,
                    'nip' => $user->nip,
                    'name' => $user->name ?? $user->nip,
                    'email' => $user->email,
                    'role' => $role,
                    'table' => method_exists($user, 'getTable') ? $user->getTable() : 'unknown',
                    'permissions' => $authService->getUserPermissions($user)
                ];
            }

            // Prepare response
            $response = [
                'success' => true,
                'authenticated' => $isAuthenticated,
                'user' => $userData,
                'role' => $role,
                'permissions' => $userData['permissions'] ?? [],
                'session_id' => $sessionId,
                'sources' => [
                    'laravel_auth' => $laravelAuth,
                    'multi_table_session' => $multiTableAuth,
                    'all_session_data' => $sessionData
                ],
                'integration_status' => [
                    'auth_service_user' => $user ? [
                        'id' => $user->id,
                        'class' => get_class($user),
                        'table' => method_exists($user, 'getTable') ? $user->getTable() : 'unknown'
                    ] : null,
                    'auth_service_role' => $role,
                    'session_valid' => true, // Session is valid if we can access it
                    'csrf_token' => csrf_token(),
                    'cookie_available' => $request->hasCookie('laravel_session'),
                    'headers' => [
                        'authorization' => $request->header('Authorization'),
                        'x_requested_with' => $request->header('X-Requested-With'),
                        'x_session_id' => $request->header('X-Session-ID')
                    ]
                ],
                'timestamp' => now()->toISOString(),
                'debug_info' => [
                    'request_path' => $request->path(),
                    'request_method' => $request->method(),
                    'is_ajax' => $request->ajax(),
                    'expects_json' => $request->expectsJson(),
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip()
                ]
            ];

            // Log for debugging only in local environment
            if (app()->isLocal()) {
                Log::debug('Integrated auth check', [
                    'authenticated' => $isAuthenticated,
                    'timestamp' => now()->toISOString()
                ]);
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Integrated auth check error', [
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'authenticated' => false,
                'user' => null,
                'role' => null,
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }
}