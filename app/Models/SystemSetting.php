<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'category',
        'description',
        'is_public',
        'updated_by_nip',
        'updated_by_type',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'value' => 'string',
    ];

    /**
     * Cache duration in seconds (5 minutes)
     */
    const CACHE_DURATION = 300;

    /**
     * Get a setting value with type casting and caching.
     */
    public static function get($key, $default = null)
    {
        // Skip database access during testing - return defaults
        if (app()->environment('testing')) {
            return $default;
        }

        $cacheKey = "system_setting_{$key}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return $setting->getCastedValue();
        });
    }

    /**
     * Set a setting value with automatic type detection and cache clearing.
     */
    public static function set($key, $value, $category = 'general', $description = null)
    {
        // Clear cache for this key
        Cache::forget("system_setting_{$key}");

        // Auto-detect type from value
        $type = static::detectType($value);

        // Prepare value for storage
        $storedValue = static::prepareValueForStorage($value, $type);

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'category' => $category,
                'description' => $description,
            ]
        );

        // Log the change for audit trail
        \Log::info("System setting updated", [
            'key' => $key,
            'old_value' => $setting->getOriginal('value'),
            'new_value' => $storedValue,
            'type' => $type,
            'category' => $category,
            'updated_by' => auth()->user()?->nip ?? 'system',
        ]);

        return $setting;
    }

    /**
     * Get all settings in a category as key-value array.
     */
    public static function getByCategory($category)
    {
        $cacheKey = "system_settings_category_{$category}";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($category) {
            return static::where('category', $category)
                ->pluck('value', 'key')
                ->map(function ($value, $key) {
                    $setting = static::where('key', $key)->first();
                    return $setting ? $setting->getCastedValue() : $value;
                })
                ->toArray();
        });
    }

    /**
     * Get all public settings as key-value array.
     */
    public static function getPublic()
    {
        $cacheKey = 'system_settings_public';

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () {
            return static::where('is_public', true)
                ->pluck('value', 'key')
                ->map(function ($value, $key) {
                    $setting = static::where('key', $key)->first();
                    return $setting ? $setting->getCastedValue() : $value;
                })
                ->toArray();
        });
    }

    /**
     * Get the casted value based on the type column.
     */
    public function getCastedValue()
    {
        return match ($this->type) {
            'boolean' => $this->castToBoolean(),
            'integer' => $this->castToInteger(),
            'json', 'array' => $this->castToJson(),
            default => $this->value,
        };
    }

    /**
     * Set the value with automatic type detection.
     */
    public function setCastedValue($value)
    {
        $this->type = static::detectType($value);
        $this->value = static::prepareValueForStorage($value, $this->type);
    }

    /**
     * Automatically detect the type of a value.
     */
    public static function detectType($value)
    {
        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_array($value) || is_object($value)) {
            return 'json';
        }

        return 'string';
    }

    /**
     * Prepare value for storage based on type.
     */
    public static function prepareValueForStorage($value, $type)
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            'json', 'array' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Cast value to boolean.
     */
    private function castToBoolean()
    {
        return in_array(strtolower($this->value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Cast value to integer.
     */
    private function castToInteger()
    {
        return (int) $this->value;
    }

    /**
     * Cast value to JSON/array.
     */
    private function castToJson()
    {
        $decoded = json_decode($this->value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::warning("Failed to decode JSON value for setting {$this->key}", [
                'value' => $this->value,
                'error' => json_last_error_msg(),
            ]);

            return null;
        }

        return $decoded;
    }

    /**
     * Scope to filter by category.
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter public settings.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Get the user who updated this setting.
     */
    public function updater()
    {
        if (!$this->updated_by_type || !$this->updated_by_nip) {
            return null;
        }

        $modelClass = match ($this->updated_by_type) {
            'admin_helpdesk' => AdminHelpdesk::class,
            'admin_aplikasi' => AdminAplikasi::class,
            'teknisi' => Teknisi::class,
            'user' => User::class,
            default => null,
        };

        return $modelClass ? $modelClass::where('nip', $this->updated_by_nip)->first() : null;
    }

    /**
     * Clear all caches for this model.
     */
    public static function clearAllCaches()
    {
        // Clear individual setting caches
        $settings = static::all();
        foreach ($settings as $setting) {
            Cache::forget("system_setting_{$setting->key}");
        }

        // Clear category caches
        $categories = static::distinct('category')->pluck('category');
        foreach ($categories as $category) {
            Cache::forget("system_settings_category_{$category}");
        }

        // Clear public cache
        Cache::forget('system_settings_public');
    }

    /**
     * Boot the model and add event listeners.
     */
    protected static function booted()
    {
        static::updated(function ($setting) {
            // Clear caches when setting is updated
            Cache::forget("system_setting_{$setting->key}");
            Cache::forget("system_settings_category_{$setting->category}");
            Cache::forget('system_settings_public');
        });

        static::deleted(function ($setting) {
            // Clear caches when setting is deleted
            Cache::forget("system_setting_{$setting->key}");
            Cache::forget("system_settings_category_{$setting->category}");
            Cache::forget('system_settings_public');
        });
    }
}