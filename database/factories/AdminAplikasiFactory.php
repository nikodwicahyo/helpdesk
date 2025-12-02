<?php

namespace Database\Factories;

use App\Models\AdminAplikasi;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AdminAplikasiFactory extends Factory
{
    protected $model = AdminAplikasi::class;

    public function definition(): array
    {
        return [
            'nip' => $this->faker->unique()->numerify('197#############'),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'department' => $this->faker->randomElement(['IT', 'Applications', 'Development', 'Operations']),
            'position' => $this->faker->randomElement(['Manager', 'Coordinator', 'Specialist', 'Analyst']),
            'status' => 'active',
            'permissions' => ['application_management', 'user_management', 'report_viewer'],
            'managed_applications' => [$this->faker->numberBetween(1, 10)],
            'technical_expertise' => $this->faker->randomElement(['Software Development', 'System Administration', 'Database Management']),
            'password' => Hash::make('admin123'),
        ];
    }
}