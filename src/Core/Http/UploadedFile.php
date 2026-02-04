<?php

declare(strict_types=1);

namespace Core\Http;

use RuntimeException;

/**
 * Represents an uploaded file from a multipart/form-data request.
 * 
 * Immutable value object wrapping PHP's $_FILES array.
 * All properties are explicitly typed - no hidden state.
 */
readonly class UploadedFile
{
    /**
     * @param string $name Original filename from the client
     * @param string $tmpPath Temporary path where PHP stored the file
     * @param int $size File size in bytes
     * @param int $error PHP upload error code (UPLOAD_ERR_*)
     * @param string|null $mimeType MIME type reported by the client (untrusted)
     */
    public function __construct(
        public string $name,
        public string $tmpPath,
        public int $size,
        public int $error,
        public ?string $mimeType = null,
    ) {
    }

    /**
     * Create from PHP's $_FILES array entry.
     */
    public static function fromArray(array $fileData): self
    {
        return new self(
            name: $fileData['name'] ?? '',
            tmpPath: $fileData['tmp_name'] ?? '',
            size: $fileData['size'] ?? 0,
            error: $fileData['error'] ?? UPLOAD_ERR_NO_FILE,
            mimeType: $fileData['type'] ?? null,
        );
    }

    /**
     * Check if upload was successful.
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * Get human-readable error message.
     */
    public function getErrorMessage(): string
    {
        return match ($this->error) {
            UPLOAD_ERR_OK => 'No error',
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension',
            default => 'Unknown upload error',
        };
    }

    /**
     * Get file extension (lowercase, without dot).
     */
    public function getExtension(): string
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    /**
     * Get actual MIME type by reading file contents.
     * 
     * More reliable than client-provided MIME type.
     * Returns null if file doesn't exist or can't be read.
     */
    public function getActualMimeType(): ?string
    {
        if (!file_exists($this->tmpPath)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }

        $mimeType = finfo_file($finfo, $this->tmpPath);
        finfo_close($finfo);

        return $mimeType !== false ? $mimeType : null;
    }

    /**
     * Move uploaded file to destination.
     * 
     * Explicit method - no automatic storage.
     * Caller is responsible for path validation.
     * 
     * @throws RuntimeException if move fails
     */
    public function moveTo(string $destination): void
    {
        if (!$this->isValid()) {
            throw new RuntimeException("Cannot move invalid upload: {$this->getErrorMessage()}");
        }

        if (!is_uploaded_file($this->tmpPath)) {
            throw new RuntimeException("File was not uploaded via HTTP POST");
        }

        $directory = dirname($destination);
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new RuntimeException("Failed to create directory: $directory");
            }
        }

        if (!move_uploaded_file($this->tmpPath, $destination)) {
            throw new RuntimeException("Failed to move uploaded file to: $destination");
        }
    }

    /**
     * Get file contents.
     * 
     * @throws RuntimeException if file can't be read
     */
    public function getContents(): string
    {
        if (!$this->isValid()) {
            throw new RuntimeException("Cannot read invalid upload: {$this->getErrorMessage()}");
        }

        $contents = file_get_contents($this->tmpPath);
        if ($contents === false) {
            throw new RuntimeException("Failed to read uploaded file");
        }

        return $contents;
    }
}
