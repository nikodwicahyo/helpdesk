<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info('Starting SystemSettingsSeeder');

        $settings = [
            // General Settings
            ['key' => 'system_name', 'value' => 'HelpDesk Kemlu', 'type' => 'string', 'category' => 'general', 'description' => 'Application name displayed throughout the system', 'is_public' => true],
            ['key' => 'system_email', 'value' => 'support@kemlu.go.id', 'type' => 'string', 'category' => 'general', 'description' => 'Default system email for notifications', 'is_public' => true],
            ['key' => 'default_language', 'value' => 'id', 'type' => 'string', 'category' => 'general', 'description' => 'Default language (id=Indonesian, en=English)', 'is_public' => true],
            ['key' => 'timezone', 'value' => 'Asia/Jakarta', 'type' => 'string', 'category' => 'general', 'description' => 'System timezone', 'is_public' => true],
            ['key' => 'items_per_page', 'value' => '15', 'type' => 'integer', 'category' => 'general', 'description' => 'Default pagination size', 'is_public' => true],
            ['key' => 'session_timeout', 'value' => '120', 'type' => 'integer', 'category' => 'general', 'description' => 'Session timeout in minutes', 'is_public' => false],
            ['key' => 'max_file_size', 'value' => '2', 'type' => 'integer', 'category' => 'general', 'description' => 'Maximum file upload size in MB', 'is_public' => true],
            ['key' => 'max_files_per_ticket', 'value' => '5', 'type' => 'integer', 'category' => 'general', 'description' => 'Maximum files allowed per ticket', 'is_public' => true],
            ['key' => 'allowed_file_types', 'value' => 'pdf,doc,docx,jpg,jpeg,png,zip', 'type' => 'string', 'category' => 'general', 'description' => 'Allowed file extensions (comma-separated)', 'is_public' => true],
            ['key' => 'working_hours_start', 'value' => '08:00', 'type' => 'string', 'category' => 'general', 'description' => 'Working hours start time for SLA', 'is_public' => false],
            ['key' => 'working_hours_end', 'value' => '17:00', 'type' => 'string', 'category' => 'general', 'description' => 'Working hours end time for SLA', 'is_public' => false],
            ['key' => 'working_days', 'value' => json_encode([1, 2, 3, 4, 5]), 'type' => 'json', 'category' => 'general', 'description' => 'Working days for SLA (1=Mon, 7=Sun)', 'is_public' => false],

            // Email Settings
            ['key' => 'mail_driver', 'value' => 'smtp', 'type' => 'string', 'category' => 'email', 'description' => 'Mail driver (smtp, sendmail, mailgun)', 'is_public' => false],
            ['key' => 'mail_host', 'value' => 'localhost', 'type' => 'string', 'category' => 'email', 'description' => 'SMTP host server', 'is_public' => false],
            ['key' => 'mail_port', 'value' => '587', 'type' => 'integer', 'category' => 'email', 'description' => 'SMTP port', 'is_public' => false],
            ['key' => 'mail_username', 'value' => '', 'type' => 'string', 'category' => 'email', 'description' => 'SMTP username', 'is_public' => false],
            ['key' => 'mail_password', 'value' => '', 'type' => 'string', 'category' => 'email', 'description' => 'SMTP password (encrypted)', 'is_public' => false],
            ['key' => 'mail_encryption', 'value' => 'tls', 'type' => 'string', 'category' => 'email', 'description' => 'SMTP encryption (tls, ssl, none)', 'is_public' => false],
            ['key' => 'mail_from_address', 'value' => 'noreply@kemlu.go.id', 'type' => 'string', 'category' => 'email', 'description' => 'Default from email address', 'is_public' => false],
            ['key' => 'mail_from_name', 'value' => 'HelpDesk Kemlu', 'type' => 'string', 'category' => 'email', 'description' => 'Default from name', 'is_public' => false],
            ['key' => 'notifications_enabled', 'value' => '1', 'type' => 'boolean', 'category' => 'email', 'description' => 'Enable email notifications', 'is_public' => false],

            // Ticket Settings
            ['key' => 'auto_assign_enabled', 'value' => '0', 'type' => 'boolean', 'category' => 'tickets', 'description' => 'Enable automatic ticket assignment', 'is_public' => false],
            ['key' => 'auto_assign_algorithm', 'value' => 'load_balanced', 'type' => 'string', 'category' => 'tickets', 'description' => 'Auto-assignment algorithm (load_balanced, round_robin)', 'is_public' => false],
            ['key' => 'default_priority', 'value' => 'medium', 'type' => 'string', 'category' => 'tickets', 'description' => 'Default ticket priority', 'is_public' => false],
            ['key' => 'auto_close_days', 'value' => '7', 'type' => 'integer', 'category' => 'tickets', 'description' => 'Auto-close resolved tickets after X days', 'is_public' => false],
            ['key' => 'max_concurrent_tickets', 'value' => '10', 'type' => 'integer', 'category' => 'tickets', 'description' => 'Max concurrent tickets per teknisi', 'is_public' => false],
            ['key' => 'escalation_urgent_hours', 'value' => '2', 'type' => 'integer', 'category' => 'tickets', 'description' => 'Escalate urgent tickets if unassigned after X hours', 'is_public' => false],
            ['key' => 'escalation_high_hours', 'value' => '4', 'type' => 'integer', 'category' => 'tickets', 'description' => 'Escalate high priority tickets if unassigned after X hours', 'is_public' => false],
            ['key' => 'allow_reopening', 'value' => '1', 'type' => 'boolean', 'category' => 'tickets', 'description' => 'Allow users to reopen closed tickets', 'is_public' => false],
            ['key' => 'require_category', 'value' => '1', 'type' => 'boolean', 'category' => 'tickets', 'description' => 'Require category selection for tickets', 'is_public' => false],
            ['key' => 'allow_attachments', 'value' => '1', 'type' => 'boolean', 'category' => 'tickets', 'description' => 'Allow file attachments on tickets', 'is_public' => false],

            // Security Settings
            ['key' => 'password_min_length', 'value' => '8', 'type' => 'integer', 'category' => 'security', 'description' => 'Minimum password length', 'is_public' => false],
            ['key' => 'password_require_uppercase', 'value' => '1', 'type' => 'boolean', 'category' => 'security', 'description' => 'Require uppercase letters in password', 'is_public' => false],
            ['key' => 'password_require_lowercase', 'value' => '1', 'type' => 'boolean', 'category' => 'security', 'description' => 'Require lowercase letters in password', 'is_public' => false],
            ['key' => 'password_require_numbers', 'value' => '1', 'type' => 'boolean', 'category' => 'security', 'description' => 'Require numbers in password', 'is_public' => false],
            ['key' => 'password_require_symbols', 'value' => '0', 'type' => 'boolean', 'category' => 'security', 'description' => 'Require special symbols in password', 'is_public' => false],
            ['key' => 'password_expiry_days', 'value' => '90', 'type' => 'integer', 'category' => 'security', 'description' => 'Password expiry in days (0=disabled)', 'is_public' => false],
            ['key' => 'max_login_attempts', 'value' => '5', 'type' => 'integer', 'category' => 'security', 'description' => 'Max login attempts before lockout', 'is_public' => false],
            ['key' => 'lockout_duration', 'value' => '15', 'type' => 'integer', 'category' => 'security', 'description' => 'Lockout duration in minutes', 'is_public' => false],
            ['key' => 'enable_two_factor', 'value' => '0', 'type' => 'boolean', 'category' => 'security', 'description' => 'Enable two-factor authentication', 'is_public' => false],
            ['key' => 'login_notifications', 'value' => '0', 'type' => 'boolean', 'category' => 'security', 'description' => 'Send login notification emails', 'is_public' => false],
            ['key' => 'session_security_strict', 'value' => '1', 'type' => 'boolean', 'category' => 'security', 'description' => 'Strict session security checks', 'is_public' => false],

            // Backup Settings
            ['key' => 'auto_backup', 'value' => 'daily', 'type' => 'string', 'category' => 'backup', 'description' => 'Auto backup frequency (disabled, daily, weekly, monthly)', 'is_public' => false],
            ['key' => 'retention_days', 'value' => '30', 'type' => 'integer', 'category' => 'backup', 'description' => 'Backup retention period in days', 'is_public' => false],
            ['key' => 'backup_location', 'value' => 'local', 'type' => 'string', 'category' => 'backup', 'description' => 'Backup storage location (local, s3, google_drive)', 'is_public' => false],
            ['key' => 'backup_include_files', 'value' => '1', 'type' => 'boolean', 'category' => 'backup', 'description' => 'Include uploaded files in backups', 'is_public' => false],
            ['key' => 'backup_time', 'value' => '02:00', 'type' => 'string', 'category' => 'backup', 'description' => 'Backup execution time (HH:MM)', 'is_public' => false],
            ['key' => 'compress_backups', 'value' => '1', 'type' => 'boolean', 'category' => 'backup', 'description' => 'Compress backups with ZIP', 'is_public' => false],

            // SLA Settings
            ['key' => 'sla_urgent_response', 'value' => '2', 'type' => 'integer', 'category' => 'sla', 'description' => 'Urgent priority response time (hours)', 'is_public' => false],
            ['key' => 'sla_urgent_resolution', 'value' => '8', 'type' => 'integer', 'category' => 'sla', 'description' => 'Urgent priority resolution time (hours)', 'is_public' => false],
            ['key' => 'sla_high_response', 'value' => '4', 'type' => 'integer', 'category' => 'sla', 'description' => 'High priority response time (hours)', 'is_public' => false],
            ['key' => 'sla_high_resolution', 'value' => '24', 'type' => 'integer', 'category' => 'sla', 'description' => 'High priority resolution time (hours)', 'is_public' => false],
            ['key' => 'sla_medium_response', 'value' => '8', 'type' => 'integer', 'category' => 'sla', 'description' => 'Medium priority response time (hours)', 'is_public' => false],
            ['key' => 'sla_medium_resolution', 'value' => '48', 'type' => 'integer', 'category' => 'sla', 'description' => 'Medium priority resolution time (hours)', 'is_public' => false],
            ['key' => 'sla_low_response', 'value' => '24', 'type' => 'integer', 'category' => 'sla', 'description' => 'Low priority response time (hours)', 'is_public' => false],
            ['key' => 'sla_low_resolution', 'value' => '120', 'type' => 'integer', 'category' => 'sla', 'description' => 'Low priority resolution time (hours)', 'is_public' => false],

            // Notification Settings
            ['key' => 'notify_ticket_created', 'value' => '1', 'type' => 'boolean', 'category' => 'notifications', 'description' => 'Notify when ticket is created', 'is_public' => false],
            ['key' => 'notify_ticket_assigned', 'value' => '1', 'type' => 'boolean', 'category' => 'notifications', 'description' => 'Notify when ticket is assigned', 'is_public' => false],
            ['key' => 'notify_ticket_updated', 'value' => '1', 'type' => 'boolean', 'category' => 'notifications', 'description' => 'Notify when ticket is updated', 'is_public' => false],
            ['key' => 'notify_ticket_resolved', 'value' => '1', 'type' => 'boolean', 'category' => 'notifications', 'description' => 'Notify when ticket is resolved', 'is_public' => false],
            ['key' => 'notify_ticket_closed', 'value' => '1', 'type' => 'boolean', 'category' => 'notifications', 'description' => 'Notify when ticket is closed', 'is_public' => false],
            ['key' => 'notify_comment_added', 'value' => '1', 'type' => 'boolean', 'category' => 'notifications', 'description' => 'Notify when comment is added', 'is_public' => false],
            ['key' => 'notification_retention_days', 'value' => '30', 'type' => 'integer', 'category' => 'notifications', 'description' => 'Keep notifications for X days', 'is_public' => false],
        ];

        $created = 0;
        $updated = 0;

        foreach ($settings as $setting) {
            $result = SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );

            if ($result->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        Log::info("SystemSettingsSeeder completed", [
            'created' => $created,
            'updated' => $updated,
            'total' => count($settings),
        ]);

        $this->command->info("System Settings Seeder: Created {$created}, Updated {$updated} settings");
    }
}
