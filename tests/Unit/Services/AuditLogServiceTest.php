<?php

namespace Tests\Unit\Services;

use Tests\DatabaseTestCase;
use App\Services\AuditLogService;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class AuditLogServiceTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_log_ticket_creation()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_nip' => $user->nip]);

        // Manually trigger calling the service (usually called by TicketService)
        // But since we want to test AuditLogService logic specifically:
        
        Auth::login($user);
        AuditLogService::logTicketCreated($ticket);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'entity_type' => 'Ticket',
            'entity_id' => $ticket->id,
            'actor_id' => $user->nip,
            'actor_type' => 'User',
        ]);
    }

    /** @test */
    public function it_can_log_generic_action()
    {
        $user = User::factory()->create();
        Auth::login($user);

        AuditLogService::log(
            'generic_action',           // action
            'Test description',         // description
            'Test Object',              // entityType
            123,                        // entityId
            []                          // metadata
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'generic_action',
            'entity_type' => 'Test Object',
            'entity_id' => '123',
            'description' => 'Test description',
            'actor_id' => $user->nip,
            'actor_type' => 'User',
        ]);
    }
}
