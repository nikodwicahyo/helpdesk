<?php

namespace App\Http\Controllers\AdminHelpdesk;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Create a new backup.
     */
    public function create(Request $request)
    {
        $admin = Auth::user();

        try {
            $backup = $this->backupService->createBackup(
                type: 'manual',
                createdByNip: $admin->nip,
                createdByType: 'admin_helpdesk'
            );

            Log::info('Manual backup created', [
                'backup_id' => $backup->id,
                'admin_nip' => $admin->nip,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Backup created successfully',
                    'backup' => [
                        'id' => $backup->id,
                        'filename' => $backup->filename,
                        'size' => $backup->size_formatted,
                        'status' => $backup->status,
                        'created_at' => $backup->created_at->toISOString(),
                    ],
                ]);
            }

            return redirect()->back()->with('success', 'Backup created successfully');

        } catch (\Exception $e) {
            Log::error('Failed to create backup', [
                'admin_nip' => $admin->nip,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create backup: ' . $e->getMessage(),
                ], 500);
            }

            // For Inertia, return error as validation error to trigger onError
            return redirect()->back()->withErrors(['backup' => 'Failed to create backup: ' . $e->getMessage()]);
        }
    }

    /**
     * Download a specific backup.
     */
    public function download(Backup $backup): StreamedResponse
    {
        if (!$backup->isCompleted()) {
            abort(404, 'Backup not found or not completed');
        }

        $stream = $this->backupService->getBackupStream($backup);

        if (!$stream) {
            abort(404, 'Backup file not found');
        }

        Log::info('Backup downloaded', [
            'backup_id' => $backup->id,
            'admin_nip' => Auth::user()->nip,
        ]);

        return response()->streamDownload(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, $backup->filename, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $backup->filename . '"',
        ]);
    }

    /**
     * Download the latest completed backup.
     */
    public function downloadLatest(): StreamedResponse
    {
        $backup = $this->backupService->getLatestBackup();

        if (!$backup) {
            abort(404, 'No completed backups found');
        }

        return $this->download($backup);
    }

    /**
     * Delete a backup.
     */
    public function destroy(Request $request, Backup $backup)
    {
        $admin = Auth::user();

        try {
            $filename = $backup->filename;
            $success = $this->backupService->deleteBackup($backup);

            if ($success) {
                Log::info('Backup deleted', [
                    'filename' => $filename,
                    'admin_nip' => $admin->nip,
                ]);

                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Backup deleted successfully',
                    ]);
                }

                return redirect()->back()->with('success', 'Backup deleted successfully');
            }

            throw new \Exception('Failed to delete backup');

        } catch (\Exception $e) {
            Log::error('Failed to delete backup', [
                'backup_id' => $backup->id,
                'admin_nip' => $admin->nip,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete backup: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to delete backup');
        }
    }

    /**
     * Get backup history (for API/AJAX).
     */
    public function history(Request $request)
    {
        $filters = [];

        if ($request->filled('type')) {
            $filters['type'] = $request->input('type');
        }

        if ($request->filled('status')) {
            $filters['status'] = $request->input('status');
        }

        $backups = $this->backupService->getBackupHistory($filters, $request->input('limit', 50));

        return response()->json([
            'success' => true,
            'backups' => $backups->map(function ($backup) {
                return [
                    'id' => $backup->id,
                    'filename' => $backup->filename,
                    'type' => $backup->type,
                    'type_label' => $backup->type_label,
                    'size' => $backup->size_formatted,
                    'status' => $backup->status,
                    'status_label' => $backup->status_label,
                    'location' => $backup->location,
                    'created_at' => $backup->created_at->toISOString(),
                    'completed_at' => $backup->completed_at?->toISOString(),
                ];
            }),
            'statistics' => $this->backupService->getStatistics(),
        ]);
    }

    /**
     * Get backup statistics.
     */
    public function statistics()
    {
        return response()->json([
            'success' => true,
            'statistics' => $this->backupService->getStatistics(),
        ]);
    }
}
