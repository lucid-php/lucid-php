<?php

declare(strict_types=1);

namespace Core\Console;

use Core\Attributes\Argument;
use Core\Attributes\ConsoleCommand;
use Core\Attributes\Option;
use Core\Container;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

class ConsoleApplication
{
    /** @var array<string, array{class: string, method: string}> */
    private array $commands = [];

    public function __construct(
        private readonly Container $container,
        private readonly OutputInterface $output
    ) {}

    /**
     * Register commands explicitly (no auto-discovery)
     * 
     * @param array<class-string> $commandClasses
     */
    public function registerCommands(array $commandClasses): void
    {
        foreach ($commandClasses as $commandClass) {
            $reflection = new ReflectionClass($commandClass);
            $attributes = $reflection->getAttributes(ConsoleCommand::class);

            if (empty($attributes)) {
                throw new RuntimeException(
                    "Command class {$commandClass} must have #[ConsoleCommand] attribute"
                );
            }

            $commandAttr = $attributes[0]->newInstance();
            
            // Find execute method
            if (!$reflection->hasMethod('execute')) {
                throw new RuntimeException(
                    "Command class {$commandClass} must have execute() method"
                );
            }

            $this->commands[$commandAttr->name] = [
                'class' => $commandClass,
                'method' => 'execute',
                'description' => $commandAttr->description
            ];
        }
    }

    public function run(array $argv): int
    {
        array_shift($argv); // Remove script name

        if (empty($argv)) {
            $this->showHelp();
            return 0;
        }

        $commandName = $argv[0];

        if ($commandName === 'list' || $commandName === '--help' || $commandName === '-h') {
            $this->showHelp();
            return 0;
        }

        if (!isset($this->commands[$commandName])) {
            $this->output->error("Command '{$commandName}' not found.");
            $this->output->writeln('');
            $this->showHelp();
            return 1;
        }

        $config = $this->commands[$commandName];
        $commandInstance = $this->container->get($config['class']);

        // Parse arguments and options
        $args = $this->parseArguments(
            $config['class'],
            $config['method'],
            array_slice($argv, 1)
        );

        try {
            return $commandInstance->execute($this->output, ...$args);
        } catch (\Throwable $e) {
            $this->output->error('Command failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Parse command-line arguments into typed method parameters
     */
    private function parseArguments(string $class, string $method, array $argv): array
    {
        $reflection = new ReflectionClass($class);
        $methodRef = $reflection->getMethod($method);
        $parameters = $methodRef->getParameters();
        
        $args = [];
        $parsedOptions = $this->parseOptions($argv);
        $positionalArgs = $parsedOptions['args'];
        $options = $parsedOptions['options'];
        
        $argIndex = 0;

        foreach ($parameters as $parameter) {
            // Skip OutputInterface (already injected)
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && $type->getName() === OutputInterface::class) {
                continue;
            }

            // Check for Argument attribute
            $argAttrs = $parameter->getAttributes(Argument::class);
            if (!empty($argAttrs)) {
                $argAttr = $argAttrs[0]->newInstance();
                
                if (isset($positionalArgs[$argIndex])) {
                    $args[] = $this->castValue($positionalArgs[$argIndex], $parameter);
                    $argIndex++;
                } elseif ($argAttr->required) {
                    throw new RuntimeException(
                        "Missing required argument: {$argAttr->name}"
                    );
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                } else {
                    throw new RuntimeException(
                        "Missing required argument: {$argAttr->name}"
                    );
                }
                continue;
            }

            // Check for Option attribute
            $optAttrs = $parameter->getAttributes(Option::class);
            if (!empty($optAttrs)) {
                $optAttr = $optAttrs[0]->newInstance();
                $optionName = $optAttr->name;
                
                if (isset($options[$optionName])) {
                    $args[] = $this->castValue($options[$optionName], $parameter);
                } elseif (isset($options[$optAttr->shortcut]) && $optAttr->shortcut !== '') {
                    $args[] = $this->castValue($options[$optAttr->shortcut], $parameter);
                } elseif ($optAttr->default !== null) {
                    $args[] = $optAttr->default;
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                } else {
                    $args[] = null;
                }
                continue;
            }
        }

        return $args;
    }

    /**
     * Parse argv into positional arguments and options
     */
    private function parseOptions(array $argv): array
    {
        $args = [];
        $options = [];

        for ($i = 0; $i < count($argv); $i++) {
            $arg = $argv[$i];

            // Long option: --option=value or --option value
            if (str_starts_with($arg, '--')) {
                $option = substr($arg, 2);
                
                if (str_contains($option, '=')) {
                    [$name, $value] = explode('=', $option, 2);
                    $options[$name] = $value;
                } else {
                    // Check if next arg is a value
                    if (isset($argv[$i + 1]) && !str_starts_with($argv[$i + 1], '-')) {
                        $options[$option] = $argv[$i + 1];
                        $i++;
                    } else {
                        $options[$option] = true;
                    }
                }
            }
            // Short option: -o value or -o=value
            elseif (str_starts_with($arg, '-')) {
                $option = substr($arg, 1);
                
                if (str_contains($option, '=')) {
                    [$name, $value] = explode('=', $option, 2);
                    $options[$name] = $value;
                } else {
                    // Check if next arg is a value
                    if (isset($argv[$i + 1]) && !str_starts_with($argv[$i + 1], '-')) {
                        $options[$option] = $argv[$i + 1];
                        $i++;
                    } else {
                        $options[$option] = true;
                    }
                }
            }
            // Positional argument
            else {
                $args[] = $arg;
            }
        }

        return ['args' => $args, 'options' => $options];
    }

    /**
     * Cast argument value to parameter type
     */
    private function castValue(mixed $value, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();
        
        if (!$type instanceof ReflectionNamedType) {
            return $value;
        }

        return match ($type->getName()) {
            'int' => (int)$value,
            'float' => (float)$value,
            'bool' => $value === true || $value === 'true' || $value === '1',
            'string' => (string)$value,
            default => $value
        };
    }

    private function showHelp(): void
    {
        $this->output->writeln('');
        $this->output->writeln('Available commands:');
        $this->output->writeln('');

        if (empty($this->commands)) {
            $this->output->writeln('  No commands registered.');
            return;
        }

        $maxLength = max(array_map('strlen', array_keys($this->commands)));

        foreach ($this->commands as $name => $config) {
            $padding = str_repeat(' ', $maxLength - strlen($name) + 2);
            $description = $config['description'] ?: 'No description';
            $this->output->writeln("  {$name}{$padding}{$description}");
        }

        $this->output->writeln('');
    }

    /**
     * Get all registered command names
     * 
     * @return array<string>
     */
    public function getCommandNames(): array
    {
        return array_keys($this->commands);
    }
}
