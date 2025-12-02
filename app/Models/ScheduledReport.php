<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduledReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'report_type',
        'parameters',
        'filters',
        'schedule_frequency',
        'schedule_time',
        'recipients',
        'is_active',
        'last_run_at',
        'next_run_at',
        'description',
        'created_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'filters' => 'array',
        'recipients' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    /**
     * Get the admin helpdesk that created the scheduled report.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminHelpdesk::class, 'created_by', 'nip');
    }

    /**
     * Calculate the next run time based on frequency
     */
    public function calculateNextRun(): \Carbon\Carbon
    {
        $now = \Carbon\Carbon::now();
        $time = explode(':', $this->schedule_time);
        $hour = (int) $time[0];
        $minute = (int) ($time[1] ?? 0);

        switch ($this->schedule_frequency) {
            case 'daily':
                return $now->copy()->setTime($hour, $minute, 0)->addDay();
            case 'weekly':
                return $now->copy()->setTime($hour, $minute, 0)->addWeek();
            case 'monthly':
                return $now->copy()->setTime($hour, $minute, 0)->addMonth();
            default:
                return $now->copy()->setTime($hour, $minute, 0)->addDay();
        }
    }

    /**
     * Get formatted schedule time
     */
    public function getFormattedScheduleTimeAttribute(): string
    {
        return \Carbon\Carbon::parse($this->schedule_time)->format('g:i A');
    }

    /**
     * Get human readable frequency
     */
    public function getHumanFrequencyAttribute(): string
    {
        return match($this->schedule_frequency) {
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            default => 'Unknown',
        };
    }
}
