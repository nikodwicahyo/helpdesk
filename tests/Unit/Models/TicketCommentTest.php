<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\TicketComment;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Teknisi;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketCommentTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_comment()
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();

        $comment = TicketComment::factory()->create([
            'ticket_id' => $ticket->id,
            'commenter_nip' => $user->nip,
            'commenter_type' => 'user',
            'comment' => 'This is a test comment',
            'type' => 'comment',
            'is_internal' => false
        ]);

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'commenter_nip' => $user->nip,
            'comment' => 'This is a test comment',
        ]);
    }

    /** @test */
    public function it_belongs_to_a_ticket()
    {
        $ticket = Ticket::factory()->create();
        $comment = TicketComment::factory()->create(['ticket_id' => $ticket->id]);

        $this->assertEquals($ticket->id, $comment->ticket->id);
    }

    /** @test */
    public function it_can_be_internal()
    {
        $comment = TicketComment::factory()->create(['is_internal' => true]);

        $this->assertTrue($comment->is_internal);
    }

    /** @test */
    public function it_can_scope_internal_comments()
    {
        TicketComment::factory()->count(3)->internal()->create();
        TicketComment::factory()->count(2)->create(['is_internal' => false]);

        $internalComments = TicketComment::where('is_internal', true)->get();
        $this->assertEquals(3, $internalComments->count());
    }

    /** @test */
    public function it_can_have_attachments()
    {
        $attachments = ['file1.jpg', 'file2.pdf'];
        $comment = TicketComment::factory()->create(['attachments' => $attachments]);

        $this->assertEquals($attachments, $comment->attachments);
    }
}
