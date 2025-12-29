<?php

namespace Tests\Feature\AdminHelpdesk;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\AdminHelpdesk;
use App\Models\SystemSetting;

class AdminSystemSettingsTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a system admin or admin with system settings permission
        $this->admin = AdminHelpdesk::factory()->create(['permissions' => ['system_admin']]);
    }

    /** @test */
    public function admin_can_view_settings_page()
    {
        $this->actingAs($this->admin, 'admin_helpdesk');

        $response = $this->get('/admin/system-settings');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('AdminHelpdesk/SystemSettings')
            ->has('settings')
        );
    }

    public function admin_can_update_general_settings()
    {
        $this->actingAs($this->admin, 'admin_helpdesk');

        $response = $this->putJson('/admin/system-settings', [
            'general' => [
                'system_name' => 'Updated HelpDesk Name',
                'items_per_page' => 20,
            ]
        ]);

        $response->assertStatus(302); // Redirect back
        // Follow redirect or check session/DB
        
        $this->assertEquals('Updated HelpDesk Name', SystemSetting::get('system_name'));
        $this->assertEquals(20, SystemSetting::get('items_per_page'));
    }

    /** @test */
    public function admin_can_update_email_settings()
    {
        $this->actingAs($this->admin);

        $response = $this->putJson('/admin/system-settings', [
            'email' => [
                'mail_driver' => 'smtp',
                'mail_host' => 'smtp.mailtrap.io',
            ]
        ]);

        $response->assertStatus(302);
        
        $this->assertEquals('smtp.mailtrap.io', SystemSetting::get('mail_host'));
    }

    /** @test */
    public function admin_can_reset_settings()
    {
        $this->actingAs($this->admin);

        // First change something
        SystemSetting::set('system_name', 'Changed Name');

        $response = $this->postJson('/admin/system-settings/reset');

        $response->assertStatus(302);
        
        // Should revert to default
        $this->assertNotEquals('Changed Name', SystemSetting::get('system_name'));
    }
}
