<?php

declare(strict_types=1);

namespace Platform\Config;

use Platform\Config\Exception\ConfigException;

/**
 * Manages application configuration with support for multiple sources
 */
class ConfigurationManager
{
    private array $config = [];

    public function __construct(array $initialConfig = [])
    {
        $this->config = $this->normalizeArrayKeys($initialConfig);
    }

    public function get(string $key, $default = null)
    {
        $this->validateStringNotEmpty($key, 'Configuration key');
        $keys = explode('.', $key);
        $current = $this->config;
        foreach ($keys as $k) {
            if (is_array($current) && array_key_exists($k, $current)) {
                $current = $current[$k];
            } else {
                return $default;
            }
        }
        return $current;
    }

    public function set(string $key, $value): void
    {
        $this->validateStringNotEmpty($key, 'Configuration key');
        $keys = explode('.', $key);
        $current = &$this->config;
        foreach (array_slice($keys, 0, -1) as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
        $current[array_slice($keys, -1)[0]] = $value;
    }

    public function has(string $key): bool
    {
        $this->validateStringNotEmpty($key, 'Configuration key');
        return $this->get($key, null) !== null;
    }

    public function all(): array
    {
        return $this->config;
    }

    public function loadFromFile(string $filePath): void
    {
        $this->validateStringNotEmpty($filePath, 'File path');
        if (!file_exists($filePath)) {
            throw new ConfigException(sprintf('Configuration file not found: %s', $filePath));
        }
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($fileExtension === '') {
            throw new ConfigException(sprintf('Unable to determine file extension: %s', $filePath));
        }
        $loader = $this->getFileLoader($fileExtension);
        $loader($filePath);
    }

    public function loadFromEnvironment(array $environment = [], string $prefix = ''): void
    {
        $env = $environment ?: $_ENV;
        foreach ($env as $key => $value) {
            if (empty($prefix) || str_starts_with($key, $prefix)) {
                $configKey = empty($prefix) ? $key : substr($key, strlen($prefix));
                $this->set(strtolower(str_replace('_', '.', $configKey)), $value);
            }
        }
    }

    private function validateStringNotEmpty(string $value, string $fieldName): void
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException(sprintf('%s cannot be empty', $fieldName));
        }
    }

    private function normalizeArrayKeys(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $normalizedKey = is_string($key) ? strtolower($key) : (string)$key;
            $result[$normalizedKey] = is_array($value) ? $this->normalizeArrayKeys($value) : $value;
        }
        return $result;
    }

    private function getFileLoader(string $extension): callable
    {
        return match ($extension) {
            'json' => fn($path) => $this->loadJsonFile($path),
            'php' => fn($path) => $this->loadPhpFile($path),
            'yaml', 'yml' => fn($path) => throw new ConfigException(sprintf('YAML configuration files are not supported: %s', $path)),
            default => fn($path) => throw new ConfigException(sprintf('Unsupported configuration file format: %s', pathinfo($path, PATHINFO_EXTENSION))),
        };
    }

    private function loadJsonFile(string $filePath): void
    {
        $json = file_get_contents($filePath);
        if ($json === false) {
            throw new ConfigException(sprintf('Failed to read JSON file: %s', $filePath));
        }
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ConfigException(sprintf('Failed to parse JSON file: %s. Error: %s', $filePath, json_last_error_msg()));
        }
        $this->config = array_merge($this->config, $this->normalizeArrayKeys($data));
    }

    private function loadPhpFile(string $filePath): void
    {
        $data = include $filePath;
        if (!is_array($data)) {
            throw new ConfigException(sprintf('PHP configuration file must return an array: %s', $filePath));
        }
        $this->config = array_merge($this->config, $this->normalizeArrayKeys($data));
    }
}
