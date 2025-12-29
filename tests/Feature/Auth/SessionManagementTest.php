<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\User;
use Illuminate\Support\Facades\Config;

class SessionManagementTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function session_expires_after_configured_timeout()
    {
        // Set short session lifetime
        Config::set('session.lifetime', 1);

        $user = User::factory()->create();
        $this->actingAs($user);

        // Fast forward time
        $this->travel(61)->minutes();

        $response = $this->get('/user/dashboard');
        
        // Should be redirected to login
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /** @test */
    public function users_can_logout()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /** @test */
    public function session_is_invalidated_on_logout()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $session = session()->getId();

        $this->post('/logout');

        $this->assertNotEquals($session, session()->getId());
    }
}
