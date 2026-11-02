<?php

namespace App\AppCore;

use Psr\Container\ContainerInterface;
use App\AppCore\Event\EventDispatcher;
use App\AppCore\Transaction\TransactionManager;

class Facade
{
    private static ?ContainerInterface $container = null;
    private static array $instances = [];

    public static function setContainer(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    public static function getContainer(): ?ContainerInterface
    {
        return self::$container;
    }

    public static function get(string $service): mixed
    {
        if (!self::$container) {
            throw new \RuntimeException('Container not initialized. Call Facade::setContainer() first.');
        }

        return self::$container->get($service);
    }

    public static function call(string $method, array $params = []): mixed
    {
        if (strpos($method, '::') === false) {
            throw new \InvalidArgumentException('Method must be in format "Class::method"');
        }

        [$class, $method] = explode('::', $method, 2);

        $instance = self::getInstance($class);

        if (!method_exists($instance, $method)) {
            throw new \BadMethodCallException("Method {$method} does not exist on {$class}");
        }

        return $instance->$method(...$params);
    }

    public static function getInstance(string $class): object
    {
        if (!isset(self::$instances[$class])) {
            if (self::$container && self::$container->has($class)) {
                self::$instances[$class] = self::$container->get($class);
            } else {
                self::$instances[$class] = new $class();
            }
        }

        return self::$instances[$class];
    }

    public static function module(string $moduleName): ?object
    {
        if (!self::$container) {
            throw new \RuntimeException('Container not initialized');
        }

        $registry = self::$container->get(ModuleRegistry::class);

        return $registry?->getModule($moduleName);
    }

    public static function event(): EventDispatcher
    {
        return self::get(EventDispatcher::class);
    }

    public static function transaction(): TransactionManager
    {
        return self::get(TransactionManager::class);
    }

    public static function config(string $key = null, $default = null): mixed
    {
        $config = self::get('config');

        if ($key === null) {
            return $config;
        }

        return $config[$key] ?? $default;
    }

    public static function clearInstances(): void
    {
        self::$instances = [];
    }

    public static function hasInstance(string $class): bool
    {
        return isset(self::$instances[$class]);
    }
}