<?php

namespace App\Services;

use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConcurrentSessionService
{
    /**
     * Maximum concurrent sessions per user
     */
    private const MAX_CONCURRENT_SESSIONS = 3;

    /**
     * Session cleanup interval in minutes
     */
    private const CLEANUP_INTERVAL = 15;

    /**
     * Track new session for user
     *
     * @param string $nip
     * @param string $sessionId
     * @param array $sessionData
     * @return bool True if session was created, false if max sessions reached
     */
    public function trackSession(string $nip, string $sessionId, array $sessionData): bool
    {
        try {
            // Clean up expired sessions first
            $this->cleanupExpiredSessions($nip);

            // Check current active sessions count
            $activeSessionsCount = $this->getActiveSessionsCount($nip);

            if ($activeSessionsCount >= self::MAX_CONCURRENT_SESSIONS) {
                // Log the rejection
                Log::warning('Maximum concurrent sessions reached', [
                    'nip' => $nip,
                    'session_id' => $sessionId,
                    'active_sessions' => $activeSessionsCount,
                    'max_allowed' => self::MAX_CONCURRENT_SESSIONS,
                    'timestamp' => Carbon::now()->toISOString()
                ]);

                return false;
            }

            // Store session tracking data
            $this->storeSessionTracking($nip, $sessionId, $sessionData);

            Log::info('New session tracked', [
                'nip' => $nip,
                'session_id' => $sessionId,
                'active_sessions' => $activeSessionsCount + 1,
                'timestamp' => Carbon::now()->toISOString()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to track session', [
                'nip' => $nip,
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Remove session tracking when session ends
     *
     * @param string $nip
     * @param string $sessionId
     * @return void
     */
    public function removeSession(string $nip, string $sessionId): void
    {
        try {
            // Remove from custom tracking (if implemented)
            // For now, we'll rely on Laravel's session cleanup

            Log::info('Session removed from tracking', [
                'nip' => $nip,
                'session_id' => $sessionId,
                'timestamp' => Carbon::now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to remove session tracking', [
                'nip' => $nip,
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get active sessions count for user
     *
     * @param string $nip
     * @return int
     */
    public function getActiveSessionsCount(string $nip): int
    {
        try {
            $sessionTimeout = 120; // 120 minutes as per PRD
            $expiredTime = Carbon::now()->subMinutes($sessionTimeout);

            // Count active sessions from user_sessions table (more reliable)
            $activeSessions = DB::table('user_sessions')
                ->where('nip', $nip)
                ->where('is_active', true)
                ->where('last_activity', '>', $expiredTime)
                ->count();

            return $activeSessions;
        } catch (\Exception $e) {
            Log::error('Failed to get active sessions count', [
                'nip' => $nip,
                'error' => $e->getMessage()
            ]);

            // Fallback to old method if user_sessions table doesn't exist yet
            try {
                $expiredTime = Carbon::now()->subMinutes(120)->timestamp;
                return DB::table('sessions')
                    ->where('last_activity', '>', $expiredTime)
                    ->where('payload', 'like', '%"nip";s:' . strlen($nip) . ':"' . $nip . '"%')
                    ->count();
            } catch (\Exception $fallbackError) {
                Log::error('Fallback session count also failed', [
                    'nip' => $nip,
                    'error' => $fallbackError->getMessage()
                ]);
                return 0;
            }
        }
    }

    /**
     * Get all active sessions for user with details
     *
     * @param string $nip
     * @return array
     */
    public function getActiveSessions(string $nip): array
    {
        try {
            $sessionTimeout = 120; // 120 minutes as per PRD
            $expiredTime = Carbon::now()->subMinutes($sessionTimeout);

            // Try to get sessions from user_sessions table first (more reliable)
            $sessions = DB::table('user_sessions')
                ->where('nip', $nip)
                ->where('is_active', true)
                ->where('last_activity', '>', $expiredTime)
                ->get(['session_id', 'last_activity', 'ip_address', 'user_agent', 'login_time']);

            $sessionDetails = [];
            foreach ($sessions as $session) {
                $sessionDetails[] = [
                    'session_id' => $session->session_id,
                    'last_activity' => $session->last_activity,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'login_time' => $session->login_time,
                    'is_current' => $session->session_id === session()->getId()
                ];
            }

            return $sessionDetails;
        } catch (\Exception $e) {
            Log::error('Failed to get active sessions from user_sessions table', [
                'nip' => $nip,
                'error' => $e->getMessage()
            ]);

            // Fallback to old method if user_sessions table doesn't exist yet
            try {
                $expiredTime = Carbon::now()->subMinutes($sessionTimeout)->timestamp;
                $sessions = DB::table('sessions')
                    ->where('last_activity', '>', $expiredTime)
                    ->where('payload', 'like', '%"nip";s:' . strlen($nip) . ':"' . $nip . '"%')
                    ->get(['id', 'last_activity', 'ip_address', 'user_agent', 'payload']);

                $sessionDetails = [];
                foreach ($sessions as $session) {
                    $payload = unserialize(base64_decode($session->payload));
                    $sessionDetails[] = [
                        'session_id' => $session->id,
                        'last_activity' => $session->last_activity,
                        'ip_address' => $session->ip_address ?? $payload['ip_address'] ?? 'unknown',
                        'user_agent' => $session->user_agent ?? $payload['user_agent'] ?? 'unknown',
                        'login_time' => $payload['login_time'] ?? 'unknown',
                        'is_current' => $session->id === session()->getId()
                    ];
                }

                return $sessionDetails;
            } catch (\Exception $fallbackError) {
                Log::error('Fallback session retrieval also failed', [
                    'nip' => $nip,
                    'error' => $fallbackError->getMessage()
                ]);
                return [];
            }
        }
    }

    /**
     * Force logout other sessions for user
     *
     * @param string $nip
     * @param string $currentSessionId
     * @return int Number of sessions terminated
     */
    public function terminateOtherSessions(string $nip, string $currentSessionId): int
    {
        try {
            $sessionTimeout = 120; // 120 minutes as per PRD
            $expiredTime = Carbon::now()->subMinutes($sessionTimeout);

            // Find and terminate other active sessions from user_sessions table
            $otherSessions = DB::table('user_sessions')
                ->where('nip', $nip)
                ->where('is_active', true)
                ->where('last_activity', '>', $expiredTime)
                ->where('session_id', '!=', $currentSessionId)
                ->get(['session_id']);

            $terminatedCount = 0;
            foreach ($otherSessions as $session) {
                // Mark session as inactive in user_sessions table
                DB::table('user_sessions')
                    ->where('session_id', $session->session_id)
                    ->update([
                        'is_active' => false,
                        'updated_at' => Carbon::now()
                    ]);

                $terminatedCount++;

                Log::info('Session terminated by user', [
                    'nip' => $nip,
                    'terminated_session_id' => $session->session_id,
                    'current_session_id' => $currentSessionId,
                    'timestamp' => Carbon::now()->toISOString()
                ]);
            }

            return $terminatedCount;
        } catch (\Exception $e) {
            Log::error('Failed to terminate other sessions', [
                'nip' => $nip,
                'current_session_id' => $currentSessionId,
                'error' => $e->getMessage()
            ]);

            // Fallback to old method if user_sessions table doesn't exist yet
            try {
                $expiredTime = Carbon::now()->subMinutes($sessionTimeout)->timestamp;
                $otherSessions = DB::table('sessions')
                    ->where('last_activity', '>', $expiredTime)
                    ->where('id', '!=', $currentSessionId)
                    ->where('payload', 'like', '%"nip";s:' . strlen($nip) . ':"' . $nip . '"%')
                    ->get(['id']);

                $terminatedCount = 0;
                foreach ($otherSessions as $session) {
                    DB::table('sessions')
                        ->where('id', $session->id)
                        ->update([
                            'payload' => base64_encode(serialize(['terminated' => true, 'terminated_at' => Carbon::now()])),
                            'updated_at' => Carbon::now()
                        ]);

                    $terminatedCount++;

                    Log::info('Session terminated by user (fallback)', [
                        'nip' => $nip,
                        'terminated_session_id' => $session->id,
                        'current_session_id' => $currentSessionId,
                        'timestamp' => Carbon::now()->toISOString()
                    ]);
                }

                return $terminatedCount;
            } catch (\Exception $fallbackError) {
                Log::error('Fallback termination also failed', [
                    'nip' => $nip,
                    'current_session_id' => $currentSessionId,
                    'error' => $fallbackError->getMessage()
                ]);
                return 0;
            }
        }
    }

    /**
     * Clean up expired sessions for user
     *
     * @param string $nip
     * @return int Number of sessions cleaned up
     */
    public function cleanupExpiredSessions(string $nip): int
    {
        try {
            $sessionTimeout = 120; // 120 minutes as per PRD
            $expiredTime = Carbon::now()->subMinutes($sessionTimeout);

            // Clean up expired sessions from user_sessions table
            $cleanedCount = DB::table('user_sessions')
                ->where('nip', $nip)
                ->where('last_activity', '<=', $expiredTime)
                ->delete();

            if ($cleanedCount > 0) {
                Log::info('Cleaned up expired sessions from user_sessions table', [
                    'nip' => $nip,
                    'cleaned_count' => $cleanedCount,
                    'timestamp' => Carbon::now()->toISOString()
                ]);
            }

            return $cleanedCount;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired sessions from user_sessions table', [
                'nip' => $nip,
                'error' => $e->getMessage()
            ]);

            // Fallback to old method if user_sessions table doesn't exist yet
            try {
                $expiredTime = Carbon::now()->subMinutes($sessionTimeout)->timestamp;
                $cleanedCount = DB::table('sessions')
                    ->where('last_activity', '<=', $expiredTime)
                    ->where('payload', 'like', '%"nip";s:' . strlen($nip) . ':"' . $nip . '"%')
                    ->delete();

                if ($cleanedCount > 0) {
                    Log::info('Cleaned up expired sessions (fallback)', [
                        'nip' => $nip,
                        'cleaned_count' => $cleanedCount,
                        'timestamp' => Carbon::now()->toISOString()
                    ]);
                }

                return $cleanedCount;
            } catch (\Exception $fallbackError) {
                Log::error('Fallback cleanup also failed', [
                    'nip' => $nip,
                    'error' => $fallbackError->getMessage()
                ]);
                return 0;
            }
        }
    }

    /**
     * Check if user can create new session
     *
     * @param string $nip
     * @return array [can_create => bool, reason => string]
     */
    public function canCreateSession(string $nip): array
    {
        $activeCount = $this->getActiveSessionsCount($nip);

        if ($activeCount >= self::MAX_CONCURRENT_SESSIONS) {
            return [
                'can_create' => false,
                'reason' => "Maximum concurrent sessions ({$activeCount}) reached. Please terminate other sessions first.",
                'active_sessions' => $activeCount,
                'max_allowed' => self::MAX_CONCURRENT_SESSIONS
            ];
        }

        return [
            'can_create' => true,
            'reason' => 'Session can be created',
            'active_sessions' => $activeCount,
            'max_allowed' => self::MAX_CONCURRENT_SESSIONS
        ];
    }

    /**
     * Store session tracking data (for future enhancement)
     *
     * @param string $nip
     * @param string $sessionId
     * @param array $sessionData
     * @return void
     */
    private function storeSessionTracking(string $nip, string $sessionId, array $sessionData): void
    {
        // For now, we'll rely on Laravel's built-in session tracking
        // This method can be enhanced later to store additional tracking data

        // Update session with tracking information
        $trackingData = [
            'tracked_at' => Carbon::now(),
            'concurrent_sessions' => $this->getActiveSessionsCount($nip) + 1,
            'max_concurrent_sessions' => self::MAX_CONCURRENT_SESSIONS
        ];

        // Store in session for easy access
        session($trackingData);
    }

    /**
     * Get session info by session ID
     *
     * @param string $sessionId
     * @return object|null
     */
    public function getSessionInfo(string $sessionId): ?object
    {
        try {
            return DB::table('user_sessions')
                ->where('session_id', $sessionId)
                ->where('is_active', true)
                ->first();
        } catch (\Exception $e) {
            Log::error('Failed to get session info', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Check if session is valid (active AND not expired)
     *
     * @param string $sessionId
     * @return bool
     */
    public function isSessionValid(string $sessionId): bool
    {
        try {
            $session = DB::table('user_sessions')
                ->where('session_id', $sessionId)
                ->where('is_active', true)
                ->first();

            if (!$session) {
                return false;
            }

            // Check if session has expired
            $expiresAt = Carbon::parse($session->expires_at);
            if (Carbon::now()->gt($expiresAt)) {
                // Mark session as inactive since it's expired
                $this->terminateSession($sessionId);
                
                Log::info('Session expired and terminated', [
                    'session_id' => $sessionId,
                    'expires_at' => $expiresAt->toISOString(),
                    'current_time' => Carbon::now()->toISOString()
                ]);
                
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to validate session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get session with expiry info
     *
     * @param string $sessionId
     * @return array|null
     */
    public function getSessionWithExpiry(string $sessionId): ?array
    {
        try {
            $session = DB::table('user_sessions')
                ->where('session_id', $sessionId)
                ->where('is_active', true)
                ->first();

            if (!$session) {
                return null;
            }

            $expiresAt = Carbon::parse($session->expires_at);
            $now = Carbon::now();
            $isExpired = $now->gt($expiresAt);
            $minutesRemaining = $isExpired ? 0 : $now->diffInMinutes($expiresAt, false);
            $secondsRemaining = $isExpired ? 0 : $now->diffInSeconds($expiresAt, false);

            return [
                'session_id' => $session->session_id,
                'nip' => $session->nip,
                'user_role' => $session->user_role,
                'is_active' => (bool) $session->is_active,
                'expires_at' => $expiresAt->toISOString(),
                'last_activity' => $session->last_activity,
                'is_expired' => $isExpired,
                'minutes_remaining' => max(0, $minutesRemaining),
                'seconds_remaining' => max(0, $secondsRemaining),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get session with expiry', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Mark all expired sessions as inactive
     *
     * @return int Number of sessions marked inactive
     */
    public function markExpiredSessionsInactive(): int
    {
        try {
            $now = Carbon::now();
            
            $expiredCount = DB::table('user_sessions')
                ->where('is_active', true)
                ->where('expires_at', '<=', $now)
                ->update([
                    'is_active' => false,
                    'updated_at' => $now
                ]);

            if ($expiredCount > 0) {
                Log::info('Marked expired sessions as inactive', [
                    'count' => $expiredCount,
                    'timestamp' => $now->toISOString()
                ]);
            }

            return $expiredCount;
        } catch (\Exception $e) {
            Log::error('Failed to mark expired sessions inactive', [
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Update session activity timestamp
     *
     * @param string $sessionId
     * @return bool
     */
    public function updateSessionActivity(string $sessionId): bool
    {
        try {
            $updated = DB::table('user_sessions')
                ->where('session_id', $sessionId)
                ->update([
                    'last_activity' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

            return $updated > 0;
        } catch (\Exception $e) {
            Log::error('Failed to update session activity', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Extend session expiry time
     *
     * @param string $sessionId
     * @param Carbon $expiresAt
     * @return bool
     */
    public function extendSession(string $sessionId, Carbon $expiresAt): bool
    {
        try {
            $updated = DB::table('user_sessions')
                ->where('session_id', $sessionId)
                ->update([
                    'expires_at' => $expiresAt,
                    'last_activity' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

            return $updated > 0;
        } catch (\Exception $e) {
            Log::error('Failed to extend session', [
                'session_id' => $sessionId,
                'expires_at' => $expiresAt->toISOString(),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get all sessions for a user (alias for getActiveSessions for API compatibility)
     *
     * @param string $nip
     * @return array
     */
    public function getUserSessions(string $nip): array
    {
        return $this->getActiveSessions($nip);
    }

    /**
     * Terminate specific session
     *
     * @param string $sessionId
     * @return bool
     */
    public function terminateSession(string $sessionId): bool
    {
        try {
            // Get session info first to log NIP
            $sessionInfo = $this->getSessionInfo($sessionId);

            $updated = DB::table('user_sessions')
                ->where('session_id', $sessionId)
                ->update([
                    'is_active' => false,
                    'updated_at' => Carbon::now()
                ]);

            if ($updated > 0 && $sessionInfo) {
                Log::info('Session terminated', [
                    'session_id' => $sessionId,
                    'nip' => $sessionInfo->nip,
                    'timestamp' => Carbon::now()->toISOString()
                ]);
            }

            return $updated > 0;
        } catch (\Exception $e) {
            Log::error('Failed to terminate session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Terminate all sessions for a user
     *
     * @param string $nip
     * @return int Number of sessions terminated
     */
    public function terminateAllUserSessions(string $nip): int
    {
        try {
            $terminatedCount = DB::table('user_sessions')
                ->where('nip', $nip)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'updated_at' => Carbon::now()
                ]);

            if ($terminatedCount > 0) {
                Log::info('All user sessions terminated', [
                    'nip' => $nip,
                    'terminated_count' => $terminatedCount,
                    'timestamp' => Carbon::now()->toISOString()
                ]);
            }

            return $terminatedCount;
        } catch (\Exception $e) {
            Log::error('Failed to terminate all user sessions', [
                'nip' => $nip,
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Get concurrent session configuration
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return [
            'max_concurrent_sessions' => self::MAX_CONCURRENT_SESSIONS,
            'cleanup_interval' => self::CLEANUP_INTERVAL,
            'session_timeout' => 120
        ];
    }
}