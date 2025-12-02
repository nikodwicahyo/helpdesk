<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Services\AuthService;
use App\Services\ConcurrentSessionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class SessionController extends Controller
{
    protected $authService;
    protected $concurrentSessionService;

    public function __construct(AuthService $authService, ConcurrentSessionService $concurrentSessionService)
    {
        $this->authService = $authService;
        $this->concurrentSessionService = $concurrentSessionService;
    }

    /**
     * Get current session status
     * DATABASE-VALIDATED: Uses user_sessions table as primary source of truth
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $sessionId = Session::getId();

            if (!$user) {
                return response()->json([
                    'authenticated' => false,
                    'session_active' => false,
                    'message' => 'No active session'
                ]);
            }

            // PRIMARY CHECK: Validate against database user_sessions table
            $dbSession = $this->concurrentSessionService->getSessionWithExpiry($sessionId);
            
            if (!$dbSession) {
                // Session not found in database - but user IS authenticated via Laravel
                // This can happen if session tracking failed during login
                // Create the session entry now to sync state
                Log::info('Session not found in database, creating entry for authenticated user', [
                    'session_id' => $sessionId,
                    'user_nip' => $user->nip
                ]);
                
                $dbSession = $this->createMissingSessionEntry($user, $sessionId, $request);
                
                if (!$dbSession) {
                    // Failed to create session entry - something is wrong
                    return response()->json([
                        'authenticated' => false,
                        'session_active' => false,
                        'message' => 'Session tracking failed'
                    ], 500);
                }
            }

            // Check if database session is expired
            if ($dbSession['is_expired']) {
                Log::info('Database session expired', [
                    'session_id' => $sessionId,
                    'user_nip' => $user->nip,
                    'expires_at' => $dbSession['expires_at']
                ]);
                
                // Mark session as inactive
                $this->concurrentSessionService->terminateSession($sessionId);
                
                return response()->json([
                    'authenticated' => false,
                    'session_active' => false,
                    'message' => 'Session expired'
                ], 401);
            }

            $minutesRemaining = $dbSession['minutes_remaining'];
            $secondsRemaining = $dbSession['seconds_remaining'];
            $sessionTimeout = Config::get('helpdesk.security.session_timeout_minutes', 120) * 60;

            // Calculate expiry and warning timestamps from database
            $expiresAt = Carbon::parse($dbSession['expires_at']);
            $warningAt = $expiresAt->copy()->subMinutes(10);

            // Store expiry info in Laravel session for middleware access
            session([
                'session_expires_at' => $expiresAt->timestamp,
                'session_warning_at' => $warningAt->timestamp,
                'minutes_remaining' => $minutesRemaining,
            ]);

            // Log only in debug mode to reduce log spam
            if (app()->isLocal()) {
                Log::debug('Session status check successful', [
                    'session_id' => $sessionId,
                    'user_nip' => $user->nip,
                    'minutes_remaining' => $minutesRemaining,
                    'expires_at' => $expiresAt->toISOString()
                ]);
            }

            return response()->json([
                'authenticated' => true,
                'session_active' => true,
                'user' => [
                    'nip' => $user->nip,
                    'name' => $user->name,
                    'role' => $this->getUserRole($user),
                    'email' => $user->email ?? null,
                ],
                'session' => [
                    'id' => $sessionId,
                    'last_activity' => $dbSession['last_activity'],
                    'expires_at' => $expiresAt->toISOString(),
                    'warning_at' => $warningAt->toISOString(),
                    'minutes_remaining' => $minutesRemaining,
                    'seconds_remaining' => $secondsRemaining,
                    'timeout_minutes' => round($sessionTimeout / 60)
                ],
                'message' => 'Session active'
            ]);

        } catch (\Exception $e) {
            Log::error('Session status check failed', [
                'error' => $e->getMessage(),
                'session_id' => Session::getId(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'authenticated' => false,
                'session_active' => false,
                'message' => 'Error checking session status'
            ], 500);
        }
    }

    /**
     * Extend current session by resetting expiry time
     * DATABASE-VALIDATED: Updates both Laravel session and user_sessions table
     */
    public function extend(Request $request): JsonResponse
    {
        try {
            $sessionId = Session::getId();
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active session to extend'
                ], 401);
            }

            // Check if session is still valid in database
            if (!$this->concurrentSessionService->isSessionValid($sessionId)) {
                return response()->json([
                    'success' => false,
                    'authenticated' => false,
                    'message' => 'Session expired or invalid'
                ], 401);
            }

            $currentTime = time();
            $sessionTimeoutMinutes = Config::get('helpdesk.security.session_timeout_minutes', 120);
            $sessionTimeout = $sessionTimeoutMinutes * 60;

            // Calculate new expiry times from now (full timeout period)
            $expiresAt = Carbon::now()->addMinutes($sessionTimeoutMinutes);
            $warningAt = $expiresAt->copy()->subMinutes(10);

            // Update Laravel session
            session([
                '_last_activity' => $currentTime,
                'session_expires_at' => $expiresAt->timestamp,
                'session_warning_at' => $warningAt->timestamp,
                'minutes_remaining' => $sessionTimeoutMinutes,
            ]);

            // Update database user_sessions table with new expiry
            $this->concurrentSessionService->extendSession($sessionId, $expiresAt);

            Log::info('Session extended successfully', [
                'session_id' => $sessionId,
                'user_nip' => $user->nip,
                'new_expires_at' => $expiresAt->toISOString(),
                'extended_by_user' => true
            ]);

            return response()->json([
                'success' => true,
                'expires_at' => $expiresAt->toISOString(),
                'warning_at' => $warningAt->toISOString(),
                'minutes_remaining' => $sessionTimeoutMinutes,
                'session' => [
                    'id' => $sessionId,
                    'last_activity' => $currentTime,
                    'expires_at' => $expiresAt->toISOString(),
                    'warning_at' => $warningAt->toISOString(),
                    'minutes_remaining' => $sessionTimeoutMinutes,
                    'seconds_remaining' => $sessionTimeout
                ],
                'message' => 'Session extended successfully for ' . $sessionTimeoutMinutes . ' minutes'
            ]);

        } catch (\Exception $e) {
            Log::error('Session extension failed', [
                'error' => $e->getMessage(),
                'session_id' => Session::getId(),
                'user_nip' => Auth::user()->nip ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to extend session'
            ], 500);
        }
    }

    /**
     * Get all active sessions for current user
     */
    public function sessions(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            $sessions = $this->concurrentSessionService->getUserSessions($user->nip);

            // Convert array to collection for mapping, or handle as array
            $sessionsData = [];
            foreach ($sessions as $session) {
                $sessionsData[] = [
                    'id' => $session['session_id'],
                    'last_activity' => $session['last_activity'],
                    'expires_at' => $session['expires_at'],
                    'is_current' => $session['session_id'] === Session::getId(),
                    'minutes_remaining' => Carbon::now()->diffInMinutes(Carbon::parse($session['expires_at']), false)
                ];
            }

            return response()->json([
                'success' => true,
                'sessions' => $sessionsData,
                'total_sessions' => count($sessionsData),
                'message' => 'Sessions retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Get user sessions failed', [
                'error' => $e->getMessage(),
                'user_nip' => Auth::user()->nip ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sessions'
            ], 500);
        }
    }

    /**
     * Terminate specific session
     */
    public function terminate(Request $request, string $sessionId): JsonResponse
    {
        try {
            $currentSessionId = Session::getId();

            if ($sessionId === $currentSessionId) {
                // Terminating current session - logout
                Auth::logout();
                Session::invalidate();
                Session::regenerateToken();

                return response()->json([
                    'success' => true,
                    'terminated_current' => true,
                    'message' => 'Current session terminated'
                ]);
            }

            // Terminate other session
            $this->concurrentSessionService->terminateSession($sessionId);

            return response()->json([
                'success' => true,
                'terminated_current' => false,
                'message' => 'Session terminated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Session termination failed', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'current_session' => Session::getId()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to terminate session'
            ], 500);
        }
    }

    /**
     * Terminate all sessions for current user
     */
    public function terminateAll(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            $currentSessionId = Session::getId();
            $userNip = $user->nip;

            // Terminate all sessions for this user in database first
            $terminatedCount = $this->concurrentSessionService->terminateAllUserSessions($userNip);

            // Logout current session
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();

            return response()->json([
                'success' => true,
                'terminated_sessions' => true,
                'terminated_count' => $terminatedCount,
                'message' => 'All sessions terminated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Terminate all sessions failed', [
                'error' => $e->getMessage(),
                'user_nip' => Auth::user()->nip ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to terminate all sessions'
            ], 500);
        }
    }

    /**
     * Get session configuration
     */
    public function config(Request $request): JsonResponse
    {
        try {
            $sessionTimeoutMinutes = Config::get('helpdesk.security.session_timeout_minutes', 120);
            $warningTimeMinutes = 10; // 10 minutes before expiry

            return response()->json([
                'success' => true,
                'session_timeout_minutes' => $sessionTimeoutMinutes,
                'session_timeout_seconds' => $sessionTimeoutMinutes * 60,
                'warning_time_minutes' => $warningTimeMinutes,
                'warning_time_seconds' => $warningTimeMinutes * 60,
                'check_interval_seconds' => 30, // Check every 30 seconds
                'auto_extend_threshold_minutes' => 5 // Auto-extend if less than 5 minutes left
            ]);
        } catch (\Exception $e) {
            Log::error('Session config retrieval failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve session configuration'
            ], 500);
        }
    }

    /**
     * Get user role from user model
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
     * Create missing session entry in user_sessions table
     * This handles the case where user is authenticated but session wasn't tracked
     */
    private function createMissingSessionEntry($user, string $sessionId, $request): ?array
    {
        try {
            $now = Carbon::now();
            $sessionTimeoutMinutes = (int) Config::get('helpdesk.security.session_timeout_minutes', 120);
            $expiresAt = $now->copy()->addMinutes($sessionTimeoutMinutes);
            $userRole = $this->getUserRole($user);

            // Insert new session entry
            \Illuminate\Support\Facades\DB::table('user_sessions')->insert([
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
                'login_time' => $now,
                'last_activity' => $now,
                'expires_at' => $expiresAt,
                'is_active' => true,
                'device_info' => $this->getDeviceInfo($request),
                'location_info' => $request->ip(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            Log::info('Created missing session entry', [
                'session_id' => $sessionId,
                'nip' => $user->nip,
                'role' => $userRole,
                'expires_at' => $expiresAt->toISOString()
            ]);

            // Return the session data in the expected format
            return [
                'session_id' => $sessionId,
                'nip' => $user->nip,
                'user_role' => $userRole,
                'is_active' => true,
                'expires_at' => $expiresAt->toISOString(),
                'last_activity' => $now->toISOString(),
                'is_expired' => false,
                'minutes_remaining' => $sessionTimeoutMinutes,
                'seconds_remaining' => $sessionTimeoutMinutes * 60,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create missing session entry', [
                'session_id' => $sessionId,
                'nip' => $user->nip,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Extract device info from request
     */
    private function getDeviceInfo($request): ?string
    {
        $userAgent = $request->userAgent();
        
        if (!$userAgent) {
            return null;
        }

        if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
            return 'Mobile';
        } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'Tablet';
        }
        
        return 'Desktop';
    }
}