<?php

declare(strict_types=1);

namespace Platform\Config\Exception;

use RuntimeException;

class ConfigException extends RuntimeException
{
    public static function fileNotFound(string $filePath): self
    {
        return new self(sprintf('Configuration file not found: %s', $filePath));
    }

    public static function unsupportedFormat(string $format): self
    {
        return new self(sprintf('Unsupported configuration file format: %s', $format));
    }

    public static function parsingFailed(string $filePath, \Exception $previous): self
    {
        return new self(
            sprintf('Failed to parse configuration file: %s', $filePath),
            0,
            $previous
        );
    }
}