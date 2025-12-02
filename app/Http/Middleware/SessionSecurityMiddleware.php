<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SessionSecurityMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip security checks for non-authenticated requests
        if (!Auth::guard('web')->check()) {
            return $next($request);
        }

        // Load session data from user_session as the only source for user metadata
        $sessionData = session('user_session', []);

        // Use AuthService::getCurrentAuthenticatedUser() as single source
        $user = $this->authService->getCurrentAuthenticatedUser();
        if (!$user) {
            return $next($request);
        }

        $sessionId = session()->getId();
        $currentIp = $request->ip();
        $currentUserAgent = $request->userAgent();

        // Only perform security checks if we have the expected user_session data
        if (!empty($sessionData) && isset($sessionData['ip_address'], $sessionData['user_agent'], $sessionData['login_time'])) {
            // Check for session hijacking - implement soft failure
            if ($this->isSessionHijacked($sessionData, $currentIp, $currentUserAgent)) {
                // Log the security issue but don't immediately invalidate
                $this->logSecurityIssue($request, $user, $sessionData, $currentIp, $currentUserAgent, 'session_hijack');

                // Only invalidate on multiple violations or clear attacks
                if ($this->shouldInvalidateSession($sessionData, $currentIp, $currentUserAgent)) {
                    return $this->handleSessionHijack($request, $user, $sessionId, $sessionData);
                }
            }

            // Check for suspicious activity
            if ($this->isSuspiciousActivity($sessionData, $currentIp)) {
                $this->logSuspiciousActivity($request, $user, $sessionData, $currentIp);
            }
        }

        // Update session activity
        $this->authService->updateSessionActivity($sessionId);

        // Periodic session regeneration for enhanced security (every 30 minutes)
        if (!empty($sessionData) && $this->shouldRegenerateSession($sessionData)) {
            $this->authService->regenerateSession();
        }

        return $next($request);
    }

    
    /**
     * Check if session has been hijacked
     */
    private function isSessionHijacked(array $sessionData, string $currentIp, string $currentUserAgent): bool
    {
        // Check IP address change (allow for dynamic IPs with some tolerance)
        if (!$this->isIpAllowed($sessionData['ip_address'], $currentIp)) {
            return true;
        }

        // User-Agent check has been disabled to prevent false positives with modern browsers.
        // The IP address check provides a reasonable level of security against hijacking.
        // if ($sessionData['user_agent'] !== $currentUserAgent) {
        //     return true;
        // }

        return false;
    }

    /**
     * Check if IP address change is allowed
     */
    private function isIpAllowed(string $sessionIp, string $currentIp): bool
    {
        // Exact match
        if ($sessionIp === $currentIp) {
            return true;
        }

        // Check if both IPs are in the same subnet (for corporate networks)
        if ($this->isSameSubnet($sessionIp, $currentIp, '24')) {
            return true;
        }

        // Check for known proxy ranges or load balancer scenarios
        if ($this->isFromKnownProxy($currentIp)) {
            return true;
        }

        return false;
    }

    /**
     * Check if two IPs are in the same subnet
     */
    private function isSameSubnet(string $ip1, string $ip2, string $mask = '24'): bool
    {
        $ip1Long = ip2long($ip1);
        $ip2Long = ip2long($ip2);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ip1Long & $maskLong) === ($ip2Long & $maskLong);
    }

    /**
     * Check if IP is from known proxy or load balancer
     */
    private function isFromKnownProxy(string $ip): bool
    {
        // Common proxy ranges - in production, this should be configurable
        $proxyRanges = [
            '10.0.0.0/8',      // Private network
            '172.16.0.0/12',   // Private network
            '192.168.0.0/16',  // Private network
            '127.0.0.0/8',     // Localhost
        ];

        foreach ($proxyRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     */
    private function ipInRange(string $ip, string $range): bool
    {
        list($subnet, $mask) = explode('/', $range);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Check for suspicious activity patterns
     */
    private function isSuspiciousActivity(array $sessionData, string $currentIp): bool
    {
        // Check for rapid IP changes (potential session hijacking attempt)
        $timeSinceLogin = time() - strtotime($sessionData['login_time']);
        $maxTimeForIpChange = 300; // 5 minutes

        if ($timeSinceLogin < $maxTimeForIpChange && $sessionData['ip_address'] !== $currentIp) {
            return true;
        }

        // Check for unusual activity patterns
        if ($this->hasUnusualActivityPattern($sessionData)) {
            return true;
        }

        return false;
    }

    /**
     * Check for unusual activity patterns
     */
    private function hasUnusualActivityPattern(array $sessionData): bool
    {
        // This could be enhanced with more sophisticated pattern detection
        // For now, we'll check for basic patterns

        $lastActivity = $sessionData['last_activity'];
        $currentTime = time();

        // Check for impossible activity timing (future timestamps)
        if ($lastActivity > $currentTime) {
            return true;
        }

        // Check for extremely old last activity (stale session)
        $maxIdleTime = 8 * 60 * 60; // 8 hours
        if (($currentTime - $lastActivity) > $maxIdleTime) {
            return true;
        }

        return false;
    }

    /**
     * Handle session hijacking attempt
     */
    private function handleSessionHijack(Request $request, $user, string $sessionId, array $sessionData): Response
    {
        // Log the hijacking attempt using session data from parameters
        Log::critical('Session hijacking detected', [
            'nip' => $user->nip,
            'user_role' => $this->authService->getUserRole($user),
            'session_id' => $sessionId,
            'original_ip' => $sessionData['ip_address'] ?? 'unknown',
            'current_ip' => $request->ip(),
            'original_user_agent' => $sessionData['user_agent'] ?? 'unknown',
            'current_user_agent' => $request->userAgent(),
            'login_time' => $sessionData['login_time'] ?? 'unknown',
            'detection_time' => now()->toISOString(),
            'request_url' => $request->fullUrl(),
            'request_method' => $request->method()
        ]);

        // Logout user and invalidate session
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Session security violation detected. Please login again.',
                'error_code' => 'SESSION_HIJACKED'
            ], 401);
        }

        return redirect()->route('login')->with('error', 'Session security violation detected. Please login again.');
    }

    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity(Request $request, $user, array $sessionData, string $currentIp): void
    {
        Log::warning('Suspicious session activity detected', [
            'nip' => $user->nip,
            'user_role' => $this->authService->getUserRole($user),
            'session_id' => session()->getId(),
            'original_ip' => $sessionData['ip_address'],
            'current_ip' => $currentIp,
            'user_agent' => $request->userAgent(),
            'request_url' => $request->fullUrl(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Check if session should be invalidated for security violations
     */
    private function shouldInvalidateSession(array $sessionData, string $currentIp, string $currentUserAgent): bool
    {
        // Only invalidate on clear attacks or multiple violations
        $violationCount = session('security_violations', 0);

        // Invalidate if there are multiple violations
        if ($violationCount >= 3) {
            return true;
        }

        // Invalidate on completely different IP (not in same subnet)
        $originalIp = $sessionData['ip_address'];
        if (!$this->isSameSubnet($originalIp, $currentIp, '24') && !$this->isFromKnownProxy($currentIp)) {
            return true;
        }

        // Increment violation count and continue
        session(['security_violations' => $violationCount + 1]);
        return false;
    }

    /**
     * Log security issues for monitoring
     */
    private function logSecurityIssue(Request $request, $user, array $sessionData, string $currentIp, string $currentUserAgent, string $issueType): void
    {
        Log::warning('Security issue detected - soft failure', [
            'nip' => $user->nip ?? 'unknown',
            'issue_type' => $issueType,
            'session_id' => session()->getId(),
            'original_ip' => $sessionData['ip_address'] ?? 'unknown',
            'current_ip' => $currentIp,
            'original_user_agent' => $sessionData['user_agent'] ?? 'unknown',
            'current_user_agent' => $currentUserAgent,
            'violation_count' => session('security_violations', 0) + 1,
            'request_url' => $request->fullUrl(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Check if session should be regenerated for security
     */
    private function shouldRegenerateSession(?array $sessionData): bool
    {
        if (!$sessionData || !isset($sessionData['login_time'])) {
            return false;
        }

        $loginTime = strtotime($sessionData['login_time']);
        $currentTime = time();

        // Regenerate every 30 minutes for enhanced security
        $regenerationInterval = 30 * 60; // 30 minutes

        return ($currentTime - $loginTime) > $regenerationInterval;
    }
}