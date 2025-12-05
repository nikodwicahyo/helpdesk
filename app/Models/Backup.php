<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'type',
        'size',
        'status',
        'location',
        'path',
        'disk',
        'notes',
        'error_message',
        'include_files',
        'created_by_nip',
        'created_by_type',
        'completed_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'include_files' => 'boolean',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const TYPE_MANUAL = 'manual';
    const TYPE_DAILY = 'daily';
    const TYPE_WEEKLY = 'weekly';
    const TYPE_MONTHLY = 'monthly';

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    const LOCATION_LOCAL = 'local';
    const LOCATION_S3 = 's3';
    const LOCATION_GOOGLE_DRIVE = 'google_drive';

    /**
     * Get the user who created this backup.
     */
    public function creator()
    {
        if (!$this->created_by_type || !$this->created_by_nip) {
            return null;
        }

        $modelClass = match ($this->created_by_type) {
            'admin_helpdesk' => AdminHelpdesk::class,
            'admin_aplikasi' => AdminAplikasi::class,
            'teknisi' => Teknisi::class,
            'user' => User::class,
            default => null,
        };

        return $modelClass ? $modelClass::where('nip', $this->created_by_nip)->first() : null;
    }

    /**
     * Get human-readable file size.
     */
    public function getSizeFormattedAttribute(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Get status label for display.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get type label for display.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_MANUAL => 'Manual',
            self::TYPE_DAILY => 'Daily',
            self::TYPE_WEEKLY => 'Weekly',
            self::TYPE_MONTHLY => 'Monthly',
            default => ucfirst($this->type),
        };
    }

    /**
     * Scope to filter completed backups.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to filter failed backups.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by location.
     */
    public function scopeByLocation($query, string $location)
    {
        return $query->where('location', $location);
    }

    /**
     * Scope to get recent backups.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if backup is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if backup failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if backup is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Mark backup as in progress.
     */
    public function markAsInProgress(): void
    {
        $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    /**
     * Mark backup as completed.
     */
    public function markAsCompleted(int $size = null, string $path = null): void
    {
        $data = [
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ];

        if ($size !== null) {
            $data['size'] = $size;
        }

        if ($path !== null) {
            $data['path'] = $path;
        }

        $this->update($data);
    }

    /**
     * Mark backup as failed.
     */
    public function markAsFailed(string $errorMessage = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }
}
