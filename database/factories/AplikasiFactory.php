<?php

namespace Database\Factories;

use App\Models\Aplikasi;
use Illuminate\Database\Eloquent\Factories\Factory;

class AplikasiFactory extends Factory
{
    protected $model = Aplikasi::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Microsoft Office 365',
                'SAP ERP System',
                'HR Management System',
                'Finance Application',
                'Email Server',
                'File Server',
                'Database Server',
                'Network Infrastructure',
                'Security System',
                'Helpdesk Portal'
            ]),
            'code' => $this->faker->unique()->lexify('???-###'),
            'description' => $this->faker->paragraph(),
            'version' => $this->faker->randomElement(['1.0', '2.0', '3.1', '4.2']),
            'vendor' => $this->faker->randomElement(['Microsoft', 'SAP', 'Oracle', 'Internal', 'Third-party']),
            'status' => $this->faker->randomElement(['active', 'inactive', 'maintenance']),
            'category' => $this->faker->randomElement(['HR', 'Finance', 'IT', 'Operations', 'Security']),
            'criticality' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'contact_person' => $this->faker->name(),
            'contact_email' => $this->faker->companyEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'technical_documentation' => $this->faker->url(),
            'server_location' => $this->faker->randomElement(['Server A', 'Server B', 'Cloud Instance', 'Virtual Machine']),
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }
}