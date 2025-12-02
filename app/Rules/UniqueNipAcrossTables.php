<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueNipAcrossTables implements ValidationRule
{
    /**
     * Validate that the NIP is unique across all user tables.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        // Check across all role tables
        $exists = DB::table('users')->where('nip', $value)->exists() ||
                  DB::table('admin_helpdesks')->where('nip', $value)->exists() ||
                  DB::table('admin_aplikasis')->where('nip', $value)->exists() ||
                  DB::table('teknisis')->where('nip', $value)->exists();

        if ($exists) {
            $fail('NIP sudah terdaftar dalam sistem');
        }
    }
}