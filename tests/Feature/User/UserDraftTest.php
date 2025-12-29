<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\User;
use App\Models\TicketDraft;

class UserDraftTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'user']);
    }

    /** @test */
    public function user_can_save_draft()
    {
        $this->actingAs($this->user);

        $response = $this->post('/user/tickets/drafts/save', [
            'step' => 1,
            'formData' => [
                'title' => 'Draft Title',
                'description' => 'Draft Description',
            ],
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('ticket_drafts', [
            'user_nip' => $this->user->nip,
            'title' => 'Draft Title',
        ]);
    }

    /** @test */
    public function user_can_load_draft()
    {
        $this->actingAs($this->user);
        
        $draft = TicketDraft::factory()->create([
            'user_nip' => $this->user->nip,
            'title' => 'Existing Draft',
        ]);

        $response = $this->get('/user/tickets/drafts/load');

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'Existing Draft']);
    }

    /** @test */
    public function user_can_delete_draft()
    {
        $this->actingAs($this->user);
        
        $draft = TicketDraft::factory()->create(['user_nip' => $this->user->nip]);

        $response = $this->delete('/user/tickets/drafts/delete');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('ticket_drafts', ['id' => $draft->id]);
    }
}
