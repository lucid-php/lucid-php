<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Attributes\Argument;
use Core\Attributes\ConsoleCommand;
use Core\Attributes\Option;
use Core\Console\CommandInterface;
use Core\Console\ConsoleApplication;
use Core\Console\ConsoleOutput;
use Core\Console\OutputInterface;
use Core\Container;
use PHPUnit\Framework\TestCase;

class ConsoleArgumentsTest extends TestCase
{
    private Container $container;
    private ConsoleOutput $output;
    private ConsoleApplication $console;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->output = new ConsoleOutput();
        $this->console = new ConsoleApplication($this->container, $this->output);
    }

    public function testCommandWithSingleArgument(): void
    {
        $command = new ArgumentCommand();
        $this->container->set(ArgumentCommand::class, fn() => $command);
        $this->console->registerCommands([ArgumentCommand::class]);
        
        $exitCode = $this->console->run(['console', 'test:args', 'value1']);
        
        $this->assertEquals(0, $exitCode);
        $this->assertEquals('value1', $command->receivedArg);
    }

    public function testCommandWithMultipleArguments(): void
    {
        $command = new MultipleArgumentsCommand();
        $this->container->set(MultipleArgumentsCommand::class, fn() => $command);
        $this->console->registerCommands([MultipleArgumentsCommand::class]);
        
        $exitCode = $this->console->run(['console', 'test:multi-args', 'first', 'second']);
        
        $this->assertEquals(0, $exitCode);
        $this->assertEquals('first', $command->arg1);
        $this->assertEquals('second', $command->arg2);
    }

    public function testCommandWithOption(): void
    {
        $command = new OptionCommand();
        $this->container->set(OptionCommand::class, fn() => $command);
        $this->console->registerCommands([OptionCommand::class]);
        
        $exitCode = $this->console->run(['console', 'test:option', '--name=John']);
        
        $this->assertEquals(0, $exitCode);
        $this->assertEquals('John', $command->receivedOption);
    }

    public function testCommandWithShortOption(): void
    {
        $command = new OptionCommand();
        $this->container->set(OptionCommand::class, fn() => $command);
        $this->console->registerCommands([OptionCommand::class]);
        
        $exitCode = $this->console->run(['console', 'test:option', '-n', 'Jane']);
        
        $this->assertEquals(0, $exitCode);
        $this->assertEquals('Jane', $command->receivedOption);
    }

    public function testCommandWithDefaultOption(): void
    {
        $command = new OptionCommand();
        $this->container->set(OptionCommand::class, fn() => $command);
        $this->console->registerCommands([OptionCommand::class]);
        
        $exitCode = $this->console->run(['console', 'test:option']);
        
        $this->assertEquals(0, $exitCode);
        $this->assertEquals('default', $command->receivedOption);
    }

    public function testCommandWithIntArgument(): void
    {
        $command = new TypedArgumentCommand();
        $this->container->set(TypedArgumentCommand::class, fn() => $command);
        $this->console->registerCommands([TypedArgumentCommand::class]);
        
        $exitCode = $this->console->run(['console', 'test:typed', '42']);
        
        $this->assertEquals(0, $exitCode);
        $this->assertSame(42, $command->receivedInt);
    }

    public function testCommandWithBoolOption(): void
    {
        $command = new BoolOptionCommand();
        $this->container->set(BoolOptionCommand::class, fn() => $command);
        $this->console->registerCommands([BoolOptionCommand::class]);
        
        $exitCode = $this->console->run(['console', 'test:bool', '--force']);
        
        $this->assertEquals(0, $exitCode);
        $this->assertTrue($command->receivedBool);
    }

    public function testCommandWithMixedArgumentsAndOptions(): void
    {
        $command = new MixedCommand();
        $this->container->set(MixedCommand::class, fn() => $command);
        $this->console->registerCommands([MixedCommand::class]);
        
        $exitCode = $this->console->run(['console', 'test:mixed', 'arg-value', '--opt=option-value']);
        
        $this->assertEquals(0, $exitCode);
        $this->assertEquals('arg-value', $command->receivedArg);
        $this->assertEquals('option-value', $command->receivedOpt);
    }
}

#[ConsoleCommand('test:args')]
class ArgumentCommand implements CommandInterface
{
    public ?string $receivedArg = null;

    public function execute(
        OutputInterface $output,
        #[Argument('name', 'The name argument')]
        string $name
    ): int {
        $this->receivedArg = $name;
        return 0;
    }
}

#[ConsoleCommand('test:multi-args')]
class MultipleArgumentsCommand implements CommandInterface
{
    public ?string $arg1 = null;
    public ?string $arg2 = null;

    public function execute(
        OutputInterface $output,
        #[Argument('first')]
        string $first,
        #[Argument('second')]
        string $second
    ): int {
        $this->arg1 = $first;
        $this->arg2 = $second;
        return 0;
    }
}

#[ConsoleCommand('test:option')]
class OptionCommand implements CommandInterface
{
    public ?string $receivedOption = null;

    public function execute(
        OutputInterface $output,
        #[Option('name', 'n', 'Name option', 'default')]
        string $name = 'default'
    ): int {
        $this->receivedOption = $name;
        return 0;
    }
}

#[ConsoleCommand('test:typed')]
class TypedArgumentCommand implements CommandInterface
{
    public ?int $receivedInt = null;

    public function execute(
        OutputInterface $output,
        #[Argument('count')]
        int $count
    ): int {
        $this->receivedInt = $count;
        return 0;
    }
}

#[ConsoleCommand('test:bool')]
class BoolOptionCommand implements CommandInterface
{
    public ?bool $receivedBool = null;

    public function execute(
        OutputInterface $output,
        #[Option('force', 'f', 'Force option')]
        bool $force = false
    ): int {
        $this->receivedBool = $force;
        return 0;
    }
}

#[ConsoleCommand('test:mixed')]
class MixedCommand implements CommandInterface
{
    public ?string $receivedArg = null;
    public ?string $receivedOpt = null;

    public function execute(
        OutputInterface $output,
        #[Argument('name')]
        string $name,
        #[Option('opt', 'o')]
        ?string $opt = null
    ): int {
        $this->receivedArg = $name;
        $this->receivedOpt = $opt;
        return 0;
    }
}
