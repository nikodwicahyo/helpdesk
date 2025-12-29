<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\TicketHistory;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketHistoryTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_history_record()
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();

        $history = TicketHistory::create([
            'ticket_id' => $ticket->id,
            'performed_by_nip' => $user->nip,
            'performed_by_type' => 'user',
            'action' => 'status_updated',
            'field_name' => 'status',
            'old_value' => 'open',
            'new_value' => 'assigned',
            'description' => 'Status changed',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent'
        ]);

        $this->assertDatabaseHas('ticket_history', [
            'ticket_id' => $ticket->id,
            'performed_by_nip' => $user->nip,
            'action' => 'status_updated',
        ]);
    }

    /** @test */
    public function it_belongs_to_a_ticket()
    {
        $ticket = Ticket::factory()->create();
        $history = TicketHistory::create([
            'ticket_id' => $ticket->id,
            'action' => 'test',
            'performed_by_type' => 'system',
            'description' => 'Test'
        ]);

        $this->assertEquals($ticket->id, $history->ticket->id);
    }

    /** @test */
    public function it_allows_nullable_performed_by_nip()
    {
        $ticket = Ticket::factory()->create();

        $history = TicketHistory::create([
            'ticket_id' => $ticket->id,
            'performed_by_nip' => null,
            'performed_by_type' => 'system',
            'action' => 'auto_update',
            'description' => 'System update'
        ]);

        $this->assertDatabaseHas('ticket_history', [
            'id' => $history->id,
            'performed_by_nip' => null,
            'performed_by_type' => 'system'
        ]);
    }

    /** @test */
    public function it_tracks_changes()
    {
        $ticket = Ticket::factory()->create();
        
        $history = TicketHistory::create([
            'ticket_id' => $ticket->id,
            'action' => 'field_update',
            'field_name' => 'status',
            'old_value' => 'open',
            'new_value' => 'closed',
            'performed_by_type' => 'system'
        ]);

        $this->assertEquals('open', $history->old_value);
        $this->assertEquals('closed', $history->new_value);
    }
}
