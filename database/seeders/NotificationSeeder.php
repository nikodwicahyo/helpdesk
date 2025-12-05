<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Ticket;
use App\Models\User;
use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Teknisi;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tickets = Ticket::all()->keyBy('ticket_number');
        $users = User::all();
        $adminHelpdesks = AdminHelpdesk::all();
        $adminAplikasis = AdminAplikasi::all();
        $teknisis = Teknisi::all();

        if ($tickets->isEmpty() || $users->isEmpty() || $adminHelpdesks->isEmpty() || $teknisis->isEmpty()) {
            $this->command->warn('Missing required data. Please run TicketSeeder, UserSeeder, AdminHelpdeskSeeder, and TeknisiSeeder first.');
            return;
        }

        $notifications = [
            [
                'type' => 'ticket_assigned',
                'notifiable_type' => 'App\Models\Teknisi',
                'notifiable_id' => $teknisis->where('name', 'Andi Wijaya, S.Kom.')->first()?->nip ?? $teknisis->first()->nip,
                'ticket_number' => 'TKT-20251004-0001',
                'triggered_by_nip' => $adminHelpdesks->first()?->nip,
                'triggered_by_type' => 'admin_helpdesk',
                'title' => 'Tiket Baru Ditugaskan',
                'message' => 'Tiket TKT-20251004-0001 telah ditugaskan kepada Anda. Masalah: Tidak bisa login ke SIKEP',
                'data' => json_encode([
                    'ticket_number' => 'TKT-20251004-0001',
                    'priority' => 'high',
                    'user_name' => 'Budi Santoso',
                ]),
                'priority' => 'high',
                'channel' => 'database',
                'status' => 'read',
                'is_read' => true,
                'sent_at' => Carbon::now()->subHours(3),
                'read_at' => Carbon::now()->subHours(2),
                'created_at' => Carbon::now()->subHours(3),
            ],
            [
                'type' => 'ticket_updated',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $users->where('name', 'Budi Santoso')->first()?->nip ?? $users->first()->nip,
                'ticket_number' => 'TKT-20251004-0001',
                'triggered_by_nip' => '199001012015011001',
                'triggered_by_type' => 'teknisi',
                'title' => 'Update Tiket',
                'message' => 'Teknisi telah memberikan komentar pada tiket Anda TKT-20251004-0001',
                'data' => json_encode([
                    'ticket_number' => 'TKT-20251004-0001',
                    'comment_preview' => 'Halo Bapak Budi Santoso, saya akan membantu...',
                ]),
                'priority' => 'medium',
                'channel' => 'database',
                'status' => 'read',
                'is_read' => true,
                'sent_at' => Carbon::now()->subHours(2),
                'read_at' => Carbon::now()->subHours(1),
                'created_at' => Carbon::now()->subHours(2),
            ],
            [
                'type' => 'ticket_resolved',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $users->where('name', 'Budi Santoso')->first()?->nip ?? $users->first()->nip,
                'ticket_number' => 'TKT-20251004-0001',
                'triggered_by_nip' => '199001012015011001',
                'triggered_by_type' => 'teknisi',
                'title' => 'Tiket Diselesaikan',
                'message' => 'Tiket TKT-20251004-0001 telah diselesaikan. Rating dan feedback diperlukan.',
                'data' => json_encode([
                    'ticket_number' => 'TKT-20251004-0001',
                    'resolution_summary' => 'Password berhasil direset melalui fitur lupa password',
                ]),
                'priority' => 'medium',
                'channel' => 'database',
                'status' => 'read',
                'is_read' => true,
                'sent_at' => Carbon::now()->subMinutes(30),
                'read_at' => Carbon::now()->subMinutes(20),
                'created_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'type' => 'ticket_comment',
                'notifiable_type' => 'App\Models\AdminHelpdesk',
                'notifiable_id' => $adminHelpdesks->first()?->nip,
                'ticket_number' => 'TKT-20251004-0002',
                'triggered_by_nip' => '199203202015013003',
                'triggered_by_type' => 'teknisi',
                'title' => 'Komentar Baru pada Tiket',
                'message' => 'Teknisi Citra Kirana memberikan komentar pada tiket TKT-20251004-0002',
                'data' => json_encode([
                    'ticket_number' => 'TKT-20251004-0002',
                    'comment_preview' => 'Selamat pagi Ibu Siti Nurhaliza...',
                ]),
                'priority' => 'medium',
                'channel' => 'database',
                'status' => 'unread',
                'is_read' => false,
                'sent_at' => Carbon::now()->subHours(1),
                'created_at' => Carbon::now()->subHours(1),
            ],
            [
                'type' => 'urgent_ticket',
                'notifiable_type' => 'App\Models\AdminHelpdesk',
                'notifiable_id' => $adminHelpdesks->first()?->nip,
                'ticket_number' => 'TKT-20251004-0003',
                'triggered_by_nip' => '199102152015012002',
                'triggered_by_type' => 'teknisi',
                'title' => 'Tiket URGENT Perlu Perhatian',
                'message' => 'Tiket TKT-20251004-0003 dengan klasifikasi RAHASIA memerlukan penanganan khusus',
                'data' => json_encode([
                    'ticket_number' => 'TKT-20251004-0003',
                    'priority' => 'urgent',
                    'security_level' => 'RAHASIA',
                ]),
                'priority' => 'urgent',
                'channel' => 'database',
                'status' => 'unread',
                'is_read' => false,
                'sent_at' => Carbon::now()->subMinutes(30),
                'created_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'type' => 'system_maintenance',
                'notifiable_type' => 'App\Models\AdminAplikasi',
                'notifiable_id' => $adminAplikasis->first()?->nip ?? '197501012000011001',
                'ticket_number' => 'TKT-20251007-0001',
                'triggered_by_nip' => 'system',
                'triggered_by_type' => 'system',
                'title' => 'Website Down - Kemungkinan Serangan',
                'message' => 'Portal kemlu.go.id tidak dapat diakses. Kemungkinan serangan DDoS terdeteksi.',
                'data' => json_encode([
                    'affected_system' => 'kemlu.go.id',
                    'incident_type' => 'possible_ddos',
                    'severity' => 'high',
                ]),
                'priority' => 'urgent',
                'channel' => 'database',
                'status' => 'unread',
                'is_read' => false,
                'sent_at' => Carbon::now()->subMinutes(15),
                'created_at' => Carbon::now()->subMinutes(15),
            ],
            [
                'type' => 'ticket_escalated',
                'notifiable_type' => 'App\Models\AdminAplikasi',
                'notifiable_id' => $adminAplikasis->skip(2)->first()?->nip ?? $adminAplikasis->first()?->nip ?? '197703202000013003',
                'ticket_number' => 'TKT-20251004-0002',
                'triggered_by_nip' => '198001012005011001',
                'triggered_by_type' => 'admin_helpdesk',
                'title' => 'Tiket Eskalasi',
                'message' => 'Tiket TKT-20251004-0002 mengalami kendala workflow yang memerlukan intervensi admin aplikasi',
                'data' => json_encode([
                    'ticket_number' => 'TKT-20251004-0002',
                    'escalation_reason' => 'Workflow approval stuck memerlukan admin intervention',
                    'application' => 'E-OFFICE',
                ]),
                'priority' => 'high',
                'channel' => 'database',
                'status' => 'unread',
                'is_read' => false,
                'sent_at' => Carbon::now()->subMinutes(45),
                'created_at' => Carbon::now()->subMinutes(45),
            ],
            [
                'type' => 'ticket_response_required',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $users->where('name', 'Rizki Ramadhan')->first()?->nip ?? $users->skip(4)->first()->nip,
                'ticket_number' => 'TKT-20251007-0002',
                'triggered_by_nip' => '199707202015017007',
                'triggered_by_type' => 'teknisi',
                'title' => 'Respons Diperlukan',
                'message' => 'Teknisi memerlukan konfirmasi Anda untuk tiket TKT-20251007-0002 mengenai koreksi anggaran',
                'data' => json_encode([
                    'ticket_number' => 'TKT-20251007-0002',
                    'question' => 'Apakah kesalahan input perlu dikoreksi?',
                ]),
                'priority' => 'medium',
                'channel' => 'database',
                'status' => 'unread',
                'is_read' => false,
                'sent_at' => Carbon::now()->subHours(3),
                'created_at' => Carbon::now()->subHours(3),
            ],
            [
                'type' => 'ticket_assigned',
                'notifiable_type' => 'App\Models\Teknisi',
                'notifiable_id' => $teknisis->where('name', 'Citra Kirana, S.Kom.')->first()?->nip ?? $teknisis->skip(1)->first()->nip,
                'ticket_number' => 'TKT-20251004-0002',
                'triggered_by_nip' => $adminHelpdesks->first()?->nip,
                'triggered_by_type' => 'admin_helpdesk',
                'title' => 'Tiket Baru Ditugaskan',
                'message' => 'Tiket TKT-20251004-0002 telah ditugaskan kepada Anda. Masalah: Workflow approval stuck di E-Office',
                'data' => json_encode([
                    'ticket_number' => 'TKT-20251004-0002',
                    'priority' => 'medium',
                    'application' => 'E-OFFICE',
                ]),
                'priority' => 'medium',
                'channel' => 'database',
                'status' => 'read',
                'is_read' => true,
                'sent_at' => Carbon::now()->subHours(6),
                'read_at' => Carbon::now()->subHours(5),
                'created_at' => Carbon::now()->subHours(6),
            ],
            [
                'type' => 'ticket_comment',
                'notifiable_type' => 'App\Models\Teknisi',
                'notifiable_id' => $teknisis->where('name', 'Andi Wijaya, S.Kom.')->first()?->nip ?? $teknisis->first()->nip,
                'ticket_number' => 'TKT-20251004-0001',
                'triggered_by_nip' => '198501012010011001',
                'triggered_by_type' => 'user',
                'title' => 'Komentar Baru dari User',
                'message' => 'User Budi Santoso memberikan komentar pada tiket TKT-20251004-0001',
                'data' => json_encode([
                    'ticket_number' => 'TKT-20251004-0001',
                    'comment_preview' => 'Terima kasih atas respons cepatnya...',
                ]),
                'priority' => 'low',
                'channel' => 'database',
                'status' => 'read',
                'is_read' => true,
                'sent_at' => Carbon::now()->subHours(1),
                'read_at' => Carbon::now()->subMinutes(50),
                'created_at' => Carbon::now()->subHours(1),
            ],
            [
                'type' => 'daily_report',
                'notifiable_type' => 'App\Models\AdminHelpdesk',
                'notifiable_id' => $adminHelpdesks->first()?->nip,
                'ticket_number' => null,
                'triggered_by_nip' => 'system',
                'triggered_by_type' => 'system',
                'title' => 'Laporan Harian Helpdesk',
                'message' => 'Laporan harian helpdesk untuk tanggal ' . Carbon::now()->format('d M Y') . ' telah tersedia',
                'data' => json_encode([
                    'report_date' => Carbon::now()->format('Y-m-d'),
                    'total_tickets' => 10,
                    'resolved_tickets' => 2,
                    'pending_tickets' => 8,
                ]),
                'priority' => 'low',
                'channel' => 'database',
                'status' => 'unread',
                'is_read' => false,
                'sent_at' => Carbon::now()->subHours(1),
                'created_at' => Carbon::now()->subHours(1),
            ],
            [
                'type' => 'system_backup',
                'notifiable_type' => 'App\Models\AdminAplikasi',
                'notifiable_id' => $adminAplikasis->first()?->nip ?? '197501012000011001',
                'ticket_number' => null,
                'triggered_by_nip' => 'system',
                'triggered_by_type' => 'system',
                'title' => 'Backup System Selesai',
                'message' => 'Backup rutin sistem telah selesai dijalankan pada pukul 02:00 WIB',
                'data' => json_encode([
                    'backup_time' => '02:00 WIB',
                    'backup_size' => '2.5 GB',
                    'status' => 'success',
                ]),
                'priority' => 'low',
                'channel' => 'database',
                'status' => 'unread',
                'is_read' => false,
                'sent_at' => Carbon::now()->subHours(6),
                'created_at' => Carbon::now()->subHours(6),
            ],
        ];

        foreach ($notifications as $notificationData) {
            $ticketNumber = $notificationData['ticket_number'] ?? null;
            unset($notificationData['ticket_number']);

            if ($ticketNumber) {
                $ticket = $tickets->get($ticketNumber);
                $notificationData['ticket_id'] = $ticket?->id;
            } else {
                $notificationData['ticket_id'] = null;
            }

            Notification::create($notificationData);
        }
    }
}
