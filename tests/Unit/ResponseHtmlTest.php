<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ResponseHtmlTest extends TestCase
{
    #[Test]
    public function it_creates_html_response(): void
    {
        $response = Response::html('<h1>Hello</h1>');
        
        $this->assertSame('<h1>Hello</h1>', $response->content);
        $this->assertSame(200, $response->status);
        $this->assertSame('text/html; charset=UTF-8', $response->headers['Content-Type']);
    }

    #[Test]
    public function it_creates_html_response_with_custom_status(): void
    {
        $response = Response::html('<h1>Not Found</h1>', 404);
        
        $this->assertSame(404, $response->status);
    }

    #[Test]
    public function it_creates_view_response(): void
    {
        // Create a temporary view file
        $viewsPath = dirname(__DIR__, 2) . '/src/App/Views';
        $testViewPath = $viewsPath . '/test_temp.php';
        
        if (!is_dir($viewsPath)) {
            mkdir($viewsPath, 0755, true);
        }
        
        file_put_contents($testViewPath, '<h1><?= $e($title) ?></h1>');
        
        try {
            $response = Response::view('test_temp', ['title' => 'Test']);
            
            $this->assertStringContainsString('<h1>Test</h1>', $response->content);
            $this->assertSame(200, $response->status);
            $this->assertSame('text/html; charset=UTF-8', $response->headers['Content-Type']);
        } finally {
            if (file_exists($testViewPath)) {
                unlink($testViewPath);
            }
        }
    }
}
