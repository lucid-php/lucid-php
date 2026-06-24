<?php

declare(strict_types=1);

namespace Tests\Support\Pipeline;

use Core\Pipeline\PipelineNext;
use Core\Pipeline\PipelineStepInterface;

final class StepTwo implements PipelineStepInterface
{
    public function handle(object $payload, PipelineNext $next): object
    {
        if ($payload instanceof PipelinePayload) {
            $payload->trace[] = 'step.two';
        }

        return $next($payload);
    }
}
