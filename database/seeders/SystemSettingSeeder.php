<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // General Category Settings
        $generalSettings = [
            [
                'key' => 'app_name',
                'value' => env('HELPDESK_EMAIL_FROM_NAME', 'HelpDesk Kemlu'),
                'type' => 'string',
                'category' => 'general',
                'description' => 'Application name displayed in the header and titles',
                'is_public' => true,
            ],
            [
                'key' => 'app_timezone',
                'value' => env('HELPDESK_TIMEZONE', 'Asia/Jakarta'),
                'type' => 'string',
                'category' => 'general',
                'description' => 'Default timezone for the application',
                'is_public' => true,
            ],
            [
                'key' => 'working_hours_start',
                'value' => env('HELPDESK_WORKING_HOURS_START', '08:00'),
                'type' => 'string',
                'category' => 'general',
                'description' => 'Start of working hours for SLA calculations',
                'is_public' => true,
            ],
            [
                'key' => 'working_hours_end',
                'value' => env('HELPDESK_WORKING_HOURS_END', '17:00'),
                'type' => 'string',
                'category' => 'general',
                'description' => 'End of working hours for SLA calculations',
                'is_public' => true,
            ],
            [
                'key' => 'working_days',
                'value' => json_encode([1, 2, 3, 4, 5]), // Monday-Friday
                'type' => 'json',
                'category' => 'general',
                'description' => 'Working days for SLA calculations (1=Monday, 7=Sunday)',
                'is_public' => true,
            ],
        ];

        // Ticket Category Settings
        $ticketSettings = [
            [
                'key' => 'auto_assign_enabled',
                'value' => env('HELPDESK_AUTO_ASSIGN', false) ? '1' : '0',
                'type' => 'boolean',
                'category' => 'tickets',
                'description' => 'Enable automatic ticket assignment to available teknisi',
                'is_public' => false,
            ],
            [
                'key' => 'auto_assign_algorithm',
                'value' => env('HELPDESK_AUTO_ASSIGN_ALGORITHM', 'load_balanced'),
                'type' => 'string',
                'category' => 'tickets',
                'description' => 'Algorithm for auto-assignment: round_robin or load_balanced',
                'is_public' => false,
            ],
            [
                'key' => 'default_priority',
                'value' => env('HELPDESK_DEFAULT_PRIORITY', 'medium'),
                'type' => 'string',
                'category' => 'tickets',
                'description' => 'Default priority for new tickets',
                'is_public' => false,
            ],
            [
                'key' => 'auto_close_resolved_after_days',
                'value' => env('HELPDESK_AUTO_CLOSE_DAYS', 7),
                'type' => 'integer',
                'category' => 'tickets',
                'description' => 'Automatically close resolved tickets after X days',
                'is_public' => false,
            ],
            [
                'key' => 'max_concurrent_tickets',
                'value' => env('HELPDESK_MAX_CONCURRENT_TICKETS', 10),
                'type' => 'integer',
                'category' => 'tickets',
                'description' => 'Maximum concurrent tickets per teknisi',
                'is_public' => false,
            ],
            [
                'key' => 'escalation_urgent_unassigned_hours',
                'value' => env('HELPDESK_ESCALATION_URGENT', 2),
                'type' => 'integer',
                'category' => 'tickets',
                'description' => 'Escalate urgent tickets if unassigned after X hours',
                'is_public' => false,
            ],
            [
                'key' => 'escalation_high_unassigned_hours',
                'value' => env('HELPDESK_ESCALATION_HIGH', 4),
                'type' => 'integer',
                'category' => 'tickets',
                'description' => 'Escalate high priority tickets if unassigned after X hours',
                'is_public' => false,
            ],
        ];

        // SLA Category Settings
        $slaSettings = [
            [
                'key' => 'sla_urgent_response_hours',
                'value' => env('HELPDESK_SLA_URGENT_RESPONSE', 2),
                'type' => 'integer',
                'category' => 'sla',
                'description' => 'Maximum response time for urgent priority tickets (in hours)',
                'is_public' => false,
            ],
            [
                'key' => 'sla_urgent_resolution_hours',
                'value' => env('HELPDESK_SLA_URGENT_RESOLUTION', 8),
                'type' => 'integer',
                'category' => 'sla',
                'description' => 'Maximum resolution time for urgent priority tickets (in hours)',
                'is_public' => false,
            ],
            [
                'key' => 'sla_high_response_hours',
                'value' => env('HELPDESK_SLA_HIGH_RESPONSE', 4),
                'type' => 'integer',
                'category' => 'sla',
                'description' => 'Maximum response time for high priority tickets (in hours)',
                'is_public' => false,
            ],
            [
                'key' => 'sla_high_resolution_hours',
                'value' => env('HELPDESK_SLA_HIGH_RESOLUTION', 24),
                'type' => 'integer',
                'category' => 'sla',
                'description' => 'Maximum resolution time for high priority tickets (in hours)',
                'is_public' => false,
            ],
            [
                'key' => 'sla_medium_response_hours',
                'value' => env('HELPDESK_SLA_MEDIUM_RESPONSE', 8),
                'type' => 'integer',
                'category' => 'sla',
                'description' => 'Maximum response time for medium priority tickets (in hours)',
                'is_public' => false,
            ],
            [
                'key' => 'sla_medium_resolution_hours',
                'value' => env('HELPDESK_SLA_MEDIUM_RESOLUTION', 48),
                'type' => 'integer',
                'category' => 'sla',
                'description' => 'Maximum resolution time for medium priority tickets (in hours)',
                'is_public' => false,
            ],
            [
                'key' => 'sla_low_response_hours',
                'value' => env('HELPDESK_SLA_LOW_RESPONSE', 24),
                'type' => 'integer',
                'category' => 'sla',
                'description' => 'Maximum response time for low priority tickets (in hours)',
                'is_public' => false,
            ],
            [
                'key' => 'sla_low_resolution_hours',
                'value' => env('HELPDESK_SLA_LOW_RESOLUTION', 120),
                'type' => 'integer',
                'category' => 'sla',
                'description' => 'Maximum resolution time for low priority tickets (in hours)',
                'is_public' => false,
            ],
        ];

        // Notification Category Settings
        $notificationSettings = [
            [
                'key' => 'notification_ticket_created_enabled',
                'value' => env('HELPDESK_EMAIL_NOTIFICATIONS', true) ? '1' : '0',
                'type' => 'boolean',
                'category' => 'notifications',
                'description' => 'Enable notifications when tickets are created',
                'is_public' => false,
            ],
            [
                'key' => 'notification_ticket_assigned_enabled',
                'value' => env('HELPDESK_EMAIL_NOTIFICATIONS', true) ? '1' : '0',
                'type' => 'boolean',
                'category' => 'notifications',
                'description' => 'Enable notifications when tickets are assigned',
                'is_public' => false,
            ],
            [
                'key' => 'notification_ticket_resolved_enabled',
                'value' => env('HELPDESK_EMAIL_NOTIFICATIONS', true) ? '1' : '0',
                'type' => 'boolean',
                'category' => 'notifications',
                'description' => 'Enable notifications when tickets are resolved',
                'is_public' => false,
            ],
            [
                'key' => 'notification_email_enabled',
                'value' => env('HELPDESK_EMAIL_NOTIFICATIONS', true) ? '1' : '0',
                'type' => 'boolean',
                'category' => 'notifications',
                'description' => 'Enable email notifications',
                'is_public' => false,
            ],
            [
                'key' => 'notification_retention_days',
                'value' => env('HELPDESK_NOTIFICATION_RETENTION_DAYS', 30),
                'type' => 'integer',
                'category' => 'notifications',
                'description' => 'Retain notifications for X days before cleanup',
                'is_public' => false,
            ],
        ];

        // Security Category Settings
        $securitySettings = [
            [
                'key' => 'session_lifetime_minutes',
                'value' => env('HELPDESK_SESSION_TIMEOUT', 120),
                'type' => 'integer',
                'category' => 'security',
                'description' => 'Session lifetime in minutes',
                'is_public' => false,
            ],
            [
                'key' => 'session_timeout_warning_minutes',
                'value' => '5',
                'type' => 'integer',
                'category' => 'security',
                'description' => 'Show session timeout warning X minutes before expiry',
                'is_public' => false,
            ],
            [
                'key' => 'login_max_attempts',
                'value' => env('HELPDESK_LOGIN_MAX_ATTEMPTS', 5),
                'type' => 'integer',
                'category' => 'security',
                'description' => 'Maximum login attempts before lockout',
                'is_public' => false,
            ],
            [
                'key' => 'login_lockout_minutes',
                'value' => env('HELPDESK_LOGIN_LOCKOUT_MINUTES', 15),
                'type' => 'integer',
                'category' => 'security',
                'description' => 'Lockout duration in minutes after max attempts',
                'is_public' => false,
            ],
            [
                'key' => 'password_expiry_days',
                'value' => '90',
                'type' => 'integer',
                'category' => 'security',
                'description' => 'Password expiry period in days (0 = disabled)',
                'is_public' => false,
            ],
            [
                'key' => 'require_strong_passwords',
                'value' => env('HELPDESK_REQUIRE_PASSWORD_COMPLEXITY', true) ? '1' : '0',
                'type' => 'boolean',
                'category' => 'security',
                'description' => 'Require strong passwords with special characters',
                'is_public' => false,
            ],
            [
                'key' => 'max_file_upload_size_mb',
                'value' => env('HELPDESK_MAX_FILE_SIZE_MB', 2),
                'type' => 'integer',
                'category' => 'security',
                'description' => 'Maximum file upload size in MB',
                'is_public' => false,
            ],
            [
                'key' => 'allowed_file_extensions',
                'value' => json_encode(['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt']),
                'type' => 'json',
                'category' => 'security',
                'description' => 'Allowed file extensions for uploads',
                'is_public' => false,
            ],
        ];

        // Email Category Settings
        $emailSettings = [
            [
                'key' => 'email_from_address',
                'value' => env('HELPDESK_EMAIL_FROM_ADDRESS', 'noreply@kemlu.go.id'),
                'type' => 'string',
                'category' => 'email',
                'description' => 'From email address for system notifications',
                'is_public' => false,
            ],
            [
                'key' => 'email_from_name',
                'value' => env('HELPDESK_EMAIL_FROM_NAME', 'HelpDesk Kemlu'),
                'type' => 'string',
                'category' => 'email',
                'description' => 'From name for system emails',
                'is_public' => false,
            ],
            [
                'key' => 'email_smtp_host',
                'value' => 'localhost',
                'type' => 'string',
                'category' => 'email',
                'description' => 'SMTP server hostname',
                'is_public' => false,
            ],
            [
                'key' => 'email_smtp_port',
                'value' => '587',
                'type' => 'integer',
                'category' => 'email',
                'description' => 'SMTP server port',
                'is_public' => false,
            ],
            [
                'key' => 'email_smtp_encryption',
                'value' => 'tls',
                'type' => 'string',
                'category' => 'email',
                'description' => 'SMTP encryption method (tls, ssl, or none)',
                'is_public' => false,
            ],
        ];

        // Backup Category Settings
        $backupSettings = [
            [
                'key' => 'backup_enabled',
                'value' => '1',
                'type' => 'boolean',
                'category' => 'backup',
                'description' => 'Enable automatic database backups',
                'is_public' => false,
            ],
            [
                'key' => 'backup_frequency',
                'value' => 'daily',
                'type' => 'string',
                'category' => 'backup',
                'description' => 'Backup frequency: daily, weekly, or monthly',
                'is_public' => false,
            ],
            [
                'key' => 'backup_retention_days',
                'value' => '30',
                'type' => 'integer',
                'category' => 'backup',
                'description' => 'Retain backup files for X days',
                'is_public' => false,
            ],
            [
                'key' => 'backup_include_files',
                'value' => '1',
                'type' => 'boolean',
                'category' => 'backup',
                'description' => 'Include uploaded files in backup',
                'is_public' => false,
            ],
        ];

        // Combine all settings
        $allSettings = array_merge(
            $generalSettings,
            $ticketSettings,
            $slaSettings,
            $notificationSettings,
            $securitySettings,
            $emailSettings,
            $backupSettings
        );

        // Insert or update settings
        foreach ($allSettings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('System settings seeded successfully.');
    }
}