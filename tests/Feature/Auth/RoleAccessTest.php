<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\User;

class RoleAccessTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_cannot_access_admin_dashboard()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_access_teknisi_dashboard()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $response = $this->get('/teknisi/dashboard');

        $response->assertForbidden();
    }

    /** @test */
    public function teknisi_cannot_access_admin_dashboard()
    {
        $user = User::factory()->create(['role' => 'teknisi']);
        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertForbidden();
    }

    /** @test */
    public function admin_helpdesk_can_access_admin_dashboard()
    {
        $user = User::factory()->create(['role' => 'admin_helpdesk']);
        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_aplikasi_can_access_admin_aplikasi_dashboard()
    {
        $user = User::factory()->create(['role' => 'admin_aplikasi']);
        $this->actingAs($user);

        $response = $this->get('/admin-aplikasi/dashboard');

        $response->assertStatus(200);
    }
}
