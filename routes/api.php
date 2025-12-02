<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TeknisiController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\AdminAplikasi\ApplicationController;
use App\Http\Controllers\AdminAplikasi\CategoryController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\User\UserDashboardController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\SystemStatusController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// API Authentication routes - NEEDS web middleware for session handling
// Without 'web' middleware, sessions aren't started and cookies aren't set properly
Route::middleware(['web'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])->name('api.')->group(function () {
    Route::post('/login', [LoginController::class, 'login'])->name('login');
    Route::post('/check-nip', [LoginController::class, 'checkNip'])->name('check-nip');
    Route::post('/attempts-status', [LoginController::class, 'getAttemptsStatus'])->name('attempts-status');
    Route::get('/login-status', [LoginController::class, 'getAttemptsStatus'])->name('login-status');
});

// API Auth routes (for frontend authentication checks) - needs web middleware for session
Route::prefix('auth')->name('api.auth.')->middleware(['web'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])->group(function () {
    Route::get('/status', [LoginController::class, 'status'])->name('status');
    
    Route::middleware(['auth:web'])->group(function() {
        Route::get('/integrated-status', [\App\Http\Controllers\Api\IntegratedAuthController::class, 'getIntegratedAuth'])->name('integrated.status');
        Route::post('/refresh', [LoginController::class, 'refresh'])->name('refresh');
    });
});

// Protected API routes with authentication but no CSRF protection - needs web middleware for session
Route::middleware(['web'])->name('api.')->group(function () {
    Route::get('/user-status', [LoginController::class, 'status'])->name('user-status');
});

// Session endpoints:

// Session config API route (public access for session timeout manager initialization)
Route::get('/session/config', [SessionController::class, 'config'])->name('api.session.config');

// Session status API route - needs web session to check auth status
// session.tracking updates last_activity but NOT expires_at for status polling
Route::middleware(['web', 'session.tracking', 'throttle:60,1'])->get('/session/status', [SessionController::class, 'status'])->name('api.session.status');

// Session management API routes (authenticated) - Higher rate limit for session monitoring (checks every 60 seconds)
// NEEDS web middleware for session cookie support
Route::middleware(['web', 'auth:web', 'throttle:120,1'])->prefix('session')->name('api.session.')->group(function () {
    Route::post('/extend', [SessionController::class, 'extend'])->name('extend');
    Route::post('/refresh', [SessionController::class, 'extend'])->name('refresh'); // Alias for extend
    Route::get('/sessions', [SessionController::class, 'sessions'])->name('sessions');
    Route::delete('/terminate/{sessionId}', [SessionController::class, 'terminate'])->name('terminate');
    Route::delete('/terminate-all', [SessionController::class, 'terminateAll'])->name('terminate-all');
});

// Authenticated API routes (session-based authentication)
// CRITICAL: web middleware MUST come before auth:web to enable session support
Route::middleware(['web', 'auth:web', 'session.timeout', 'session.security'])->group(function () {
    Route::get('/user', function () {
        return Auth::user();
    })->name('api.user');

    Route::get('/permissions', function () {
        $user = Auth::user();
        return response()->json([
            'permissions' => app(\App\Services\AuthService::class)->getUserPermissions($user)
        ]);
    })->name('api.permissions');

    // General dashboard stats (for all authenticated users) - Rate limited for polling protection
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('api.dashboard.stats');
        
        // Dashboard metrics endpoints
        Route::get('/dashboard/metrics', [DashboardController::class, 'getDashboardMetrics'])->name('api.dashboard.metrics');
        Route::post('/dashboard/metrics/refresh', [DashboardController::class, 'refreshMetrics'])->name('api.dashboard.metrics.refresh');
    });

    // General Ticket Route - Rate limited for polling protection
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('/tickets/refresh', [TicketController::class, 'refresh'])->name('api.tickets.refresh');
    });

    // File upload API routes (all authenticated users)
    Route::prefix('files')->name('api.files.')->group(function () {
        Route::post('/upload', [FileUploadController::class, 'uploadTicketFiles'])->name('upload');
        Route::post('/delete', [FileUploadController::class, 'deleteFile'])->name('delete');
        Route::get('/info', [FileUploadController::class, 'getFileInfo'])->name('info');
    });

    // System status API routes (admin only)
    Route::middleware('role:admin_helpdesk')->prefix('system')->name('api.system.')->group(function () {
        Route::get('/health', [SystemStatusController::class, 'health'])->name('health');
        Route::get('/metrics', [SystemStatusController::class, 'metrics'])->name('metrics');
    });

    // Notification API routes - Moved to end to avoid authentication conflicts

    // Role-specific API routes
    Route::middleware('role:admin_helpdesk')->group(function () {
        Route::get('/admin/tickets', [TicketController::class, 'index'])->name('api.admin.tickets');
        Route::post('/admin/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('api.admin.tickets.assign');

        // Admin helpdesk dashboard metrics - Rate limited for polling protection
        Route::get('/admin/dashboard/metrics', [DashboardController::class, 'getAdminMetrics'])->middleware('throttle:60,1')->name('api.admin.dashboard.metrics');
        // Removed real-time metrics endpoint

        // Admin Helpdesk Refresh Routes - Rate limited for polling protection
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/admin/tickets/refresh', [TicketController::class, 'refreshTickets'])->name('api.admin.tickets.refresh');
            Route::get('/admin/stats/refresh', [DashboardController::class, 'refreshStats'])->name('api.admin.stats.refresh');
            Route::get('/admin/users/refresh', [UserController::class, 'refreshUsers'])->name('api.admin.users.refresh');
        });
    });

    Route::middleware('role:teknisi')->group(function () {
        Route::get('/teknisi/tickets', [TeknisiController::class, 'tickets'])->name('api.teknisi.tickets');
        Route::post('/teknisi/tickets/{ticket}/update', [TeknisiController::class, 'update'])->name('api.teknisi.tickets.update');
        
        // Get available teknisis for reassignment
        Route::get('/teknisis/available', [TeknisiController::class, 'getAvailableTeknisis'])->name('api.teknisis.available');

        // Teknisi dashboard metrics - Rate limited for polling protection
        Route::get('/teknisi/dashboard/metrics', [DashboardController::class, 'getTeknisiMetrics'])->middleware('throttle:60,1')->name('api.teknisi.dashboard.metrics');
        // Removed real-time metrics endpoint

        // Teknisi Refresh Routes - Rate limited for polling protection
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/teknisi/tickets/assigned/refresh', [TeknisiController::class, 'refreshAssignedTickets'])->name('api.teknisi.tickets.assigned.refresh');
            Route::get('/teknisi/workload/refresh', [TeknisiController::class, 'refreshWorkload'])->name('api.teknisi.workload.refresh');
        });
    });

    Route::middleware('role:user')->group(function () {
        Route::get('/user/tickets', [UserController::class, 'tickets'])->name('api.user.tickets');
        Route::post('/user/tickets', [UserController::class, 'store'])->name('api.user.tickets.store');

        // User dashboard API routes - Rate limited for polling protection
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/dashboard-stats', [UserDashboardController::class, 'getTicketStats'])->name('user.dashboard-stats');
            Route::get('/activity-summary', [UserDashboardController::class, 'getActivitySummary'])->name('user.activity-summary');
        });

        // User dashboard metrics - Rate limited for polling protection
        Route::get('/user/dashboard/metrics', [DashboardController::class, 'getUserMetrics'])->middleware('throttle:60,1')->name('api.user.dashboard.metrics');
        // Removed real-time metrics endpoint

        // User tickets API routes
        Route::get('/tickets', [\App\Http\Controllers\User\TicketController::class, 'apiIndex'])->name('api.user.tickets.index');
        Route::get('/tickets/{ticket}', [\App\Http\Controllers\User\TicketController::class, 'getStats'])->name('api.user.tickets.show');
        
        // Application API routes
        Route::get('/applications/{id}/categories', [\App\Http\Controllers\Api\ApplicationController::class, 'getCategories'])->name('api.applications.categories');
        Route::get('/applications/{id}', [\App\Http\Controllers\Api\ApplicationController::class, 'show'])->name('api.applications.show');
    });

    Route::middleware('role:admin_aplikasi')->group(function () {
        // Admin aplikasi dashboard metrics - Rate limited for polling protection
        Route::get('/admin-aplikasi/dashboard/metrics', [DashboardController::class, 'getAdminAplikasiMetrics'])->middleware('throttle:60,1')->name('api.admin.aplikasi.dashboard.metrics');
        // Removed real-time metrics endpoint

        // Admin Aplikasi Refresh Routes - Rate limited for polling protection
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/admin-aplikasi/applications/refresh', [ApplicationController::class, 'refreshApplications'])->name('api.admin.aplikasi.applications.refresh');
            Route::get('/admin-aplikasi/categories/refresh', [CategoryController::class, 'refreshCategories'])->name('api.admin.aplikasi.categories.refresh');
        });
    });
});

// Notification API routes - NEEDS web middleware for session handling
// Without 'web' middleware, auth:web fails because session isn't started
Route::middleware(['web', 'auth:web', 'throttle:60,1'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'getUnread'])->name('unread');
    Route::get('/all', [\App\Http\Controllers\Api\NotificationController::class, 'index'])->name('index');
    Route::post('/{notification}/mark-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead'])->name('mark-read');
    Route::post('/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    Route::delete('/{notification}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-mark-read', [\App\Http\Controllers\Api\NotificationController::class, 'bulkMarkAsRead'])->name('bulk-mark-read');
    Route::get('/recent', [\App\Http\Controllers\Api\NotificationController::class, 'getRecent'])->name('recent');
    Route::get('/stats', [\App\Http\Controllers\Api\NotificationController::class, 'stats'])->name('stats');
    Route::get('/{notification}/details', [\App\Http\Controllers\Api\NotificationController::class, 'details'])->name('details');
});
