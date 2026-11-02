<?php

declare(strict_types=1);

namespace App\AppCore\Exception;

use RuntimeException;

class BootstrapException extends RuntimeException
{
    public static function moduleDiscoveryFailed(string $moduleName, string $path, \Exception $previous): self
    {
        return new self(
            sprintf('Failed to discover module "%s" at path "%s"', $moduleName, $path),
            0,
            $previous
        );
    }

    public static function moduleLoadFailed(string $moduleName, \Exception $previous): self
    {
        return new self(
            sprintf('Failed to load module "%s"', $moduleName),
            0,
            $previous
        );
    }

    public static function containerNotInitialized(): self
    {
        return new self('Container not initialized. Call Facade::setContainer() first.');
    }

    public static function moduleNotFound(string $moduleName): self
    {
        return new self(sprintf('Module "%s" not found', $moduleName));
    }
}