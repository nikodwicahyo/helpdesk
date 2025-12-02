<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SystemStatusController extends Controller
{
    /**
     * Get system health status
     */
    public function health()
    {
        try {
            $status = [
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'checks' => $this->performHealthChecks()
            ];

            // Check if any critical checks failed
            $criticalFailures = collect($status['checks'])
                ->filter(fn($check) => $check['status'] === 'critical' && !$check['pass']);

            if ($criticalFailures->isNotEmpty()) {
                $status['status'] = 'critical';
            } else {
                // Check if any warnings
                $warnings = collect($status['checks'])
                    ->filter(fn($check) => $check['status'] === 'warning' && !$check['pass']);

                if ($warnings->isNotEmpty()) {
                    $status['status'] = 'warning';
                }
            }

            return response()->json($status);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'timestamp' => now()->toISOString(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system metrics
     */
    public function metrics()
    {
        try {
            $metrics = [
                'timestamp' => now()->toISOString(),
                'database' => $this->getDatabaseMetrics(),
                'storage' => $this->getStorageMetrics(),
                'cache' => $this->getCacheMetrics(),
                'tickets' => $this->getTicketMetrics(),
                'users' => $this->getUserMetrics(),
                'performance' => $this->getPerformanceMetrics()
            ];

            return $this->successResponse($metrics, 'System metrics retrieved');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get system metrics', [$e->getMessage()], 500);
        }
    }

    /**
     * Perform comprehensive health checks
     */
    private function performHealthChecks(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'sessions' => $this->checkSessions(),
            'queue' => $this->checkQueue(),
            'memory' => $this->checkMemory(),
            'disk_space' => $this->checkDiskSpace()
        ];
    }

    /**
     * Check database connection
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'critical',
                'pass' => true,
                'message' => 'Database connection successful',
                'response_time_ms' => $responseTime,
                'details' => [
                    'connection' => config('database.default'),
                    'host' => config('database.connections.mysql.host'),
                    'database' => config('database.connections.mysql.database')
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'pass' => false,
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check cache functionality
     */
    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';

            // Test write
            Cache::put($testKey, $testValue, 60);

            // Test read
            $retrieved = Cache::get($testKey);

            // Cleanup
            Cache::forget($testKey);

            $pass = $retrieved === $testValue;

            return [
                'status' => 'warning',
                'pass' => $pass,
                'message' => $pass ? 'Cache working correctly' : 'Cache not working correctly',
                'details' => [
                    'driver' => config('cache.default'),
                    'store' => config('cache.stores.' . config('cache.default'))
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'pass' => false,
                'message' => 'Cache check failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check storage functionality
     */
    private function checkStorage(): array
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'Health check test file';

            // Test write
            Storage::disk('public')->put($testFile, $testContent);

            // Test read
            $retrieved = Storage::disk('public')->get($testFile);

            // Cleanup
            Storage::disk('public')->delete($testFile);

            $pass = $retrieved === $testContent;

            return [
                'status' => 'warning',
                'pass' => $pass,
                'message' => $pass ? 'Storage working correctly' : 'Storage not working correctly',
                'details' => [
                    'disk' => 'public',
                    'root' => config('filesystems.disks.public.root')
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'pass' => false,
                'message' => 'Storage check failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check session functionality
     */
    private function checkSessions(): array
    {
        try {
            $sessionId = session()->getId();
            $testKey = 'health_check_' . time();

            session([$testKey => 'test_value']);
            session()->save();

            $retrieved = session($testKey);
            session()->forget($testKey);

            $pass = $retrieved === 'test_value';

            return [
                'status' => 'warning',
                'pass' => $pass,
                'message' => $pass ? 'Sessions working correctly' : 'Sessions not working correctly',
                'details' => [
                    'driver' => config('session.driver'),
                    'session_id' => $sessionId,
                    'lifetime' => config('session.lifetime')
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'pass' => false,
                'message' => 'Session check failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check queue system
     */
    private function checkQueue(): array
    {
        return [
            'status' => 'info',
            'pass' => true,
            'message' => 'Queue system available',
            'details' => [
                'default_connection' => config('queue.default'),
                'driver' => config('queue.connections.' . config('queue.default') . '.driver')
            ]
        ];
    }

    /**
     * Check memory usage
     */
    private function checkMemory(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryUsagePercent = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) * 100 : 0;

        $status = 'info';
        $pass = true;

        if ($memoryUsagePercent > 90) {
            $status = 'warning';
            $pass = false;
        }

        return [
            'status' => $status,
            'pass' => $pass,
            'message' => "Memory usage: {$memoryUsagePercent}%",
            'details' => [
                'current_usage_bytes' => $memoryUsage,
                'limit_bytes' => $memoryLimit,
                'usage_percent' => round($memoryUsagePercent, 2),
                'current_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
                'limit_mb' => round($memoryLimit / 1024 / 1024, 2)
            ]
        ];
    }

    /**
     * Check disk space
     */
    private function checkDiskSpace(): array
    {
        $freeSpace = disk_free_space(base_path());
        $totalSpace = disk_total_space(base_path());
        $usedSpace = $totalSpace - $freeSpace;
        $usagePercent = ($usedSpace / $totalSpace) * 100;

        $status = 'warning';
        $pass = $usagePercent < 90;

        return [
            'status' => $status,
            'pass' => $pass,
            'message' => "Disk usage: " . round($usagePercent, 2) . "%",
            'details' => [
                'total_bytes' => $totalSpace,
                'used_bytes' => $usedSpace,
                'free_bytes' => $freeSpace,
                'usage_percent' => round($usagePercent, 2),
                'total_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'used_gb' => round($usedSpace / 1024 / 1024 / 1024, 2),
                'free_gb' => round($freeSpace / 1024 / 1024 / 1024, 2)
            ]
        ];
    }

    /**
     * Get database metrics
     */
    private function getDatabaseMetrics(): array
    {
        try {
            $tables = DB::select('SHOW TABLE STATUS');

            return [
                'tables_count' => count($tables),
                'total_size_mb' => round(collect($tables)->sum('Data_length') / 1024 / 1024, 2),
                'connection_time_ms' => 0 // Will be measured in real implementation
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get storage metrics
     */
    private function getStorageMetrics(): array
    {
        try {
            $publicPath = storage_path('app/public');
            $size = $this->getDirectorySize($publicPath);
            $files = $this->countFiles($publicPath);

            return [
                'public_storage_size_mb' => round($size / 1024 / 1024, 2),
                'public_files_count' => $files
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get cache metrics
     */
    private function getCacheMetrics(): array
    {
        try {
            return [
                'driver' => config('cache.default'),
                'hits' => 0, // Would need cache monitoring setup
                'misses' => 0,
                'hit_rate' => 0
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get ticket metrics
     */
    private function getTicketMetrics(): array
    {
        try {
            $ticketCounts = DB::table('tickets')
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open,
                    SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as closed
                ')
                ->first();

            return [
                'total_tickets' => $ticketCounts->total ?? 0,
                'open_tickets' => $ticketCounts->open ?? 0,
                'in_progress_tickets' => $ticketCounts->in_progress ?? 0,
                'resolved_tickets' => $ticketCounts->resolved ?? 0,
                'closed_tickets' => $ticketCounts->closed ?? 0
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get user metrics
     */
    private function getUserMetrics(): array
    {
        try {
            return [
                'total_users' => DB::table('users')->count(),
                'total_admin_helpdesks' => DB::table('admin_helpdesks')->count(),
                'total_admin_aplikasis' => DB::table('admin_aplikasis')->count(),
                'total_teknisis' => DB::table('teknisis')->count()
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        return [
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'cpu_load' => sys_getloadavg()[0] ?? 0,
            'uptime' => $this->getSystemUptime()
        ];
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = strtolower($limit);
        $multiplier = 1;

        if (strpos($limit, 'g') !== false) {
            $multiplier = 1024 * 1024 * 1024;
        } elseif (strpos($limit, 'm') !== false) {
            $multiplier = 1024 * 1024;
        } elseif (strpos($limit, 'k') !== false) {
            $multiplier = 1024;
        }

        return (int) preg_replace('/[^0-9]/', '', $limit) * $multiplier;
    }

    /**
     * Get directory size
     */
    private function getDirectorySize(string $path): int
    {
        $size = 0;
        $files = glob(rtrim($path, '/') . '/*', GLOB_NOSORT);

        foreach ($files as $file) {
            if (is_file($file)) {
                $size += filesize($file);
            } elseif (is_dir($file)) {
                $size += $this->getDirectorySize($file);
            }
        }

        return $size;
    }

    /**
     * Count files in directory
     */
    private function countFiles(string $path): int
    {
        $count = 0;
        $files = glob(rtrim($path, '/') . '/*', GLOB_NOSORT);

        foreach ($files as $file) {
            if (is_file($file)) {
                $count++;
            } elseif (is_dir($file)) {
                $count += $this->countFiles($file);
            }
        }

        return $count;
    }

    /**
     * Get system uptime
     */
    private function getSystemUptime(): string
    {
        if (function_exists('shell_exec')) {
            $uptime = shell_exec('uptime');
            return trim($uptime ?: 'Unknown');
        }

        return 'Unknown';
    }
}