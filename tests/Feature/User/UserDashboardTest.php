<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\User;
use App\Models\Ticket;

class UserDashboardTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'user']);
    }

    /** @test */
    public function user_can_view_dashboard()
    {
        $this->actingAs($this->user);

        $response = $this->get('/user/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    /** @test */
    public function user_can_view_dashboard_stats()
    {
        $this->actingAs($this->user);
        
        // Create some tickets
        Ticket::factory()->count(3)->create(['user_nip' => $this->user->nip, 'status' => 'open']);
        Ticket::factory()->count(2)->create(['user_nip' => $this->user->nip, 'status' => 'closed']);

        $response = $this->get('/user/dashboard-stats');

        $response->assertStatus(200);
        $response->assertJson([
            'stats' => [ // Adjust structure based on specific controller response
                'total_tickets' => 5,
                'open_tickets' => 3,
                'closed_tickets' => 2,
            ]
        ]);
    }
}
