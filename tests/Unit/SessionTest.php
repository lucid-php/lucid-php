<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Session\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    private Session $session;

    protected function setUp(): void
    {
        // Use custom session name to avoid conflicts
        $this->session = new Session([
            'name' => 'test_session_' . uniqid(),
            'use_cookies' => false, // Don't use cookies in tests
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->session->isStarted()) {
            $this->session->destroy();
        }
    }

    public function testStartSession(): void
    {
        $this->assertFalse($this->session->isStarted());
        
        $this->session->start();
        
        $this->assertTrue($this->session->isStarted());
    }

    public function testStartSessionMultipleTimes(): void
    {
        $this->session->start();
        $id1 = $this->session->getId();
        
        // Starting again should not change ID
        $this->session->start();
        $id2 = $this->session->getId();
        
        $this->assertSame($id1, $id2);
    }

    public function testSetAndGet(): void
    {
        $this->session->start();
        
        $this->session->set('name', 'Magnus');
        $this->session->set('age', 30);
        
        $this->assertSame('Magnus', $this->session->get('name'));
        $this->assertSame(30, $this->session->get('age'));
    }

    public function testGetWithDefault(): void
    {
        $this->session->start();
        
        $this->assertSame('default', $this->session->get('missing', 'default'));
        $this->assertNull($this->session->get('missing'));
    }

    public function testHas(): void
    {
        $this->session->start();
        
        $this->assertFalse($this->session->has('name'));
        
        $this->session->set('name', 'Magnus');
        
        $this->assertTrue($this->session->has('name'));
    }

    public function testRemove(): void
    {
        $this->session->start();
        
        $this->session->set('name', 'Magnus');
        $this->assertTrue($this->session->has('name'));
        
        $this->session->remove('name');
        
        $this->assertFalse($this->session->has('name'));
    }

    public function testAll(): void
    {
        $this->session->start();
        
        $this->session->set('name', 'Magnus');
        $this->session->set('age', 30);
        
        $all = $this->session->all();
        
        $this->assertIsArray($all);
        $this->assertArrayHasKey('name', $all);
        $this->assertArrayHasKey('age', $all);
        $this->assertSame('Magnus', $all['name']);
        $this->assertSame(30, $all['age']);
    }

    public function testClear(): void
    {
        $this->session->start();
        
        $this->session->set('name', 'Magnus');
        $this->session->set('age', 30);
        
        $this->session->clear();
        
        $this->assertEmpty($this->session->all());
        $this->assertFalse($this->session->has('name'));
        $this->assertFalse($this->session->has('age'));
    }

    public function testDestroy(): void
    {
        $this->session->start();
        $this->session->set('name', 'Magnus');
        
        $this->session->destroy();
        
        $this->assertFalse($this->session->isStarted());
    }

    public function testRegenerate(): void
    {
        $this->session->start();
        $oldId = $this->session->getId();
        
        $this->session->set('name', 'Magnus');
        
        $this->session->regenerate();
        
        $newId = $this->session->getId();
        
        $this->assertNotSame($oldId, $newId);
        // Data should persist after regeneration
        $this->assertSame('Magnus', $this->session->get('name'));
    }

    public function testThrowsExceptionIfNotStarted(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Session not started');
        
        $this->session->get('name');
    }

    public function testSetThrowsExceptionIfNotStarted(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Session not started');
        
        $this->session->set('name', 'Magnus');
    }
}
