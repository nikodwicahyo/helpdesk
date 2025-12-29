<?php

namespace Database\Factories;

use App\Models\Backup;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupFactory extends Factory
{
    protected $model = Backup::class;

    public function definition()
    {
        return [
            'filename' => 'backup-' . now()->format('Y-m-d-His') . '.zip',
            'path' => 'backups/backup-' . now()->format('Y-m-d-His') . '.zip',
            'disk' => 'local',
            'size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'status' => 'completed',
            'created_by_nip' => null, // System
            'created_by_type' => null, // System
            'created_at' => now(),
            'completed_at' => now(),
        ];
    }
}
