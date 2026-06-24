<?php

declare(strict_types=1);

namespace Core\Pipeline;

final class PipelineRegistry
{
    /**
     * @var array<string, Pipeline>
     */
    private array $pipelines = [];

    public function define(PipelineDefinition $definition): void
    {
        $this->pipelines[$definition->name] = new Pipeline(
            name: $definition->name,
            steps: $definition->steps
        );
    }

    public function get(string $name): Pipeline
    {
        if (!isset($this->pipelines[$name])) {
            throw new PipelineNotFoundException(
                sprintf('Pipeline "%s" not found', $name)
            );
        }

        return $this->pipelines[$name];
    }

    public function addBefore(
        string $pipeline,
        string $beforeStep,
        string $step,
    ): void {
        if (!isset($this->pipelines[$pipeline])) {
            throw new PipelineNotFoundException(
                sprintf('Pipeline "%s" not found', $pipeline)
            );
        }

        $this->pipelines[$pipeline]->addBefore($beforeStep, $step);
    }

    public function addAfter(
        string $pipeline,
        string $afterStep,
        string $step,
    ): void {
        if (!isset($this->pipelines[$pipeline])) {
            throw new PipelineNotFoundException(
                sprintf('Pipeline "%s" not found', $pipeline)
            );
        }

        $this->pipelines[$pipeline]->addAfter($afterStep, $step);
    }
}
