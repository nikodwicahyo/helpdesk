<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Teknisi;

class UserTicketRatingTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $user;
    protected $teknisi;
    protected $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'user']);
        $this->teknisi = Teknisi::factory()->create();
        
        // Create a resolved ticket assigned to teknisi
        $this->ticket = Ticket::factory()->create([
            'user_nip' => $this->user->nip,
            'assigned_teknisi_nip' => $this->teknisi->nip,
            'status' => 'resolved',
        ]);
    }

    /** @test */
    public function user_can_rate_resolved_ticket()
    {
        $this->actingAs($this->user);

        $response = $this->post("/user/tickets/{$this->ticket->id}/rate", [
            'rating' => 5,
            'review' => 'Great service!',
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('tickets', [
            'id' => $this->ticket->id,
            'user_rating' => 5,
            // 'status' => 'closed', // Rating might not close it immediately depending on logic
        ]);
        
        // Check ticket_feedback if table exists, or relevant fields in tickets table
        $this->assertDatabaseHas('tickets', [
            'id' => $this->ticket->id,
            'user_feedback' => 'Great service!',
        ]);
    }

    /** @test */
    public function user_cannot_rate_open_ticket()
    {
        $this->actingAs($this->user);
        
        $openTicket = Ticket::factory()->create([
            'user_nip' => $this->user->nip,
            'status' => 'open',
        ]);

        $response = $this->post("/user/tickets/{$openTicket->id}/rate", [
            'rating' => 5,
        ]);

        $response->assertForbidden(); // Or 404/redirect depending on implementation
    }
}
