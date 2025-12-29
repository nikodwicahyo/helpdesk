<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\KategoriMasalah;
use App\Models\Aplikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;

class KategoriMasalahTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_category()
    {
        $aplikasi = Aplikasi::factory()->create();
        
        $category = KategoriMasalah::factory()->create([
            'aplikasi_id' => $aplikasi->id,
            'name' => 'Login Issue',
            'priority' => 'high',
            'status' => 'active'
        ]);

        $this->assertDatabaseHas('kategori_masalahs', [
            'name' => 'Login Issue',
            'priority' => 'high',
            'aplikasi_id' => $aplikasi->id
        ]);
    }

    /** @test */
    public function it_belongs_to_an_aplikasi()
    {
        $aplikasi = Aplikasi::factory()->create();
        $category = KategoriMasalah::factory()->create(['aplikasi_id' => $aplikasi->id]);

        $this->assertEquals($aplikasi->id, $category->aplikasi->id);
    }

    /** @test */
    public function it_can_have_parent_category()
    {
        $parent = KategoriMasalah::factory()->create();
        $child = KategoriMasalah::factory()->create(['parent_id' => $parent->id]);

        $this->assertEquals($parent->id, $child->parent->id);
    }

    /** @test */
    public function it_can_have_children_categories()
    {
        $parent = KategoriMasalah::factory()->create();
        $child1 = KategoriMasalah::factory()->create(['parent_id' => $parent->id]);
        $child2 = KategoriMasalah::factory()->create(['parent_id' => $parent->id]);

        $this->assertEquals(2, $parent->children->count());
    }

    /** @test */
    public function it_can_scope_active_categories()
    {
        KategoriMasalah::factory()->count(3)->create(['status' => 'active']);
        KategoriMasalah::factory()->count(2)->create(['status' => 'inactive']);

        $activeCategories = KategoriMasalah::where('status', 'active')->get();
        $this->assertEquals(3, $activeCategories->count());
    }
}
