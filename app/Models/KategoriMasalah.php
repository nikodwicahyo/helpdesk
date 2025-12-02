<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class KategoriMasalah extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'aplikasi_id',
        'parent_id',
        'name',
        'description',
        'priority',
        'status',
        'sort_order',
        'level',
        'icon',
        'color',
        'keywords',
        'estimated_resolution_time',
        'common_solutions',
        'requires_attachment',
        'sla_hours',
        'ticket_count',
        'resolved_count',
        'avg_resolution_time',
        'success_rate',
        'metadata',
    ];

    protected $casts = [
        'keywords' => 'array',
        'metadata' => 'array',
        'requires_attachment' => 'boolean',
        'sla_hours' => 'decimal:2',
        'ticket_count' => 'integer',
        'resolved_count' => 'integer',
        'avg_resolution_time' => 'decimal:2',
        'success_rate' => 'decimal:2',
        'level' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the aplikasi that owns this category
     */
    public function aplikasi(): BelongsTo
    {
        return $this->belongsTo(Aplikasi::class, 'aplikasi_id');
    }

    /**
     * Get tickets for this category
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'kategori_masalah_id');
    }

    /**
     * Get teknisi who are experts in this category
     */
    public function expertTeknisis(): BelongsToMany
    {
        return $this->belongsToMany(Teknisi::class, 'teknisi_kategori_expertise', 'kategori_masalah_id', 'teknisi_nip')
                    ->withPivot('expertise_level', 'success_rate', 'avg_resolution_time')
                    ->withTimestamps();
    }

    /**
     * Get parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(KategoriMasalah::class, 'parent_id');
    }

    /**
     * Get child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(KategoriMasalah::class, 'parent_id');
    }

    /**
     * Get all descendants (recursive)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all ancestors (recursive)
     */
    public function ancestors()
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }

        return $ancestors;
    }

    // ==================== CATEGORY HIERARCHY ====================

    /**
     * Check if category is a root category (no parent)
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if category has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * Check if category is a leaf (no children)
     */
    public function isLeaf(): bool
    {
        return !$this->hasChildren();
    }

    /**
     * Get full category path (breadcrumb style)
     */
    public function getFullPath(string $separator = ' > '): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode($separator, $path);
    }

    /**
     * Get category depth in hierarchy
     */
    public function getDepth(): int
    {
        if ($this->isRoot()) {
            return 0;
        }

        return $this->ancestors()->count() + 1;
    }

    /**
     * Move category to new parent
     */
    public function moveToParent(?int $parentId): bool
    {
        if ($parentId && $parentId === $this->id) {
            return false; // Prevent self-parenting
        }

        $this->parent_id = $parentId;
        $this->level = $this->calculateLevel();

        return $this->save();
    }

    /**
     * Calculate category level based on parent
     */
    private function calculateLevel(): int
    {
        if (!$this->parent_id) {
            return 0;
        }

        $parent = self::find($this->parent_id);
        return $parent ? $parent->level + 1 : 0;
    }

    /**
     * Get all root categories for an aplikasi
     */
    public static function getRootCategories(int $aplikasiId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('aplikasi_id', $aplikasiId)
                  ->whereNull('parent_id')
                  ->orderBy('sort_order')
                  ->orderBy('name')
                  ->get();
    }

    /**
     * Get category tree structure
     */
    public static function getCategoryTree(int $aplikasiId): array
    {
        $roots = self::getRootCategories($aplikasiId);

        return $roots->map(function (KategoriMasalah $root) {
            return [
                'id' => $root->id,
                'name' => $root->name,
                'description' => $root->description,
                'priority' => $root->priority,
                'icon' => $root->icon,
                'color' => $root->color,
                'ticket_count' => $root->ticket_count,
                'children' => $root->getChildrenTree(),
            ];
        })->toArray();
    }

    /**
     * Get children tree recursively
     */
    public function getChildrenTree(): array
    {
        return $this->children()
                   ->orderBy('sort_order')
                   ->orderBy('name')
                   ->get()
                   ->map(function (KategoriMasalah $child) {
                       return [
                           'id' => $child->id,
                           'name' => $child->name,
                           'description' => $child->description,
                           'priority' => $child->priority,
                           'icon' => $child->icon,
                           'color' => $child->color,
                           'ticket_count' => $child->ticket_count,
                           'children' => $child->getChildrenTree(),
                       ];
                   })->toArray();
    }

    // ==================== USAGE ANALYTICS ====================

    /**
     * Update category statistics based on tickets
     */
    public function updateStatistics(): void
    {
        $tickets = $this->tickets;
        $resolvedTickets = $this->tickets()->where('status', Ticket::STATUS_RESOLVED)->get();

        $this->ticket_count = $tickets->count();
        $this->resolved_count = $resolvedTickets->count();

        if ($resolvedTickets->isNotEmpty()) {
            $this->avg_resolution_time = (float) $resolvedTickets->avg('resolution_time_minutes');
            $this->success_rate = round(($this->resolved_count / $this->ticket_count) * 100, 2);
        }

        $this->save();
    }

    /**
     * Get ticket volume for date range
     */
    public function getTicketVolume(Carbon $startDate, Carbon $endDate): int
    {
        return $this->tickets()
                   ->whereBetween('created_at', [$startDate, $endDate])
                   ->count();
    }

    /**
     * Get daily ticket volume for last N days
     */
    public function getDailyTicketVolume(int $days = 30): array
    {
        $data = [];
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays($days);

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $count = $this->tickets()
                         ->whereDate('created_at', $date->toDateString())
                         ->count();

            $data[] = [
                'date' => $date->toDateString(),
                'count' => $count,
            ];
        }

        return $data;
    }

    /**
     * Get category performance metrics (calculated from actual ticket data)
     */
    public function getPerformanceMetrics(): array
    {
        // Calculate from actual tickets, not stored counters
        $totalTickets = $this->tickets()->count();
        $resolvedTickets = $this->tickets()
            ->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
            ->count();
        
        $resolutionRate = $totalTickets > 0 ? ($resolvedTickets / $totalTickets) * 100 : 0;
        
        // Calculate average resolution time from actual resolved tickets
        $avgResolutionTime = $this->calculateAverageResolutionTime();
        
        // Calculate success rate (tickets resolved without reopening)
        $successRate = $this->calculateSuccessRate();
        
        $slaCompliance = $this->getSlaComplianceRate();
        
        // Calculate health score based on multiple factors
        $healthScore = $this->calculateHealthScore($resolutionRate, $slaCompliance, $successRate);
        
        return [
            'total_tickets' => $totalTickets,
            'resolved_tickets' => $resolvedTickets,
            'resolution_rate' => $resolutionRate,
            'avg_resolution_time' => $avgResolutionTime,
            'success_rate' => $successRate,
            'estimated_time' => $this->estimated_resolution_time ?? 0,
            'sla_compliance' => $slaCompliance,
            'health_score' => $healthScore,
        ];
    }
    
    /**
     * Calculate average resolution time from resolved tickets
     */
    protected function calculateAverageResolutionTime(): float
    {
        $resolvedTickets = $this->tickets()
            ->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
            ->whereNotNull('resolved_at')
            ->get();
        
        if ($resolvedTickets->isEmpty()) {
            return 0;
        }
        
        $totalMinutes = 0;
        $count = 0;
        
        foreach ($resolvedTickets as $ticket) {
            if ($ticket->resolved_at && $ticket->created_at) {
                $minutes = $ticket->created_at->diffInMinutes($ticket->resolved_at);
                $totalMinutes += $minutes;
                $count++;
            }
        }
        
        return $count > 0 ? $totalMinutes / $count : 0;
    }
    
    /**
     * Calculate success rate (resolved tickets / total closed tickets)
     */
    protected function calculateSuccessRate(): float
    {
        $closedTickets = $this->tickets()
            ->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
            ->count();
        
        if ($closedTickets === 0) {
            return 0;
        }
        
        // Count tickets that were resolved successfully (status is resolved or closed)
        $successfulTickets = $this->tickets()
            ->where('status', Ticket::STATUS_RESOLVED)
            ->orWhere(function ($query) {
                $query->where('kategori_masalah_id', $this->id)
                      ->where('status', Ticket::STATUS_CLOSED);
            })
            ->count();
        
        return ($successfulTickets / $closedTickets) * 100;
    }
    
    /**
     * Calculate health score based on various metrics
     */
    protected function calculateHealthScore(float $resolutionRate, float $slaCompliance, float $successRate = 0): float
    {
        // Weight factors for health score calculation
        $resolutionWeight = 0.4;
        $slaWeight = 0.3;
        $successWeight = 0.3;
        
        $resolutionScore = min(100, $resolutionRate);
        $slaScore = min(100, $slaCompliance);
        $successScore = min(100, $successRate);
        
        return ($resolutionScore * $resolutionWeight) + 
               ($slaScore * $slaWeight) + 
               ($successScore * $successWeight);
    }

    /**
     * Get SLA compliance rate for this category
     */
    public function getSlaComplianceRate(int $days = 30): float
    {
        if (!$this->sla_hours) {
            return 100.0; // No SLA defined
        }

        $recentTickets = $this->tickets()
                             ->where('created_at', '>=', Carbon::now()->subDays($days))
                             ->whereIn('status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED])
                             ->get();

        if ($recentTickets->isEmpty()) {
            return 100.0; // No tickets to measure
        }

        $slaBreachCount = 0;
        foreach ($recentTickets as $ticket) {
            $slaDeadline = $ticket->created_at->copy()->addHours($this->sla_hours);
            if ($ticket->resolved_at && $ticket->resolved_at->isAfter($slaDeadline)) {
                $slaBreachCount++;
            }
        }

        return round((($recentTickets->count() - $slaBreachCount) / $recentTickets->count()) * 100, 2);
    }

    /**
     * Get category health score (0-100)
     */
    public function getHealthScore(): float
    {
        $score = 100;

        // Penalize for low resolution rate
        if ($this->ticket_count > 0) {
            $resolutionRate = ($this->resolved_count / $this->ticket_count) * 100;
            if ($resolutionRate < 80) {
                $score -= (80 - $resolutionRate) * 2;
            }
        }

        // Penalize for long resolution times
        if ($this->avg_resolution_time && $this->estimated_resolution_time) {
            if ($this->avg_resolution_time > $this->estimated_resolution_time * 1.5) {
                $score -= 20;
            }
        }

        // Penalize for SLA breaches
        if ($this->getSlaComplianceRate() < 95) {
            $score -= 15;
        }

        return max(0, $score);
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Get status badge color for UI
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'secondary',
            default => 'light',
        };
    }

    /**
     * Get priority badge color for UI
     */
    public function getPriorityBadgeColorAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'success',
            self::PRIORITY_MEDIUM => 'info',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_URGENT => 'danger',
            default => 'light',
        };
    }

    /**
     * Get status label for UI
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            default => 'Unknown',
        };
    }

    /**
     * Get default_priority as alias for priority
     */
    public function getDefaultPriorityAttribute(): string
    {
        return $this->priority ?? self::PRIORITY_MEDIUM;
    }

    /**
     * Set default_priority as alias for priority
     */
    public function setDefaultPriorityAttribute($value): void
    {
        $this->attributes['priority'] = $value;
    }

    /**
     * Get priority label for UI
     */
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted SLA hours
     */
    public function getFormattedSlaHoursAttribute(): string
    {
        if (!$this->sla_hours) {
            return 'No SLA';
        }

        return $this->sla_hours . ' hours';
    }

    /**
     * Get formatted success rate
     */
    public function getFormattedSuccessRateAttribute(): string
    {
        return number_format((float) $this->success_rate, 1) . '%';
    }

    /**
     * Get formatted average resolution time
     */
    public function getFormattedAvgResolutionTimeAttribute(): string
    {
        if (!$this->avg_resolution_time) {
            return 'Not available';
        }

        $hours = floor($this->avg_resolution_time / 60);
        $minutes = $this->avg_resolution_time % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    /**
     * Get category icon with fallback
     */
    public function getCategoryIconAttribute(): string
    {
        if ($this->icon) {
            return $this->icon;
        }

        // Default icons based on priority
        return match($this->priority) {
            self::PRIORITY_URGENT => 'fas fa-exclamation-triangle',
            self::PRIORITY_HIGH => 'fas fa-arrow-up',
            self::PRIORITY_MEDIUM => 'fas fa-minus',
            self::PRIORITY_LOW => 'fas fa-arrow-down',
            default => 'fas fa-folder',
        };
    }

    /**
     * Get category color with fallback
     */
    public function getCategoryColorAttribute(): string
    {
        if ($this->color) {
            return $this->color;
        }

        // Default colors based on priority
        return match($this->priority) {
            self::PRIORITY_URGENT => '#dc3545',
            self::PRIORITY_HIGH => '#fd7e14',
            self::PRIORITY_MEDIUM => '#20c997',
            self::PRIORITY_LOW => '#6c757d',
            default => '#007bff',
        };
    }

    /**
     * Get category statistics for dashboard
     */
    public function getDashboardStatsAttribute(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'full_path' => $this->getFullPath(),
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,
            'priority_badge_color' => $this->priority_badge_color,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_badge_color' => $this->status_badge_color,
            'ticket_count' => $this->ticket_count,
            'resolved_count' => $this->resolved_count,
            'resolution_rate' => $this->ticket_count > 0 ? ($this->resolved_count / $this->ticket_count) * 100 : 0,
            'avg_resolution_time' => $this->formatted_avg_resolution_time,
            'success_rate' => $this->formatted_success_rate,
            'sla_hours' => $this->formatted_sla_hours,
            'health_score' => $this->getHealthScore(),
            'icon' => $this->category_icon,
            'color' => $this->category_color,
            'has_children' => $this->hasChildren(),
            'level' => $this->level,
        ];
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Check if category needs attention
     */
    public function needsAttention(): bool
    {
        $reasons = [];

        if ($this->status !== self::STATUS_ACTIVE) {
            $reasons[] = 'inactive';
        }

        if ($this->ticket_count > 0) {
            $resolutionRate = ($this->resolved_count / $this->ticket_count) * 100;
            if ($resolutionRate < 70) {
                $reasons[] = 'low_resolution_rate';
            }
        }

        if ($this->getSlaComplianceRate() < 90) {
            $reasons[] = 'sla_issues';
        }

        if ($this->avg_resolution_time && $this->estimated_resolution_time &&
            $this->avg_resolution_time > $this->estimated_resolution_time * 2) {
            $reasons[] = 'slow_resolution';
        }

        return !empty($reasons);
    }

    /**
     * Get attention reasons
     */
    public function getAttentionReasons(): array
    {
        $reasons = [];

        if ($this->status !== self::STATUS_ACTIVE) {
            $reasons[] = [
                'type' => 'status',
                'message' => 'Category is not active',
                'severity' => 'warning',
            ];
        }

        if ($this->ticket_count > 0) {
            $resolutionRate = ($this->resolved_count / $this->ticket_count) * 100;
            if ($resolutionRate < 70) {
                $reasons[] = [
                    'type' => 'performance',
                    'message' => 'Low resolution rate: ' . number_format($resolutionRate, 1) . '%',
                    'severity' => 'danger',
                ];
            }
        }

        if ($this->getSlaComplianceRate() < 90) {
            $reasons[] = [
                'type' => 'sla',
                'message' => 'SLA compliance below 90%',
                'severity' => 'warning',
            ];
        }

        if ($this->avg_resolution_time && $this->estimated_resolution_time &&
            $this->avg_resolution_time > $this->estimated_resolution_time * 2) {
            $reasons[] = [
                'type' => 'efficiency',
                'message' => 'Resolution time is more than double the estimate',
                'severity' => 'warning',
            ];
        }

        return $reasons;
    }

    /**
     * Get category recommendations
     */
    public function getRecommendations(): array
    {
        $recommendations = [];

        if ($this->ticket_count > 100 && !$this->hasChildren()) {
            $recommendations[] = [
                'type' => 'subcategorization',
                'message' => 'High ticket volume suggests need for subcategorization',
                'action' => 'Consider creating child categories',
                'priority' => 'medium',
            ];
        }

        if ($this->getSlaComplianceRate() < 80) {
            $recommendations[] = [
                'type' => 'sla_improvement',
                'message' => 'SLA compliance needs improvement',
                'action' => 'Review resolution process and teknisi assignment',
                'priority' => 'high',
            ];
        }

        if ($this->avg_resolution_time && $this->estimated_resolution_time &&
            $this->avg_resolution_time > $this->estimated_resolution_time * 1.5) {
            $recommendations[] = [
                'type' => 'time_estimation',
                'message' => 'Actual resolution time exceeds estimate',
                'action' => 'Update estimated resolution time or improve efficiency',
                'priority' => 'medium',
            ];
        }

        return $recommendations;
    }

    // ==================== QUERY SCOPES ====================

    /**
     * Scope for active categories
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for inactive categories
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Scope for root categories
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for child categories
     */
    public function scopeChildren(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope for categories by aplikasi
     */
    public function scopeByAplikasi(Builder $query, int $aplikasiId): Builder
    {
        return $query->where('aplikasi_id', $aplikasiId);
    }

    /**
     * Scope for categories by priority
     */
    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for urgent categories
     */
    public function scopeUrgent(Builder $query): Builder
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }

    /**
     * Scope for high priority categories
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Scope for popular categories (by ticket count)
     */
    public function scopePopular(Builder $query, int $minTickets = 10): Builder
    {
        return $query->where('ticket_count', '>=', $minTickets);
    }

    /**
     * Scope for categories needing attention
     */
    public function scopeNeedingAttention(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_INACTIVE)
              ->orWhere(function ($subQ) {
                  $subQ->where('ticket_count', '>', 0)
                       ->whereRaw('resolved_count / ticket_count < 0.7');
              })
              ->orWhere(function ($subQ) {
                  $subQ->whereNotNull('sla_hours')
                       ->whereRaw('ticket_count > 0');
              });
        });
    }

    /**
     * Scope for healthy categories
     */
    public function scopeHealthy(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where(function ($q) {
                        $q->where('ticket_count', '=', 0)
                          ->orWhere(function ($subQ) {
                              $subQ->where('ticket_count', '>', 0)
                                   ->whereRaw('resolved_count / ticket_count >= 0.8');
                          });
                    });
    }

    /**
     * Scope for categories by level
     */
    public function scopeByLevel(Builder $query, int $level): Builder
    {
        return $query->where('level', $level);
    }

    /**
     * Scope for leaf categories
     */
    public function scopeLeaves(Builder $query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    /**
     * Scope for categories with children
     */
    public function scopeHasChildren(Builder $query): Builder
    {
        return $query->whereHas('children');
    }

    /**
     * Scope for categories by teknisi expertise
     */
    public function scopeExpertiseByTeknisi(Builder $query, string $teknisiNip): Builder
    {
        return $query->whereHas('expertTeknisis', function ($q) use ($teknisiNip) {
            $q->where('teknisi_nip', $teknisiNip);
        });
    }

    /**
     * Scope for categories with SLA
     */
    public function scopeWithSla(Builder $query): Builder
    {
        return $query->whereNotNull('sla_hours');
    }

    /**
     * Scope for categories without SLA
     */
    public function scopeWithoutSla(Builder $query): Builder
    {
        return $query->whereNull('sla_hours');
    }

    /**
     * Scope for search by name or description
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for ordering by priority
     */
    public function scopeOrderByPriority(Builder $query, string $direction = 'desc'): Builder
    {
        $priorityOrder = [
            self::PRIORITY_URGENT => 4,
            self::PRIORITY_HIGH => 3,
            self::PRIORITY_MEDIUM => 2,
            self::PRIORITY_LOW => 1,
        ];

        return $query->orderByRaw("CASE
            WHEN priority = 'urgent' THEN 4
            WHEN priority = 'high' THEN 3
            WHEN priority = 'medium' THEN 2
            WHEN priority = 'low' THEN 1
            ELSE 0
        END {$direction}");
    }

    /**
     * Scope for ordering by popularity (ticket count)
     */
    public function scopeOrderByPopularity(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('ticket_count', $direction);
    }

    /**
     * Scope for hierarchical ordering
     */
    public function scopeHierarchical(Builder $query): Builder
    {
        return $query->orderBy('level')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope for categories with high ticket volume
     */
    public function scopeHighVolume(Builder $query, int $minTickets = 50): Builder
    {
        return $query->where('ticket_count', '>=', $minTickets);
    }

    /**
     * Scope for categories with low performance
     */
    public function scopeLowPerformance(Builder $query, float $minResolutionRate = 70.0): Builder
    {
        return $query->where('ticket_count', '>', 0)
                    ->whereRaw("resolved_count / ticket_count * 100 < {$minResolutionRate}");
    }

    /**
     * Scope for categories by parent
     */
    public function scopeByParent(Builder $query, ?int $parentId): Builder
    {
        if ($parentId === null) {
            return $query->whereNull('parent_id');
        }

        return $query->where('parent_id', $parentId);
    }

    /**
     * Scope for categories by color
     */
    public function scopeByColor(Builder $query, string $color): Builder
    {
        return $query->where('color', $color);
    }

    /**
     * Scope for categories requiring attachment
     */
    public function scopeRequiresAttachment(Builder $query): Builder
    {
        return $query->where('requires_attachment', true);
    }

    /**
     * Scope for categories with keywords
     */
    public function scopeWithKeywords(Builder $query, array $keywords): Builder
    {
        return $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('keywords', 'like', "%{$keyword}%");
            }
        });
    }

    /**
     * Scope for categories by estimated resolution time
     */
    public function scopeByEstimatedTime(Builder $query, int $minMinutes, ?int $maxMinutes = null): Builder
    {
        $query->where('estimated_resolution_time', '>=', $minMinutes);
        if ($maxMinutes) {
            $query->where('estimated_resolution_time', '<=', $maxMinutes);
        }
        return $query;
    }

    /**
     * Scope for categories with success rate above threshold
     */
    public function scopeHighSuccessRate(Builder $query, float $minRate = 80.0): Builder
    {
        return $query->where('success_rate', '>=', $minRate);
    }

    /**
     * Scope for categories with low success rate
     */
    public function scopeLowSuccessRate(Builder $query, float $maxRate = 50.0): Builder
    {
        return $query->where('success_rate', '<=', $maxRate);
    }

    // ==================== STATIC METHODS ====================

    /**
     * Get category analytics for an aplikasi
     */
    public static function getAnalyticsForAplikasi(int $aplikasiId): array
    {
        $categories = self::where('aplikasi_id', $aplikasiId)->get();

        if ($categories->isEmpty()) {
            return [];
        }

        $totalTickets = $categories->sum('ticket_count');
        $totalResolved = $categories->sum('resolved_count');

        return [
            'summary' => [
                'total_categories' => $categories->count(),
                'active_categories' => $categories->where('status', self::STATUS_ACTIVE)->count(),
                'root_categories' => $categories->whereNull('parent_id')->count(),
                'total_tickets' => $totalTickets,
                'total_resolved' => $totalResolved,
                'overall_resolution_rate' => $totalTickets > 0 ? ($totalResolved / $totalTickets) * 100 : 0,
            ],
            'hierarchy' => [
                'max_depth' => $categories->max('level'),
                'categories_by_level' => $categories->groupBy('level')->map->count(),
            ],
            'performance' => [
                'avg_resolution_rate' => $categories->where('ticket_count', '>', 0)->avg(function ($cat) {
                    return $cat->ticket_count > 0 ? ($cat->resolved_count / $cat->ticket_count) * 100 : 0;
                }),
                'avg_resolution_time' => $categories->whereNotNull('avg_resolution_time')->avg('avg_resolution_time'),
                'high_performers' => $categories->where('success_rate', '>=', 80)->count(),
                'low_performers' => $categories->where('success_rate', '<', 50)->count(),
            ],
            'distribution' => [
                'by_priority' => $categories->groupBy('priority')->map->count(),
                'by_status' => $categories->groupBy('status')->map->count(),
                'top_categories' => $categories->sortByDesc('ticket_count')->take(5)->map(function ($cat) use ($totalTickets) {
                    return [
                        'name' => $cat->name,
                        'ticket_count' => $cat->ticket_count,
                        'percentage' => $totalTickets > 0 ? ($cat->ticket_count / $totalTickets) * 100 : 0,
                    ];
                }),
            ],
        ];
    }

    /**
     * Sync categories with predefined templates
     */
    public static function syncWithTemplates(int $aplikasiId, array $templates): array
    {
        $results = ['created' => 0, 'updated' => 0, 'errors' => 0];

        foreach ($templates as $template) {
            try {
                $category = self::where('aplikasi_id', $aplikasiId)
                              ->where('name', $template['name'])
                              ->first();

                $categoryData = array_merge($template, ['aplikasi_id' => $aplikasiId]);

                if (!$category) {
                    self::create($categoryData);
                    $results['created']++;
                } else {
                    $category->update($categoryData);
                    $results['updated']++;
                }
            } catch (\Exception $e) {
                $results['errors']++;
            }
        }

        return $results;
    }

    // ==================== MODEL EVENTS ====================

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Set level when saving
        static::saving(function ($category) {
            if ($category->parent_id) {
                $parent = self::find($category->parent_id);
                $category->level = $parent ? $parent->level + 1 : 0;
            } else {
                $category->level = 0;
            }
        });

        // Update statistics when tickets change
        static::saved(function ($category) {
            if ($category->wasChanged(['estimated_resolution_time', 'sla_hours', 'priority'])) {
                $category->updateStatistics();
            }
        });
    }
}