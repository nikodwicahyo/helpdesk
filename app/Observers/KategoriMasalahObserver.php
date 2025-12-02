<?php

namespace App\Observers;

use App\Models\KategoriMasalah;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

class KategoriMasalahObserver
{
    /**
     * Handle the KategoriMasalah "created" event.
     */
    public function created(KategoriMasalah $category): void
    {
        try {
            AuditLogService::logCategoryCreated($category);
            
            Log::info("Category created successfully", [
                'category_id' => $category->id,
                'category_name' => $category->name,
            ]);
        } catch (\Exception $e) {
            Log::error("Error in KategoriMasalahObserver::created", [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the KategoriMasalah "updated" event.
     */
    public function updated(KategoriMasalah $category): void
    {
        try {
            $changes = $category->getChanges();
            AuditLogService::logCategoryUpdated($category, $changes);
            
            // Log specific status change if status was changed
            if ($category->wasChanged('status')) {
                $oldStatus = $category->getOriginal('status');
                AuditLogService::logCategoryStatusChanged($category, $oldStatus, $category->status);
            }
            
            Log::info("Category updated", [
                'category_id' => $category->id,
                'changes' => array_keys($changes),
            ]);
        } catch (\Exception $e) {
            Log::error("Error in KategoriMasalahObserver::updated", [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the KategoriMasalah "deleted" event.
     */
    public function deleted(KategoriMasalah $category): void
    {
        try {
            AuditLogService::logCategoryDeleted($category);
            
            Log::info("Category deleted", [
                'category_id' => $category->id,
                'category_name' => $category->name,
            ]);
        } catch (\Exception $e) {
            Log::error("Error in KategoriMasalahObserver::deleted", [
                'category_id' => $category->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
