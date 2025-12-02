<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Try standard Laravel Auth first
        if (Auth::check()) {
            return $next($request);
        }

        // Use AuthService for session-based authentication
        $authService = app(\App\Services\AuthService::class);
        $user = $authService->getCurrentAuthenticatedUser();

        if ($user) {
            $sessionNip = $user->nip;
            $sessionRole = $authService->getUserRole($user);
        } else {
            $sessionNip = null;
            $sessionRole = null;
        }

        if ($sessionNip && $sessionRole) {
            // Create a temporary user object for the request
            $request->setUserResolver(function () use ($sessionNip, $sessionRole) {
                return (object) [
                    'nip' => $sessionNip,
                    'role' => $sessionRole
                ];
            });

            return $next($request);
        }

        // No authentication found - return empty response in expected format for polling compatibility
        // This allows frontend to handle unauthenticated state gracefully
        if ($request->is('api/notifications') || $request->is('api/notifications/*')) {
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

        // For other notification endpoints, require authentication
        return response()->json([
            'success' => false,
            'message' => 'Authentication required',
            'errors' => ['Unauthorized access']
        ], 401);
    }
}