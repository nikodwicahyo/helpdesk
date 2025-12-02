<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    /**
        * Maximum file size in kilobytes (2048 KB = 2MB)
     */
    const MAX_FILE_SIZE = 2048;

    /**
     * Allowed file types
     */
    const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    /**
     * Upload files for tickets
     */
    public function uploadTicketFiles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|max:5',
            'files.*' => 'required|file|max:' . self::MAX_FILE_SIZE . '|mimes:' . $this->getAllowedExtensions(),
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
     * Get allowed file extensions
     */
    private function getAllowedExtensions(): string
    {
        return 'jpeg,jpg,png,gif,webp,pdf,doc,docx,txt,xls,xlsx';
    }

    /**
     * Validate file type and size
     */
    private function validateFile($file): bool
    {
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE * 1024) {
            return false;
        }

        // Check mime type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIMES)) {
            return false;
        }

        return true;
    }
}