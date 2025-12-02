<?php

namespace Database\Seeders;

use App\Models\Aplikasi;
use App\Models\AdminAplikasi;
use Illuminate\Database\Seeder;

class AplikasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Application-to-Admin mapping
        $appAdminMapping = [
            'SIKEP' => ['primary' => '197602152000012002', 'backup' => '197501012000011001'],
            'SIAN' => ['primary' => '197804252000014004', 'backup' => '197501012000011001'],
            'E-OFFICE' => ['primary' => '197703202000013003', 'backup' => '197501012000011001'],
            'SIKEU' => ['primary' => '197905102000015005', 'backup' => '197501012000011001'],
            'PDD' => ['primary' => '197804252000014004', 'backup' => '197703202000013003'],
            'SIKON' => ['primary' => '197905102000015005', 'backup' => '197703202000013003'],
            'SIMONAS' => ['primary' => '198006152000016006', 'backup' => '197501012000011001'],
            'E-LEARN' => ['primary' => '198006152000016006', 'backup' => '197804252000014004'],
            'HELPDESK' => ['primary' => '198107202000017007', 'backup' => '197501012000011001'],
            'APD' => ['primary' => '198208252000018008', 'backup' => '197703202000013003'],
        ];

        $applications = [
            [
                'name' => 'Sistem Kepegawaian Terpadu',
                'code' => 'SIKEP',
                'description' => 'Sistem terintegrasi untuk pengelolaan data kepegawaian Kementerian Luar Negeri',
                'version' => '2.1.0',
                'status' => 'active',
                'criticality' => 'high',
                'category' => 'Human Resource',
                'vendor' => 'PT. Sistech Kharisma',
                'contact_person' => 'Budi Santoso',
                'contact_email' => 'budi.santoso@sistech.co.id',
                'contact_phone' => '+62-21-3841234',
                'technical_documentation' => 'https://docs.sistech.co.id/sikep',
                'supported_os' => ['Windows 10', 'Windows 11', 'Linux'],
                'supported_browsers' => ['Chrome', 'Firefox', 'Edge'],
                'server_location' => 'Data Center Kemlu Jakarta',
                'backup_schedule' => 'Daily 02:00 WIB',
                'notes' => 'Aplikasi kritikal untuk manajemen SDM',
                'icon' => 'fas fa-users',
                'sort_order' => 1,
            ],
            [
                'name' => 'Sistem Informasi Arsip Nasional',
                'code' => 'SIAN',
                'description' => 'Pengelolaan arsip dan dokumentasi diplomatik',
                'version' => '1.5.2',
                'status' => 'active',
                'criticality' => 'high',
                'category' => 'Document Management',
                'vendor' => 'PT. Arsip Digital Indonesia',
                'contact_person' => 'Siti Nurhaliza',
                'contact_email' => 'siti.nurhaliza@ardi.co.id',
                'contact_phone' => '+62-21-3845678',
                'technical_documentation' => 'https://docs.ardi.co.id/sian',
                'supported_os' => ['Windows 10', 'Windows 11'],
                'supported_browsers' => ['Chrome', 'Firefox'],
                'server_location' => 'Data Center Kemlu Jakarta',
                'backup_schedule' => 'Daily 01:00 WIB',
                'notes' => 'Menangani dokumen rahasia negara',
                'icon' => 'fas fa-archive',
                'sort_order' => 2,
            ],
            [
                'name' => 'E-Office Kemlu',
                'code' => 'E-OFFICE',
                'description' => 'Sistem surat menyurat elektronik dan workflow approval',
                'version' => '3.0.1',
                'status' => 'active',
                'criticality' => 'critical',
                'category' => 'Office Automation',
                'vendor' => 'Kementerian Luar Negeri',
                'contact_person' => 'Agus Setiawan',
                'contact_email' => 'agus.setiawan@kemlu.go.id',
                'contact_phone' => '+62-21-3849000',
                'technical_documentation' => 'https://intranet.kemlu.go.id/eoffice-docs',
                'supported_os' => ['Windows 10', 'Windows 11', 'macOS'],
                'supported_browsers' => ['Chrome', 'Firefox', 'Safari', 'Edge'],
                'server_location' => 'Data Center Kemlu Jakarta (Primary) & Disaster Recovery Site',
                'backup_schedule' => 'Every 4 hours',
                'notes' => 'Aplikasi dengan availability 99.9%',
                'icon' => 'fas fa-envelope',
                'sort_order' => 3,
            ],
            [
                'name' => 'Sistem Informasi Keuangan',
                'code' => 'SIKEU',
                'description' => 'Pengelolaan anggaran dan keuangan kementerian',
                'version' => '2.3.0',
                'status' => 'active',
                'criticality' => 'high',
                'category' => 'Finance',
                'vendor' => 'PT. Fintek Solusi',
                'contact_person' => 'Dewi Sartika',
                'contact_email' => 'dewi.sartika@fintek.co.id',
                'contact_phone' => '+62-21-3842468',
                'technical_documentation' => 'https://docs.fintek.co.id/sikeu',
                'supported_os' => ['Windows 10', 'Windows 11'],
                'supported_browsers' => ['Chrome', 'Edge'],
                'server_location' => 'Data Center Kemlu Jakarta',
                'backup_schedule' => 'Daily 03:00 WIB',
                'notes' => 'Terintegrasi dengan sistem SPAN',
                'icon' => 'fas fa-money-bill-wave',
                'sort_order' => 4,
            ],
            [
                'name' => 'Portal Diplomasi Digital',
                'code' => 'PDD',
                'description' => 'Portal informasi dan layanan publik Kementerian Luar Negeri',
                'version' => '1.8.0',
                'status' => 'active',
                'criticality' => 'medium',
                'category' => 'Public Service',
                'vendor' => 'Kementerian Luar Negeri',
                'contact_person' => 'Rizki Ramadhan',
                'contact_email' => 'rizki.ramadhan@kemlu.go.id',
                'contact_phone' => '+62-21-3847555',
                'technical_documentation' => 'https://portal.kemlu.go.id/docs',
                'supported_os' => ['Windows', 'macOS', 'Linux', 'iOS', 'Android'],
                'supported_browsers' => ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'],
                'server_location' => 'Cloud Server (AWS)',
                'backup_schedule' => 'Daily 00:00 WIB',
                'notes' => 'Responsive design untuk semua device',
                'icon' => 'fas fa-globe-asia',
                'sort_order' => 5,
            ],
            [
                'name' => 'Sistem Informasi Konsuler',
                'code' => 'SIKON',
                'description' => 'Aplikasi untuk pelayanan kekonsuleran WNI di luar negeri',
                'version' => '2.2.1',
                'status' => 'active',
                'criticality' => 'high',
                'category' => 'Consular Service',
                'vendor' => 'PT. Konsul Teknologi',
                'contact_person' => 'Hendro Wicaksono',
                'contact_email' => 'hendro.wicaksono@konsultech.co.id',
                'contact_phone' => '+62-21-3841357',
                'technical_documentation' => 'https://docs.konsultech.co.id/sikon',
                'supported_os' => ['Windows 10', 'Windows 11'],
                'supported_browsers' => ['Chrome', 'Firefox', 'Edge'],
                'server_location' => 'Data Center Kemlu Jakarta & Backup Server',
                'backup_schedule' => 'Daily 02:30 WIB',
                'notes' => 'High availability untuk pelayanan 24/7',
                'icon' => 'fas fa-passport',
                'sort_order' => 6,
            ],
            [
                'name' => 'Sistem Monitoring Aset',
                'code' => 'SIMONAS',
                'description' => 'Monitoring dan inventarisasi aset kedutaan dan konsulat',
                'version' => '1.3.0',
                'status' => 'active',
                'criticality' => 'medium',
                'category' => 'Asset Management',
                'vendor' => 'PT. Asset Prima',
                'contact_person' => 'Maya Sari',
                'contact_email' => 'maya.sari@assetprima.co.id',
                'contact_phone' => '+62-21-3849753',
                'technical_documentation' => 'https://docs.assetprima.co.id/simonas',
                'supported_os' => ['Windows 10', 'Windows 11', 'Android'],
                'supported_browsers' => ['Chrome', 'Firefox'],
                'server_location' => 'Data Center Kemlu Jakarta',
                'backup_schedule' => 'Weekly Sunday 01:00 WIB',
                'notes' => 'Include mobile app untuk tracking',
                'icon' => 'fas fa-boxes',
                'sort_order' => 7,
            ],
            [
                'name' => 'E-Learning Platform Kemlu',
                'code' => 'E-LEARN',
                'description' => 'Platform pembelajaran dan pelatihan online untuk pegawai',
                'version' => '1.1.0',
                'status' => 'active',
                'criticality' => 'low',
                'category' => 'Education',
                'vendor' => 'Kementerian Luar Negeri',
                'contact_person' => 'Tina Agustina',
                'contact_email' => 'tina.agustina@kemlu.go.id',
                'contact_phone' => '+62-21-3848642',
                'technical_documentation' => 'https://elearning.kemlu.go.id/help',
                'supported_os' => ['Windows', 'macOS', 'Linux', 'iOS', 'Android'],
                'supported_browsers' => ['Chrome', 'Firefox', 'Safari', 'Edge'],
                'server_location' => 'Cloud Server (Google Cloud)',
                'backup_schedule' => 'Daily 23:00 WIB',
                'notes' => 'Platform untuk pengembangan kompetensi',
                'icon' => 'fas fa-graduation-cap',
                'sort_order' => 8,
            ],
            [
                'name' => 'Sistem Helpdesk & Ticketing',
                'code' => 'HELPDESK',
                'description' => 'Sistem manajemen tiket dan permintaan bantuan teknis',
                'version' => '1.0.0',
                'status' => 'active',
                'criticality' => 'medium',
                'category' => 'IT Service Management',
                'vendor' => 'Kementerian Luar Negeri',
                'contact_person' => 'Ahmad Fauzi',
                'contact_email' => 'ahmad.fauzi@kemlu.go.id',
                'contact_phone' => '+62-21-3841122',
                'technical_documentation' => 'https://helpdesk.kemlu.go.id/docs',
                'supported_os' => ['Windows', 'macOS', 'Linux', 'iOS', 'Android'],
                'supported_browsers' => ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'],
                'server_location' => 'Local Server Kemlu',
                'backup_schedule' => 'Daily 04:00 WIB',
                'notes' => 'Sistem yang sedang dikembangkan',
                'icon' => 'fas fa-headset',
                'sort_order' => 9,
            ],
            [
                'name' => 'Aplikasi Protokol Diplomatik',
                'code' => 'APD',
                'description' => 'Manajemen acara protokol dan kunjungan diplomatik',
                'version' => '1.7.0',
                'status' => 'active',
                'criticality' => 'medium',
                'category' => 'Protocol Management',
                'vendor' => 'PT. Protokol Digital',
                'contact_person' => 'Rina Wijaya',
                'contact_email' => 'rina.wijaya@prodigit.co.id',
                'contact_phone' => '+62-21-3847531',
                'technical_documentation' => 'https://docs.prodigit.co.id/apd',
                'supported_os' => ['Windows 10', 'Windows 11', 'iOS'],
                'supported_browsers' => ['Chrome', 'Safari', 'Edge'],
                'server_location' => 'Data Center Kemlu Jakarta',
                'backup_schedule' => 'Daily 01:30 WIB',
                'notes' => 'Aplikasi untuk manajemen event protokol',
                'icon' => 'fas fa-handshake',
                'sort_order' => 10,
            ],
        ];

        foreach ($applications as $app) {
            $appCode = $app['code'];
            
            // Add admin assignments if mapping exists
            if (isset($appAdminMapping[$appCode])) {
                // Check if admin aplikasi exists before assigning
                $primaryAdmin = AdminAplikasi::where('nip', $appAdminMapping[$appCode]['primary'])->first();
                $backupAdmin = AdminAplikasi::where('nip', $appAdminMapping[$appCode]['backup'])->first();
                
                if ($primaryAdmin) {
                    $app['admin_aplikasi_nip'] = $primaryAdmin->nip;
                }
                
                if ($backupAdmin) {
                    $app['backup_admin_nip'] = $backupAdmin->nip;
                }
            }
            
            Aplikasi::updateOrCreate(
                ['code' => $app['code']], // Use unique 'code' field as identifier
                $app
            );
        }
        
        $this->command->info('✓ Applications seeded successfully with admin assignments');
    }
}