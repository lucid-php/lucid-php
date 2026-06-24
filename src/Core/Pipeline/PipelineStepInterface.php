<?php

declare(strict_types=1);

namespace Core\Pipeline;

interface PipelineStepInterface
{
    public function handle(object $payload, PipelineNext $next): object;
}
