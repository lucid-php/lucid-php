<?php

declare(strict_types=1);

namespace Tests\Support\Pipeline;

use Core\Pipeline\PipelineNext;
use Core\Pipeline\PipelineStepInterface;

final readonly class RecordingPipelineStep implements PipelineStepInterface
{
    public function __construct(
        private string $name = self::class,
    ) {
    }

    public function handle(object $payload, PipelineNext $next): object
    {
        if ($payload instanceof PipelinePayload) {
            $payload->trace[] = $this->name;
        }

        return $next($payload);
    }
}
