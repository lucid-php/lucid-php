<?php

declare(strict_types=1);

namespace Core\GraphQL;

use Core\Attribute\Route;
use Core\Http\Request;
use Core\Http\Response;

final readonly class GraphQLController
{
    public function __construct(
        private GraphQLRequestFactory $requestFactory,
        private GraphQLContextFactory $contextFactory,
        private GraphQLExecutor $executor,
    ) {
    }

    #[Route('POST', '/graphql')]
    public function execute(Request $request): Response
    {
        try {
            $graphQLRequest = $this->requestFactory->fromHttpRequest($request);
        } catch (GraphQLRequestException $exception) {
            return Response::json([
                'errors' => [
                    ['message' => $exception->getMessage()],
                ],
            ], 400);
        }

        $context = $this->contextFactory->fromHttpRequest($request);

        return Response::json(
            $this->executor->execute($graphQLRequest, $context)
        );
    }
}
