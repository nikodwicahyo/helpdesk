<?php

namespace Database\Factories;

use App\Models\TicketComment;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Teknisi;
use App\Models\AdminHelpdesk;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketCommentFactory extends Factory
{
    protected $model = TicketComment::class;

    public function definition(): array
    {
        $commenterType = $this->faker->randomElement(['user', 'teknisi', 'admin_helpdesk']);
        
        return [
            'ticket_id' => Ticket::factory(),
            'commenter_type' => $commenterType,
            'commenter_nip' => $this->getCommenterNip($commenterType),
            'comment' => $this->faker->paragraph(),
            'attachments' => null,
            'is_internal' => $commenterType !== 'user' ? $this->faker->boolean(30) : false,
            'created_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Get commenter ID based on type.
     */
    protected function getCommenterNip(string $type): string
    {
        return match($type) {
            'user' => User::factory()->create()->nip,
            'teknisi' => Teknisi::factory()->create()->nip,
            'admin_helpdesk' => AdminHelpdesk::factory()->create()->nip,
            default => '123456789',
        };
    }

    /**
     * Indicate that the comment is from a user.
     */
    public function fromUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'commenter_type' => 'user',
            'commenter_nip' => User::factory()->create()->nip,
            'is_internal' => false,
        ]);
    }

    /**
     * Indicate that the comment is from a teknisi.
     */
    public function fromTeknisi(): static
    {
        return $this->state(fn (array $attributes) => [
            'commenter_type' => 'teknisi',
            'commenter_nip' => Teknisi::factory()->create()->nip,
        ]);
    }

    /**
     * Indicate that the comment is internal.
     */
    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_internal' => true,
        ]);
    }

    /**
     * Indicate that the comment has attachments.
     */
    public function withAttachments(): static
    {
        return $this->state(fn (array $attributes) => [
            'attachments' => [
                'solution_screenshot.png',
            ],
        ]);
    }
}
