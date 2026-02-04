<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Http\ApiResponse;
use Core\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase
{
    #[Test]
    public function it_creates_success_response(): void
    {
        $response = ApiResponse::success(
            data: ['id' => 1, 'name' => 'John'],
            message: 'User retrieved'
        );

        $this->assertTrue($response->success);
        $this->assertEquals(['id' => 1, 'name' => 'John'], $response->data);
        $this->assertEquals('User retrieved', $response->message);
        $this->assertNull($response->errors);
        $this->assertNull($response->meta);
        $this->assertEquals(200, $response->statusCode);
    }

    #[Test]
    public function it_creates_success_response_with_metadata(): void
    {
        $meta = ['total' => 100, 'page' => 1];
        $response = ApiResponse::success(
            data: ['items' => []],
            meta: $meta
        );

        $this->assertTrue($response->success);
        $this->assertEquals($meta, $response->meta);
    }

    #[Test]
    public function it_creates_created_response(): void
    {
        $response = ApiResponse::created(
            data: ['id' => 42],
            message: 'Resource created'
        );

        $this->assertTrue($response->success);
        $this->assertEquals(['id' => 42], $response->data);
        $this->assertEquals('Resource created', $response->message);
        $this->assertEquals(201, $response->statusCode);
    }

    #[Test]
    public function it_creates_no_content_response(): void
    {
        $response = ApiResponse::noContent();

        $this->assertTrue($response->success);
        $this->assertNull($response->data);
        $this->assertNull($response->message);
        $this->assertEquals(204, $response->statusCode);
    }

    #[Test]
    public function it_creates_error_response(): void
    {
        $errors = ['field' => 'Invalid value'];
        $response = ApiResponse::error(
            message: 'Operation failed',
            errors: $errors,
            statusCode: 400
        );

        $this->assertFalse($response->success);
        $this->assertEquals('Operation failed', $response->message);
        $this->assertEquals($errors, $response->errors);
        $this->assertEquals(400, $response->statusCode);
    }

    #[Test]
    public function it_creates_validation_error_response(): void
    {
        $errors = [
            'email' => ['Email is required', 'Email must be valid'],
            'password' => ['Password too short']
        ];
        $response = ApiResponse::validationError($errors);

        $this->assertFalse($response->success);
        $this->assertEquals('Validation failed', $response->message);
        $this->assertEquals($errors, $response->errors);
        $this->assertEquals(422, $response->statusCode);
    }

    #[Test]
    public function it_creates_validation_error_with_custom_message(): void
    {
        $response = ApiResponse::validationError(
            errors: ['email' => ['Required']],
            message: 'Custom validation message'
        );

        $this->assertEquals('Custom validation message', $response->message);
    }

    #[Test]
    public function it_creates_not_found_response(): void
    {
        $response = ApiResponse::notFound('User not found');

        $this->assertFalse($response->success);
        $this->assertEquals('User not found', $response->message);
        $this->assertEquals(404, $response->statusCode);
    }

    #[Test]
    public function it_creates_not_found_with_default_message(): void
    {
        $response = ApiResponse::notFound();

        $this->assertEquals('Resource not found', $response->message);
    }

    #[Test]
    public function it_creates_unauthorized_response(): void
    {
        $response = ApiResponse::unauthorized('Login required');

        $this->assertFalse($response->success);
        $this->assertEquals('Login required', $response->message);
        $this->assertEquals(401, $response->statusCode);
    }

    #[Test]
    public function it_creates_unauthorized_with_default_message(): void
    {
        $response = ApiResponse::unauthorized();

        $this->assertEquals('Unauthorized', $response->message);
    }

    #[Test]
    public function it_creates_forbidden_response(): void
    {
        $response = ApiResponse::forbidden('Access denied');

        $this->assertFalse($response->success);
        $this->assertEquals('Access denied', $response->message);
        $this->assertEquals(403, $response->statusCode);
    }

    #[Test]
    public function it_creates_forbidden_with_default_message(): void
    {
        $response = ApiResponse::forbidden();

        $this->assertEquals('Forbidden', $response->message);
    }

    #[Test]
    public function it_creates_paginated_response(): void
    {
        $items = [['id' => 1], ['id' => 2], ['id' => 3]];
        $response = ApiResponse::paginated(
            items: $items,
            total: 100,
            page: 2,
            perPage: 3,
            message: 'Items retrieved'
        );

        $this->assertTrue($response->success);
        $this->assertEquals($items, $response->data);
        $this->assertEquals('Items retrieved', $response->message);
        $this->assertEquals(200, $response->statusCode);
        
        // Check pagination metadata
        $this->assertNotNull($response->meta);
        $this->assertArrayHasKey('pagination', $response->meta);
        
        $pagination = $response->meta['pagination'];
        $this->assertEquals(100, $pagination['total']);
        $this->assertEquals(2, $pagination['page']);
        $this->assertEquals(3, $pagination['per_page']);
        $this->assertEquals(34, $pagination['total_pages']);
        $this->assertTrue($pagination['has_more']);
    }

    #[Test]
    public function it_calculates_pagination_metadata_correctly(): void
    {
        // Last page
        $response = ApiResponse::paginated(
            items: [['id' => 1]],
            total: 10,
            page: 5,
            perPage: 2
        );

        $pagination = $response->meta['pagination'];
        $this->assertEquals(5, $pagination['total_pages']);
        $this->assertFalse($pagination['has_more']);
    }

    #[Test]
    public function it_converts_to_array_with_all_fields(): void
    {
        $response = ApiResponse::success(
            data: ['test' => 'data'],
            message: 'Success message',
            meta: ['key' => 'value']
        );

        $array = $response->toArray();

        $this->assertArrayHasKey('success', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('meta', $array);
        $this->assertTrue($array['success']);
        $this->assertEquals(['test' => 'data'], $array['data']);
        $this->assertEquals('Success message', $array['message']);
        $this->assertEquals(['key' => 'value'], $array['meta']);
    }

    #[Test]
    public function it_converts_to_array_omitting_null_fields(): void
    {
        $response = ApiResponse::success(data: ['test' => 'data']);

        $array = $response->toArray();

        $this->assertArrayHasKey('success', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayNotHasKey('message', $array);
        $this->assertArrayNotHasKey('errors', $array);
        $this->assertArrayNotHasKey('meta', $array);
    }

    #[Test]
    public function it_converts_error_to_array_with_errors_field(): void
    {
        $response = ApiResponse::error(
            message: 'Error occurred',
            errors: ['field' => 'error']
        );

        $array = $response->toArray();

        $this->assertFalse($array['success']);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('errors', $array);
        $this->assertArrayNotHasKey('data', $array);
    }

    #[Test]
    public function it_converts_to_http_response(): void
    {
        $apiResponse = ApiResponse::success(
            data: ['id' => 1],
            message: 'Success'
        );

        $httpResponse = $apiResponse->toResponse();

        $this->assertInstanceOf(Response::class, $httpResponse);
        $this->assertEquals(200, $httpResponse->status);
        $this->assertEquals('application/json', $httpResponse->headers['Content-Type']);
        
        $content = json_decode($httpResponse->content, true);
        $this->assertTrue($content['success']);
        $this->assertEquals(['id' => 1], $content['data']);
        $this->assertEquals('Success', $content['message']);
    }

    #[Test]
    public function it_converts_error_to_http_response_with_correct_status(): void
    {
        $apiResponse = ApiResponse::notFound('User not found');

        $httpResponse = $apiResponse->toResponse();

        $this->assertEquals(404, $httpResponse->status);
        
        $content = json_decode($httpResponse->content, true);
        $this->assertFalse($content['success']);
        $this->assertEquals('User not found', $content['message']);
    }

    #[Test]
    public function it_converts_no_content_to_empty_http_response(): void
    {
        $apiResponse = ApiResponse::noContent();

        $httpResponse = $apiResponse->toResponse();

        $this->assertEquals(204, $httpResponse->status);
        $this->assertEquals('', $httpResponse->content);
    }

    #[Test]
    public function it_is_readonly_class(): void
    {
        $response = ApiResponse::success(data: ['test']);

        $reflection = new \ReflectionClass($response);
        $this->assertTrue($reflection->isReadOnly());
    }

    #[Test]
    public function it_supports_custom_status_codes(): void
    {
        $response = ApiResponse::success(
            data: ['test'],
            statusCode: 202
        );

        $this->assertEquals(202, $response->statusCode);
    }

    #[Test]
    public function it_handles_null_data_in_success_response(): void
    {
        $response = ApiResponse::success();

        $this->assertTrue($response->success);
        $this->assertNull($response->data);
        $this->assertEquals(200, $response->statusCode);
    }

    #[Test]
    public function it_handles_complex_data_structures(): void
    {
        $complexData = [
            'users' => [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane']
            ],
            'nested' => [
                'level1' => [
                    'level2' => ['value' => 123]
                ]
            ]
        ];

        $response = ApiResponse::success(data: $complexData);

        $this->assertEquals($complexData, $response->data);
        
        $array = $response->toArray();
        $this->assertEquals($complexData, $array['data']);
    }
}
