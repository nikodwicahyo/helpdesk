<?php

namespace App\Http\Controllers\Traits;

use App\Models\SystemSetting;

/**
 * Trait for automatic pagination using system settings
 * 
 * Usage in any controller:
 * 
 * use App\Http\Controllers\Traits\UsesPagination;
 * 
 * class TicketController extends Controller
 * {
 *     use UsesPagination;
 *     
 *     public function index()
 *     {
 *         $tickets = Ticket::query()->latest();
 *         
 *         return Inertia::render('Tickets/Index', [
 *             'tickets' => $this->paginateWithSettings($tickets)
 *         ]);
 *     }
 * }
 */
trait UsesPagination
{
    /**
     * Get pagination size from system settings
     * 
     * @param int|null $override Optional override value
     * @return int Number of items per page
     */
    protected function getPaginationSize(?int $override = null): int
    {
        // If override provided, use it
        if ($override !== null && $override > 0) {
            return $override;
        }
        
        // Check if per_page is in request (user override)
        if (request()->has('per_page')) {
            $perPage = (int) request()->get('per_page');
            if ($perPage > 0 && $perPage <= 100) {
                return $perPage;
            }
        }
        
        // Get from system settings with caching
        return SystemSetting::get('items_per_page', 15);
    }
    
    /**
     * Paginate a query builder with system settings
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query The query to paginate
     * @param int|null $override Optional override for items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function paginateWithSettings($query, ?int $override = null)
    {
        return $query->paginate($this->getPaginationSize($override));
    }
    
    /**
     * Simple paginate a query builder with system settings
     * (No total count, more performant for large datasets)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query The query to paginate
     * @param int|null $override Optional override for items per page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    protected function simplePaginateWithSettings($query, ?int $override = null)
    {
        return $query->simplePaginate($this->getPaginationSize($override));
    }
    
    /**
     * Get pagination options for select dropdowns
     * 
     * @return array Array of pagination options
     */
    protected function getPaginationOptions(): array
    {
        $default = $this->getPaginationSize();
        
        return [
            10 => ['value' => 10, 'label' => '10 per page', 'selected' => $default === 10],
            15 => ['value' => 15, 'label' => '15 per page', 'selected' => $default === 15],
            25 => ['value' => 25, 'label' => '25 per page', 'selected' => $default === 25],
            50 => ['value' => 50, 'label' => '50 per page', 'selected' => $default === 50],
            100 => ['value' => 100, 'label' => '100 per page', 'selected' => $default === 100],
        ];
    }
}
