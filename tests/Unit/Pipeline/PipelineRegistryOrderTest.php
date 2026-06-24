<?php

declare(strict_types=1);

namespace Tests\Unit\Pipeline;

use Core\Pipeline\PipelineDefinition;
use Core\Pipeline\PipelineRegistry;
use Core\Pipeline\PipelineStepNotFoundException;
use PHPUnit\Framework\TestCase;
use Tests\Support\Pipeline\PipelinePayload;
use Tests\Support\Pipeline\StepOne;
use Tests\Support\Pipeline\StepThree;
use Tests\Support\Pipeline\StepTwo;

final class PipelineRegistryOrderTest extends TestCase
{
    public function testRecordingStepCapturesExecutionOrder(): void
    {
        $registry = new PipelineRegistry();
        $registry->define(new PipelineDefinition(
            name: 'tests.order',
            steps: [StepOne::class, StepThree::class],
        ));

        $registry->addBefore(
            pipeline: 'tests.order',
            beforeStep: StepThree::class,
            step: StepTwo::class,
        );

        $payload = new PipelinePayload();
        $result = $registry->get('tests.order')->run($payload);

        self::assertInstanceOf(PipelinePayload::class, $result);
        self::assertSame(['step.one', 'step.two', 'step.three'], $result->trace);
    }

    public function testAddAfterMissingStepIncludesPipelineName(): void
    {
        $registry = new PipelineRegistry();
        $registry->define(new PipelineDefinition(
            name: 'tests.order',
            steps: [StepOne::class],
        ));

        $this->expectException(PipelineStepNotFoundException::class);
        $this->expectExceptionMessage('Pipeline "tests.order" step "missing.step" not found');

        $registry->addAfter(
            pipeline: 'tests.order',
            afterStep: 'missing.step',
            step: StepTwo::class,
        );
    }
}
