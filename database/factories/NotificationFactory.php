<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use App\Models\Teknisi;
use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        $notifiableType = $this->faker->randomElement(['user', 'teknisi', 'admin_helpdesk', 'admin_aplikasi']);
        $isRead = $this->faker->boolean(30);
        
        return [
            'notifiable_type' => $notifiableType,
            'notifiable_id' => $this->getNotifiableId($notifiableType),
            'ticket_id' => Ticket::factory(),
            'type' => $this->faker->randomElement([
                'ticket_created',
                'ticket_assigned',
                'status_updated',
                'comment_added',
                'ticket_resolved',
            ]),
            'title' => $this->faker->sentence(),
            'message' => $this->faker->paragraph(),
            'data' => json_encode([]),
            'read_at' => $isRead ? $this->faker->dateTimeBetween('-7 days', 'now') : null,
            'status' => $isRead ? 'read' : 'unread',
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Get notifiable ID (NIP) based on type.
     */
    private function getNotifiableId(string $type): string
    {
        return match ($type) {
            'user' => User::factory()->create()->nip,
            'teknisi' => Teknisi::factory()->create()->nip,
            'admin_helpdesk' => AdminHelpdesk::factory()->create()->nip,
            'admin_aplikasi' => AdminAplikasi::factory()->create()->nip,
            default => User::factory()->create()->nip,
        };
    }

    /**
     * Indicate that the notification is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unread',
            'read_at' => null,
        ]);
    }

    /**
     * Indicate that the notification is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'read',
            'read_at' => now(),
        ]);
    }

    /**
     * Indicate that the notification is for a user.
     */
    public function forUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'notifiable_type' => 'user',
            'notifiable_id' => User::factory(),
        ]);
    }

    /**
     * Indicate that the notification is for a teknisi.
     */
    public function forTeknisi(): static
    {
        return $this->state(fn (array $attributes) => [
            'notifiable_type' => 'teknisi',
            'notifiable_id' => Teknisi::factory(),
        ]);
    }
}
