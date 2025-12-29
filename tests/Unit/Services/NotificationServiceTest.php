<?php

namespace Tests\Unit\Services;

use Tests\DatabaseTestCase;
use App\Services\NotificationService;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Notification;
use App\Models\AdminHelpdesk;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationServiceTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = new NotificationService();
    }

    /** @test */
    public function it_can_create_a_notification()
    {
        $user = User::factory()->create();

        $data = [
            'type' => 'test_type',
            'title' => 'Test Notification',
            'message' => 'This is a test',
            'user_nip' => $user->nip, // Targeting user
        ];

        $result = $this->notificationService->createNotification($data);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('notifications', [
            'type' => 'test_type',
            'title' => 'Test Notification',
            'notifiable_id' => $user->nip,
            'notifiable_type' => User::class,
        ]);
    }

    /** @test */
    public function it_can_notify_admins_on_ticket_creation()
    {
        $admin = AdminHelpdesk::factory()->create(['status' => 'active']); // Make sure admin is active
        $ticket = Ticket::factory()->create();

        $result = $this->notificationService->notifyTicketCreated($ticket);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['notifications_created']);
        
        $this->assertDatabaseHas('notifications', [
            'type' => 'ticket_created',
            'ticket_id' => $ticket->id,
            'notifiable_id' => $admin->nip,
        ]);
    }
}
