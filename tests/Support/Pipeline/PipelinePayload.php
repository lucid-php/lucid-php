<?php

declare(strict_types=1);

namespace Tests\Support\Pipeline;

final class PipelinePayload
{
    /**
     * @param list<string> $trace
     */
    public function __construct(
        public array $trace = [],
    ) {
    }
}
