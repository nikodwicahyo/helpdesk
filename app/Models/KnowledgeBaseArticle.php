<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class KnowledgeBaseArticle extends Model
{
    use SoftDeletes;

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'author_nip',
        'title',
        'content',
        'summary',
        'kategori_masalah_id',
        'aplikasi_id',
        'tags',
        'status',
        'view_count',
        'helpful_count',
        'is_featured',
    ];

    protected $casts = [
        'tags' => 'array',
        'view_count' => 'integer',
        'helpful_count' => 'integer',
        'is_featured' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the author (teknisi) who wrote this article
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Teknisi::class, 'author_nip', 'nip');
    }

    /**
     * Get the problem category this article relates to
     */
    public function kategoriMasalah(): BelongsTo
    {
        return $this->belongsTo(KategoriMasalah::class, 'kategori_masalah_id');
    }

    /**
     * Get the application this article relates to
     */
    public function aplikasi(): BelongsTo
    {
        return $this->belongsTo(Aplikasi::class, 'aplikasi_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope for published articles
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope for draft articles
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for featured articles
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for searching articles
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%")
              ->orWhere('summary', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for filtering by application
     */
    public function scopeByApplication(Builder $query, int $aplikasiId): Builder
    {
        return $query->where('aplikasi_id', $aplikasiId);
    }

    /**
     * Scope for filtering by category
     */
    public function scopeByCategory(Builder $query, int $kategoriId): Builder
    {
        return $query->where('kategori_masalah_id', $kategoriId);
    }

    /**
     * Scope for popular articles (most viewed)
     */
    public function scopePopular(Builder $query, int $limit = 10): Builder
    {
        return $query->published()
                    ->orderBy('view_count', 'desc')
                    ->limit($limit);
    }

    /**
     * Scope for helpful articles (most marked as helpful)
     */
    public function scopeHelpful(Builder $query, int $limit = 10): Builder
    {
        return $query->published()
                    ->orderBy('helpful_count', 'desc')
                    ->limit($limit);
    }

    // ==================== METHODS ====================

    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Increment helpful count
     */
    public function markAsHelpful(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * Publish article
     */
    public function publish(): bool
    {
        $this->status = self::STATUS_PUBLISHED;
        return $this->save();
    }

    /**
     * Archive article
     */
    public function archive(): bool
    {
        $this->status = self::STATUS_ARCHIVED;
        return $this->save();
    }

    /**
     * Check if article is published
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Check if article is draft
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Get formatted created date
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('d M Y, H:i');
    }

    /**
     * Get formatted updated date
     */
    public function getFormattedUpdatedAtAttribute(): string
    {
        return $this->updated_at->format('d M Y, H:i');
    }

    /**
     * Get reading time estimate in minutes
     */
    public function getReadingTimeAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        return max(1, ceil($wordCount / 200)); // Assuming 200 words per minute
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PUBLISHED => 'success',
            self::STATUS_DRAFT => 'warning',
            self::STATUS_ARCHIVED => 'secondary',
            default => 'light',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ARCHIVED => 'Archived',
            default => 'Unknown',
        };
    }
}
