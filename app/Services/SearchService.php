<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\Teknisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Laravel\Scout\Builder as ScoutBuilder;

class SearchService
{
    /**
     * Search tickets with advanced filters
     *
     * @param array $filters
     * @param int $perPage
     * @param bool $useScout
     * @return array
     */
    public function searchTickets(array $filters = [], int $perPage = 15, bool $useScout = true): array
    {
        $query = $useScout ? $this->buildScoutQuery($filters) : $this->buildDatabaseQuery($filters);

        // Apply pagination
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $perPage;

        if ($useScout) {
            $results = $query->paginate($perPage, 'page', $page);
        } else {
            $total = $query->count();
            $tickets = $query->offset($offset)->limit($perPage)->get();

            $results = new \Illuminate\Pagination\LengthAwarePaginator(
                $tickets,
                $total,
                $perPage,
                $page,
                ['path' => request()->url(), 'page' => $page]
            );
        }

        return [
            'tickets' => $results,
            'filters' => $filters,
            'total' => $results->total(),
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $results->lastPage(),
        ];
    }

    /**
     * Build Scout search query with filters
     *
     * @param array $filters
     * @return ScoutBuilder
     */
    protected function buildScoutQuery(array $filters): ScoutBuilder
    {
        $searchQuery = $filters['query'] ?? '';
        $query = Ticket::search($searchQuery);

        // Apply where clauses for exact matches
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['priority'])) {
            if (is_array($filters['priority'])) {
                $query->whereIn('priority', $filters['priority']);
            } else {
                $query->where('priority', $filters['priority']);
            }
        }

        if (!empty($filters['aplikasi_id'])) {
            $query->where('aplikasi_id', $filters['aplikasi_id']);
        }

        if (!empty($filters['kategori_masalah_id'])) {
            $query->where('kategori_masalah_id', $filters['kategori_masalah_id']);
        }

        if (!empty($filters['user_nip'])) {
            $query->where('user_nip', $filters['user_nip']);
        }

        if (!empty($filters['assigned_teknisi_nip'])) {
            $query->where('assigned_teknisi_nip', $filters['assigned_teknisi_nip']);
        }

        if (isset($filters['is_escalated'])) {
            $query->where('is_escalated', $filters['is_escalated']);
        }

        // Apply date range filters
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'])->timestamp);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay()->timestamp);
        }

        if (!empty($filters['due_date_from'])) {
            $query->where('due_date', '>=', Carbon::parse($filters['due_date_from'])->timestamp);
        }

        if (!empty($filters['due_date_to'])) {
            $query->where('due_date', '<=', Carbon::parse($filters['due_date_to'])->endOfDay()->timestamp);
        }

        // Apply sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';

        switch ($sortField) {
            case 'priority':
                $query->orderBy('priority', $sortDirection);
                break;
            case 'status':
                $query->orderBy('status', $sortDirection);
                break;
            case 'ticket_number':
                $query->orderBy('ticket_number', $sortDirection);
                break;
            case 'due_date':
                $query->orderBy('due_date', $sortDirection);
                break;
            case 'created_at':
            default:
                $query->orderBy('created_at', $sortDirection);
                break;
        }

        return $query;
    }

    /**
     * Build database query with filters (fallback when Scout is disabled)
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildDatabaseQuery(array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = Ticket::with(['user', 'aplikasi', 'kategoriMasalah', 'assignedTeknisi']);

        // Apply text search
        if (!empty($filters['query'])) {
            $searchTerm = $filters['query'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhere('ticket_number', 'like', "%{$searchTerm}%")
                  ->orWhere('resolution_notes', 'like', "%{$searchTerm}%")
                  ->orWhere('location', 'like', "%{$searchTerm}%")
                  ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                      $userQuery->where('name', 'like', "%{$searchTerm}%")
                               ->orWhere('email', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('aplikasi', function ($appQuery) use ($searchTerm) {
                      $appQuery->where('name', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('kategoriMasalah', function ($catQuery) use ($searchTerm) {
                      $catQuery->where('name', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('assignedTeknisi', function ($tekQuery) use ($searchTerm) {
                      $tekQuery->where('name', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('comments', function ($commentQuery) use ($searchTerm) {
                      $commentQuery->where('content', 'like', "%{$searchTerm}%")
                                   ->where('is_internal', false);
                  });
            });
        }

        // Apply status filter
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Apply priority filter
        if (!empty($filters['priority'])) {
            if (is_array($filters['priority'])) {
                $query->whereIn('priority', $filters['priority']);
            } else {
                $query->where('priority', $filters['priority']);
            }
        }

        // Apply aplikasi filter
        if (!empty($filters['aplikasi_id'])) {
            $query->where('aplikasi_id', $filters['aplikasi_id']);
        }

        // Apply kategori filter
        if (!empty($filters['kategori_masalah_id'])) {
            $query->where('kategori_masalah_id', $filters['kategori_masalah_id']);
        }

        // Apply user filter
        if (!empty($filters['user_nip'])) {
            $query->where('user_nip', $filters['user_nip']);
        }

        // Apply teknisi filter
        if (!empty($filters['assigned_teknisi_nip'])) {
            $query->where('assigned_teknisi_nip', $filters['assigned_teknisi_nip']);
        }

        // Apply escalation filter
        if (isset($filters['is_escalated'])) {
            $query->where('is_escalated', $filters['is_escalated']);
        }

        // Apply date range filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['due_date_from'])) {
            $query->whereDate('due_date', '>=', $filters['due_date_from']);
        }

        if (!empty($filters['due_date_to'])) {
            $query->whereDate('due_date', '<=', $filters['due_date_to']);
        }

        // Apply advanced filters
        if (!empty($filters['has_attachments'])) {
            $query->whereNotNull('attachments')->where('attachments', '!=', '[]');
        }

        if (!empty($filters['has_rating'])) {
            $query->whereNotNull('user_rating');
        }

        if (!empty($filters['is_overdue'])) {
            $query->whereNotNull('due_date')
                  ->where('due_date', '<', Carbon::now())
                  ->whereNotIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);
        }

        if (!empty($filters['is_unassigned'])) {
            $query->whereNull('assigned_teknisi_nip');
        }

        // Apply sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';

        switch ($sortField) {
            case 'priority':
                $query->orderByPriority($sortDirection);
                break;
            case 'ticket_number':
                $query->orderBy('ticket_number', $sortDirection);
                break;
            case 'due_date':
                $query->orderBy('due_date', $sortDirection);
                break;
            case 'updated_at':
                $query->orderBy('updated_at', $sortDirection);
                break;
            case 'created_at':
            default:
                $query->orderBy('created_at', $sortDirection);
                break;
        }

        return $query;
    }

    /**
     * Get search suggestions based on query
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function getSearchSuggestions(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $cacheKey = "search_suggestions_" . md5($query);

        return Cache::remember($cacheKey, 300, function () use ($query, $limit) {
            $suggestions = [];

            // Ticket number suggestions
            $ticketNumbers = Ticket::where('ticket_number', 'like', "%{$query}%")
                ->limit($limit / 2)
                ->pluck('ticket_number')
                ->toArray();

            // Title suggestions
            $titles = Ticket::where('title', 'like', "%{$query}%")
                ->limit($limit / 2)
                ->pluck('title')
                ->map(function ($title) {
                    // Limit title length for suggestions
                    return strlen($title) > 60 ? substr($title, 0, 60) . '...' : $title;
                })
                ->toArray();

            $suggestions = array_merge($ticketNumbers, $titles);

            // Remove duplicates and limit
            return array_slice(array_unique($suggestions), 0, $limit);
        });
    }

    /**
     * Get popular search terms
     *
     * @param int $limit
     * @return array
     */
    public function getPopularSearchTerms(int $limit = 10): array
    {
        return Cache::remember('popular_search_terms', 3600, function () use ($limit) {
            // This would typically come from a search analytics table
            // For now, return common terms based on ticket data
            return [
                'login',
                'password',
                'error',
                'sistem',
                'aplikasi',
                'email',
                'internet',
                'printer',
                'network',
                'software'
            ];
        });
    }

    /**
     * Get search filters options
     *
     * @return array
     */
    public function getFilterOptions(): array
    {
        return Cache::remember('search_filter_options', 1800, function () {
            return [
                'statuses' => [
                    ['value' => Ticket::STATUS_OPEN, 'label' => 'Open'],
                    ['value' => Ticket::STATUS_IN_PROGRESS, 'label' => 'In Progress'],
                    ['value' => Ticket::STATUS_WAITING_RESPONSE, 'label' => 'Waiting Response'],
                    ['value' => Ticket::STATUS_RESOLVED, 'label' => 'Resolved'],
                    ['value' => Ticket::STATUS_CLOSED, 'label' => 'Closed'],
                ],
                'priorities' => [
                    ['value' => Ticket::PRIORITY_LOW, 'label' => 'Low'],
                    ['value' => Ticket::PRIORITY_MEDIUM, 'label' => 'Medium'],
                    ['value' => Ticket::PRIORITY_HIGH, 'label' => 'High'],
                    ['value' => Ticket::PRIORITY_URGENT, 'label' => 'Urgent'],
                ],
                'aplikasis' => Aplikasi::orderBy('name')->get(['id', 'name'])->toArray(),
                'kategori_masalahs' => KategoriMasalah::with('aplikasi')
                    ->orderBy('name')
                    ->get(['id', 'name', 'aplikasi_id'])
                    ->toArray(),
                'users' => User::orderBy('name')->limit(100)->get(['nip', 'name', 'email'])->toArray(),
                'teknisis' => Teknisi::orderBy('name')->get(['nip', 'name', 'email'])->toArray(),
            ];
        });
    }

    /**
     * Save search history for authenticated user
     *
     * @param array $filters
     * @return void
     */
    public function saveSearchHistory(array $filters): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $searchData = [
            'query' => $filters['query'] ?? '',
            'filters' => $filters,
            'searched_at' => Carbon::now(),
            'user_type' => $this->getUserType(),
            'user_id' => $user->getAuthIdentifier(),
        ];

        // Store in cache for recent searches (you might want to move this to a database table)
        $cacheKey = "search_history_{$this->getUserType()}_{$user->getAuthIdentifier()}";
        $history = Cache::get($cacheKey, []);

        // Add new search to beginning of array
        array_unshift($history, $searchData);

        // Keep only last 20 searches
        $history = array_slice($history, 0, 20);

        Cache::put($cacheKey, $history, 86400); // 24 hours
    }

    /**
     * Get search history for authenticated user
     *
     * @param int $limit
     * @return array
     */
    public function getSearchHistory(int $limit = 10): array
    {
        if (!Auth::check()) {
            return [];
        }

        $user = Auth::user();
        $cacheKey = "search_history_{$this->getUserType()}_{$user->getAuthIdentifier()}";
        $history = Cache::get($cacheKey, []);

        return array_slice($history, 0, $limit);
    }

    /**
     * Clear search history for authenticated user
     *
     * @return void
     */
    public function clearSearchHistory(): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $cacheKey = "search_history_{$this->getUserType()}_{$user->getAuthIdentifier()}";
        Cache::forget($cacheKey);
    }

    /**
     * Get saved searches for authenticated user
     *
     * @return array
     */
    public function getSavedSearches(): array
    {
        if (!Auth::check()) {
            return [];
        }

        $user = Auth::user();
        $cacheKey = "saved_searches_{$this->getUserType()}_{$user->getAuthIdentifier()}";
        return Cache::get($cacheKey, []);
    }

    /**
     * Save search for authenticated user
     *
     * @param string $name
     * @param array $filters
     * @return bool
     */
    public function saveSearch(string $name, array $filters): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        $cacheKey = "saved_searches_{$this->getUserType()}_{$user->getAuthIdentifier()}";
        $savedSearches = Cache::get($cacheKey, []);

        $savedSearches[] = [
            'id' => uniqid(),
            'name' => $name,
            'filters' => $filters,
            'created_at' => Carbon::now(),
        ];

        // Keep only last 20 saved searches
        $savedSearches = array_slice($savedSearches, -20);

        Cache::put($cacheKey, $savedSearches, 604800); // 7 days

        return true;
    }

    /**
     * Delete saved search
     *
     * @param string $searchId
     * @return bool
     */
    public function deleteSavedSearch(string $searchId): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        $cacheKey = "saved_searches_{$this->getUserType()}_{$user->getAuthIdentifier()}";
        $savedSearches = Cache::get($cacheKey, []);

        $savedSearches = array_filter($savedSearches, function ($search) use ($searchId) {
            return $search['id'] !== $searchId;
        });

        Cache::put($cacheKey, array_values($savedSearches), 604800);

        return true;
    }

    /**
     * Get the current user type
     *
     * @return string
     */
    protected function getUserType(): string
    {
        if (Auth::guard('admin_helpdesk')->check()) {
            return 'admin_helpdesk';
        } elseif (Auth::guard('admin_aplikasi')->check()) {
            return 'admin_aplikasi';
        } elseif (Auth::guard('teknisi')->check()) {
            return 'teknisi';
        } elseif (Auth::guard('web')->check()) {
            return 'user';
        }

        return 'guest';
    }

    /**
     * Format filters for display
     *
     * @param array $filters
     * @return array
     */
    public function formatFiltersForDisplay(array $filters): array
    {
        $formatted = [];

        if (!empty($filters['query'])) {
            $formatted[] = ['label' => 'Search', 'value' => $filters['query']];
        }

        if (!empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
            $formatted[] = ['label' => 'Status', 'value' => implode(', ', $statuses)];
        }

        if (!empty($filters['priority'])) {
            $priorities = is_array($filters['priority']) ? $filters['priority'] : [$filters['priority']];
            $formatted[] = ['label' => 'Priority', 'value' => implode(', ', $priorities)];
        }

        if (!empty($filters['aplikasi_id'])) {
            $aplikasi = Aplikasi::find($filters['aplikasi_id']);
            $formatted[] = ['label' => 'Application', 'value' => $aplikasi?->name];
        }

        if (!empty($filters['kategori_masalah_id'])) {
            $kategori = KategoriMasalah::find($filters['kategori_masalah_id']);
            $formatted[] = ['label' => 'Category', 'value' => $kategori?->nama_kategori];
        }

        if (!empty($filters['user_nip'])) {
            $user = User::where('nip', $filters['user_nip'])->first();
            $formatted[] = ['label' => 'User', 'value' => $user?->name ?? $filters['user_nip']];
        }

        if (!empty($filters['assigned_teknisi_nip'])) {
            $teknisi = Teknisi::where('nip', $filters['assigned_teknisi_nip'])->first();
            $formatted[] = ['label' => 'Technician', 'value' => $teknisi?->name ?? $filters['assigned_teknisi_nip']];
        }

        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $dateRange = trim(($filters['date_from'] ?? '') . ' - ' . ($filters['date_to'] ?? ''), ' -');
            $formatted[] = ['label' => 'Date Range', 'value' => $dateRange ?: 'Any'];
        }

        if (isset($filters['is_escalated'])) {
            $formatted[] = ['label' => 'Escalated', 'value' => $filters['is_escalated'] ? 'Yes' : 'No'];
        }

        return $formatted;
    }
}