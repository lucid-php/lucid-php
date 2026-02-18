<?php

declare(strict_types=1);

namespace Core\Http;

use RuntimeException;

/**
 * Streamed HTTP Response for Large Files
 * 
 * Streams files in chunks to avoid loading entire file into memory.
 * Supports range requests for partial content (video/audio seeking).
 * 
 * Philosophy:
 * - Explicit chunk size (no hidden defaults)
 * - Explicit range support (opt-in, not automatic)
 * - No magic buffering or compression
 * - All behavior traceable and controllable
 */
class StreamedResponse
{
    private const DEFAULT_CHUNK_SIZE = 8192; // 8KB chunks

    /**
     * @param string $path Absolute path to file to stream
     * @param string|null $filename Download filename (defaults to basename)
     * @param string $mimeType MIME type for Content-Type header
     * @param bool $inline Whether to display inline (true) or force download (false)
     * @param int $chunkSize Size of chunks to stream (bytes)
     * @param bool $supportRanges Whether to support HTTP range requests
     */
    public function __construct(
        private readonly string $path,
        private readonly ?string $filename = null,
        private readonly string $mimeType = 'application/octet-stream',
        private readonly bool $inline = false,
        private readonly int $chunkSize = self::DEFAULT_CHUNK_SIZE,
        private readonly bool $supportRanges = true,
    ) {
        if (!file_exists($this->path)) {
            throw new RuntimeException("File not found: {$this->path}");
        }

        if (!is_readable($this->path)) {
            throw new RuntimeException("File not readable: {$this->path}");
        }
    }

    /**
     * Send the streamed response.
     * 
     * Handles range requests if enabled and requested.
     * Streams file in chunks to minimize memory usage.
     */
    public function send(): void
    {
        $fileSize = filesize($this->path);
        if ($fileSize === false) {
            throw new RuntimeException("Failed to get file size: {$this->path}");
        }

        $filename = $this->filename ?? basename($this->path);
        $disposition = $this->inline ? 'inline' : 'attachment';

        // Encode filename to prevent header injection and properly handle special characters
        // Use RFC 5987 encoding for international characters
        $encodedFilename = rawurlencode($filename);
        
        // Sanitize filename for ASCII compatibility - remove control characters and quotes
        // Keep printable ASCII (0x20-0x7E) including spaces for better user experience
        // Spaces are safe within quoted filename parameter per RFC 2616
        $safeFilename = preg_replace('/[^\x20-\x7E]/', '', $filename);
        $safeFilename = str_replace(['"', '\\', "\r", "\n"], '', $safeFilename); // Remove problematic chars
        
        $contentDisposition = "$disposition; filename=\"$safeFilename\"; filename*=UTF-8''" . $encodedFilename;

        // Check for range request
        $rangeHeader = $_SERVER['HTTP_RANGE'] ?? null;
        $range = $this->supportRanges && $rangeHeader ? $this->parseRange($rangeHeader, $fileSize) : null;

        if ($range !== null) {
            // Partial content response
            http_response_code(206);
            header('Content-Type: ' . $this->mimeType);
            header('Content-Disposition: ' . $contentDisposition);
            header('Accept-Ranges: bytes');
            header('Content-Range: bytes ' . $range['start'] . '-' . $range['end'] . '/' . $fileSize);
            header('Content-Length: ' . $range['length']);

            $this->streamRange($range['start'], $range['end']);
        } else {
            // Full content response
            http_response_code(200);
            header('Content-Type: ' . $this->mimeType);
            header('Content-Disposition: ' . $contentDisposition);
            header('Content-Length: ' . (string) $fileSize);
            
            if ($this->supportRanges) {
                header('Accept-Ranges: bytes');
            }

            $this->streamFull();
        }
    }

    /**
     * Parse HTTP Range header.
     * 
     * Returns array with start, end, and length, or null if invalid.
     * 
     * @return array{start: int, end: int, length: int}|null
     */
    private function parseRange(string $rangeHeader, int $fileSize): ?array
    {
        // Format: "bytes=start-end"
        if (!preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $matches)) {
            return null;
        }

        $start = (int) $matches[1];
        $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

        // Validate range
        if ($start > $end || $start >= $fileSize || $end >= $fileSize) {
            return null;
        }

        return [
            'start' => $start,
            'end' => $end,
            'length' => $end - $start + 1,
        ];
    }

    /**
     * Stream entire file in chunks.
     */
    private function streamFull(): void
    {
        $handle = fopen($this->path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Failed to open file: {$this->path}");
        }

        try {
            while (!feof($handle)) {
                $chunk = fread($handle, $this->chunkSize);
                if ($chunk === false) {
                    throw new RuntimeException("Failed to read from file: {$this->path}");
                }
                echo $chunk;
                flush();
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Stream a specific byte range of the file.
     */
    private function streamRange(int $start, int $end): void
    {
        $handle = fopen($this->path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Failed to open file: {$this->path}");
        }

        try {
            // Seek to start position
            if (fseek($handle, $start) === -1) {
                throw new RuntimeException("Failed to seek in file: {$this->path}");
            }

            $remaining = $end - $start + 1;

            while ($remaining > 0 && !feof($handle)) {
                $readSize = min($this->chunkSize, $remaining);
                $chunk = fread($handle, $readSize);
                
                if ($chunk === false) {
                    throw new RuntimeException("Failed to read from file: {$this->path}");
                }

                echo $chunk;
                flush();
                $remaining -= strlen($chunk);
            }
        } finally {
            fclose($handle);
        }
    }
}
