<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\Notification;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_notification()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $notification = Notification::factory()->create([
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $user->nip,
            'ticket_id' => $ticket->id,
            'type' => 'ticket_created',
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $user->nip,
            'type' => 'ticket_created',
        ]);
    }

    /** @test */
    public function it_can_be_marked_as_read()
    {
        $notification = Notification::factory()->create(['status' => 'unread']);

        $notification->markAsRead();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function it_can_scope_unread_notifications()
    {
        Notification::factory()->count(3)->unread()->create();
        Notification::factory()->count(2)->read()->create();

        $unreadCount = Notification::unread()->count();
        $this->assertEquals(3, $unreadCount);
    }
}
