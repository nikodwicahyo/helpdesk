<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\AdminAplikasi;
use App\Models\Aplikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminAplikasiTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_admin_aplikasi()
    {
        $admin = AdminAplikasi::factory()->create([
            'nip' => '199012345678',
            'name' => 'Admin App',
            'email' => 'admin.app@example.com',
            'role' => 'admin_aplikasi'
        ]);

        $this->assertDatabaseHas('admin_aplikasis', [
            'nip' => '199012345678',
            'name' => 'Admin App',
        ]);
        
        // Verify assertions based on model attributes
        $this->assertEquals('admin_aplikasi', $admin->role);
    }

    /** @test */
    public function it_can_have_managed_applications()
    {
        $admin = AdminAplikasi::factory()->create();
        // Since there's no direct foreign key in aplikasis schema shown, we test the pivot or json column if applicable
        // The migration shows 'managed_applications' json column on admin_aplikasis table
        
        $admin->update(['managed_applications' => json_encode(['app_1', 'app_2'])]);
        
        $this->assertNotNull($admin->managed_applications);
    }
    
    /** @test */
    public function it_verifies_role_attribute()
    {
        $admin = AdminAplikasi::factory()->create(['role' => 'admin_aplikasi']);
        $this->assertEquals('admin_aplikasi', $admin->role);
    }
}
