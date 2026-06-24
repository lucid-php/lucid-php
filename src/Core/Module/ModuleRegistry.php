<?php

declare(strict_types=1);

namespace Core\Module;

final class ModuleRegistry
{
    /**
     * @var array<string, ModuleInterface>
     */
    private array $modules = [];

    /**
     * @param list<ModuleInterface> $modules
     */
    public function __construct(array $modules = [])
    {
        foreach ($modules as $module) {
            $this->register($module);
        }
    }

    public function register(ModuleInterface $module): void
    {
        $name = $module->name();

        if (isset($this->modules[$name])) {
            throw new ModuleAlreadyRegisteredException(
                sprintf('Module "%s" is already registered', $name)
            );
        }

        $this->modules[$name] = $module;
    }

    /**
     * @return list<ModuleInterface>
     */
    public function getSorted(): array
    {
        $this->validateDependencies();
        return $this->topologicalSort();
    }

    private function validateDependencies(): void
    {
        foreach ($this->modules as $module) {
            foreach ($module->dependsOn() as $dependency) {
                if (!isset($this->modules[$dependency])) {
                    throw new ModuleNotFoundException(
                        sprintf(
                            'Module "%s" depends on missing module "%s"',
                            $module->name(),
                            $dependency
                        )
                    );
                }
            }
        }
    }

    /**
     * @return list<ModuleInterface>
     */
    private function topologicalSort(): array
    {
        $visited = [];
        $visiting = [];
        $result = [];

        $moduleNames = array_keys($this->modules);
        sort($moduleNames);

        foreach ($moduleNames as $moduleName) {
            if (!isset($visited[$moduleName])) {
                $this->visit($moduleName, $visited, $visiting, $result, []);
            }
        }

        return $result;
    }

    /**
     * @param array<string, bool> $visited
     * @param array<string, bool> $visiting
     * @param list<ModuleInterface> $result
     * @param list<string> $path
     */
    private function visit(
        string $moduleName,
        array &$visited,
        array &$visiting,
        array &$result,
        array $path,
    ): void {
        if (isset($visiting[$moduleName])) {
            $cycleStart = array_search($moduleName, $path, true);
            $cyclePath = $cycleStart === false
                ? [...$path, $moduleName]
                : [...array_slice($path, $cycleStart), $moduleName];
            throw new CircularModuleDependencyException(
                sprintf(
                    'Circular module dependency detected: %s',
                    implode(' -> ', $cyclePath)
                )
            );
        }

        if (isset($visited[$moduleName])) {
            return;
        }

        $visiting[$moduleName] = true;
        $path[] = $moduleName;
        $module = $this->modules[$moduleName];
        $dependencies = $module->dependsOn();
        sort($dependencies);

        foreach ($dependencies as $dependency) {
            $this->visit($dependency, $visited, $visiting, $result, $path);
        }

        unset($visiting[$moduleName]);
        $visited[$moduleName] = true;
        $result[] = $module;
    }
}
