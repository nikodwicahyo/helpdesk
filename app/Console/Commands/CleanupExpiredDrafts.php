<?php

namespace App\Console\Commands;

use App\Models\TicketDraft;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupExpiredDrafts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drafts:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired ticket drafts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting expired draft cleanup...');

        try {
            // Log start of cleanup process
            Log::info('Draft cleanup process started');

            // Get expired drafts
            $expiredDrafts = TicketDraft::where('expires_at', '<', now())->get();
            $expiredCount = $expiredDrafts->count();

            if ($expiredCount === 0) {
                $this->info('No expired drafts found.');
                Log::info('No expired drafts found during cleanup');
                return Command::SUCCESS;
            }

            $this->info("Found {$expiredCount} expired drafts to delete.");
            Log::info("Found {$expiredCount} expired drafts to delete");

            // Delete expired drafts
            $deletedCount = 0;
            foreach ($expiredDrafts as $draft) {
                try {
                    $draft->delete();
                    $deletedCount++;
                    $this->line("Deleted draft ID: {$draft->id} for user: {$draft->user_nip}");
                } catch (\Exception $e) {
                    $this->error("Failed to delete draft ID: {$draft->id}. Error: " . $e->getMessage());
                    Log::error("Failed to delete draft ID: {$draft->id}", [
                        'error' => $e->getMessage(),
                        'draft_id' => $draft->id,
                        'user_nip' => $draft->user_nip
                    ]);
                }
            }

            $this->info("Successfully deleted {$deletedCount} expired drafts.");
            Log::info("Draft cleanup completed successfully", [
                'total_found' => $expiredCount,
                'total_deleted' => $deletedCount
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Draft cleanup failed: ' . $e->getMessage());
            Log::error('Draft cleanup process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}