<?php

declare(strict_types=1);

namespace Core\Http;

/**
 * MIME Type Detection Helper
 * 
 * Explicit MIME type detection - no magic guessing.
 * Uses finfo extension for reliable detection based on file content.
 * 
 * Philosophy:
 * - Explicit detection (must call explicitly, no automatic detection)
 * - Content-based (not extension-based)
 * - Falls back to octet-stream (safe default)
 * - No hidden behavior or caching
 */
class MimeTypeDetector
{
    /**
     * Common MIME types by extension (for reference/fallback).
     * Not used for detection, only for documentation.
     */
    private const COMMON_TYPES = [
        'txt' => 'text/plain',
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'pdf' => 'application/pdf',
        'zip' => 'application/zip',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'ogg' => 'audio/ogg',
    ];

    /**
     * Detect MIME type from file contents.
     * 
     * Uses fileinfo extension (finfo) to detect MIME type
     * by analyzing actual file contents, not extension.
     * 
     * @param string $path Absolute path to file
     * @return string MIME type (defaults to application/octet-stream if detection fails)
     */
    public static function detect(string $path): string
    {
        if (!file_exists($path)) {
            return 'application/octet-stream';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return 'application/octet-stream';
        }

        $mimeType = finfo_file($finfo, $path);

        return $mimeType !== false ? $mimeType : 'application/octet-stream';
    }

    /**
     * Get MIME type by file extension (less reliable).
     * 
     * Extension-based lookup. Not recommended for security-critical
     * operations - use detect() for content-based detection.
     * 
     * @param string $extension File extension (without dot)
     * @return string MIME type or application/octet-stream if unknown
     */
    public static function fromExtension(string $extension): string
    {
        $extension = strtolower($extension);
        return self::COMMON_TYPES[$extension] ?? 'application/octet-stream';
    }

    /**
     * Detect MIME type with explicit extension fallback.
     * 
     * Tries content-based detection first, falls back to extension.
     * Explicit two-step process - no hidden fallback.
     * 
     * @param string $path Absolute path to file
     * @param string $extension File extension for fallback
     * @return string MIME type
     */
    public static function detectWithFallback(string $path, string $extension): string
    {
        $detected = self::detect($path);
        
        // If detection returned generic type, try extension
        if ($detected === 'application/octet-stream') {
            return self::fromExtension($extension);
        }

        return $detected;
    }

    /**
     * Check if MIME type is safe for inline display.
     * 
     * Explicit whitelist of MIME types safe to display inline.
     * Used to prevent XSS attacks from user-uploaded content.
     * 
     * @param string $mimeType MIME type to check
     * @return bool True if safe for inline display
     */
    public static function isSafeForInline(string $mimeType): bool
    {
        $safeMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/pdf',
            'video/mp4',
            'video/webm',
            'audio/mpeg',
            'audio/ogg',
            'text/plain',
        ];

        return in_array($mimeType, $safeMimeTypes, strict: true);
    }
}
