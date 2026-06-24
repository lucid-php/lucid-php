<?php

declare(strict_types=1);

namespace Core\GraphQL;

use Core\Http\Request;

final class GraphQLRequestFactory
{
    public function fromHttpRequest(Request $request): GraphQLRequest
    {
        if ($request->jsonParseError) {
            throw new GraphQLRequestException('Invalid JSON body.');
        }

        $body = $request->body;
        if (array_is_list($body)) {
            throw new GraphQLRequestException('GraphQL request body must be a JSON object.');
        }

        /** @var mixed $query */
        $query = $body['query'] ?? null;
        if (!is_string($query) || trim($query) === '') {
            throw new GraphQLRequestException('GraphQL "query" is required and must be a non-empty string.');
        }

        /** @var mixed $variables */
        $variables = $body['variables'] ?? null;
        if ($variables !== null) {
            if (!is_array($variables) || array_is_list($variables)) {
                throw new GraphQLRequestException('GraphQL "variables" must be an object or null.');
            }
        }

        /** @var mixed $operationName */
        $operationName = $body['operationName'] ?? null;
        if ($operationName !== null && !is_string($operationName)) {
            throw new GraphQLRequestException('GraphQL "operationName" must be a string or null.');
        }

        return new GraphQLRequest(
            query: $query,
            variables: $variables,
            operationName: $operationName,
        );
    }
}
