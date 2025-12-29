<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\Teknisi;
use App\Models\AdminHelpdesk;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['open', 'assigned', 'in_progress', 'waiting_user', 'resolved', 'closed']);
        $createdAt = $this->faker->dateTimeBetween('-30 days', 'now');
        
        return [
            'ticket_number' => 'TKT-' . date('Ymd', $createdAt->getTimestamp()) . '-' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'user_nip' => User::factory(),
            'aplikasi_id' => Aplikasi::factory(),
            'kategori_masalah_id' => KategoriMasalah::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(3),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => $status,
            'assigned_teknisi_nip' => in_array($status, ['assigned', 'in_progress', 'waiting_user', 'resolved', 'closed']) ? Teknisi::factory() : null,
            'assigned_by_nip' => in_array($status, ['assigned', 'in_progress', 'waiting_user', 'resolved', 'closed']) ? AdminHelpdesk::factory() : null,
            'location' => $this->faker->randomElement(['Lantai 1', 'Lantai 2', 'Lantai 3', 'Ruang Server', 'Kantor Pusat']),
            'attachments' => null,
            'screenshot' => null,
            'resolved_at' => in_array($status, ['resolved', 'closed']) ? $this->faker->dateTimeBetween($createdAt, 'now') : null,
            'closed_at' => $status === 'closed' ? $this->faker->dateTimeBetween($createdAt, 'now') : null,
            'user_rating' => $status === 'closed' ? $this->faker->numberBetween(1, 5) : null,
            'user_feedback' => $status === 'closed' ? $this->faker->optional()->sentence() : null,
            'created_at' => $createdAt,
            'updated_at' => $this->faker->dateTimeBetween($createdAt, 'now'),
        ];
    }

    /**
     * Indicate that the ticket is open (unassigned).
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'assigned_teknisi_nip' => null,
            'assigned_by_nip' => null,

            'resolved_at' => null,
            'closed_at' => null,
            'user_rating' => null,
            'user_feedback' => null,
        ]);
    }

    /**
     * Indicate that the ticket is assigned.
     */
    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'assigned',
            'assigned_teknisi_nip' => Teknisi::factory(),
            'assigned_by_nip' => AdminHelpdesk::factory(),

        ]);
    }

    /**
     * Indicate that the ticket is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'assigned_teknisi_nip' => Teknisi::factory(),
            'assigned_by_nip' => AdminHelpdesk::factory(),

        ]);
    }

    /**
     * Indicate that the ticket is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'assigned_teknisi_nip' => Teknisi::factory(),
            'assigned_by_nip' => AdminHelpdesk::factory(),

            'resolved_at' => now(),
        ]);
    }

    /**
     * Indicate that the ticket is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'assigned_teknisi_nip' => Teknisi::factory(),
            'assigned_by_nip' => AdminHelpdesk::factory(),

            'resolved_at' => now()->subDay(),
            'closed_at' => now(),
            'user_rating' => 5,
            'user_feedback' => 'Excellent service!',
        ]);
    }

    /**
     * Indicate that the ticket is urgent.
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
        ]);
    }

    /**
     * Indicate that the ticket is high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }

    /**
     * Indicate that the ticket has attachments.
     */
    public function withAttachments(): static
    {
        return $this->state(fn (array $attributes) => [
            'attachments' => json_encode([
                'screenshot.png',
                'error_log.txt',
            ]),
        ]);
    }
}
