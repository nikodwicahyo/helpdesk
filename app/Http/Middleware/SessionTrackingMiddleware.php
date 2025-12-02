<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SessionTrackingMiddleware
{
    /**
     * Routes that should not extend session expiry (polling/status checks)
     */
    private const NON_EXTENDING_ROUTES = [
        'api/session/status',
        'api/auth/status',
        'api/user-status',
        'api/notifications',
    ];

    /**
     * Handle an incoming request and track session in user_sessions table
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only track authenticated users
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            $sessionId = session()->getId();
            
            // Check if this is a status polling request (should not extend session)
            $shouldExtendExpiry = !$this->isNonExtendingRoute($request);
            
            try {
                $this->trackSession($user, $sessionId, $request, $shouldExtendExpiry);
            } catch (\Exception $e) {
                // Log error but don't block the request
                Log::error('Session tracking failed', [
                    'error' => $e->getMessage(),
                    'session_id' => $sessionId,
                    'nip' => $user->nip ?? 'unknown'
                ]);
            }
        }

        return $next($request);
    }

    /**
     * Check if this request should not extend session expiry
     */
    private function isNonExtendingRoute(Request $request): bool
    {
        $path = $request->path();
        
        foreach (self::NON_EXTENDING_ROUTES as $route) {
            if (str_starts_with($path, $route)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Track or update session in user_sessions table
     */
    private function trackSession($user, string $sessionId, Request $request, bool $shouldExtendExpiry = true): void
    {
        $now = Carbon::now();
        $sessionTimeout = (int) config('helpdesk.security.session_timeout_minutes', 120);
        $expiresAt = $now->copy()->addMinutes($sessionTimeout);
        
        $userRole = $this->getUserRole($user);
        
        // Check if session already exists
        $existingSession = DB::table('user_sessions')
            ->where('session_id', $sessionId)
            ->first();

        if ($existingSession) {
            // Update existing session
            $updateData = [
                'last_activity' => $now,
                'is_active' => true,
                'updated_at' => $now
            ];
            
            // Only extend expiry for user-initiated requests, not polling
            if ($shouldExtendExpiry) {
                $updateData['expires_at'] = $expiresAt;
            }
            
            DB::table('user_sessions')
                ->where('session_id', $sessionId)
                ->update($updateData);
        } else {
            // Create new session entry
            DB::table('user_sessions')->insert([
                'session_id' => $sessionId,
                'nip' => $user->nip,
                'user_role' => $userRole,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_data' => json_encode([
                    'name' => $user->name ?? $user->nip,
                    'email' => $user->email ?? null,
                    'role' => $userRole
                ]),
                'login_time' => session('_session_created_at', $now),
                'last_activity' => $now,
                'expires_at' => $expiresAt,
                'is_active' => true,
                'device_info' => $this->getDeviceInfo($request),
                'location_info' => $request->ip(),
                'created_at' => $now,
                'updated_at' => $now
            ]);

            Log::info('New session tracked in database', [
                'session_id' => $sessionId,
                'nip' => $user->nip,
                'role' => $userRole,
                'ip' => $request->ip()
            ]);
        }
    }

    /**
     * Get user role from model type
     */
    private function getUserRole($user): string
    {
        if ($user instanceof \App\Models\AdminHelpdesk) {
            return 'admin_helpdesk';
        } elseif ($user instanceof \App\Models\AdminAplikasi) {
            return 'admin_aplikasi';
        } elseif ($user instanceof \App\Models\Teknisi) {
            return 'teknisi';
        } else {
            return 'user';
        }
    }

    /**
     * Extract device information from user agent
     */
    private function getDeviceInfo(Request $request): ?string
    {
        $userAgent = $request->userAgent();
        
        if (!$userAgent) {
            return null;
        }

        // Simple device detection
        $device = 'Desktop';
        if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
            $device = 'Mobile';
        } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
            $device = 'Tablet';
        }

        // Extract browser
        $browser = 'Unknown';
        if (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/edge/i', $userAgent)) {
            $browser = 'Edge';
        }

        return "$device - $browser";
    }
}
