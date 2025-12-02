<?php

namespace App\Services;

use App\Models\AdminHelpdesk;
use App\Models\AdminAplikasi;

class AdminValidationService
{
    /**
     * Check if the given NIP exists in either admin_helpdesks or admin_aplikasis table
     */
    public static function isValidAdminNip(?string $nip): bool
    {
        if (!$nip) {
            return true; // Allow null values
        }

        return AdminHelpdesk::where('nip', $nip)->exists() ||
               AdminAplikasi::where('nip', $nip)->exists();
    }

    /**
     * Get the admin type for a given NIP
     */
    public static function getAdminType(?string $nip): ?string
    {
        if (!$nip) {
            return null;
        }

        if (AdminHelpdesk::where('nip', $nip)->exists()) {
            return 'admin_helpdesk';
        }

        if (AdminAplikasi::where('nip', $nip)->exists()) {
            return 'admin_aplikasi';
        }

        return null;
    }

    /**
     * Get the admin model instance for a given NIP
     */
    public static function getAdminModel(?string $nip)
    {
        if (!$nip) {
            return null;
        }

        // Try admin_helpdesks first
        $adminHelpdesk = AdminHelpdesk::where('nip', $nip)->first();
        if ($adminHelpdesk) {
            return $adminHelpdesk;
        }

        // Try admin_aplikasis
        return AdminAplikasi::where('nip', $nip)->first();
    }

    /**
     * Validate multiple admin NIPs
     */
    public static function validateAdminNips(array $nips): array
    {
        $results = [];

        foreach ($nips as $nip) {
            $results[$nip] = [
                'is_valid' => self::isValidAdminNip($nip),
                'type' => self::getAdminType($nip),
                'model' => self::getAdminModel($nip),
            ];
        }

        return $results;
    }
}