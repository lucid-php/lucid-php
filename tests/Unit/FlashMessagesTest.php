<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Session\FlashMessages;
use Core\Session\Session;
use PHPUnit\Framework\TestCase;

class FlashMessagesTest extends TestCase
{
    private Session $session;
    private FlashMessages $flash;

    protected function setUp(): void
    {
        $this->session = new Session([
            'name' => 'test_flash_' . uniqid(),
            'use_cookies' => false,
        ]);
        $this->session->start();
        
        $this->flash = new FlashMessages($this->session);
    }

    protected function tearDown(): void
    {
        if ($this->session->isStarted()) {
            $this->session->destroy();
        }
    }

    public function testAddSuccessMessage(): void
    {
        $this->flash->success('Operation successful');
        
        $messages = $this->flash->get('success');
        
        $this->assertCount(1, $messages);
        $this->assertSame('Operation successful', $messages[0]);
    }

    public function testAddErrorMessage(): void
    {
        $this->flash->error('Something went wrong');
        
        $messages = $this->flash->get('error');
        
        $this->assertCount(1, $messages);
        $this->assertSame('Something went wrong', $messages[0]);
    }

    public function testAddInfoMessage(): void
    {
        $this->flash->info('Please note this');
        
        $messages = $this->flash->get('info');
        
        $this->assertCount(1, $messages);
        $this->assertSame('Please note this', $messages[0]);
    }

    public function testAddWarningMessage(): void
    {
        $this->flash->warning('Be careful');
        
        $messages = $this->flash->get('warning');
        
        $this->assertCount(1, $messages);
        $this->assertSame('Be careful', $messages[0]);
    }

    public function testMultipleMessagesOfSameType(): void
    {
        $this->flash->success('First');
        $this->flash->success('Second');
        $this->flash->success('Third');
        
        $messages = $this->flash->get('success');
        
        $this->assertCount(3, $messages);
        $this->assertSame(['First', 'Second', 'Third'], $messages);
    }

    public function testMessagesAreRemovedAfterRetrieval(): void
    {
        $this->flash->success('Message');
        
        // First retrieval
        $messages = $this->flash->get('success');
        $this->assertCount(1, $messages);
        
        // Second retrieval should be empty
        $messages = $this->flash->get('success');
        $this->assertEmpty($messages);
    }

    public function testGetAllMessages(): void
    {
        $this->flash->success('Success message');
        $this->flash->error('Error message');
        $this->flash->info('Info message');
        
        $all = $this->flash->getAll();
        
        $this->assertArrayHasKey('success', $all);
        $this->assertArrayHasKey('error', $all);
        $this->assertArrayHasKey('info', $all);
        $this->assertSame('Success message', $all['success'][0]);
        $this->assertSame('Error message', $all['error'][0]);
        $this->assertSame('Info message', $all['info'][0]);
    }

    public function testGetAllRemovesMessages(): void
    {
        $this->flash->success('Message');
        
        // First retrieval
        $all = $this->flash->getAll();
        $this->assertNotEmpty($all);
        
        // Second retrieval should be empty
        $all = $this->flash->getAll();
        $this->assertEmpty($all);
    }

    public function testHasReturnsTrueWhenMessagesExist(): void
    {
        $this->flash->success('Message');
        
        $this->assertTrue($this->flash->has('success'));
        $this->assertFalse($this->flash->has('error'));
    }

    public function testHasAnyReturnsTrueWhenAnyMessagesExist(): void
    {
        $this->assertFalse($this->flash->hasAny());
        
        $this->flash->info('Message');
        
        $this->assertTrue($this->flash->hasAny());
    }

    public function testGetNonExistentTypeReturnsEmptyArray(): void
    {
        $messages = $this->flash->get('nonexistent');
        
        $this->assertIsArray($messages);
        $this->assertEmpty($messages);
    }

    public function testFlashMessagesSurviveSessionRegenerate(): void
    {
        $this->flash->success('Important message');
        
        $this->session->regenerate();
        
        $messages = $this->flash->get('success');
        $this->assertCount(1, $messages);
        $this->assertSame('Important message', $messages[0]);
    }
}
