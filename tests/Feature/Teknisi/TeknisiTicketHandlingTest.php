<?php

namespace Tests\Feature\Teknisi;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\User;
use App\Models\Teknisi;
use App\Models\Ticket;
use App\Models\AdminHelpdesk;

class TeknisiTicketHandlingTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $teknisi;
    protected $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teknisi = Teknisi::factory()->create();
        $admin = AdminHelpdesk::factory()->create();
        
        // Create an assigned ticket
        $this->ticket = Ticket::factory()->create([
            'status' => 'assigned',
            'assigned_teknisi_nip' => $this->teknisi->nip,
            'assigned_by_nip' => $admin->nip,
        ]);
    }

    /** @test */
    public function teknisi_can_view_assigned_tickets()
    {
        $this->actingAs($this->teknisi);

        $response = $this->get('/teknisi/tickets');

        $response->assertStatus(200);
        $response->assertSee($this->ticket->title);
    }

    /** @test */
    public function teknisi_can_start_working_on_ticket()
    {
        $this->actingAs($this->teknisi);

        $response = $this->post("/teknisi/tickets/{$this->ticket->id}/update-status", [
            'status' => 'in_progress',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('tickets', [
            'id' => $this->ticket->id,
            'status' => 'in_progress',
        ]);
    }

    /** @test */
    public function teknisi_can_resolve_ticket()
    {
        $this->actingAs($this->teknisi);
        
        // Set to in_progress first
        $this->ticket->update(['status' => 'in_progress']);

        $response = $this->post("/teknisi/tickets/{$this->ticket->id}/resolve", [
            'resolution_notes' => 'Fixed the issue.',
            'solution_summary' => 'Replaced faulty component',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('tickets', [
            'id' => $this->ticket->id,
            'status' => 'resolved',
            'resolution_notes' => 'Fixed the issue.',
        ]);
    }
}
