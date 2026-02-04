<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Security\CsrfTokenManager;
use Core\Session\Session;
use PHPUnit\Framework\TestCase;

class CsrfTokenManagerTest extends TestCase
{
    private Session $session;
    private CsrfTokenManager $csrfManager;

    protected function setUp(): void
    {
        $this->session = new Session([
            'name' => 'test_csrf_' . uniqid(),
            'use_cookies' => false,
        ]);
        $this->session->start();
        
        $this->csrfManager = new CsrfTokenManager($this->session);
    }

    protected function tearDown(): void
    {
        if ($this->session->isStarted()) {
            $this->session->destroy();
        }
    }

    public function testGenerateToken(): void
    {
        $token = $this->csrfManager->generateToken();
        
        $this->assertIsString($token);
        $this->assertSame(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function testGetTokenReturnsExistingToken(): void
    {
        $token1 = $this->csrfManager->generateToken();
        $token2 = $this->csrfManager->getToken();
        
        $this->assertSame($token1, $token2);
    }

    public function testGetTokenGeneratesIfNotExists(): void
    {
        $token = $this->csrfManager->getToken();
        
        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));
    }

    public function testValidateTokenWithValidToken(): void
    {
        $token = $this->csrfManager->generateToken();
        
        $isValid = $this->csrfManager->validateToken($token);
        
        $this->assertTrue($isValid);
    }

    public function testValidateTokenWithInvalidToken(): void
    {
        $this->csrfManager->generateToken();
        
        $isValid = $this->csrfManager->validateToken('invalid-token');
        
        $this->assertFalse($isValid);
    }

    public function testValidateTokenWithNoSessionToken(): void
    {
        $isValid = $this->csrfManager->validateToken('any-token');
        
        $this->assertFalse($isValid);
    }

    public function testRegenerateToken(): void
    {
        $token1 = $this->csrfManager->generateToken();
        $token2 = $this->csrfManager->regenerateToken();
        
        $this->assertNotSame($token1, $token2);
        $this->assertSame(64, strlen($token2));
        
        // Old token should be invalid
        $this->assertFalse($this->csrfManager->validateToken($token1));
        
        // New token should be valid
        $this->assertTrue($this->csrfManager->validateToken($token2));
    }

    public function testClearToken(): void
    {
        $this->csrfManager->generateToken();
        
        $this->csrfManager->clearToken();
        
        // Validation should fail after clearing
        $this->assertFalse($this->csrfManager->validateToken('any-token'));
    }

    public function testTimingSafeComparison(): void
    {
        $token = $this->csrfManager->generateToken();
        
        // Test that similar but wrong tokens fail
        $wrongToken = substr($token, 0, -1) . 'x';
        
        $this->assertFalse($this->csrfManager->validateToken($wrongToken));
    }

    public function testMultipleTokensAreUnique(): void
    {
        $token1 = $this->csrfManager->generateToken();
        $this->csrfManager->clearToken();
        $token2 = $this->csrfManager->generateToken();
        
        $this->assertNotSame($token1, $token2);
    }
}
