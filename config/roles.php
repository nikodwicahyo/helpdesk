<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Role to Dashboard Route Mapping
    |--------------------------------------------------------------------------
    |
    | This configuration defines the mapping between user roles and their
    | corresponding dashboard routes. This is the single source of truth
    | for role-based routing throughout the application.
    |
    */

    'dashboard_routes' => [
        'admin_helpdesk' => 'admin.dashboard',
        'admin_aplikasi' => 'admin-aplikasi.dashboard',
        'teknisi' => 'teknisi.dashboard',
        'user' => 'user.dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Routes
    |--------------------------------------------------------------------------
    |
    | Default routes used when role mapping fails or user is not found.
    |
    */

    'default_dashboard' => 'user.dashboard',
    'login_route' => 'login',

    /*
    |--------------------------------------------------------------------------
    | Role Definitions
    |--------------------------------------------------------------------------
    |
    | Comprehensive role definitions with their display names and descriptions.
    | Used throughout the application for consistent role handling.
    |
    */

    'roles' => [
        'admin_helpdesk' => [
            'name' => 'Admin Helpdesk',
            'description' => 'Full system administration and helpdesk management',
            'level' => 4,
        ],
        'admin_aplikasi' => [
            'name' => 'Admin Aplikasi',
            'description' => 'Application catalog and category management',
            'level' => 3,
        ],
        'teknisi' => [
            'name' => 'Teknisi',
            'description' => 'Technical support and ticket handling',
            'level' => 2,
        ],
        'user' => [
            'name' => 'User',
            'description' => 'Regular employees who can create and manage tickets',
            'level' => 1,
        ],
    ],
];