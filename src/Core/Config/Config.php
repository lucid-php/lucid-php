<?php

declare(strict_types=1);

namespace Core\Config;

class Config
{
    private array $config = [];

    public function __construct(private readonly string $configPath)
    {
        if (!is_dir($configPath)) {
            throw new \RuntimeException("Config directory not found: {$configPath}");
        }
    }

    /**
     * Load a configuration file.
     *
     * @param string $file The config file name (without .php extension)
     * @return array The configuration array
     */
    public function load(string $file): array
    {
        if (isset($this->config[$file])) {
            return $this->config[$file];
        }

        $filePath = $this->configPath . '/' . $file . '.php';

        if (!file_exists($filePath)) {
            throw new \RuntimeException("Config file not found: {$filePath}");
        }

        $config = require $filePath;

        if (!is_array($config)) {
            throw new \RuntimeException("Config file must return an array: {$filePath}");
        }

        $this->config[$file] = $config;
        return $config;
    }

    /**
     * Get a configuration value using dot notation.
     *
     * @param string $key The config key (e.g., 'database.driver')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        $config = $this->load($file);

        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return $default;
            }
            $config = $config[$part];
        }

        return $config;
    }

    /**
     * Typed accessors. These cast the resolved value and avoid the `mixed`
     * return of get(), keeping call sites strictly typed. Each throws if the
     * key is present but holds an incompatible type.
     */
    public function getString(string $key, ?string $default = null): string
    {
        $value = $this->get($key, $default);

        if (!is_string($value)) {
            throw $this->typeError($key, 'string', $value);
        }

        return $value;
    }

    public function getInt(string $key, ?int $default = null): int
    {
        $value = $this->get($key, $default);

        if (is_int($value)) {
            return $value;
        }

        // Accept clean numeric strings (e.g. env-derived values).
        if (is_string($value) && $value !== '' && (string) (int) $value === $value) {
            return (int) $value;
        }

        throw $this->typeError($key, 'int', $value);
    }

    public function getBool(string $key, ?bool $default = null): bool
    {
        $value = $this->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        // Accept common truthy/falsy scalars (e.g. "true"/"1"/0).
        $normalized = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($normalized !== null) {
            return $normalized;
        }

        throw $this->typeError($key, 'bool', $value);
    }

    /**
     * @param array<mixed>|null $default
     * @return array<mixed>
     */
    public function getArray(string $key, ?array $default = null): array
    {
        $value = $this->get($key, $default);

        if (!is_array($value)) {
            throw $this->typeError($key, 'array', $value);
        }

        return $value;
    }

    private function typeError(string $key, string $expected, mixed $actual): \RuntimeException
    {
        return new \RuntimeException(
            "Config key '{$key}' expected {$expected}, got " . get_debug_type($actual) . '.'
        );
    }

    /**
     * Check if a configuration key exists.
     */
    public function has(string $key): bool
    {
        try {
            $parts = explode('.', $key);
            $file = array_shift($parts);
            $config = $this->load($file);

            foreach ($parts as $part) {
                if (!isset($config[$part])) {
                    return false;
                }
                $config = $config[$part];
            }

            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }

    /**
     * Get all configuration for a file.
     */
    public function all(string $file): array
    {
        return $this->load($file);
    }
}
