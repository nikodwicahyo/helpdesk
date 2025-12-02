<?php

return [
    // Auto-assignment settings
    'auto_assign_tickets' => env('HELPDESK_AUTO_ASSIGN', false),
    'auto_assign_algorithm' => env('HELPDESK_AUTO_ASSIGN_ALGORITHM', 'load_balanced'),
    'max_concurrent_tickets' => env('HELPDESK_MAX_CONCURRENT_TICKETS', 10),

    // SLA settings (in hours)
    'sla' => [
        'urgent' => [
            'response' => env('HELPDESK_SLA_URGENT_RESPONSE', 2),
            'resolution' => env('HELPDESK_SLA_URGENT_RESOLUTION', 8),
        ],
        'high' => [
            'response' => env('HELPDESK_SLA_HIGH_RESPONSE', 4),
            'resolution' => env('HELPDESK_SLA_HIGH_RESOLUTION', 24),
        ],
        'medium' => [
            'response' => env('HELPDESK_SLA_MEDIUM_RESPONSE', 8),
            'resolution' => env('HELPDESK_SLA_MEDIUM_RESOLUTION', 48),
        ],
        'low' => [
            'response' => env('HELPDESK_SLA_LOW_RESPONSE', 24),
            'resolution' => env('HELPDESK_SLA_LOW_RESOLUTION', 120),
        ],
    ],

    // Escalation settings
    'escalation' => [
        'urgent_unassigned_hours' => env('HELPDESK_ESCALATION_URGENT', 2),
        'high_unassigned_hours' => env('HELPDESK_ESCALATION_HIGH', 4),
    ],

    // Ticket settings
    'default_priority' => env('HELPDESK_DEFAULT_PRIORITY', 'medium'),
    'auto_close_resolved_days' => env('HELPDESK_AUTO_CLOSE_DAYS', 7),
    'max_file_size_mb' => env('HELPDESK_MAX_FILE_SIZE_MB', 2),
    'max_files_per_ticket' => env('HELPDESK_MAX_FILES_PER_TICKET', 5),

    // Working hours for SLA calculations
    'working_hours' => [
        'start' => env('HELPDESK_WORKING_HOURS_START', '08:00'),
        'end' => env('HELPDESK_WORKING_HOURS_END', '17:00'),
        'days' => [1, 2, 3, 4, 5], // Monday-Friday
    ],

    // Security settings
    'security' => [
        'session_timeout_minutes' => env('HELPDESK_SESSION_TIMEOUT', 120),
        'login_max_attempts' => env('HELPDESK_LOGIN_MAX_ATTEMPTS', 5),
        'login_lockout_minutes' => env('HELPDESK_LOGIN_LOCKOUT_MINUTES', 15),
        'password_min_length' => env('HELPDESK_PASSWORD_MIN_LENGTH', 8),
        'require_password_complexity' => env('HELPDESK_REQUIRE_PASSWORD_COMPLEXITY', true),
    ],

    // Notification settings
    'notifications' => [
        'email_enabled' => env('HELPDESK_EMAIL_NOTIFICATIONS', true),
        'browser_enabled' => env('HELPDESK_BROWSER_NOTIFICATIONS', true),
        'retention_days' => env('HELPDESK_NOTIFICATION_RETENTION_DAYS', 30),
    ],

    // Backup settings
    'backup' => [
        'enabled' => env('HELPDESK_BACKUP_ENABLED', true),
        'frequency' => env('HELPDESK_BACKUP_FREQUENCY', 'daily'),
        'retention_days' => env('HELPDESK_BACKUP_RETENTION_DAYS', 30),
        'include_files' => env('HELPDESK_BACKUP_INCLUDE_FILES', true),
    ],

    // Performance settings
    'performance' => [
        'cache_ttl_minutes' => env('HELPDESK_CACHE_TTL', 5),
        'queue_enabled' => env('HELPDESK_QUEUE_ENABLED', true),
        'rate_limiting' => env('HELPDESK_RATE_LIMITING', true),
        'max_search_results' => env('HELPDESK_MAX_SEARCH_RESULTS', 100),
    ],

    // Email configuration (SMTP)
    'email' => [
        'from_name' => env('HELPDESK_EMAIL_FROM_NAME', 'HelpDesk Kemlu'),
        'from_address' => env('HELPDESK_EMAIL_FROM_ADDRESS', 'noreply@kemlu.go.id'),
        'smtp_host' => env('HELPDESK_SMTP_HOST', 'localhost'),
        'smtp_port' => env('HELPDESK_SMTP_PORT', 587),
        'smtp_username' => env('HELPDESK_SMTP_USERNAME'),
        'smtp_password' => env('HELPDESK_SMTP_PASSWORD'),
        'smtp_encryption' => env('HELPDESK_SMTP_ENCRYPTION', 'tls'),
    ],

    // File upload settings
    'files' => [
        'allowed_extensions' => [
            'jpg', 'jpeg', 'png', 'gif', 'pdf',
            'doc', 'docx', 'xls', 'xlsx', 'txt',
            'zip', 'rar', '7z'
        ],
        'allowed_mime_types' => [
            'image/jpeg', 'image/png', 'image/gif', 'application/pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain', 'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'
        ],
    ],

    // UI/UX settings
    'ui' => [
        'theme' => env('HELPDESK_THEME', 'light'),
        'language' => env('HELPDESK_LANGUAGE', 'id'),
        'timezone' => env('HELPDESK_TIMEZONE', 'Asia/Jakarta'),
        'date_format' => env('HELPDESK_DATE_FORMAT', 'd M Y'),
        'time_format' => env('HELPDESK_TIME_FORMAT', 'H:i'),
    ],

    // API settings
    'api' => [
        'rate_limit_per_minute' => env('HELPDESK_API_RATE_LIMIT', 60),
        'timeout_seconds' => env('HELPDESK_API_TIMEOUT', 30),
        'paginate_per_page' => env('HELPDESK_API_PAGINATE_PER_PAGE', 15),
    ],

    // Integration settings
    'integrations' => [
        'ldap_enabled' => env('HELPDESK_LDAP_ENABLED', false),
        'ldap_host' => env('HELPDESK_LDAP_HOST', ''),
        'ldap_port' => env('HELPDESK_LDAP_PORT', 389),
        'ldap_base_dn' => env('HELPDESK_LDAP_BASE_DN', ''),
        'ldap_bind_dn' => env('HELPDESK_LDAP_BIND_DN', ''),
        'ldap_bind_password' => env('HELPDESK_LDAP_BIND_PASSWORD', ''),
    ],

    // Monitoring and analytics
    'monitoring' => [
        'analytics_enabled' => env('HELPDESK_ANALYTICS_ENABLED', true),
        'error_tracking_enabled' => env('HELPDESK_ERROR_TRACKING_ENABLED', false),
        'performance_monitoring' => env('HELPDESK_PERFORMANCE_MONITORING', false),
    ],

    // Maintenance mode
    'maintenance' => [
        'enabled' => env('HELPDESK_MAINTENANCE_MODE', false),
        'message' => env('HELPDESK_MAINTENANCE_MESSAGE', 'System is under maintenance. Please try again later.'),
        'allowed_ips' => env('HELPDESK_MAINTENANCE_ALLOWED_IPS', '127.0.0.1'),
    ],
];