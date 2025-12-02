<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\Ticket;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display available applications for users to create tickets.
     */
    public function applications(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $filters = $request->only(['search', 'status', 'sort_by', 'sort_direction']);

        // Set default sorting
        if (!isset($filters['sort_by'])) {
            $filters['sort_by'] = 'name';
            $filters['sort_direction'] = 'asc';
        }

        // Get active applications
        $applications = $this->getFilteredApplications($filters, 20);

        // Format applications for frontend
        $formattedApplications = $applications->getCollection()->map(function ($application) {
            return [
                'id' => $application->id,
                'name' => $application->name,
                'code' => $application->code,
                'description' => $application->description,
                'version' => $application->version,
                'status' => $application->status,
                'status_badge' => $application->status_badge,
                'total_tickets' => $application->tickets()->count(),
                'user_tickets' => $application->tickets()->where('user_nip', Auth::user()->nip)->count(),
                'open_tickets' => $application->tickets()->where('status', Ticket::STATUS_OPEN)->count(),
                'categories' => $application->kategoriMasalahs()
                    ->active()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                        ];
                    }),
                'admin_aplikasi' => $application->adminAplikasi ? [
                    'nip' => $application->adminAplikasi->nip,
                    'name' => $application->adminAplikasi->name,
                ] : null,
                'created_at' => $application->created_at,
                'formatted_created_at' => $application->created_at->format('d M Y'),
            ];
        });

        // Create new paginator with formatted data
        $formattedPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $formattedApplications,
            $applications->total(),
            $applications->perPage(),
            $applications->currentPage(),
            ['path' => $applications->path(), 'pageName' => 'page']
        );

        // Get user's recent tickets for quick access
        $recentTickets = $this->getUserRecentTickets($user);

        // Get popular applications (by ticket count)
        $popularApplications = $this->getPopularApplications();

        // Get application statistics
        $applicationStats = $this->getApplicationStatistics();

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        return Inertia::render('User/Applications/Index', [
            'applications' => $formattedPaginator,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'recentTickets' => $recentTickets,
            'popularApplications' => $popularApplications,
            'applicationStats' => $applicationStats,
        ]);
    }

    /**
     * Get filtered applications for users.
     */
    private function getFilteredApplications(array $filters, int $perPage = 20)
    {
        $query = Aplikasi::active()
            ->with(['kategoriMasalahs' => function ($query) {
                $query->active()->orderBy('name');
            }]);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('code', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDirection = $filters['sort_direction'] ?? 'asc';

        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            case 'code':
                $query->orderBy('code', $sortDirection);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortDirection);
                break;
            case 'total_tickets':
                $query->withCount('tickets')->orderBy('tickets_count', $sortDirection);
                break;
            default:
                $query->orderBy('name', 'asc');
                break;
        }

        return $query->paginate($perPage);
    }

    /**
     * Get user's recent tickets for quick access.
     */
    private function getUserRecentTickets($user, int $limit = 5): array
    {
        return $user->tickets()
            ->with(['aplikasi', 'kategoriMasalah', 'assignedTeknisi'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'status_label' => $ticket->status_label,
                    'priority' => $ticket->priority,
                    'priority_label' => $ticket->priority_label,
                    'aplikasi' => $ticket->aplikasi ? [
                        'name' => $ticket->aplikasi->name,
                        'code' => $ticket->aplikasi->code,
                    ] : null,
                    'kategori_masalah' => $ticket->kategoriMasalah ? [
                        'name' => $ticket->kategoriMasalah->name,
                    ] : null,
                    'assigned_teknisi' => $ticket->assignedTeknisi ? [
                        'name' => $ticket->assignedTeknisi->name,
                    ] : null,
                    'created_at' => $ticket->created_at,
                    'formatted_created_at' => $ticket->created_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Get popular applications by ticket count.
     */
    private function getPopularApplications(int $limit = 10): array
    {
        return Aplikasi::active()
            ->withCount('tickets')
            ->orderBy('tickets_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($application) {
                return [
                    'id' => $application->id,
                    'name' => $application->name,
                    'code' => $application->code,
                    'ticket_count' => $application->tickets_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get application statistics for users.
     */
    private function getApplicationStatistics(): array
    {
        $totalApplications = Aplikasi::active()->count();
        $totalCategories = KategoriMasalah::active()->count();
        $totalTickets = Ticket::count();
        $openTickets = Ticket::where('status', Ticket::STATUS_OPEN)->count();

        return [
            'total_applications' => $totalApplications,
            'total_categories' => $totalCategories,
            'total_tickets' => $totalTickets,
            'open_tickets' => $openTickets,
            'applications_with_tickets' => Ticket::distinct('aplikasi_id')->count('aplikasi_id'),
        ];
    }

    /**
     * Get filter options for applications.
     */
    private function getFilterOptions(): array
    {
        return [
            'statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
        ];
    }
}