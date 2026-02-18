<?php

declare(strict_types=1);

namespace Core\Http;

/**
 * HTTP Response
 * 
 * Factory methods are marked with #[\NoDiscard] to ensure
 * the returned Response is used (typically returned or sent).
 * This prevents accidental non-use of critical return values.
 */
class Response
{
    public function __construct(
        public private(set) string $content = '',
        public private(set) int $status = 200,
        public private(set) array $headers = []
    ) {
    }

    #[\NoDiscard]
    public static function json(array $data, int $status = 200): self
    {
        return new self(
            content: json_encode($data, JSON_THROW_ON_ERROR),
            status: $status,
            headers: ['Content-Type' => 'application/json']
        );
    }

    #[\NoDiscard]
    public static function text(string $text, int $status = 200): self
    {
        return new self(
            content: $text,
            status: $status,
            headers: ['Content-Type' => 'text/plain']
        );
    }

    /**
     * Create an HTML response
     * Explicit HTML content - no template rendering
     */
    #[\NoDiscard]
    public static function html(string $html, int $status = 200): self
    {
        return new self(
            content: $html,
            status: $status,
            headers: ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    /**
     * Create an HTML response from a view template
     * Explicit view rendering with explicit data passing
     * 
     * @param string $template Template path relative to views directory
     * @param array<string, mixed> $data Variables to pass to template
     * @param int $status HTTP status code
     */
    #[\NoDiscard]
    public static function view(string $template, array $data = [], int $status = 200): self
    {
        $viewsPath = dirname(__DIR__, 3) . '/src/App/Views';
        $view = new \Core\View\View($viewsPath);
        $html = $view->render($template, $data);
        
        return self::html($html, $status);
    }

    /**
     * Create a redirect response
     * PHP 8.5: #[\NoDiscard] ensures redirect is properly returned/sent
     */
    #[\NoDiscard]
    public static function redirect(string $url, int $status = 302): self
    {
        return new self(
            content: '',
            status: $status,
            headers: ['Location' => $url]
        );
    }

    /**
     * Create a new Response with an additional header
     * Follows immutability pattern - returns new instance
     * PHP 8.5: #[\NoDiscard] ensures the new instance is used
     */
    #[\NoDiscard]
    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[$name] = $value;
        
        return new self(
            content: $this->content,
            status: $this->status,
            headers: $headers
        );
    }

    /**
     * Create a new Response with multiple additional headers
     * Follows immutability pattern - returns new instance
     * PHP 8.5: #[\NoDiscard] ensures the new instance is used
     */
    #[\NoDiscard]
    public function withHeaders(array $headers): self
    {
        return new self(
            content: $this->content,
            status: $this->status,
            headers: array_merge($this->headers, $headers)
        );
    }

    /**
     * Create a file download response.
     * 
     * Explicit file serving - no magic MIME detection.
     * Caller must provide MIME type or use MimeTypeDetector.
     * 
     * @param string $path Absolute path to file (validated by caller)
     * @param string $filename Download filename (defaults to basename)
     * @param string $mimeType MIME type (explicit, no auto-detection)
     * @param bool $inline Whether to display inline (true) or force download (false)
     * @throws \RuntimeException if file doesn't exist or can't be read
     */
    #[\NoDiscard]
    public static function file(
        string $path,
        ?string $filename = null,
        string $mimeType = 'application/octet-stream',
        bool $inline = false
    ): self {
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: $path");
        }

        if (!is_readable($path)) {
            throw new \RuntimeException("File not readable: $path");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: $path");
        }

        $filename = $filename ?? basename($path);
        $disposition = $inline ? 'inline' : 'attachment';

        // Encode filename to prevent header injection and properly handle special characters
        // Use RFC 5987 encoding for international characters
        $encodedFilename = rawurlencode($filename);
        
        // Sanitize filename for ASCII compatibility - remove control characters and quotes
        // Keep printable ASCII (0x20-0x7E) including spaces for better user experience
        // Spaces are safe within quoted filename parameter per RFC 2616
        $safeFilename = preg_replace('/[^\x20-\x7E]/', '', $filename);
        $safeFilename = str_replace(['"', '\\', "\r", "\n"], '', $safeFilename); // Remove problematic chars
        
        return new self(
            content: $content,
            status: 200,
            headers: [
                'Content-Type' => $mimeType,
                'Content-Disposition' => "$disposition; filename=\"$safeFilename\"; filename*=UTF-8''" . $encodedFilename,
                'Content-Length' => (string) strlen($content),
            ]
        );
    }

    /**
     * Create a file download response (convenience for attachment).
     * 
     * @param string $path Absolute path to file
     * @param string|null $filename Download filename
     * @param string $mimeType MIME type
     */
    #[\NoDiscard]
    public static function download(
        string $path,
        ?string $filename = null,
        string $mimeType = 'application/octet-stream'
    ): self {
        return self::file($path, $filename, $mimeType, inline: false);
    }

    /**
     * Create a streamed response for large files.
     * 
     * Returns StreamedResponse for efficient memory usage.
     * Use for files larger than available PHP memory.
     * 
     * @param string $path Absolute path to file
     * @param string|null $filename Download filename
     * @param string $mimeType MIME type
     * @param bool $inline Whether to display inline or force download
     */
    #[\NoDiscard]
    public static function stream(
        string $path,
        ?string $filename = null,
        string $mimeType = 'application/octet-stream',
        bool $inline = false
    ): StreamedResponse {
        return new StreamedResponse($path, $filename, $mimeType, $inline);
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $key => $value) {
            // Prevent header injection by validating header values
            // Remove any newline characters that could be used for injection
            $sanitizedValue = str_replace(["\r", "\n"], '', $value);
            header("{$key}: {$sanitizedValue}");
        }

        echo $this->content;
    }
}
