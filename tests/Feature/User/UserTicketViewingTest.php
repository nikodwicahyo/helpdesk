<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\User;
use App\Models\Ticket;

class UserTicketViewingTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'user']);
    }

    /** @test */
    public function user_can_view_own_tickets()
    {
        $this->actingAs($this->user);
        
        $ticket = Ticket::factory()->create(['user_nip' => $this->user->nip]);

        $response = $this->get('/user/tickets');

        $response->assertStatus(200);
        $response->assertSee($ticket->title);
    }

    /** @test */
    public function user_cannot_view_other_user_tickets()
    {
        $this->actingAs($this->user);
        
        $otherUser = User::factory()->create();
        $otherTicket = Ticket::factory()->create(['user_nip' => $otherUser->nip]);

        // Trying to view detail of another user's ticket
        $response = $this->get("/user/tickets/{$otherTicket->id}");
        
        $response->assertRedirect('/user/tickets');
        $response->assertSessionHasErrors();
    }

    /** @test */
    public function user_can_view_ticket_details_of_own_ticket()
    {
        $this->actingAs($this->user);
        
        $ticket = Ticket::factory()->create(['user_nip' => $this->user->nip]);

        $response = $this->get("/user/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertSee($ticket->title);
    }
}
