<?php

namespace Tests\Feature\Teknisi;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\Teknisi;

class TeknisiDashboardTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $teknisi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teknisi = Teknisi::factory()->create();
    }

    /** @test */
    public function teknisi_can_view_dashboard()
    {
        $this->actingAs($this->teknisi);

        $response = $this->get('/teknisi/dashboard');

        $response->assertStatus(200);
    }
}
