<?php

namespace Tests\Feature\Teknisi;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;
use App\Models\Teknisi;

class TeknisiKnowledgeBaseTest extends DatabaseTestCase
{
    use RefreshDatabase;

    protected $teknisi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teknisi = Teknisi::factory()->create();
    }

    /** @test */
    public function teknisi_can_view_knowledge_base()
    {
        $this->actingAs($this->teknisi);

        $response = $this->get('/teknisi/knowledge-base');

        $response->assertStatus(200);
    }
}
