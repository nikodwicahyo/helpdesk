<?php

namespace Tests\Unit\Services;

use Tests\DatabaseTestCase;
use App\Services\BackupService;
use App\Models\Backup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class BackupServiceTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $backupService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupService = new BackupService();
    }

    /** @test */
    public function it_can_get_latest_backup()
    {
        $olderBackup = Backup::factory()->create(['created_at' => now()->subDay(), 'status' => 'completed']);
        $newerBackup = Backup::factory()->create(['created_at' => now(), 'status' => 'completed']);

        $latest = $this->backupService->getLatestBackup();

        // Note: Using 'completed_at' for updates in service, but standard 'created_at' for order if completed_at is null? 
        // Service uses ->orderBy('completed_at', 'desc').
        // Factory should set completed_at.
        
        // Let's assume factory sets it or we set it manually.
        $newerBackup->update(['completed_at' => now()]);
        $olderBackup->update(['completed_at' => now()->subDay()]);

        $latest = $this->backupService->getLatestBackup();

        $this->assertEquals($newerBackup->id, $latest->id);
    }
    
    /** @test */
    public function it_can_get_backup_settings()
    {
        // Should return defaults in testing env as per service code
        $settings = $this->backupService->getBackupSettings();
        
        $this->assertIsArray($settings);
        $this->assertEquals('daily', $settings['auto_backup']);
    }
}
