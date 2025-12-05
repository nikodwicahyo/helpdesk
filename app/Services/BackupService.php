<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class BackupService
{
    protected string $backupPath = 'backups';

    /**
     * Create a new backup.
     */
    public function createBackup(
        string $type = 'manual',
        ?string $createdByNip = null,
        ?string $createdByType = null
    ): Backup {
        $settings = $this->getBackupSettings();
        $filename = $this->generateFilename($type);
        $location = $settings['location'];
        $disk = $this->getDiskForLocation($location);

        // Create backup record
        $backup = Backup::create([
            'filename' => $filename,
            'type' => $type,
            'status' => Backup::STATUS_PENDING,
            'location' => $location,
            'disk' => $disk,
            'include_files' => $settings['include_files'],
            'created_by_nip' => $createdByNip,
            'created_by_type' => $createdByType,
        ]);

        try {
            $backup->markAsInProgress();

            // Create the backup file
            $backupData = $this->performBackup($backup, $settings);

            // Update backup record with results
            $backup->markAsCompleted($backupData['size'], $backupData['path']);

            Log::info('Backup created successfully', [
                'backup_id' => $backup->id,
                'filename' => $filename,
                'type' => $type,
                'size' => $backupData['size'],
                'location' => $location,
            ]);

        } catch (\Exception $e) {
            $backup->markAsFailed($e->getMessage());

            Log::error('Backup creation failed', [
                'backup_id' => $backup->id,
                'filename' => $filename,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return $backup;
    }

    /**
     * Perform the actual backup operation.
     */
    protected function performBackup(Backup $backup, array $settings): array
    {
        $disk = Storage::disk($backup->disk);
        $backupDir = $this->backupPath . '/' . date('Y/m');

        // Ensure backup directory exists
        if (!$disk->exists($backupDir)) {
            $disk->makeDirectory($backupDir);
        }

        $fullPath = $backupDir . '/' . $backup->filename;

        // Create database dump
        $databaseBackup = $this->createDatabaseDump();

        // Create the backup package
        $backupContent = $this->createBackupPackage($databaseBackup, $settings['include_files']);

        // Store the backup
        $disk->put($fullPath, $backupContent);

        // Get file size
        $size = $disk->size($fullPath);

        // Cleanup temp files
        $this->cleanupTempFiles($databaseBackup);

        return [
            'path' => $fullPath,
            'size' => $size,
        ];
    }

    /**
     * Create database dump.
     */
    protected function createDatabaseDump(): string
    {
        $connection = Config::get('database.default');
        $config = Config::get("database.connections.{$connection}");

        $tempFile = storage_path('app/temp/db_backup_' . time() . '.sql');
        $tempDir = dirname($tempFile);

        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s 2>&1',
            escapeshellarg($config['host']),
            escapeshellarg($config['port'] ?? 3306),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['database']),
            escapeshellarg($tempFile)
        );

        // Execute the command
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            // If mysqldump fails, create a PHP-based backup
            $this->createPhpDatabaseBackup($tempFile, $config);
        }

        return $tempFile;
    }

    /**
     * Create PHP-based database backup (fallback).
     */
    protected function createPhpDatabaseBackup(string $filePath, array $config): void
    {
        $tables = DB::select('SHOW TABLES');
        $databaseKey = 'Tables_in_' . $config['database'];

        $sqlContent = "-- HelpDesk Kemlu Database Backup\n";
        $sqlContent .= "-- Generated: " . now()->toDateTimeString() . "\n";
        $sqlContent .= "-- Database: {$config['database']}\n\n";
        $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$databaseKey;

            // Get create table statement
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $sqlContent .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            
            // Handle different MySQL result formats
            $createTableData = (array) $createTable[0];
            $createTableSql = $createTableData['Create Table'] ?? $createTableData['create table'] ?? null;
            
            if ($createTableSql) {
                $sqlContent .= $createTableSql . ";\n\n";
            } else {
                // Fallback: get the second element (Create Table statement is always second)
                $values = array_values($createTableData);
                if (isset($values[1])) {
                    $sqlContent .= $values[1] . ";\n\n";
                }
            }

            // Get table data
            $rows = DB::table($tableName)->get();
            if ($rows->count() > 0) {
                foreach ($rows as $row) {
                    $values = [];
                    foreach ((array) $row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } elseif (is_numeric($value)) {
                            $values[] = $value;
                        } else {
                            $values[] = "'" . addslashes((string) $value) . "'";
                        }
                    }
                    $sqlContent .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
                }
            }
            $sqlContent .= "\n";
        }

        $sqlContent .= "SET FOREIGN_KEY_CHECKS=1;\n";

        file_put_contents($filePath, $sqlContent);
    }

    /**
     * Create backup package (ZIP file).
     */
    protected function createBackupPackage(string $databaseBackupPath, bool $includeFiles): string
    {
        $zipPath = storage_path('app/temp/backup_' . time() . '.zip');

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            throw new \Exception('Cannot create backup ZIP file');
        }

        // Add database dump
        $zip->addFile($databaseBackupPath, 'database.sql');

        // Add uploaded files if requested
        if ($includeFiles) {
            $uploadPath = storage_path('app/public/uploads');
            if (file_exists($uploadPath)) {
                $this->addDirectoryToZip($zip, $uploadPath, 'uploads');
            }
        }

        // Add backup metadata
        $metadata = [
            'created_at' => now()->toISOString(),
            'app_name' => config('app.name'),
            'app_version' => '1.0.0',
            'include_files' => $includeFiles,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
        $zip->addFromString('metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));

        $zip->close();

        // Read and return content
        $content = file_get_contents($zipPath);
        unlink($zipPath);

        return $content;
    }

    /**
     * Add directory to ZIP archive recursively.
     */
    protected function addDirectoryToZip(\ZipArchive $zip, string $path, string $relativePath): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $zipFilePath = $relativePath . '/' . substr($filePath, strlen($path) + 1);
                $zip->addFile($filePath, $zipFilePath);
            }
        }
    }

    /**
     * Cleanup temporary files.
     */
    protected function cleanupTempFiles(string ...$files): void
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Generate backup filename.
     */
    protected function generateFilename(string $type): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $appName = preg_replace('/[^a-zA-Z0-9]/', '_', config('app.name'));

        return "{$appName}_backup_{$type}_{$timestamp}.zip";
    }

    /**
     * Get disk for storage location.
     */
    protected function getDiskForLocation(string $location): string
    {
        return match ($location) {
            's3' => 's3',
            'google_drive' => 'google',
            default => 'local',
        };
    }

    /**
     * Get backup settings from system settings.
     */
    public function getBackupSettings(): array
    {
        return [
            'auto_backup' => SystemSetting::get('auto_backup', 'daily'),
            'retention_days' => SystemSetting::get('retention_days', 30),
            'location' => SystemSetting::get('backup_location', 'local'),
            'include_files' => SystemSetting::get('backup_include_files', true),
            'backup_time' => SystemSetting::get('backup_time', '02:00'),
            'compress_backups' => SystemSetting::get('compress_backups', true),
        ];
    }

    /**
     * Delete a backup.
     */
    public function deleteBackup(Backup $backup): bool
    {
        try {
            // Delete file from storage
            if ($backup->path) {
                $disk = Storage::disk($backup->disk);
                if ($disk->exists($backup->path)) {
                    $disk->delete($backup->path);
                }
            }

            // Delete database record
            $backup->delete();

            Log::info('Backup deleted', [
                'backup_id' => $backup->id,
                'filename' => $backup->filename,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete backup', [
                'backup_id' => $backup->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get backup file path for download.
     */
    public function getBackupPath(Backup $backup): ?string
    {
        if (!$backup->path || !$backup->isCompleted()) {
            return null;
        }

        $disk = Storage::disk($backup->disk);

        if (!$disk->exists($backup->path)) {
            return null;
        }

        return $disk->path($backup->path);
    }

    /**
     * Get backup file stream for download.
     */
    public function getBackupStream(Backup $backup)
    {
        if (!$backup->path || !$backup->isCompleted()) {
            return null;
        }

        $disk = Storage::disk($backup->disk);

        if (!$disk->exists($backup->path)) {
            return null;
        }

        return $disk->readStream($backup->path);
    }

    /**
     * Cleanup old backups based on retention period.
     */
    public function cleanupOldBackups(): int
    {
        $settings = $this->getBackupSettings();
        $retentionDays = $settings['retention_days'];
        $cutoffDate = now()->subDays($retentionDays);

        $oldBackups = Backup::where('created_at', '<', $cutoffDate)
            ->completed()
            ->get();

        $deletedCount = 0;

        foreach ($oldBackups as $backup) {
            if ($this->deleteBackup($backup)) {
                $deletedCount++;
            }
        }

        Log::info('Old backups cleanup completed', [
            'retention_days' => $retentionDays,
            'deleted_count' => $deletedCount,
        ]);

        return $deletedCount;
    }

    /**
     * Get the latest completed backup.
     */
    public function getLatestBackup(): ?Backup
    {
        return Backup::completed()
            ->orderBy('completed_at', 'desc')
            ->first();
    }

    /**
     * Get backup history with optional filters.
     */
    public function getBackupHistory(array $filters = [], int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $query = Backup::orderBy('created_at', 'desc');

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['location'])) {
            $query->byLocation($filters['location']);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Check if automatic backup is enabled for the given frequency.
     */
    public function isAutoBackupEnabled(string $frequency): bool
    {
        $autoBackup = SystemSetting::get('auto_backup', 'disabled');
        return $autoBackup === $frequency;
    }

    /**
     * Get backup statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_backups' => Backup::count(),
            'completed_backups' => Backup::completed()->count(),
            'failed_backups' => Backup::failed()->count(),
            'total_size' => Backup::completed()->sum('size'),
            'latest_backup' => $this->getLatestBackup(),
            'backups_today' => Backup::whereDate('created_at', today())->count(),
            'backups_this_week' => Backup::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];
    }
}
