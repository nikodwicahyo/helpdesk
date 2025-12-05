<?php

namespace Database\Seeders;

use App\Models\AdminAplikasi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminAplikasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminAplikasis = [
            [
                'nip' => '197501012000011001',
                'name' => 'Dr. Ir. Hendro Wicaksono, M.T., Ph.D.',
                'email' => 'hendro.wicaksono@kemlu.go.id',
                'phone' => '+62-21-3843001',
                'department' => 'Pusat Data dan Informasi',
                'position' => 'Kepala Pusat Data dan Informasi',
                'status' => 'active',
                'permissions' => [
                    'manage_all_applications',
                    'system_administration',
                    'user_management',
                    'security_management',
                    'backup_management',
                    'audit_logging',
                ],
                'managed_applications' => ['SIKEP', 'SIAN', 'E-OFFICE', 'SIKEU', 'PDD', 'SIKON', 'SIMONAS', 'E-LEARN', 'HELPDESK', 'APD'],
                'technical_expertise' => 'Enterprise Architecture, System Integration, Cybersecurity',
                'password' => Hash::make('admin123'),
            ],
            [
                'nip' => '197602152000012002',
                'name' => 'Ir. Siti Nurhaliza, M.Kom.',
                'email' => 'siti.nurhaliza.admin@kemlu.go.id',
                'phone' => '+62-21-3843002',
                'department' => 'Pusat Data dan Informasi',
                'position' => 'Administrator Sistem SIKEP',
                'status' => 'active',
                'permissions' => [
                    'manage_sikep',
                    'user_management_sikep',
                    'data_backup_sikep',
                    'report_generation_sikep',
                ],
                'managed_applications' => ['SIKEP'],
                'technical_expertise' => 'Human Resource Information Systems, Database Administration',
                'password' => Hash::make('admin123'),
            ],
            [
                'nip' => '197703202000013003',
                'name' => 'Drs. Agus Setiawan, M.Si.',
                'email' => 'agus.setiawan.admin@kemlu.go.id',
                'phone' => '+62-21-3843003',
                'department' => 'Pusat Data dan Informasi',
                'position' => 'Administrator E-Office',
                'status' => 'active',
                'permissions' => [
                    'manage_eoffice',
                    'workflow_management',
                    'document_template_management',
                    'user_access_control',
                ],
                'managed_applications' => ['E-OFFICE'],
                'technical_expertise' => 'Document Management Systems, Workflow Automation',
                'password' => Hash::make('admin123'),
            ],
            [
                'nip' => '197804252000014004',
                'name' => 'Dewi Sartika, S.Kom., M.T.',
                'email' => 'dewi.sartika.admin@kemlu.go.id',
                'phone' => '+62-21-3843004',
                'department' => 'Pusat Data dan Informasi',
                'position' => 'Administrator SIAN & PDD',
                'status' => 'active',
                'permissions' => [
                    'manage_sian',
                    'manage_pdd',
                    'content_management',
                    'web_administration',
                ],
                'managed_applications' => ['SIAN', 'PDD'],
                'technical_expertise' => 'Web Development, Content Management Systems',
                'password' => Hash::make('admin123'),
            ],
            [
                'nip' => '197905102000015005',
                'name' => 'Rizki Ramadhan, S.T., M.T.',
                'email' => 'rizki.ramadhan.admin@kemlu.go.id',
                'phone' => '+62-21-3843005',
                'department' => 'Pusat Data dan Informasi',
                'position' => 'Administrator SIKEU & SIKON',
                'status' => 'active',
                'permissions' => [
                    'manage_sikeu',
                    'manage_sikon',
                    'financial_systems',
                    'consular_systems',
                ],
                'managed_applications' => ['SIKEU', 'SIKON'],
                'technical_expertise' => 'Financial Systems, Consular Applications',
                'password' => Hash::make('admin123'),
            ],
            [
                'nip' => '198006152000016006',
                'name' => 'Maya Sari, S.Kom., M.Cs.',
                'email' => 'maya.sari.admin@kemlu.go.id',
                'phone' => '+62-21-3843006',
                'department' => 'Pusat Data dan Informasi',
                'position' => 'Administrator SIMONAS & E-LEARN',
                'status' => 'active',
                'permissions' => [
                    'manage_simonas',
                    'manage_elearn',
                    'asset_management',
                    'learning_management',
                ],
                'managed_applications' => ['SIMONAS', 'E-LEARN'],
                'technical_expertise' => 'Asset Management Systems, E-Learning Platforms',
                'password' => Hash::make('admin123'),
            ],
            [
                'nip' => '198107202000017007',
                'name' => 'Ahmad Fauzi, S.T., M.T.',
                'email' => 'ahmad.fauzi.admin@kemlu.go.id',
                'phone' => '+62-21-3843007',
                'department' => 'Pusat Data dan Informasi',
                'position' => 'Administrator Helpdesk System',
                'status' => 'active',
                'permissions' => [
                    'manage_helpdesk',
                    'system_configuration',
                    'ticket_management',
                    'reporting_analytics',
                ],
                'managed_applications' => ['HELPDESK'],
                'technical_expertise' => 'IT Service Management, System Administration',
                'password' => Hash::make('admin123'),
            ],
            [
                'nip' => '198208252000018008',
                'name' => 'Rina Wijaya, S.Kom., M.T.',
                'email' => 'rina.wijaya.admin@kemlu.go.id',
                'phone' => '+62-21-3843008',
                'department' => 'Pusat Data dan Informasi',
                'position' => 'Administrator APD',
                'status' => 'active',
                'permissions' => [
                    'manage_apd',
                    'protocol_management',
                    'event_coordination',
                ],
                'managed_applications' => ['APD'],
                'technical_expertise' => 'Protocol Management Systems, Event Management',
                'password' => Hash::make('admin123'),
            ],
        ];

        foreach ($adminAplikasis as $admin) {
            AdminAplikasi::firstOrCreate(
                ['nip' => $admin['nip']],
                $admin
            );
        }
    }
}