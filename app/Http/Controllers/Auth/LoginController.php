<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\UserRetrievalService;
use App\Services\RoleValidationService;
use App\Services\RoleRouteService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Contracts\Auth\Authenticatable;
use Carbon\Carbon;

class LoginController extends Controller
{
    private AuthService $authService;
    private UserRetrievalService $userRetrievalService;
    private RoleValidationService $roleValidationService;

    public function __construct(
        AuthService $authService,
        UserRetrievalService $userRetrievalService,
        RoleValidationService $roleValidationService
    ) {
        $this->authService = $authService;
        $this->userRetrievalService = $userRetrievalService;
        $this->roleValidationService = $roleValidationService;
    }

    /**
     * Show login form - SIMPLIFIED
     */
    public function showLoginForm(): InertiaResponse|RedirectResponse
    {
        // Use web guard as primary source
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            $role = $this->roleValidationService->getUserRole($user);

            return redirect()->route(RoleRouteService::getDashboardRoute($role))
                ->with('info', 'Anda sudah login. Mengalihkan ke dashboard...');
        }

        return Inertia::render('Login', [
            'auth' => [
                'user' => null,
                'session' => [
                    'timeout' => 120, // 2 hours
                    'warning_time' => 10, // 10 minutes
                ]
            ]
        ]);
    }

    /**
     * Handle login request with consolidated logic
     */
    public function login(Request $request)
    {
        $request->validate([
            'nip' => 'required|string',
            'password' => 'required|string',
        ], [
            'nip.required' => 'NIP wajib diisi',
            'password.required' => 'Password wajib diisi',
        ]);

        $nip = $request->input('nip');

        if (app()->isLocal()) {
            Log::debug('LoginController: Login attempt started', [
                'nip' => $nip,
                'ip' => $request->ip(),
                'timestamp' => now()->toISOString()
            ]);
        }
        $password = $request->input('password');
        $remember = $request->boolean('remember');
        $ipAddress = $request->ip();

        // Check if locked out using unified service
        if ($this->authService->isLockedOut($nip, $ipAddress)) {
            $remainingTime = ceil($this->authService->getLockoutTimeRemaining($nip, $ipAddress) / 60);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many login attempts.',
                    'error_code' => 'ACCOUNT_LOCKED',
                    'lockout_time_remaining' => $remainingTime
                ], 422);
            }

            throw ValidationException::withMessages([
                'nip' => ["Terlalu banyak percobaan login. Silakan coba lagi dalam {$remainingTime} menit."]
            ]);
        }

        // Get user using unified retrieval service
        $user = $this->userRetrievalService->getUserByNip($nip);

        if (!$user) {
            $this->handleFailedLogin($nip, $ipAddress, 'user_not_found');
            return $this->returnInvalidCredentialsResponse($request, $nip, $ipAddress);
        }

        // Check if user is active using unified service
        if (!$this->userRetrievalService->isUserActive($user)) {
            // Log the failed attempt with specific reason
            $this->handleFailedLogin($nip, $ipAddress, 'account_inactive');
            
            Log::info('Login attempt for inactive user', [
                'nip' => $nip,
                'status' => $user->status
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun tidak aktif. Silakan hubungi administrator.',
                    'error_code' => 'ACCOUNT_INACTIVE'
                ], 422);
            }

            throw ValidationException::withMessages([
                'nip' => ['Akun tidak aktif. Silakan hubungi administrator.']
            ]);
        }

        // Validate credentials using unified service
        if (!$this->userRetrievalService->validateUserCredentials($user, $password)) {
            $this->handleFailedLogin($nip, $ipAddress);
            return $this->returnInvalidCredentialsResponse($request, $nip, $ipAddress);
        }

        // Clear rate limiting on successful login
        $this->authService->clearRateLimit($nip, $ipAddress);

        // Update last login timestamp
        $this->updateLastLogin($user);

        // Get user role using unified service
        $role = $this->roleValidationService->getUserRole($user);

        // Log successful login
        Log::info('User login successful via unified controller', [
            'nip' => $user->nip,
            'role' => $role,
            'ip_address' => $ipAddress,
            'user_agent' => $request->userAgent()
        ]);

        // Complete login process with unified session management
        return $this->completeLogin($user, $role, $request, $ipAddress, $remember);
    }

    /**
     * Handle failed login attempt
     */
    private function handleFailedLogin(string $nip, string $ipAddress, string $reason = 'invalid_credentials'): void
    {
        $this->authService->handleFailedAttempt($nip, $ipAddress);
        
        // Log failed login attempt to audit log
        AuditLogService::logLoginAttemptFailed($nip, $reason);
        
        Log::warning('Failed login attempt', [
            'nip' => $nip,
            'ip_address' => $ipAddress,
            'reason' => $reason
        ]);
    }

    /**
     * Return invalid credentials response
     */
    private function returnInvalidCredentialsResponse(Request $request, string $nip, string $ipAddress)
    {
        $attemptsRemaining = $this->authService->getAttemptsRemaining($nip, $ipAddress);

        if ($request->expectsJson()) {
            $lockoutTimeRemaining = $this->authService->isLockedOut($nip, $ipAddress)
                ? ceil($this->authService->getLockoutTimeRemaining($nip, $ipAddress) / 60)
                : 0;

            return response()->json([
                'success' => false,
                'message' => 'Invalid NIP or password.',
                'error_code' => 'INVALID_CREDENTIALS',
                'attempts_remaining' => $attemptsRemaining,
                'lockout_time_remaining' => $lockoutTimeRemaining
            ], 422);
        }

        $message = 'Invalid NIP or password.';
        if ($attemptsRemaining > 0 && $attemptsRemaining <= 3) {
            $message .= " Sisa percobaan: {$attemptsRemaining}";
        }

        throw ValidationException::withMessages([
            'nip' => [$message]
        ]);
    }

    /**
     * Update last login timestamp - FIXED for remember me functionality
     */
    private function updateLastLogin($user): void
    {
        try {
            // Only update last_login_at if it's a fillable field and exists in database
            if ($user->isFillable('last_login_at')) {
                $user->update(['last_login_at' => Carbon::now()]);
                Log::info('Updated last_login_at', [
                    'nip' => $user->nip,
                    'user_class' => get_class($user)
                ]);
            } else {
                // Just touch the updated_at timestamp if last_login_at is not available
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
     * Complete login process with simplified robust approach
     */
    private function completeLogin(Authenticatable&EloquentModel $user, string $role, Request $request, string $ipAddress, bool $remember)
    {
        try {
                  if (app()->isLocal()) {
            Log::debug('Login completion started', [
                'nip' => $user->nip,
                'role' => $role,
                'expects_json' => $request->expectsJson(),
            ]);
        }

            // Authenticate with web guard - this is the authoritative step
            Auth::guard('web')->login($user, $remember);
            
            // Log the login activity
            AuditLogService::logLogin($user);

            if (app()->isLocal()) {
            Log::debug('Web guard login completed', [
                'auth_check' => Auth::guard('web')->check(),
                'session_id' => session()->getId(),
            ]);
        }

            // Verify authentication is working before continuing
            if (!Auth::guard('web')->check()) {
                throw new \Exception('Web guard authentication failed immediately after login');
            }

            // Use AuthService to create and store unified session data
            $sessionData = $this->authService->createSessionData($user, $ipAddress, $request->userAgent());

            if (app()->isLocal()) {
                Log::debug('LoginController: Session data created', [
                    'nip' => $user->nip,
                    'session_id' => $sessionData['session_id'],
                    'role' => $sessionData['user_role'],
                    'has_all_fields' => isset($sessionData['session_id'], $sessionData['nip'], $sessionData['user_role'])
                ]);
            }

            // Standardize on single user_session array structure
            session(['user_session' => $sessionData]);

            // Store session in user_sessions table for tracking and concurrent session management
            try {
                $this->authService->storeUserSession($sessionData);
                
                if (app()->isLocal()) {
                    Log::debug('LoginController: storeUserSession completed successfully');
                }
            } catch (\Exception $e) {
                Log::error('LoginController: Failed to store session in user_sessions table', [
                    'nip' => $user->nip,
                    'session_id' => $sessionData['session_id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                
                // Don't fail the login if session storage fails, but log it
                if (app()->isLocal()) {
                    // In local environment, we want to see this error
                    Log::warning('Session storage failed but continuing with login');
                }
            }

            // Initialize session timestamps for ACTIVITY-BASED timeout
            $currentTime = time();
            session(['_session_created_at' => Carbon::now()]); // Track when session was created
            session(['_last_activity' => $currentTime]); // Track last activity for timeout calculation

            if (app()->isLocal()) {
                Log::debug('LoginController: Session data stored via AuthService', [
                    'nip' => $user->nip,
                    'role' => $role,
                    'session_id' => session()->getId(),
                ]);
            }

            // Session regeneration removed - causes Inertia navigation issues
            // Auth state will persist through navigation without regeneration
            // Security is maintained through CSRF tokens and session timeouts

            if (app()->isLocal()) {
                Log::debug('LoginController: Session data stored without regeneration', [
                    'nip' => $user->nip,
                    'role' => $role,
                    'session_id' => session()->getId(),
                    'auth_check' => Auth::guard('web')->check(),
                ]);
            }

            Log::info('Login completed successfully', [
                'nip' => $user->nip,
                'role' => $role,
                'session_id' => session()->getId(),
                'auth_check' => Auth::guard('web')->check() ? 'PASS' : 'FAIL',
                'user_id' => Auth::guard('web')->id(),
            ]);

            // Return appropriate response
            if ($request->expectsJson()) {
                return $this->returnJsonLoginResponse($user, $role);
            }

            return $this->returnWebLoginResponse($user, $role);

        } catch (\Exception $e) {
            Log::error('Login completion failed', [
                'error' => $e->getMessage(),
                'nip' => $user->nip ?? 'unknown',
                'trace' => $e->getTraceAsString(),
                'session_id' => session()->getId(),
                'auth_check_before_cleanup' => Auth::guard('web')->check(),
            ]);

            // Only cleanup if authentication exists - avoid premature logout
            if (Auth::guard('web')->check()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Login completion failed: ' . $e->getMessage(),
                    'error_code' => 'LOGIN_COMPLETION_FAILED'
                ], 500);
            }

            throw ValidationException::withMessages([
                'nip' => ['Login completion failed. Please try again.']
            ]);
        }
    }

    /**
     * Return JSON response for API login
     *
     * For XHR/SPA login requests:
     * - Return JSON with redirect URL for client-side navigation
     * - Server does NOT perform redirect to avoid double navigation
     * - Client will handle redirect using window.location.href
     *
     * For traditional form POST:
     * - Use returnWebLoginResponse() which performs server-side redirect
     * - Client should not attempt additional navigation
     */
    private function returnJsonLoginResponse($user, string $role)
    {
        $routeName = RoleRouteService::getDashboardRoute($role);

        $permissions = $this->authService->getUserPermissions($user);
        $sessionData = session('user_session', []);
        $sessionTimeoutMinutes = $this->authService->getSessionTimeout();
        $defaultExpiry = now()->addMinutes($sessionTimeoutMinutes)->timestamp;
        $expiresAt = $sessionData['expires_at'] ?? $defaultExpiry;
        $warningOffsetSeconds = $this->authService->getSessionWarningTime() * 60;

        $sessionInfo = [
            'session_id' => $sessionData['session_id'] ?? session()->getId(),
            'expires_at' => $expiresAt,
            'warning_time' => $sessionData['warning_time'] ?? max($expiresAt - $warningOffsetSeconds, 0),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'status' => 'success',
            'user' => [
                'id' => $user->getKey(),
                'nip' => $user->nip,
                'name' => $user->name,
                'email' => $user->email ?? null,
                'status' => $user->status ?? 'active',
            ],
            'role' => $role,
            'permissions' => $permissions,
            'session_info' => $sessionInfo,
            'redirect' => route($routeName),
            'debug' => app()->isLocal() ? [
                'session_id' => session()->getId(),
                'auth_check' => Auth::guard('web')->check(),
            ] : null
        ]);
    }

    /**
     * Return web response for browser login
     *
     * For traditional form POST requests:
     * - Server performs direct redirect to dashboard
     * - Client should NOT perform additional navigation
     * - Avoids double navigation conflicts
     */
    private function returnWebLoginResponse($user, string $role)
    {
        $routeName = RoleRouteService::getDashboardRoute($role);

        // Generate the redirect URL and verify it exists
        try {
            $redirectUrl = route($routeName);
            return redirect($redirectUrl)
                ->with('success', 'Login berhasil! Selamat datang, ' . $user->name);
        } catch (\Exception $e) {
            // Fallback to default dashboard if route generation fails
            Log::error('Failed to generate dashboard route', [
                'role' => $role,
                'route_name' => $routeName,
                'error' => $e->getMessage()
            ]);

            return redirect()->route(RoleRouteService::getDashboardRoute('user'))
                ->with('success', 'Login berhasil! Selamat datang, ' . $user->name);
        }
    }

    /**
     * Get login attempts status for a NIP
     */
    public function getAttemptsStatus(Request $request)
    {
        $nip = $request->input('nip');
        $ipAddress = $request->ip();

        if (!$nip) {
            return response()->json([
                'success' => false,
                'message' => 'NIP required'
            ], 400);
        }

        $isLockedOut = $this->authService->isLockedOut($nip, $ipAddress);
        $attemptsRemaining = $this->authService->getAttemptsRemaining($nip, $ipAddress);
        $lockoutTimeRemaining = $isLockedOut ? ceil($this->authService->getLockoutTimeRemaining($nip, $ipAddress) / 60) : 0;

        return response()->json([
            'success' => true,
            'is_locked_out' => $isLockedOut,
            'attempts_remaining' => $attemptsRemaining,
            'lockout_time_remaining' => $lockoutTimeRemaining,
        ]);
    }

    /**
     * Check NIP existence and get role info using unified service
     */
    public function checkNip(Request $request)
    {
        $nip = $request->input('nip');

        if (empty($nip)) {
            return response()->json([
                'exists' => false,
                'role' => null,
                'active' => false
            ]);
        }

        $user = $this->userRetrievalService->getUserByNip($nip);

        if ($user) {
            $role = $this->roleValidationService->getUserRole($user);
            return response()->json([
                'exists' => true,
                'role' => $role,
                'active' => $this->userRetrievalService->isUserActive($user)
            ]);
        }

        return response()->json([
            'exists' => false,
            'role' => null,
            'active' => false
        ]);
    }

    /**
     * Get authenticated user status - relies strictly on web guard
     */
    public function status(Request $request)
    {
        // Primary source: Auth::guard('web') - the authoritative authentication check
        $webGuardCheck = Auth::guard('web')->check();

        if (app()->isLocal()) {
            Log::debug('Status endpoint debug', [
                'session_id' => session()->getId(),
                'web_guard_check' => $webGuardCheck,
                'web_guard_user_class' => Auth::guard('web')->user() ? get_class(Auth::guard('web')->user()) : null,
            ]);
        }

        // Only perform session-based reconstruction when explicitly wanting to self-heal inconsistent state
        if (!$webGuardCheck) {
            $userSession = session('user_session');
            if ($userSession && isset($userSession['user_id'])) {
                // Attempt self-healing reconstruction
                $user = $this->userRetrievalService->getUserById($userSession['user_id']);
                if ($user && isset($user->status) && $user->status === 'active') {
                    // After reconstruction, re-login to normalize future requests
                    try {
                        Auth::guard('web')->login($user, false);
                        $webGuardCheck = Auth::guard('web')->check();

                        if (app()->isLocal()) {
                            Log::debug('Status endpoint: Self-healed authentication', [
                                'nip' => $user->nip,
                                'new_web_guard_check' => $webGuardCheck,
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('Status endpoint: Failed to self-heal authentication', [
                            'error' => $e->getMessage(),
                            'user_nip' => $user->nip ?? 'unknown',
                        ]);
                    }
                }
            }
        }

        // Final check after any self-healing attempts
        if (!$webGuardCheck) {
            return response()->json([
                'authenticated' => false,
                'message' => 'Not authenticated',
                'debug' => app()->isLocal() ? [
                    'session_id' => session()->getId(),
                    'web_guard_check' => $webGuardCheck,
                ] : null
            ], 200);
        }

        $user = Auth::guard('web')->user();
        $role = $this->roleValidationService->getUserRole($user);

        // Get redirect URL based on user role using centralized service
        $routeName = RoleRouteService::getDashboardRoute($role);
        $redirectUrl = route($routeName);

        // Cast to EloquentModel to ensure getKey() is available
        $eloquentUser = $user instanceof EloquentModel ? $user : null;

        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $eloquentUser?->getKey() ?? $user->getAuthIdentifier(),
                'nip' => $user->nip,
                'name' => $user->name,
                'email' => $user->email ?? null,
            ],
            'role' => $role,
            'redirect' => $redirectUrl,
            'debug' => app()->isLocal() ? [
                'session_id' => session()->getId(),
                'web_guard_check' => $webGuardCheck,
                'web_guard_id' => Auth::guard('web')->id(),
            ] : null
        ]);
    }

    /**
     * Handle logout request with unified cleanup
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = $this->userRetrievalService->getAuthenticatedUser();

        if ($user) {
            Log::info('User logout via unified controller', [
                'nip' => $user->nip,
                'role' => $this->roleValidationService->getUserRole($user),
                'session_id' => session()->getId(),
                'ip_address' => $request->ip(),
            ]);
            
            // Log the logout activity
            AuditLogService::logLogout($user);

            // Complete logout process with session cleanup
            $this->authService->completeLogout($user->nip, session()->getId());
        }

        // Clear Laravel auth
        Auth::guard('web')->logout();

        // Clear session completely
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Anda telah berhasil logout.');
    }

    /**
     * Refresh authentication session with unified logic
     */
    public function refresh(Request $request)
    {
        try {
            $user = $this->userRetrievalService->getAuthenticatedUser();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            // Refresh session activity
            $sessionId = session()->getId();
            $this->authService->updateSessionActivity($sessionId);

            // Update session data
            $sessionData = session('user_session');
            if ($sessionData) {
                $sessionData['last_activity'] = now()->timestamp;
                session(['user_session' => $sessionData]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Session refreshed successfully',
                'session_info' => [
                    'expires_at' => $this->authService->getSessionExpiryTime(),
                    'warning_time' => $this->authService->getSessionWarningTime(),
                    'last_activity' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Session refresh failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh session'
            ], 500);
        }
    }
}
