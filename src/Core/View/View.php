<?php

declare(strict_types=1);

namespace Core\View;

use RuntimeException;

/**
 * Simple PHP Template Renderer
 * 
 * Philosophy: Explicit, no magic
 * - Explicit template paths (no auto-discovery)
 * - Variables explicitly passed and extracted
 * - Plain PHP templates (no custom syntax)
 * - XSS protection via explicit escape() function
 */
class View
{
    public function __construct(
        private readonly string $viewsPath
    ) {}

    /**
     * Render a template file with data
     * 
     * @param string $template Relative path from views directory (e.g., 'users/profile')
     * @param array<string, mixed> $data Variables to extract into template scope
     * @return string Rendered HTML
     * @throws RuntimeException if template not found
     */
    public function render(string $template, array $data = []): string
    {
        $templatePath = $this->resolveTemplatePath($template);
        
        if (!file_exists($templatePath)) {
            throw new RuntimeException("View template not found: {$template}");
        }

        // Extract variables into local scope
        // Note: Using EXTR_SKIP to prevent overwriting existing variables like $templatePath
        extract($data, EXTR_SKIP);
        
        // Make escape function available as closure
        $escape = fn(?string $value): string => htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
        
        // Capture output
        ob_start();
        
        try {
            require $templatePath;
            return ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw new RuntimeException("Error rendering template {$template}: {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * Escape HTML for XSS protection
     * Static method that can be used in templates
     */
    public static function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Resolve template path
     * Adds .php extension if not present
     * Validates against path traversal attacks
     */
    private function resolveTemplatePath(string $template): string
    {
        $template = str_replace('.', '/', $template);
        
        if (!str_ends_with($template, '.php')) {
            $template .= '.php';
        }
        
        $templatePath = $this->viewsPath . '/' . $template;
        
        // Normalize path and validate it's within views directory
        // Use string operations for validation before file existence check
        $normalizedTemplate = str_replace(['\\', '../', './'], ['/', '', ''], $template);
        
        // Check for path traversal attempts
        if (str_contains($template, '..') || str_starts_with($template, '/')) {
            throw new RuntimeException("Template path traversal detected: {$template}");
        }
        
        return $templatePath;
    }
}
