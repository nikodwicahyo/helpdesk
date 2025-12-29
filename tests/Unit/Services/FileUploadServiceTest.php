<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FileUploadService;
use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FileUploadServiceTest extends \Tests\DatabaseTestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /** @test */
    public function it_validates_file_successfully()
    {
        Storage::fake('public');
        
        // Seed default system settings if necessary, or rely on Service defaults
        // SystemSetting::create(['key' => 'max_file_size', 'value' => 5]);

        $file = UploadedFile::fake()->create('document.pdf', 1000); // 1MB
        
        $result = FileUploadService::validate($file);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_rejects_large_files()
    {
        Storage::fake('public');
        
        // Mock SystemSetting to return 1MB max
        // Since we can't easily mock static method on model without facade, let's use the DB
        // Assuming SystemSetting model stores data in 'system_settings' table
        // But SystemSetting::get() might use cache.
        // If it uses Cache, we should clear it or set it.
        // Let's assume it checks DB.
        
        // For now, testing default behavior (2MB limit)
        $file = UploadedFile::fake()->create('large.pdf', 3000); // 3MB
        
        $result = FileUploadService::validate($file);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('exceeds maximum allowed size', $result['errors'][0]);
    }

    /** @test */
    public function it_stores_file_correctly()
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->create('test.jpg', 500);
        
        $path = FileUploadService::store($file, 'tickets');
        
        Storage::disk('public')->assertExists($path);
    }
}
