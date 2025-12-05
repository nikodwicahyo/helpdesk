<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class SystemSettingsService
{
    /**
     * Get all settings grouped by category
     */
    public function getAllSettings(): array
    {
        return [
            'general' => $this->getGeneralSettings(),
            'email' => $this->getEmailSettings(),
            'tickets' => $this->getTicketSettings(),
            'security' => $this->getSecuritySettings(),
            'backup' => $this->getBackupSettings(),
            'sla' => $this->getSLASettings(),
            'notifications' => $this->getNotificationSettings(),
        ];
    }

    /**
     * Get general settings
     */
    public function getGeneralSettings(): array
    {
        return [
            'system_name' => SystemSetting::get('system_name', config('app.name')),
            'system_email' => SystemSetting::get('system_email', config('mail.from.address')),
            'default_language' => SystemSetting::get('default_language', 'id'),
            'timezone' => SystemSetting::get('timezone', config('app.timezone')),
            'items_per_page' => SystemSetting::get('items_per_page', 15),
            'session_timeout' => SystemSetting::get('session_timeout', 120),
            'max_file_size' => SystemSetting::get('max_file_size', 2),
            'max_files_per_ticket' => SystemSetting::get('max_files_per_ticket', 5),
            'allowed_file_types' => SystemSetting::get('allowed_file_types', 'pdf,doc,docx,jpg,png,zip'),
            'working_hours_start' => SystemSetting::get('working_hours_start', '08:00'),
            'working_hours_end' => SystemSetting::get('working_hours_end', '17:00'),
            'working_days' => SystemSetting::get('working_days', [1, 2, 3, 4, 5]),
        ];
    }

    /**
     * Get email settings
     */
    public function getEmailSettings(): array
    {
        return [
            'mail_driver' => SystemSetting::get('mail_driver', 'smtp'),
            'mail_host' => SystemSetting::get('mail_host', 'localhost'),
            'mail_port' => SystemSetting::get('mail_port', 587),
            'mail_username' => SystemSetting::get('mail_username', ''),
            'mail_password' => SystemSetting::get('mail_password', ''),
            'mail_encryption' => SystemSetting::get('mail_encryption', 'tls'),
            'mail_from_address' => SystemSetting::get('mail_from_address', 'noreply@kemlu.go.id'),
            'mail_from_name' => SystemSetting::get('mail_from_name', 'HelpDesk Kemlu'),
            'notifications_enabled' => SystemSetting::get('notifications_enabled', true),
        ];
    }

    /**
     * Get ticket settings
     */
    public function getTicketSettings(): array
    {
        return [
            'auto_assign_enabled' => SystemSetting::get('auto_assign_enabled', false),
            'auto_assign_algorithm' => SystemSetting::get('auto_assign_algorithm', 'load_balanced'),
            'default_priority' => SystemSetting::get('default_priority', 'medium'),
            'auto_close_days' => SystemSetting::get('auto_close_days', 7),
            'max_concurrent_tickets' => SystemSetting::get('max_concurrent_tickets', 10),
            'escalation_urgent_hours' => SystemSetting::get('escalation_urgent_hours', 2),
            'escalation_high_hours' => SystemSetting::get('escalation_high_hours', 4),
            'allow_reopening' => SystemSetting::get('allow_reopening', true),
            'require_category' => SystemSetting::get('require_category', true),
            'allow_attachments' => SystemSetting::get('allow_attachments', true),
        ];
    }

    /**
     * Get security settings
     */
    public function getSecuritySettings(): array
    {
        return [
            'password_min_length' => SystemSetting::get('password_min_length', 8),
            'password_require_uppercase' => SystemSetting::get('password_require_uppercase', true),
            'password_require_numbers' => SystemSetting::get('password_require_numbers', true),
            'password_require_symbols' => SystemSetting::get('password_require_symbols', false),
            'password_expiry_days' => SystemSetting::get('password_expiry_days', 90),
            'max_login_attempts' => SystemSetting::get('max_login_attempts', 5),
            'lockout_duration' => SystemSetting::get('lockout_duration', 15),
            'enable_two_factor' => SystemSetting::get('enable_two_factor', false),
            'login_notifications' => SystemSetting::get('login_notifications', false),
            'session_security_strict' => SystemSetting::get('session_security_strict', true),
        ];
    }

    /**
     * Get backup settings
     */
    public function getBackupSettings(): array
    {
        return [
            'auto_backup' => SystemSetting::get('auto_backup', 'daily'),
            'retention_days' => SystemSetting::get('retention_days', 30),
            'location' => SystemSetting::get('backup_location', 'local'),
            'include_files' => SystemSetting::get('backup_include_files', true),
            'backup_time' => SystemSetting::get('backup_time', '02:00'),
            'compress_backups' => SystemSetting::get('compress_backups', true),
        ];
    }

    /**
     * Get SLA settings
     */
    public function getSLASettings(): array
    {
        return [
            'urgent_response' => SystemSetting::get('sla_urgent_response', 2),
            'urgent_resolution' => SystemSetting::get('sla_urgent_resolution', 8),
            'high_response' => SystemSetting::get('sla_high_response', 4),
            'high_resolution' => SystemSetting::get('sla_high_resolution', 24),
            'medium_response' => SystemSetting::get('sla_medium_response', 8),
            'medium_resolution' => SystemSetting::get('sla_medium_resolution', 48),
            'low_response' => SystemSetting::get('sla_low_response', 24),
            'low_resolution' => SystemSetting::get('sla_low_resolution', 120),
        ];
    }

    /**
     * Get notification settings
     */
    public function getNotificationSettings(): array
    {
        return [
            'ticket_created' => SystemSetting::get('notify_ticket_created', true),
            'ticket_assigned' => SystemSetting::get('notify_ticket_assigned', true),
            'ticket_updated' => SystemSetting::get('notify_ticket_updated', true),
            'ticket_resolved' => SystemSetting::get('notify_ticket_resolved', true),
            'ticket_closed' => SystemSetting::get('notify_ticket_closed', true),
            'comment_added' => SystemSetting::get('notify_comment_added', true),
            'retention_days' => SystemSetting::get('notification_retention_days', 30),
        ];
    }

    /**
     * Update multiple settings at once
     */
    public function updateSettings(array $settings, ?string $updatedByNip = null, ?string $updatedByType = null): bool
    {
        try {
            foreach ($settings as $category => $categorySettings) {
                foreach ($categorySettings as $key => $value) {
                    $fullKey = $this->getFullKey($category, $key);
                    $description = $this->getSettingDescription($category, $key);
                    
                    SystemSetting::set($fullKey, $value, $category, $description);
                    
                    // Update metadata
                    if ($updatedByNip && $updatedByType) {
                        $setting = SystemSetting::where('key', $fullKey)->first();
                        if ($setting) {
                            $setting->update([
                                'updated_by_nip' => $updatedByNip,
                                'updated_by_type' => $updatedByType,
                            ]);
                        }
                    }
                }
            }

            // Clear all caches
            SystemSetting::clearAllCaches();
            Cache::forget('system_settings_all');

            // Sync with Laravel config
            $this->syncWithConfig();

            Log::info('System settings updated', [
                'updated_by' => $updatedByNip ?? 'system',
                'categories' => array_keys($settings),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update system settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Get full setting key with category prefix
     */
    private function getFullKey(string $category, string $key): string
    {
        // Some keys already have prefixes, don't double-prefix
        $prefixMap = [
            'sla' => 'sla_',
            'notifications' => 'notify_',
        ];

        $prefix = $prefixMap[$category] ?? '';
        
        // Check if key already starts with the prefix
        if ($prefix && str_starts_with($key, $prefix)) {
            return $key;
        }

        return $prefix ? $prefix . $key : $key;
    }

    /**
     * Get setting description
     */
    private function getSettingDescription(string $category, string $key): string
    {
        $descriptions = [
            'general' => [
                'system_name' => 'Application name displayed throughout the system',
                'system_email' => 'Default system email address for notifications',
                'default_language' => 'Default language for the application',
                'timezone' => 'System timezone for date/time display',
                'items_per_page' => 'Default number of items per page in listings',
                'session_timeout' => 'User session timeout in minutes',
                'max_file_size' => 'Maximum file upload size in MB',
                'max_files_per_ticket' => 'Maximum number of files per ticket',
                'allowed_file_types' => 'Comma-separated list of allowed file extensions',
            ],
            'email' => [
                'mail_driver' => 'Email driver (smtp, sendmail, mailgun, etc.)',
                'mail_host' => 'SMTP server hostname',
                'mail_port' => 'SMTP server port',
                'mail_encryption' => 'Email encryption method (tls, ssl)',
                'mail_from_address' => 'Default from email address',
                'mail_from_name' => 'Default from name',
            ],
            'tickets' => [
                'auto_assign_enabled' => 'Enable automatic ticket assignment',
                'auto_assign_algorithm' => 'Algorithm for automatic assignment',
                'default_priority' => 'Default priority for new tickets',
                'auto_close_days' => 'Auto-close resolved tickets after X days',
            ],
            'security' => [
                'password_min_length' => 'Minimum password length',
                'max_login_attempts' => 'Maximum login attempts before lockout',
                'lockout_duration' => 'Account lockout duration in minutes',
            ],
        ];

        return $descriptions[$category][$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Sync settings with Laravel config at runtime
     */
    public function syncWithConfig(): void
    {
        $generalSettings = $this->getGeneralSettings();
        $emailSettings = $this->getEmailSettings();

        // Sync app config
        Config::set('app.name', $generalSettings['system_name']);
        Config::set('app.timezone', $generalSettings['timezone']);
        Config::set('app.locale', $generalSettings['default_language']);

        // Sync mail config
        Config::set('mail.default', $emailSettings['mail_driver']);
        Config::set('mail.from.address', $emailSettings['mail_from_address']);
        Config::set('mail.from.name', $emailSettings['mail_from_name']);
        Config::set('mail.mailers.smtp.host', $emailSettings['mail_host']);
        Config::set('mail.mailers.smtp.port', $emailSettings['mail_port']);
        Config::set('mail.mailers.smtp.encryption', $emailSettings['mail_encryption']);
        Config::set('mail.mailers.smtp.username', $emailSettings['mail_username']);
        Config::set('mail.mailers.smtp.password', $emailSettings['mail_password']);

        // Sync session config
        Config::set('session.lifetime', $generalSettings['session_timeout']);
    }

    /**
     * Reset settings to default values by running the seeder
     */
    public function resetToDefaults(): bool
    {
        try {
            // Re-run the seeder (updateOrCreate will restore defaults)
            \Artisan::call('db:seed', [
                '--class' => 'SystemSettingsSeeder',
                '--force' => true,
            ]);

            // Clear all caches
            SystemSetting::clearAllCaches();
            Cache::forget('system_settings_all');

            Log::info('System settings reset to defaults');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to reset system settings', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Apply setting value in code - Helper method
     */
    public static function apply(string $key, $default = null)
    {
        return SystemSetting::get($key, $default);
    }

    /**
     * Check if a feature is enabled
     */
    public static function isEnabled(string $feature): bool
    {
        return (bool) SystemSetting::get($feature, false);
    }

    /**
     * Get setting value with type hint
     */
    public static function getString(string $key, string $default = ''): string
    {
        return (string) SystemSetting::get($key, $default);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) SystemSetting::get($key, $default);
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        return (bool) SystemSetting::get($key, $default);
    }

    public static function getArray(string $key, array $default = []): array
    {
        $value = SystemSetting::get($key, $default);
        return is_array($value) ? $value : $default;
    }

    /**
     * Get password validation rules based on system settings.
     */
    public static function getPasswordValidationRules(bool $required = true): array
    {
        $minLength = SystemSetting::get('password_min_length', 8);
        $requireUppercase = SystemSetting::get('password_require_uppercase', true);
        $requireLowercase = SystemSetting::get('password_require_lowercase', true);
        $requireNumbers = SystemSetting::get('password_require_numbers', true);
        $requireSymbols = SystemSetting::get('password_require_symbols', false);

        $rules = [];

        if ($required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        $rules[] = 'string';
        $rules[] = "min:{$minLength}";
        $rules[] = 'max:128';

        // Build regex pattern based on requirements
        $regexParts = [];

        if ($requireUppercase) {
            $regexParts[] = '(?=.*[A-Z])';
        }

        if ($requireLowercase) {
            $regexParts[] = '(?=.*[a-z])';
        }

        if ($requireNumbers) {
            $regexParts[] = '(?=.*\d)';
        }

        if ($requireSymbols) {
            $regexParts[] = '(?=.*[@$!%*?&])';
        }

        if (!empty($regexParts)) {
            $regex = '/^' . implode('', $regexParts) . '.+$/';
            $rules[] = "regex:{$regex}";
        }

        return $rules;
    }

    /**
     * Get password validation rules as a string (for simple validation).
     */
    public static function getPasswordValidationString(bool $required = true): string
    {
        return implode('|', self::getPasswordValidationRules($required));
    }

    /**
     * Get file upload validation rules based on system settings.
     */
    public static function getFileValidationRules(): array
    {
        $maxSizeKb = SystemSetting::get('max_file_size', 2) * 1024; // Convert MB to KB
        $allowedTypes = SystemSetting::get('allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png');

        return [
            'file',
            "max:{$maxSizeKb}",
            "mimes:{$allowedTypes}",
        ];
    }

    /**
     * Get file upload validation rules as a string.
     */
    public static function getFileValidationString(): string
    {
        return implode('|', self::getFileValidationRules());
    }

    /**
     * Get maximum files per ticket.
     */
    public static function getMaxFilesPerTicket(): int
    {
        return (int) SystemSetting::get('max_files_per_ticket', 5);
    }

    /**
     * Get maximum file size in MB.
     */
    public static function getMaxFileSize(): int
    {
        return (int) SystemSetting::get('max_file_size', 2);
    }

    /**
     * Get allowed file types as array.
     */
    public static function getAllowedFileTypes(): array
    {
        $types = SystemSetting::get('allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png');
        return array_map('trim', explode(',', $types));
    }

    /**
     * Get items per page setting.
     */
    public static function getItemsPerPage(): int
    {
        return (int) SystemSetting::get('items_per_page', 15);
    }

    /**
     * Get session timeout in minutes.
     */
    public static function getSessionTimeout(): int
    {
        return (int) SystemSetting::get('session_timeout', 120);
    }

    /**
     * Get SLA settings for a specific priority.
     */
    public static function getSlaForPriority(string $priority): array
    {
        $priority = strtolower($priority);

        return [
            'response_hours' => (int) SystemSetting::get("sla_{$priority}_response", match ($priority) {
                'urgent' => 2,
                'high' => 4,
                'medium' => 8,
                'low' => 24,
                default => 8,
            }),
            'resolution_hours' => (int) SystemSetting::get("sla_{$priority}_resolution", match ($priority) {
                'urgent' => 8,
                'high' => 24,
                'medium' => 48,
                'low' => 120,
                default => 48,
            }),
        ];
    }

    /**
     * Check if auto-assignment is enabled.
     */
    public static function isAutoAssignEnabled(): bool
    {
        return (bool) SystemSetting::get('auto_assign_enabled', false);
    }

    /**
     * Get auto-assignment algorithm.
     */
    public static function getAutoAssignAlgorithm(): string
    {
        return (string) SystemSetting::get('auto_assign_algorithm', 'load_balanced');
    }

    /**
     * Get working hours configuration.
     */
    public static function getWorkingHours(): array
    {
        return [
            'start' => SystemSetting::get('working_hours_start', '08:00'),
            'end' => SystemSetting::get('working_hours_end', '17:00'),
            'days' => SystemSetting::get('working_days', [1, 2, 3, 4, 5]),
        ];
    }

    /**
     * Get security settings for login.
     */
    public static function getLoginSecuritySettings(): array
    {
        return [
            'max_attempts' => (int) SystemSetting::get('max_login_attempts', 5),
            'lockout_duration' => (int) SystemSetting::get('lockout_duration', 15),
            'enable_two_factor' => (bool) SystemSetting::get('enable_two_factor', false),
            'login_notifications' => (bool) SystemSetting::get('login_notifications', false),
        ];
    }
}
