<?php

namespace App\AppCore\Facade;

use App\AppCore\ModuleRegistry;

class ModuleFacade
{
    private ModuleRegistry $moduleRegistry;

    public function __construct(ModuleRegistry $moduleRegistry)
    {
        $this->moduleRegistry = $moduleRegistry;
    }

    public function get(string $moduleName): ?object
    {
        return $this->moduleRegistry->getModule($moduleName);
    }

    public function call(string $moduleName, string $method, ...$args): mixed
    {
        $module = $this->get($moduleName);
        
        if (!$module) {
            throw new \InvalidArgumentException("Module {$moduleName} not loaded");
        }

        if (!method_exists($module, $method)) {
            throw new \BadMethodCallException("Method {$method} not found in module {$moduleName}");
        }

        return $module->$method(...$args);
    }

    public function has(string $moduleName): bool
    {
        return $this->moduleRegistry->isLoaded($moduleName);
    }

    public function getAll(): array
    {
        return $this->moduleRegistry->getLoadedModules();
    }

    public function getDependencyGraph(): array
    {
        $graph = [];
        $modules = $this->getAll();

        foreach ($modules as $name => $module) {
            $dependencies = $this->moduleRegistry->getDependencies($name);
            $graph[$name] = $dependencies;
        }

        return $graph;
    }

    public function canCall(string $moduleName, string $method): bool
    {
        if (!$this->has($moduleName)) {
            return false;
        }

        $module = $this->get($moduleName);
        return method_exists($module, $method);
    }
}