<?php

declare(strict_types=1);

namespace Tests\Support\Pipeline;

use Core\Pipeline\PipelineNext;
use Core\Pipeline\PipelineStepInterface;

final class StepThree implements PipelineStepInterface
{
    public function handle(object $payload, PipelineNext $next): object
    {
        if ($payload instanceof PipelinePayload) {
            $payload->trace[] = 'step.three';
        }

        return $next($payload);
    }
}
