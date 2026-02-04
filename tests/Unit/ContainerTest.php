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
}

class SimpleService {}

class DependentService {
    public function __construct(public SimpleService $simple) {}
}
