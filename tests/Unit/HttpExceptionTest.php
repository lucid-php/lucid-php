<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\BadRequestException;
use Core\Http\ConflictException;
use Core\Http\ForbiddenException;
use Core\Http\HttpException;
use Core\Http\NotFoundException;
use Core\Http\UnauthorizedException;
use PHPUnit\Framework\TestCase;

class HttpExceptionTest extends TestCase
{
    public function test_not_found_exception_has_404_status(): void
    {
        $exception = new NotFoundException('User not found');

        $this->assertInstanceOf(HttpException::class, $exception);
        $this->assertEquals(404, $exception->statusCode);
        $this->assertEquals('User not found', $exception->getMessage());
    }

    public function test_not_found_exception_has_default_message(): void
    {
        $exception = new NotFoundException();

        $this->assertEquals('Resource not found', $exception->getMessage());
        $this->assertEquals(404, $exception->statusCode);
    }

    public function test_unauthorized_exception_has_401_status(): void
    {
        $exception = new UnauthorizedException('Invalid credentials');

        $this->assertEquals(401, $exception->statusCode);
        $this->assertEquals('Invalid credentials', $exception->getMessage());
    }

    public function test_unauthorized_exception_has_default_message(): void
    {
        $exception = new UnauthorizedException();

        $this->assertEquals('Unauthorized', $exception->getMessage());
    }

    public function test_forbidden_exception_has_403_status(): void
    {
        $exception = new ForbiddenException('Access denied');

        $this->assertEquals(403, $exception->statusCode);
        $this->assertEquals('Access denied', $exception->getMessage());
    }

    public function test_bad_request_exception_has_400_status(): void
    {
        $exception = new BadRequestException('Invalid input');

        $this->assertEquals(400, $exception->statusCode);
        $this->assertEquals('Invalid input', $exception->getMessage());
    }

    public function test_conflict_exception_has_409_status(): void
    {
        $exception = new ConflictException('Email already exists');

        $this->assertEquals(409, $exception->statusCode);
        $this->assertEquals('Email already exists', $exception->getMessage());
    }

    public function test_http_exception_can_have_custom_headers(): void
    {
        $exception = new class('Test', 500, ['X-Custom' => 'value']) extends HttpException {};

        $this->assertEquals(['X-Custom' => 'value'], $exception->headers);
    }
}
