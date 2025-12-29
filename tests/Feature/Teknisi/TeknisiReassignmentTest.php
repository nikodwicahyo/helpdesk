<?php

namespace Tests\Feature\Teknisi;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\Teknisi;
use App\Models\Ticket;
use App\Models\AdminHelpdesk;

class TeknisiReassignmentTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $teknisi;
    protected $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teknisi = Teknisi::factory()->create();
        $admin = AdminHelpdesk::factory()->create();
        
        $this->ticket = Ticket::factory()->create([
            'status' => 'assigned',
            'assigned_teknisi_nip' => $this->teknisi->nip,
            'assigned_by_nip' => $admin->nip,
        ]);
    }

    /** @test */
    public function teknisi_can_request_reassignment()
    {
        $this->actingAs($this->teknisi);

        $response = $this->post("/teknisi/tickets/{$this->ticket->id}/request-reassignment", [
            'reason' => 'Not my expertise.',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        
        // Check ticket actions or comments or wherever the request is stored
        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $this->ticket->id,
            'type' => 'reassignment_request',
            'is_internal' => true,
        ]);
    }
}
