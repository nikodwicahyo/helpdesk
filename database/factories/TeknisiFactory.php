<?php

namespace Database\Factories;

use App\Models\Teknisi;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class TeknisiFactory extends Factory
{
    protected $model = Teknisi::class;

    public function definition(): array
    {
        return [
            'nip' => $this->faker->unique()->numerify('199#############'),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'department' => $this->faker->randomElement(['IT', 'Network', 'Hardware', 'Software']),
            'position' => $this->faker->randomElement(['Staff', 'Senior Staff', 'Lead']),
            'status' => 'active',
            'role' => 'teknisi',
            'skill_level' => $this->faker->randomElement(['junior', 'senior', 'expert']),
            'skills' => ['hardware', 'networking', 'Network'],
            'certifications' => ['CompTIA A+', 'Microsoft Certified'],
            'ticket_count' => 0,
            'experience_years' => $this->faker->numberBetween(1, 15),
            'bio' => $this->faker->paragraph(),
            'password' => Hash::make('teknisi123'),
        ];
    }
}