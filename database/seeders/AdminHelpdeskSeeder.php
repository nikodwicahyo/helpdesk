<?php

namespace Database\Seeders;

use App\Models\AdminHelpdesk;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminHelpdeskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminHelpdesks = [
            [
                'nip' => '198001012005011001',
                'name' => 'Dr. Ahmad Surya Wijaya, M.T.',
                'email' => 'ahmad.wijaya@kemlu.go.id',
                'phone' => '+62-21-3842001',
                'department' => 'Pusat Data dan Informasi',
                'position' => 'Kepala Bagian Helpdesk',
                'status' => 'active',
                'permissions' => [
                    'manage_tickets',
                    'assign_tickets',
                    'view_all_tickets',
                    'manage_users',
                    'generate_reports',
                    'escalate_tickets',
                    'manage_categories',
                ],
                'specialization' => 'IT Service Management, Network Infrastructure',
                'password' => Hash::make('admin123'),
            ],
            [
                'nip' => '198102152005012002',
                'name' => 'Ir. Siti Rahayu, M.Kom.',
                'email' => 'siti.rahayu@kemlu.go.id',
                'phone' => '+62-21-3842002',
                'department' => 'Pusat Data dan Informasi',
                'position' => 'Koordinator Helpdesk Teknis',
                'status' => 'active',
                'permissions' => [
                    'manage_tickets',
                    'assign_tickets',
                    'view_assigned_tickets',
                    'technical_support',
                    'manage_knowledge_base',
                ],
                'specialization' => 'Software Development, Database Management',
                'password' => Hash::make('admin123'),
            ],
            [
                'nip' => '198203202005013003',
                'name' => 'Drs. Budi Hermawan, M.Si.',
                'email' => 'budi.hermawan@kemlu.go.id',
                'phone' => '+62-21-3842003',
                'department' => 'Pusat Data dan Informasi',
                'position' => 'Supervisor Helpdesk',
                'status' => 'active',
                'permissions' => [
                    'manage_tickets',
                    'assign_tickets',
                    'view_team_tickets',
                    'quality_assurance',
                    'training_coordination',
                ],
                'specialization' => 'Customer Service, Quality Management',
                'password' => Hash::make('admin123'),
            ],
            [
                'nip' => '198304252005014004',
                'name' => 'Maya Fitriani, S.Kom., M.T.',
                'email' => 'maya.fitriani@kemlu.go.id',
                'phone' => '+62-21-3842004',
                'department' => 'Pusat Data dan Informasi',
                'position' => 'Senior Helpdesk Analyst',
                'status' => 'active',
                'permissions' => [
                    'manage_tickets',
                    'technical_support',
                    'knowledge_base_management',
                    'problem_analysis',
                ],
                'specialization' => 'System Analysis, Business Process',
                'password' => Hash::make('admin123'),
            ],
            [
                'nip' => '198405102005015005',
                'name' => 'Agus Santoso, S.T., M.T.',
                'email' => 'agus.santoso@kemlu.go.id',
                'phone' => '+62-21-3842005',
                'department' => 'Pusat Data dan Informasi',
                'position' => 'Helpdesk Specialist',
                'status' => 'active',
                'permissions' => [
                    'manage_tickets',
                    'technical_support',
                    'user_training',
                    'documentation',
                ],
                'specialization' => 'Hardware Support, User Training',
                'password' => Hash::make('admin123'),
            ],
        ];

        foreach ($adminHelpdesks as $admin) {
            AdminHelpdesk::firstOrCreate(
                ['nip' => $admin['nip']], // Use unique 'nip' field as identifier
                $admin
            );
        }
    }
}