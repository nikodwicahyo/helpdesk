<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\TicketDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketDraftTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $draft = TicketDraft::factory()->create(['user_nip' => $user->nip]);

        $this->assertInstanceOf(User::class, $draft->user);
        $this->assertEquals($user->nip, $draft->user->nip);
    }

    /** @test */
    public function it_casts_draft_data_to_array()
    {
        $draft = TicketDraft::factory()->create([
            'draft_data' => ['title' => 'Test Draft', 'priority' => 'high']
        ]);

        $this->assertIsArray($draft->draft_data);
        $this->assertEquals('Test Draft', $draft->draft_data['title']);
    }
}
