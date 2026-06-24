<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    #[Test]
    public function it_can_set_and_get_instances(): void
    {
        $container = new Container();
        $instance = new \stdClass();
        $container->set(\stdClass::class, $instance);

        $this->assertSame($instance, $container->get(\stdClass::class));
    }

    #[Test]
    public function it_can_autowire_simple_class(): void
    {
        $container = new Container();
        $instance = $container->get(SimpleService::class);

        $this->assertInstanceOf(SimpleService::class, $instance);
    }

    #[Test]
    public function it_can_autowire_dependencies(): void
    {
        $container = new Container();
        $service = $container->get(DependentService::class);

        $this->assertInstanceOf(DependentService::class, $service);
        $this->assertInstanceOf(SimpleService::class, $service->simple);
    }

    #[Test]
    public function it_throws_exception_if_class_not_found(): void
    {
        $this->expectException(\Exception::class);
        $container = new Container();
        (void) $container->get('NonExistentClass');
    }

    #[Test]
    public function it_resolves_a_bound_interface_to_its_concrete(): void
    {
        $container = new Container();
        $container->bind(GreeterInterface::class, EnglishGreeter::class);

        $greeter = $container->get(GreeterInterface::class);

        $this->assertInstanceOf(EnglishGreeter::class, $greeter);
    }

    #[Test]
    public function it_autowires_a_constructor_dependency_typed_on_a_bound_interface(): void
    {
        $container = new Container();
        $container->bind(GreeterInterface::class, EnglishGreeter::class);

        $consumer = $container->get(GreeterConsumer::class);

        $this->assertInstanceOf(GreeterConsumer::class, $consumer);
        $this->assertInstanceOf(EnglishGreeter::class, $consumer->greeter);
    }

    #[Test]
    public function has_reports_true_for_bound_interfaces(): void
    {
        $container = new Container();
        $this->assertFalse($container->has(GreeterInterface::class));

        $container->bind(GreeterInterface::class, EnglishGreeter::class);
        $this->assertTrue($container->has(GreeterInterface::class));
    }

    #[Test]
    public function it_throws_a_clear_error_for_an_unbound_interface_dependency(): void
    {
        $this->expectException(\Exception::class);
        $container = new Container();
        (void) $container->get(GreeterConsumer::class); // GreeterInterface not bound
    }
}

class SimpleService
{
}

class DependentService
{
    public function __construct(public SimpleService $simple)
    {
    }
}

interface GreeterInterface
{
    public function greet(): string;
}

class EnglishGreeter implements GreeterInterface
{
    public function greet(): string
    {
        return 'Hello';
    }
}

class GreeterConsumer
{
    public function __construct(public GreeterInterface $greeter)
    {
    }
}
