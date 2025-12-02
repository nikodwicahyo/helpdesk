<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Define custom rate limiters
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure custom rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Custom rate limiter for dashboard polling endpoints
        RateLimiter::for('dashboard-poll', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Custom rate limiter for notification polling endpoints  
        RateLimiter::for('notification-poll', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Custom rate limiter for admin polling endpoints
        RateLimiter::for('admin-poll', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Custom rate limiter for session management endpoints
        RateLimiter::for('session-manage', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Default Laravel rate limiter for login attempts
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');
            
            return [
                Limit::perMinute(5)->by($email.$request->ip()),
                Limit::perMinute(3)->by('login', $email.$request->ip()),
            ];
        });

        // Rate limiter for password reset
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($request->email.$request->ip());
        });
    }
}