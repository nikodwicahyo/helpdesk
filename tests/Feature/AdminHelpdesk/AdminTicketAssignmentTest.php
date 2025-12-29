<?php

namespace Tests\Feature\AdminHelpdesk;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\AdminHelpdesk;
use App\Models\Ticket;
use App\Models\Teknisi;

class AdminTicketAssignmentTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminHelpdesk::factory()->create();
    }

    /** @test */
    public function admin_can_assign_ticket_to_teknisi()
    {
        $this->actingAs($this->admin);

        $ticket = Ticket::factory()->open()->create();
        $teknisi = Teknisi::factory()->create();

        $response = $this->postJson("/admin/tickets-management/{$ticket->id}/assign", [
            'teknisi_nip' => $teknisi->nip,
            'notes' => 'Please handle this.',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_teknisi_nip' => $teknisi->nip,
            'status' => 'assigned',
        ]);
    }

    /** @test */
    public function admin_can_unassign_ticket()
    {
        $this->actingAs($this->admin);

        $teknisi = Teknisi::factory()->create();
        $ticket = Ticket::factory()->assigned()->create([
            'assigned_teknisi_nip' => $teknisi->nip,
        ]);

        $response = $this->postJson("/admin/tickets-management/{$ticket->id}/unassign", [
            'reason' => 'Mistake assignment',
        ]);
        
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_teknisi_nip' => null,
            'status' => 'open',
        ]);
    }
}
