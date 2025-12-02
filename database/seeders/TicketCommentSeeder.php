<?php

namespace Database\Seeders;

use App\Models\TicketComment;
use App\Models\Ticket;
use App\Models\User;
use App\Models\AdminHelpdesk;
use App\Models\Teknisi;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TicketCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tickets = Ticket::all();
        $users = User::all();
        $adminHelpdesks = AdminHelpdesk::all();
        $teknisis = Teknisi::all();

        // Check if required dependencies exist
        if ($tickets->isEmpty()) {
            $this->command->warn('No tickets found. Please run TicketSeeder first.');
            return;
        }

        $comments = [
            [
                'ticket_id' => 1, // TICKET-2025-001
                'commenter_nip' => '199001012015011001', // Andi Wijaya (Teknisi)
                'commenter_type' => 'App\Models\Teknisi',
                'comment' => 'Halo Bapak Budi Santoso, saya akan membantu menyelesaikan masalah login SIKEP Anda. Untuk sementara, silakan coba reset password melalui fitur "Lupa Password" di halaman login. Apakah Anda masih ingat alamat email yang terdaftar?',
                'type' => 'comment',
                'is_internal' => false,
                'requires_response' => true,
                'technical_details' => 'User mengalami masalah autentikasi. Perlu verifikasi email terdaftar.',
                'created_at' => Carbon::now()->subHours(2),
            ],
            [
                'ticket_id' => 1,
                'commenter_nip' => '198501012010011001', // Budi Santoso (User)
                'commenter_type' => 'App\Models\User',
                'comment' => 'Terima kasih atas respons cepatnya. Saya sudah coba fitur lupa password dan berhasil reset. Sekarang sudah bisa login kembali. Masalah sudah teratasi.',
                'type' => 'comment',
                'is_internal' => false,
                'requires_response' => false,
                'responded_at' => Carbon::now()->subHours(1),
                'created_at' => Carbon::now()->subHours(1),
            ],
            [
                'ticket_id' => 1,
                'commenter_nip' => '199001012015011001', // Andi Wijaya (Teknisi)
                'commenter_type' => 'App\Models\Teknisi',
                'comment' => 'Senang mendengar masalah sudah teratasi. Untuk mencegah kejadian serupa, sebaiknya gunakan password manager dan aktifkan 2FA jika tersedia. Tiket akan saya tutup setelah konfirmasi dari Anda.',
                'type' => 'resolution',
                'is_internal' => false,
                'requires_response' => false,
                'created_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'ticket_id' => 2, // TICKET-2025-002
                'commenter_nip' => '199203202015013003', // Citra Kirana (Teknisi)
                'commenter_type' => 'App\Models\Teknisi',
                'comment' => 'Selamat pagi Ibu Siti Nurhaliza, saya sudah menerima tiket Anda mengenai surat yang tidak kunjung di-approve. Saya perlu informasi tambahan: kapan surat tersebut di-submit dan siapa atasan langsung yang seharusnya memberikan approval?',
                'type' => 'comment',
                'is_internal' => false,
                'requires_response' => true,
                'technical_details' => 'Perlu trace workflow approval di database E-Office.',
                'created_at' => Carbon::now()->subHours(1),
            ],
            [
                'ticket_id' => 3, // TICKET-2025-003
                'commenter_nip' => '199304252015014004', // Doni Ramadhan (Teknisi)
                'commenter_type' => 'App\Models\Teknisi',
                'comment' => 'URGENT: Bapak Agus Setiawan, masalah upload dokumen rahasia ini memerlukan penanganan khusus karena melibatkan file dengan klasifikasi RAHASIA. Saya akan koordinasi dengan tim security untuk pengecekan khusus. Mohon kesediaan untuk verifikasi identitas via video call.',
                'type' => 'comment',
                'is_internal' => false,
                'requires_response' => true,
                'technical_details' => 'File rahasia memerlukan special handling dan verifikasi tambahan.',
                'created_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'ticket_id' => 4, // TICKET-2025-004
                'commenter_nip' => '199405102015015005', // Eva Sari Dewi (Teknisi)
                'commenter_type' => 'App\Models\Teknisi',
                'comment' => 'Ibu Dewi Sartika, website kemlu.go.id sedang mengalami masalah teknis dan tim sudah sedang menanganinya. Sebagai alternatif sementara, Anda bisa mengakses informasi melalui intranet.kemlu.go.id atau menghubungi bagian informasi langsung di ext. 4100.',
                'type' => 'comment',
                'is_internal' => false,
                'requires_response' => false,
                'technical_details' => 'Website down - server maintenance sedang berlangsung.',
                'created_at' => Carbon::now()->subMinutes(15),
            ],
            [
                'ticket_id' => 5, // TICKET-2025-005
                'commenter_nip' => '199607202015017007', // Gita Permata (Teknisi)
                'commenter_type' => 'App\Models\Teknisi',
                'comment' => 'Bapak Rizki Ramadhan, saya sudah melakukan pengecekan terhadap laporan anggaran bulan Oktober 2025. Ditemukan ada kesalahan input pada tanggal 15 Oktober sebesar Rp 50.000.000 untuk kategori "Biaya Perjalanan Dinas". Apakah ini perlu dikoreksi atau ada penjelasan khusus?',
                'type' => 'comment',
                'is_internal' => false,
                'requires_response' => true,
                'technical_details' => 'Data entry error pada transaksi ID: TRX-20251015-001.',
                'created_at' => Carbon::now()->subHours(3),
            ],
            [
                'ticket_id' => 6, // TICKET-2025-006
                'commenter_nip' => '199708252015018008', // Hadi Saputra (Teknisi)
                'commenter_type' => 'App\Models\Teknisi',
                'comment' => 'Bapak Hendro Wicaksono, saya sudah menghubungi KJRI Jeddah untuk verifikasi data WNI Ahmad Hassan. Menurut informasi dari sana, yang bersangkutan sudah pindah alamat dan belum update data terbaru. Kita perlu koordinasi dengan pihak imigrasi setempat untuk update data.',
                'type' => 'comment',
                'is_internal' => false,
                'requires_response' => false,
                'technical_details' => 'Data perlu verifikasi dengan pihak imigrasi Saudi Arabia.',
                'created_at' => Carbon::now()->subHours(2),
            ],
            [
                'ticket_id' => 7, // TICKET-2025-007
                'commenter_nip' => '199809102015019009', // Indah Lestari (Teknisi)
                'commenter_type' => 'App\Models\Teknisi',
                'comment' => 'Ibu Maya Sari Dewi, saya akan membantu dengan masalah scanning QR code di aplikasi SIMONAS. Pertama, pastikan kamera smartphone bersih dan pencahayaan cukup. Coba aplikasi dengan QR code lain dulu untuk test fungsionalitas kamera.',
                'type' => 'comment',
                'is_internal' => false,
                'requires_response' => true,
                'technical_details' => 'Kemungkinan masalah kamera atau kualitas QR code.',
                'created_at' => Carbon::now()->subMinutes(45),
            ],
            [
                'ticket_id' => 8, // TICKET-2025-008
                'commenter_nip' => '199910152015011010', // Joko Susilo (Teknisi)
                'commenter_type' => 'App\Models\Teknisi',
                'comment' => 'Ibu Tina Agustina, video training yang bermasalah sudah saya periksa. File video modul 3 memang corrupted. Saya akan mengganti dengan file backup yang masih bagus. Proses ini akan memakan waktu sekitar 30 menit. Saya akan update progress-nya.',
                'type' => 'comment',
                'is_internal' => false,
                'requires_response' => false,
                'technical_details' => 'File video corrupted - perlu replace dengan backup file.',
                'created_at' => Carbon::now()->subHours(6),
            ],
            [
                'ticket_id' => 8,
                'commenter_nip' => '199910152015011010', // Joko Susilo (Teknisi)
                'commenter_type' => 'App\Models\Teknisi',
                'comment' => 'Update: Video sudah berhasil diganti dengan file yang baik. Silakan coba akses kembali dan refresh halaman browser jika perlu. Video sekarang seharusnya bisa diputar dengan normal.',
                'type' => 'status_update',
                'is_internal' => false,
                'requires_response' => false,
                'created_at' => Carbon::now()->subHours(3),
            ],
            [
                'ticket_id' => 9, // TICKET-2025-009
                'commenter_nip' => '199405102015015005', // Eva Sari Dewi (Teknisi)
                'commenter_type' => 'App\Models\Teknisi',
                'comment' => 'Bapak Ahmad Fauzi Rahman, saya sudah mengecek form pembuatan tiket di helpdesk system. Ada kemungkinan masalah JavaScript yang tidak loaded dengan benar. Coba clear browser cache dan cookies, lalu akses kembali. Jika masih bermasalah, silakan coba dengan mode incognito.',
                'type' => 'comment',
                'is_internal' => false,
                'requires_response' => true,
                'technical_details' => 'JavaScript error pada form submission.',
                'created_at' => Carbon::now()->subMinutes(45),
            ],
            [
                'ticket_id' => 10, // TICKET-2025-010
                'commenter_nip' => '199506152015016006', // Fajar Nugroho (Teknisi)
                'commenter_type' => 'App\Models\Teknisi',
                'comment' => 'Ibu Rina Wijaya Sari, masalah bentrok jadwal protokol ini memang perlu koordinasi dengan beberapa pihak. Saya sudah informasikan ke Direktur Protokol dan menunggu arahan lebih lanjut. Sistem APD akan diupdate untuk memberikan warning yang lebih jelas untuk konflik jadwal.',
                'type' => 'comment',
                'is_internal' => false,
                'requires_response' => false,
                'technical_details' => 'Perlu koordinasi antar direktorat untuk resolusi konflik jadwal.',
                'created_at' => Carbon::now()->subHours(4),
            ],
            // Internal comments
            [
                'ticket_id' => 3,
                'commenter_nip' => '198001012005011001', // Dr. Ahmad Surya Wijaya (Admin Helpdesk)
                'commenter_type' => 'App\Models\AdminHelpdesk',
                'comment' => 'CASE SENSITIVE: File rahasia - perlu melibatkan tim security untuk audit trail. Jangan proses melalui channel normal.',
                'type' => 'comment',
                'is_internal' => true,
                'requires_response' => false,
                'technical_details' => 'Security clearance level: RAHASIA - memerlukan special handling protocol.',
                'created_at' => Carbon::now()->subMinutes(20),
            ],
            [
                'ticket_id' => 4,
                'commenter_nip' => '198001012005011001', // Dr. Ahmad Surya Wijaya (Admin Helpdesk)
                'commenter_type' => 'App\Models\AdminHelpdesk',
                'comment' => 'Website down - kemungkinan DDoS attack atau server overload. Tim sudah dikerahkan untuk investigation.',
                'type' => 'status_update',
                'is_internal' => true,
                'requires_response' => false,
                'technical_details' => 'Possible security incident - monitoring network traffic.',
                'created_at' => Carbon::now()->subMinutes(10),
            ],
        ];

        foreach ($comments as $comment) {
            // Use a combination of fields that make each comment unique
            $uniqueFields = [
                'ticket_id' => $comment['ticket_id'],
                'commenter_nip' => $comment['commenter_nip'],
                'commenter_type' => $comment['commenter_type'],
                'comment' => $comment['comment'],
                'created_at' => $comment['created_at'],
            ];

            TicketComment::firstOrCreate(
                $uniqueFields,
                $comment
            );
        }
    }
}