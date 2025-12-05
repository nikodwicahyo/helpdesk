<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use Illuminate\Support\Facades\Log;

class CleanupOldBackups extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:cleanup-old-backups
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old backups based on retention period from system settings';

    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $settings = $this->backupService->getBackupSettings();
        $retentionDays = $settings['retention_days'];

        $this->info("Cleaning up backups older than {$retentionDays} days...");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No files will be deleted');
        }

        try {
            if ($dryRun) {
                // Just show what would be deleted
                $cutoffDate = now()->subDays($retentionDays);
                $oldBackups = \App\Models\Backup::where('created_at', '<', $cutoffDate)
                    ->completed()
                    ->get();

                if ($oldBackups->isEmpty()) {
                    $this->info('No old backups found to delete.');
                    return self::SUCCESS;
                }

                $this->info("Would delete {$oldBackups->count()} backup(s):");
                $this->table(
                    ['ID', 'Filename', 'Created At', 'Size'],
                    $oldBackups->map(fn($b) => [
                        $b->id,
                        $b->filename,
                        $b->created_at->format('Y-m-d H:i:s'),
                        $b->size_formatted,
                    ])->toArray()
                );

                return self::SUCCESS;
            }

            $deletedCount = $this->backupService->cleanupOldBackups();

            $this->info("Cleanup completed. Deleted {$deletedCount} old backup(s).");

            Log::info('Old backups cleanup completed', [
                'retention_days' => $retentionDays,
                'deleted_count' => $deletedCount,
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Cleanup failed: " . $e->getMessage());

            Log::error('Old backups cleanup failed', [
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
