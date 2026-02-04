<?php

declare(strict_types=1);

namespace Core\Config;

class Config
{
    private array $config = [];

    public function __construct(private final string $configPath)
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
