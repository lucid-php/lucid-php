<?php

declare(strict_types=1);

namespace Core\Upload;

use Core\Http\UploadedFile;
use RuntimeException;

/**
 * Handles file upload storage with explicit configuration.
 * 
 * No magic paths, no auto-naming - everything is explicit.
 * Provides security helpers for path traversal prevention.
 */
class FileUploadHandler
{
    /**
     * @param string $uploadDirectory Base directory for uploads (absolute path)
     * @param bool $createDirectory Whether to create directory if it doesn't exist
     */
    public function __construct(
        private readonly string $uploadDirectory,
        private readonly bool $createDirectory = true,
    ) {
        if ($this->createDirectory && !is_dir($this->uploadDirectory)) {
            if (!mkdir($this->uploadDirectory, 0755, true)) {
                throw new RuntimeException("Failed to create upload directory: {$this->uploadDirectory}");
            }
        }

        if (!is_dir($this->uploadDirectory)) {
            throw new RuntimeException("Upload directory does not exist: {$this->uploadDirectory}");
        }

        if (!is_writable($this->uploadDirectory)) {
            throw new RuntimeException("Upload directory is not writable: {$this->uploadDirectory}");
        }
    }

    /**
     * Store uploaded file with explicit filename.
     * 
     * Prevents path traversal attacks by sanitizing filename.
     * Returns absolute path to stored file.
     * 
     * @param UploadedFile $file The uploaded file to store
     * @param string $filename Desired filename (will be sanitized)
     * @param string|null $subdirectory Optional subdirectory within upload directory
     * @return string Absolute path to stored file
     * @throws RuntimeException if storage fails
     */
    public function store(UploadedFile $file, string $filename, ?string $subdirectory = null): string
    {
        $safeFilename = $this->sanitizeFilename($filename);
        
        $targetDirectory = $this->uploadDirectory;
        if ($subdirectory !== null) {
            $safeSubdirectory = $this->sanitizePath($subdirectory);
            $targetDirectory = rtrim($this->uploadDirectory, '/') . '/' . $safeSubdirectory;
            
            if (!is_dir($targetDirectory)) {
                if (!mkdir($targetDirectory, 0755, true)) {
                    throw new RuntimeException("Failed to create subdirectory: $targetDirectory");
                }
            }
        }

        $destination = rtrim($targetDirectory, '/') . '/' . $safeFilename;

        // Prevent overwriting existing files - explicit behavior
        if (file_exists($destination)) {
            throw new RuntimeException("File already exists: $destination");
        }

        $file->moveTo($destination);

        return $destination;
    }

    /**
     * Store uploaded file with generated unique filename.
     * 
     * Uses uniqid() with more entropy for uniqueness.
     * Preserves original file extension.
     * 
     * @param UploadedFile $file The uploaded file to store
     * @param string|null $subdirectory Optional subdirectory
     * @return string Absolute path to stored file
     * @throws RuntimeException if storage fails
     */
    public function storeWithUniqueName(UploadedFile $file, ?string $subdirectory = null): string
    {
        $extension = $file->getExtension();
        $uniqueName = uniqid('upload_', true);
        
        if ($extension !== '') {
            $uniqueName .= '.' . $extension;
        }

        return $this->store($file, $uniqueName, $subdirectory);
    }

    /**
     * Store uploaded file with hashed filename.
     * 
     * Uses SHA-256 hash of file contents as filename.
     * Useful for content-addressable storage.
     * Returns existing file path if hash collision (same content already exists).
     * 
     * @param UploadedFile $file The uploaded file to store
     * @param string|null $subdirectory Optional subdirectory
     * @return string Absolute path to stored file
     * @throws RuntimeException if storage fails
     */
    public function storeWithHashedName(UploadedFile $file, ?string $subdirectory = null): string
    {
        $contents = $file->getContents();
        $hash = hash('sha256', $contents);
        $extension = $file->getExtension();
        
        $filename = $extension !== '' ? "$hash.$extension" : $hash;

        $targetDirectory = $this->uploadDirectory;
        if ($subdirectory !== null) {
            $safeSubdirectory = $this->sanitizePath($subdirectory);
            $targetDirectory = rtrim($this->uploadDirectory, '/') . '/' . $safeSubdirectory;
        }

        $destination = rtrim($targetDirectory, '/') . '/' . $filename;

        // If file with same hash exists, content is identical - return existing path
        if (file_exists($destination)) {
            return $destination;
        }

        return $this->store($file, $filename, $subdirectory);
    }

    /**
     * Delete a file from the upload directory.
     * 
     * Explicit deletion - no automatic cleanup.
     * Validates path is within upload directory to prevent path traversal.
     * 
     * @param string $path Absolute path to file
     * @throws RuntimeException if deletion fails or path is invalid
     */
    public function delete(string $path): void
    {
        $realPath = realpath($path);
        $realUploadDir = realpath($this->uploadDirectory);

        if ($realPath === false) {
            throw new RuntimeException("File does not exist: $path");
        }

        if ($realUploadDir === false) {
            throw new RuntimeException("Upload directory does not exist: {$this->uploadDirectory}");
        }

        // Ensure file is within upload directory (prevent path traversal)
        if (!str_starts_with($realPath, $realUploadDir)) {
            throw new RuntimeException("File is outside upload directory: $path");
        }

        if (!unlink($realPath)) {
            throw new RuntimeException("Failed to delete file: $path");
        }
    }

    /**
     * Sanitize filename to prevent path traversal and security issues.
     * 
     * Removes: directory separators, null bytes, control characters
     * Preserves: alphanumeric, dots, hyphens, underscores
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove any directory separators
        $filename = basename($filename);
        
        // Remove null bytes and control characters
        $filename = preg_replace('/[\x00-\x1F\x7F]/u', '', $filename);
        
        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);
        
        // Remove any remaining dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        if ($filename === '' || $filename === '.' || $filename === '..') {
            throw new RuntimeException("Invalid filename after sanitization");
        }

        return $filename;
    }

    /**
     * Sanitize path to prevent directory traversal.
     * 
     * Removes: .., null bytes, absolute path indicators
     */
    private function sanitizePath(string $path): string
    {
        // Remove null bytes
        $path = str_replace("\0", '', $path);
        
        // Remove directory traversal attempts (multiple passes to catch encoded attempts)
        do {
            $before = $path;
            $path = str_replace(['..', './'], '', $path);
        } while ($path !== $before);
        
        // Remove leading slashes (prevent absolute paths)
        $path = ltrim($path, '/\\');
        
        // Replace backslashes with forward slashes
        $path = str_replace('\\', '/', $path);
        
        if ($path === '' || str_contains($path, '..')) {
            throw new RuntimeException("Invalid path after sanitization");
        }

        return $path;
    }
}
