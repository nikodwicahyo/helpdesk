<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\SystemSettingsService;

class FileUploadController extends Controller
{
    /**
     * Get maximum file size in kilobytes from system settings
     */
    protected function getMaxFileSize(): int
    {
        return SystemSettingsService::getMaxFileSize() * 1024; // Convert MB to KB
    }

    /**
     * Get maximum files per ticket from system settings
     */
    protected function getMaxFilesPerTicket(): int
    {
        return SystemSettingsService::getMaxFilesPerTicket();
    }

    /**
     * Upload files for tickets
     */
    public function uploadTicketFiles(Request $request)
    {
        $maxFiles = $this->getMaxFilesPerTicket();
        $maxSize = $this->getMaxFileSize();
        $allowedExtensions = $this->getAllowedExtensions();

        $validator = Validator::make($request->all(), [
            'files' => "required|array|max:{$maxFiles}",
            'files.*' => "required|file|max:{$maxSize}|mimes:{$allowedExtensions}",
            'ticket_number' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors()->all(), 422);
        }

        try {
            $uploadedFiles = [];
            $ticketNumber = $request->input('ticket_number', 'temp_' . time());
            $files = $request->file('files');

            foreach ($files as $file) {
                if (!$file->isValid()) {
                    continue;
                }

                $originalName = $file->getClientOriginalName();
                $fileName = $this->generateUniqueFileName($originalName);
                $path = "tickets/{$ticketNumber}/{$fileName}";

                // Store file
                $storedPath = $file->storeAs(
                    "tickets/{$ticketNumber}",
                    $fileName,
                    'public'
                );

                if ($storedPath) {
                    $uploadedFiles[] = [
                        'original_name' => $originalName,
                        'file_name' => $fileName,
                        'file_path' => $storedPath,
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'download_url' => Storage::url($storedPath),
                        'uploaded_at' => now()->toIso8601String()
                    ];
                }
            }

            return $this->successResponse([
                'files' => $uploadedFiles,
                'ticket_number' => $ticketNumber
            ], 'Files uploaded successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload files', [$e->getMessage()], 500);
        }
    }

    /**
     * Delete uploaded file
     */
    public function deleteFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'ticket_number' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors()->all(), 422);
        }

        try {
            $filePath = $request->input('file_path');

            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
                return $this->successResponse(null, 'File deleted successfully');
            }

            return $this->errorResponse('File not found', ['File does not exist'], 404);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete file', [$e->getMessage()], 500);
        }
    }

    /**
     * Get file information
     */
    public function getFileInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', $validator->errors()->all(), 422);
        }

        try {
            $filePath = $request->input('file_path');

            if (!Storage::disk('public')->exists($filePath)) {
                return $this->errorResponse('File not found', ['File does not exist'], 404);
            }

            $fileInfo = [
                'file_path' => $filePath,
                'file_size' => Storage::disk('public')->size($filePath),
                'last_modified' => Storage::disk('public')->lastModified($filePath),
                'mime_type' => mime_content_type(Storage::disk('public')->path($filePath)),
                'url' => Storage::url($filePath),
                'exists' => true
            ];

            return $this->successResponse($fileInfo, 'File information retrieved');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get file information', [$e->getMessage()], 500);
        }
    }

    /**
     * Download file
     */
    public function downloadFile(Request $request, $ticketNumber, $fileName)
    {
        try {
            $filePath = "tickets/{$ticketNumber}/{$fileName}";

            if (!Storage::disk('public')->exists($filePath)) {
                abort(404, 'File not found');
            }

            $fullPath = Storage::disk('public')->path($filePath);

            return response()->download($fullPath, $fileName);

        } catch (\Exception $e) {
            abort(404, 'File not found');
        }
    }

    /**
     * Generate unique filename
     */
    private function generateUniqueFileName(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        $timestamp = time();
        $random = Str::random(6);

        return "{$filename}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get allowed file extensions from system settings
     */
    private function getAllowedExtensions(): string
    {
        $types = SystemSettingsService::getAllowedFileTypes();
        return implode(',', $types);
    }

    /**
     * Validate file type and size using system settings
     */
    private function validateFile($file): bool
    {
        $maxSizeBytes = $this->getMaxFileSize() * 1024;
        
        // Check file size
        if ($file->getSize() > $maxSizeBytes) {
            return false;
        }

        // Check extension
        $allowedTypes = SystemSettingsService::getAllowedFileTypes();
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedTypes)) {
            return false;
        }

        return true;
    }
}