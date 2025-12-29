<?php

namespace Tests\Feature\AdminHelpdesk;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\AdminHelpdesk;
use App\Models\User;

class AdminUserManagementTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Create an admin user with admin_helpdesk role
        $this->admin = AdminHelpdesk::factory()->create();
    }

    /** @test */
    public function admin_can_view_users_list()
    {
        $this->actingAs($this->admin);

        // Create some users
        User::factory()->count(3)->create();

        $response = $this->get('/admin/users-management');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('AdminHelpdesk/UserManagement')
        );
    }

    /** @test */
    public function admin_can_create_user()
    {
        $this->actingAs($this->admin);

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'nip' => '123456789',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'user',
            'department' => 'Test Dept',
            'phone' => '08123456789',
            'status' => 'active',
        ];

        // The route relies on Resource Controller: store
        $response = $this->post('/admin/users', $userData);

        $response->assertRedirect(); // Likely redirects to index or show
        
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'nip' => '123456789',
        ]);
    }

    /** @test */
    public function admin_can_update_user()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'nip' => $user->nip,
            'role' => 'user', 
            'status' => 'active',
        ];

        $response = $this->put("/admin/users/{$user->nip}", $updateData);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('users', [
            'nip' => $user->nip,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function admin_can_toggle_user_status()
    {
        $this->actingAs($this->admin);

        // Ensure user has a status
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->postJson("/admin/users/{$user->nip}/toggle-status", [
            'status' => 'inactive'
        ]);
        
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'user' => ['status' => 'inactive']
                 ]);
        
        $user->refresh();
        $this->assertEquals('inactive', $user->status);
    }
}
