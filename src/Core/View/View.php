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
        extract($data, EXTR_SKIP);
        
        // Make escape function available as closure
        $e = fn(?string $value): string => htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
        
        // Capture output
        ob_start();
        
        try {
            require $templatePath;
            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new RuntimeException("Error rendering template {$template}: {$e->getMessage()}", 0, $e);
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
     */
    private function resolveTemplatePath(string $template): string
    {
        $template = str_replace('.', '/', $template);
        
        if (!str_ends_with($template, '.php')) {
            $template .= '.php';
        }
        
        return $this->viewsPath . '/' . $template;
    }
}
