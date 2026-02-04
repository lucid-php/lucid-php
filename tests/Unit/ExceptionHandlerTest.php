<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\BadRequestException;
use Core\Http\ConflictException;
use Core\Http\ExceptionHandler;
use Core\Http\ForbiddenException;
use Core\Http\NotFoundException;
use Core\Http\UnauthorizedException;
use Core\Http\Response;
use Core\Validation\ValidationException;
use Exception;
use PHPUnit\Framework\TestCase;

class ExceptionHandlerTest extends TestCase
{
    public function testHandlesNotFoundException(): void
    {
        $handler = new ExceptionHandler(debug: false);
        $exception = new NotFoundException('User not found');

        $response = $handler->handle($exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(404, $response->status);
        
        $data = json_decode($response->content, true);
        $this->assertSame('Not Found', $data['error']);
        $this->assertSame('User not found', $data['message']);
        $this->assertArrayNotHasKey('trace', $data);
    }

    public function testHandlesUnauthorizedException(): void
    {
        $handler = new ExceptionHandler(debug: false);
        $exception = new UnauthorizedException('Invalid token');

        $response = $handler->handle($exception);

        $this->assertSame(401, $response->status);
        
        $data = json_decode($response->content, true);
        $this->assertSame('Unauthorized', $data['error']);
        $this->assertSame('Invalid token', $data['message']);
    }

    public function testHandlesForbiddenException(): void
    {
        $handler = new ExceptionHandler(debug: false);
        $exception = new ForbiddenException('Access denied');

        $response = $handler->handle($exception);

        $this->assertSame(403, $response->status);
        
        $data = json_decode($response->content, true);
        $this->assertSame('Forbidden', $data['error']);
    }

    public function testHandlesBadRequestException(): void
    {
        $handler = new ExceptionHandler(debug: false);
        $exception = new BadRequestException('Invalid input');

        $response = $handler->handle($exception);

        $this->assertSame(400, $response->status);
        
        $data = json_decode($response->content, true);
        $this->assertSame('Bad Request', $data['error']);
    }

    public function testHandlesConflictException(): void
    {
        $handler = new ExceptionHandler(debug: false);
        $exception = new ConflictException('Email already exists');

        $response = $handler->handle($exception);

        $this->assertSame(409, $response->status);
        
        $data = json_decode($response->content, true);
        $this->assertSame('Conflict', $data['error']);
    }

    public function testHandlesValidationException(): void
    {
        $handler = new ExceptionHandler(debug: false);
        $errors = ['email' => 'Email is required', 'name' => 'Name must be at least 3 characters'];
        $exception = new ValidationException($errors);

        $response = $handler->handle($exception);

        $this->assertSame(422, $response->status);
        
        $data = json_decode($response->content, true);
        $this->assertSame('Validation Failed', $data['error']);
        $this->assertArrayHasKey('details', $data);
        $this->assertSame($errors, $data['details']);
    }

    public function testHandlesGenericException(): void
    {
        $handler = new ExceptionHandler(debug: false);
        $exception = new Exception('Something went wrong');

        $response = $handler->handle($exception);

        $this->assertSame(500, $response->status);
        
        $data = json_decode($response->content, true);
        $this->assertSame('Internal Server Error', $data['error']);
        $this->assertSame('Something went wrong', $data['message']);
    }

    public function testDebugModeIncludesStackTrace(): void
    {
        $handler = new ExceptionHandler(debug: true);
        $exception = new Exception('Debug error');

        $response = $handler->handle($exception);

        $data = json_decode($response->content, true);
        $this->assertArrayHasKey('exception', $data);
        $this->assertArrayHasKey('file', $data);
        $this->assertArrayHasKey('line', $data);
        $this->assertArrayHasKey('trace', $data);
        $this->assertSame(Exception::class, $data['exception']);
        $this->assertIsArray($data['trace']);
    }

    public function testDebugModeOmittedInProduction(): void
    {
        $handler = new ExceptionHandler(debug: false);
        $exception = new Exception('Production error');

        $response = $handler->handle($exception);

        $data = json_decode($response->content, true);
        $this->assertArrayNotHasKey('exception', $data);
        $this->assertArrayNotHasKey('file', $data);
        $this->assertArrayNotHasKey('line', $data);
        $this->assertArrayNotHasKey('trace', $data);
    }

    public function testStackTraceLimitedToTenFrames(): void
    {
        $handler = new ExceptionHandler(debug: true);
        
        // Create exception with deep stack
        $exception = $this->createDeepStackException();

        $response = $handler->handle($exception);

        $data = json_decode($response->content, true);
        $this->assertLessThanOrEqual(10, count($data['trace']));
    }

    private function createDeepStackException(): Exception
    {
        return $this->level1();
    }

    private function level1(): Exception { return $this->level2(); }
    private function level2(): Exception { return $this->level3(); }
    private function level3(): Exception { return $this->level4(); }
    private function level4(): Exception { return $this->level5(); }
    private function level5(): Exception { return $this->level6(); }
    private function level6(): Exception { return $this->level7(); }
    private function level7(): Exception { return $this->level8(); }
    private function level8(): Exception { return $this->level9(); }
    private function level9(): Exception { return $this->level10(); }
    private function level10(): Exception { return new Exception('Deep stack'); }
}
