<?php

namespace App\Installation;

use Illuminate\Support\Facades\File;

final class InstallationState
{
    public function installed(): bool
    {
        return $this->value('INSTAAL_IS_ATIVE', '0') === '1';
    }

    public function setInstalled(bool $installed): void
    {
        $this->write(['INSTAAL_IS_ATIVE' => $installed ? '1' : '0']);
    }

    /** @param array<string, string> $values */
    public function write(array $values): void
    {
        $this->writeFile(RuntimeEnvironment::path(), $values, true);

        $environmentPath = base_path('.env');
        if (File::exists($environmentPath) && is_writable($environmentPath)) {
            $this->writeFile($environmentPath, $values);
        }
    }

    private function value(string $key, string $default): string
    {
        foreach ([RuntimeEnvironment::path(), base_path('.env')] as $path) {
            if (! File::exists($path)) {
                continue;
            }

            if (preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', File::get($path), $match) === 1) {
                return trim(trim($match[1]), "\"'");
            }
        }

        return $default;
    }

    private function quote(string $value): string
    {
        return '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '', ''], $value).'"';
    }

    /** @param array<string, string> $values */
    private function writeFile(string $path, array $values, bool $protect = false): void
    {
        File::ensureDirectoryExists(dirname($path));
        $content = File::exists($path) ? File::get($path) : '';

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->quote((string) $value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            $content = preg_match($pattern, $content)
                ? (string) preg_replace($pattern, $line, $content)
                : rtrim($content).PHP_EOL.$line.PHP_EOL;
        }

        File::put($path, ltrim($content), true);
        if ($protect) {
            @chmod($path, 0600);
        }
    }
}
