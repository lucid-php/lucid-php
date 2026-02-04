<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\View\View;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ViewTest extends TestCase
{
    private string $tempDir;
    private View $view;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/view_test_' . uniqid();
        mkdir($this->tempDir);
        $this->view = new View($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Clean up temp files recursively
        $this->removeDirectory($this->tempDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    #[Test]
    public function it_renders_simple_template(): void
    {
        file_put_contents($this->tempDir . '/test.php', '<h1>Hello</h1>');
        
        $result = $this->view->render('test');
        
        $this->assertSame('<h1>Hello</h1>', $result);
    }

    #[Test]
    public function it_passes_data_to_template(): void
    {
        file_put_contents($this->tempDir . '/greeting.php', '<h1>Hello <?= $name ?></h1>');
        
        $result = $this->view->render('greeting', ['name' => 'World']);
        
        $this->assertSame('<h1>Hello World</h1>', $result);
    }

    #[Test]
    public function it_escapes_html_with_e_function(): void
    {
        file_put_contents($this->tempDir . '/escape.php', '<?= $e($content) ?>');
        
        $result = $this->view->render('escape', ['content' => '<script>alert("xss")</script>']);
        
        $this->assertStringContainsString('&lt;script&gt;', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    #[Test]
    public function it_adds_php_extension_automatically(): void
    {
        file_put_contents($this->tempDir . '/auto.php', 'content');
        
        $result = $this->view->render('auto');
        
        $this->assertSame('content', $result);
    }

    #[Test]
    public function it_converts_dots_to_slashes(): void
    {
        mkdir($this->tempDir . '/users');
        file_put_contents($this->tempDir . '/users/profile.php', 'profile');
        
        $result = $this->view->render('users.profile');
        
        $this->assertSame('profile', $result);
    }

    #[Test]
    public function it_throws_exception_for_missing_template(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('View template not found');
        
        $this->view->render('nonexistent');
    }

    #[Test]
    public function it_handles_template_errors(): void
    {
        file_put_contents($this->tempDir . '/error.php', '<?php throw new Exception("Template error"); ?>');
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error rendering template');
        
        $this->view->render('error');
    }
}
