<?php

namespace Database\Factories;

use App\Models\AdminHelpdesk;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AdminHelpdeskFactory extends Factory
{
    protected $model = AdminHelpdesk::class;

    public function definition(): array
    {
        return [
            'nip' => $this->faker->unique()->numerify('198#############'),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'department' => $this->faker->randomElement(['IT', 'Helpdesk', 'Support', 'Operations']),
            'position' => $this->faker->randomElement(['Manager', 'Coordinator', 'Specialist', 'Analyst']),
            'status' => 'active',
            'role' => 'admin_helpdesk',
            'permissions' => ['ticket_management', 'user_management', 'report_viewer'],
            'specialization' => $this->faker->randomElement(['IT Support', 'Network Administration', 'System Analysis']),
            'password' => Hash::make('admin123'),
        ];
    }
}