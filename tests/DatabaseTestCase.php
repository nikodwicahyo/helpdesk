<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Base test case for tests that require database access.
 * Uses RefreshDatabase to ensure a clean database state for each test.
 */
abstract class DatabaseTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed the database with test data.
     */
    protected function seedDatabase(): void
    {
        $this->seed(\Database\Seeders\TestDatabaseSeeder::class);
    }

    /**
     * Create a complete test environment with all roles.
     */
    protected function createCompleteTestEnvironment(): array
    {
        return [
            'user' => \App\Models\User::factory()->create(),
            'admin_helpdesk' => \App\Models\AdminHelpdesk::factory()->create(),
            'admin_aplikasi' => \App\Models\AdminAplikasi::factory()->create(),
            'teknisi' => \App\Models\Teknisi::factory()->create(),
            'aplikasi' => \App\Models\Aplikasi::factory()->create(),
            'kategori' => \App\Models\KategoriMasalah::factory()->create(),
        ];
    }
}
