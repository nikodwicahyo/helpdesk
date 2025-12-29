<?php

namespace Tests\Feature\AdminHelpdesk;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\AdminHelpdesk;
use App\Models\Ticket;
use App\Models\Teknisi;
use App\Models\User;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;

class AdminTicketManagementTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminHelpdesk::factory()->create();
    }

    /** @test */
    public function admin_can_view_tickets_list()
    {
        $this->actingAs($this->admin);

        Ticket::factory()->count(5)->create();

        $response = $this->get('/admin/tickets-management');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('AdminHelpdesk/TicketManagement')
            ->has('tickets.data', 5)
        );
    }

    /** @test */
    public function admin_can_view_ticket_detail()
    {
        $this->actingAs($this->admin);

        $ticket = Ticket::factory()->create();

        $response = $this->get("/admin/tickets-management/{$ticket->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('AdminHelpdesk/TicketDetail')
            ->where('ticket.id', $ticket->id)
        );
    }

    /** @test */
    public function admin_can_update_ticket_priority()
    {
        $this->actingAs($this->admin);

        $ticket = Ticket::factory()->create(['priority' => 'low']);

        $response = $this->postJson("/admin/tickets-management/{$ticket->id}/update-priority", [
            'priority' => 'high',
            'reason' => 'Urgent request',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'priority' => 'high',
        ]);
    }

    /** @test */
    public function admin_can_bulk_assign_tickets()
    {
        $this->actingAs($this->admin);

        $tickets = Ticket::factory()->count(3)->open()->create();
        $teknisi = Teknisi::factory()->create();
        $ticketIds = $tickets->pluck('id')->toArray();

        $response = $this->post("/admin/tickets-management/bulk-assign", [
            'ticket_ids' => $ticketIds,
            'teknisi_nip' => $teknisi->nip,
            'notes' => 'Bulk assignment',
        ]);

        $response->assertRedirect(); // Assuming bulk assign redirects back

        foreach ($tickets as $ticket) {
            $this->assertDatabaseHas('tickets', [
                'id' => $ticket->id,
                'assigned_teknisi_nip' => $teknisi->nip,
            ]);
        }
    }

    /** @test */
    public function admin_can_bulk_update_status()
    {
        $this->actingAs($this->admin);

        $tickets = Ticket::factory()->count(3)->inProgress()->create();
        $ticketIds = $tickets->pluck('id')->toArray();

        $response = $this->postJson("/admin/tickets-management/bulk-update-status", [
            'ticket_ids' => $ticketIds,
            'status' => 'resolved',
            'notes' => 'Closing all',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        foreach ($tickets as $ticket) {
            $this->assertDatabaseHas('tickets', [
                'id' => $ticket->id,
                'status' => 'resolved',
            ]);
        }
    }

    /** @test */
    public function admin_can_get_ticket_stats()
    {
        $this->actingAs($this->admin);

        Ticket::factory()->count(5)->create();
        Ticket::factory()->count(2)->resolved()->create();

        $response = $this->getJson('/admin/tickets-management/stats');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'overview' => ['total_tickets', 'resolved_tickets'],
                     'trends',
                     'priority_breakdown',
                 ]);
    }
}
