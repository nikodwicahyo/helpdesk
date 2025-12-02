<?php

namespace App\Services;

use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserRetrievalService
{
    private AuthService $authService;

    /**
     * Model priority order for user retrieval (highest to lowest priority)
     */
    private const MODEL_PRIORITY = [
        AdminHelpdesk::class,
        AdminAplikasi::class,
        Teknisi::class,
        User::class,
    ];

    /**
     * Cache TTL for user lookups (in minutes)
     */
    private const CACHE_TTL = 30;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Get user by NIP with unified logic and caching
     *
     * @param string $nip
     * @return mixed|null
     */
    public function getUserByNip(string $nip)
    {
        if (empty($nip)) {
            return null;
        }

        $cacheKey = "user_by_nip:{$nip}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () use ($nip) {
            return $this->findUserByNip($nip);
        });
    }

    /**
     * Find user by NIP without caching (for real-time data)
     *
     * @param string $nip
     * @return mixed|null
     */
    public function findUserByNip(string $nip)
    {
        foreach (self::MODEL_PRIORITY as $modelClass) {
            try {
                $user = $modelClass::where('nip', $nip)->first();

                if ($user) {
                    Log::debug('User found', [
                        'nip' => $nip,
                        'model' => $modelClass,
                        'user_id' => $user->getKey()
                    ]);

                    return $user;
                }
            } catch (\Exception $e) {
                Log::warning('Error querying user model', [
                    'nip' => $nip,
                    'model' => $modelClass,
                    'error' => $e->getMessage()
                ]);

                // Continue to next model if this one fails
                continue;
            }
        }

        Log::info('User not found in any model', ['nip' => $nip]);
        return null;
    }

    /**
     * Get user by ID with unified logic
     *
     * @param mixed $id
     * @return mixed|null
     */
    public function getUserById($id)
    {
        if (empty($id)) {
            return null;
        }

        $cacheKey = "user_by_id:{$id}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () use ($id) {
            return $this->findUserById($id);
        });
    }

    /**
     * Find user by ID without caching
     *
     * @param mixed $id
     * @return mixed|null
     */
    public function findUserById($id)
    {
        foreach (self::MODEL_PRIORITY as $modelClass) {
            try {
                $user = $modelClass::find($id);

                if ($user) {
                    Log::debug('User found by ID', [
                        'id' => $id,
                        'model' => $modelClass,
                        'nip' => $user->nip ?? 'no_nip'
                    ]);

                    return $user;
                }
            } catch (\Exception $e) {
                Log::warning('Error querying user model by ID', [
                    'id' => $id,
                    'model' => $modelClass,
                    'error' => $e->getMessage()
                ]);

                continue;
            }
        }

        Log::info('User not found by ID in any model', ['id' => $id]);
        return null;
    }

    /**
     * Get user by email with unified logic
     *
     * @param string $email
     * @return mixed|null
     */
    public function getUserByEmail(string $email)
    {
        if (empty($email)) {
            return null;
        }

        $cacheKey = "user_by_email:" . md5($email);

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () use ($email) {
            return $this->findUserByEmail($email);
        });
    }

    /**
     * Find user by email without caching
     *
     * @param string $email
     * @return mixed|null
     */
    public function findUserByEmail(string $email)
    {
        foreach (self::MODEL_PRIORITY as $modelClass) {
            try {
                $user = $modelClass::where('email', $email)->first();

                if ($user) {
                    Log::debug('User found by email', [
                        'email' => $email,
                        'model' => $modelClass,
                        'nip' => $user->nip ?? 'no_nip'
                    ]);

                    return $user;
                }
            } catch (\Exception $e) {
                Log::warning('Error querying user model by email', [
                    'email' => $email,
                    'model' => $modelClass,
                    'error' => $e->getMessage()
                ]);

                continue;
            }
        }

        Log::info('User not found by email in any model', ['email' => $email]);
        return null;
    }

    /**
     * Get authenticated user with fallback logic
     *
     * @return mixed|null
     */
    public function getAuthenticatedUser()
    {
        // First try Laravel's web guard
        $user = auth('web')->user();

        if (!$user) {
            // Try to get from session data for multi-role support
            $sessionData = session('user_session');
            if ($sessionData && isset($sessionData['nip'])) {
                $user = $this->getUserByNip($sessionData['nip']);
            }
        }

        if ($user) {
            Log::debug('Authenticated user retrieved', [
                'nip' => $user->nip,
                'role' => $this->authService->getUserRole($user),
                'method' => $user === auth('web')->user() ? 'web_guard' : 'session_fallback'
            ]);
        }

        return $user;
    }

    /**
     * Get users by role with unified logic
     *
     * @param string $role
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersByRole(string $role)
    {
        $cacheKey = "users_by_role:{$role}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () use ($role) {
            return $this->findUsersByRole($role);
        });
    }

    /**
     * Find users by role without caching
     *
     * @param string $role
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findUsersByRole(string $role)
    {
        $modelClass = $this->getModelClassForRole($role);

        if (!$modelClass) {
            Log::warning('No model class found for role', ['role' => $role]);
            return collect();
        }

        try {
            $users = $modelClass::where('status', 'active')
                               ->where('role', $role)
                               ->get();

            Log::debug('Users found by role', [
                'role' => $role,
                'count' => $users->count(),
                'model' => $modelClass
            ]);

            return $users;
        } catch (\Exception $e) {
            Log::error('Error finding users by role', [
                'role' => $role,
                'model' => $modelClass,
                'error' => $e->getMessage()
            ]);

            return collect();
        }
    }

    /**
     * Get users by department with unified logic
     *
     * @param string $department
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersByDepartment(string $department)
    {
        $cacheKey = "users_by_department:" . md5($department);

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () use ($department) {
            return $this->findUsersByDepartment($department);
        });
    }

    /**
     * Find users by department without caching
     *
     * @param string $department
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findUsersByDepartment(string $department)
    {
        $users = collect();

        foreach (self::MODEL_PRIORITY as $modelClass) {
            try {
                $modelUsers = $modelClass::where('department', $department)
                                        ->where('status', 'active')
                                        ->get();

                $users = $users->merge($modelUsers);

                Log::debug('Users found in model by department', [
                    'department' => $department,
                    'count' => $modelUsers->count(),
                    'model' => $modelClass
                ]);
            } catch (\Exception $e) {
                Log::warning('Error finding users by department in model', [
                    'department' => $department,
                    'model' => $modelClass,
                    'error' => $e->getMessage()
                ]);

                continue;
            }
        }

        Log::debug('Total users found by department', [
            'department' => $department,
            'total_count' => $users->count()
        ]);

        return $users;
    }

    /**
     * Get users by status with unified logic
     *
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersByStatus(string $status)
    {
        $cacheKey = "users_by_status:{$status}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () use ($status) {
            return $this->findUsersByStatus($status);
        });
    }

    /**
     * Find users by status without caching
     *
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findUsersByStatus(string $status)
    {
        $users = collect();

        foreach (self::MODEL_PRIORITY as $modelClass) {
            try {
                $modelUsers = $modelClass::where('status', $status)->get();
                $users = $users->merge($modelUsers);

                Log::debug('Users found in model by status', [
                    'status' => $status,
                    'count' => $modelUsers->count(),
                    'model' => $modelClass
                ]);
            } catch (\Exception $e) {
                Log::warning('Error finding users by status in model', [
                    'status' => $status,
                    'model' => $modelClass,
                    'error' => $e->getMessage()
                ]);

                continue;
            }
        }

        Log::debug('Total users found by status', [
            'status' => $status,
            'total_count' => $users->count()
        ]);

        return $users;
    }

    /**
     * Search users by name, email, or NIP with unified logic
     *
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchUsers(string $search)
    {
        if (empty($search)) {
            return collect();
        }

        $cacheKey = "users_search:" . md5($search);

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () use ($search) {
            return $this->findUsersBySearch($search);
        });
    }

    /**
     * Find users by search term without caching
     *
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findUsersBySearch(string $search)
    {
        $users = collect();

        foreach (self::MODEL_PRIORITY as $modelClass) {
            try {
                $modelUsers = $modelClass::where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('nip', 'like', "%{$search}%");
                })->get();

                $users = $users->merge($modelUsers);

                Log::debug('Users found in model by search', [
                    'search' => $search,
                    'count' => $modelUsers->count(),
                    'model' => $modelClass
                ]);
            } catch (\Exception $e) {
                Log::warning('Error searching users in model', [
                    'search' => $search,
                    'model' => $modelClass,
                    'error' => $e->getMessage()
                ]);

                continue;
            }
        }

        Log::debug('Total users found by search', [
            'search' => $search,
            'total_count' => $users->count()
        ]);

        return $users;
    }

    /**
     * Get model class for specific role
     *
     * @param string $role
     * @return string|null
     */
    private function getModelClassForRole(string $role): ?string
    {
        return match($role) {
            'admin_helpdesk' => AdminHelpdesk::class,
            'admin_aplikasi' => AdminAplikasi::class,
            'teknisi' => Teknisi::class,
            'user' => User::class,
            default => null,
        };
    }

    /**
     * Clear user cache for specific NIP
     *
     * @param string $nip
     * @return void
     */
    public function clearUserCache(string $nip): void
    {
        Cache::forget("user_by_nip:{$nip}");

        // Also clear related caches
        $user = $this->findUserByNip($nip);
        if ($user) {
            Cache::forget("user_by_id:{$user->getKey()}");
            Cache::forget("user_by_email:" . md5($user->email));
        }
    }

    /**
     * Clear all user-related caches
     *
     * @return void
     */
    public function clearAllUserCaches(): void
    {
        $patterns = [
            'user_by_nip:*',
            'user_by_id:*',
            'user_by_email:*',
            'users_by_role:*',
            'users_by_department:*',
            'users_by_status:*',
            'users_search:*',
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Get user statistics across all models
     *
     * @return array
     */
    public function getUserStatistics(): array
    {
        $cacheKey = 'user_statistics';

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () {
            return $this->calculateUserStatistics();
        });
    }

    /**
     * Calculate user statistics without caching
     *
     * @return array
     */
    private function calculateUserStatistics(): array
    {
        $stats = [
            'total_users' => 0,
            'active_users' => 0,
            'inactive_users' => 0,
            'by_role' => [],
            'by_model' => [],
            'by_status' => [],
        ];

        foreach (self::MODEL_PRIORITY as $modelClass) {
            try {
                $modelName = class_basename($modelClass);
                $stats['by_model'][$modelName] = [
                    'total' => 0,
                    'active' => 0,
                    'inactive' => 0,
                ];

                $totalUsers = $modelClass::count();
                $activeUsers = $modelClass::where('status', 'active')->count();
                $inactiveUsers = $totalUsers - $activeUsers;

                $stats['by_model'][$modelName] = [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'inactive' => $inactiveUsers,
                ];

                $stats['total_users'] += $totalUsers;
                $stats['active_users'] += $activeUsers;
                $stats['inactive_users'] += $inactiveUsers;

                // Get role distribution for this model
                $roleStats = $modelClass::selectRaw('role, status, COUNT(*) as count')
                                       ->groupBy('role', 'status')
                                       ->get();

                foreach ($roleStats as $roleStat) {
                    $role = $roleStat->role ?? 'no_role';
                    $status = $roleStat->status;

                    if (!isset($stats['by_role'][$role])) {
                        $stats['by_role'][$role] = ['total' => 0, 'active' => 0, 'inactive' => 0];
                    }

                    $stats['by_role'][$role]['total'] += $roleStat->count;
                    if ($status === 'active') {
                        $stats['by_role'][$role]['active'] += $roleStat->count;
                    } else {
                        $stats['by_role'][$role]['inactive'] += $roleStat->count;
                    }
                }

                Log::debug('User statistics calculated for model', [
                    'model' => $modelClass,
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'inactive' => $inactiveUsers
                ]);

            } catch (\Exception $e) {
                Log::error('Error calculating user statistics for model', [
                    'model' => $modelClass,
                    'error' => $e->getMessage()
                ]);

                continue;
            }
        }

        Log::info('User statistics calculated', $stats);

        return $stats;
    }

    /**
     * Validate user credentials with unified logic
     *
     * @param mixed $user
     * @param string $password
     * @return bool
     */
    public function validateUserCredentials($user, string $password): bool
    {
        if (!$user) {
            return false;
        }

        try {
            return password_verify($password, $user->password);
        } catch (\Exception $e) {
            Log::error('Error validating user credentials', [
                'user_id' => $user->getKey(),
                'user_type' => get_class($user),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Check if user account is active
     *
     * @param mixed $user
     * @return bool
     */
    public function isUserActive($user): bool
    {
        if (!$user) {
            return false;
        }

        $status = $user->status ?? 'active';
        return $status === 'active';
    }

    /**
     * Get user display information with unified format
     *
     * @param mixed $user
     * @return array
     */
    public function getUserDisplayInfo($user): array
    {
        if (!$user) {
            return [];
        }

        return [
            'id' => $user->getKey(),
            'nip' => $user->nip,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $this->authService->getUserRole($user),
            'status' => $user->status ?? 'active',
            'department' => $user->department ?? null,
            'position' => $user->position ?? null,
            'profile_photo_url' => method_exists($user, 'profile_photo_url') ? $user->profile_photo_url : null,
            'last_login' => $user->last_login_at ?? null,
            'model_type' => class_basename($user),
        ];
    }
}