<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Display the search page
     */
    public function index(): Response
    {
        return Inertia::render('Search/Index', [
            'filterOptions' => $this->searchService->getFilterOptions(),
            'popularSearches' => $this->searchService->getPopularSearchTerms(),
            'searchHistory' => $this->searchService->getSearchHistory(5),
            'savedSearches' => $this->searchService->getSavedSearches(),
        ]);
    }

    /**
     * Perform search
     */
    public function search(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'query' => 'nullable|string|max:255',
            'status' => 'nullable|array',
            'status.*' => 'string|in:open,in_progress,waiting_response,resolved,closed',
            'priority' => 'nullable|array',
            'priority.*' => 'string|in:low,medium,high,urgent',
            'aplikasi_id' => 'nullable|integer|exists:aplikasis,id',
            'kategori_masalah_id' => 'nullable|integer|exists:kategori_masalahs,id',
            'user_nip' => 'nullable|string|exists:users,nip',
            'assigned_teknisi_nip' => 'nullable|string|exists:teknisis,nip',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'due_date_from' => 'nullable|date',
            'due_date_to' => 'nullable|date|after_or_equal:due_date_from',
            'is_escalated' => 'nullable|boolean',
            'has_attachments' => 'nullable|boolean',
            'has_rating' => 'nullable|boolean',
            'is_overdue' => 'nullable|boolean',
            'is_unassigned' => 'nullable|boolean',
            'sort' => 'nullable|string|in:created_at,updated_at,priority,status,ticket_number,due_date',
            'direction' => 'nullable|string|in:asc,desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // Set default values
        $filters['page'] = $filters['page'] ?? 1;
        $filters['per_page'] = $filters['per_page'] ?? 15;
        $filters['sort'] = $filters['sort'] ?? 'created_at';
        $filters['direction'] = $filters['direction'] ?? 'desc';

        // Determine if we should use Scout (full-text search) or database search
        $useScout = !empty($filters['query']) && strlen($filters['query']) >= 2;

        // Save search history
        if (!empty($filters['query'])) {
            $this->searchService->saveSearchHistory($filters);
        }

        // Perform search
        $results = $this->searchService->searchTickets($filters, $filters['per_page'], $useScout);

        // Format the results for the frontend
        $tickets = $results['tickets']->through(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'title' => $ticket->title,
                'description' => substr(strip_tags($ticket->description), 0, 200) . '...',
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'priority_label' => $ticket->priority_label,
                'status_label' => $ticket->status_label,
                'priority_badge_color' => $ticket->priority_badge_color,
                'status_badge_color' => $ticket->status_badge_color,
                'user' => $ticket->user ? [
                    'nip' => $ticket->user->nip,
                    'name' => $ticket->user->name,
                    'email' => $ticket->user->email,
                ] : null,
                'aplikasi' => $ticket->aplikasi ? [
                    'id' => $ticket->aplikasi->id,
                    'name' => $ticket->aplikasi->name,
                ] : null,
                'kategori_masalah' => $ticket->kategoriMasalah ? [
                    'id' => $ticket->kategoriMasalah->id,
                    'nama_kategori' => $ticket->kategoriMasalah->nama_kategori,
                ] : null,
                'assigned_teknisi' => $ticket->assignedTeknisi ? [
                    'nip' => $ticket->assignedTeknisi->nip,
                    'name' => $ticket->assignedTeknisi->name,
                    'email' => $ticket->assignedTeknisi->email,
                ] : null,
                'created_at' => $ticket->formatted_created_at,
                'updated_at' => $ticket->updated_at->format('d M Y, H:i'),
                'due_date' => $ticket->formatted_due_date,
                'is_overdue' => $ticket->is_overdue,
                'is_within_sla' => $ticket->is_within_sla,
                'sla_status' => $ticket->sla_status,
                'is_escalated' => $ticket->is_escalated,
                'view_count' => $ticket->view_count,
                'attachments_count' => is_array($ticket->attachments) ? count($ticket->attachments) : 0,
            ];
        });

        return response()->json([
            'tickets' => $tickets,
            'pagination' => [
                'current_page' => $results['page'],
                'last_page' => $results['last_page'],
                'per_page' => $results['per_page'],
                'total' => $results['total'],
                'from' => ($results['page'] - 1) * $results['per_page'] + 1,
                'to' => min($results['page'] * $results['per_page'], $results['total']),
            ],
            'filters' => $results['filters'],
            'applied_filters_display' => $this->searchService->formatFiltersForDisplay($filters),
            'search_performed' => true,
        ]);
    }

    /**
     * Get search suggestions
     */
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
        ]);

        $suggestions = $this->searchService->getSearchSuggestions(
            $request->input('query'),
            $request->input('limit', 10)
        );

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Save a search
     */
    public function saveSearch(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'filters' => 'required|array',
        ]);

        $saved = $this->searchService->saveSearch(
            $request->input('name'),
            $request->input('filters')
        );

        if ($saved) {
            return response()->json([
                'message' => 'Search saved successfully',
                'saved_searches' => $this->searchService->getSavedSearches(),
            ]);
        }

        return response()->json([
            'message' => 'Failed to save search',
        ], 500);
    }

    /**
     * Delete a saved search
     */
    public function deleteSavedSearch(Request $request): JsonResponse
    {
        $request->validate([
            'search_id' => 'required|string',
        ]);

        $deleted = $this->searchService->deleteSavedSearch($request->input('search_id'));

        if ($deleted) {
            return response()->json([
                'message' => 'Saved search deleted successfully',
                'saved_searches' => $this->searchService->getSavedSearches(),
            ]);
        }

        return response()->json([
            'message' => 'Failed to delete saved search',
        ], 500);
    }

    /**
     * Clear search history
     */
    public function clearHistory(): JsonResponse
    {
        $this->searchService->clearSearchHistory();

        return response()->json([
            'message' => 'Search history cleared successfully',
            'search_history' => [],
        ]);
    }

    /**
     * Get search statistics
     */
    public function statistics(): JsonResponse
    {
        // You can expand this method with more detailed analytics
        return response()->json([
            'total_searches' => 0, // This would come from analytics table
            'popular_terms' => $this->searchService->getPopularSearchTerms(5),
            'saved_searches_count' => count($this->searchService->getSavedSearches()),
            'search_history_count' => count($this->searchService->getSearchHistory()),
        ]);
    }

    /**
     * Export search results
     */
    public function export(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'filters' => 'required|array',
            'format' => 'required|string|in:csv,xlsx,pdf',
        ]);

        // Set a higher per page limit for exports
        $filters['per_page'] = 1000;
        $filters['page'] = 1;

        $useScout = !empty($filters['query']) && strlen($filters['query']) >= 2;
        $results = $this->searchService->searchTickets($filters, $filters['per_page'], $useScout);

        // Format for export
        $exportData = $results['tickets']->map(function ($ticket) {
            return [
                'Ticket Number' => $ticket->ticket_number,
                'Title' => $ticket->title,
                'Description' => strip_tags($ticket->description),
                'Priority' => $ticket->priority_label,
                'Status' => $ticket->status_label,
                'User' => $ticket->user?->name,
                'User Email' => $ticket->user?->email,
                'Application' => $ticket->aplikasi?->name,
                'Category' => $ticket->kategoriMasalah?->nama_kategori,
                'Assigned Technician' => $ticket->assignedTeknisi?->name,
                'Created At' => $ticket->formatted_created_at,
                'Updated At' => $ticket->updated_at->format('d M Y, H:i'),
                'Due Date' => $ticket->formatted_due_date,
                'Is Overdue' => $ticket->is_overdue ? 'Yes' : 'No',
                'Is Escalated' => $ticket->is_escalated ? 'Yes' : 'No',
                'View Count' => $ticket->view_count,
            ];
        })->toArray();

        return response()->json([
            'data' => $exportData,
            'filename' => 'ticket_search_results_' . date('Y-m-d_H-i-s'),
            'total_records' => $results['total'],
        ]);
    }
}