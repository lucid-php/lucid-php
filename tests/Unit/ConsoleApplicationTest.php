<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Attributes\ConsoleCommand;
use Core\Console\CommandInterface;
use Core\Console\ConsoleApplication;
use Core\Console\ConsoleOutput;
use Core\Console\OutputInterface;
use Core\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ConsoleApplicationTest extends TestCase
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

    public function testRegisterCommandsWithAttribute(): void
    {
        $this->console->registerCommands([TestCommand::class]);
        
        $commands = $this->console->getCommandNames();
        
        $this->assertContains('test:command', $commands);
    }

    public function testRegisterCommandThrowsIfNoAttribute(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must have #[ConsoleCommand] attribute');
        
        $this->console->registerCommands([CommandWithoutAttribute::class]);
    }

    public function testRegisterCommandThrowsIfNoExecuteMethod(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must have execute() method');
        
        $this->console->registerCommands([CommandWithoutExecute::class]);
    }

    public function testRunUnknownCommandReturnsError(): void
    {
        $exitCode = $this->console->run(['console', 'unknown:command']);
        
        $this->assertEquals(1, $exitCode);
    }

    public function testRunWithNoArgumentsShowsHelp(): void
    {
        $this->console->registerCommands([TestCommand::class]);
        
        $exitCode = $this->console->run(['console']);
        
        $this->assertEquals(0, $exitCode);
    }

    public function testRunWithHelpFlagShowsHelp(): void
    {
        $this->console->registerCommands([TestCommand::class]);
        
        $exitCode = $this->console->run(['console', '--help']);
        
        $this->assertEquals(0, $exitCode);
    }

    public function testRunExecutesCommand(): void
    {
        $this->container->set(TestCommand::class, fn() => new TestCommand());
        $this->console->registerCommands([TestCommand::class]);
        
        $exitCode = $this->console->run(['console', 'test:command']);
        
        $this->assertEquals(0, $exitCode);
    }

    public function testRunHandlesCommandException(): void
    {
        $this->container->set(FailingCommand::class, fn() => new FailingCommand());
        $this->console->registerCommands([FailingCommand::class]);
        
        $exitCode = $this->console->run(['console', 'test:failing']);
        
        $this->assertEquals(1, $exitCode);
    }

    public function testGetCommandNamesReturnsAllRegistered(): void
    {
        $this->console->registerCommands([
            TestCommand::class,
        ]);
        
        $names = $this->console->getCommandNames();
        
        $this->assertCount(1, $names);
        $this->assertContains('test:command', $names);
    }
}

#[ConsoleCommand(name: 'test:command', description: 'Test command')]
class TestCommand implements CommandInterface
{
    public function execute(OutputInterface $output): int
    {
        return 0;
    }
}

class CommandWithoutAttribute implements CommandInterface
{
    public function execute(OutputInterface $output): int
    {
        return 0;
    }
}

#[ConsoleCommand(name: 'test:no-execute')]
class CommandWithoutExecute
{
    // Missing execute method
}

#[ConsoleCommand(name: 'test:failing')]
class FailingCommand implements CommandInterface
{
    public function execute(OutputInterface $output): int
    {
        throw new RuntimeException('Command failed');
    }
}
