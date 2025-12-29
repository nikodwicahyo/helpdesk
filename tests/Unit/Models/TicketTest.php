<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Teknisi;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_ticket()
    {
        $user = User::factory()->create();
        $aplikasi = Aplikasi::factory()->create();
        $kategori = KategoriMasalah::factory()->create();

        $ticket = Ticket::factory()->create([
            'user_nip' => $user->nip,
            'aplikasi_id' => $aplikasi->id,
            'kategori_masalah_id' => $kategori->id,
            'title' => 'Test Ticket',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('tickets', [
            'user_nip' => $user->nip,
            'title' => 'Test Ticket',
            'status' => 'open',
        ]);
    }

    /** @test */
    public function it_has_user_relationship()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_nip' => $user->nip]);

        $this->assertEquals($user->nip, $ticket->user->nip);
    }

    /** @test */
    public function it_has_aplikasi_relationship()
    {
        $aplikasi = Aplikasi::factory()->create();
        $ticket = Ticket::factory()->create(['aplikasi_id' => $aplikasi->id]);

        $this->assertEquals($aplikasi->id, $ticket->aplikasi->id);
    }

    /** @test */
    public function it_can_check_valid_status_transitions()
    {
        $ticket = Ticket::factory()->create(['status' => 'open']);

        $this->assertTrue($ticket->canTransitionTo('assigned'));
    }

    /** @test */
    public function it_can_transition_to_new_status()
    {
        $ticket = Ticket::factory()->create(['status' => 'open']);

        $result = $ticket->transitionTo('assigned');

        $this->assertTrue($result);
        $this->assertEquals('assigned', $ticket->fresh()->status);
    }

    /** @test */
    public function it_can_mark_first_response()
    {
        $ticket = Ticket::factory()->create();

        $ticket->markFirstResponse();

        $this->assertNotNull($ticket->fresh()->first_response_at);
    }

    /** @test */
    public function it_can_scope_by_status()
    {
        Ticket::factory()->count(3)->create(['status' => 'open']);
        Ticket::factory()->count(2)->create(['status' => 'closed']);

        $openTickets = Ticket::byStatus('open')->get();
        $this->assertEquals(3, $openTickets->count());
    }

    /** @test */
    public function it_can_scope_by_priority()
    {
        Ticket::factory()->count(2)->create(['priority' => 'urgent']);
        Ticket::factory()->count(3)->create(['priority' => 'low']);

        $urgentTickets = Ticket::byPriority('urgent')->get();
        $this->assertEquals(2, $urgentTickets->count());
    }
}
