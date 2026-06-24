<?php

declare(strict_types=1);

namespace Core\Pipeline;

final class Pipeline
{
    public function __construct(
        private readonly string $name,
        array $steps,
    ) {
        $this->steps = $steps;
    }

    /**
     * @var list<string>
     */
    private array $steps;

    public function addBefore(string $beforeStep, string $step): void
    {
        $key = array_search($beforeStep, $this->steps, true);

        if ($key === false) {
            throw new PipelineStepNotFoundException(
                sprintf(
                    'Pipeline "%s" step "%s" not found for addBefore().',
                    $this->name,
                    $beforeStep
                )
            );
        }

        array_splice($this->steps, $key, 0, [$step]);
    }

    public function addAfter(string $afterStep, string $step): void
    {
        $key = array_search($afterStep, $this->steps, true);

        if ($key === false) {
            throw new PipelineStepNotFoundException(
                sprintf(
                    'Pipeline "%s" step "%s" not found for addAfter().',
                    $this->name,
                    $afterStep
                )
            );
        }

        array_splice($this->steps, $key + 1, 0, [$step]);
    }

    public function run(object $payload): object
    {
        $stepInstances = array_map(
            static fn (string $stepClass) => new $stepClass(),
            $this->steps
        );

        $next = new PipelineNext($stepInstances);
        return $next($payload);
    }
}
