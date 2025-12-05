<?php

namespace App\Http\Controllers\AdminHelpdesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\AdminHelpdesk;
use App\Models\SystemSetting;
use App\Models\Backup;
use App\Services\BackupService;

class SystemController extends Controller
{
    /**
     * Display system settings page.
     */
    public function index()
    {
        $admin = Auth::user();

        // Get current system settings from database - aligned with frontend expectations
        $settings = [
            'general' => [
                'system_name' => SystemSetting::get('system_name', config('app.name')),
                'system_email' => SystemSetting::get('system_email', config('mail.from.address')),
                'default_language' => SystemSetting::get('default_language', 'id'),
                'timezone' => SystemSetting::get('timezone', config('app.timezone')),
                'items_per_page' => SystemSetting::get('items_per_page', 15),
                'session_timeout' => SystemSetting::get('session_timeout', 120),
                'max_file_size' => SystemSetting::get('max_file_size', 2),
                'max_files_per_ticket' => SystemSetting::get('max_files_per_ticket', 5),
                'allowed_file_types' => SystemSetting::get('allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png'),
            ],
            'email' => [
                'mail_driver' => SystemSetting::get('mail_driver', 'smtp'),
                'mail_host' => SystemSetting::get('mail_host', 'localhost'),
                'mail_port' => SystemSetting::get('mail_port', 587),
                'mail_username' => SystemSetting::get('mail_username', ''),
                'mail_password' => SystemSetting::get('mail_password', ''),
                'mail_encryption' => SystemSetting::get('mail_encryption', 'tls'),
                'notify_new_ticket' => SystemSetting::get('notify_ticket_created', true),
                'notify_ticket_assigned' => SystemSetting::get('notify_ticket_assigned', true),
                'notify_status_change' => SystemSetting::get('notify_ticket_updated', true),
                'notify_comment_added' => SystemSetting::get('notify_comment_added', true),
            ],
            'tickets' => [
                'auto_assign_enabled' => SystemSetting::get('auto_assign_enabled', false),
                'auto_assign_algorithm' => SystemSetting::get('auto_assign_algorithm', 'load_balanced'),
                'default_priority' => SystemSetting::get('default_priority', 'medium'),
                'auto_close_resolved_days' => SystemSetting::get('auto_close_days', 7),
                'max_concurrent_tickets' => SystemSetting::get('max_concurrent_tickets', 10),
                'escalation_urgent_hours' => SystemSetting::get('escalation_urgent_hours', 2),
                'escalation_high_hours' => SystemSetting::get('escalation_high_hours', 4),
                'allow_reopen' => SystemSetting::get('allow_reopening', 'within_7d'),
                'working_hours_start' => SystemSetting::get('working_hours_start', '08:00'),
                'working_hours_end' => SystemSetting::get('working_hours_end', '17:00'),
                'working_days' => SystemSetting::get('working_days', [1, 2, 3, 4, 5]),
                // SLA settings merged into tickets tab
                'sla_urgent_response' => SystemSetting::get('sla_urgent_response', 2),
                'sla_urgent_resolution' => SystemSetting::get('sla_urgent_resolution', 8),
                'sla_high_response' => SystemSetting::get('sla_high_response', 4),
                'sla_high_resolution' => SystemSetting::get('sla_high_resolution', 24),
                'sla_medium_response' => SystemSetting::get('sla_medium_response', 8),
                'sla_medium_resolution' => SystemSetting::get('sla_medium_resolution', 48),
                'sla_low_response' => SystemSetting::get('sla_low_response', 24),
                'sla_low_resolution' => SystemSetting::get('sla_low_resolution', 120),
            ],
            'security' => [
                'min_password_length' => SystemSetting::get('password_min_length', 8),
                'password_expiry' => SystemSetting::get('password_expiry_days', 90),
                'require_uppercase' => SystemSetting::get('password_require_uppercase', true),
                'require_lowercase' => SystemSetting::get('password_require_lowercase', true),
                'require_numbers' => SystemSetting::get('password_require_numbers', true),
                'require_symbols' => SystemSetting::get('password_require_symbols', false),
                'max_login_attempts' => SystemSetting::get('max_login_attempts', 5),
                'lockout_duration' => SystemSetting::get('lockout_duration', 15),
                'enable_two_factor' => SystemSetting::get('enable_two_factor', false),
                'login_notifications' => SystemSetting::get('login_notifications', false),
            ],
            'backup' => [
                'auto_backup' => SystemSetting::get('auto_backup', 'daily'),
                'retention_days' => SystemSetting::get('retention_days', 30),
                'location' => SystemSetting::get('backup_location', 'local'),
            ],
        ];

        // Get system health status
        $healthStatus = $this->getSystemHealthStatus();

        // Get recent system logs
        $recentLogs = $this->getRecentSystemLogs();

        // Get backup history from database
        $backupHistory = Backup::orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($backup) {
                return [
                    'id' => $backup->id,
                    'filename' => $backup->filename,
                    'type' => $backup->type_label,
                    'size' => $backup->size_formatted,
                    'status' => $backup->status,
                    'location' => $backup->location,
                    'created_at' => $backup->created_at->toISOString(),
                    'completed_at' => $backup->completed_at?->toISOString(),
                ];
            })
            ->toArray();

        return Inertia::render('AdminHelpdesk/SystemSettings', [
            'settings' => $settings,
            'healthStatus' => $healthStatus,
            'recentLogs' => $recentLogs,
            'backupHistory' => $backupHistory,
        ]);
    }

    /**
     * Update system settings.
     */
    public function update(Request $request)
    {
        $admin = Auth::user();

        // Validate request data - aligned with frontend field names
        $validator = Validator::make($request->all(), [
            // General settings
            'general.system_name' => 'nullable|string|max:255',
            'general.system_email' => 'nullable|email|max:255',
            'general.default_language' => 'nullable|string|in:id,en',
            'general.timezone' => 'nullable|string|max:50',
            'general.items_per_page' => 'nullable|integer|min:5|max:100',
            'general.session_timeout' => 'nullable|integer|min:15|max:480',
            'general.max_file_size' => 'nullable|integer|min:1|max:50',
            'general.max_files_per_ticket' => 'nullable|integer|min:1|max:20',
            'general.allowed_file_types' => 'nullable|string|max:255',

            // Email settings
            'email.mail_driver' => 'nullable|string|in:smtp,mail,sendmail',
            'email.mail_host' => 'nullable|string|max:255',
            'email.mail_port' => 'nullable|integer|min:1|max:65535',
            'email.mail_username' => 'nullable|string|max:255',
            'email.mail_password' => 'nullable|string|max:255',
            'email.mail_encryption' => 'nullable|string|in:,tls,ssl,none',
            'email.notify_new_ticket' => 'nullable|boolean',
            'email.notify_ticket_assigned' => 'nullable|boolean',
            'email.notify_status_change' => 'nullable|boolean',
            'email.notify_comment_added' => 'nullable|boolean',

            // Ticket settings
            'tickets.auto_assign_enabled' => 'nullable|boolean',
            'tickets.auto_assign_algorithm' => 'nullable|string|in:round_robin,load_balanced,random',
            'tickets.default_priority' => 'nullable|string|in:low,medium,high,urgent',
            'tickets.auto_close_resolved_days' => 'nullable|integer|min:1|max:365',
            'tickets.max_concurrent_tickets' => 'nullable|integer|min:1|max:100',
            'tickets.escalation_urgent_hours' => 'nullable|numeric|min:0.5|max:168',
            'tickets.escalation_high_hours' => 'nullable|numeric|min:0.5|max:168',
            'tickets.allow_reopen' => 'nullable|string|in:disabled,within_24h,within_7d,always',
            'tickets.working_hours_start' => 'nullable|string',
            'tickets.working_hours_end' => 'nullable|string',
            'tickets.working_days' => 'nullable|array',
            // SLA settings (now in tickets)
            'tickets.sla_urgent_response' => 'nullable|numeric|min:0.5|max:168',
            'tickets.sla_urgent_resolution' => 'nullable|integer|min:1|max:720',
            'tickets.sla_high_response' => 'nullable|integer|min:1|max:168',
            'tickets.sla_high_resolution' => 'nullable|integer|min:1|max:720',
            'tickets.sla_medium_response' => 'nullable|integer|min:1|max:168',
            'tickets.sla_medium_resolution' => 'nullable|integer|min:1|max:720',
            'tickets.sla_low_response' => 'nullable|integer|min:1|max:168',
            'tickets.sla_low_resolution' => 'nullable|integer|min:1|max:720',

            // Security settings
            'security.min_password_length' => 'nullable|integer|min:6|max:20',
            'security.password_expiry' => 'nullable|integer|min:0|max:365',
            'security.require_uppercase' => 'nullable|boolean',
            'security.require_lowercase' => 'nullable|boolean',
            'security.require_numbers' => 'nullable|boolean',
            'security.require_symbols' => 'nullable|boolean',
            'security.max_login_attempts' => 'nullable|integer|min:1|max:20',
            'security.lockout_duration' => 'nullable|integer|min:1|max:1440',
            'security.enable_two_factor' => 'nullable|boolean',
            'security.login_notifications' => 'nullable|boolean',

            // Backup settings
            'backup.auto_backup' => 'nullable|string|in:disabled,daily,weekly,monthly',
            'backup.retention_days' => 'nullable|integer|min:1|max:365',
            'backup.location' => 'nullable|string|in:local,s3,google_drive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Use database transaction for atomic updates
            DB::beginTransaction();

            $updatedSettings = [];

            // Update General settings (aligned with frontend field names)
            if ($request->has('general.system_name')) {
                SystemSetting::set('system_name', $request->input('general.system_name'), 'general', 'Application name displayed throughout the system');
                $updatedSettings[] = 'system_name';
            }

            if ($request->has('general.system_email')) {
                SystemSetting::set('system_email', $request->input('general.system_email'), 'general', 'Default system email for notifications');
                $updatedSettings[] = 'system_email';
            }

            if ($request->has('general.default_language')) {
                SystemSetting::set('default_language', $request->input('general.default_language'), 'general', 'Default language (id=Indonesian, en=English)');
                $updatedSettings[] = 'default_language';
            }

            if ($request->has('general.timezone')) {
                SystemSetting::set('timezone', $request->input('general.timezone'), 'general', 'System timezone');
                $updatedSettings[] = 'timezone';
            }

            if ($request->has('general.items_per_page')) {
                SystemSetting::set('items_per_page', $request->integer('general.items_per_page'), 'general', 'Default pagination size');
                $updatedSettings[] = 'items_per_page';
            }

            if ($request->has('general.session_timeout')) {
                SystemSetting::set('session_timeout', $request->integer('general.session_timeout'), 'general', 'Session timeout in minutes');
                $updatedSettings[] = 'session_timeout';
            }

            if ($request->has('general.max_file_size')) {
                SystemSetting::set('max_file_size', $request->integer('general.max_file_size'), 'general', 'Maximum file upload size in MB');
                $updatedSettings[] = 'max_file_size';
            }

            if ($request->has('general.max_files_per_ticket')) {
                SystemSetting::set('max_files_per_ticket', $request->integer('general.max_files_per_ticket'), 'general', 'Maximum files allowed per ticket');
                $updatedSettings[] = 'max_files_per_ticket';
            }

            if ($request->has('general.allowed_file_types')) {
                SystemSetting::set('allowed_file_types', $request->input('general.allowed_file_types'), 'general', 'Allowed file extensions (comma-separated)');
                $updatedSettings[] = 'allowed_file_types';
            }

            // Update Email settings
            if ($request->has('email.mail_driver')) {
                SystemSetting::set('mail_driver', $request->input('email.mail_driver'), 'email', 'Mail driver (smtp, sendmail, mailgun)');
                $updatedSettings[] = 'mail_driver';
            }

            if ($request->has('email.mail_host')) {
                SystemSetting::set('mail_host', $request->input('email.mail_host'), 'email', 'SMTP host server');
                $updatedSettings[] = 'mail_host';
            }

            if ($request->has('email.mail_port')) {
                SystemSetting::set('mail_port', $request->integer('email.mail_port'), 'email', 'SMTP port');
                $updatedSettings[] = 'mail_port';
            }

            if ($request->has('email.mail_username')) {
                SystemSetting::set('mail_username', $request->input('email.mail_username'), 'email', 'SMTP username');
                $updatedSettings[] = 'mail_username';
            }

            if ($request->has('email.mail_password')) {
                SystemSetting::set('mail_password', $request->input('email.mail_password'), 'email', 'SMTP password (encrypted)');
                $updatedSettings[] = 'mail_password';
            }

            if ($request->has('email.mail_encryption')) {
                SystemSetting::set('mail_encryption', $request->input('email.mail_encryption'), 'email', 'SMTP encryption (tls, ssl, none)');
                $updatedSettings[] = 'mail_encryption';
            }

            if ($request->has('email.notify_new_ticket')) {
                SystemSetting::set('notify_ticket_created', $request->boolean('email.notify_new_ticket'), 'notifications', 'Notify when ticket is created');
                $updatedSettings[] = 'notify_ticket_created';
            }

            if ($request->has('email.notify_ticket_assigned')) {
                SystemSetting::set('notify_ticket_assigned', $request->boolean('email.notify_ticket_assigned'), 'notifications', 'Notify when ticket is assigned');
                $updatedSettings[] = 'notify_ticket_assigned';
            }

            if ($request->has('email.notify_status_change')) {
                SystemSetting::set('notify_ticket_updated', $request->boolean('email.notify_status_change'), 'notifications', 'Notify when ticket is updated');
                $updatedSettings[] = 'notify_ticket_updated';
            }

            if ($request->has('email.notify_comment_added')) {
                SystemSetting::set('notify_comment_added', $request->boolean('email.notify_comment_added'), 'notifications', 'Notify when comment is added');
                $updatedSettings[] = 'notify_comment_added';
            }

            // Update Ticket settings
            if ($request->has('tickets.auto_assign_enabled')) {
                SystemSetting::set('auto_assign_enabled', $request->boolean('tickets.auto_assign_enabled'), 'tickets', 'Enable automatic ticket assignment to available teknisi');
                $updatedSettings[] = 'auto_assign_enabled';
            }

            if ($request->has('tickets.auto_assign_algorithm')) {
                SystemSetting::set('auto_assign_algorithm', $request->input('tickets.auto_assign_algorithm'), 'tickets', 'Algorithm for auto-assignment: round_robin or load_balanced');
                $updatedSettings[] = 'auto_assign_algorithm';
            }

            if ($request->has('tickets.default_priority')) {
                SystemSetting::set('default_priority', $request->input('tickets.default_priority'), 'tickets', 'Default priority for new tickets');
                $updatedSettings[] = 'default_priority';
            }

            if ($request->has('tickets.auto_close_resolved_days')) {
                SystemSetting::set('auto_close_days', $request->integer('tickets.auto_close_resolved_days'), 'tickets', 'Auto-close resolved tickets after X days');
                $updatedSettings[] = 'auto_close_days';
            }

            if ($request->has('tickets.max_concurrent_tickets')) {
                SystemSetting::set('max_concurrent_tickets', $request->integer('tickets.max_concurrent_tickets'), 'tickets', 'Max concurrent tickets per teknisi');
                $updatedSettings[] = 'max_concurrent_tickets';
            }

            if ($request->has('tickets.escalation_urgent_hours')) {
                SystemSetting::set('escalation_urgent_hours', (float) $request->input('tickets.escalation_urgent_hours'), 'tickets', 'Escalate urgent tickets if unassigned after X hours');
                $updatedSettings[] = 'escalation_urgent_hours';
            }

            if ($request->has('tickets.escalation_high_hours')) {
                SystemSetting::set('escalation_high_hours', (float) $request->input('tickets.escalation_high_hours'), 'tickets', 'Escalate high priority tickets if unassigned after X hours');
                $updatedSettings[] = 'escalation_high_hours';
            }

            if ($request->has('tickets.allow_reopen')) {
                SystemSetting::set('allow_reopening', $request->input('tickets.allow_reopen'), 'tickets', 'Allow users to reopen closed tickets');
                $updatedSettings[] = 'allow_reopening';
            }

            if ($request->has('tickets.working_hours_start')) {
                SystemSetting::set('working_hours_start', $request->input('tickets.working_hours_start'), 'general', 'Start of working hours for SLA calculations');
                $updatedSettings[] = 'working_hours_start';
            }

            if ($request->has('tickets.working_hours_end')) {
                SystemSetting::set('working_hours_end', $request->input('tickets.working_hours_end'), 'general', 'End of working hours for SLA calculations');
                $updatedSettings[] = 'working_hours_end';
            }

            if ($request->has('tickets.working_days')) {
                SystemSetting::set('working_days', $request->input('tickets.working_days'), 'general', 'Working days for SLA calculations (1=Mon, 7=Sun)');
                $updatedSettings[] = 'working_days';
            }

            // Update SLA settings (now nested in tickets)
            if ($request->has('tickets.sla_urgent_response')) {
                SystemSetting::set('sla_urgent_response', $request->input('tickets.sla_urgent_response'), 'sla', 'Urgent priority response time (hours)');
                $updatedSettings[] = 'sla_urgent_response';
            }

            if ($request->has('tickets.sla_urgent_resolution')) {
                SystemSetting::set('sla_urgent_resolution', $request->integer('tickets.sla_urgent_resolution'), 'sla', 'Urgent priority resolution time (hours)');
                $updatedSettings[] = 'sla_urgent_resolution';
            }

            if ($request->has('tickets.sla_high_response')) {
                SystemSetting::set('sla_high_response', $request->integer('tickets.sla_high_response'), 'sla', 'High priority response time (hours)');
                $updatedSettings[] = 'sla_high_response';
            }

            if ($request->has('tickets.sla_high_resolution')) {
                SystemSetting::set('sla_high_resolution', $request->integer('tickets.sla_high_resolution'), 'sla', 'High priority resolution time (hours)');
                $updatedSettings[] = 'sla_high_resolution';
            }

            if ($request->has('tickets.sla_medium_response')) {
                SystemSetting::set('sla_medium_response', $request->integer('tickets.sla_medium_response'), 'sla', 'Medium priority response time (hours)');
                $updatedSettings[] = 'sla_medium_response';
            }

            if ($request->has('tickets.sla_medium_resolution')) {
                SystemSetting::set('sla_medium_resolution', $request->integer('tickets.sla_medium_resolution'), 'sla', 'Medium priority resolution time (hours)');
                $updatedSettings[] = 'sla_medium_resolution';
            }

            if ($request->has('tickets.sla_low_response')) {
                SystemSetting::set('sla_low_response', $request->integer('tickets.sla_low_response'), 'sla', 'Low priority response time (hours)');
                $updatedSettings[] = 'sla_low_response';
            }

            if ($request->has('tickets.sla_low_resolution')) {
                SystemSetting::set('sla_low_resolution', $request->integer('tickets.sla_low_resolution'), 'sla', 'Low priority resolution time (hours)');
                $updatedSettings[] = 'sla_low_resolution';
            }

            // Update Security settings
            if ($request->has('security.min_password_length')) {
                SystemSetting::set('password_min_length', $request->integer('security.min_password_length'), 'security', 'Minimum password length');
                $updatedSettings[] = 'password_min_length';
            }

            if ($request->has('security.password_expiry')) {
                SystemSetting::set('password_expiry_days', $request->integer('security.password_expiry'), 'security', 'Password expiry in days (0=disabled)');
                $updatedSettings[] = 'password_expiry_days';
            }

            if ($request->has('security.require_uppercase')) {
                SystemSetting::set('password_require_uppercase', $request->boolean('security.require_uppercase'), 'security', 'Require uppercase letters in password');
                $updatedSettings[] = 'password_require_uppercase';
            }

            if ($request->has('security.require_lowercase')) {
                SystemSetting::set('password_require_lowercase', $request->boolean('security.require_lowercase'), 'security', 'Require lowercase letters in password');
                $updatedSettings[] = 'password_require_lowercase';
            }

            if ($request->has('security.require_numbers')) {
                SystemSetting::set('password_require_numbers', $request->boolean('security.require_numbers'), 'security', 'Require numbers in password');
                $updatedSettings[] = 'password_require_numbers';
            }

            if ($request->has('security.require_symbols')) {
                SystemSetting::set('password_require_symbols', $request->boolean('security.require_symbols'), 'security', 'Require special symbols in password');
                $updatedSettings[] = 'password_require_symbols';
            }

            if ($request->has('security.max_login_attempts')) {
                SystemSetting::set('max_login_attempts', $request->integer('security.max_login_attempts'), 'security', 'Max login attempts before lockout');
                $updatedSettings[] = 'max_login_attempts';
            }

            if ($request->has('security.lockout_duration')) {
                SystemSetting::set('lockout_duration', $request->integer('security.lockout_duration'), 'security', 'Lockout duration in minutes');
                $updatedSettings[] = 'lockout_duration';
            }

            if ($request->has('security.enable_two_factor')) {
                SystemSetting::set('enable_two_factor', $request->boolean('security.enable_two_factor'), 'security', 'Enable two-factor authentication');
                $updatedSettings[] = 'enable_two_factor';
            }

            if ($request->has('security.login_notifications')) {
                SystemSetting::set('login_notifications', $request->boolean('security.login_notifications'), 'security', 'Send login notification emails');
                $updatedSettings[] = 'login_notifications';
            }

            // Update Backup settings
            if ($request->has('backup.auto_backup')) {
                SystemSetting::set('auto_backup', $request->input('backup.auto_backup'), 'backup', 'Auto backup frequency (disabled, daily, weekly, monthly)');
                $updatedSettings[] = 'auto_backup';
            }

            if ($request->has('backup.retention_days')) {
                SystemSetting::set('retention_days', $request->integer('backup.retention_days'), 'backup', 'Backup retention period in days');
                $updatedSettings[] = 'retention_days';
            }

            if ($request->has('backup.location')) {
                SystemSetting::set('backup_location', $request->input('backup.location'), 'backup', 'Backup storage location (local, s3, google_drive)');
                $updatedSettings[] = 'backup_location';
            }

            // Update who made the changes
            foreach ($updatedSettings as $key) {
                $setting = SystemSetting::where('key', $key)->first();
                if ($setting) {
                    $setting->update([
                        'updated_by_nip' => $admin->nip,
                        'updated_by_type' => 'admin_helpdesk',
                    ]);
                }
            }

            DB::commit();

            // Clear all system setting caches
            SystemSetting::clearAllCaches();

            // Log the settings update
            Log::info('System settings updated', [
                'admin_nip' => $admin->nip,
                'admin_name' => $admin->name,
                'updated_settings' => $updatedSettings,
            ]);

            return redirect()->back()->with('success', 'System settings updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update system settings', [
                'admin_nip' => $admin->nip,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to update system settings: ' . $e->getMessage());
        }
    }

    /**
     * Update auto-assignment setting.
     */
    public function updateAutoAssignment(Request $request)
    {
        $admin = Auth::user();

        $validator = Validator::make($request->all(), [
            'enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            // Update the auto-assignment setting
            SystemSetting::set(
                'auto_assign_enabled',
                $request->boolean('enabled'),
                'tickets',
                'Enable automatic ticket assignment to available teknisi'
            );

            // Update who made the change
            $setting = SystemSetting::where('key', 'auto_assign_enabled')->first();
            if ($setting) {
                $setting->update([
                    'updated_by_nip' => $admin->nip,
                    'updated_by_type' => 'admin_helpdesk',
                ]);
            }

            DB::commit();

            // Clear cache
            SystemSetting::clearAllCaches();

            // Log the change
            Log::info('Auto-assignment setting updated', [
                'admin_nip' => $admin->nip,
                'admin_name' => $admin->name,
                'enabled' => $request->boolean('enabled'),
            ]);

            $status = $request->boolean('enabled') ? 'enabled' : 'disabled';
            return redirect()->back()->with('success', "Auto-assignment {$status} successfully");

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update auto-assignment setting', [
                'admin_nip' => $admin->nip,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to update auto-assignment setting: ' . $e->getMessage());
        }
    }

    /**
     * Get system health status.
     */
    private function getSystemHealthStatus(): array
    {
        $health = [];

        // Database connection check
        try {
            DB::connection()->getPdo();
            $health['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection is working',
            ];
        } catch (\Exception $e) {
            $health['database'] = [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }

        // Cache check
        try {
            Cache::store()->put('health_check', 'ok', 10);
            $cacheValue = Cache::store()->get('health_check');
            $health['cache'] = [
                'status' => $cacheValue === 'ok' ? 'healthy' : 'warning',
                'message' => $cacheValue === 'ok' ? 'Cache is working' : 'Cache may have issues',
            ];
        } catch (\Exception $e) {
            $health['cache'] = [
                'status' => 'error',
                'message' => 'Cache system failed: ' . $e->getMessage(),
            ];
        }

        // Storage check
        try {
            $storagePath = storage_path('app');
            $health['storage'] = [
                'status' => is_writable($storagePath) ? 'healthy' : 'warning',
                'message' => is_writable($storagePath) ? 'Storage is writable' : 'Storage may not be writable',
            ];
        } catch (\Exception $e) {
            $health['storage'] = [
                'status' => 'error',
                'message' => 'Storage check failed: ' . $e->getMessage(),
            ];
        }

        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryPercentage = $memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 1) : 0;

        $health['memory'] = [
            'status' => $memoryPercentage > 90 ? 'warning' : 'healthy',
            'message' => "Memory usage: {$memoryPercentage}%",
            'usage' => $memoryUsage,
            'limit' => $memoryLimit,
            'percentage' => $memoryPercentage,
        ];

        return $health;
    }

    /**
     * Get recent system logs.
     */
    private function getRecentSystemLogs(int $limit = 50): array
    {
        try {
            $logPath = storage_path('logs/laravel.log');
            $logs = [];

            if (file_exists($logPath)) {
                $file = new \SplFileObject($logPath, 'r');
                $file->seek(PHP_INT_MAX);
                $totalLines = $file->key();

                $linesToRead = min($limit, $totalLines);
                $file->seek($totalLines - $linesToRead);

                while (!$file->eof()) {
                    $line = trim($file->fgets());
                    if (!empty($line)) {
                        $logs[] = [
                            'message' => $line,
                            'timestamp' => now()->toDateTimeString(),
                        ];
                    }
                }
            }

            return array_reverse($logs);
        } catch (\Exception $e) {
            return [
                [
                    'message' => 'Error reading log file: ' . $e->getMessage(),
                    'timestamp' => now()->toDateTimeString(),
                ]
            ];
        }
    }

    /**
     * Reset system settings to defaults.
     */
    public function reset(Request $request)
    {
        $admin = Auth::user();

        try {
            // Use the service to reset settings
            $service = app(\App\Services\SystemSettingsService::class);
            $success = $service->resetToDefaults();

            if ($success) {
                Log::info('System settings reset to defaults', [
                    'admin_nip' => $admin->nip,
                    'admin_name' => $admin->name,
                ]);

                return redirect()->back()->with('success', 'System settings reset to defaults successfully');
            } else {
                return redirect()->back()->with('error', 'Failed to reset system settings');
            }

        } catch (\Exception $e) {
            Log::error('Failed to reset system settings', [
                'admin_nip' => $admin->nip,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to reset system settings: ' . $e->getMessage());
        }
    }

    /**
     * Send test email to verify email settings.
     */
    public function testEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $admin = Auth::user();
        $email = $request->input('email');

        try {
            // Get email settings from service
            $service = app(\App\Services\SystemSettingsService::class);
            $emailSettings = $service->getEmailSettings();
            
            // Configure mailer temporarily with current settings
            \Config::set('mail.mailers.smtp.host', $emailSettings['mail_host']);
            \Config::set('mail.mailers.smtp.port', $emailSettings['mail_port']);
            \Config::set('mail.mailers.smtp.encryption', $emailSettings['mail_encryption']);
            \Config::set('mail.mailers.smtp.username', $emailSettings['mail_username']);
            \Config::set('mail.mailers.smtp.password', $emailSettings['mail_password']);
            \Config::set('mail.from.address', $emailSettings['mail_from_address']);
            \Config::set('mail.from.name', $emailSettings['mail_from_name']);
            
            // Send test email
            \Mail::raw('This is a test email from HelpDesk Kemlu system. If you received this, your email settings are configured correctly!', function ($message) use ($email, $emailSettings) {
                $message->to($email)
                        ->from($emailSettings['mail_from_address'], $emailSettings['mail_from_name'])
                        ->subject('Test Email - HelpDesk Kemlu System Settings');
            });

            Log::info('Test email sent successfully', [
                'admin_nip' => $admin->nip,
                'test_email' => $email,
                'smtp_host' => $emailSettings['mail_host'],
            ]);

            return redirect()->back()->with('success', "Test email sent successfully to {$email}");

        } catch (\Exception $e) {
            Log::error('Failed to send test email', [
                'admin_nip' => $admin->nip,
                'test_email' => $email,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }

    /**
     * Parse memory limit string to bytes.
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => (int) $memoryLimit,
        };
    }
}