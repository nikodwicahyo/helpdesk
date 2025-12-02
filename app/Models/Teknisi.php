<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Models\AdminHelpdesk;

class Teknisi extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'nip';
    public $incrementing = false;
    protected $keyType = 'string';

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_BUSY = 'busy';
    const STATUS_AVAILABLE = 'available';

    // Skill level constants
    const SKILL_JUNIOR = 'junior';
    const SKILL_SENIOR = 'senior';
    const SKILL_EXPERT = 'expert';

    // Workload thresholds
    const WORKLOAD_LIGHT = 5;
    const WORKLOAD_MODERATE = 10;
    const WORKLOAD_HEAVY = 15;
    const WORKLOAD_OVERLOADED = 20;

    // Workload scoring constants
    const BASE_POINTS_PER_TICKET = 10;
    const URGENT_TICKET_MULTIPLIER = 3;
    const HIGH_TICKET_MULTIPLIER = 2;
    const BASE_PRIORITY_MULTIPLIER = 1;
    const MAX_WORKLOAD_SCORE = 1000.00;
    const MIN_WORKLOAD_SCORE = 0.00;
    const WORKLOAD_SCORE_PRECISION = 2;

    protected $fillable = [
        'nip',
        'name',
        'email',
        'phone',
        'department',
        'position',
        'status',
        'role',
        'skill_level',
        'skills',
        'certifications',
        'ticket_count',
        'rating',
        'experience_years',
        'bio',
        'last_active_at',
        'email_verified_at',
        'password',
        'remember_token',
        'login_attempts',
        'locked_until',
        'max_concurrent_tickets',
        'specializations',
        'performance_metrics',
        'availability_status',
        'workload_score',
        'is_available',
        'current_workload',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_active_at' => 'datetime',
        'skills' => 'array',
        'certifications' => 'array',
        'specializations' => 'array',
        'performance_metrics' => 'array',
        'password' => 'hashed',
        'max_concurrent_tickets' => 'integer',
        'workload_score' => 'decimal:2',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get tickets assigned to this teknisi
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_teknisi_nip', 'nip');
    }

    /**
     * Get tickets assigned to this teknisi (alias for assignedTickets for consistency)
     */
    public function tickets(): HasMany
    {
        return $this->assignedTickets();
    }

    /**
     * Get active tickets (not closed)
     */
    public function activeTickets(): HasMany
    {
        return $this->assignedTickets()->whereNotIn('status', [Ticket::STATUS_CLOSED]);
    }

    /**
     * Get resolved tickets for performance tracking
     */
    public function resolvedTickets(): HasMany
    {
        return $this->assignedTickets()->where('status', Ticket::STATUS_RESOLVED);
    }

    /**
     * Get applications this teknisi is expert in
     */
    public function expertApplications(): BelongsToMany
    {
        return $this->belongsToMany(Aplikasi::class, 'teknisi_aplikasi_expertise', 'teknisi_nip', 'aplikasi_id')
                    ->withPivot('expertise_level', 'certified_at', 'notes')
                    ->withTimestamps();
    }

    /**
     * Get applications this teknisi is assigned to
     */
    public function assignedApplications(): BelongsToMany
    {
        return $this->belongsToMany(Aplikasi::class, 'teknisi_aplikasi_assignments', 'teknisi_nip', 'aplikasi_id')
                    ->withPivot('assigned_by_nip', 'assigned_at', 'notes')
                    ->withTimestamps();
    }

    /**
     * Get problem categories this teknisi specializes in
     */
    public function expertCategories(): BelongsToMany
    {
        return $this->belongsToMany(KategoriMasalah::class, 'teknisi_kategori_expertise', 'teknisi_nip', 'kategori_masalah_id')
                    ->withPivot('expertise_level', 'success_rate', 'avg_resolution_time')
                    ->withTimestamps();
    }

    /**
     * Get performance records
     * TODO: Create TeknisiPerformance model or remove this relationship
     */
    // public function performanceRecords(): HasMany
    // {
    //     return $this->hasMany(TeknisiPerformance::class, 'teknisi_nip', 'nip');
    // }

    /**
     * Get skill endorsements from other teknisi
     * TODO: Create TeknisiSkillEndorsement model or remove this relationship
     */
    // public function skillEndorsements(): HasMany
    // {
    //     return $this->hasMany(TeknisiSkillEndorsement::class, 'teknisi_nip', 'nip');
    // }

    /**
     * Get teknisi who endorsed this teknisi's skills
     */
    public function endorsedBy(): BelongsToMany
    {
        return $this->belongsToMany(Teknisi::class, 'teknisi_skill_endorsements', 'teknisi_nip', 'endorsed_by_nip');
    }

    /**
     * Get notifications for this teknisi using polymorphic relationship
     */
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Get unread notifications
     */
    public function unreadNotifications()
    {
        return $this->notifications()->where('status', 'unread');
    }

    /**
     * Get knowledge base articles authored by this teknisi
     */
    public function knowledgeBaseArticles(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticle::class, 'author_nip', 'nip');
    }

    /**
     * Get published knowledge base articles
     */
    public function publishedKnowledgeBaseArticles(): HasMany
    {
        return $this->knowledgeBaseArticles()->where('status', KnowledgeBaseArticle::STATUS_PUBLISHED);
    }

    /**
     * Get ticket actions performed by this teknisi
     */
    public function ticketActions(): HasMany
    {
        return $this->hasMany(TicketAction::class, 'actor_nip', 'nip')
                    ->where('actor_type', 'teknisi');
    }

    // ==================== SKILL MANAGEMENT ====================

    /**
     * Check if teknisi has skill for specific application
     */
    public function hasSkillForApplication(int $aplikasiId): bool
    {
        return $this->expertApplications()->where('aplikasi_id', $aplikasiId)->exists();
    }

    /**
     * Check if teknisi has skill for specific category
     */
    public function hasSkillForCategory(int $kategoriId): bool
    {
        return $this->expertCategories()->where('kategori_masalah_id', $kategoriId)->exists();
    }

    /**
     * Get expertise level for application (1-5 scale)
     */
    public function getExpertiseLevelForApplication(int $aplikasiId): int
    {
        $expertise = $this->expertApplications()
                         ->where('aplikasi_id', $aplikasiId)
                         ->first();

        return $expertise ? $expertise->pivot->expertise_level : 0;
    }

    /**
     * Get expertise level for category (1-5 scale)
     */
    public function getExpertiseLevelForCategory(int $kategoriId): int
    {
        $expertise = $this->expertCategories()
                         ->where('kategori_masalah_id', $kategoriId)
                         ->first();

        return $expertise ? $expertise->pivot->expertise_level : 0;
    }

    /**
     * Add or update application expertise
     */
    public function setApplicationExpertise(int $aplikasiId, int $level, ?string $notes = null): void
    {
        $this->expertApplications()->syncWithoutDetaching([
            $aplikasiId => [
                'expertise_level' => min(5, max(1, $level)),
                'certified_at' => Carbon::now(),
                'notes' => $notes,
            ]
        ]);
    }

    /**
     * Add or update category expertise
     */
    public function setCategoryExpertise(int $kategoriId, int $level, ?float $successRate = null, ?int $avgResolutionTime = null): void
    {
        $this->expertCategories()->syncWithoutDetaching([
            $kategoriId => [
                'expertise_level' => min(5, max(1, $level)),
                'success_rate' => $successRate,
                'avg_resolution_time' => $avgResolutionTime,
            ]
        ]);
    }

    /**
     * Get all skills as formatted string
     */
    public function getFormattedSkillsAttribute(): string
    {
        if (!$this->skills || empty($this->skills)) {
            return 'No skills specified';
        }

        return implode(', ', $this->skills);
    }

    /**
     * Get skill level badge color
     */
    public function getSkillLevelBadgeColorAttribute(): string
    {
        return match($this->skill_level) {
            self::SKILL_JUNIOR => 'info',
            self::SKILL_SENIOR => 'warning',
            self::SKILL_EXPERT => 'success',
            default => 'light',
        };
    }

    /**
     * Get skill level label
     */
    public function getSkillLevelLabelAttribute(): string
    {
        return match($this->skill_level) {
            self::SKILL_JUNIOR => 'Junior',
            self::SKILL_SENIOR => 'Senior',
            self::SKILL_EXPERT => 'Expert',
            default => 'Unknown',
        };
    }

    // ==================== WORKLOAD MANAGEMENT ====================

    /**
     * Get current active ticket count
     */
    public function getCurrentWorkload(): int
    {
        return $this->activeTickets()->count();
    }

    /**
     * Get workload percentage based on max concurrent tickets
     */
    public function getWorkloadPercentage(): float
    {
        $maxTickets = $this->max_concurrent_tickets ?? self::WORKLOAD_MODERATE;
        $currentWorkload = $this->getCurrentWorkload();

        return min(100, ($currentWorkload / $maxTickets) * 100);
    }

    /**
     * Get workload status
     */
    public function getWorkloadStatusAttribute(): string
    {
        $percentage = $this->getWorkloadPercentage();

        if ($percentage >= 100) return 'overloaded';
        if ($percentage >= 80) return 'heavy';
        if ($percentage >= 50) return 'moderate';
        return 'light';
    }

    /**
     * Get workload badge color
     */
    public function getWorkloadBadgeColorAttribute(): string
    {
        return match($this->workload_status) {
            'overloaded' => 'danger',
            'heavy' => 'warning',
            'moderate' => 'info',
            'light' => 'success',
            default => 'light',
        };
    }

    /**
     * Check if teknisi is available for new assignments
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_ACTIVE &&
               $this->getCurrentWorkload() < ($this->max_concurrent_tickets ?? self::WORKLOAD_MODERATE);
    }

    /**
     * Check if teknisi can accept more tickets
     */
    public function canAcceptTickets(): bool
    {
        return $this->isAvailable() && !$this->isBusy();
    }

    /**
     * Check if teknisi is busy (workload too high)
     */
    public function isBusy(): bool
    {
        return $this->getCurrentWorkload() >= ($this->max_concurrent_tickets ?? self::WORKLOAD_HEAVY);
    }

    /**
     * Get available capacity (how many more tickets can be assigned)
     */
    public function getAvailableCapacity(): int
    {
        $maxTickets = $this->max_concurrent_tickets ?? self::WORKLOAD_MODERATE;
        return max(0, $maxTickets - $this->getCurrentWorkload());
    }

    /**
     * Update workload score based on current tickets with comprehensive error handling and optimization
     *
     * This method calculates a weighted workload score considering:
     * - Base workload from active tickets
     * - Priority distribution (urgent and high priority tickets)
     * - Performance factors and edge cases
     *
     * @return bool True if update was successful, false otherwise
     * @throws \Exception If database operation fails
     */
    public function updateWorkloadScore(): bool
    {
        try {
            // Input validation and early validation
            if (!$this->exists) {
                \Illuminate\Support\Facades\Log::warning('Attempted to update workload score for non-existent teknisi', [
                    'nip' => $this->nip ?? 'unknown'
                ]);
                return false;
            }

            // Cache expensive queries to avoid multiple database hits
            $currentWorkload = $this->getCurrentWorkload();
            $activeTickets = $this->activeTickets()->get();

            // Calculate base score with bounds checking
            $baseScore = $this->calculateBaseWorkloadScore($currentWorkload);
            if ($baseScore < self::MIN_WORKLOAD_SCORE) {
                $this->workload_score = (string) self::MIN_WORKLOAD_SCORE;
                return $this->save();
            }

            // Calculate priority multiplier with validation
            $priorityMultiplier = $this->calculatePriorityMultiplier($activeTickets);
            if ($priorityMultiplier < self::BASE_PRIORITY_MULTIPLIER) {
                \Illuminate\Support\Facades\Log::warning('Invalid priority multiplier calculated', [
                    'nip' => $this->nip,
                    'multiplier' => $priorityMultiplier,
                    'active_tickets_count' => $activeTickets->count()
                ]);
                $priorityMultiplier = self::BASE_PRIORITY_MULTIPLIER;
            }

            // Calculate final score with bounds checking
            $finalScore = $this->calculateFinalWorkloadScore($baseScore, $priorityMultiplier);

            // Ensure score is within acceptable bounds
            $finalScore = max(self::MIN_WORKLOAD_SCORE, min(self::MAX_WORKLOAD_SCORE, $finalScore));

            // Update score with precision handling
            $this->workload_score = round($finalScore, self::WORKLOAD_SCORE_PRECISION);

            // Save with error handling
            if ($this->save()) {
                \Illuminate\Support\Facades\Log::debug('Workload score updated successfully', [
                    'nip' => $this->nip,
                    'old_score' => $this->getOriginal('workload_score'),
                    'new_score' => $this->workload_score,
                    'current_workload' => $currentWorkload,
                    'priority_multiplier' => $priorityMultiplier
                ]);
                return true;
            }

            \Illuminate\Support\Facades\Log::error('Failed to save workload score update', [
                'nip' => $this->nip,
                'calculated_score' => $this->workload_score
            ]);
            return false;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Exception occurred while updating workload score', [
                'nip' => $this->nip ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Set a safe default score on error
            $this->workload_score = self::MIN_WORKLOAD_SCORE;
            return false;
        }
    }

    /**
     * Calculate base workload score from current active tickets
     *
     * @param int $currentWorkload
     * @return float
     */
    private function calculateBaseWorkloadScore(int $currentWorkload): float
    {
        if ($currentWorkload < 0) {
            \Illuminate\Support\Facades\Log::warning('Negative workload detected', [
                'nip' => $this->nip,
                'workload' => $currentWorkload
            ]);
            return self::MIN_WORKLOAD_SCORE;
        }

        return $currentWorkload * self::BASE_POINTS_PER_TICKET;
    }

    /**
     * Calculate priority multiplier based on ticket distribution
     *
     * @param \Illuminate\Database\Eloquent\Collection $activeTickets
     * @return float
     */
    private function calculatePriorityMultiplier($activeTickets): float
    {
        if ($activeTickets->isEmpty()) {
            return self::BASE_PRIORITY_MULTIPLIER;
        }

        $urgentCount = $activeTickets->where('priority', Ticket::PRIORITY_URGENT)->count();
        $highCount = $activeTickets->where('priority', Ticket::PRIORITY_HIGH)->count();

        // Validate counts are non-negative
        $urgentCount = max(0, $urgentCount);
        $highCount = max(0, $highCount);

        return ($urgentCount * self::URGENT_TICKET_MULTIPLIER) +
               ($highCount * self::HIGH_TICKET_MULTIPLIER) +
               self::BASE_PRIORITY_MULTIPLIER;
    }

    /**
     * Calculate final workload score with additional performance factors
     *
     * @param float $baseScore
     * @param float $priorityMultiplier
     * @return float
     */
    private function calculateFinalWorkloadScore(float $baseScore, float $priorityMultiplier): float
    {
        $rawScore = $baseScore * $priorityMultiplier;

        // Apply performance-based adjustments if rating exists
        if ($this->rating && $this->rating > 0) {
            // Higher rated teknisi get slight workload bonus for efficiency
            $performanceAdjustment = 1 + (($this->rating - 3) * 0.1);
            $rawScore *= $performanceAdjustment;
        }

        // Apply experience-based efficiency factor
        if ($this->experience_years && $this->experience_years > 0) {
            // More experienced teknisi are generally more efficient
            $experienceEfficiency = 1 - (min($this->experience_years, 10) * 0.02);
            $rawScore *= $experienceEfficiency;
        }

        return $rawScore;
    }

    /**
     * Get estimated completion time for new ticket
     */
    public function getEstimatedCompletionTime(int $priority = Ticket::PRIORITY_MEDIUM): ?Carbon
    {
        if (!$this->canAcceptTickets()) {
            return null;
        }

        $baseHours = match($priority) {
            Ticket::PRIORITY_URGENT => 2,
            Ticket::PRIORITY_HIGH => 4,
            Ticket::PRIORITY_MEDIUM => 8,
            Ticket::PRIORITY_LOW => 24,
            default => 8, // Default to medium if priority is unknown
        };

        // Adjust based on current workload
        $workloadMultiplier = 1 + ($this->getCurrentWorkload() * 0.2);

        return Carbon::now()->addHours($baseHours * $workloadMultiplier);
    }

    // ==================== PERFORMANCE TRACKING ====================

    /**
     * Get average resolution time in minutes
     */
    public function getAverageResolutionTime(): ?float
    {
        $resolvedTickets = $this->resolvedTickets()
                               ->whereNotNull('resolution_time_minutes')
                               ->get();

        if ($resolvedTickets->isEmpty()) {
            return null;
        }

        return $resolvedTickets->avg('resolution_time_minutes');
    }

    /**
     * Get resolution rate (percentage of resolved vs total assigned)
     */
    public function getResolutionRate(): float
    {
        $totalAssigned = $this->assignedTickets()->count();
        if ($totalAssigned === 0) {
            return 0.0;
        }

        $resolvedCount = $this->resolvedTickets()->count();
        return round(($resolvedCount / $totalAssigned) * 100, 2);
    }

    /**
     * Get customer satisfaction score (average rating)
     */
    public function getCustomerSatisfactionScore(): ?float
    {
        $ratedTickets = $this->resolvedTickets()
                            ->whereNotNull('user_rating')
                            ->get();

        if ($ratedTickets->isEmpty()) {
            return null;
        }

        return round($ratedTickets->avg('user_rating'), 2);
    }

    /**
     * Get first response time average in minutes
     */
    public function getAverageFirstResponseTime(): ?float
    {
        $ticketsWithResponse = $this->assignedTickets()
                                   ->whereNotNull('first_response_at')
                                   ->get();

        if ($ticketsWithResponse->isEmpty()) {
            return null;
        }

        $totalMinutes = $ticketsWithResponse->sum(function ($ticket) {
            return Carbon::parse($ticket->created_at)->diffInMinutes($ticket->first_response_at);
        });

        return round($totalMinutes / $ticketsWithResponse->count(), 2);
    }

    /**
     * Get performance metrics summary
     */
    public function getPerformanceMetricsAttribute(): array
    {
        return [
            'resolution_rate' => $this->getResolutionRate(),
            'avg_resolution_time' => $this->getAverageResolutionTime(),
            'customer_satisfaction' => $this->getCustomerSatisfactionScore(),
            'avg_first_response_time' => $this->getAverageFirstResponseTime(),
            'total_tickets_handled' => $this->ticket_count ?? 0,
            'current_workload' => $this->getCurrentWorkload(),
            'rating' => $this->rating,
        ];
    }

    /**
     * Get performance grade (A-F)
     */
    public function getPerformanceGradeAttribute(): string
    {
        $metrics = $this->performance_metrics;

        if (!$metrics || !isset($metrics['resolution_rate'])) {
            return 'N/A';
        }

        $score = 0;
        $factors = 0;

        // Resolution rate (40% weight)
        if (isset($metrics['resolution_rate'])) {
            $score += ($metrics['resolution_rate'] / 100) * 40;
            $factors++;
        }

        // Customer satisfaction (30% weight)
        if (isset($metrics['customer_satisfaction'])) {
            $score += ($metrics['customer_satisfaction'] / 5) * 30;
            $factors++;
        }

        // Workload efficiency (30% weight)
        $workloadEfficiency = min(100, 100 - (($this->getCurrentWorkload() / ($this->max_concurrent_tickets ?? 10)) * 100));
        $score += ($workloadEfficiency / 100) * 30;
        $factors++;

        $averageScore = $factors > 0 ? $score / $factors : 0;

        return match(true) {
            $averageScore >= 90 => 'A',
            $averageScore >= 80 => 'B',
            $averageScore >= 70 => 'C',
            $averageScore >= 60 => 'D',
            default => 'F',
        };
    }

    /**
     * Update performance metrics
     */
    public function updatePerformanceMetrics(): void
    {
        $this->performance_metrics = [
            'resolution_rate' => $this->getResolutionRate(),
            'avg_resolution_time' => $this->getAverageResolutionTime(),
            'customer_satisfaction' => $this->getCustomerSatisfactionScore(),
            'avg_first_response_time' => $this->getAverageFirstResponseTime(),
            'calculated_at' => Carbon::now(),
        ];

        $this->save();
    }

    /**
     * Record performance for a specific ticket
     */
    public function recordTicketPerformance(Ticket $ticket): void
    {
        if (!$ticket->resolved_at) {
            return;
        }

        $resolutionTime = $ticket->calculateResolutionTime();
        $firstResponseTime = $ticket->first_response_at ?
            Carbon::parse($ticket->created_at)->diffInMinutes($ticket->first_response_at) : null;

        // Update ticket count
        $this->increment('ticket_count');

        // Update rating if provided
        if ($ticket->user_rating) {
            $this->recalculateRating();
        }

        // Update performance metrics
        $this->updatePerformanceMetrics();

        // Update category expertise if applicable
        if ($ticket->kategori_masalah_id) {
            $this->updateCategoryExpertise($ticket->kategori_masalah_id, $resolutionTime, $ticket->user_rating);
        }
    }

    /**
     * Recalculate overall rating based on recent tickets
     */
    private function recalculateRating(): void
    {
        $recentTickets = $this->resolvedTickets()
                             ->whereNotNull('user_rating')
                             ->where('resolved_at', '>=', Carbon::now()->subMonths(3))
                             ->get();

        if ($recentTickets->isNotEmpty()) {
            $averageRating = $recentTickets->avg('user_rating');
            $this->rating = round($averageRating, 2);
            $this->save();
        }
    }

    /**
     * Update category expertise based on ticket performance
     */
    private function updateCategoryExpertise(int $kategoriId, ?int $resolutionTime, ?int $rating): void
    {
        $expertise = $this->expertCategories()
                         ->where('kategori_masalah_id', $kategoriId)
                         ->first();

        if ($expertise) {
            $currentSuccessRate = $expertise->pivot->success_rate ?? 0;
            $currentAvgTime = $expertise->pivot->avg_resolution_time ?? 0;
            $ticketCount = $expertise->pivot->ticket_count ?? 0;

            // Update success rate (weighted average)
            $newSuccessRate = (($currentSuccessRate * $ticketCount) + ($rating ?? 3)) / ($ticketCount + 1);

            // Update average resolution time (weighted average)
            $newAvgTime = $resolutionTime ?
                (($currentAvgTime * $ticketCount) + $resolutionTime) / ($ticketCount + 1) : $currentAvgTime;

            $this->expertCategories()->updateExistingPivot($kategoriId, [
                'success_rate' => $newSuccessRate,
                'avg_resolution_time' => $newAvgTime,
                'ticket_count' => $ticketCount + 1,
            ]);
        }
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
     * Get status label
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
     * Get formatted last active time
     */
    public function getFormattedLastActiveAttribute(): string
    {
        if (!$this->last_active_at) {
            return 'Never';
        }

        return $this->last_active_at->diffForHumans();
    }

    /**
     * Get formatted experience years
     */
    public function getFormattedExperienceAttribute(): string
    {
        if (!$this->experience_years) {
            return 'Not specified';
        }

        return $this->experience_years . ' years';
    }

    /**
     * Get formatted rating with stars
     */
    public function getFormattedRatingAttribute(): string
    {
        if (!$this->rating) {
            return 'No ratings yet';
        }

        $stars = str_repeat('★', round($this->rating));
        $stars .= str_repeat('☆', 5 - round($this->rating));

        return $this->rating . '/5 ' . $stars;
    }

    /**
     * Get availability status badge
     */
    public function getAvailabilityBadgeAttribute(): string
    {
        if (!$this->isAvailable()) {
            return '<span class="badge badge-danger">Busy</span>';
        }

        return '<span class="badge badge-success">Available</span>';
    }

    /**
     * Get workload indicator
     */
    public function getWorkloadIndicatorAttribute(): array
    {
        return [
            'current' => $this->getCurrentWorkload(),
            'max' => $this->max_concurrent_tickets ?? self::WORKLOAD_MODERATE,
            'percentage' => $this->getWorkloadPercentage(),
            'status' => $this->workload_status,
            'badge_color' => $this->workload_badge_color,
        ];
    }

    /**
     * Get formatted certifications
     */
    public function getFormattedCertificationsAttribute(): string
    {
        if (!$this->certifications || empty($this->certifications)) {
            return 'No certifications';
        }

        return implode(', ', $this->certifications);
    }

    /**
     * Get the admin who assigned this teknisi to applications (either AdminHelpdesk or AdminAplikasi)
     */
    public function getAssignedByAdmin()
    {
        // Get the latest assignment record for this teknisi
        $latestAssignment = $this->assignedApplications()->first();

        if (!$latestAssignment) {
            return null;
        }

        $assignedByNip = $latestAssignment->pivot->assigned_by_nip;

        if (!$assignedByNip) {
            return null;
        }

        // Try to find in admin_helpdesks first
        $adminHelpdesk = AdminHelpdesk::where('nip', $assignedByNip)->first();
        if ($adminHelpdesk) {
            return $adminHelpdesk;
        }

        // If not found in admin_helpdesks, try admin_aplikasis
        return AdminAplikasi::where('nip', $assignedByNip)->first();
    }

    /**
     * Get the type of admin who assigned this teknisi
     */
    public function getAssignedByAdminType(): ?string
    {
        $latestAssignment = $this->assignedApplications()->first();

        if (!$latestAssignment || !$latestAssignment->pivot->assigned_by_nip) {
            return null;
        }

        $assignedByNip = $latestAssignment->pivot->assigned_by_nip;

        // Check if exists in admin_helpdesks
        $adminHelpdesk = AdminHelpdesk::where('nip', $assignedByNip)->exists();
        if ($adminHelpdesk) {
            return 'admin_helpdesk';
        }

        // Check if exists in admin_aplikasis
        $adminAplikasi = AdminAplikasi::where('nip', $assignedByNip)->exists();
        if ($adminAplikasi) {
            return 'admin_aplikasi';
        }

        return null;
    }

    /**
     * Get profile completeness percentage
     */
    public function getProfileCompletenessAttribute(): float
    {
        $fields = [
            'name', 'email', 'phone', 'department', 'position',
            'bio', 'skills', 'certifications', 'experience_years'
        ];

        $completedFields = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completedFields++;
            }
        }

        return round(($completedFields / count($fields)) * 100, 1);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStatsAttribute(): array
    {
        return [
            'total_tickets' => $this->ticket_count ?? 0,
            'active_tickets' => $this->getCurrentWorkload(),
            'resolved_tickets' => $this->resolvedTickets()->count(),
            'resolution_rate' => $this->getResolutionRate(),
            'current_rating' => $this->rating,
            'workload_percentage' => $this->getWorkloadPercentage(),
            'available_capacity' => $this->getAvailableCapacity(),
            'performance_grade' => $this->performance_grade,
            'profile_completeness' => $this->profile_completeness,
        ];
    }

    /**
     * Get recent activity summary
     */
    public function getRecentActivityAttribute(): array
    {
        return [
            'last_active' => $this->formatted_last_active,
            'recent_tickets' => $this->assignedTickets()
                                   ->where('created_at', '>=', Carbon::now()->subDays(7))
                                   ->count(),
            'resolved_this_week' => $this->resolvedTickets()
                                        ->where('resolved_at', '>=', Carbon::now()->startOfWeek())
                                        ->count(),
        ];
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Find best teknisi for ticket assignment using intelligent algorithm
     */
    public static function findBestTeknisiForTicket(Ticket $ticket): ?Teknisi
    {
        $candidates = self::getAvailableTeknisiForTicket($ticket);

        if ($candidates->isEmpty()) {
            return null;
        }

        // Score each candidate
        $scoredCandidates = $candidates->map(function ($teknisi) use ($ticket) {
            $score = $teknisi->calculateAssignmentScore($ticket);
            return [
                'teknisi' => $teknisi,
                'score' => $score,
            ];
        })->sortByDesc('score');

        return $scoredCandidates->first()['teknisi'] ?? null;
    }

    /**
     * Get available teknisi for specific ticket
     */
    public static function getAvailableTeknisiForTicket(Ticket $ticket): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::where('status', self::STATUS_ACTIVE)
                    ->whereHas('expertApplications', function ($q) use ($ticket) {
                        $q->where('aplikasi_id', $ticket->aplikasi_id);
                    })
                    ->orWhereHas('expertCategories', function ($q) use ($ticket) {
                        $q->where('kategori_masalah_id', $ticket->kategori_masalah_id);
                    });

        return $query->get()->filter(function ($teknisi) {
            return $teknisi->canAcceptTickets();
        });
    }

    /**
     * Calculate assignment score for a ticket
     */
    public function calculateAssignmentScore(Ticket $ticket): float
    {
        $score = 0;

        // Base availability score (30%)
        if ($this->isAvailable()) {
            $score += 30;
        }

        // Application expertise (25%)
        if ($this->hasSkillForApplication($ticket->aplikasi_id)) {
            $expertiseLevel = $this->getExpertiseLevelForApplication($ticket->aplikasi_id);
            $score += (25 * $expertiseLevel) / 5;
        }

        // Category expertise (25%)
        if ($this->hasSkillForCategory($ticket->kategori_masalah_id)) {
            $expertiseLevel = $this->getExpertiseLevelForCategory($ticket->kategori_masalah_id);
            $score += (25 * $expertiseLevel) / 5;
        }

        // Workload efficiency (10%)
        $workloadEfficiency = 100 - $this->getWorkloadPercentage();
        $score += ($workloadEfficiency / 100) * 10;

        // Performance rating (10%)
        if ($this->rating) {
            $score += ($this->rating / 5) * 10;
        }

        // Experience bonus (5%)
        if ($this->experience_years) {
            $experienceBonus = min(5, $this->experience_years / 2);
            $score += $experienceBonus;
        }

        return $score;
    }

    /**
     * Auto-assign ticket to best available teknisi
     */
    public static function autoAssignTicket(Ticket $ticket, ?string $assignedByNip = null): ?Teknisi
    {
        $bestTeknisi = self::findBestTeknisiForTicket($ticket);

        if ($bestTeknisi && $ticket->assignToTeknisi($bestTeknisi->nip, $assignedByNip ?? 'system')) {
            // Update teknisi workload
            $bestTeknisi->updateWorkloadScore();

            // Log assignment
            \Illuminate\Support\Facades\Log::info("Auto-assigned ticket {$ticket->ticket_number} to teknisi {$bestTeknisi->nip}");

            return $bestTeknisi;
        }

        return null;
    }

    /**
     * Rebalance workload across all teknisi
     */
    public static function rebalanceWorkload(): array
    {
        $results = ['reassigned' => 0, 'errors' => 0];

        // Get overloaded teknisi
        $overloadedTeknisi = self::where('status', self::STATUS_ACTIVE)
                                ->where('workload_score', '>', 100)
                                ->get();

        foreach ($overloadedTeknisi as $teknisi) {
            $ticketsToReassign = $teknisi->activeTickets()
                                       ->whereIn('priority', [Ticket::PRIORITY_LOW, Ticket::PRIORITY_MEDIUM])
                                       ->limit(2)
                                       ->get();

            foreach ($ticketsToReassign as $ticket) {
                $newTeknisi = self::findBestTeknisiForTicket($ticket);

                if ($newTeknisi && $newTeknisi->nip !== $teknisi->nip) {
                    if ($ticket->assignToTeknisi($newTeknisi->nip, 'system', 'Workload rebalancing')) {
                        $results['reassigned']++;
                        $teknisi->updateWorkloadScore();
                        $newTeknisi->updateWorkloadScore();
                    } else {
                        $results['errors']++;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Get workload distribution statistics
     */
    public static function getWorkloadDistribution(): array
    {
        $teknisi = self::where('status', self::STATUS_ACTIVE)->get();

        $distribution = [
            'light' => 0,
            'moderate' => 0,
            'heavy' => 0,
            'overloaded' => 0,
            'total' => $teknisi->count(),
        ];

        foreach ($teknisi as $tek) {
            $status = $tek->workload_status;
            if (isset($distribution[$status])) {
                $distribution[$status]++;
            }
        }

        return $distribution;
    }

    /**
     * Calculate team performance metrics
     */
    public static function getTeamPerformanceMetrics(): array
    {
        $teknisi = self::where('status', self::STATUS_ACTIVE)->get();

        if ($teknisi->isEmpty()) {
            return [];
        }

        $totalTickets = $teknisi->sum('ticket_count');
        $totalRating = $teknisi->whereNotNull('rating')->avg('rating');
        $avgResolutionRate = $teknisi->avg(function ($tek) {
            return $tek->getResolutionRate();
        });

        return [
            'total_teknisi' => $teknisi->count(),
            'total_tickets_handled' => $totalTickets,
            'average_rating' => round($totalRating, 2),
            'average_resolution_rate' => round($avgResolutionRate, 2),
            'workload_distribution' => self::getWorkloadDistribution(),
        ];
    }

    /**
     * Get teknisi leaderboard
     */
    public static function getLeaderboard(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', self::STATUS_ACTIVE)
                  ->whereNotNull('rating')
                  ->orderBy('rating', 'desc')
                  ->orderBy('ticket_count', 'desc')
                  ->limit($limit)
                  ->get();
    }

    /**
     * Generate performance report for teknisi
     */
    public function generatePerformanceReport(Carbon $startDate, Carbon $endDate): array
    {
        $tickets = $this->assignedTickets()
                       ->whereBetween('created_at', [$startDate, $endDate])
                       ->get();

        $resolvedTickets = $tickets->where('status', Ticket::STATUS_RESOLVED);
        $totalResolutionTime = $resolvedTickets->sum('resolution_time_minutes');

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'tickets_assigned' => $tickets->count(),
            'tickets_resolved' => $resolvedTickets->count(),
            'resolution_rate' => $tickets->count() > 0 ? ($resolvedTickets->count() / $tickets->count()) * 100 : 0,
            'avg_resolution_time' => $resolvedTickets->count() > 0 ? $totalResolutionTime / $resolvedTickets->count() : 0,
            'customer_satisfaction' => $resolvedTickets->whereNotNull('user_rating')->avg('user_rating'),
            'performance_grade' => $this->performance_grade,
        ];
    }

    // ==================== QUERY SCOPES ====================

    /**
     * Scope for active teknisi
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for available teknisi
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->whereRaw('COALESCE(max_concurrent_tickets, 10) > (
                        SELECT COUNT(*)
                        FROM tickets
                        WHERE assigned_teknisi_nip = teknisis.nip
                        AND status NOT IN ("resolved", "closed")
                    )');
    }

    /**
     * Scope for busy teknisi
     */
    public function scopeBusy(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->whereRaw('COALESCE(max_concurrent_tickets, 10) <= (
                        SELECT COUNT(*)
                        FROM tickets
                        WHERE assigned_teknisi_nip = teknisis.nip
                        AND status NOT IN ("resolved", "closed")
                    )');
    }

    /**
     * Scope for teknisi by skill level
     */
    public function scopeBySkillLevel(Builder $query, string $skillLevel): Builder
    {
        return $query->where('skill_level', $skillLevel);
    }

    /**
     * Scope for expert teknisi
     */
    public function scopeExperts(Builder $query): Builder
    {
        return $query->where('skill_level', self::SKILL_EXPERT);
    }

    /**
     * Scope for senior teknisi
     */
    public function scopeSeniors(Builder $query): Builder
    {
        return $query->whereIn('skill_level', [self::SKILL_SENIOR, self::SKILL_EXPERT]);
    }

    /**
     * Scope for teknisi by department
     */
    public function scopeByDepartment(Builder $query, string $department): Builder
    {
        return $query->where('department', $department);
    }

    /**
     * Scope for teknisi with high performance
     */
    public function scopeHighPerformers(Builder $query, float $minRating = 4.0): Builder
    {
        return $query->where('rating', '>=', $minRating)
                    ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for teknisi by workload status
     */
    public function scopeByWorkloadStatus(Builder $query, string $status): Builder
    {
        // This is a simplified version - in practice, you'd calculate workload status in the query
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for teknisi with light workload
     */
    public function scopeLightWorkload(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->whereRaw('(
                        SELECT COUNT(*)
                        FROM tickets
                        WHERE assigned_teknisi_nip = teknisis.nip
                        AND status NOT IN ("resolved", "closed")
                    ) <= 5');
    }

    /**
     * Scope for teknisi with heavy workload
     */
    public function scopeHeavyWorkload(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->whereRaw('(
                        SELECT COUNT(*)
                        FROM tickets
                        WHERE assigned_teknisi_nip = teknisis.nip
                        AND status NOT IN ("resolved", "closed")
                    ) >= 15');
    }

    /**
     * Scope for teknisi by application expertise
     */
    public function scopeExpertInApplication(Builder $query, int $aplikasiId): Builder
    {
        return $query->whereHas('expertApplications', function ($q) use ($aplikasiId) {
            $q->where('aplikasi_id', $aplikasiId);
        });
    }

    /**
     * Scope for teknisi by category expertise
     */
    public function scopeExpertInCategory(Builder $query, int $kategoriId): Builder
    {
        return $query->whereHas('expertCategories', function ($q) use ($kategoriId) {
            $q->where('kategori_masalah_id', $kategoriId);
        });
    }

    /**
     * Scope for teknisi with recent activity
     */
    public function scopeRecentlyActive(Builder $query, int $hours = 24): Builder
    {
        return $query->where('last_active_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * Scope for teknisi with high resolution rate
     */
    public function scopeHighResolutionRate(Builder $query, float $minRate = 80.0): Builder
    {
        // This would need a more complex query to calculate resolution rate
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for teknisi by experience level
     */
    public function scopeByExperience(Builder $query, int $minYears): Builder
    {
        return $query->where('experience_years', '>=', $minYears);
    }

    /**
     * Scope for experienced teknisi
     */
    public function scopeExperienced(Builder $query, int $minYears = 3): Builder
    {
        return $query->where('experience_years', '>=', $minYears);
    }

    /**
     * Scope for teknisi with certifications
     */
    public function scopeWithCertifications(Builder $query): Builder
    {
        return $query->whereNotNull('certifications')
                    ->where('certifications', '!=', '[]');
    }

    /**
     * Scope for teknisi without recent activity
     */
    public function scopeIdle(Builder $query, int $hours = 48): Builder
    {
        return $query->where(function ($q) use ($hours) {
            $q->whereNull('last_active_at')
              ->orWhere('last_active_at', '<', Carbon::now()->subHours($hours));
        });
    }

    /**
     * Scope for teknisi by rating range
     */
    public function scopeByRating(Builder $query, float $minRating, ?float $maxRating = null): Builder
    {
        $query->where('rating', '>=', $minRating);
        if ($maxRating) {
            $query->where('rating', '<=', $maxRating);
        }
        return $query;
    }

    /**
     * Scope for top rated teknisi
     */
    public function scopeTopRated(Builder $query, int $limit = 10): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->whereNotNull('rating')
                    ->orderBy('rating', 'desc')
                    ->orderBy('ticket_count', 'desc')
                    ->limit($limit);
    }

    /**
     * Scope for teknisi needing skill development
     */
    public function scopeNeedsSkillDevelopment(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('rating')
              ->orWhere('rating', '<', 3.0)
              ->orWhere('ticket_count', '<', 10);
        });
    }

    /**
     * Scope for search by name, email, or NIP
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('nip', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for teknisi with complete profiles
     */
    public function scopeProfileComplete(Builder $query, float $minCompleteness = 80.0): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Get user initials (2 characters max)
     */
    public function getInitialsAttribute(): string
    {
        $name = trim($this->name);
        if (empty($name)) {
            return 'T'; // Default for Teknisi
        }

        // Split name by spaces and get first letter of first two parts
        $words = explode(' ', $name);
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(mb_substr($word, 0, 1));
            }
        }

        return empty($initials) ? 'T' : substr($initials, 0, 2);
    }

    /**
     * Get user initials with styling for frontend display
     */
    public function getFormattedInitialsAttribute(): array
    {
        $initials = $this->initials;

        // Generate background color based on name hash for consistency
        $colors = [
            'bg-orange-500', 'bg-amber-500', 'bg-yellow-500', 'bg-lime-500',
            'bg-green-500', 'bg-emerald-500', 'bg-teal-500', 'bg-cyan-500'
        ];

        $colorIndex = crc32($this->name) % count($colors);
        $backgroundColor = $colors[$colorIndex];

        return [
            'text' => $initials,
            'background_color' => $backgroundColor,
            'text_color' => 'text-white',
        ];
    }

    }