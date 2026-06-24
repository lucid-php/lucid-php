<?php

declare(strict_types=1);

namespace Core\Pipeline;

final readonly class PipelineDefinition
{
    /**
     * @param list<string> $steps
     */
    public function __construct(
        public string $name,
        public array $steps,
    ) {
    }
}
