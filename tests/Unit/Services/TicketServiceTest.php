<?php

namespace Tests\Unit\Services;

use Tests\DatabaseTestCase;
use App\Services\TicketService;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Exception;

class TicketServiceTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $ticketService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ticketService = new TicketService();
    }

    /** @test */
    public function it_can_generate_ticket_number()
    {
        // Format: TKT-{YYYYMMDD}-{Sequence}
        $number = $this->ticketService->generateTicketNumber();
        $this->assertMatchesRegularExpression('/^TKT-\d{8}-\d{4}$/', $number);
    }

    /** @test */
    public function it_can_create_a_ticket()
    {
        $user = User::factory()->create();
        $this->actingAs($user); // Authenticate as the user

        $aplikasi = Aplikasi::factory()->create();
        $kategori = KategoriMasalah::factory()->create(['aplikasi_id' => $aplikasi->id]);

        $data = [
            'user_nip' => $user->nip,
            'title' => 'Test Ticket Service',
            'description' => 'Testing service creation',
            'aplikasi_id' => $aplikasi->id,
            'kategori_masalah_id' => $kategori->id,
            'priority' => 'medium',
            'status' => 'open'
        ];

        $result = $this->ticketService->createTicket($data);

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(Ticket::class, $result['ticket']);
        $this->assertEquals($user->nip, $result['ticket']->user_nip);
        $this->assertEquals('Test Ticket Service', $result['ticket']->title);
        $this->assertEquals('open', $result['ticket']->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'entity_type' => 'Ticket',
            'entity_id' => $result['ticket']->id,
        ]);
    }

    /** @test */
    public function it_validates_ticket_data()
    {
        $data = [
            'user_nip' => 'INVALID',
            // Missing required fields
        ];

        $result = $this->ticketService->createTicket($data);
        
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
    }

    /** @test */
    public function it_can_update_ticket_status()
    {
        $ticket = Ticket::factory()->create(['status' => 'open']);
        $user = User::factory()->create(); // Actor

        $result = $this->ticketService->updateTicketStatus($ticket->id, 'in_progress', $user->nip);

        $this->assertTrue($result['success']);
        $this->assertEquals('in_progress', $result['ticket']->status);
    }
}
