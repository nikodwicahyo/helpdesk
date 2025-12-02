<?php

namespace Database\Factories;

use App\Models\TicketDraft;
use App\Models\User;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketDraft>
 */
class TicketDraftFactory extends Factory
{
    protected $model = TicketDraft::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_nip' => User::factory(),
            'aplikasi_id' => Aplikasi::factory(),
            'kategori_masalah_id' => KategoriMasalah::factory(),
            'judul' => $this->faker->sentence(),
            'deskripsi' => $this->faker->paragraph(),
            'prioritas' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'lokasi' => $this->faker->optional(0.7)->address(),
            'draft_data' => [
                'aplikasi_id' => $this->faker->numberBetween(1, 10),
                'kategori_masalah_id' => $this->faker->numberBetween(1, 20),
                'judul' => $this->faker->sentence(),
                'deskripsi' => $this->faker->paragraph(3),
                'prioritas' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
                'lokasi' => $this->faker->optional(0.7)->address(),
                'form_metadata' => [
                    'step' => $this->faker->numberBetween(1, 3),
                    'validation_errors' => [],
                    'last_field_updated' => $this->faker->randomElement(['judul', 'deskripsi', 'prioritas', 'lokasi'])
                ]
            ],
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * Indicate that the draft is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate that the draft has no application or category.
     */
    public function withoutApplication(): static
    {
        return $this->state(fn (array $attributes) => [
            'aplikasi_id' => null,
            'kategori_masalah_id' => null,
            'draft_data' => array_merge($attributes['draft_data'] ?? [], [
                'aplikasi_id' => null,
                'kategori_masalah_id' => null,
            ]),
        ]);
    }

    /**
     * Indicate that the draft is for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_nip' => $user->nip,
        ]);
    }

    /**
     * Indicate that the draft is complete with all fields filled.
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'judul' => $this->faker->sentence(5),
            'deskripsi' => $this->faker->paragraph(5),
            'prioritas' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'lokasi' => $this->faker->address(),
            'draft_data' => [
                'aplikasi_id' => $this->faker->numberBetween(1, 10),
                'kategori_masalah_id' => $this->faker->numberBetween(1, 20),
                'judul' => $this->faker->sentence(5),
                'deskripsi' => $this->faker->paragraph(5),
                'prioritas' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
                'lokasi' => $this->faker->address(),
                'attachments' => [
                    [
                        'name' => 'screenshot.png',
                        'size' => 1024,
                        'type' => 'image/png'
                    ],
                    [
                        'name' => 'document.pdf',
                        'size' => 2048,
                        'type' => 'application/pdf'
                    ]
                ],
                'form_metadata' => [
                    'step' => 3,
                    'validation_errors' => [],
                    'last_field_updated' => 'deskripsi',
                    'time_spent' => $this->faker->numberBetween(60, 1800), // seconds
                    'field_changes' => $this->faker->numberBetween(5, 25)
                ]
            ],
        ]);
    }

    /**
     * Indicate that the draft is minimal with only basic fields.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'aplikasi_id' => null,
            'kategori_masalah_id' => null,
            'lokasi' => null,
            'draft_data' => [
                'aplikasi_id' => null,
                'kategori_masalah_id' => null,
                'judul' => $this->faker->sentence(3),
                'deskripsi' => $this->faker->sentence(10),
                'prioritas' => 'medium',
                'lokasi' => null,
                'form_metadata' => [
                    'step' => 1,
                    'validation_errors' => ['judul' => 'Required'],
                    'last_field_updated' => 'deskripsi'
                ]
            ],
        ]);
    }

    /**
     * Indicate that the draft is about to expire soon.
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addHours($this->faker->numberBetween(1, 23)),
        ]);
    }

    /**
     * Indicate that the draft has high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'prioritas' => 'high',
            'draft_data' => array_merge($attributes['draft_data'] ?? [], [
                'prioritas' => 'high',
            ]),
        ]);
    }

    /**
     * Indicate that the draft has urgent priority.
     */
    public function urgentPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'prioritas' => 'urgent',
            'draft_data' => array_merge($attributes['draft_data'] ?? [], [
                'prioritas' => 'urgent',
            ]),
        ]);
    }

    /**
     * Indicate that the draft has low priority.
     */
    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'prioritas' => 'low',
            'draft_data' => array_merge($attributes['draft_data'] ?? [], [
                'prioritas' => 'low',
            ]),
        ]);
    }

    /**
     * Indicate that the draft has validation errors.
     */
    public function withValidationErrors(): static
    {
        return $this->state(fn (array $attributes) => [
            'draft_data' => array_merge($attributes['draft_data'] ?? [], [
                'form_metadata' => [
                    'step' => 2,
                    'validation_errors' => [
                        'judul' => 'The title field is required.',
                        'deskripsi' => 'The description must be at least 10 characters.'
                    ],
                    'last_field_updated' => 'judul'
                ]
            ]),
        ]);
    }

    /**
     * Indicate that the draft is for a specific application.
     */
    public function forApplication(Aplikasi $aplikasi): static
    {
        return $this->state(fn (array $attributes) => [
            'aplikasi_id' => $aplikasi->id,
            'draft_data' => array_merge($attributes['draft_data'] ?? [], [
                'aplikasi_id' => $aplikasi->id,
            ]),
        ]);
    }

    /**
     * Indicate that the draft is for a specific category.
     */
    public function forCategory(KategoriMasalah $kategori): static
    {
        return $this->state(fn (array $attributes) => [
            'kategori_masalah_id' => $kategori->id,
            'draft_data' => array_merge($attributes['draft_data'] ?? [], [
                'kategori_masalah_id' => $kategori->id,
            ]),
        ]);
    }
}