<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * STABLE AUTHENTICATION MIDDLEWARE
 *
 * This middleware provides consistent authentication state management
 * without interfering with Inertia.js navigation or causing session conflicts.
 * It gently restores sessions and tracks user activity, but delegates
 * authentication enforcement to route-level middleware.
 *
 * NOTE:
 * This middleware DOES NOT update session activity or '_last_activity'. Session activity updates
 * and timeout management are exclusively handled by SessionTimeoutMiddleware.
 */
class StableAuthMiddleware
{
    /**
     * Handle an incoming request with stable authentication checking
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Attempt to restore session from a stable source if not currently authenticated.
        if (!Auth::guard('web')->check()) {
            $this->attemptSessionRestoration($request);
        }

        // Pass the request to the next middleware in the stack.
        // Authentication enforcement is handled by the 'auth' middleware on the route.
        return $next($request);
    }

    /**
     * Attempt gentle session restoration without breaking existing sessions
     */
    private function attemptSessionRestoration(Request $request): void
    {
        try {
            // Check if we have user session data that can be restored
            $userSession = session('user_session');

            if ($userSession && isset($userSession['user_id'])) {
                // Use AuthService to restore session
                $authService = app(\App\Services\AuthService::class);
                $user = $authService->getUserById($userSession['user_id']);

                if ($user && isset($user->status) && $user->status === 'active') {
                    // Restore authentication without invalidating session
                    Auth::guard('web')->login($user, false);

                    Log::info('Session restored successfully', [
                        'user_id' => $user->getKey(),
                        'nip' => $user->nip,
                        'session_id' => session()->getId()
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Session restoration failed', [
                'error' => $e->getMessage(),
                'session_id' => session()->getId()
            ]);
        }
    }
}