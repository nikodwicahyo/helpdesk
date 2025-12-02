<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'api/check-nip',
            'api/attempts-status',
            'api/login-status',
            'api/login',
        ]);

        $middleware->alias([
            // Sanctum middleware
            'auth.sanctum' => \Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,

            // Inertia middleware
            'inertia' => \App\Http\Middleware\HandleInertiaRequests::class,
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,

            // Custom security middleware
            'guest' => \App\Http\Middleware\GuestMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'session.timeout' => \App\Http\Middleware\SessionTimeoutMiddleware::class,
            'session.tracking' => \App\Http\Middleware\SessionTrackingMiddleware::class,
            'session.security' => \App\Http\Middleware\SessionSecurityMiddleware::class,
            'audit' => \App\Http\Middleware\AuditMiddleware::class,
            'query.logging' => \App\Http\Middleware\QueryLoggingMiddleware::class,

            // Role-based aliases for convenience
            'admin' => \App\Http\Middleware\RoleMiddleware::class . ':admin_helpdesk|admin_aplikasi',
            'admin_helpdesk' => \App\Http\Middleware\RoleMiddleware::class . ':admin_helpdesk',
            'admin_aplikasi' => \App\Http\Middleware\RoleMiddleware::class . ':admin_aplikasi',
            'teknisi' => \App\Http\Middleware\RoleMiddleware::class . ':teknisi',
            'user' => \App\Http\Middleware\RoleMiddleware::class . ':user',

            // Multiple role aliases
            'admin_or_teknisi' => \App\Http\Middleware\RoleMiddleware::class . ':admin_helpdesk|admin_aplikasi|teknisi',
            'teknisi_or_user' => \App\Http\Middleware\RoleMiddleware::class . ':teknisi|user',
            'all_roles' => \App\Http\Middleware\RoleMiddleware::class . ':admin_helpdesk|admin_aplikasi|teknisi|user',
        ]);

        // Register global middleware (applied to all requests)
        $middleware->use([
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            // Apply system settings early to configure app name, timezone, mail, etc.
            \App\Http\Middleware\ApplySystemSettings::class,
            // Use stable auth middleware instead of InertiaAuthMiddleware to prevent conflicts
            \App\Http\Middleware\StableAuthMiddleware::class,
        ]);

        // API middleware group
        // NOTE: Removed EnsureFrontendRequestsAreStateful as we use session-based auth, not Sanctum tokens
        // This was causing session cookie conflicts between API and web routes
        $middleware->api(prepend: [
            // Empty - no Sanctum middleware needed for session-based authentication
        ]);

        // Create API-only middleware group without CSRF protection
        $middleware->group('api-only', []);

        // Web middleware group enhancements
        $middleware->web(append: [
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        // Priority-based middleware (order matters)
        // Stable auth middleware should run before session tracking and timeout
        $middleware->priority([
            \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\StableAuthMiddleware::class,
            \App\Http\Middleware\SessionTrackingMiddleware::class,
            \App\Http\Middleware\RoleMiddleware::class,
            \App\Http\Middleware\AuditMiddleware::class,
            \App\Http\Middleware\SessionTimeoutMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
