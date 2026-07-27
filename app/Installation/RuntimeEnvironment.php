<?php

namespace App\Installation;

use Illuminate\Support\Facades\File;

final class RuntimeEnvironment
{
    public static function path(): string
    {
        return dirname(__DIR__, 2).'/storage/app/platform/installation.env';
    }

    public static function load(): void
    {
        self::ensureRuntimeDirectories();

        $path = self::path();
        if (! is_file($path) || ! is_readable($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (str_starts_with(ltrim($line), '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            if (preg_match('/^[A-Z][A-Z0-9_]*$/', $key) !== 1) {
                continue;
            }

            $value = trim(trim($value), "\"'");
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private static function ensureRuntimeDirectories(): void
    {
        $basePath = dirname(__DIR__, 2);
        $directories = [
            $basePath.'/bootstrap/cache',
            $basePath.'/storage/app/platform',
            $basePath.'/storage/framework/cache/data',
            $basePath.'/storage/framework/sessions',
            $basePath.'/storage/framework/views',
            $basePath.'/storage/logs',
        ];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }
        }
    }
}
