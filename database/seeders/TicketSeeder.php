<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\AdminHelpdesk;
use App\Models\Teknisi;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $applications = Aplikasi::all();
        $categories = KategoriMasalah::all();
        $adminHelpdesks = AdminHelpdesk::all();
        $teknisis = Teknisi::all();

        if ($users->isEmpty() || $applications->isEmpty() || $categories->isEmpty() || $adminHelpdesks->isEmpty() || $teknisis->isEmpty()) {
            $this->command->warn('Missing required data. Please run UserSeeder, AplikasiSeeder, KategoriMasalahSeeder, AdminHelpdeskSeeder, and TeknisiSeeder first.');
            return;
        }

        $tickets = [
            [
                'ticket_number' => 'TKT-20251004-0001',
                'user_nip' => $users->where('name', 'Budi Santoso')->first()?->nip ?? $users->first()->nip,
                'aplikasi_id' => $applications->where('code', 'SIKEP')->first()?->id ?? $applications->first()->id,
                'kategori_masalah_id' => $categories->where('name', 'Login & Authentication')->first()?->id ?? $categories->first()->id,
                'assigned_teknisi_nip' => $teknisis->where('name', 'Andi Wijaya, S.Kom.')->first()?->nip ?? $teknisis->first()->nip,
                'assigned_by_nip' => $adminHelpdesks->first()?->nip,
                'title' => 'Tidak bisa login ke SIKEP',
                'description' => 'Saya sudah mencoba login ke aplikasi SIKEP menggunakan username dan password yang biasa, tapi selalu gagal. Error message: "Invalid credentials". Padahal saya yakin username dan password sudah benar. Sudah coba clear cache browser tapi masih sama.',
                'priority' => 'high',
                'status' => 'resolved',
                'location' => 'Direktorat Asia Pasifik',
                'device_info' => 'Chrome 120.0.6099.71, Windows 11 Pro',
                'ip_address' => '192.168.1.100',
                'resolution_notes' => 'Password berhasil direset. User diminta untuk menggunakan fitur forgot password.',
                'resolution_time_minutes' => 25,
                'user_rating' => 5,
                'user_feedback' => 'Terima kasih atas bantuannya yang cepat!',
                'due_date' => Carbon::now()->addHours(4),
                'first_response_at' => Carbon::now()->subHours(2),
                'resolved_at' => Carbon::now()->subHours(1),
                'closed_at' => Carbon::now()->subMinutes(30),
                'view_count' => 12,
                'created_at' => Carbon::now()->subHours(3),
                'updated_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'ticket_number' => 'TKT-20251004-0002',
                'user_nip' => $users->where('name', 'Siti Nurhaliza')->first()?->nip ?? $users->skip(1)->first()->nip,
                'aplikasi_id' => $applications->where('code', 'E-OFFICE')->first()?->id ?? $applications->skip(1)->first()->id,
                'kategori_masalah_id' => $categories->where('name', 'Workflow Approval Stuck')->first()?->id ?? $categories->skip(1)->first()->id,
                'assigned_teknisi_nip' => $teknisis->where('name', 'Citra Kirana, S.Kom.')->first()?->nip ?? $teknisis->skip(1)->first()->nip,
                'assigned_by_nip' => $adminHelpdesks->first()?->nip,
                'title' => 'Surat tidak kunjung di-approve atasan',
                'description' => 'Saya sudah submit surat izin cuti sejak kemarin pagi, tapi sampai sekarang statusnya masih "Pending Approval" dari Direktur. Padahal surat sudah dikirim ke atasan langsung. Mohon bantuan untuk mengecek workflow approval.',
                'priority' => 'medium',
                'status' => 'in_progress',
                'location' => 'Direktorat Eropa dan Amerika',
                'device_info' => 'Firefox 119.0, Windows 10 Enterprise',
                'ip_address' => '192.168.1.101',
                'due_date' => Carbon::now()->addHours(8),
                'first_response_at' => Carbon::now()->subHours(1),
                'view_count' => 8,
                'created_at' => Carbon::now()->subHours(6),
                'updated_at' => Carbon::now()->subHours(1),
            ],
            [
                'ticket_number' => 'TKT-20251004-0003',
                'user_nip' => $users->where('name', 'Agus Setiawan')->first()?->nip ?? $users->skip(2)->first()->nip,
                'aplikasi_id' => $applications->where('code', 'SIAN')->first()?->id ?? $applications->skip(2)->first()->id,
                'kategori_masalah_id' => $categories->where('name', 'Upload Dokumen Gagal')->first()?->id ?? $categories->skip(2)->first()->id,
                'assigned_teknisi_nip' => $teknisis->where('name', 'Budi Santoso, S.T.')->first()?->nip ?? $teknisis->skip(2)->first()->nip,
                'assigned_by_nip' => $adminHelpdesks->skip(1)->first()?->nip ?? $adminHelpdesks->first()?->nip,
                'title' => 'Upload dokumen rahasia selalu gagal',
                'description' => 'Setiap kali saya mencoba upload dokumen dengan klasifikasi RAHASIA, proses selalu gagal di tengah jalan. File size sekitar 15MB, format PDF. Error message: "Upload timeout". Sudah coba beberapa kali dengan koneksi berbeda tapi hasilnya sama.',
                'priority' => 'urgent',
                'status' => 'assigned',
                'location' => 'Direktorat Hukum dan Perjanjian Internasional',
                'device_info' => 'Chrome 120.0.6099.71, Windows 11 Pro',
                'ip_address' => '192.168.1.102',
                'due_date' => Carbon::now()->addHours(2),
                'first_response_at' => Carbon::now()->subMinutes(30),
                'view_count' => 15,
                'created_at' => Carbon::now()->subHours(4),
                'updated_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'ticket_number' => 'TKT-20251007-0001',
                'user_nip' => $users->where('name', 'Dewi Sartika')->first()?->nip ?? $users->skip(3)->first()->nip,
                'aplikasi_id' => $applications->where('code', 'PDD')->first()?->id ?? $applications->skip(3)->first()->id,
                'kategori_masalah_id' => $categories->where('name', 'Website Tidak Bisa Diakses')->first()?->id ?? $categories->skip(3)->first()->id,
                'assigned_teknisi_nip' => $teknisis->where('name', 'Doni Ramadhan, S.T.')->first()?->nip ?? $teknisis->skip(3)->first()->nip,
                'assigned_by_nip' => $adminHelpdesks->first()?->nip,
                'title' => 'Portal web kemlu.go.id tidak bisa diakses',
                'description' => 'Dari tadi pagi portal website Kementerian Luar Negeri tidak bisa diakses. Ketika dibuka muncul error "500 Internal Server Error". Ini sangat mengganggu karena ada beberapa informasi penting yang harus diakses dari website.',
                'priority' => 'urgent',
                'status' => 'in_progress',
                'location' => 'Direktorat Informasi dan Media',
                'device_info' => 'Safari 17.1.2, macOS Sonoma',
                'ip_address' => '192.168.1.103',
                'due_date' => Carbon::now()->addHours(1),
                'first_response_at' => Carbon::now()->subMinutes(15),
                'view_count' => 23,
                'created_at' => Carbon::now()->subHours(2),
                'updated_at' => Carbon::now()->subMinutes(15),
            ],
            [
                'ticket_number' => 'TKT-20251007-0002',
                'user_nip' => $users->where('name', 'Rizki Ramadhan')->first()?->nip ?? $users->skip(4)->first()->nip,
                'aplikasi_id' => $applications->where('code', 'SIKEU')->first()?->id ?? $applications->skip(4)->first()->id,
                'kategori_masalah_id' => $categories->where('name', 'Anggaran Tidak Balance')->first()?->id ?? $categories->skip(4)->first()->id,
                'assigned_teknisi_nip' => $teknisis->where('name', 'Gita Permata, S.Kom.')->first()?->nip ?? $teknisis->skip(4)->first()->nip,
                'assigned_by_nip' => $adminHelpdesks->skip(1)->first()?->nip ?? $adminHelpdesks->first()?->nip,
                'title' => 'Laporan anggaran bulan ini tidak balance',
                'description' => 'Laporan anggaran untuk bulan Oktober 2025 menunjukkan ketidaksesuaian antara pemasukan dan pengeluaran. Total pengeluaran lebih besar Rp 50.000.000 dari yang seharusnya. Perlu dicek apakah ada transaksi yang double entry atau kesalahan input.',
                'priority' => 'high',
                'status' => 'waiting_user',
                'location' => 'Direktorat Protokol dan Konsuler',
                'device_info' => 'Edge 118.0.2088.76, Windows 11 Pro',
                'ip_address' => '192.168.1.104',
                'due_date' => Carbon::now()->addDays(1),
                'first_response_at' => Carbon::now()->subHours(3),
                'view_count' => 7,
                'created_at' => Carbon::now()->subHours(8),
                'updated_at' => Carbon::now()->subHours(3),
            ],
            [
                'ticket_number' => 'TKT-20251009-0001',
                'user_nip' => $users->where('name', 'Maya Sari Dewi')->first()?->nip ?? $users->skip(5)->first()?->nip ?? $users->first()->nip,
                'aplikasi_id' => $applications->where('code', 'SIKON')->first()?->id ?? $applications->first()->id,
                'kategori_masalah_id' => $categories->where('name', 'Data WNI Tidak Ditemukan')->first()?->id ?? $categories->first()->id,
                'assigned_teknisi_nip' => $teknisis->where('name', 'Hadi Saputra, S.T.')->first()?->nip ?? $teknisis->first()->nip,
                'assigned_by_nip' => $adminHelpdesks->first()?->nip,
                'title' => 'Data WNI di Jeddah tidak ditemukan dalam sistem',
                'description' => 'Saya mencoba mencari data WNI atas nama Ahmad Hassan yang sedang berada di Jeddah, Saudi Arabia untuk keperluan konsuler, tapi data tidak ditemukan dalam sistem SIKON. Padahal menurut informasi dari keluarga, yang bersangkutan sudah melapor ke KJRI Jeddah bulan lalu.',
                'priority' => 'high',
                'status' => 'waiting_admin',
                'location' => 'Sekretariat Badan Pengkajian dan Pengembangan Kebijakan',
                'device_info' => 'Chrome 120.0.6099.71, Windows 10 Enterprise',
                'ip_address' => '192.168.1.105',
                'due_date' => Carbon::now()->addHours(6),
                'first_response_at' => Carbon::now()->subHours(2),
                'view_count' => 11,
                'created_at' => Carbon::now()->subHours(5),
                'updated_at' => Carbon::now()->subHours(2),
            ],
            [
                'ticket_number' => 'TKT-20251009-0002',
                'user_nip' => $users->where('name', 'Hendro Wicaksono')->first()?->nip ?? $users->skip(6)->first()?->nip ?? $users->first()->nip,
                'aplikasi_id' => $applications->where('code', 'SIMONAS')->first()?->id ?? $applications->skip(1)->first()->id,
                'kategori_masalah_id' => $categories->where('name', 'QR Code Tidak Bisa Scan')->first()?->id ?? $categories->skip(1)->first()->id,
                'assigned_teknisi_nip' => $teknisis->where('name', 'Indah Lestari, S.Kom.')->first()?->nip ?? $teknisis->skip(1)->first()->nip,
                'assigned_by_nip' => $adminHelpdesks->skip(1)->first()?->nip ?? $adminHelpdesks->first()?->nip,
                'title' => 'QR Code asset inventaris tidak terbaca',
                'description' => 'Aplikasi SIMONAS di smartphone tidak bisa scan QR code untuk asset inventaris nomor INV-2025-0456. QR code terlihat jelas dan tidak rusak, tapi aplikasi selalu gagal mendeteksi. Sudah coba restart aplikasi dan clear cache tapi masih sama.',
                'priority' => 'low',
                'status' => 'open',
                'location' => 'Direktorat Kerja Sama Teknik',
                'device_info' => 'SIMONAS Mobile App v2.1, Android 13',
                'ip_address' => '192.168.1.106',
                'due_date' => Carbon::now()->addDays(2),
                'view_count' => 4,
                'created_at' => Carbon::now()->subHours(1),
                'updated_at' => Carbon::now()->subHours(1),
            ],
            [
                'ticket_number' => 'TKT-20251010-0001',
                'user_nip' => $users->where('name', 'Tina Agustina')->first()?->nip ?? $users->skip(7)->first()?->nip ?? $users->skip(1)->first()->nip,
                'aplikasi_id' => $applications->where('code', 'E-LEARN')->first()?->id ?? $applications->skip(2)->first()->id,
                'kategori_masalah_id' => $categories->where('name', 'Video Tidak Bisa Diputar')->first()?->id ?? $categories->skip(2)->first()->id,
                'assigned_teknisi_nip' => $teknisis->where('name', 'Joko Susilo, S.T.')->first()?->nip ?? $teknisis->skip(2)->first()->nip,
                'assigned_by_nip' => $adminHelpdesks->first()?->nip,
                'title' => 'Video training protokol diplomatik tidak bisa diputar',
                'description' => 'Saya sedang mengikuti kursus "Protokol Diplomatik Lanjutan" di E-Learning platform, tapi video modul 3 tentang "Tata Cara Penyambutan Tamu Negara" tidak bisa diputar sama sekali. Loading terus menerus dan akhirnya error. Koneksi internet normal dan video lain bisa diputar.',
                'priority' => 'medium',
                'status' => 'resolved',
                'location' => 'Pusat Pendidikan dan Pelatihan',
                'device_info' => 'Chrome 120.0.6099.71, Windows 11 Pro',
                'ip_address' => '192.168.1.107',
                'resolution_notes' => 'Masalah disebabkan oleh format video yang corrupted. Video sudah di-replace dengan file baru.',
                'resolution_time_minutes' => 90,
                'user_rating' => 4,
                'user_feedback' => 'Video sudah bisa diputar, terima kasih atas perbaikan yang cepat.',
                'due_date' => Carbon::now()->addHours(12),
                'first_response_at' => Carbon::now()->subHours(6),
                'resolved_at' => Carbon::now()->subHours(3),
                'closed_at' => Carbon::now()->subHours(2),
                'view_count' => 9,
                'created_at' => Carbon::now()->subHours(8),
                'updated_at' => Carbon::now()->subHours(2),
            ],
            [
                'ticket_number' => 'TKT-20251010-0002',
                'user_nip' => $users->where('name', 'Ahmad Fauzi Rahman')->first()?->nip ?? $users->skip(8)->first()?->nip ?? $users->skip(2)->first()->nip,
                'aplikasi_id' => $applications->where('code', 'HELPDESK')->first()?->id ?? $applications->skip(3)->first()->id,
                'kategori_masalah_id' => $categories->where('name', 'Tidak Bisa Buat Tiket')->first()?->id ?? $categories->skip(3)->first()->id,
                'assigned_teknisi_nip' => $teknisis->where('name', 'Eva Sari Dewi, S.Kom.')->first()?->nip ?? $teknisis->skip(3)->first()->nip,
                'assigned_by_nip' => $adminHelpdesks->skip(1)->first()?->nip ?? $adminHelpdesks->first()?->nip,
                'title' => 'Form pembuatan tiket tidak muncul',
                'description' => 'Ketika saya klik tombol "Buat Tiket Baru" di halaman helpdesk, form untuk pengisian tiket tidak muncul sama sekali. Halaman hanya loading sebentar lalu kembali ke halaman list tiket. Sudah coba dengan beberapa browser berbeda tapi hasilnya sama.',
                'priority' => 'high',
                'status' => 'in_progress',
                'location' => 'Inspektorat',
                'device_info' => 'Chrome 120.0.6099.71, Windows 11 Pro',
                'ip_address' => '192.168.1.108',
                'due_date' => Carbon::now()->addHours(4),
                'first_response_at' => Carbon::now()->subMinutes(45),
                'view_count' => 6,
                'created_at' => Carbon::now()->subHours(3),
                'updated_at' => Carbon::now()->subMinutes(45),
            ],
            [
                'ticket_number' => 'TKT-20251011-0001',
                'user_nip' => $users->where('name', 'Rina Wijaya Sari')->first()?->nip ?? $users->skip(9)->first()?->nip ?? $users->skip(3)->first()->nip,
                'aplikasi_id' => $applications->where('code', 'APD')->first()?->id ?? $applications->skip(4)->first()->id,
                'kategori_masalah_id' => $categories->where('name', 'Jadwal Protokol Bentrok')->first()?->id ?? $categories->skip(4)->first()->id,
                'assigned_teknisi_nip' => $teknisis->where('name', 'Fajar Nugroho, S.T.')->first()?->nip ?? $teknisis->skip(4)->first()->nip,
                'assigned_by_nip' => $adminHelpdesks->first()?->nip,
                'title' => 'Jadwal kunjungan delegasi Malaysia bentrok dengan acara internal',
                'description' => 'Kunjungan delegasi Malaysia yang dijadwalkan tanggal 15 Oktober 2025 bentrok dengan rapat internal Direktorat yang sudah di-agendakan sejak bulan lalu. Sistem APD tidak memberikan warning saat penginputan jadwal. Perlu penyesuaian prioritas dan koordinasi ulang.',
                'priority' => 'high',
                'status' => 'waiting_admin',
                'location' => 'Direktorat Afrika dan Timur Tengah',
                'device_info' => 'Chrome 120.0.6099.71, Windows 11 Pro',
                'ip_address' => '192.168.1.109',
                'due_date' => Carbon::now()->addDays(1),
                'first_response_at' => Carbon::now()->subHours(4),
                'view_count' => 13,
                'created_at' => Carbon::now()->subHours(7),
                'updated_at' => Carbon::now()->subHours(4),
            ],
        ];

        foreach ($tickets as $ticket) {
            Ticket::firstOrCreate(
                ['ticket_number' => $ticket['ticket_number']],
                $ticket
            );
        }
    }
}
