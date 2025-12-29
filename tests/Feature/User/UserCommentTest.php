<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketComment;

class UserCommentTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $user;
    protected $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'user']);
        $this->ticket = Ticket::factory()->create(['user_nip' => $this->user->nip]);
    }

    /** @test */
    public function user_can_add_comment_to_own_ticket()
    {
        $this->actingAs($this->user);

        $response = $this->post("/user/tickets/{$this->ticket->id}/comments", [
            'comment' => 'This is a new comment',
        ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'comment' => 'This is a new comment',
            'is_internal' => false,
        ]);
    }

    /** @test */
    public function user_cannot_comment_on_other_tickets()
    {
        $otherUser = User::factory()->create();
        $otherTicket = Ticket::factory()->create(['user_nip' => $otherUser->nip]);
        
        $this->actingAs($this->user);

        $response = $this->post("/user/tickets/{$otherTicket->id}/comments", [
            'comment' => 'Intruder comment',
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_delete_own_comment()
    {
        $this->actingAs($this->user);
        
        $comment = TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->delete("/user/tickets/{$this->ticket->id}/comments/{$comment->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('ticket_comments', ['id' => $comment->id]);
    }
}
