<?php

namespace Tests\Unit\Services;

use Tests\DatabaseTestCase;
use App\Services\SystemSettingsService;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SystemSettingsServiceTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settingsService = new SystemSettingsService();
    }

    /** @test */
    public function it_can_get_general_settings()
    {
        $settings = $this->settingsService->getGeneralSettings();
        
        $this->assertIsArray($settings);
        $this->assertArrayHasKey('system_name', $settings);
    }

    /** @test */
    public function it_can_update_settings()
    {
        $newSettings = [
            'general' => [
                'system_name' => 'Updated System Name',
            ]
        ];

        $result = $this->settingsService->updateSettings($newSettings);

        $this->assertTrue($result);
        $this->assertDatabaseHas('system_settings', [
            'key' => 'system_name',
            'value' => 'Updated System Name'
        ]);
    }
}
