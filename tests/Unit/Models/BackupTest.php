<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\Backup;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BackupTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_backup_record()
    {
        $backup = Backup::create([
            'backup_type' => 'database',
            'filename' => 'backup_20250101_000000.sql',
            'file_path' => 'storage/backups/backup_20250101_000000.sql',
            'file_size' => 1024000,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('backups', [
            'filename' => 'backup_20250101_000000.sql',
            'backup_type' => 'database',
        ]);
    }

    /** @test */
    public function it_can_create_a_database_backup()
    {
        $backup = Backup::create([
            'backup_type' => 'database',
            'filename' => 'db_backup.sql',
            'file_path' => 'storage/backups/db_backup.sql',
            'status' => 'completed',
        ]);

        $this->assertEquals('database', $backup->backup_type);
    }

    /** @test */
    public function it_can_create_a_file_backup()
    {
        $backup = Backup::create([
            'backup_type' => 'files',
            'filename' => 'files_backup.zip',
            'file_path' => 'storage/backups/files_backup.zip',
            'status' => 'completed',
        ]);

        $this->assertEquals('files', $backup->backup_type);
    }

    /** @test */
    public function it_can_track_backup_status()
    {
        $backup = Backup::create([
            'backup_type' => 'database',
            'filename' => 'test_backup.sql',
            'file_path' => 'storage/backups/test_backup.sql',
            'status' => 'pending',
        ]);

        $this->assertEquals('pending', $backup->status);

        $backup->update(['status' => 'in_progress']);
        $this->assertEquals('in_progress', $backup->fresh()->status);

        $backup->update(['status' => 'completed']);
        $this->assertEquals('completed', $backup->fresh()->status);
    }

    /** @test */
    public function it_can_log_backup_failure()
    {
        $backup = Backup::create([
            'backup_type' => 'database',
            'filename' => 'failed_backup.sql',
            'file_path' => 'storage/backups/failed_backup.sql',
            'status' => 'failed',
            'error_message' => 'Database connection failed',
        ]);

        $this->assertEquals('failed', $backup->status);
        $this->assertEquals('Database connection failed', $backup->error_message);
    }

    /** @test */
    public function it_can_store_file_size()
    {
        $fileSize = 5242880; // 5 MB

        $backup = Backup::create([
            'backup_type' => 'database',
            'filename' => 'large_backup.sql',
            'file_path' => 'storage/backups/large_backup.sql',
            'file_size' => $fileSize,
            'status' => 'completed',
        ]);

        $this->assertEquals($fileSize, $backup->file_size);
    }

    /** @test */
    public function it_can_store_backup_metadata()
    {
        $metadata = [
            'database' => 'helpdesk_kemlu',
            'tables_count' => 25,
            'records_count' => 50000,
        ];

        $backup = Backup::create([
            'backup_type' => 'database',
            'filename' => 'metadata_backup.sql',
            'file_path' => 'storage/backups/metadata_backup.sql',
            'status' => 'completed',
            'metadata' => $metadata,
        ]);

        $this->assertEquals($metadata, $backup->metadata);
    }

    /** @test */
    public function it_can_track_backup_creator()
    {
        $backup = Backup::create([
            'backup_type' => 'database',
            'filename' => 'manual_backup.sql',
            'file_path' => 'storage/backups/manual_backup.sql',
            'initiated_by_nip' => '123456789',
            'initiated_by_type' => 'admin_helpdesk',
            'status' => 'completed',
        ]);

        $this->assertEquals('123456789', $backup->initiated_by_nip);
        $this->assertEquals('admin_helpdesk', $backup->initiated_by_type);
    }

    /** @test */
    public function it_can_track_backup_completion_time()
    {
        $backup = Backup::create([
            'backup_type' => 'database',
            'filename' => 'timed_backup.sql',
            'file_path' => 'storage/backups/timed_backup.sql',
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        $this->assertNotNull($backup->started_at);
        $this->assertNotNull($backup->completed_at);
    }

    /** @test */
    public function it_can_retrieve_all_backups()
    {
        Backup::factory()->count(5)->create();

        $backups = Backup::all();

        $this->assertEquals(5, $backups->count());
    }

    /** @test */
    public function it_can_retrieve_backups_by_type()
    {
        Backup::factory()->count(3)->create(['backup_type' => 'database']);
        Backup::factory()->count(2)->create(['backup_type' => 'files']);

        $dbBackups = Backup::where('backup_type', 'database')->get();

        $this->assertEquals(3, $dbBackups->count());
    }

    /** @test */
    public function it_can_retrieve_backups_by_status()
    {
        Backup::factory()->count(3)->create(['status' => 'completed']);
        Backup::factory()->count(2)->create(['status' => 'pending']);

        $completedBackups = Backup::where('status', 'completed')->get();

        $this->assertEquals(3, $completedBackups->count());
    }

    /** @test */
    public function it_can_store_backup_notes()
    {
        $notes = 'Pre-migration backup before system upgrade';

        $backup = Backup::create([
            'backup_type' => 'database',
            'filename' => 'upgrade_backup.sql',
            'file_path' => 'storage/backups/upgrade_backup.sql',
            'status' => 'completed',
            'notes' => $notes,
        ]);

        $this->assertEquals($notes, $backup->notes);
    }

    /** @test */
    public function it_sets_created_at_timestamp()
    {
        $backup = Backup::create([
            'backup_type' => 'database',
            'filename' => 'timestamp_test.sql',
            'file_path' => 'storage/backups/timestamp_test.sql',
            'status' => 'completed',
        ]);

        $this->assertNotNull($backup->created_at);
    }

    /** @test */
    public function it_can_sort_backups_by_creation_date()
    {
        Backup::factory()->create(['created_at' => now()->subDays(5)]);
        Backup::factory()->create(['created_at' => now()->subDays(1)]);
        Backup::factory()->create(['created_at' => now()]);

        $backups = Backup::orderBy('created_at', 'desc')->get();

        $this->assertEquals(3, $backups->count());
        $this->assertTrue($backups->first()->created_at->isAfter($backups->last()->created_at));
    }
}
