<?php

namespace Database\Factories;

use App\Models\KategoriMasalah;
use Illuminate\Database\Eloquent\Factories\Factory;

class KategoriMasalahFactory extends Factory
{
    protected $model = KategoriMasalah::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Hardware Issue',
                'Software Bug',
                'Network Problem',
                'Access Request',
                'Password Reset',
                'Email Issue',
                'Printer Problem',
                'Application Error',
                'Performance Issue',
                'Security Concern',
                'Data Recovery',
                'System Configuration',
                'User Training',
                'License Issue',
                'Backup/Restore',
                'Database Problem',
                'Integration Issue',
                'Mobile Device',
                'Remote Access',
                'File Sharing'
            ]),
            'description' => $this->faker->paragraph(),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'estimated_resolution_time' => $this->faker->numberBetween(30, 480), // 30 minutes to 8 hours
            'sla_hours' => $this->faker->randomElement([4, 8, 24, 48, 72]),
            'requires_attachment' => $this->faker->boolean(30),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'icon' => $this->faker->randomElement(['fas fa-desktop', 'fas fa-bug', 'fas fa-network-wired', 'fas fa-key']),
            'color' => $this->faker->randomElement(['#007bff', '#28a745', '#fd7e14', '#dc3545']),
            'keywords' => $this->faker->words(5),
            'aplikasi_id' => \App\Models\Aplikasi::factory(),
        ];
    }
}