<?php

namespace Tests\Feature\Teknisi;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\Teknisi;
use App\Models\Ticket;
use App\Models\AdminHelpdesk;

class TeknisiCommentTest extends DatabaseTestCase
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
    public function teknisi_can_add_public_comment()
    {
        $this->actingAs($this->teknisi);

        $response = $this->post("/teknisi/tickets/{$this->ticket->id}/comments", [
            'comment' => 'Public update.',
            'is_internal' => false,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $this->ticket->id,
            'commenter_nip' => $this->teknisi->nip,
            'commenter_type' => Teknisi::class,
            'comment' => 'Public update.',
            'is_internal' => false,
        ]);
    }

    /** @test */
    public function teknisi_can_add_internal_note()
    {
        $this->actingAs($this->teknisi);

        $response = $this->post("/teknisi/tickets/{$this->ticket->id}/comments", [
            'comment' => 'Internal note.',
            'is_internal' => true,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
        
        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $this->ticket->id,
            'commenter_nip' => $this->teknisi->nip,
            'commenter_type' => Teknisi::class,
            'comment' => 'Internal note.',
            'is_internal' => true,
        ]);
    }
}
