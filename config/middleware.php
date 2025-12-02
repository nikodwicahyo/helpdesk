<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global HTTP Middleware Stack
    |--------------------------------------------------------------------------
    |
    | These middleware are run during every request to your application.
    |
    */

    'global' => [
        \App\Http\Middleware\SessionTimeoutMiddleware::class,
        \App\Http\Middleware\SessionSecurityMiddleware::class,
        \App\Http\Middleware\AuditMiddleware::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Route middleware may be assigned to groups or used individually. These
    | middleware will be assigned to every route in the application.
    |
    */

    'aliases' => [

        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\GuestMiddleware::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        // Custom middleware aliases
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'session.timeout' => \App\Http\Middleware\SessionTimeoutMiddleware::class,
        'session.security' => \App\Http\Middleware\SessionSecurityMiddleware::class,
        'audit' => \App\Http\Middleware\AuditMiddleware::class,

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
    ],

    /*
    |--------------------------------------------------------------------------
    | CSRF Protection
    |--------------------------------------------------------------------------
    |
    | Configure CSRF protection settings for the application
    |
    */

    'csrf' => [
        'enable' => env('CSRF_PROTECTION', true),
        'except' => [
            'api/*',
            'sanctum/csrf-cookie',
            'livewire/*',
        ],
        'token_name' => '_token',
        'cookie_name' => 'XSRF-TOKEN',
        'secure' => env('CSRF_COOKIE_SECURE', false),
        'same_site' => env('CSRF_COOKIE_SAME_SITE', 'lax'),
        'skip_for_api' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for authentication endpoints
    |
    */

    'rate_limiting' => [
        'login' => [
            'max_attempts' => 5,
            'decay_minutes' => 15,
            'lockout_minutes' => 30,
        ],
        'api' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Configure session security settings
    |
    */

    'session' => [
        'timeout_minutes' => 120,
        'lifetime_minutes' => 480, // 8 hours max
        'secure' => env('SESSION_SECURE_COOKIE', false),
        'same_site' => env('SESSION_COOKIE_SAME_SITE', 'lax'),
        'regenerate_on_login' => true,
        'regenerate_on_logout' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configure security headers for the application
    |
    */

    'security_headers' => [
        'x_frame_options' => 'SAMEORIGIN',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'strict_transport_security' => env('HSTS_ENABLED', false) ? 'max-age=31536000; includeSubDomains' : null,
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'content_security_policy' => env('CSP_ENABLED', false) ? "default-src 'self'" : null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configure audit logging settings
    |
    */

    'audit' => [
        'enable' => env('AUDIT_LOGGING', true),
        'log_level' => env('AUDIT_LOG_LEVEL', 'info'),
        'sensitive_routes' => [
            'login', 'logout', 'password.*', 'admin.*', 'user.*', 'teknisi.*', 'admin-aplikasi.*'
        ],
        'exclude_routes' => [
            'api/user-status', 'debugbar.*', '_debugbar.*'
        ],
    ],

];