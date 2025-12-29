<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\KnowledgeBaseArticle;
use App\Models\Teknisi;
use App\Models\KategoriMasalah;
use App\Models\Aplikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;

class KnowledgeBaseArticleTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_knowledge_base_article()
    {
        $teknisi = Teknisi::factory()->create();
        $kategori = KategoriMasalah::factory()->create();

        $article = KnowledgeBaseArticle::create([
            'author_nip' => $teknisi->nip,
            'title' => 'How to Reset Password',
            'content' => 'Step 1: Click forgot password...',
            'kategori_masalah_id' => $kategori->id,
            'status' => KnowledgeBaseArticle::STATUS_PUBLISHED,
        ]);

        $this->assertDatabaseHas('knowledge_base_articles', [
            'title' => 'How to Reset Password',
            'status' => KnowledgeBaseArticle::STATUS_PUBLISHED,
        ]);
    }

    /** @test */
    public function it_can_create_a_draft_article()
    {
        $teknisi = Teknisi::factory()->create();

        $article = KnowledgeBaseArticle::create([
            'author_nip' => $teknisi->nip,
            'title' => 'Draft Article',
            'content' => 'Draft content',
            'status' => KnowledgeBaseArticle::STATUS_DRAFT,
        ]);

        $this->assertTrue($article->status === KnowledgeBaseArticle::STATUS_DRAFT);
    }

    /** @test */
    public function it_can_publish_an_article()
    {
        $article = KnowledgeBaseArticle::factory()->create([
            'status' => KnowledgeBaseArticle::STATUS_DRAFT,
        ]);

        $article->update(['status' => KnowledgeBaseArticle::STATUS_PUBLISHED]);

        $this->assertEquals(KnowledgeBaseArticle::STATUS_PUBLISHED, $article->fresh()->status);
    }

    /** @test */
    public function it_can_archive_an_article()
    {
        $article = KnowledgeBaseArticle::factory()->create([
            'status' => KnowledgeBaseArticle::STATUS_PUBLISHED,
        ]);

        $article->update(['status' => KnowledgeBaseArticle::STATUS_ARCHIVED]);

        $this->assertEquals(KnowledgeBaseArticle::STATUS_ARCHIVED, $article->fresh()->status);
    }

    /** @test */
    public function it_has_author_relationship()
    {
        $teknisi = Teknisi::factory()->create();
        $article = KnowledgeBaseArticle::factory()->create([
            'author_nip' => $teknisi->nip,
        ]);

        $this->assertEquals($teknisi->nip, $article->author->nip);
    }

    /** @test */
    public function it_has_kategori_masalah_relationship()
    {
        $kategori = KategoriMasalah::factory()->create();
        $article = KnowledgeBaseArticle::factory()->create([
            'kategori_masalah_id' => $kategori->id,
        ]);

        $this->assertEquals($kategori->id, $article->kategoriMasalah->id);
    }

    /** @test */
    public function it_has_aplikasi_relationship()
    {
        $aplikasi = Aplikasi::factory()->create();
        $article = KnowledgeBaseArticle::factory()->create([
            'aplikasi_id' => $aplikasi->id,
        ]);

        $this->assertEquals($aplikasi->id, $article->aplikasi->id);
    }

    /** @test */
    public function it_can_store_tags_as_array()
    {
        $tags = ['password', 'reset', 'account'];

        $article = KnowledgeBaseArticle::create([
            'author_nip' => Teknisi::factory()->create()->nip,
            'title' => 'Article with tags',
            'content' => 'Content',
            'tags' => $tags,
            'status' => KnowledgeBaseArticle::STATUS_PUBLISHED,
        ]);

        $this->assertEquals($tags, $article->tags);
    }

    /** @test */
    public function it_starts_with_zero_view_count()
    {
        $article = KnowledgeBaseArticle::factory()->create();

        $this->assertEquals(0, $article->view_count);
    }

    /** @test */
    public function it_starts_with_zero_helpful_count()
    {
        $article = KnowledgeBaseArticle::factory()->create();

        $this->assertEquals(0, $article->helpful_count);
    }

    /** @test */
    public function it_can_track_view_count()
    {
        $article = KnowledgeBaseArticle::factory()->create();

        $article->increment('view_count');
        $article->increment('view_count');

        $this->assertEquals(2, $article->fresh()->view_count);
    }

    /** @test */
    public function it_can_track_helpful_count()
    {
        $article = KnowledgeBaseArticle::factory()->create();

        $article->increment('helpful_count');

        $this->assertEquals(1, $article->fresh()->helpful_count);
    }

    /** @test */
    public function it_can_be_marked_as_featured()
    {
        $article = KnowledgeBaseArticle::factory()->create([
            'is_featured' => false,
        ]);

        $article->update(['is_featured' => true]);

        $this->assertTrue($article->fresh()->is_featured);
    }

    /** @test */
    public function it_can_soft_delete_an_article()
    {
        $article = KnowledgeBaseArticle::factory()->create();
        $articleId = $article->id;

        $article->delete();

        $this->assertSoftDeleted('knowledge_base_articles', ['id' => $articleId]);
    }

    /** @test */
    public function it_can_restore_a_deleted_article()
    {
        $article = KnowledgeBaseArticle::factory()->create();

        $article->delete();
        $article->restore();

        $this->assertNull($article->fresh()->deleted_at);
    }

    /** @test */
    public function it_only_retrieves_non_deleted_articles_by_default()
    {
        KnowledgeBaseArticle::factory()->count(3)->create();
        $article = KnowledgeBaseArticle::factory()->create();
        $article->delete();

        $articles = KnowledgeBaseArticle::all();

        $this->assertEquals(3, $articles->count());
    }

    /** @test */
    public function it_can_retrieve_only_deleted_articles()
    {
        $article1 = KnowledgeBaseArticle::factory()->create();
        $article2 = KnowledgeBaseArticle::factory()->create();
        $article1->delete();
        $article2->delete();

        $deletedArticles = KnowledgeBaseArticle::onlyTrashed()->get();

        $this->assertEquals(2, $deletedArticles->count());
    }

    /** @test */
    public function it_can_store_summary()
    {
        $article = KnowledgeBaseArticle::create([
            'author_nip' => Teknisi::factory()->create()->nip,
            'title' => 'Article Title',
            'content' => 'Detailed content here',
            'summary' => 'Brief summary of the article',
            'status' => KnowledgeBaseArticle::STATUS_PUBLISHED,
        ]);

        $this->assertEquals('Brief summary of the article', $article->summary);
    }

    /** @test */
    public function it_casts_tags_to_array()
    {
        $tags = ['tag1', 'tag2', 'tag3'];
        $article = KnowledgeBaseArticle::factory()->create([
            'tags' => $tags,
        ]);

        $this->assertIsArray($article->tags);
        $this->assertEquals($tags, $article->tags);
    }

    /** @test */
    public function it_casts_timestamps_correctly()
    {
        $article = KnowledgeBaseArticle::factory()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $article->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $article->updated_at);
    }
}
