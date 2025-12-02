<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AdminHelpdesk\AdminHelpdeskDashboardController;
use App\Http\Controllers\AdminHelpdesk\TicketManagementController;
use App\Http\Controllers\AdminHelpdesk\AnalyticsController;
use App\Http\Controllers\AdminHelpdesk\ApplicationManagementController;
use App\Http\Controllers\AdminHelpdesk\CategoriesManagementController;
use App\Http\Controllers\AdminHelpdesk\UserManagementController;
use App\Http\Controllers\AdminHelpdesk\ReportController;
use App\Http\Controllers\AdminHelpdesk\NotificationController;
use App\Http\Controllers\AdminHelpdesk\ScheduledReportController;
use App\Http\Controllers\AdminHelpdesk\SystemController;
use App\Http\Controllers\AdminHelpdesk\ActivityLogController;
use App\Http\Controllers\AdminAplikasi\DashboardController as AdminAplikasiDashboardController;
use App\Http\Controllers\AdminAplikasi\ApplicationController as AdminAplikasiApplicationController;
use App\Http\Controllers\AdminAplikasi\CategoryController as AdminAplikasiCategoryController;
use App\Http\Controllers\AdminAplikasi\AnalyticsController as AdminAplikasiAnalyticsController;
use App\Http\Controllers\Teknisi\TeknisiDashboardController;
use App\Http\Controllers\Teknisi\TeknisiController;
use App\Http\Controllers\Teknisi\TicketHandlingController;
use App\Http\Controllers\Teknisi\KnowledgeBaseController;
use App\Http\Controllers\User\UserDashboardController;
use App\Http\Controllers\User\TicketController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\SharedNotificationController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\SystemStatusController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Authentication routes (public access)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // AJAX validation endpoints
    Route::post('/check-nip', [RegisterController::class, 'checkNip'])->name('check.nip');
    Route::post('/check-email', [RegisterController::class, 'checkEmail'])->name('check.email');
    Route::post('/check-password', [RegisterController::class, 'checkPassword'])->name('check.password');
});

// Public routes (no authentication required)
Route::get('/', function () {
    return Inertia::render('Landing');
})->name('landing');

// Protected routes with role-based access control
Route::middleware(['auth', 'session.tracking', 'session.timeout', 'inertia'])->group(function () {

    // Common authenticated routes (all roles)
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Language switching (all authenticated users)
    Route::post('/language/switch', [App\Http\Controllers\LanguageController::class, 'switch'])->name('language.switch');
    Route::get('/language/current', [App\Http\Controllers\LanguageController::class, 'current'])->name('language.current');

    // File upload routes (all authenticated users)
    Route::prefix('files')->name('files.')->group(function () {
        Route::post('/upload', [FileUploadController::class, 'uploadTicketFiles'])->name('upload');
        Route::post('/delete', [FileUploadController::class, 'deleteFile'])->name('delete');
        Route::get('/info', [FileUploadController::class, 'getFileInfo'])->name('info');
        Route::get('/download/{ticket_number}/{filename}', [FileUploadController::class, 'downloadFile'])->name('download');
    });

    // System status routes (admin only)
    Route::middleware('role:admin_helpdesk')->prefix('system')->name('system.')->group(function () {
        Route::get('/health', [SystemStatusController::class, 'health'])->name('health');
        Route::get('/metrics', [SystemStatusController::class, 'metrics'])->name('metrics');
    });

    // Search routes (available to all authenticated roles)
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('/', [SearchController::class, 'index'])->name('index');
        Route::post('/tickets', [SearchController::class, 'search'])->name('tickets');
        Route::get('/suggestions', [SearchController::class, 'suggestions'])->name('suggestions');
        Route::post('/save', [SearchController::class, 'saveSearch'])->name('save');
        Route::delete('/saved/{search_id}', [SearchController::class, 'deleteSavedSearch'])->name('delete');
        Route::delete('/history', [SearchController::class, 'clearHistory'])->name('clear-history');
        Route::get('/statistics', [SearchController::class, 'statistics'])->name('statistics');
        Route::post('/export', [SearchController::class, 'export'])->name('export');
    });

    // Admin Helpdesk routes (full system access)
    Route::middleware('role:admin_helpdesk')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminHelpdeskDashboardController::class, 'admin'])->name('dashboard');
        Route::post('/dashboard/refresh-stats', [AdminHelpdeskDashboardController::class, 'refreshStats'])->name('dashboard.refresh-stats');

        // Ticket Management routes
        Route::get('/tickets', [TicketManagementController::class, 'index'])->name('admin.tickets.index');
        Route::prefix('/tickets-management')->name('tickets-management.')->group(function () {
            Route::get('/', [TicketManagementController::class, 'index'])->name('index');
            Route::post('/', [TicketManagementController::class, 'store'])->name('store');
            Route::get('/{ticket}', [TicketManagementController::class, 'show'])->name('show');
            Route::put('/{ticket}', [TicketManagementController::class, 'update'])->name('update');
            Route::post('/{ticket}/update-status', [TicketManagementController::class, 'updateStatus'])->name('update-status');
            Route::post('/{ticket}/assign', [TicketManagementController::class, 'assign'])->name('assign');
            Route::post('/{ticket}/unassign', [TicketManagementController::class, 'unassign'])->name('unassign');
            Route::post('/{ticket}/close', [TicketManagementController::class, 'close'])->name('close');
            Route::post('/{ticket}/update-priority', [TicketManagementController::class, 'updatePriority'])->name('update-priority');
            Route::post('/bulk-action', [TicketManagementController::class, 'bulkAction'])->name('bulk-action');
            Route::post('/bulk-update-status', [TicketManagementController::class, 'bulkUpdateStatus'])->name('bulk-update-status');
            Route::post('/bulk-assign', [TicketManagementController::class, 'bulkAssign'])->name('bulk-assign');
            Route::get('/export', [TicketManagementController::class, 'export'])->name('export');

            // Comment routes for ticket management
            Route::prefix('/{ticket}/comments')->name('comments.')->group(function () {
                Route::post('/', [TicketManagementController::class, 'addComment'])->name('store');
                Route::get('/', [TicketManagementController::class, 'getComments'])->name('index');
            });
        });

        // User Management routes
        Route::get('/users-management', [UserManagementController::class, 'index'])->name('users-management.index');
        
        // User validation endpoints (for real-time checking)
        Route::post('/users/check-nip', [UserManagementController::class, 'checkNip'])->name('users.check-nip');
        Route::post('/users/check-email', [UserManagementController::class, 'checkEmail'])->name('users.check-email');
        
        // User CSV import routes - MUST BE BEFORE resource routes to avoid route conflicts
        Route::post('/users/import', [UserManagementController::class, 'importCsv'])->name('users.import');
        Route::get('/users/import-template', [UserManagementController::class, 'downloadTemplate'])->name('users.import-template');
        
        // User export and bulk action routes - BEFORE resource routes
        Route::post('/users/bulk-action', [UserManagementController::class, 'bulkAction'])->name('users.bulk-action');
        Route::get('/users/export', [UserManagementController::class, 'export'])->name('users.export');
        Route::get('/users-stats', [UserManagementController::class, 'getStats'])->name('users.stats');
        
        // Resource routes for users (creates CRUD routes)
        Route::resource('users', UserManagementController::class)->parameters([
            'users' => 'nip'
        ]);
        
        // User-specific action routes
        Route::post('/users/{nip}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{nip}/update-status', [UserManagementController::class, 'updateStatus'])->name('users.update-status');
        Route::post('/users/{nip}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');

        // Report routes
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/preview', [ReportController::class, 'preview'])->name('preview');
            Route::get('/export', [ReportController::class, 'export'])->name('export');
            Route::post('/schedule', [ReportController::class, 'schedule'])->name('schedule');
            Route::delete('/schedule/{schedule}', [ReportController::class, 'deleteSchedule'])->name('schedule.delete');

            // New ScheduledReportController routes - Fixed naming
            Route::prefix('scheduled-reports')->name('scheduled-reports.')->group(function () {
                Route::get('/', [ScheduledReportController::class, 'index'])->name('index');
                Route::post('/', [ScheduledReportController::class, 'store'])->name('store');
                Route::put('/{scheduledReport}', [ScheduledReportController::class, 'update'])->name('update');
                Route::delete('/{scheduledReport}', [ScheduledReportController::class, 'destroy'])->name('destroy');
            });

            // Legacy routes for backward compatibility
            Route::get('/daily', [ReportController::class, 'daily'])->name('daily');
            Route::get('/weekly', [ReportController::class, 'weekly'])->name('weekly');
            Route::get('/monthly', [ReportController::class, 'monthly'])->name('monthly');
            Route::post('/custom', [ReportController::class, 'custom'])->name('custom');

            // Export routes
            Route::get('/daily/excel', [ReportController::class, 'exportDailyExcel'])->name('daily.excel');
            Route::get('/weekly/excel', [ReportController::class, 'exportWeeklyExcel'])->name('weekly.excel');
            Route::get('/monthly/excel', [ReportController::class, 'exportMonthlyExcel'])->name('monthly.excel');
            Route::post('/custom/excel', [ReportController::class, 'exportCustomExcel'])->name('custom.excel');

            Route::get('/daily/pdf', [ReportController::class, 'exportDailyPdf'])->name('daily.pdf');
            Route::get('/weekly/pdf', [ReportController::class, 'exportWeeklyPdf'])->name('weekly.pdf');
            Route::get('/monthly/pdf', [ReportController::class, 'exportMonthlyPdf'])->name('monthly.pdf');
            Route::post('/custom/pdf', [ReportController::class, 'exportCustomPdf'])->name('custom.pdf');
        });

        // System settings
        Route::get('/system-settings', [SystemController::class, 'index'])->name('system.index');
        Route::put('/system-settings', [SystemController::class, 'update'])->name('system.update');
        Route::post('/system-settings/auto-assignment', [SystemController::class, 'updateAutoAssignment'])->name('system.auto-assignment.toggle');
        Route::post('/system-settings/reset', [SystemController::class, 'reset'])->name('system.reset');
        Route::post('/system-settings/test-email', [SystemController::class, 'testEmail'])->name('system.test-email');

        // Activity Log routes
        Route::prefix('activity-log')->name('activity-log.')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index'])->name('index');
            Route::get('/export', [ActivityLogController::class, 'export'])->name('export');
            Route::get('/{log}', [ActivityLogController::class, 'show'])->name('show');
        });

        // Profile management
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        // Application Management routes (admin helpdesk oversight)
        Route::get('/applications', [ApplicationManagementController::class, 'index'])->name('applications.index');
        Route::get('/applications/export', [ApplicationManagementController::class, 'export'])->name('applications.export');
        Route::post('/applications', [ApplicationManagementController::class, 'store'])->name('applications.store');
        Route::get('/applications/{application}', [ApplicationManagementController::class, 'show'])->name('applications.show');
        Route::put('/applications/{application}', [ApplicationManagementController::class, 'update'])->name('applications.update');
        Route::delete('/applications/{application}', [ApplicationManagementController::class, 'destroy'])->name('applications.destroy');
        Route::post('/applications/{application}/toggle-status', [ApplicationManagementController::class, 'toggleStatus'])->name('applications.toggle-status');

        // Categories Management routes (admin helpdesk oversight)
        Route::get('/categories', [CategoriesManagementController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoriesManagementController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoriesManagementController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoriesManagementController::class, 'destroy'])->name('categories.destroy');
        Route::post('/categories/bulk-action', [CategoriesManagementController::class, 'bulkAction'])->name('categories.bulk-action');
        Route::get('/categories/export', [CategoriesManagementController::class, 'export'])->name('categories.export');

        // Shared admin routes
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
        Route::get('/notifications', [SharedNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}/details', [SharedNotificationController::class, 'getNotificationDetails'])->name('notifications.details');
        Route::post('/notifications', [SharedNotificationController::class, 'store'])->name('notifications.store');
        Route::post('/notifications/{notification}/mark-read', [SharedNotificationController::class, 'markAsReadWeb'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [SharedNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/{notification}', [SharedNotificationController::class, 'destroy'])->name('notifications.destroy');
    });

    // Admin Aplikasi routes (application management)
    Route::middleware('role:admin_aplikasi')->prefix('admin-aplikasi')->name('admin-aplikasi.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminAplikasiDashboardController::class, 'index'])->name('dashboard');
        Route::post('/dashboard/refresh-stats', [AdminAplikasiDashboardController::class, 'refreshStats'])->name('dashboard.refresh-stats');

        // Application Management
        Route::get('/applications', [AdminAplikasiApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/create', [AdminAplikasiApplicationController::class, 'create'])->name('applications.create');
        Route::post('/applications', [AdminAplikasiApplicationController::class, 'store'])->name('applications.store');
        Route::get('/applications/export', [AdminAplikasiApplicationController::class, 'export'])->name('applications.export');
        Route::get('/applications/export-pdf', [AdminAplikasiApplicationController::class, 'exportPdf'])->name('applications.export-pdf');
        Route::post('/applications/bulk-action', [AdminAplikasiApplicationController::class, 'bulkAction'])->name('applications.bulk-action');
        Route::get('/applications/{application}', [AdminAplikasiApplicationController::class, 'show'])->name('applications.show');
        Route::get('/applications/{application}/edit', [AdminAplikasiApplicationController::class, 'edit'])->name('applications.edit');
        Route::put('/applications/{application}', [AdminAplikasiApplicationController::class, 'update'])->name('applications.update');
        Route::delete('/applications/{application}', [AdminAplikasiApplicationController::class, 'destroy'])->name('applications.destroy');
        Route::post('/applications/{application}/assign-teknisi', [AdminAplikasiApplicationController::class, 'assignTeknisi'])->name('applications.assign-teknisi');
        Route::post('/applications/{application}/maintenance', [AdminAplikasiApplicationController::class, 'toggleMaintenance'])->name('applications.maintenance');
        Route::post('/applications/{application}/health-check', [AdminAplikasiApplicationController::class, 'healthCheck'])->name('applications.health-check');

        // Category Management
        Route::get('/categories', [AdminAplikasiCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [AdminAplikasiCategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}', [AdminAplikasiCategoryController::class, 'show'])->name('categories.show');
        Route::put('/categories/{category}', [AdminAplikasiCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [AdminAplikasiCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/categories/{category}/update-status', [AdminAplikasiCategoryController::class, 'updateStatus'])->name('categories.update-status');
        Route::post('/categories/bulk-action', [AdminAplikasiCategoryController::class, 'bulkAction'])->name('categories.bulk-action');
        Route::get('/categories/export', [AdminAplikasiCategoryController::class, 'export'])->name('categories.export');

        // Analytics
        Route::get('/analytics', [AdminAplikasiAnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/export', [AdminAplikasiAnalyticsController::class, 'export'])->name('analytics.export');

        // Notifications
        Route::get('/notifications', [SharedNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}/details', [SharedNotificationController::class, 'getNotificationDetails'])->name('notifications.details');
        Route::post('/notifications/{notification}/mark-read', [SharedNotificationController::class, 'markAsReadWeb'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [SharedNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/{notification}', [SharedNotificationController::class, 'destroy'])->name('notifications.destroy');

        // Profile management
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    // Teknisi routes (technical support)
    Route::middleware('role:teknisi')->prefix('teknisi')->name('teknisi.')->group(function () {
        Route::get('/dashboard', [TeknisiDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard-stats', [TeknisiDashboardController::class, 'getStats'])->name('dashboard.stats');

        // Ticket handling routes
        Route::get('/ticket-handling', [TicketHandlingController::class, 'index'])->name('ticket-handling.index');
        Route::get('/tickets', [TicketHandlingController::class, 'myTickets'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [TicketHandlingController::class, 'show'])->name('tickets.show');
        Route::get('/tickets/{ticket}/details', [TicketHandlingController::class, 'getTicketDetails'])->name('tickets.details');
        Route::get('/tickets/{ticket}/timeline', [TicketHandlingController::class, 'getTicketTimeline'])->name('tickets.timeline');
        Route::post('/tickets/{ticket}/comments', [TicketHandlingController::class, 'addComment'])->name('tickets.comments.store');
        Route::post('/tickets/{ticket}/update-status', [TicketHandlingController::class, 'updateStatus'])->name('tickets.update-status');
        Route::post('/tickets/{ticket}/resolve', [TicketHandlingController::class, 'resolve'])->name('tickets.resolve');
        Route::post('/tickets/{ticket}/reassign', [TicketHandlingController::class, 'reassign'])->name('tickets.reassign');
        Route::post('/tickets/{ticket}/technical-notes', [TicketHandlingController::class, 'addTechnicalNote'])->name('tickets.technical-notes.store');
        Route::post('/tickets/{ticket}/first-response', [TicketHandlingController::class, 'markFirstResponse'])->name('tickets.first-response');
        Route::post('/tickets/{ticket}/request-reassignment', [TicketHandlingController::class, 'requestReassignment'])->name('tickets.request-reassignment');
        Route::post('/tickets/{ticket}/upload-solution', [TicketHandlingController::class, 'uploadSolutionDoc'])->name('tickets.upload-solution');

        // Knowledge base CRUD
        Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('knowledge-base.index');
        Route::get('/knowledge-base/search', [KnowledgeBaseController::class, 'search'])->name('knowledge-base.search');
        Route::get('/knowledge-base/{id}', [KnowledgeBaseController::class, 'show'])->name('knowledge-base.show');
        Route::post('/knowledge-base', [KnowledgeBaseController::class, 'store'])->name('knowledge-base.store');
        Route::put('/knowledge-base/{id}', [KnowledgeBaseController::class, 'update'])->name('knowledge-base.update');
        Route::delete('/knowledge-base/{id}', [KnowledgeBaseController::class, 'destroy'])->name('knowledge-base.destroy');
        Route::post('/knowledge-base/{id}/view', [KnowledgeBaseController::class, 'incrementViewCount'])->name('knowledge-base.view');
        Route::post('/knowledge-base/{id}/helpful', [KnowledgeBaseController::class, 'markAsHelpful'])->name('knowledge-base.helpful');
        Route::get('/knowledge-base/{id}/export', [KnowledgeBaseController::class, 'exportArticle'])->name('knowledge-base.export');
        Route::get('/knowledge-base/export/all', [KnowledgeBaseController::class, 'exportAll'])->name('knowledge-base.export-all');

        // Reports
        Route::get('/reports', [TeknisiController::class, 'reports'])->name('reports.index');
        Route::get('/reports/export', [TeknisiController::class, 'exportReport'])->name('reports.export');

        // Profile management
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        // Notifications
        Route::get('/notifications', [SharedNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}/details', [SharedNotificationController::class, 'getNotificationDetails'])->name('notifications.details');
        Route::post('/notifications/{notification}/mark-read', [SharedNotificationController::class, 'markAsReadWeb'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [SharedNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/{notification}', [SharedNotificationController::class, 'destroy'])->name('notifications.destroy');
    });

    // User routes (regular employees)
    Route::middleware('role:user')->prefix('user')->name('user.')->group(function () {
        Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard-stats', [UserDashboardController::class, 'getDashboardStats'])->name('dashboard.stats');

        // Ticket management
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::resource('tickets', TicketController::class)->except(['destroy']);
        Route::post('/tickets/{ticket}/comments', [TicketController::class, 'addComment'])->name('tickets.comments.store');
        Route::put('/tickets/{ticket}/comments/{comment}', [TicketController::class, 'updateComment'])->name('tickets.comments.update');
        Route::delete('/tickets/{ticket}/comments/{comment}', [TicketController::class, 'deleteComment'])->name('tickets.comments.destroy');
        Route::post('/tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
        Route::post('/tickets/{ticket}/rate', [TicketController::class, 'rateTicket'])->name('tickets.rate');
        Route::get('/tickets/{ticket}/download/{filename}', [TicketController::class, 'downloadAttachment'])->name('tickets.download');
        Route::get('/tickets/{ticket}/download-all', [TicketController::class, 'downloadAllAttachments'])->name('tickets.download-all');
        Route::get('/tickets/export', [TicketController::class, 'export'])->name('tickets.export');

        // Ticket draft management
        Route::post('/tickets/drafts/save', [TicketController::class, 'saveDraft'])->name('tickets.drafts.save');
        Route::get('/tickets/drafts/load', [TicketController::class, 'loadDraft'])->name('tickets.drafts.load');
        Route::delete('/tickets/drafts/delete', [TicketController::class, 'deleteDraft'])->name('tickets.drafts.delete');

        // History page
        Route::get('/history', [App\Http\Controllers\User\TicketHistoryController::class, 'index'])->name('history.index');
        Route::get('/history/export', [App\Http\Controllers\User\TicketHistoryController::class, 'export'])->name('history.export');

        // Applications access
        Route::get('/applications', [UserController::class, 'applications'])->name('applications.index');

        // Notifications
        Route::get('/notifications', [SharedNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}/details', [SharedNotificationController::class, 'getNotificationDetails'])->name('notifications.details');
        Route::post('/notifications/{notification}/mark-read', [SharedNotificationController::class, 'markAsReadWeb'])->name('notifications.mark-read');
        Route::post('/notifications/mark-all-read', [SharedNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::delete('/notifications/{notification}', [SharedNotificationController::class, 'destroy'])->name('notifications.destroy');

        // Profile management
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    // Multi-role shared routes
    Route::middleware('role:teknisi|user')->prefix('support')->name('support.')->group(function () {
        Route::get('/faq', [SupportController::class, 'faq'])->name('faq');
        Route::get('/contact', [SupportController::class, 'contact'])->name('contact');
    });

    // Default dashboard route (redirects based on role)
    Route::get('/dashboard', function () {
        // Derive role solely from Auth::guard('web') via AuthService
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            $role = app(\App\Services\AuthService::class)->getUserRole($user);
            return app(\App\Services\RoleRouteService::class)::redirectToDashboard($role);
        }

        // Default to login if not authenticated
        return redirect()->route('login');
    })->name('dashboard');
}); // Close auth middleware group
