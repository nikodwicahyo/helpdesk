<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestSystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds for testing.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'auto_backup',
                'value' => 'false',
                'type' => 'boolean',
                'category' => 'system',
                'description' => 'Enable automatic backup',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'app_name',
                'value' => 'HelpDesk Kemlu Test',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Application name',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'max_file_size',
                'value' => '10240',
                'type' => 'integer',
                'category' => 'upload',
                'description' => 'Maximum file upload size in KB',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('system_settings')->insert($settings);
    }
}
