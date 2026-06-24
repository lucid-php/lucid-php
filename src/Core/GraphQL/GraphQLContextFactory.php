<?php

declare(strict_types=1);

namespace Core\GraphQL;

use Core\Http\Request;

final class GraphQLContextFactory
{
    public function fromHttpRequest(Request $request): GraphQLContext
    {
        /** @var mixed $user */
        $user = $request->getAttribute('user');
        $typedUser = is_object($user) ? $user : null;

        /** @var mixed $requestId */
        $requestId = $request->server['HTTP_X_REQUEST_ID'] ?? null;
        $typedRequestId = is_string($requestId) ? $requestId : null;

        return new GraphQLContext(
            requestId: $typedRequestId,
            user: $typedUser,
            attributes: $request->attributes,
        );
    }
}
