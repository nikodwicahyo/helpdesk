<?php

namespace App\Services;

use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use App\Models\User;
use App\Services\ConcurrentSessionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

class AuthService
{
    private ConcurrentSessionService $concurrentSessionService;

    /**
     * Authentication table priority order (highest to lowest)
     */
    private const TABLE_PRIORITY = [
        AdminHelpdesk::class,
        AdminAplikasi::class,
        Teknisi::class,
        User::class,
    ];

    /**
     * Rate limiting configuration
     */
    private const MAX_ATTEMPTS = 5;
    private const DECAY_MINUTES = 15;
    private const LOCKOUT_MINUTES = 30;

    /**
     * Session timeout in minutes (as per PRD)
     */
    private const SESSION_TIMEOUT = 120;

    /**
     * Session warning time in minutes (10 minutes before expiry)
     */
    private const SESSION_WARNING_TIME = 10;

    /**
     * Maximum concurrent sessions per user
     */
    private const MAX_CONCURRENT_SESSIONS = 3;

    public function __construct(ConcurrentSessionService $concurrentSessionService)
    {
        $this->concurrentSessionService = $concurrentSessionService;
    }

    /**
     * Attempt login with NIP and password
     */
    public function attemptLogin(string $nip, string $password, ?string $ipAddress = null): array
    {
        // Rate limiting check
        if ($this->isRateLimited($nip, $ipAddress)) {
            $this->logLoginAttempt($nip, false, 'rate_limited', $ipAddress);
            return [
                'success' => false,
                'message' => 'Too many login attempts. Please try again later.',
                'error_code' => 'RATE_LIMITED'
            ];
        }

        // Find user across all tables
        $user = $this->getUserByNip($nip);

        if (!$user) {
            $this->handleFailedAttempt($nip, $ipAddress);
            return [
                'success' => false,
                'message' => 'Invalid NIP or password.',
                'error_code' => 'INVALID_CREDENTIALS'
            ];
        }

        // Check if user is active (all tables now use 'status' field)
        if (isset($user->status) && $user->status !== 'active') {
            $this->logLoginAttempt($nip, false, 'account_inactive', $ipAddress);
            return [
                'success' => false,
                'message' => 'Akun tidak aktif. Silakan hubungi administrator.',
                'error_code' => 'ACCOUNT_INACTIVE'
            ];
        }

        // Validate password
        if (!$this->validateCredentials($user, $password)) {
            $this->handleFailedAttempt($nip, $ipAddress);
            return [
                'success' => false,
                'message' => 'Invalid NIP or password.',
                'error_code' => 'INVALID_CREDENTIALS'
            ];
        }

        return $this->completeLogin($user, $nip, $ipAddress);
    }

    /**
     * Find user by NIP across all authentication tables
     */
    public function getUserByNip(string $nip)
    {
        // Maintain original priority order for backward compatibility
        foreach (self::TABLE_PRIORITY as $modelClass) {
            $user = $modelClass::where('nip', $nip)->first();
            if ($user) {
                return $user;
            }
        }
        return null;
    }

    /**
     * Find user by ID across all authentication tables
     */
    public function getUserById($id)
    {
        // Maintain original priority order for backward compatibility
        foreach (self::TABLE_PRIORITY as $modelClass) {
            $user = $modelClass::find($id);
            if ($user) {
                return $user;
            }
        }
        return null;
    }

    /**
     * Validate password against user's hash
     */
    public function validateCredentials($user, string $password): bool
    {
        return password_verify($password, $user->password);
    }

    /**
     * Get user role from database field
     */
    public function getUserRole($user): string
    {
        // Use role field from database if available
        if (isset($user->role) && !empty($user->role)) {
            return $user->role;
        }

        // Fallback to instanceof checks for backward compatibility
        if ($user instanceof AdminHelpdesk) {
            return 'admin_helpdesk';
        } elseif ($user instanceof AdminAplikasi) {
            return 'admin_aplikasi';
        } elseif ($user instanceof Teknisi) {
            return 'teknisi';
        } elseif ($user instanceof User) {
            return 'user';
        }

        // Default fallback
        return 'user';
    }

    /**
     * Update last login timestamp - FIXED for remember me functionality
     */
    public function updateLastLogin($user): void
    {
        try {
            // Try to update last_login_at if it's a fillable field and exists in database
            if ($user->isFillable('last_login_at')) {
                $user->update(['last_login_at' => Carbon::now()]);
                Log::info('Updated last_login_at', [
                    'nip' => $user->nip,
                    'user_class' => get_class($user)
                ]);
            } else {
                // Just touch updated_at timestamp if last_login_at is not available
                $user->touch();
                Log::info('Touched user updated_at timestamp', [
                    'nip' => $user->nip,
                    'user_class' => get_class($user)
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update last login timestamp', [
                'nip' => $user->nip,
                'user_class' => get_class($user),
                'error' => $e->getMessage()
            ]);

            // Final fallback - just touch the model
            try {
                $user->touch();
            } catch (\Exception $touchException) {
                Log::error('Failed to touch user model', [
                    'nip' => $user->nip,
                    'error' => $touchException->getMessage()
                ]);
            }
        }
    }

    /**
     * Handle failed login attempt with rate limiting and logging
     */
    public function handleFailedAttempt(string $nip, ?string $ipAddress = null): void
    {
        $key = $this->getRateLimitKey($nip, $ipAddress);
        RateLimiter::hit($key, self::DECAY_MINUTES * 60);
        $this->logLoginAttempt($nip, false, 'failed', $ipAddress);
    }

    /**
     * Clear rate limiting for successful login
     */
    public function clearRateLimit(string $nip, ?string $ipAddress = null): void
    {
        $key = $this->getRateLimitKey($nip, $ipAddress);
        RateLimiter::clear($key);
    }

    /**
     * Check if user is rate limited
     */
    public function isRateLimited(string $nip, ?string $ipAddress = null): bool
    {
        $key = $this->getRateLimitKey($nip, $ipAddress);
        return RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS);
    }

    /**
     * Check if user is locked out (alias for isRateLimited for backward compatibility)
     */
    public function isLockedOut(string $nip, ?string $ipAddress = null): bool
    {
        return $this->isRateLimited($nip, $ipAddress);
    }

    /**
     * Get remaining attempts before lockout
     */
    public function getAttemptsRemaining(string $nip, ?string $ipAddress = null): int
    {
        $key = $this->getRateLimitKey($nip, $ipAddress);
        $attempts = RateLimiter::attempts($key);
        return max(0, self::MAX_ATTEMPTS - $attempts);
    }

    /**
     * Get remaining lockout time in seconds
     */
    public function getLockoutTimeRemaining(string $nip, ?string $ipAddress = null): int
    {
        $key = $this->getRateLimitKey($nip, $ipAddress);
        return RateLimiter::availableIn($key);
    }

    /**
     * Generate rate limiting key
     */
    private function getRateLimitKey(string $nip, ?string $ipAddress = null): string
    {
        return 'login_attempt:' . md5($nip . '|' . ($ipAddress ?? ''));
    }

    /**
     * Get session timeout in minutes
     */
    public function getSessionTimeout(): int
    {
        return self::SESSION_TIMEOUT;
    }

    /**
     * Check if current session is expired
     */
    public function isSessionExpired(int $lastActivity): bool
    {
        $timeout = $this->getSessionTimeout() * 60; // Convert to seconds
        return (time() - $lastActivity) > $timeout;
    }

    /**
     * Get user permissions based on role
     */
    public function getUserPermissions($user): array
    {
        $role = $this->getUserRole($user);

        $permissions = match($role) {
            'admin_helpdesk' => [
                'manage_tickets',
                'assign_tickets',
                'view_reports',
                'manage_users',
                'system_settings'
            ],
            'admin_aplikasi' => [
                'manage_applications',
                'assign_teknisi',
                'view_reports',
                'manage_categories'
            ],
            'teknisi' => [
                'view_assigned_tickets',
                'update_ticket_status',
                'add_ticket_comments',
                'view_knowledge_base'
            ],
            'user' => [
                'create_tickets',
                'view_own_tickets',
                'add_ticket_comments'
            ]
        };

        // Merge with user-specific permissions if available
        if (isset($user->permissions) && is_array($user->permissions)) {
            $permissions = array_merge($permissions, $user->permissions);
        }

        return array_unique($permissions);
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission($user, string $permission): bool
    {
        $permissions = $this->getUserPermissions($user);
        return in_array($permission, $permissions);
    }

    /**
     * Get user's full name based on role
     */
    private function getUserFullName($user): string
    {
        // Try user's name field first
        if (!empty($user->name)) {
            return $user->name;
        }

        // Fallback to NIP if name is empty
        return $user->nip ?? 'Unknown User';
    }

    /**
     * Log login attempts for debugging
     */
    private function logLoginAttempt(string $nip, bool $success, string $reason = 'login', ?string $ipAddress = null): void
    {
        Log::info('Login attempt', [
            'nip' => $nip,
            'success' => $success,
            'reason' => $reason,
            'ip_address' => $ipAddress,
            'timestamp' => now()->toISOString(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Create comprehensive session data for multi-role authentication
     */
    public function createSessionData($user, string $ipAddress, string $userAgent): array
    {
        $role = $this->getUserRole($user);
        $fullName = $this->getUserFullName($user);
        $now = Carbon::now();

        $sessionData = [
            'user_id' => $user->getKey(), // Use primary key instead of assuming 'id'
            'user_role' => $role,
            'user_name' => $fullName,
            'nip' => $user->nip,
            'login_time' => $now->toISOString(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'session_id' => session()->getId(),
            'permissions' => $this->getUserPermissions($user),
            'last_activity' => $now->timestamp,
            'expires_at' => $now->addMinutes(self::SESSION_TIMEOUT)->timestamp,
            'is_active' => true
        ];

        return $sessionData;
    }

    /**
     * Validate session data integrity
     */
    public function validateSessionData(array $sessionData): bool
    {
        $requiredKeys = ['user_id', 'user_role', 'nip', 'login_time'];
        foreach ($requiredKeys as $key) {
            if (!isset($sessionData[$key])) {
                Log::warning('Session data validation failed: missing key', [
                    'missing_key' => $key,
                    'session_id' => session()->getId()
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Store session data in user_sessions table for tracking and concurrent session management
     */
    public function storeUserSession(array $sessionData): void
    {
        try {
            $now = Carbon::now();
            
            // Validate required fields
            if (empty($sessionData['session_id'])) {
                throw new \Exception('Session ID is empty or missing');
            }
            
            if (empty($sessionData['nip'])) {
                throw new \Exception('NIP is empty or missing');
            }
            
            // Prepare data for user_sessions table
            $userSessionData = [
                'session_id' => $sessionData['session_id'],
                'nip' => $sessionData['nip'],
                'user_role' => $sessionData['user_role'],
                'ip_address' => $sessionData['ip_address'] ?? null,
                'user_agent' => $sessionData['user_agent'] ?? null,
                'session_data' => json_encode($sessionData),
                'login_time' => $now,
                'last_activity' => $now,
                'expires_at' => $now->copy()->addMinutes(self::SESSION_TIMEOUT),
                'is_active' => true,
                'device_info' => $this->extractDeviceInfo($sessionData['user_agent'] ?? ''),
                'location_info' => $sessionData['ip_address'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (app()->isLocal()) {
                Log::debug('Attempting to store session in user_sessions table', [
                    'session_id' => $sessionData['session_id'],
                    'nip' => $sessionData['nip'],
                    'role' => $sessionData['user_role'],
                    'ip_address' => $userSessionData['ip_address'],
                    'device_info' => $userSessionData['device_info']
                ]);
            }

            // Insert or update session in user_sessions table
            DB::table('user_sessions')
                ->updateOrInsert(
                    ['session_id' => $sessionData['session_id']],
                    $userSessionData
                );

            Log::info('Session stored in user_sessions table', [
                'session_id' => $sessionData['session_id'],
                'nip' => $sessionData['nip'],
                'role' => $sessionData['user_role']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store session in user_sessions table', [
                'session_id' => $sessionData['session_id'] ?? 'unknown',
                'nip' => $sessionData['nip'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw the exception to make it visible during testing
            if (app()->isLocal()) {
                throw $e;
            }
        }
    }

    /**
     * Extract device info from user agent string
     */
    private function extractDeviceInfo(string $userAgent): ?string
    {
        if (empty($userAgent)) {
            return null;
        }

        // Simple device detection
        if (preg_match('/mobile|android|iphone|ipad|ipod/i', $userAgent)) {
            return 'Mobile';
        } elseif (preg_match('/tablet/i', $userAgent)) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }

    /**
     * Update session activity tracking in both sessions and user_sessions tables
     * 
     * IMPORTANT: This method only updates last_activity, NOT expires_at
     * Session expiry is managed by SessionTrackingMiddleware based on user activity type
     */
    public function updateSessionActivity(?string $sessionId = null): void
    {
        $sessionId = $sessionId ?? session()->getId();
        $currentTime = time(); // Use integer seconds to match database column type
        $now = Carbon::now();

        session([
            'last_activity' => $currentTime,
            'session_id' => $sessionId
        ]);

        // Update in sessions table for persistent tracking
        try {
            DB::table('sessions')
                ->where('id', $sessionId)
                ->update([
                    'last_activity' => $currentTime // Integer timestamp for sessions.last_activity column
                ]);
        } catch (\Exception $e) {
            Log::warning('Failed to update session activity in sessions table', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }

        // Update in user_sessions table - only last_activity, NOT expires_at
        // expires_at is managed by SessionTrackingMiddleware based on route type
        try {
            DB::table('user_sessions')
                ->where('session_id', $sessionId)
                ->update([
                    'last_activity' => $now,
                    // DO NOT update expires_at here - let SessionTrackingMiddleware handle it
                    'updated_at' => $now
                ]);
        } catch (\Exception $e) {
            Log::warning('Failed to update session activity in user_sessions table', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clean up expired sessions for a user
     */
    public function cleanupExpiredSessions(string $nip): int
    {
        try {
            $expiredTime = Carbon::now()->subMinutes(self::SESSION_TIMEOUT)->timestamp;
            $cleanedCount = DB::table('sessions')
                ->where('last_activity', '<', $expiredTime)
                ->where('payload', 'like', '%"nip";s:' . strlen($nip) . ':"' . $nip . '"%')
                ->delete();

            if ($cleanedCount > 0) {
                Log::info('Cleaned up expired sessions', [
                    'nip' => $nip,
                    'cleaned_count' => $cleanedCount,
                    'expired_before' => $expiredTime
                ]);
            }

            return $cleanedCount;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired sessions', [
                'nip' => $nip,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get session warning time in minutes
     */
    public function getSessionWarningTime(): int
    {
        return self::SESSION_WARNING_TIME;
    }

    /**
     * Check if session is near expiry (within warning time)
     */
    public function isSessionNearExpiry(int $lastActivity): bool
    {
        $warningTime = (self::SESSION_TIMEOUT - self::SESSION_WARNING_TIME) * 60; // Convert to seconds
        return (time() - $lastActivity) > $warningTime;
    }

    /**
     * Regenerate session ID for security
     */
    public function regenerateSession(): string
    {
        $oldSessionId = session()->getId();
        
        // Regenerate the session ID (returns bool, not new ID)
        session()->regenerate();
        
        $newSessionId = session()->getId();

        // Update session tracking
        $this->updateSessionInDatabase($oldSessionId, [
            'migrated_to' => $newSessionId,
            'migrated_at' => Carbon::now()
        ]);

        return $newSessionId;
    }

    /**
     * Complete login process with session creation and user authentication
     */
    private function completeLogin($user, string $nip, ?string $ipAddress = null): array
    {
        // Check concurrent session limits
        $sessionCheck = $this->concurrentSessionService->canCreateSession($nip);
        if (!$sessionCheck['can_create']) {
            return [
                'success' => false,
                'message' => $sessionCheck['reason'],
                'error_code' => 'MAX_SESSIONS_REACHED',
                'active_sessions' => $sessionCheck['active_sessions']
            ];
        }

        // Attempt authentication - this is the authoritative step
        try {
            Auth::guard('web')->login($user, true);

            if (!Auth::guard('web')->check()) {
                Log::error('Web guard authentication failed after login', [
                    'nip' => $nip,
                    'user_class' => get_class($user)
                ]);

                return [
                    'success' => false,
                    'message' => 'Authentication failed. Please try again.',
                    'error_code' => 'AUTH_FAILED'
                ];
            }

            // Create and store unified session data
            $userRole = $this->getUserRole($user);
            $sessionData = $this->createSessionData($user, $ipAddress, request()->userAgent());

            // Standardize on single user_session array structure
            session(['user_session' => $sessionData]);

            // Initialize session start time for timeout middleware
            session(['_session_start_time' => time()]);

            // Store session in user_sessions table for tracking
            $this->storeUserSession($sessionData);

            // Additional session setup
            $this->updateSessionActivity();
            $this->clearRateLimit($nip, $ipAddress);

            // Update last login timestamp
            $this->updateLastLogin($user);

            Log::info('User logged in successfully', [
                'nip' => $nip,
                'user_id' => $user->getKey(),
                'role' => $userRole,
                'session_id' => session()->getId()
            ]);

            // Get user permissions
            $permissions = $this->getUserPermissions($user);

            // Prepare user data for frontend
            $userData = [
                'id' => $user->id,
                'nip' => $user->nip,
                'name' => $user->name ?? $user->nip,
                'email' => $user->email,
                'role' => $userRole
            ];

            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $userData,
                'role' => $userRole,
                'permissions' => $permissions,
                'session_info' => $sessionData
            ];

        } catch (\Exception $e) {
            Log::error('Login completion failed', [
                'nip' => $nip,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Login failed due to system error.',
                'error_code' => 'SYSTEM_ERROR'
            ];
        }
    }

    /**
     * Get session expiry timestamp
     */
    public function getSessionExpiryTime(?int $lastActivityTimestamp = null): int
    {
        $lastActivity = $lastActivityTimestamp ?? session('last_activity', time());
        $timeoutSeconds = self::SESSION_TIMEOUT * 60; // Convert minutes to seconds
        return $lastActivity + $timeoutSeconds;
    }

    /**
     * Get session time remaining in minutes
     */
    public function getSessionTimeRemaining(?int $lastActivityTimestamp = null): int
    {
        $expiryTime = $this->getSessionExpiryTime($lastActivityTimestamp);
        $remainingSeconds = $expiryTime - time();
        return (int) max(0, ceil($remainingSeconds / 60));
    }

    /**
     * Update session in database with additional data
     */
    private function updateSessionInDatabase(string $sessionId, array $data): void
    {
        try {
            DB::table('sessions')
                ->where('id', $sessionId)
                ->update($data);
        } catch (\Exception $e) {
            Log::warning('Failed to update session in database', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get concurrent session management service
     */
    public function getConcurrentSessionService(): ConcurrentSessionService
    {
        return $this->concurrentSessionService;
    }

    /**
     * Get current authenticated user from auth guard
     * Always uses the web guard as primary source of truth.
     * Session reconstruction is only used as last resort and immediately re-authenticates the user.
     *
     * @return mixed|null
     */
    public function getCurrentAuthenticatedUser()
    {
        // Primary source: Auth::guard('web')->user() - this is authoritative
        $user = Auth::guard('web')->user();
        if ($user) {
            return $user;
        }

        // Last resort: session reconstruction only if web guard fails
        // After successful reconstruction, immediately re-login to web guard
        $userSession = session('user_session');
        if ($userSession && isset($userSession['user_id'])) {
            foreach (self::TABLE_PRIORITY as $modelClass) {
                $user = $modelClass::find($userSession['user_id']);
                if ($user && isset($user->status) && $user->status === 'active') {
                    // Immediately re-authenticate to normalize future requests
                    try {
                        Auth::guard('web')->login($user, false);
                        return $user;
                    } catch (\Exception $e) {
                        Log::error('Failed to re-authenticate reconstructed user', [
                            'error' => $e->getMessage(),
                            'user_nip' => $user->nip ?? 'unknown',
                        ]);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get current user role from auth guard or session
     * Always uses the web guard as primary source of truth.
     *
     * @return string|null
     */
    public function getCurrentUserRole()
    {
        // Primary source: Auth guard user
        $user = Auth::guard('web')->user();
        if ($user) {
            return $this->getUserRole($user);
        }

        // Last resort: session data
        $userSession = session('user_session');
        if ($userSession && isset($userSession['user_role'])) {
            return $userSession['user_role'];
        }

        return null;
    }

    /**
     * Complete logout process with session cleanup
     */
    public function completeLogout(string $nip, string $sessionId): void
    {
        try {
            // Log the logout attempt
            Log::info('AuthService::completeLogout called', [
                'nip' => $nip,
                'session_id' => $sessionId,
                'timestamp' => now()->toISOString()
            ]);

            // Clear rate limiting for this user
            $this->clearRateLimit($nip, null); // Clear for all IPs

            // Mark session as inactive in user_sessions table
            try {
                DB::table('user_sessions')
                    ->where('session_id', $sessionId)
                    ->update([
                        'is_active' => false,
                        'updated_at' => Carbon::now()
                    ]);
                
                Log::info('Session marked as inactive in user_sessions table', [
                    'session_id' => $sessionId,
                    'nip' => $nip
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to mark session as inactive in user_sessions table', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage()
                ]);
            }

            // Clean up expired sessions for this user
            $this->cleanupExpiredSessions($nip);

            // Update session tracking in database if needed
            try {
                DB::table('sessions')
                    ->where('id', $sessionId)
                    ->update([
                        'last_activity' => time(),
                        'payload' => '' // Clear payload to invalidate session
                    ]);
            } catch (\Exception $e) {
                Log::warning('Failed to update session in database during logout', [
                    'session_id' => $sessionId,
                    'nip' => $nip,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('AuthService::completeLogout completed successfully', [
                'nip' => $nip,
                'session_id' => $sessionId
            ]);

        } catch (\Exception $e) {
            Log::error('AuthService::completeLogout failed', [
                'nip' => $nip,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Don't throw exception - logout should always succeed even if cleanup fails
        }
    }

    /**
     * Validate if user can access specific channel type
     * Optimized method for WebSocket channel authorization
     */
    public function canAccessChannel($user, string $userRole, string $channelName): bool
    {
        // Remove private- prefix if present
        $cleanChannelName = str_replace('private-', '', $channelName);

        // User-specific channels (notifications.{userId}, user.tickets.{userId}, teknisi.{userId})
        if (preg_match('/\.(notifications|user\.tickets|teknisi)\.(\d+)$/', $cleanChannelName, $matches)) {
            return (int) $user->getKey() === (int) $matches[2];
        }

        // Role-based channels
        return match ($userRole) {
            'admin_helpdesk' => in_array($cleanChannelName, [
                'admin.tickets', 'admin.comments', 'analytics.metrics', 'new-tickets'
            ]),
            'teknisi' => in_array($cleanChannelName, [
                'teknisi.assigned-tickets'
            ]),
            'admin_aplikasi' => in_array($cleanChannelName, [
                'admin.aplikasi'
            ]),
            'user' => str_contains($cleanChannelName, 'user.'), // Basic user channels
            default => false
        };
    }

    /**
     * Get user's session data in a standardized format
     * This method provides a consistent data format for use across the app
     */
    public function getUserSessionData($user, string $ipAddress = null, string $userAgent = null): array
    {
        $role = $this->getUserRole($user);
        $now = Carbon::now();

        return [
            'user_id' => $user->getKey(),
            'user_role' => $role,
            'user_name' => $this->getUserFullName($user),
            'nip' => $user->nip,
            'login_time' => $now->toISOString(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'session_id' => session()->getId(),
            'permissions' => $this->getUserPermissions($user),
            'last_activity' => $now->timestamp,
            'expires_at' => $now->addMinutes(self::SESSION_TIMEOUT)->timestamp,
            'is_active' => true
        ];
    }
}
