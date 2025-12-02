<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Ticket;
use App\Models\User;
use App\Models\AdminHelpdesk;
use App\Models\Teknisi;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
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
        if ($tickets->isEmpty() || $users->isEmpty() || $adminHelpdesks->isEmpty() || $teknisis->isEmpty()) {
            $this->command->warn('Missing required data. Please run TicketSeeder, UserSeeder, AdminHelpdeskSeeder, and TeknisiSeeder first.');
            return;
        }

        $notifications = [
            [
                'id' => Str::uuid(),
                'type' => 'ticket_assigned',
                'notifiable_type' => 'App\Models\Teknisi',
                'notifiable_id' => $teknisis->where('name', 'Andi Wijaya, S.Kom.')->first()?->nip ?? $teknisis->first()->nip,
                'ticket_id' => 1,
                'triggered_by_nip' => $adminHelpdesks->first()?->nip,
                'triggered_by_type' => 'admin_helpdesk',
                'title' => 'Tiket Baru Ditugaskan',
                'message' => 'Tiket TICKET-2025-001 telah ditugaskan kepada Anda. Masalah: Tidak bisa login ke SIKEP',
                'data' => [
                    'ticket_number' => 'TICKET-2025-001',
                    'priority' => 'high',
                    'user_name' => 'Budi Santoso',
                ],
                'priority' => 'high',
                'channel' => 'database',
                'sent_at' => Carbon::now()->subHours(3),
                'created_at' => Carbon::now()->subHours(3),
            ],
            [
                'id' => Str::uuid(),
                'type' => 'ticket_updated',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $users->where('name', 'Budi Santoso')->first()?->nip ?? $users->first()->nip,
                'ticket_id' => 1,
                'triggered_by_nip' => '199001012015011001',
                'triggered_by_type' => 'teknisi',
                'title' => 'Update Tiket',
                'message' => 'Teknisi telah memberikan komentar pada tiket Anda TICKET-2025-001',
                'data' => [
                    'ticket_number' => 'TICKET-2025-001',
                    'comment_preview' => 'Halo Bapak Budi Santoso, saya akan membantu...',
                ],
                'priority' => 'medium',
                'channel' => 'database',
                'sent_at' => Carbon::now()->subHours(2),
                'created_at' => Carbon::now()->subHours(2),
            ],
            [
                'id' => Str::uuid(),
                'type' => 'ticket_resolved',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $users->where('name', 'Budi Santoso')->first()?->nip ?? $users->first()->nip,
                'ticket_id' => 1,
                'triggered_by_nip' => '199001012015011001',
                'triggered_by_type' => 'teknisi',
                'title' => 'Tiket Diselesaikan',
                'message' => 'Tiket TICKET-2025-001 telah diselesaikan. Rating dan feedback diperlukan.',
                'data' => [
                    'ticket_number' => 'TICKET-2025-001',
                    'resolution_summary' => 'Password berhasil direset melalui fitur lupa password',
                ],
                'priority' => 'medium',
                'channel' => 'database',
                'sent_at' => Carbon::now()->subMinutes(30),
                'created_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'id' => Str::uuid(),
                'type' => 'ticket_comment',
                'notifiable_type' => 'App\Models\AdminHelpdesk',
                'notifiable_id' => $adminHelpdesks->first()?->nip,
                'ticket_id' => 2,
                'triggered_by_nip' => '199203202015013003',
                'triggered_by_type' => 'teknisi',
                'title' => 'Komentar Baru pada Tiket',
                'message' => 'Teknisi Citra Kirana memberikan komentar pada tiket TICKET-2025-002',
                'data' => [
                    'ticket_number' => 'TICKET-2025-002',
                    'comment_preview' => 'Selamat pagi Ibu Siti Nurhaliza...',
                ],
                'priority' => 'medium',
                'channel' => 'database',
                'sent_at' => Carbon::now()->subHours(1),
                'created_at' => Carbon::now()->subHours(1),
            ],
            [
                'id' => Str::uuid(),
                'type' => 'urgent_ticket',
                'notifiable_type' => 'App\Models\AdminHelpdesk',
                'notifiable_id' => $adminHelpdesks->first()?->nip,
                'ticket_id' => 3,
                'triggered_by_nip' => '199304252015014004',
                'triggered_by_type' => 'teknisi',
                'title' => 'Tiket URGENT Perlu Perhatian',
                'message' => 'Tiket TICKET-2025-003 dengan klasifikasi RAHASIA memerlukan penanganan khusus',
                'data' => [
                    'ticket_number' => 'TICKET-2025-003',
                    'priority' => 'urgent',
                    'security_level' => 'RAHASIA',
                ],
                'priority' => 'urgent',
                'channel' => 'database',
                'sent_at' => Carbon::now()->subMinutes(30),
                'created_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'id' => Str::uuid(),
                'type' => 'system_maintenance',
                'notifiable_type' => 'App\Models\AdminAplikasi',
                'notifiable_id' => '197501012000011001', // Hendro Wicaksono
                'ticket_id' => 4,
                'triggered_by_nip' => 'system',
                'triggered_by_type' => 'system',
                'title' => 'Website Down - Kemungkinan Serangan',
                'message' => 'Portal kemlu.go.id tidak dapat diakses. Kemungkinan serangan DDoS terdeteksi.',
                'data' => [
                    'affected_system' => 'kemlu.go.id',
                    'incident_type' => 'possible_ddos',
                    'severity' => 'high',
                ],
                'priority' => 'urgent',
                'channel' => 'database',
                'sent_at' => Carbon::now()->subMinutes(15),
                'created_at' => Carbon::now()->subMinutes(15),
            ],
            [
                'id' => Str::uuid(),
                'type' => 'ticket_escalated',
                'notifiable_type' => 'App\Models\AdminAplikasi',
                'notifiable_id' => '197703202000013003', // Agus Setiawan (Admin E-Office)
                'ticket_id' => 2,
                'triggered_by_nip' => '198001012005011001',
                'triggered_by_type' => 'admin_helpdesk',
                'title' => 'Tiket Eskalasi',
                'message' => 'Tiket TICKET-2025-002 mengalami kendala workflow yang memerlukan intervensi admin aplikasi',
                'data' => [
                    'ticket_number' => 'TICKET-2025-002',
                    'escalation_reason' => 'Workflow approval stuck memerlukan admin intervention',
                    'application' => 'E-OFFICE',
                ],
                'priority' => 'high',
                'channel' => 'database',
                'sent_at' => Carbon::now()->subMinutes(45),
                'created_at' => Carbon::now()->subMinutes(45),
            ],
            [
                'id' => Str::uuid(),
                'type' => 'ticket_response_required',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $users->where('name', 'Rizki Ramadhan')->first()?->nip ?? $users->skip(4)->first()->nip,
                'ticket_id' => 5,
                'triggered_by_nip' => '199607202015017007',
                'triggered_by_type' => 'teknisi',
                'title' => 'Respons Diperlukan',
                'message' => 'Teknisi memerlukan konfirmasi Anda untuk tiket TICKET-2025-005 mengenai koreksi anggaran',
                'data' => [
                    'ticket_number' => 'TICKET-2025-005',
                    'question' => 'Apakah kesalahan input perlu dikoreksi?',
                ],
                'priority' => 'medium',
                'channel' => 'database',
                'sent_at' => Carbon::now()->subHours(3),
                'created_at' => Carbon::now()->subHours(3),
            ],
            [
                'id' => Str::uuid(),
                'type' => 'ticket_assigned',
                'notifiable_type' => 'App\Models\Teknisi',
                'notifiable_id' => $teknisis->where('name', 'Citra Kirana, S.Kom.')->first()?->nip ?? $teknisis->skip(1)->first()->nip,
                'ticket_id' => 2,
                'triggered_by_nip' => $adminHelpdesks->first()?->nip,
                'triggered_by_type' => 'admin_helpdesk',
                'title' => 'Tiket Baru Ditugaskan',
                'message' => 'Tiket TICKET-2025-002 telah ditugaskan kepada Anda. Masalah: Workflow approval stuck di E-Office',
                'data' => [
                    'ticket_number' => 'TICKET-2025-002',
                    'priority' => 'medium',
                    'application' => 'E-OFFICE',
                ],
                'priority' => 'medium',
                'channel' => 'database',
                'sent_at' => Carbon::now()->subHours(6),
                'created_at' => Carbon::now()->subHours(6),
            ],
            [
                'id' => Str::uuid(),
                'type' => 'ticket_comment',
                'notifiable_type' => 'App\Models\Teknisi',
                'notifiable_id' => $teknisis->where('name', 'Andi Wijaya, S.Kom.')->first()?->nip ?? $teknisis->first()->nip,
                'ticket_id' => 1,
                'triggered_by_nip' => '198501012010011001',
                'triggered_by_type' => 'user',
                'title' => 'Komentar Baru dari User',
                'message' => 'User Budi Santoso memberikan komentar pada tiket TICKET-2025-001',
                'data' => [
                    'ticket_number' => 'TICKET-2025-001',
                    'comment_preview' => 'Terima kasih atas respons cepatnya...',
                ],
                'priority' => 'low',
                'channel' => 'database',
                'sent_at' => Carbon::now()->subHours(1),
                'created_at' => Carbon::now()->subHours(1),
            ],
            [
                'id' => Str::uuid(),
                'type' => 'daily_report',
                'notifiable_type' => 'App\Models\AdminHelpdesk',
                'notifiable_id' => $adminHelpdesks->first()?->nip,
                'ticket_id' => null,
                'triggered_by_nip' => 'system',
                'triggered_by_type' => 'system',
                'title' => 'Laporan Harian Helpdesk',
                'message' => 'Laporan harian helpdesk untuk tanggal ' . Carbon::now()->format('d M Y') . ' telah tersedia',
                'data' => [
                    'report_date' => Carbon::now()->format('Y-m-d'),
                    'total_tickets' => 10,
                    'resolved_tickets' => 2,
                    'pending_tickets' => 8,
                ],
                'priority' => 'low',
                'channel' => 'database',
                'sent_at' => Carbon::now()->subHours(1),
                'created_at' => Carbon::now()->subHours(1),
            ],
            [
                'id' => Str::uuid(),
                'type' => 'system_backup',
                'notifiable_type' => 'App\Models\AdminAplikasi',
                'notifiable_id' => '197501012000011001',
                'ticket_id' => null,
                'triggered_by_nip' => 'system',
                'triggered_by_type' => 'system',
                'title' => 'Backup System Selesai',
                'message' => 'Backup rutin sistem telah selesai dijalankan pada pukul 02:00 WIB',
                'data' => [
                    'backup_time' => '02:00 WIB',
                    'backup_size' => '2.5 GB',
                    'status' => 'success',
                ],
                'priority' => 'low',
                'channel' => 'database',
                'sent_at' => Carbon::now()->subHours(6),
                'created_at' => Carbon::now()->subHours(6),
            ],
        ];

        foreach ($notifications as $notification) {
            // Remove the 'id' field to allow auto-increment to work
            $notificationData = $notification;
            unset($notificationData['id']);
            
            Notification::firstOrCreate(
                ['type' => $notification['type']], // Use type as identifier for uniqueness
                $notificationData
            );
        }
    }
}