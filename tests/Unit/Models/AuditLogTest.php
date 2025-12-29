<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;
use App\Models\AdminHelpdesk;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditLogTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_audit_log()
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();

        $auditLog = AuditLog::create([
            'action' => AuditLog::ACTION_CREATED,
            'entity_type' => 'ticket',
            'entity_id' => $ticket->id,
            'actor_type' => 'user',
            'actor_nip' => $user->nip,
            'description' => 'Ticket created',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_CREATED,
            'entity_type' => 'ticket',
        ]);
    }

    /** @test */
    public function it_can_log_ticket_creation()
    {
        $user = User::factory()->create();

        AuditLog::create([
            'action' => AuditLog::ACTION_CREATED,
            'entity_type' => 'ticket',
            'entity_id' => 1,
            'actor_type' => 'user',
            'actor_nip' => $user->nip,
            'description' => 'Ticket created',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_CREATED,
            'description' => 'Ticket created',
        ]);
    }

    /** @test */
    public function it_can_log_ticket_assignment()
    {
        AuditLog::create([
            'action' => AuditLog::ACTION_ASSIGNED,
            'entity_type' => 'ticket',
            'entity_id' => 1,
            'actor_type' => 'admin_helpdesk',
            'actor_nip' => '123456',
            'description' => 'Ticket assigned to teknisi',
            'changes' => ['assigned_to' => 'TK001'],
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_ASSIGNED,
        ]);
    }

    /** @test */
    public function it_can_log_status_change()
    {
        AuditLog::create([
            'action' => AuditLog::ACTION_STATUS_CHANGED,
            'entity_type' => 'ticket',
            'entity_id' => 1,
            'actor_type' => 'teknisi',
            'actor_nip' => '654321',
            'description' => 'Status changed to in_progress',
            'old_value' => 'open',
            'new_value' => 'in_progress',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_STATUS_CHANGED,
            'old_value' => 'open',
            'new_value' => 'in_progress',
        ]);
    }

    /** @test */
    public function it_can_log_priority_change()
    {
        AuditLog::create([
            'action' => AuditLog::ACTION_PRIORITY_CHANGED,
            'entity_type' => 'ticket',
            'entity_id' => 1,
            'actor_type' => 'admin_helpdesk',
            'actor_nip' => '123456',
            'old_value' => 'medium',
            'new_value' => 'high',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_PRIORITY_CHANGED,
        ]);
    }

    /** @test */
    public function it_can_log_ticket_resolution()
    {
        AuditLog::create([
            'action' => AuditLog::ACTION_RESOLVED,
            'entity_type' => 'ticket',
            'entity_id' => 1,
            'actor_type' => 'teknisi',
            'actor_nip' => '654321',
            'description' => 'Ticket marked as resolved',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_RESOLVED,
        ]);
    }

    /** @test */
    public function it_can_log_ticket_closure()
    {
        AuditLog::create([
            'action' => AuditLog::ACTION_CLOSED,
            'entity_type' => 'ticket',
            'entity_id' => 1,
            'actor_type' => 'user',
            'actor_nip' => User::factory()->create()->nip,
            'description' => 'Ticket closed',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_CLOSED,
        ]);
    }

    /** @test */
    public function it_can_log_login_action()
    {
        $user = User::factory()->create();

        AuditLog::create([
            'action' => AuditLog::ACTION_LOGIN,
            'entity_type' => 'user',
            'entity_id' => $user->nip,
            'actor_type' => 'user',
            'actor_nip' => $user->nip,
            'description' => 'User logged in',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_LOGIN,
            'ip_address' => '192.168.1.1',
        ]);
    }

    /** @test */
    public function it_can_log_failed_login()
    {
        AuditLog::create([
            'action' => AuditLog::ACTION_LOGIN_FAILED,
            'entity_type' => 'user',
            'entity_id' => 'unknown_user',
            'actor_type' => 'system',
            'description' => 'Failed login attempt',
            'ip_address' => '192.168.1.100',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_LOGIN_FAILED,
        ]);
    }

    /** @test */
    public function it_can_log_password_change()
    {
        $user = User::factory()->create();

        AuditLog::create([
            'action' => AuditLog::ACTION_PASSWORD_CHANGED,
            'entity_type' => 'user',
            'entity_id' => $user->nip,
            'actor_type' => 'user',
            'actor_nip' => $user->nip,
            'description' => 'Password changed',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_PASSWORD_CHANGED,
        ]);
    }

    /** @test */
    public function it_can_log_bulk_assignment()
    {
        AuditLog::create([
            'action' => AuditLog::ACTION_BULK_ASSIGNED,
            'entity_type' => 'tickets',
            'entity_id' => null,
            'actor_type' => 'admin_helpdesk',
            'actor_nip' => '123456',
            'description' => 'Bulk assigned 10 tickets',
            'changes' => ['count' => 10],
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_BULK_ASSIGNED,
        ]);
    }

    /** @test */
    public function it_can_log_export_action()
    {
        AuditLog::create([
            'action' => AuditLog::ACTION_EXPORTED,
            'entity_type' => 'reports',
            'entity_id' => null,
            'actor_type' => 'admin_helpdesk',
            'actor_nip' => '123456',
            'description' => 'Exported tickets to CSV',
            'metadata' => ['format' => 'csv', 'count' => 50],
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_EXPORTED,
        ]);
    }

    /** @test */
    public function it_can_log_setting_change()
    {
        AuditLog::create([
            'action' => AuditLog::ACTION_SETTING_CHANGED,
            'entity_type' => 'system_settings',
            'entity_id' => null,
            'actor_type' => 'admin_helpdesk',
            'actor_nip' => '123456',
            'description' => 'System setting changed',
            'metadata' => ['setting_key' => 'max_tickets', 'old_value' => 100, 'new_value' => 150],
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_SETTING_CHANGED,
        ]);
    }

    /** @test */
    public function it_stores_changes_as_json()
    {
        $changes = [
            'status' => ['from' => 'open', 'to' => 'assigned'],
            'assigned_teknisi_nip' => ['from' => null, 'to' => 'TK001'],
        ];

        $auditLog = AuditLog::create([
            'action' => AuditLog::ACTION_UPDATED,
            'entity_type' => 'ticket',
            'entity_id' => 1,
            'actor_type' => 'user',
            'actor_nip' => '123456',
            'changes' => $changes,
        ]);

        $this->assertEquals($changes, $auditLog->changes);
    }

    /** @test */
    public function it_stores_metadata_as_json()
    {
        $metadata = [
            'module' => 'tickets',
            'action_type' => 'bulk_operation',
            'affected_records' => 25,
        ];

        $auditLog = AuditLog::create([
            'action' => AuditLog::ACTION_BULK_UPDATED,
            'entity_type' => 'tickets',
            'entity_id' => null,
            'actor_type' => 'admin_helpdesk',
            'actor_nip' => '123456',
            'metadata' => $metadata,
        ]);

        $this->assertEquals($metadata, $auditLog->metadata);
    }

    /** @test */
    public function it_can_query_logs_by_action()
    {
        AuditLog::create([
            'action' => AuditLog::ACTION_CREATED,
            'entity_type' => 'ticket',
            'entity_id' => 1,
            'actor_type' => 'user',
            'actor_nip' => '111111',
        ]);
        AuditLog::create([
            'action' => AuditLog::ACTION_UPDATED,
            'entity_type' => 'ticket',
            'entity_id' => 1,
            'actor_type' => 'teknisi',
            'actor_nip' => '222222',
        ]);

        $createdLogs = AuditLog::where('action', AuditLog::ACTION_CREATED)->get();

        $this->assertEquals(1, $createdLogs->count());
    }

    /** @test */
    public function it_can_query_logs_by_entity_type()
    {
        AuditLog::create([
            'action' => AuditLog::ACTION_CREATED,
            'entity_type' => 'ticket',
            'entity_id' => 1,
            'actor_type' => 'user',
            'actor_nip' => '111111',
        ]);
        AuditLog::create([
            'action' => AuditLog::ACTION_CREATED,
            'entity_type' => 'user',
            'entity_id' => 2,
            'actor_type' => 'admin_helpdesk',
            'actor_nip' => '222222',
        ]);

        $ticketLogs = AuditLog::where('entity_type', 'ticket')->get();

        $this->assertEquals(1, $ticketLogs->count());
    }
}
