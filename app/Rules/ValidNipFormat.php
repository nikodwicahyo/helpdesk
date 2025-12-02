<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidNipFormat implements ValidationRule
{
    /**
     * Validate Indonesian NIP format (18 digits with specific pattern).
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        // Remove any spaces or non-digit characters first
        $cleanedNip = preg_replace('/\D/', '', $value);

        // Check if exactly 18 digits
        if (strlen($cleanedNip) !== 18) {
            $fail('NIP harus 18 digit');
            return;
        }

        // Check if all digits
        if (!ctype_digit($cleanedNip)) {
            $fail('NIP hanya boleh berisi angka');
            return;
        }

        // Basic NIP pattern validation (first 8 digits = birth date in YYYYMMDD format)
        $birthDate = substr($cleanedNip, 0, 8);
        $year = substr($birthDate, 0, 4);
        $month = substr($birthDate, 4, 2);
        $day = substr($birthDate, 6, 2);

        // Validate year range (reasonable birth years)
        if ($year < 1920 || $year > 2050) {
            $fail('Format NIP tidak valid (tahun lahir tidak sesuai)');
            return;
        }

        // Validate month
        if ($month < 1 || $month > 12) {
            $fail('Format NIP tidak valid (bulan lahir tidak sesuai)');
            return;
        }

        // Validate day based on month
        $maxDays = 31;
        if (in_array($month, [4, 6, 9, 11])) {
            $maxDays = 30;
        } elseif ($month == 2) {
            // Check for leap year
            $isLeapYear = ($year % 4 == 0 && $year % 100 != 0) || ($year % 400 == 0);
            $maxDays = $isLeapYear ? 29 : 28;
        }

        if ($day < 1 || $day > $maxDays) {
            $fail('Format NIP tidak valid (tanggal lahir tidak sesuai)');
            return;
        }
    }
}