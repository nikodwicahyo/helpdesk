<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_user()
    {
        $user = User::factory()->create([
            'nip' => '199012345678',
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'nip' => '199012345678',
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    /** @test */
    public function it_has_tickets_relationship()
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_nip' => $user->nip]);

        $this->assertTrue($user->tickets->contains($ticket));
    }

    /** @test */
    public function it_returns_correct_initials()
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        
        $this->assertEquals('JD', $user->initials);
    }

    /** @test */
    public function it_returns_display_name_with_nip()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'nip' => '199012345678',
        ]);

        $this->assertEquals('John Doe (199012345678)', $user->display_name);
    }

    /** @test */
    public function it_can_check_if_account_is_locked()
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->assertFalse($user->isLocked());
    }

    /** @test */
    public function it_returns_active_tickets_count()
    {
        $user = User::factory()->create();
        
        Ticket::factory()->count(3)->create([
            'user_nip' => $user->nip,
            'status' => 'open',
        ]);

        $this->assertEquals(3, $user->active_tickets_count);
    }
}
