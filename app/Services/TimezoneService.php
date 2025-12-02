<?php

namespace App\Services;

use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling timezone-aware date/time operations
 * 
 * Usage:
 * 
 * $systemTime = TimezoneService::now();
 * $formatted = TimezoneService::format($date, 'datetime');
 * $isWorking = TimezoneService::isWithinWorkingHours($time);
 */
class TimezoneService
{
    /**
     * Get system timezone from settings
     * 
     * @return string Timezone string (e.g., 'Asia/Jakarta')
     */
    public static function getSystemTimezone(): string
    {
        return SystemSetting::get('timezone', config('app.timezone', 'Asia/Jakarta'));
    }
    
    /**
     * Get current time in system timezone
     * 
     * @return Carbon
     */
    public static function now(): Carbon
    {
        return Carbon::now(self::getSystemTimezone());
    }
    
    /**
     * Convert any date to system timezone
     * 
     * @param Carbon|string $date Date to convert
     * @return Carbon Date in system timezone
     */
    public static function toSystemTimezone($date): Carbon
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        
        return $date->setTimezone(self::getSystemTimezone());
    }
    
    /**
     * Format date/time according to system timezone and language
     * 
     * @param Carbon|string $date
     * @param string $format Format type: 'date', 'time', 'datetime', 'short', 'long'
     * @return string Formatted date string
     */
    public static function format($date, string $format = 'datetime'): string
    {
        if (!$date) {
            return '';
        }
        
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        $carbon = self::toSystemTimezone($carbon);
        
        // Get language setting
        $language = SystemSetting::get('default_language', 'id');
        $carbon->locale($language === 'id' ? 'id_ID' : 'en_US');
        
        switch ($format) {
            case 'date':
                return $carbon->format('d M Y');
            
            case 'time':
                return $carbon->format('H:i');
            
            case 'datetime':
                return $carbon->format('d M Y H:i');
            
            case 'short':
                return $carbon->format('d/m/Y');
            
            case 'long':
                return $carbon->translatedFormat('l, d F Y');
            
            case 'full':
                return $carbon->translatedFormat('l, d F Y H:i');
            
            case 'relative':
                return $carbon->diffForHumans();
            
            case 'iso':
                return $carbon->toIso8601String();
            
            default:
                return $carbon->format($format);
        }
    }
    
    /**
     * Get working hours from settings
     * 
     * @return array ['start' => '08:00', 'end' => '17:00', 'days' => [1,2,3,4,5]]
     */
    public static function getWorkingHours(): array
    {
        return [
            'start' => SystemSetting::get('working_hours_start', '08:00'),
            'end' => SystemSetting::get('working_hours_end', '17:00'),
            'days' => SystemSetting::get('working_days', [1, 2, 3, 4, 5]), // Monday-Friday
        ];
    }
    
    /**
     * Check if given time is within working hours
     * 
     * @param Carbon $time
     * @return bool
     */
    public static function isWithinWorkingHours(Carbon $time): bool
    {
        $workingHours = self::getWorkingHours();
        
        // Check if day is a working day (1 = Monday, 7 = Sunday)
        $dayOfWeek = $time->dayOfWeekIso;
        if (!in_array($dayOfWeek, $workingHours['days'])) {
            return false;
        }
        
        // Check if time is within working hours
        $startTime = Carbon::parse($workingHours['start'], self::getSystemTimezone());
        $endTime = Carbon::parse($workingHours['end'], self::getSystemTimezone());
        
        $checkTime = $time->copy()->setDate(
            $startTime->year,
            $startTime->month,
            $startTime->day
        );
        
        return $checkTime->between($startTime, $endTime);
    }
    
    /**
     * Calculate business hours between two dates
     * (Only counts working hours and working days)
     * 
     * @param Carbon $start
     * @param Carbon $end
     * @return float Number of business hours
     */
    public static function calculateBusinessHours(Carbon $start, Carbon $end): float
    {
        $workingHours = self::getWorkingHours();
        $businessHours = 0;
        
        $startTime = Carbon::parse($workingHours['start']);
        $endTime = Carbon::parse($workingHours['end']);
        $hoursPerDay = $endTime->diffInHours($startTime);
        
        $current = $start->copy();
        
        while ($current->lessThan($end)) {
            // Check if current day is a working day
            if (in_array($current->dayOfWeekIso, $workingHours['days'])) {
                // Get start and end of working day
                $dayStart = $current->copy()->setTimeFrom($startTime);
                $dayEnd = $current->copy()->setTimeFrom($endTime);
                
                // Calculate overlap with [start, end] period
                $periodStart = $current->lessThan($start) ? $start : $dayStart;
                $periodEnd = $end->lessThan($dayEnd) ? $end : $dayEnd;
                
                // Only count if there's an overlap
                if ($periodStart->lessThan($periodEnd)) {
                    $businessHours += $periodStart->floatDiffInHours($periodEnd);
                }
            }
            
            // Move to next day
            $current->addDay()->startOfDay();
        }
        
        return round($businessHours, 2);
    }
    
    /**
     * Add business hours to a date
     * (Skips non-working hours and non-working days)
     * 
     * @param Carbon $start Starting date/time
     * @param float $hours Number of business hours to add
     * @return Carbon Resulting date/time
     */
    public static function addBusinessHours(Carbon $start, float $hours): Carbon
    {
        $workingHours = self::getWorkingHours();
        $result = $start->copy();
        $remainingHours = $hours;
        
        $startTime = Carbon::parse($workingHours['start']);
        $endTime = Carbon::parse($workingHours['end']);
        $hoursPerDay = $endTime->diffInHours($startTime);
        
        while ($remainingHours > 0) {
            // Skip to next working day if current day is not a working day
            while (!in_array($result->dayOfWeekIso, $workingHours['days'])) {
                $result->addDay()->setTimeFrom($startTime);
            }
            
            // Ensure we're within working hours
            $dayStart = $result->copy()->setTimeFrom($startTime);
            $dayEnd = $result->copy()->setTimeFrom($endTime);
            
            if ($result->lessThan($dayStart)) {
                $result = $dayStart;
            }
            
            if ($result->greaterThanOrEqualTo($dayEnd)) {
                // Move to next working day
                $result->addDay()->setTimeFrom($startTime);
                continue;
            }
            
            // Calculate hours available in current day
            $hoursAvailable = $result->floatDiffInHours($dayEnd);
            
            if ($remainingHours <= $hoursAvailable) {
                // Can finish today
                $result->addHours($remainingHours);
                $remainingHours = 0;
            } else {
                // Need to continue tomorrow
                $remainingHours -= $hoursAvailable;
                $result->addDay()->setTimeFrom($startTime);
            }
        }
        
        return $result;
    }
    
    /**
     * Get timezone offset in hours
     * 
     * @param string|null $timezone
     * @return float Offset in hours (e.g., 7 for WIB, 8 for WITA, 9 for WIT)
     */
    public static function getTimezoneOffset(?string $timezone = null): float
    {
        $tz = $timezone ?? self::getSystemTimezone();
        $dt = new \DateTime('now', new \DateTimeZone($tz));
        return $dt->getOffset() / 3600;
    }
    
    /**
     * Get timezone name for display
     * 
     * @param string|null $timezone
     * @return string Display name (e.g., "WIB", "WITA", "WIT")
     */
    public static function getTimezoneDisplayName(?string $timezone = null): string
    {
        $tz = $timezone ?? self::getSystemTimezone();
        
        $displayNames = [
            'Asia/Jakarta' => 'WIB (UTC+7)',
            'Asia/Makassar' => 'WITA (UTC+8)',
            'Asia/Jayapura' => 'WIT (UTC+9)',
        ];
        
        return $displayNames[$tz] ?? $tz;
    }
    
    /**
     * Get all available Indonesian timezones
     * 
     * @return array
     */
    public static function getIndonesianTimezones(): array
    {
        return [
            'Asia/Jakarta' => 'WIB - Waktu Indonesia Barat (UTC+7)',
            'Asia/Makassar' => 'WITA - Waktu Indonesia Tengah (UTC+8)',
            'Asia/Jayapura' => 'WIT - Waktu Indonesia Timur (UTC+9)',
        ];
    }
    
    /**
     * Convert between Indonesian timezones
     * 
     * @param Carbon $date
     * @param string $fromTimezone
     * @param string $toTimezone
     * @return Carbon
     */
    public static function convertBetweenTimezones(Carbon $date, string $fromTimezone, string $toTimezone): Carbon
    {
        return $date->copy()
            ->setTimezone($fromTimezone)
            ->setTimezone($toTimezone);
    }
    
    /**
     * Log timezone-related operation
     * 
     * @param string $message
     * @param array $context
     */
    private static function log(string $message, array $context = []): void
    {
        Log::info('[TimezoneService] ' . $message, array_merge($context, [
            'system_timezone' => self::getSystemTimezone(),
        ]));
    }
}
