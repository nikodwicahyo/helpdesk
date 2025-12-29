<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\User;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserTicketCreationTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $user;
    protected $aplikasi;
    protected $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'user']);
        $this->aplikasi = Aplikasi::factory()->create();
        $this->kategori = KategoriMasalah::factory()->create(['aplikasi_id' => $this->aplikasi->id]);
    }

    /** @test */
    public function user_can_view_create_ticket_page()
    {
        $this->actingAs($this->user);

        $response = $this->get('/user/tickets/create');

        $response->assertStatus(200);
        $response->assertSee('Create Ticket');
    }

    /** @test */
    public function user_can_create_a_ticket_with_valid_data()
    {
        $this->actingAs($this->user);
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $response = $this->post('/user/tickets', [
            'aplikasi_id' => $this->aplikasi->id,
            'kategori_masalah_id' => $this->kategori->id,
            'title' => 'Test Ticket Issue',
            'description' => 'This is a description of the issue.',
            'priority' => 'medium',
            'location' => 'Jakarta',
            'attachments' => [$file],
        ]);

        $ticket = \App\Models\Ticket::where('title', 'Test Ticket Issue')->first();
        $response->assertRedirect("/user/tickets/{$ticket->id}");
        
        $this->assertDatabaseHas('tickets', [
            'user_nip' => $this->user->nip,
            'title' => 'Test Ticket Issue',
            'status' => 'open',
        ]);

        // Verify file upload logic if applicable (database check for file)
        // Note: Assuming implementation details, adjusting assertion as needed
    }

    /** @test */
    public function ticket_creation_requires_mandatory_fields()
    {
        $this->actingAs($this->user);

        $response = $this->post('/user/tickets', []);

        $response->assertSessionHasErrors(['title', 'description', 'aplikasi_id', 'kategori_masalah_id']);
    }
}
