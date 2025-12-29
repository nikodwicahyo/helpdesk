<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AplikasiTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_an_aplikasi()
    {
        $aplikasi = Aplikasi::factory()->create([
            'name' => 'Test Application',
            'code' => 'APP-001',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('aplikasis', [
            'name' => 'Test Application',
            'code' => 'APP-001',
        ]);
    }

    /** @test */
    public function it_has_kategori_masalahs_relationship()
    {
        $aplikasi = Aplikasi::factory()->create();
        $kategori = KategoriMasalah::factory()->create(['aplikasi_id' => $aplikasi->id]);

        $this->assertTrue($aplikasi->kategoriMasalahs->contains($kategori));
    }

    /** @test */
    public function it_has_tickets_relationship()
    {
        $aplikasi = Aplikasi::factory()->create();
        $ticket = Ticket::factory()->create(['aplikasi_id' => $aplikasi->id]);

        $this->assertTrue($aplikasi->tickets->contains($ticket));
    }
}
