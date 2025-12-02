<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuditMiddleware
{
    /**
     * Maximum size for request/response data to log (in bytes)
     */
    private const MAX_LOG_SIZE = 1024 * 1024; // 1MB

    /**
     * Maximum size for individual request parameters (in bytes)
     */
    private const MAX_PARAM_SIZE = 1024 * 100; // 100KB

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log successful requests (2xx status codes)
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            try {
                $this->logAccess($request, $response);
            } catch (Throwable $e) {
                // Log the error but don't break the application flow
                Log::error('Audit middleware error', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method()
                ]);
            }
        }

        return $response;
    }

    /**
     * Log access for audit purposes
     */
    private function logAccess(Request $request, Response $response): void
    {
        // Check response size before processing to avoid memory issues
        $responseContent = $response->getContent();
        $responseSize = strlen($responseContent);

        // Skip logging if response is too large to prevent memory issues
        if ($responseSize > self::MAX_LOG_SIZE) {
            Log::info('Large response skipped for audit logging', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'route' => $this->getRouteNameSafely($request),
                'response_size' => $responseSize,
                'reason' => 'Response too large'
            ]);
            return;
        }

        $user = Auth::user();
        $logData = [
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'route' => $this->getRouteNameSafely($request),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => $response->getStatusCode(),
            'response_size' => $responseSize,
            'referer' => $request->header('referer'),
        ];

        // Add user information if authenticated
        if ($user) {
            $logData['user_info'] = [
                'nip' => $user->nip ?? null,
                'role' => app(AuthService::class)->getUserRole($user),
                'authenticated_at' => session('authenticated_at', null)
            ];
        }

        // Add request parameters for POST/PUT/PATCH requests with size limits
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $logData['request_data'] = [
                'input' => $this->getFilteredInput($request),
                'files' => $this->hasAnyFile($request) ? $this->getOptimizedFileInfo($request) : null
            ];
        }

        // Add query parameters for GET requests with size limits
        if ($request->method() === 'GET' && $request->query()) {
            $logData['query_params'] = $this->getFilteredQueryParams($request);
        }

        // Log based on sensitivity level
        $this->logBySensitivity($logData, $request);
    }

    /**
     * Safely get route name handling null routes
     */
    private function getRouteNameSafely(Request $request): string
    {
        $route = $request->route();
        return $route?->getName() ?? 'unknown';
    }

    /**
     * Get filtered input data with size limits
     */
    private function getFilteredInput(Request $request): array
    {
        $input = $request->except(['password', 'password_confirmation', '_token']);

        // Limit the size of input data to prevent memory issues
        $filteredInput = [];
        foreach ($input as $key => $value) {
            $valueStr = is_array($value) ? json_encode($value) : (string) $value;
            if (strlen($valueStr) > self::MAX_PARAM_SIZE) {
                $filteredInput[$key] = '[Data too large: ' . strlen($valueStr) . ' bytes]';
            } else {
                $filteredInput[$key] = $value;
            }
        }

        return $filteredInput;
    }

    /**
     * Get filtered query parameters with size limits
     */
    private function getFilteredQueryParams(Request $request): array
    {
        $queryParams = $request->query();

        // Limit the size of query parameters to prevent memory issues
        $filteredParams = [];
        foreach ($queryParams as $key => $value) {
            $valueStr = is_array($value) ? json_encode($value) : (string) $value;
            if (strlen($valueStr) > self::MAX_PARAM_SIZE) {
                $filteredParams[$key] = '[Data too large: ' . strlen($valueStr) . ' bytes]';
            } else {
                $filteredParams[$key] = $value;
            }
        }

        return $filteredParams;
    }

    /**
     * Check if request has any files
     */
    private function hasAnyFile(Request $request): bool
    {
        foreach ($request->all() as $key => $value) {
            if ($request->hasFile($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get optimized file information for audit logging
     */
    private function getOptimizedFileInfo(Request $request): array
    {
        $fileInfo = [];
        $totalSize = 0;
        $maxTotalSize = 1024 * 1024; // 1MB limit for all file info combined

        foreach ($request->allFiles() as $key => $file) {
            if ($file && $file->isValid()) {
                $fileSize = $file->getSize();

                // Check if adding this file would exceed the total size limit
                if (($totalSize + $fileSize) > $maxTotalSize) {
                    $fileInfo['__truncated__'] = 'File information truncated due to size limit';
                    break;
                }

                $fileInfo[$key] = [
                    'name' => $file->getClientOriginalName(),
                    'size' => $fileSize,
                    'mime_type' => $file->getMimeType()
                ];

                $totalSize += $fileSize;
            }
        }

        return $fileInfo;
    }

    /**
     * Log based on sensitivity level
     */
    private function logBySensitivity(array $logData, Request $request): void
    {
        $routeName = $this->getRouteNameSafely($request);

        // High sensitivity routes (authentication, user management)
        $highSensitivityRoutes = [
            'login', 'logout', 'api.login', 'api.user-status',
            'admin.*', 'user.*', 'teknisi.*', 'admin-aplikasi.*'
        ];

        // Medium sensitivity routes (data modification)
        $mediumSensitivityRoutes = [
            'ticket.*', 'application.*', 'category.*', 'report.*'
        ];

        $isHighSensitivity = $this->matchesPatterns($routeName, $highSensitivityRoutes);
        $isMediumSensitivity = $this->matchesPatterns($routeName, $mediumSensitivityRoutes);

        try {
            if ($isHighSensitivity) {
                Log::info('High sensitivity access', $logData);
            } elseif ($isMediumSensitivity) {
                Log::info('Medium sensitivity access', $logData);
            } else {
                Log::info('General access', $logData);
            }
        } catch (Throwable $e) {
            // If logging fails, try to log a minimal error message
            try {
                Log::error('Audit logging failed', [
                    'error' => $e->getMessage(),
                    'route' => $routeName,
                    'method' => $request->method(),
                    'url' => $request->fullUrl()
                ]);
            } catch (Throwable $fallbackError) {
                // If even the fallback logging fails, we can't do anything else
                // This prevents infinite loops and application crashes
            }
        }
    }

    /**
     * Check if route name matches any of the given patterns
     */
    private function matchesPatterns(string $routeName, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                $regex = str_replace('\*', '.*', preg_quote($pattern, '/'));
                if (preg_match('/^' . $regex . '$/', $routeName)) {
                    return true;
                }
            } elseif ($routeName === $pattern) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get audit log summary for dashboard
     */
    public static function getAuditSummary(): array
    {
        // This would typically query a dedicated audit log table
        // For now, return basic statistics from Laravel logs
        return [
            'total_requests_today' => 0,
            'unique_users_today' => 0,
            'failed_requests_today' => 0,
            'average_response_time' => 0
        ];
    }

    /**
     * Get recent audit logs
     */
    public static function getRecentLogs(int $limit = 50): array
    {
        // This would typically query a dedicated audit log table
        // For now, return empty array
        return [];
    }
}