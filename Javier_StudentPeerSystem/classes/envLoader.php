<?php

/**
 * Environment Configuration Loader
 * Loads .env file variables into PHP environment
 * Simple implementation without dependencies
 */

class EnvLoader
{
    /**
     * Load environment variables from .env file
     * @param string $filePath Path to .env file
     * @return bool Success status
     */
    public static function load($filePath)
    {
        if (!file_exists($filePath)) {
            error_log("Warning: .env file not found at: $filePath");
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE format
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);

                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                $value = trim($value, '"\'');

                // Set environment variable
                if (!empty($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }

        return true;
    }
}
