<?php

namespace App\Services;

use App\Models\KnowledgeBaseArticle;
use App\Models\Teknisi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\KnowledgeBaseExport;

class KnowledgeBaseService
{
    /**
     * Create a new knowledge base article
     */
    public function createArticle(array $data, string $authorNip): KnowledgeBaseArticle
    {
        $articleData = [
            'author_nip' => $authorNip,
            'title' => $data['title'],
            'content' => $data['content'],
            'summary' => $data['summary'] ?? null,
            'kategori_masalah_id' => $data['kategori_masalah_id'] ?? null,
            'aplikasi_id' => $data['aplikasi_id'] ?? null,
            'tags' => $data['tags'] ?? [],
            'status' => $data['status'] ?? KnowledgeBaseArticle::STATUS_DRAFT,
            'is_featured' => $data['is_featured'] ?? false,
            'view_count' => 0,
            'helpful_count' => 0,
        ];

        return KnowledgeBaseArticle::create($articleData);
    }

    /**
     * Update an existing article
     */
    public function updateArticle(int $id, array $data): KnowledgeBaseArticle
    {
        $article = KnowledgeBaseArticle::findOrFail($id);

        $article->update([
            'title' => $data['title'],
            'content' => $data['content'],
            'summary' => $data['summary'] ?? $article->summary,
            'kategori_masalah_id' => $data['kategori_masalah_id'] ?? $article->kategori_masalah_id,
            'aplikasi_id' => $data['aplikasi_id'] ?? $article->aplikasi_id,
            'tags' => $data['tags'] ?? $article->tags,
            'status' => $data['status'] ?? $article->status,
            'is_featured' => $data['is_featured'] ?? $article->is_featured,
        ]);

        return $article->fresh();
    }

    /**
     * Delete an article
     */
    public function deleteArticle(int $id): bool
    {
        $article = KnowledgeBaseArticle::findOrFail($id);
        return $article->delete();
    }

    /**
     * Search and filter articles
     */
    public function searchArticles(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = KnowledgeBaseArticle::query()
            ->with(['author', 'kategoriMasalah', 'aplikasi']);

        // Search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Category filter
        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        // Author filter (for "My Articles")
        if (!empty($filters['author_nip'])) {
            $query->where('author_nip', $filters['author_nip']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else if (empty($filters['author_nip'])) {
            // Default: show only published articles (unless user is viewing their own)
            $query->published();
        }

        // Application filter
        if (!empty($filters['aplikasi_id'])) {
            $query->byApplication($filters['aplikasi_id']);
        }

        // Tags filter
        if (!empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : explode(',', $filters['tags']);
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', trim($tag));
                }
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'updated_at';
        switch ($sortBy) {
            case 'views':
                $query->orderBy('view_count', 'desc');
                break;
            case 'helpful':
                $query->orderBy('helpful_count', 'desc');
                break;
            case 'created_at':
                $query->orderBy('created_at', 'desc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'updated_at':
            default:
                $query->orderBy('updated_at', 'desc');
                break;
        }

        // Paginate results
        $articles = $query->paginate($perPage);

        // Transform articles for frontend
        $articles->getCollection()->transform(function ($article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'summary' => $article->summary,
                'excerpt' => $article->summary ?? substr(strip_tags($article->content), 0, 150) . '...',
                'content' => $article->content,
                'tags' => $article->tags ?? [],
                'status' => $article->status,
                'status_label' => $article->status_label,
                'is_featured' => $article->is_featured,
                'view_count' => $article->view_count ?? 0,
                'helpful_count' => $article->helpful_count ?? 0,
                'reading_time' => $article->reading_time,
                'author' => $article->author ? [
                    'nip' => $article->author->nip,
                    'name' => $article->author->name,
                ] : null,
                'kategori_masalah' => $article->kategoriMasalah ? [
                    'id' => $article->kategoriMasalah->id,
                    'name' => $article->kategoriMasalah->name,
                ] : null,
                'aplikasi' => $article->aplikasi ? [
                    'id' => $article->aplikasi->id,
                    'name' => $article->aplikasi->name,
                    'code' => $article->aplikasi->code,
                ] : null,
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
                'formatted_created_at' => $article->formatted_created_at,
                'formatted_updated_at' => $article->formatted_updated_at,
                'can_edit' => Auth::check() && $article->author_nip === Auth::user()->nip,
            ];
        });

        return $articles;
    }

    /**
     * Get popular tags
     */
    public function getPopularTags(int $limit = 20): array
    {
        $articles = KnowledgeBaseArticle::published()
            ->whereNotNull('tags')
            ->get(['tags']);

        $tagCounts = [];

        foreach ($articles as $article) {
            if ($article->tags && is_array($article->tags)) {
                foreach ($article->tags as $tag) {
                    $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                }
            }
        }

        arsort($tagCounts);

        return array_map(function ($tag, $count) {
            return [
                'name' => $tag,
                'count' => $count,
            ];
        }, array_keys(array_slice($tagCounts, 0, $limit, true)), array_slice($tagCounts, 0, $limit, true));
    }

    /**
     * Get statistics for a teknisi
     */
    public function getStatistics(string $teknisiNip): array
    {
        $totalArticles = KnowledgeBaseArticle::published()->count();

        $articlesThisMonth = KnowledgeBaseArticle::published()
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $myPublished = KnowledgeBaseArticle::where('author_nip', $teknisiNip)
            ->where('status', KnowledgeBaseArticle::STATUS_PUBLISHED)
            ->count();

        $myDrafts = KnowledgeBaseArticle::where('author_nip', $teknisiNip)
            ->where('status', KnowledgeBaseArticle::STATUS_DRAFT)
            ->count();

        return [
            'total_articles' => $totalArticles,
            'articles_this_month' => $articlesThisMonth,
            'my_published' => $myPublished,
            'my_drafts' => $myDrafts,
        ];
    }

    /**
     * Increment view count for an article
     */
    public function incrementViewCount(int $articleId, string $viewerNip): void
    {
        // Check if already viewed in this session (prevent duplicate counting)
        $alreadyViewed = DB::table('teknisi_knowledge_base_views')
            ->where('article_id', $articleId)
            ->where('viewer_nip', $viewerNip)
            ->where('viewed_at', '>=', Carbon::now()->subHours(24))
            ->exists();

        if (!$alreadyViewed) {
            // Record view
            DB::table('teknisi_knowledge_base_views')->insert([
                'article_id' => $articleId,
                'viewer_nip' => $viewerNip,
                'viewer_type' => 'teknisi',
                'viewed_at' => Carbon::now(),
            ]);

            // Increment article view count
            KnowledgeBaseArticle::where('id', $articleId)->increment('view_count');
        }
    }

    /**
     * Mark article as helpful
     */
    public function markAsHelpful(int $articleId, string $voterNip): array
    {
        // Check if already marked
        $alreadyMarked = DB::table('teknisi_knowledge_base_helpfuls')
            ->where('article_id', $articleId)
            ->where('voter_nip', $voterNip)
            ->exists();

        if ($alreadyMarked) {
            $article = KnowledgeBaseArticle::find($articleId);
            return [
                'already_marked' => true,
                'helpful_count' => $article->helpful_count ?? 0,
            ];
        }

        // Record helpful vote
        DB::table('teknisi_knowledge_base_helpfuls')->insert([
            'article_id' => $articleId,
            'voter_nip' => $voterNip,
            'voter_type' => 'teknisi',
            'voted_at' => Carbon::now(),
        ]);

        // Increment article helpful count
        $article = KnowledgeBaseArticle::where('id', $articleId)->first();
        $article->increment('helpful_count');

        return [
            'already_marked' => false,
            'helpful_count' => $article->fresh()->helpful_count,
        ];
    }

    /**
     * Export article to PDF
     */
    public function exportToPDF(int $articleId)
    {
        $article = KnowledgeBaseArticle::with(['author', 'kategoriMasalah', 'aplikasi'])
            ->findOrFail($articleId);

        $data = [
            'article' => $article,
            'generated_at' => Carbon::now()->format('d M Y H:i'),
        ];

        // Use dompdf or snappy based on configuration
        $pdf = Pdf::loadView('pdf.knowledge-base-article', $data);

        return $pdf;
    }

    /**
     * Export articles to Excel
     */
    public function exportBulkToExcel(array $filters)
    {
        // Get all articles based on filters (without pagination)
        $query = KnowledgeBaseArticle::query()
            ->with(['author', 'kategoriMasalah', 'aplikasi']);

        // Apply same filters as search
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->published();
        }

        if (!empty($filters['aplikasi_id'])) {
            $query->byApplication($filters['aplikasi_id']);
        }

        if (!empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : explode(',', $filters['tags']);
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', trim($tag));
                }
            });
        }

        $articles = $query->orderBy('updated_at', 'desc')->get();

        return new KnowledgeBaseExport($articles);
    }
}
