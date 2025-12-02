<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\Relation;
use Barryvdh\Debugbar\Facades\Debugbar;
use Inertia\Inertia;
use App\Providers\MultiModelUserProvider;
use App\Models\Ticket;
use App\Models\User;
use App\Models\AdminHelpdesk;
use App\Models\Teknisi;
use App\Models\AdminAplikasi;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Observers\TicketObserver;
use App\Observers\UserObserver;
use App\Observers\TeknisiObserver;
use App\Observers\AplikasiObserver;
use App\Observers\KategoriMasalahObserver;
use App\Services\DashboardMetricsService;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge helpdesk configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/helpdesk.php',
            'helpdesk'
        );

        // Register the multi-model user provider
        Auth::provider('multi_model', function ($app, array $config) {
            return new MultiModelUserProvider($app['hash'], $config['model']);
        });

        // Register DashboardMetricsService as singleton
        $this->app->singleton(DashboardMetricsService::class, function ($app) {
            return new DashboardMetricsService();
        });
    }

    /**
     * Register model observers for automatic notifications and logging
     */
    private function registerModelObservers(): void
    {
        try {
            // Register TicketObserver for automatic notifications on ticket events
            Ticket::observe(TicketObserver::class);

            // Register UserObserver for user lifecycle activity logging (all user types)
            User::observe(UserObserver::class);
            AdminHelpdesk::observe(UserObserver::class);
            AdminAplikasi::observe(UserObserver::class);

            // Register TeknisiObserver for teknisi assignment and workload notifications
            Teknisi::observe(TeknisiObserver::class);

            // Register AplikasiObserver for application status changes and health monitoring
            Aplikasi::observe(AplikasiObserver::class);

            // Register KategoriMasalahObserver for category management activity logging
            KategoriMasalah::observe(KategoriMasalahObserver::class);

            \Illuminate\Support\Facades\Log::info('Model observers registered successfully', [
                'observers' => [
                    'TicketObserver',
                    'UserObserver (User, AdminHelpdesk, AdminAplikasi)',
                    'TeknisiObserver',
                    'AplikasiObserver',
                    'KategoriMasalahObserver',
                ],
                'registered_at' => Carbon::now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to register model observers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define polymorphic relationship mapping to handle class name resolution
        Relation::morphMap([
            // User models
            'user' => User::class,
            'admin_helpdesk' => AdminHelpdesk::class,
            'admin_aplikasi' => AdminAplikasi::class,
            'teknisi' => Teknisi::class,

            // Full class names
            'App\Models\User' => User::class,
            'App\Models\AdminHelpdesk' => AdminHelpdesk::class,
            'App\Models\AdminAplikasi' => AdminAplikasi::class,
            'App\Models\Teknisi' => Teknisi::class,
        ]);

        // Always share auth data to prevent undefined errors in Vue components
        \Inertia\Inertia::share([
            'auth' => function () {
                // First try the standard Auth facade check
                if (Auth::check()) {
                    $user = Auth::user();
                    $role = session('user_session.user_role')
                          ?: app(\App\Services\AuthService::class)->getUserRole($user);

                    return [
                        'user' => [
                            'id' => $user->getKey(),
                            'nip' => $user->nip,
                            'name' => $user->name ?? $user->nama_lengkap,
                            'email' => $user->email ?? null,
                            'status' => $user->status ?? 'active',
                            'table' => $user->getTable(),
                        ],
                        'role' => $role,
                        'permissions' => app(\App\Services\AuthService::class)->getUserPermissions($user),
                        'session' => [
                            'id' => session()->getId(),
                            'last_activity' => session('_last_activity'),
                            'session_start_time' => session('_session_start_time'),
                            'expires_at' => session('session_expires_at'),
                            'warning_at' => session('session_warning_at'),
                            'login_time' => session('user_session.login_time'),
                        ],
                        'meta' => [
                            'auth_method' => 'authenticated',
                            'ip_address' => request()->ip(),
                            'user_table' => $user->getTable(),
                        ]
                    ];
                } else {
                    // If Auth facade is not available (middleware not run yet), try session reconstruction
                    $userSession = session('user_session');
                    if ($userSession && isset($userSession['user_id']) && isset($userSession['is_active']) && $userSession['is_active']) {
                        // Reconstruct user from session data
                        $user = app(\App\Services\AuthService::class)->getUserById($userSession['user_id']);
                        if ($user && isset($user->status) && $user->status === 'active') {
                            $role = $userSession['user_role'] ?? app(\App\Services\AuthService::class)->getUserRole($user);

                            return [
                                'user' => [
                                    'id' => $user->getKey(),
                                    'nip' => $user->nip,
                                    'name' => $user->name ?? $user->nama_lengkap ?? $userSession['user_name'] ?? $user->nip,
                                    'email' => $user->email ?? null,
                                    'status' => $user->status ?? 'active',
                                    'table' => $user->getTable(),
                                ],
                                'role' => $role,
                                'permissions' => $userSession['permissions'] ?? app(\App\Services\AuthService::class)->getUserPermissions($user),
                                'session' => [
                                    'id' => session()->getId(),
                                    'last_activity' => session('_last_activity') ?? $userSession['last_activity'],
                                    'session_start_time' => session('_session_start_time'),
                                    'expires_at' => session('session_expires_at') ?? $userSession['expires_at'],
                                    'warning_at' => session('session_warning_at'),
                                    'login_time' => $userSession['login_time'],
                                ],
                                'meta' => [
                                    'auth_method' => 'session_reconstructed',
                                    'ip_address' => request()->ip(),
                                    'user_table' => $user->getTable(),
                                ]
                            ];
                        }
                    }

                    // If not authenticated or session reconstruction failed, return unauthenticated structure
                    return [
                        'user' => null,
                        'role' => null,
                        'permissions' => [],
                        'session' => [
                            'id' => session()->getId(),
                            'last_activity' => null,
                            'session_start_time' => null,
                            'expires_at' => null,
                            'warning_at' => null,
                            'login_time' => null,
                        ],
                        'meta' => [
                            'auth_method' => 'unauthenticated',
                            'ip_address' => request()->ip(),
                        ]
                    ];
                }
            },
            'errors' => function () {
                return session()->get('errors') ? session()->get('errors')->getBag('default')->getMessages() : (object) [];
            },
            'flash' => function () {
                return [
                    'success' => session('success'),
                    'error' => session('error'),
                    'warning' => session('warning'),
                ];
            },
            'notifications' => function () {
                return Auth::check() ? Auth::user()->notifications()->limit(10)->get() : collect([]);
            },
            'unreadNotifications' => function () {
                return Auth::check() ? Auth::user()->unreadNotifications()->count() : 0;
            },
            'loading' => false,
        ]);

        // Register model observers for automatic notifications
        $this->registerModelObservers();

        // Register custom helpdesk collector for Debugbar
        $this->registerHelpdeskCollector();

        // Security: Disable Debugbar in production and for non-admin users
        $this->configureDebugbarSecurity();
    }

    /**
     * Register custom helpdesk data collector for Debugbar
     */
    private function registerHelpdeskCollector(): void
    {
        if ($this->app->environment('local') && class_exists('\Barryvdh\Debugbar\Facades\Debugbar')) {
            Debugbar::addCollector(new class implements \DebugBar\DataCollector\DataCollectorInterface
            {
                public function collect()
                {
                    $timezone = config('app.timezone', 'Asia/Jakarta');

                    return [
                        'helpdesk_stats' => [
                            'total_tickets' => Ticket::count(),
                            'open_tickets' => Ticket::where('status', 'open')->count(),
                            'in_progress_tickets' => Ticket::where('status', 'in_progress')->count(),
                            'closed_tickets' => Ticket::where('status', 'closed')->count(),
                            'total_users' => User::count(),
                            'admin_helpdesks' => AdminHelpdesk::count(),
                            'teknisis' => Teknisi::count(),
                            'admin_aplikasis' => AdminAplikasi::count(),
                        ],
                        'current_time' => Carbon::now($timezone)->format('Y-m-d H:i:s T'),
                        'timezone' => $timezone,
                        'environment' => config('app.env'),
                        'memory_usage' => [
                            'current' => number_format(memory_get_usage() / 1024 / 1024, 2) . ' MB',
                            'peak' => number_format(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
                        ],
                    ];
                }

                public function getName()
                {
                    return 'helpdesk';
                }

                public function getWidgets()
                {
                    return [
                        'helpdesk' => [
                            'icon' => 'clipboard',
                            'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                            'map' => 'helpdesk',
                            'default' => '{}'
                        ],
                        'helpdesk_stats' => [
                            'icon' => 'clipboard',
                            'widget' => 'PhpDebugBar.Widgets.VariableListWidget',
                            'map' => 'helpdesk.helpdesk_stats',
                            'default' => '{}'
                        ]
                    ];
                }
            });
        }
    }

    /**
     * Configure Debugbar security settings
     */
    private function configureDebugbarSecurity(): void
    {
        // Disable Debugbar in console/Artisan commands to avoid auth initialization issues
        if ($this->app->runningInConsole()) {
            config(['debugbar.enabled' => false]);
            return;
        }

        if ($this->app->environment('production') || !$this->app->environment('local')) {
            // Disable Debugbar in production or non-local environments
            config(['debugbar.enabled' => false]);
            return;
        }

        // Only show Debugbar to authenticated admin users in development
        if ($this->app->environment('local')) {
            try {
                // Check if auth is properly initialized before trying to get user
                if ($this->app->bound('auth') && $this->app['auth']->hasResolvedGuards()) {
                    $user = Auth::user();
                    if (!$user || !$this->isAdminUser($user)) {
                        config(['debugbar.enabled' => false]);
                    }
                } else {
                    // Auth not yet initialized, disable debugbar for safety
                    config(['debugbar.enabled' => false]);
                }
            } catch (\Exception $e) {
                // If there's any issue with auth, disable debugbar for safety
                config(['debugbar.enabled' => false]);
            }
        }
    }

    /**
     * Check if user is an admin user for Debugbar access
     */
    private function isAdminUser($user): bool
    {
        // Check if user is admin_helpdesk or admin_aplikasi
        return $user->role === 'admin_helpdesk' || $user->role === 'admin_aplikasi';
    }
}
