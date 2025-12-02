<?php

namespace App\Http\Controllers\Teknisi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\KnowledgeBaseArticle;
use App\Models\Teknisi;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Services\KnowledgeBaseService;
use Carbon\Carbon;

class KnowledgeBaseController extends Controller
{
    protected $knowledgeBaseService;

    public function __construct(KnowledgeBaseService $knowledgeBaseService)
    {
        $this->knowledgeBaseService = $knowledgeBaseService;
    }

    /**
     * Display knowledge base articles with filtering and search
     */
    public function index(Request $request)
    {
        $teknisi = Auth::user();

        if (!$teknisi instanceof Teknisi) {
            abort(403, 'Access denied. Teknisi role required.');
        }

        // Get filters from request
        $filters = $request->only([
            'search', 'category', 'status', 'aplikasi_id', 
            'priority', 'sort_by', 'tags', 'per_page', 'my_articles'
        ]);

        $perPage = $request->get('per_page', 12);

        // Handle "My Articles" filter
        if ($request->boolean('my_articles')) {
            $filters['author_nip'] = $teknisi->nip;
        }

        // Get filtered articles
        $articles = $this->knowledgeBaseService->searchArticles($filters, $perPage);

        // Get statistics
        $stats = $this->knowledgeBaseService->getStatistics($teknisi->nip);

        // Get popular tags
        $popularTags = $this->knowledgeBaseService->getPopularTags(20);

        // Get filter options
        $categories = KategoriMasalah::active()->orderBy('name')->get(['id', 'name', 'aplikasi_id']);
        $applications = Aplikasi::active()->orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('Teknisi/KnowledgeBase', [
            'articles' => $articles,
            'stats' => $stats,
            'popularTags' => $popularTags,
            'categories' => $categories,
            'applications' => $applications,
            'filters' => $filters,
        ]);
    }

    /**
     * Display a specific article
     */
    public function show($id)
    {
        $teknisi = Auth::user();

        if (!$teknisi instanceof Teknisi) {
            abort(403, 'Access denied. Teknisi role required.');
        }

        $article = KnowledgeBaseArticle::with(['author', 'kategoriMasalah', 'aplikasi'])
            ->findOrFail($id);

        // Check if article is published or belongs to current user
        if (!$article->isPublished() && $article->author_nip !== $teknisi->nip) {
            abort(403, 'Access denied. You can only view published articles or your own drafts.');
        }

        // Increment view count (session-based to prevent duplicate counting)
        $sessionKey = "kb_article_viewed_{$id}_{$teknisi->nip}";
        if (!session()->has($sessionKey)) {
            $this->knowledgeBaseService->incrementViewCount($id, $teknisi->nip);
            session()->put($sessionKey, true);
        }

        // Check if user has marked as helpful
        $hasMarkedHelpful = DB::table('teknisi_knowledge_base_helpfuls')
            ->where('article_id', $id)
            ->where('voter_nip', $teknisi->nip)
            ->exists();

        return Inertia::render('Teknisi/KnowledgeBaseArticle', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'content' => $article->content,
                'summary' => $article->summary,
                'tags' => $article->tags ?? [],
                'status' => $article->status,
                'status_label' => $article->status_label,
                'is_featured' => $article->is_featured,
                'view_count' => $article->view_count,
                'helpful_count' => $article->helpful_count,
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
                'formatted_created_at' => $article->formatted_created_at,
                'updated_at' => $article->updated_at,
                'formatted_updated_at' => $article->formatted_updated_at,
                'can_edit' => $article->author_nip === $teknisi->nip,
                'has_marked_helpful' => $hasMarkedHelpful,
            ],
        ]);
    }

    /**
     * Store a new article
     */
    public function store(Request $request)
    {
        $teknisi = Auth::user();

        if (!$teknisi instanceof Teknisi) {
            return response()->json([
                'success' => false,
                'errors' => ['Access denied. Teknisi role required.'],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'summary' => 'nullable|string|max:500',
            'kategori_masalah_id' => 'nullable|exists:kategori_masalahs,id',
            'aplikasi_id' => 'nullable|exists:aplikasis,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            $article = $this->knowledgeBaseService->createArticle($request->all(), $teknisi->nip);

            return response()->json([
                'success' => true,
                'message' => 'Article created successfully',
                'article' => [
                    'id' => $article->id,
                    'title' => $article->title,
                    'status' => $article->status,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating knowledge base article', [
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'errors' => ['Failed to create article. Please try again.'],
            ], 500);
        }
    }

    /**
     * Update an existing article
     */
    public function update(Request $request, $id)
    {
        $teknisi = Auth::user();

        if (!$teknisi instanceof Teknisi) {
            return response()->json([
                'success' => false,
                'errors' => ['Access denied. Teknisi role required.'],
            ], 403);
        }

        $article = KnowledgeBaseArticle::findOrFail($id);

        // Check ownership
        if ($article->author_nip !== $teknisi->nip) {
            return response()->json([
                'success' => false,
                'errors' => ['You can only edit your own articles.'],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'summary' => 'nullable|string|max:500',
            'kategori_masalah_id' => 'nullable|exists:kategori_masalahs,id',
            'aplikasi_id' => 'nullable|exists:aplikasis,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'status' => 'required|in:draft,published,archived',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            $updatedArticle = $this->knowledgeBaseService->updateArticle($id, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Article updated successfully',
                'article' => [
                    'id' => $updatedArticle->id,
                    'title' => $updatedArticle->title,
                    'status' => $updatedArticle->status,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating knowledge base article', [
                'article_id' => $id,
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'errors' => ['Failed to update article. Please try again.'],
            ], 500);
        }
    }

    /**
     * Delete an article
     */
    public function destroy($id)
    {
        $teknisi = Auth::user();

        if (!$teknisi instanceof Teknisi) {
            return response()->json([
                'success' => false,
                'errors' => ['Access denied. Teknisi role required.'],
            ], 403);
        }

        $article = KnowledgeBaseArticle::findOrFail($id);

        // Check ownership
        if ($article->author_nip !== $teknisi->nip) {
            return response()->json([
                'success' => false,
                'errors' => ['You can only delete your own articles.'],
            ], 403);
        }

        try {
            $this->knowledgeBaseService->deleteArticle($id);

            return response()->json([
                'success' => true,
                'message' => 'Article deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting knowledge base article', [
                'article_id' => $id,
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'errors' => ['Failed to delete article. Please try again.'],
            ], 500);
        }
    }

    /**
     * Search knowledge base articles
     */
    public function search(Request $request)
    {
        $teknisi = Auth::user();

        if (!$teknisi instanceof Teknisi) {
            return response()->json([
                'success' => false,
                'errors' => ['Access denied. Teknisi role required.'],
            ], 403);
        }

        $filters = $request->only([
            'search', 'category', 'status', 'aplikasi_id', 
            'priority', 'sort_by', 'tags', 'per_page'
        ]);

        $perPage = $request->get('per_page', 12);

        try {
            $articles = $this->knowledgeBaseService->searchArticles($filters, $perPage);

            return response()->json([
                'success' => true,
                'articles' => $articles,
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching knowledge base', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'errors' => ['Search failed. Please try again.'],
            ], 500);
        }
    }

    /**
     * Increment view count for an article
     */
    public function incrementViewCount($id)
    {
        $teknisi = Auth::user();

        if (!$teknisi instanceof Teknisi) {
            return response()->json([
                'success' => false,
                'errors' => ['Access denied.'],
            ], 403);
        }

        try {
            $this->knowledgeBaseService->incrementViewCount($id, $teknisi->nip);

            return response()->json([
                'success' => true,
                'message' => 'View counted',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to record view.'],
            ], 500);
        }
    }

    /**
     * Mark article as helpful
     */
    public function markAsHelpful($id)
    {
        $teknisi = Auth::user();

        if (!$teknisi instanceof Teknisi) {
            return response()->json([
                'success' => false,
                'errors' => ['Access denied.'],
            ], 403);
        }

        try {
            $result = $this->knowledgeBaseService->markAsHelpful($id, $teknisi->nip);

            if ($result['already_marked']) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already marked this article as helpful',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Marked as helpful',
                'helpful_count' => $result['helpful_count'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking article as helpful', [
                'article_id' => $id,
                'teknisi_nip' => $teknisi->nip,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'errors' => ['Failed to mark as helpful.'],
            ], 500);
        }
    }

    /**
     * Export a single article to PDF
     */
    public function exportArticle($id)
    {
        $teknisi = Auth::user();

        if (!$teknisi instanceof Teknisi) {
            abort(403, 'Access denied.');
        }

        try {
            $article = KnowledgeBaseArticle::with(['author', 'kategoriMasalah', 'aplikasi'])
                ->findOrFail($id);

            // Check if article is published or belongs to current user
            if (!$article->isPublished() && $article->author_nip !== $teknisi->nip) {
                abort(403, 'Access denied.');
            }

            $pdf = $this->knowledgeBaseService->exportToPDF($id);

            return $pdf->download("knowledge-base-{$article->id}-{$article->title}.pdf");
        } catch (\Exception $e) {
            Log::error('Error exporting article to PDF', [
                'article_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors(['Failed to export article. Please try again.']);
        }
    }

    /**
     * Export all articles (with filters) to Excel
     */
    public function exportAll(Request $request)
    {
        $teknisi = Auth::user();

        if (!$teknisi instanceof Teknisi) {
            abort(403, 'Access denied.');
        }

        try {
            $filters = $request->only([
                'search', 'category', 'status', 'aplikasi_id', 
                'priority', 'tags'
            ]);

            $export = $this->knowledgeBaseService->exportBulkToExcel($filters);

            $filename = 'knowledge-base-export-' . Carbon::now()->format('Y-m-d-His') . '.xlsx';

            return Excel::download($export, $filename);
        } catch (\Exception $e) {
            Log::error('Error exporting knowledge base to Excel', [
                'filters' => $filters ?? [],
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors(['Failed to export knowledge base. Please try again.']);
        }
    }
}
