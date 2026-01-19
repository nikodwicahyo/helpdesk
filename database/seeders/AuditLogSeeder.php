<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\AdminHelpdesk;
use App\Models\Teknisi;
use App\Models\Ticket;
use Carbon\Carbon;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder creates realistic audit logs based on actual data from:
     * - UserSeeder
     * - AdminHelpdeskSeeder
     * - TeknisiSeeder
     * - TicketSeeder
     */
    public function run(): void
    {
        $this->command->info('Creating audit logs based on real data...');

        // Get real data from database
        $users = User::all();
        $adminHelpdesks = AdminHelpdesk::all();
        $teknisis = Teknisi::all();
        $tickets = Ticket::all();

        if ($users->isEmpty() || $adminHelpdesks->isEmpty() || $teknisis->isEmpty() || $tickets->isEmpty()) {
            $this->command->warn('Missing required data. Please run other seeders first.');
            return;
        }

        $auditLogs = [];

        // 1. Create login logs for all users (spread over last 60 days)
        // IMPORTANT: Always ensure timestamps are in the past (minimum 2 days ago)
        // This ensures real user activity always appears first in Activity Log
        $this->command->info('  → Creating login/logout logs...');
        foreach ($users as $user) {
            // Only 1-2 logins per user (reduced from 3-5)
            for ($i = 0; $i < rand(1, 2); $i++) {
                // Ensure timestamps are always in the past (2-60 days ago)
                $loginTime = Carbon::now()->subDays(rand(2, 60))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
                
                $auditLogs[] = [
                    'action' => 'login',
                    'entity_type' => null,
                    'entity_id' => null,
                    'actor_type' => 'User',
                    'actor_id' => $user->nip,
                    'actor_name' => $user->name,
                    'description' => "{$user->name} logged in to the system",
                    'metadata' => json_encode(['login_time' => $loginTime->toDateTimeString()]),
                    'ip_address' => '192.168.1.' . rand(1, 255),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'route_name' => 'login',
                    'http_method' => 'POST',
                    'created_at' => $loginTime,
                ];

                // Some logout logs (50% of logins have logout - reduced from 70%)
                if (rand(1, 10) <= 5) {
                    $logoutTime = $loginTime->copy()->addHours(rand(2, 8));
                    $auditLogs[] = [
                        'action' => 'logout',
                        'entity_type' => null,
                        'entity_id' => null,
                        'actor_type' => 'User',
                        'actor_id' => $user->nip,
                        'actor_name' => $user->name,
                        'description' => "{$user->name} logged out from the system",
                        'metadata' => json_encode(['logout_time' => $logoutTime->toDateTimeString()]),
                        'ip_address' => '192.168.1.' . rand(1, 255),
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'route_name' => 'logout',
                        'http_method' => 'POST',
                        'created_at' => $logoutTime,
                    ];
                }
            }
        }

        // Admin login logs (reduced from 5-10 to 2-3)
        foreach ($adminHelpdesks as $admin) {
            for ($i = 0; $i < rand(2, 3); $i++) {
                // Ensure timestamps are always in the past (2-60 days ago)
                $loginTime = Carbon::now()->subDays(rand(2, 60))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
                
                $auditLogs[] = [
                    'action' => 'login',
                    'entity_type' => null,
                    'entity_id' => null,
                    'actor_type' => 'AdminHelpdesk',
                    'actor_id' => $admin->nip,
                    'actor_name' => $admin->name,
                    'description' => "{$admin->name} logged in to the system",
                    'metadata' => json_encode(['login_time' => $loginTime->toDateTimeString(), 'role' => 'admin']),
                    'ip_address' => '192.168.1.' . rand(1, 255),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'route_name' => 'login',
                    'http_method' => 'POST',
                    'created_at' => $loginTime,
                ];
            }
        }

        // Teknisi login logs (reduced from 5-10 to 2-3)
        foreach ($teknisis as $teknisi) {
            for ($i = 0; $i < rand(2, 3); $i++) {
                // Ensure timestamps are always in the past (2-60 days ago)
                $loginTime = Carbon::now()->subDays(rand(2, 60))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
                
                $auditLogs[] = [
                    'action' => 'login',
                    'entity_type' => null,
                    'entity_id' => null,
                    'actor_type' => 'Teknisi',
                    'actor_id' => $teknisi->nip,
                    'actor_name' => $teknisi->name,
                    'description' => "{$teknisi->name} logged in to the system",
                    'metadata' => json_encode(['login_time' => $loginTime->toDateTimeString(), 'role' => 'teknisi']),
                    'ip_address' => '192.168.1.' . rand(1, 255),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'route_name' => 'login',
                    'http_method' => 'POST',
                    'created_at' => $loginTime,
                ];
            }
        }

        // 2. Create ticket lifecycle audit logs
        $this->command->info('  → Creating ticket lifecycle logs...');
        foreach ($tickets as $ticket) {
            $user = $users->where('nip', $ticket->user_nip)->first();
            if (!$user) continue;

            // 2a. Ticket created by user
            $auditLogs[] = [
                'action' => 'created',
                'entity_type' => 'Ticket',
                'entity_id' => $ticket->id,
                'actor_type' => 'User',
                'actor_id' => $user->nip,
                'actor_name' => $user->name,
                'description' => "Created ticket #{$ticket->ticket_number}",
                'metadata' => json_encode([
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'priority' => $ticket->priority,
                    'status' => 'open',
                ]),
                'ip_address' => '192.168.1.' . rand(1, 255),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'route_name' => 'user.tickets.store',
                'http_method' => 'POST',
                'created_at' => $ticket->created_at,
            ];

            // 2b. If ticket is assigned, log the assignment
            if ($ticket->assigned_teknisi_nip) {
                $assignedTeknisi = $teknisis->where('nip', $ticket->assigned_teknisi_nip)->first();
                $assignedByAdmin = $adminHelpdesks->where('nip', $ticket->assigned_by_nip)->first();

                if ($assignedTeknisi && $assignedByAdmin) {
                    // Calculate assignedAt ensuring it's not in the future
                    // Ticket created_at is in the past (e.g. 1-8 hours ago)
                    // We add 15-60 mins, but clamp to now()
                    $assignedAt = $ticket->created_at->copy()->addMinutes(rand(15, 60));
                    if ($assignedAt->gt(Carbon::now())) {
                        $assignedAt = Carbon::now()->subMinutes(rand(5, 30));
                        // Ensure it's still after created_at
                        if ($assignedAt->lte($ticket->created_at)) {
                            $assignedAt = $ticket->created_at->copy()->addMinutes(1);
                        }
                    }
                    
                    $auditLogs[] = [
                        'action' => 'assigned',
                        'entity_type' => 'Ticket',
                        'entity_id' => $ticket->id,
                        'actor_type' => 'AdminHelpdesk',
                        'actor_id' => $assignedByAdmin->nip,
                        'actor_name' => $assignedByAdmin->name,
                        'description' => "Assigned ticket #{$ticket->ticket_number} to {$assignedTeknisi->name}",
                        'metadata' => json_encode([
                            'ticket_number' => $ticket->ticket_number,
                            'assigned_to' => $assignedTeknisi->name,
                            'assigned_to_nip' => $assignedTeknisi->nip,
                            'old_status' => 'open',
                            'new_status' => 'assigned',
                        ]),
                        'ip_address' => '192.168.1.' . rand(1, 255),
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'route_name' => 'admin.tickets.assign',
                        'http_method' => 'POST',
                        'created_at' => $assignedAt,
                    ];

                    // 2c. Teknisi comments/updates (1-3 comments)
                    for ($i = 0; $i < rand(1, 3); $i++) {
                        // Calculate commentAt relative to assignedAt, ensuring not in future
                        $commentAt = $assignedAt->copy()->addMinutes(rand(30, 180));
                        if ($commentAt->gt(Carbon::now())) {
                            $commentAt = Carbon::now()->subMinutes(rand(1, 10));
                            // Ensure after assignedAt
                            if ($commentAt->lte($assignedAt)) {
                                $commentAt = $assignedAt->copy()->addMinutes(rand(1, 5));
                            }
                        }
                        
                        // Double check we didn't push it into future with the fix
                        if ($commentAt->gt(Carbon::now())) {
                           $commentAt = Carbon::now()->subSeconds(rand(10, 59));
                        }

                        $auditLogs[] = [
                            'action' => 'commented',
                            'entity_type' => 'Ticket',
                            'entity_id' => $ticket->id,
                            'actor_type' => 'Teknisi',
                            'actor_id' => $assignedTeknisi->nip,
                            'actor_name' => $assignedTeknisi->name,
                            'description' => "Added comment to ticket #{$ticket->ticket_number}",
                            'metadata' => json_encode([
                                'ticket_number' => $ticket->ticket_number,
                                'comment_type' => $i === 0 ? 'initial_response' : 'follow_up',
                            ]),
                            'ip_address' => '192.168.1.' . rand(1, 255),
                            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                            'route_name' => 'teknisi.tickets.comment',
                            'http_method' => 'POST',
                            'created_at' => $commentAt,
                        ];
                    }

                    // 2d. If ticket is resolved
                    if ($ticket->status === 'resolved' && $ticket->resolved_at) {
                        $auditLogs[] = [
                            'action' => 'resolved',
                            'entity_type' => 'Ticket',
                            'entity_id' => $ticket->id,
                            'actor_type' => 'Teknisi',
                            'actor_id' => $assignedTeknisi->nip,
                            'actor_name' => $assignedTeknisi->name,
                            'description' => "Resolved ticket #{$ticket->ticket_number}",
                            'metadata' => json_encode([
                                'ticket_number' => $ticket->ticket_number,
                                'resolution_time_minutes' => $ticket->resolution_time_minutes,
                                'resolution_notes' => $ticket->resolution_notes,
                            ]),
                            'ip_address' => '192.168.1.' . rand(1, 255),
                            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                            'route_name' => 'teknisi.tickets.resolve',
                            'http_method' => 'PUT',
                            'created_at' => $ticket->resolved_at,
                        ];

                        // 2e. If ticket is closed
                        if ($ticket->closed_at) {
                            $auditLogs[] = [
                                'action' => 'closed',
                                'entity_type' => 'Ticket',
                                'entity_id' => $ticket->id,
                                'actor_type' => 'User',
                                'actor_id' => $user->nip,
                                'actor_name' => $user->name,
                                'description' => "Closed ticket #{$ticket->ticket_number}",
                                'metadata' => json_encode([
                                    'ticket_number' => $ticket->ticket_number,
                                    'user_rating' => $ticket->user_rating,
                                    'user_feedback' => $ticket->user_feedback,
                                ]),
                                'ip_address' => '192.168.1.' . rand(1, 255),
                                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                                'route_name' => 'user.tickets.close',
                                'http_method' => 'PUT',
                                'created_at' => $ticket->closed_at,
                            ];
                        }
                    }
                }
            }
        }

        // 3. Create user creation logs (simulate that admins created these users in the past)
        $this->command->info('  → Creating user management logs...');
        $firstAdmin = $adminHelpdesks->first();
        if ($firstAdmin) {
            foreach ($users->take(3) as $user) {
                $createdTime = $user->created_at ?? Carbon::now()->subDays(rand(30, 60));
                
                $auditLogs[] = [
                    'action' => 'created',
                    'entity_type' => 'User',
                    'entity_id' => $user->nip,
                    'actor_type' => 'AdminHelpdesk',
                    'actor_id' => $firstAdmin->nip,
                    'actor_name' => $firstAdmin->name,
                    'description' => "Created User: {$user->name}",
                    'metadata' => json_encode([
                        'name' => $user->name,
                        'email' => $user->email,
                        'nip' => $user->nip,
                        'department' => $user->department,
                    ]),
                    'ip_address' => '192.168.1.' . rand(1, 255),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'route_name' => 'admin.users.store',
                    'http_method' => 'POST',
                    'created_at' => $createdTime,
                ];
            }
        }

        // 4. Sort all logs by created_at to maintain chronological order
        usort($auditLogs, function($a, $b) {
            return $a['created_at'] <=> $b['created_at'];
        });

        // 5. Batch insert for performance
        $this->command->info('  → Inserting audit logs into database...');
        $chunks = array_chunk($auditLogs, 100);
        foreach ($chunks as $chunk) {
            AuditLog::insert($chunk);
        }

        $totalLogs = count($auditLogs);
        $this->command->info("✓ Created {$totalLogs} audit log entries (reduced for better performance)!");
        $this->command->info('  • Login/Logout logs: ' . collect($auditLogs)->whereIn('action', ['login', 'logout'])->count());
        $this->command->info('  • Ticket lifecycle logs: ' . collect($auditLogs)->whereIn('action', ['created', 'assigned', 'commented', 'resolved', 'closed'])->count());
        $this->command->info('  • User management logs: ' . collect($auditLogs)->where('action', 'created')->where('entity_type', 'User')->count());
        $this->command->info('  ℹ All timestamps are 2-60 days in the past to ensure real user activity appears first');
    }
}
