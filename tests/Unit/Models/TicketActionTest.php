<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\TicketAction;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Teknisi;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketActionTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_ticket_action()
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();

        $action = TicketAction::create([
            'ticket_id' => $ticket->id,
            'actor_nip' => $user->nip,
            'actor_type' => 'user',
            'action_type' => TicketAction::ACTION_CREATED,
            'description' => 'Ticket created',
        ]);

        $this->assertDatabaseHas('ticket_actions', [
            'ticket_id' => $ticket->id,
            'action_type' => TicketAction::ACTION_CREATED,
        ]);
    }

    /** @test */
    public function it_can_log_ticket_creation()
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();

        $action = TicketAction::log(
            $ticket->id,
            $user->nip,
            'user',
            TicketAction::ACTION_CREATED,
            'Ticket created'
        );

        $this->assertDatabaseHas('ticket_actions', [
            'ticket_id' => $ticket->id,
            'action_type' => TicketAction::ACTION_CREATED,
        ]);
    }

    /** @test */
    public function it_can_log_ticket_assignment()
    {
        $ticket = Ticket::factory()->create();
        $teknisi = Teknisi::factory()->create();

        $action = TicketAction::log(
            $ticket->id,
            $teknisi->nip,
            'teknisi',
            TicketAction::ACTION_ASSIGNED,
            'Ticket assigned',
            ['assigned_to' => $teknisi->nip]
        );

        $this->assertEquals(TicketAction::ACTION_ASSIGNED, $action->action_type);
    }

    /** @test */
    public function it_can_log_status_change()
    {
        $ticket = Ticket::factory()->create();

        $action = TicketAction::log(
            $ticket->id,
            '123456',
            'user',
            TicketAction::ACTION_STATUS_CHANGED,
            'Status changed to assigned'
        );

        $this->assertEquals(TicketAction::ACTION_STATUS_CHANGED, $action->action_type);
    }

    /** @test */
    public function it_can_log_priority_change()
    {
        $ticket = Ticket::factory()->create();

        $action = TicketAction::log(
            $ticket->id,
            '123456',
            'admin_helpdesk',
            TicketAction::ACTION_PRIORITY_CHANGED,
            'Priority changed to high'
        );

        $this->assertEquals(TicketAction::ACTION_PRIORITY_CHANGED, $action->action_type);
    }

    /** @test */
    public function it_can_log_comment_action()
    {
        $ticket = Ticket::factory()->create();

        $action = TicketAction::log(
            $ticket->id,
            '123456',
            'user',
            TicketAction::ACTION_COMMENTED,
            'New comment added'
        );

        $this->assertEquals(TicketAction::ACTION_COMMENTED, $action->action_type);
    }

    /** @test */
    public function it_can_log_ticket_resolution()
    {
        $ticket = Ticket::factory()->create();

        $action = TicketAction::log(
            $ticket->id,
            '654321',
            'teknisi',
            TicketAction::ACTION_RESOLVED,
            'Ticket marked as resolved'
        );

        $this->assertEquals(TicketAction::ACTION_RESOLVED, $action->action_type);
    }

    /** @test */
    public function it_can_log_ticket_closure()
    {
        $ticket = Ticket::factory()->create();

        $action = TicketAction::log(
            $ticket->id,
            '123456',
            'user',
            TicketAction::ACTION_CLOSED,
            'Ticket closed'
        );

        $this->assertEquals(TicketAction::ACTION_CLOSED, $action->action_type);
    }

    /** @test */
    public function it_can_log_ticket_reopening()
    {
        $ticket = Ticket::factory()->create();

        $action = TicketAction::log(
            $ticket->id,
            '123456',
            'user',
            TicketAction::ACTION_REOPENED,
            'Ticket reopened'
        );

        $this->assertEquals(TicketAction::ACTION_REOPENED, $action->action_type);
    }

    /** @test */
    public function it_can_log_ticket_escalation()
    {
        $ticket = Ticket::factory()->create();

        $action = TicketAction::log(
            $ticket->id,
            '123456',
            'admin_helpdesk',
            TicketAction::ACTION_ESCALATED,
            'Ticket escalated'
        );

        $this->assertEquals(TicketAction::ACTION_ESCALATED, $action->action_type);
    }

    /** @test */
    public function it_can_log_first_response()
    {
        $ticket = Ticket::factory()->create();

        $action = TicketAction::log(
            $ticket->id,
            '654321',
            'teknisi',
            TicketAction::ACTION_FIRST_RESPONSE,
            'First response provided'
        );

        $this->assertEquals(TicketAction::ACTION_FIRST_RESPONSE, $action->action_type);
    }

    /** @test */
    public function it_stores_metadata_as_json()
    {
        $metadata = [
            'old_status' => 'open',
            'new_status' => 'assigned',
            'reassign_reason' => 'Wrong department',
        ];

        $action = TicketAction::create([
            'ticket_id' => Ticket::factory()->create()->id,
            'actor_nip' => '123456',
            'actor_type' => 'admin_helpdesk',
            'action_type' => TicketAction::ACTION_STATUS_CHANGED,
            'description' => 'Status changed',
            'metadata' => $metadata,
        ]);

        $this->assertEquals($metadata, $action->metadata);
    }

    /** @test */
    public function it_belongs_to_ticket()
    {
        $ticket = Ticket::factory()->create();
        $action = TicketAction::factory()->create(['ticket_id' => $ticket->id]);

        $this->assertEquals($ticket->id, $action->ticket->id);
    }

    /** @test */
    public function it_can_scope_by_ticket()
    {
        $ticket1 = Ticket::factory()->create();
        $ticket2 = Ticket::factory()->create();

        TicketAction::factory()->count(3)->create(['ticket_id' => $ticket1->id]);
        TicketAction::factory()->count(2)->create(['ticket_id' => $ticket2->id]);

        $actions = TicketAction::forTicket($ticket1->id)->get();

        $this->assertEquals(3, $actions->count());
    }

    /** @test */
    public function it_can_scope_by_action_type()
    {
        TicketAction::factory()->count(2)->create([
            'action_type' => TicketAction::ACTION_CREATED,
        ]);
        TicketAction::factory()->count(3)->create([
            'action_type' => TicketAction::ACTION_COMMENTED,
        ]);

        $createdActions = TicketAction::byActionType(TicketAction::ACTION_CREATED)->get();

        $this->assertEquals(2, $createdActions->count());
    }

    /** @test */
    public function it_can_scope_by_actor()
    {
        $actor = '123456';

        TicketAction::factory()->count(4)->create(['actor_nip' => $actor]);
        TicketAction::factory()->count(2)->create(['actor_nip' => '654321']);

        $actions = TicketAction::byActor($actor)->get();

        $this->assertEquals(4, $actions->count());
    }

    /** @test */
    public function it_can_get_recent_actions()
    {
        TicketAction::factory()->count(60)->create();

        $recentActions = TicketAction::recent(50)->get();

        $this->assertEquals(50, $recentActions->count());
    }

    /** @test */
    public function it_has_formatted_created_at()
    {
        $action = TicketAction::factory()->create();

        $formatted = $action->formatted_created_at;

        $this->assertIsString($formatted);
        $this->assertStringContainsString('M Y', $formatted);
    }

    /** @test */
    public function it_has_time_ago_attribute()
    {
        $action = TicketAction::factory()->create();

        $timeAgo = $action->time_ago;

        $this->assertIsString($timeAgo);
    }

    /** @test */
    public function it_has_action_icon_attribute()
    {
        $action = TicketAction::factory()->create([
            'action_type' => TicketAction::ACTION_CREATED,
        ]);

        $icon = $action->action_icon;

        $this->assertEquals('plus-circle', $icon);
    }

    /** @test */
    public function it_has_action_color_attribute()
    {
        $action = TicketAction::factory()->create([
            'action_type' => TicketAction::ACTION_RESOLVED,
        ]);

        $color = $action->action_color;

        $this->assertEquals('green', $color);
    }
}
