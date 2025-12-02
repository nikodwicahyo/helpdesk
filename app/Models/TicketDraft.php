<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TicketDraft extends Model
{
    use HasFactory;

    protected $table = 'ticket_drafts';

    protected $fillable = [
        'user_nip',
        'aplikasi_id',
        'kategori_masalah_id',
        'title',
        'description',
        'priority',
        'location',
        'draft_data',
        'expires_at',
    ];

    protected $casts = [
        'draft_data' => 'array',
        'expires_at' => 'datetime',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'expires_at',
    ];

    /**
     * Get the user that owns the draft.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_nip', 'nip');
    }

    /**
     * Get the application for this draft.
     */
    public function aplikasi()
    {
        return $this->belongsTo(Aplikasi::class, 'aplikasi_id');
    }

    /**
     * Get the category for this draft.
     */
    public function kategoriMasalah()
    {
        return $this->belongsTo(KategoriMasalah::class, 'kategori_masalah_id');
    }

    /**
     * Scope to get only active (non-expired) drafts.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to get drafts for a specific user.
     */
    public function scopeForUser($query, $userNip)
    {
        return $query->where('user_nip', $userNip);
    }

    /**
     * Check if the draft has expired.
     */
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Set the expiration date to 7 days from now.
     */
    public function setExpirationDate()
    {
        $this->expires_at = Carbon::now()->addDays(7);
        return $this;
    }

    /**
     * Convert draft to format expected by frontend form.
     */
    public function toFormData()
    {
        return [
            'aplikasi_id' => $this->aplikasi_id,
            'kategori_masalah_id' => $this->kategori_masalah_id,
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority,
            'location' => $this->location,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Model event: creating - automatically set expiration date.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($draft) {
            if (is_null($draft->expires_at)) {
                $draft->setExpirationDate();
            }
        });
    }
}