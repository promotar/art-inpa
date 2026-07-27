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
        $path = base_path('.env');
        $content = File::exists($path) ? File::get($path) : '';

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->quote($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
            $content = preg_match($pattern, $content)
                ? preg_replace($pattern, $line, $content)
                : rtrim($content).PHP_EOL.$line.PHP_EOL;
        }

        File::put($path, ltrim($content));
    }

    private function value(string $key, string $default): string
    {
        $path = base_path('.env');
        if (! File::exists($path)) {
            return $default;
        }

        if (preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', File::get($path), $match) !== 1) {
            return $default;
        }

        return trim(trim($match[1]), "\"'");
    }

    private function quote(string $value): string
    {
        return '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '', ''], $value).'"';
    }
}
