<?php

declare(strict_types=1);

namespace Core\Pipeline;

final class PipelineNext
{
    /**
     * @var list<PipelineStepInterface>
     */
    private array $remainingSteps;

    /**
     * @param list<PipelineStepInterface> $remainingSteps
     */
    public function __construct(array $remainingSteps)
    {
        $this->remainingSteps = $remainingSteps;
    }

    public function __invoke(object $payload): object
    {
        if (empty($this->remainingSteps)) {
            return $payload;
        }

        $step = array_shift($this->remainingSteps);
        $next = new self($this->remainingSteps);

        return $step->handle($payload, $next);
    }
}
