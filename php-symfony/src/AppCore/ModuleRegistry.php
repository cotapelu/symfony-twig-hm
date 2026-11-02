<?php

namespace App\AppCore;

use App\Attribute\Module;
use ReflectionClass;
use ReflectionException;

class ModuleRegistry
{
    private array $modules = [];
    private array $loadedModules = [];
    private array $dependencies = [];
    private array $loadOrder = [];

    public function discover(string $name, string $path): void
    {
        try {
            $moduleClass = $this->findModuleClassInPath($path);
            if ($moduleClass) {
                $this->modules[$name] = ['class' => $moduleClass, 'path' => $path, 'name' => $name];
            }
        } catch (ReflectionException $e) {
            throw new \RuntimeException("Failed to discover module {$name}: " . $e->getMessage());
        }
    }

    public function load(string $moduleName): void
    {
        if (isset($this->loadedModules[$moduleName])) return;
        if (!isset($this->modules[$moduleName])) throw new \InvalidArgumentException("Module {$moduleName} not found");
        $moduleInfo = $this->modules[$moduleName];
        $moduleClass = $moduleInfo['class'];
        try {
            $module = new $moduleClass();
            $this->loadedModules[$moduleName] = $module;
            $this->dependencies[$moduleName] = $module->getDependencies() ?? [];
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to load module {$moduleName}: " . $e->getMessage());
        }
    }

    public function getLoadedModules(): array
    {
        return $this->loadedModules;
    }

    public function getModule(string $name): ?object
    {
        return $this->loadedModules[$name] ?? null;
    }

    public function isLoaded(string $name): bool
    {
        return isset($this->loadedModules[$name]);
    }

    public function getDependencies(string $moduleName): array
    {
        return $this->dependencies[$moduleName] ?? [];
    }

    public function getLoadOrder(): array
    {
        if (empty($this->loadOrder)) $this->calculateLoadOrder();
        return $this->loadOrder;
    }

    private function calculateLoadOrder(): void
    {
        $visited = $visiting = $order = [];
        foreach (array_keys($this->loadedModules) as $module) {
            $this->visitModule($module, $visited, $visiting, $order);
        }
        $this->loadOrder = $order;
    }

    private function visitModule(string $module, array &$visited, array &$visiting, array &$order): void
    {
        if (in_array($module, $visited)) return;
        if (in_array($module, $visiting)) throw new \RuntimeException("Circular dependency detected involving module {$module}");
        $visiting[] = $module;
        $dependencies = $this->getDependencies($module);
        foreach ($dependencies as $dependency) {
            if (isset($this->loadedModules[$dependency])) {
                $this->visitModule($dependency, $visited, $visiting, $order);
            }
        }
        $visiting = array_diff($visiting, [$module]);
        $visited[] = $module;
        $order[] = $module;
    }

    private function findModuleClassInPath(string $path): ?string
    {
        $files = glob($path . '/*.php');
        foreach ($files as $file) {
            $className = $this->extractClassName($file);
            if ($className && class_exists($className)) {
                $reflection = new ReflectionClass($className);
                if ($reflection->getAttributes(Module::class)) {
                    return $className;
                }
            }
        }
        return null;
    }

    private function extractClassName(string $file): ?string
    {
        $content = file_get_contents($file);
        if ($content === false) return null;
        if (preg_match('/^<\?php\s+namespace\s+([^;]+);/m', $content, $matches)) {
            $namespace = trim($matches[1]);
            if (preg_match('/^(?:final\s+)?(?:abstract\s+)?class\s+(\w+)/m', $content, $classMatches)) {
                return $namespace . '\\' . trim($classMatches[1]);
            }
        }
        return null;
    }
}
