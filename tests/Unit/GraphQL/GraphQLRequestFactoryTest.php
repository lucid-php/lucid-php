<?php

declare(strict_types=1);

namespace Tests\Unit\GraphQL;

use Core\GraphQL\GraphQLRequestException;
use Core\GraphQL\GraphQLRequestFactory;
use Core\Http\Request;
use PHPUnit\Framework\TestCase;

final class GraphQLRequestFactoryTest extends TestCase
{
    private GraphQLRequestFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new GraphQLRequestFactory();
    }

    public function testRejectsInvalidJson(): void
    {
        $request = new Request('POST', '/graphql', body: [], jsonParseError: true);

        $this->expectException(GraphQLRequestException::class);
        $this->factory->fromHttpRequest($request);
    }

    public function testRejectsJsonArrayBody(): void
    {
        $request = new Request('POST', '/graphql', body: ['a']);

        $this->expectException(GraphQLRequestException::class);
        $this->factory->fromHttpRequest($request);
    }

    public function testRejectsMissingQuery(): void
    {
        $request = new Request('POST', '/graphql', body: []);

        $this->expectException(GraphQLRequestException::class);
        $this->factory->fromHttpRequest($request);
    }

    public function testRejectsEmptyQuery(): void
    {
        $request = new Request('POST', '/graphql', body: ['query' => ' ']);

        $this->expectException(GraphQLRequestException::class);
        $this->factory->fromHttpRequest($request);
    }

    public function testRejectsNonStringQuery(): void
    {
        $request = new Request('POST', '/graphql', body: ['query' => 123]);

        $this->expectException(GraphQLRequestException::class);
        $this->factory->fromHttpRequest($request);
    }

    public function testAcceptsVariablesAndOperationName(): void
    {
        $request = new Request('POST', '/graphql', body: [
            'query' => 'query Echo($message: String!) { health }',
            'variables' => ['message' => 'hello'],
            'operationName' => 'Echo',
        ]);

        $graphQLRequest = $this->factory->fromHttpRequest($request);

        self::assertSame(['message' => 'hello'], $graphQLRequest->variables);
        self::assertSame('Echo', $graphQLRequest->operationName);
    }

    public function testRejectsInvalidVariables(): void
    {
        $request = new Request('POST', '/graphql', body: [
            'query' => 'query { health }',
            'variables' => ['x'],
        ]);

        $this->expectException(GraphQLRequestException::class);
        $this->factory->fromHttpRequest($request);
    }

    public function testRejectsNonStringOperationName(): void
    {
        $request = new Request('POST', '/graphql', body: [
            'query' => 'query Test { health }',
            'operationName' => 100,
        ]);

        $this->expectException(GraphQLRequestException::class);
        $this->factory->fromHttpRequest($request);
    }
}
