<?php

namespace Tests\Helpers;

use App\Models\User;
use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use App\Models\Ticket;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;

/**
 * Helper class for authentication-related test utilities.
 */
class AuthHelper
{
    /**
     * Create a user with specific attributes.
     */
    public static function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Create an admin helpdesk with specific attributes.
     */
    public static function createAdminHelpdesk(array $attributes = []): AdminHelpdesk
    {
        return AdminHelpdesk::factory()->create($attributes);
    }

    /**
     * Create an admin aplikasi with specific attributes.
     */
    public static function createAdminAplikasi(array $attributes = []): AdminAplikasi
    {
        return AdminAplikasi::factory()->create($attributes);
    }

    /**
     * Create a teknisi with specific attributes.
     */
    public static function createTeknisi(array $attributes = []): Teknisi
    {
        return Teknisi::factory()->create($attributes);
    }

    /**
     * Create an inactive user.
     */
    public static function createInactiveUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'is_active' => false,
        ], $attributes));
    }

    /**
     * Create a super admin helpdesk.
     */
    public static function createSuperAdminHelpdesk(array $attributes = []): AdminHelpdesk
    {
        return AdminHelpdesk::factory()->create(array_merge([
            'level_admin' => 'super',
        ], $attributes));
    }

    /**
     * Create a senior teknisi.
     */
    public static function createSeniorTeknisi(array $attributes = []): Teknisi
    {
        return Teknisi::factory()->create(array_merge([
            'level_teknisi' => 'senior',
        ], $attributes));
    }
}
