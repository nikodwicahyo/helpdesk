<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:backup-database 
                            {type=manual : The type of backup (manual, daily, weekly, monthly)}
                            {--force : Force backup even if auto-backup is disabled}';

    /**
     * The console command description.
     */
    protected $description = 'Create a database backup of the helpdesk system';

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
        $type = $this->argument('type');
        $force = $this->option('force');

        // Check if auto-backup is enabled for scheduled backups
        if (!$force && $type !== 'manual') {
            $autoBackup = SystemSetting::get('auto_backup', 'disabled');
            
            if ($autoBackup === 'disabled') {
                $this->info('Auto-backup is disabled. Use --force to override.');
                return self::SUCCESS;
            }

            if ($autoBackup !== $type) {
                $this->info("Auto-backup is set to '{$autoBackup}', not '{$type}'. Skipping.");
                return self::SUCCESS;
            }
        }

        $this->info("Starting {$type} backup...");

        try {
            $backup = $this->backupService->createBackup(
                type: $type,
                createdByNip: null,
                createdByType: 'system'
            );

            $this->info("Backup created successfully!");
            $this->table(
                ['Property', 'Value'],
                [
                    ['ID', $backup->id],
                    ['Filename', $backup->filename],
                    ['Size', $backup->size_formatted],
                    ['Status', $backup->status],
                    ['Location', $backup->location],
                ]
            );

            Log::info('Scheduled backup completed', [
                'type' => $type,
                'backup_id' => $backup->id,
                'filename' => $backup->filename,
                'size' => $backup->size,
            ]);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());

            Log::error('Scheduled backup failed', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
