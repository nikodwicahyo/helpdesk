<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class QueryLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Enable query logging for this request
        DB::enableQueryLog();

        // Start timing the request
        $startTime = microtime(true);

        $response = $next($request);

        // Calculate request duration
        $duration = microtime(true) - $startTime;

        // Log slow queries and performance metrics
        $this->logQueryPerformance($request, $duration);

        return $response;
    }

    /**
     * Log query performance metrics
     *
     * @param Request $request
     * @param float $requestDuration
     * @return void
     */
    protected function logQueryPerformance(Request $request, float $requestDuration): void
    {
        $queries = DB::getQueryLog();
        $totalQueryTime = 0;
        $slowQueries = [];
        $queryCount = count($queries);

        foreach ($queries as $query) {
            $queryTime = $query['time'] / 1000; // Convert to seconds
            $totalQueryTime += $queryTime;

            // Log queries slower than 100ms
            if ($queryTime > 0.1) {
                $slowQueries[] = [
                    'sql' => $query['query'],
                    'time' => $queryTime,
                    'bindings' => $query['bindings'] ?? [],
                ];
            }
        }

        // Log performance metrics
        $performanceData = [
            'request_uri' => $request->getRequestUri(),
            'request_method' => $request->getMethod(),
            'user_id' => \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : null,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'total_request_time' => round($requestDuration, 4),
            'total_query_time' => round($totalQueryTime, 4),
            'query_count' => $queryCount,
            'avg_query_time' => $queryCount > 0 ? round($totalQueryTime / $queryCount, 4) : 0,
            'slow_queries_count' => count($slowQueries),
        ];

        // Log to different levels based on performance
        if ($requestDuration > 2.0) { // Very slow request (>2 seconds)
            Log::warning('Slow request detected', array_merge($performanceData, [
                'slow_queries' => $slowQueries,
            ]));
        } elseif ($totalQueryTime > 1.0) { // High database load (>1 second)
            Log::info('High database load detected', $performanceData);
        } elseif ($queryCount > 50) { // Too many queries
            Log::info('High query count detected', $performanceData);
        } elseif (!empty($slowQueries)) { // Some slow queries
            Log::debug('Slow queries detected', array_merge($performanceData, [
                'slow_queries' => $slowQueries,
            ]));
        } else {
            // Log basic metrics for monitoring
            Log::debug('Request performance metrics', $performanceData);
        }

        // Store performance metrics in cache for monitoring dashboard
        $this->storePerformanceMetrics($performanceData);
    }

    /**
     * Store performance metrics for monitoring dashboard
     *
     * @param array $metrics
     * @return void
     */
    protected function storePerformanceMetrics(array $metrics): void
    {
        try {
            $cacheKey = 'performance_metrics_' . date('Y-m-d-H-i');
            $existingMetrics = \Illuminate\Support\Facades\Cache::get($cacheKey, []);

            $existingMetrics[] = $metrics;

            // Keep only last 100 metrics per minute
            if (count($existingMetrics) > 100) {
                $existingMetrics = array_slice($existingMetrics, -100);
            }

            \Illuminate\Support\Facades\Cache::put($cacheKey, $existingMetrics, 3600); // Cache for 1 hour

        } catch (\Exception $e) {
            Log::error('Failed to store performance metrics', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
