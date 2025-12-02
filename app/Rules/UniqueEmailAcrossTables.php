<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueEmailAcrossTables implements ValidationRule
{
    /**
     * Validate that the email is unique across all user tables.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        // Normalize email for comparison
        $normalizedEmail = strtolower(trim($value));

        // Check across all role tables
        $exists = DB::table('users')->whereRaw('LOWER(email) = ?', [$normalizedEmail])->exists() ||
                  DB::table('admin_helpdesks')->whereRaw('LOWER(email) = ?', [$normalizedEmail])->exists() ||
                  DB::table('admin_aplikasis')->whereRaw('LOWER(email) = ?', [$normalizedEmail])->exists() ||
                  DB::table('teknisis')->whereRaw('LOWER(email) = ?', [$normalizedEmail])->exists();

        if ($exists) {
            $fail('Email sudah terdaftar dalam sistem');
        }
    }
}