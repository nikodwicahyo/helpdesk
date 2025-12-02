<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling file uploads with system settings validation
 * 
 * Usage:
 * 
 * $validation = FileUploadService::validate($file, $existingFiles);
 * if (!$validation['valid']) {
 *     return back()->withErrors(['file' => $validation['errors']]);
 * }
 * 
 * $path = FileUploadService::store($file, 'tickets');
 */
class FileUploadService
{
    /**
     * Validate uploaded file against system settings
     * 
     * @param UploadedFile $file The uploaded file
     * @param array $existingFiles Array of existing files (for max files check)
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validate(UploadedFile $file, array $existingFiles = []): array
    {
        $errors = [];
        
        // Get settings from database
        $maxSize = SystemSetting::get('max_file_size', 2); // MB
        $maxFiles = SystemSetting::get('max_files_per_ticket', 5);
        $allowedTypes = SystemSetting::get('allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png');
        
        // Validate file size
        $fileSizeInMB = $file->getSize() / 1024 / 1024; // Convert to MB
        if ($fileSizeInMB > $maxSize) {
            $errors[] = "File size (" . round($fileSizeInMB, 2) . "MB) exceeds maximum allowed size of {$maxSize}MB";
        }
        
        // Validate file extension
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedTypesArray = explode(',', $allowedTypes);
        $allowedTypesArray = array_map('trim', $allowedTypesArray);
        $allowedTypesArray = array_map('strtolower', $allowedTypesArray);
        
        if (!in_array($extension, $allowedTypesArray)) {
            $errors[] = "File type '.{$extension}' is not allowed. Allowed types: " . $allowedTypes;
        }
        
        // Validate MIME type for security
        $mimeType = $file->getMimeType();
        if (!self::isAllowedMimeType($mimeType, $extension)) {
            $errors[] = "File MIME type '{$mimeType}' does not match extension '.{$extension}'. Possible security risk.";
        }
        
        // Validate max files count
        if (count($existingFiles) >= $maxFiles) {
            $errors[] = "Maximum of {$maxFiles} files allowed per ticket. Please delete existing files before uploading new ones.";
        }
        
        // Validate filename
        $filename = $file->getClientOriginalName();
        if (strlen($filename) > 255) {
            $errors[] = "Filename is too long. Maximum 255 characters allowed.";
        }
        
        // Check for potentially dangerous filenames
        if (preg_match('/[<>:"|?*]/', $filename)) {
            $errors[] = "Filename contains illegal characters.";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'file_info' => [
                'name' => $filename,
                'size' => $fileSizeInMB,
                'extension' => $extension,
                'mime_type' => $mimeType,
            ],
        ];
    }
    
    /**
     * Store uploaded file
     * 
     * @param UploadedFile $file
     * @param string $directory Storage directory
     * @param string $disk Storage disk (default: public)
     * @return string Stored file path
     */
    public static function store(UploadedFile $file, string $directory = 'uploads', string $disk = 'public'): string
    {
        // Generate safe filename
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = time() . '_' . uniqid() . '.' . $extension;
        
        // Store file
        $path = $file->storeAs($directory, $filename, $disk);
        
        // Log upload
        Log::info('File uploaded successfully', [
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
        
        return $path;
    }
    
    /**
     * Delete stored file
     * 
     * @param string $path File path
     * @param string $disk Storage disk
     * @return bool
     */
    public static function delete(string $path, string $disk = 'public'): bool
    {
        if (Storage::disk($disk)->exists($path)) {
            $deleted = Storage::disk($disk)->delete($path);
            
            if ($deleted) {
                Log::info('File deleted successfully', ['path' => $path]);
            }
            
            return $deleted;
        }
        
        return false;
    }
    
    /**
     * Get Laravel validation rules based on system settings
     * 
     * @return array Validation rules array
     */
    public static function getValidationRules(): array
    {
        $maxSize = SystemSetting::get('max_file_size', 2) * 1024; // Convert to KB
        $allowedTypes = SystemSetting::get('allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png');
        
        // Convert comma-separated string to validation format
        $mimes = str_replace(',', ',', trim($allowedTypes));
        
        return [
            'required',
            'file',
            "max:{$maxSize}", // KB
            "mimes:{$mimes}",
        ];
    }
    
    /**
     * Get maximum file size in human-readable format
     * 
     * @return string e.g., "2MB"
     */
    public static function getMaxFileSizeFormatted(): string
    {
        $maxSize = SystemSetting::get('max_file_size', 2);
        return $maxSize . 'MB';
    }
    
    /**
     * Get allowed file types as array
     * 
     * @return array
     */
    public static function getAllowedFileTypes(): array
    {
        $allowedTypes = SystemSetting::get('allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png');
        return array_map('trim', explode(',', $allowedTypes));
    }
    
    /**
     * Check if MIME type is allowed for given extension
     * 
     * @param string $mimeType
     * @param string $extension
     * @return bool
     */
    private static function isAllowedMimeType(string $mimeType, string $extension): bool
    {
        // Mapping of extensions to allowed MIME types
        $allowedMimeTypes = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'txt' => ['text/plain'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            'rar' => ['application/x-rar-compressed'],
        ];
        
        if (!isset($allowedMimeTypes[$extension])) {
            // Unknown extension, allow any MIME type
            return true;
        }
        
        return in_array($mimeType, $allowedMimeTypes[$extension]);
    }
    
    /**
     * Get file icon based on extension
     * 
     * @param string $extension
     * @return string Icon class or emoji
     */
    public static function getFileIcon(string $extension): string
    {
        $icons = [
            'pdf' => '📄',
            'doc' => '📝',
            'docx' => '📝',
            'xls' => '📊',
            'xlsx' => '📊',
            'jpg' => '🖼️',
            'jpeg' => '🖼️',
            'png' => '🖼️',
            'gif' => '🖼️',
            'txt' => '📃',
            'zip' => '🗜️',
            'rar' => '🗜️',
        ];
        
        return $icons[strtolower($extension)] ?? '📎';
    }
    
    /**
     * Format file size to human-readable format
     * 
     * @param int $bytes
     * @return string
     */
    public static function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
