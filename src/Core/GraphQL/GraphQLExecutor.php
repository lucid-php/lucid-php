<?php

declare(strict_types=1);

namespace Core\GraphQL;

use GraphQL\GraphQL;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\DisableIntrospection;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;

final readonly class GraphQLExecutor
{
    public function __construct(
        private GraphQLSchemaFactory $schemaFactory,
        private GraphQLResponseFormatter $formatter,
        private GraphQLConfig $config,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(GraphQLRequest $request, GraphQLContext $context): array
    {
        $validationRules = DocumentValidator::allRules();

        if (!$this->config->introspectionEnabled) {
            $validationRules[] = new DisableIntrospection(DisableIntrospection::ENABLED);
        }

        if ($this->config->maxDepth !== null) {
            $validationRules[] = new QueryDepth($this->config->maxDepth);
        }

        if ($this->config->maxComplexity !== null) {
            $validationRules[] = new QueryComplexity($this->config->maxComplexity);
        }

        $result = GraphQL::executeQuery(
            schema: $this->schemaFactory->create(),
            source: $request->query,
            rootValue: null,
            contextValue: $context,
            variableValues: $request->variables,
            operationName: $request->operationName,
            validationRules: $validationRules,
        );

        return $this->formatter->format($result);
    }
}
