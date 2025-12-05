<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'nip' => '198501012010011001',
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@kemlu.go.id',
                'password' => Hash::make('password123'),
                'position' => 'Diplomat Madya',
                'department' => 'Direktorat Asia Pasifik',
                'phone' => '+62-21-3841001',
                'status' => 'active',
            ],
            [
                'nip' => '198602152010012002',
                'name' => 'Siti Nurhaliza',
                'email' => 'siti.nurhaliza@kemlu.go.id',
                'password' => Hash::make('password123'),
                'position' => 'Diplomat Muda',
                'department' => 'Direktorat Eropa dan Amerika',
                'phone' => '+62-21-3841002',
                'status' => 'active',
            ],
            [
                'nip' => '198703202010013003',
                'name' => 'Agus Setiawan',
                'email' => 'agus.setiawan@kemlu.go.id',
                'password' => Hash::make('password123'),
                'position' => 'Analis Diplomatik Pertama',
                'department' => 'Direktorat Hukum dan Perjanjian Internasional',
                'phone' => '+62-21-3841003',
                'status' => 'active',
            ],
            [
                'nip' => '198804252010014004',
                'name' => 'Dewi Sartika',
                'email' => 'dewi.sartika@kemlu.go.id',
                'password' => Hash::make('password123'),
                'position' => 'Diplomat Madya',
                'department' => 'Direktorat Informasi dan Media',
                'phone' => '+62-21-3841004',
                'status' => 'active',
            ],
            [
                'nip' => '198905102010015005',
                'name' => 'Rizki Ramadhan',
                'email' => 'rizki.ramadhan@kemlu.go.id',
                'password' => Hash::make('password123'),
                'position' => 'Diplomat Muda',
                'department' => 'Direktorat Protokol dan Konsuler',
                'phone' => '+62-21-3841005',
                'status' => 'active',
            ],
            [
                'nip' => '199006152010016006',
                'name' => 'Maya Sari Dewi',
                'email' => 'maya.dewi@kemlu.go.id',
                'password' => Hash::make('password123'),
                'position' => 'Analis Kebijakan',
                'department' => 'Sekretariat Badan Pengkajian dan Pengembangan Kebijakan',
                'phone' => '+62-21-3841006',
                'status' => 'active',
            ],
            [
                'nip' => '199107202010017007',
                'name' => 'Hendro Wicaksono',
                'email' => 'hendro.wicaksono@kemlu.go.id',
                'password' => Hash::make('password123'),
                'position' => 'Diplomat Muda',
                'department' => 'Direktorat Kerja Sama Teknik',
                'phone' => '+62-21-3841007',
                'status' => 'active',
            ],
            [
                'nip' => '199208252010018008',
                'name' => 'Tina Agustina',
                'email' => 'tina.agustina@kemlu.go.id',
                'password' => Hash::make('password123'),
                'position' => 'Staf Pendidikan',
                'department' => 'Pusat Pendidikan dan Pelatihan',
                'phone' => '+62-21-3841008',
                'status' => 'active',
            ],
            [
                'nip' => '199309102010019009',
                'name' => 'Ahmad Fauzi Rahman',
                'email' => 'ahmad.fauzi@kemlu.go.id',
                'password' => Hash::make('password123'),
                'position' => 'Auditor Muda',
                'department' => 'Inspektorat',
                'phone' => '+62-21-3841009',
                'status' => 'active',
            ],
            [
                'nip' => '199410152010011010',
                'name' => 'Rina Wijaya Sari',
                'email' => 'rina.wijaya@kemlu.go.id',
                'password' => Hash::make('password123'),
                'position' => 'Diplomat Pertama',
                'department' => 'Direktorat Afrika dan Timur Tengah',
                'phone' => '+62-21-3841010',
                'status' => 'active',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['nip' => $userData['nip']],
                $userData
            );
        }
    }
}