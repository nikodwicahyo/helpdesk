<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\AdminHelpdesk;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminHelpdeskTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_admin_helpdesk()
    {
        $admin = AdminHelpdesk::factory()->create([
            'nip' => '199012345678',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $this->assertDatabaseHas('admin_helpdesks', [
            'nip' => '199012345678',
            'name' => 'Admin User',
        ]);
    }

    /** @test */
    public function it_can_check_if_is_admin_helpdesk()
    {
        $admin = AdminHelpdesk::factory()->create();
        
        $this->assertEquals('admin_helpdesk', $admin->role);
    }

    /** @test */
    public function it_returns_correct_user_role()
    {
        $admin = AdminHelpdesk::factory()->create();
        
        $this->assertEquals('admin_helpdesk', $admin->role);
    }
}
