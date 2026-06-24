<?php

declare(strict_types=1);

namespace Tests\Core\Pipeline;

use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineDefinition;
use Core\Pipeline\PipelineNext;
use Core\Pipeline\PipelineNotFoundException;
use Core\Pipeline\PipelineRegistry;
use Core\Pipeline\PipelineStepInterface;
use Core\Pipeline\PipelineStepNotFoundException;
use PHPUnit\Framework\TestCase;

final class PipelineTest extends TestCase
{
    public function testRunsStepsInOrder(): void
    {
        $definition = new PipelineDefinition('test', [TestStep1::class, TestStep2::class]);
        $pipeline = new Pipeline($definition->steps);

        $result = $pipeline->run(new \stdClass());

        self::assertInstanceOf(\stdClass::class, $result);
    }

    public function testAddBeforeInsertsAtCorrectPosition(): void
    {
        $definition = new PipelineDefinition('test', [TestStep1::class, TestStep3::class]);
        $pipeline = new Pipeline($definition->steps);

        $pipeline->addBefore(TestStep3::class, TestStep2::class);

        $reflection = new \ReflectionClass($pipeline);
        $stepsProperty = $reflection->getProperty('steps');
        $stepsProperty->setAccessible(true);
        $steps = $stepsProperty->getValue($pipeline);

        self::assertSame(TestStep1::class, $steps[0]);
        self::assertSame(TestStep2::class, $steps[1]);
        self::assertSame(TestStep3::class, $steps[2]);
    }

    public function testAddAfterInsertsAtCorrectPosition(): void
    {
        $definition = new PipelineDefinition('test', [TestStep1::class, TestStep2::class]);
        $pipeline = new Pipeline($definition->steps);

        $pipeline->addAfter(TestStep1::class, TestStep1Point5::class);

        $reflection = new \ReflectionClass($pipeline);
        $stepsProperty = $reflection->getProperty('steps');
        $stepsProperty->setAccessible(true);
        $steps = $stepsProperty->getValue($pipeline);

        self::assertSame(TestStep1::class, $steps[0]);
        self::assertSame(TestStep1Point5::class, $steps[1]);
        self::assertSame(TestStep2::class, $steps[2]);
    }

    public function testThrowsOnMissingTargetStepForBefore(): void
    {
        $definition = new PipelineDefinition('test', [TestStep1::class]);
        $pipeline = new Pipeline($definition->steps);

        $this->expectException(PipelineStepNotFoundException::class);
        $pipeline->addBefore('NonexistentStep', TestStep2::class);
    }

    public function testThrowsOnMissingTargetStepForAfter(): void
    {
        $definition = new PipelineDefinition('test', [TestStep1::class]);
        $pipeline = new Pipeline($definition->steps);

        $this->expectException(PipelineStepNotFoundException::class);
        $pipeline->addAfter('NonexistentStep', TestStep2::class);
    }
}

final class PipelineRegistryTest extends TestCase
{
    public function testDefinesPipeline(): void
    {
        $registry = new PipelineRegistry();
        $definition = new PipelineDefinition('test', [TestStep1::class]);

        $registry->define($definition);

        self::assertInstanceOf(Pipeline::class, $registry->get('test'));
    }

    public function testThrowsOnMissingPipeline(): void
    {
        $registry = new PipelineRegistry();

        $this->expectException(PipelineNotFoundException::class);
        $registry->get('nonexistent');
    }

    public function testAddBeforeCallsPipeline(): void
    {
        $registry = new PipelineRegistry();
        $definition = new PipelineDefinition('test', [TestStep1::class, TestStep3::class]);
        $registry->define($definition);

        $registry->addBefore('test', TestStep3::class, TestStep2::class);

        $pipeline = $registry->get('test');
        $reflection = new \ReflectionClass($pipeline);
        $stepsProperty = $reflection->getProperty('steps');
        $stepsProperty->setAccessible(true);
        $steps = $stepsProperty->getValue($pipeline);

        self::assertCount(3, $steps);
    }

    public function testAddAfterCallsPipeline(): void
    {
        $registry = new PipelineRegistry();
        $definition = new PipelineDefinition('test', [TestStep1::class, TestStep2::class]);
        $registry->define($definition);

        $registry->addAfter('test', TestStep1::class, TestStep1Point5::class);

        $pipeline = $registry->get('test');
        $reflection = new \ReflectionClass($pipeline);
        $stepsProperty = $reflection->getProperty('steps');
        $stepsProperty->setAccessible(true);
        $steps = $stepsProperty->getValue($pipeline);

        self::assertCount(3, $steps);
    }

    public function testThrowsOnAddBeforeNonexistentPipeline(): void
    {
        $registry = new PipelineRegistry();

        $this->expectException(PipelineNotFoundException::class);
        $registry->addBefore('nonexistent', TestStep1::class, TestStep2::class);
    }

    public function testThrowsOnAddAfterNonexistentPipeline(): void
    {
        $registry = new PipelineRegistry();

        $this->expectException(PipelineNotFoundException::class);
        $registry->addAfter('nonexistent', TestStep1::class, TestStep2::class);
    }
}

class TestStep1 implements PipelineStepInterface
{
    public function handle(object $payload, PipelineNext $next): object
    {
        return $next($payload);
    }
}

class TestStep1Point5 implements PipelineStepInterface
{
    public function handle(object $payload, PipelineNext $next): object
    {
        return $next($payload);
    }
}

class TestStep2 implements PipelineStepInterface
{
    public function handle(object $payload, PipelineNext $next): object
    {
        return $next($payload);
    }
}

class TestStep3 implements PipelineStepInterface
{
    public function handle(object $payload, PipelineNext $next): object
    {
        return $next($payload);
    }
}
