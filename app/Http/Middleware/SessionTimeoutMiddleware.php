<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Services\ConcurrentSessionService;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeoutMiddleware
{
    protected ConcurrentSessionService $sessionService;

    public function __construct(ConcurrentSessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Get session timeout from configuration
     */
    private function getSessionTimeout(): int
    {
        return Config::get('helpdesk.security.session_timeout_minutes', 120) * 60;
    }

    /**
     * Get warning time from configuration
     */
    private function getWarningTime(): int
    {
        return 10 * 60; // 10 minutes before expiry
    }

    /**
     * Handle an incoming request with DATABASE-VALIDATED session timeout management
     *
     * This middleware validates session expiry against:
     * 1. Database user_sessions table (primary source of truth)
     * 2. Laravel session _last_activity (fallback)
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip session timeout check for more routes to reduce interference
        if ($this->shouldSkipSessionCheck($request)) {
            return $next($request);
        }

        // Check if session is available
        if (!$request->hasSession()) {
            return $next($request);
        }

        // Only apply timeout check if user is actually authenticated
        if (!Auth::guard('web')->check()) {
            return $next($request);
        }

        $user = Auth::guard('web')->user();
        $sessionId = session()->getId();
        $currentTime = time();
        $sessionTimeout = $this->getSessionTimeout();

        // PRIMARY CHECK: Validate against database user_sessions table
        $dbSessionValid = $this->validateDatabaseSession($sessionId, $user);
        
        if (!$dbSessionValid) {
            Log::info('Session timeout - database session expired or invalid', [
                'nip' => $user->nip,
                'session_id' => $sessionId,
                'ip_address' => $request->ip(),
                'timestamp' => Carbon::now()->toISOString()
            ]);

            return $this->handleSessionTimeout($request, $user);
        }

        // SECONDARY CHECK: Laravel session _last_activity (fallback)
        $lastActivity = session('_last_activity');

        // Initialize last activity if not set
        if (!$lastActivity) {
            session(['_last_activity' => $currentTime]);
            session(['_session_start_time' => $currentTime]);
            return $next($request);
        }

        $inactiveTime = $currentTime - $lastActivity;

        // Check if session has expired based on ACTIVITY-based timeout
        if ($inactiveTime > $sessionTimeout) {
            Log::info('Session timeout - inactive logout', [
                'nip' => $user->nip,
                'session_id' => $sessionId,
                'last_activity' => date('Y-m-d H:i:s', $lastActivity),
                'inactive_seconds' => $inactiveTime,
                'inactive_minutes' => round($inactiveTime / 60, 1),
                'timeout_seconds' => $sessionTimeout,
                'ip_address' => $request->ip(),
            ]);

            // Mark database session as inactive
            $this->sessionService->terminateSession($sessionId);

            return $this->handleSessionTimeout($request, $user);
        }

        // Update activity for ALL authenticated requests
        session(['_last_activity' => $currentTime]);

        // Update database session activity
        $this->sessionService->updateSessionActivity($sessionId);

        // Get remaining time from database session
        $dbSession = $this->sessionService->getSessionWithExpiry($sessionId);
        $remainingTime = $dbSession ? $dbSession['seconds_remaining'] : max(0, $sessionTimeout - $inactiveTime);

        // Update session expiry info for frontend tracking
        $expirationTime = $currentTime + $remainingTime;
        $warningTime = $this->getWarningTime();
        $warningExpiry = $expirationTime - $warningTime;

        session([
            'session_expires_at' => $expirationTime,
            'session_warning_at' => $warningExpiry,
            'minutes_remaining' => round($remainingTime / 60, 1),
        ]);

        return $next($request);
    }

    /**
     * Validate session against database user_sessions table
     */
    private function validateDatabaseSession(string $sessionId, $user): bool
    {
        try {
            // Check if session exists and is valid in database
            $isValid = $this->sessionService->isSessionValid($sessionId);
            
            if (!$isValid) {
                // Session doesn't exist in database or is expired
                // This can happen if user_sessions was cleaned up
                Log::debug('Database session validation failed', [
                    'session_id' => $sessionId,
                    'nip' => $user->nip
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            // If database check fails, fall back to Laravel session check
            Log::warning('Database session validation error, falling back to Laravel session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            
            // Return true to allow fallback to Laravel session check
            return true;
        }
    }

    /**
     * Handle session timeout more gracefully
     */
    private function handleSessionTimeout(Request $request, $user = null): Response
    {
        $sessionId = session()->getId();
        
        // Mark database session as inactive
        try {
            $this->sessionService->terminateSession($sessionId);
        } catch (\Exception $e) {
            Log::error('Failed to terminate database session on timeout', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }

        // Logout user
        Auth::guard('web')->logout();

        // Invalidate the session
        session()->invalidate();
        session()->regenerateToken();

        if ($this->isApiRequest($request)) {
            return response()->json([
                'success' => false,
                'authenticated' => false,
                'message' => 'Session expired. Please login again.',
                'error_code' => 'SESSION_EXPIRED',
                'redirect' => route('login')
            ], 401);
        }

        return redirect()->route('login')
            ->with('warning', 'Sesi Anda telah berakhir karena tidak aktif. Silakan login kembali.');
    }

    
    /**
     * Determine if session check should be skipped for this request
     */
    private function shouldSkipSessionCheck(Request $request): bool
    {
        // Only skip for critical auth routes and file uploads
        $skipRoutes = [
            'login',
            'logout',
            'files/upload',
            'files/delete',
        ];

        foreach ($skipRoutes as $route) {
            if ($request->is($route) || $request->is("{$route}/*")) {
                return true;
            }
        }

        // Skip for static assets
        if ($request->is('build/*') || $request->is('assets/*') || $request->is('storage/*')) {
            return true;
        }

        return false;
    }

    /**
     * Check if this is an API request
     */
    private function isApiRequest(Request $request): bool
    {
        // Restrict API request detection to requests explicitly targeting the 'api/' route prefix.
        // This avoids misinterpreting Inertia's JSON-expecting page loads as API calls.
        return $request->is('api/*');
    }
}
