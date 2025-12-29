<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\Teknisi;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TeknisiTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_teknisi()
    {
        $teknisi = Teknisi::factory()->create([
            'nip' => '199012345678',
            'name' => 'John Teknisi',
            'email' => 'teknisi@example.com',
        ]);

        $this->assertDatabaseHas('teknisis', [
            'nip' => '199012345678',
            'name' => 'John Teknisi',
        ]);
    }

    /** @test */
    public function it_can_check_if_is_teknisi()
    {
        $teknisi = Teknisi::factory()->create();
        
        $this->assertEquals('teknisi', $teknisi->role);
    }

    /** @test */
    public function it_returns_correct_skill_level()
    {
        $juniorTeknisi = Teknisi::factory()->create(['skill_level' => 'junior']);
        $this->assertEquals('junior', $juniorTeknisi->skill_level);

        $seniorTeknisi = Teknisi::factory()->create(['skill_level' => 'senior']);
        $this->assertEquals('senior', $seniorTeknisi->skill_level);
    }
}
