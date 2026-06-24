<?php

declare(strict_types=1);

namespace Core\GraphQL;

use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;

final readonly class GraphQLResponseFormatter
{
    public function __construct(
        private GraphQLConfig $config,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function format(ExecutionResult $result): array
    {
        $response = [];

        if ($result->data !== null) {
            $response['data'] = $result->data;
        }

        if ($result->errors !== []) {
            $response['errors'] = array_map(
                fn (Error $error): array => $this->formatError($error),
                $result->errors
            );
        }

        if ($response === []) {
            $response['data'] = null;
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatError(Error $error): array
    {
        $message = (!$this->config->debug && !$error->isClientSafe())
            ? 'Internal server error.'
            : $error->getMessage();

        $formatted = ['message' => $message];

        $locations = $error->getLocations();
        if ($locations !== []) {
            $formatted['locations'] = array_map(
                static fn (object $location): array => [
                    'line' => $location->line,
                    'column' => $location->column,
                ],
                $locations
            );
        }

        $path = $error->getPath();
        if (is_array($path) && $path !== []) {
            $formatted['path'] = $path;
        }

        $extensions = $error->getExtensions();
        if (is_array($extensions) && $extensions !== []) {
            $formatted['extensions'] = $extensions;
        }

        if ($this->config->debug && $error->getPrevious() !== null) {
            $formatted['debug'] = [
                'exception' => $error->getPrevious()::class,
                'message' => $error->getPrevious()->getMessage(),
            ];
        }

        return $formatted;
    }
}
