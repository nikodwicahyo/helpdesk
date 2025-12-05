<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\SystemSetting;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCloseTickets extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:auto-close-tickets
                            {--dry-run : Show what would be closed without actually closing}';

    /**
     * The console command description.
     */
    protected $description = 'Automatically close resolved tickets after the configured number of days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $autoCloseDays = (int) SystemSetting::get('auto_close_days', 7);

        $this->info("Auto-closing tickets resolved more than {$autoCloseDays} days ago...");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No tickets will be closed');
        }

        try {
            $cutoffDate = now()->subDays($autoCloseDays);

            // Find tickets that are resolved and haven't been updated in X days
            $ticketsToClose = Ticket::where('status', Ticket::STATUS_RESOLVED)
                ->where('resolved_at', '<=', $cutoffDate)
                ->get();

            if ($ticketsToClose->isEmpty()) {
                $this->info('No tickets found to auto-close.');
                return self::SUCCESS;
            }

            if ($dryRun) {
                $this->info("Would close {$ticketsToClose->count()} ticket(s):");
                $this->table(
                    ['Ticket #', 'Title', 'Resolved At', 'User NIP'],
                    $ticketsToClose->map(fn($t) => [
                        $t->ticket_number,
                        \Illuminate\Support\Str::limit($t->title, 40),
                        $t->resolved_at?->format('Y-m-d H:i:s'),
                        $t->user_nip,
                    ])->toArray()
                );
                return self::SUCCESS;
            }

            $closedCount = 0;
            $notificationService = app(NotificationService::class);

            DB::beginTransaction();

            foreach ($ticketsToClose as $ticket) {
                try {
                    $oldStatus = $ticket->status;

                    // Update ticket status
                    $ticket->update([
                        'status' => Ticket::STATUS_CLOSED,
                        'closed_at' => now(),
                    ]);

                    // Record history
                    TicketHistory::create([
                        'ticket_id' => $ticket->id,
                        'actor_nip' => 'system',
                        'actor_type' => 'system',
                        'action' => 'status_changed',
                        'old_value' => $oldStatus,
                        'new_value' => Ticket::STATUS_CLOSED,
                        'notes' => "Auto-closed after {$autoCloseDays} days in resolved status",
                    ]);

                    // Send notification to user
                    try {
                        $notificationService->notifyStatusChanged($ticket, 'resolved', 'closed');
                    } catch (\Exception $e) {
                        Log::warning('Failed to send auto-close notification', [
                            'ticket_id' => $ticket->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $closedCount++;
                    $this->line("Closed: {$ticket->ticket_number}");

                } catch (\Exception $e) {
                    $this->warn("Failed to close {$ticket->ticket_number}: " . $e->getMessage());
                    Log::warning('Failed to auto-close ticket', [
                        'ticket_id' => $ticket->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            $this->info("Auto-close completed. Closed {$closedCount} ticket(s).");

            Log::info('Auto-close tickets completed', [
                'auto_close_days' => $autoCloseDays,
                'closed_count' => $closedCount,
                'total_found' => $ticketsToClose->count(),
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Auto-close failed: " . $e->getMessage());

            Log::error('Auto-close tickets failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
