<?php

namespace App\Platform\Core\PageBuilder;

class BuilderSanitizer
{
    /**
     * @return array{html: string, css: string}
     */
    public function sanitize(string $html, string $css, bool $allowUnsafe = false): array
    {
        if ($allowUnsafe) {
            return [
                'html' => $html,
                'css' => $css,
            ];
        }

        return [
            'html' => $this->sanitizeHtml($html),
            'css' => $this->sanitizeCss($css),
        ];
    }

    private function sanitizeHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace_callback('/<iframe\b(?P<attrs>[^>]*)>.*?<\/iframe>/is', function (array $matches): string {
            $src = $this->attributeValue((string) ($matches['attrs'] ?? ''), 'src');

            return $this->isSafeIframeUrl($src)
                ? '<iframe'.$this->safeAttributes((string) ($matches['attrs'] ?? ''), ['src' => $src]).'></iframe>'
                : '';
        }, $html) ?? $html;

        $html = preg_replace_callback('/<(?P<tag>[a-z][a-z0-9:-]*)(?P<attrs>[^>]*)>/is', function (array $matches): string {
            $tag = strtolower((string) $matches['tag']);

            if (in_array($tag, ['script'], true)) {
                return '';
            }

            return '<'.$tag.$this->safeAttributes((string) ($matches['attrs'] ?? '')).'>';
        }, $html) ?? $html;

        return trim($html);
    }

    private function sanitizeCss(string $css): string
    {
        if ($css === '') {
            return '';
        }

        $css = preg_replace('/@import\b[^;]+;/i', '', $css) ?? $css;
        $css = preg_replace('/expression\s*\([^)]*\)/i', '', $css) ?? $css;
        $css = preg_replace('/javascript\s*:/i', '', $css) ?? $css;
        $css = preg_replace('/behavior\s*:/i', 'blocked-behavior:', $css) ?? $css;
        $css = preg_replace('/-moz-binding\s*:/i', 'blocked-moz-binding:', $css) ?? $css;

        return trim($css);
    }

    /**
     * @param array<string, string|null> $forced
     */
    private function safeAttributes(string $attrs, array $forced = []): string
    {
        $safe = [];

        if (preg_match_all('/\s([A-Za-z_:][-A-Za-z0-9_:.]*)\s*=\s*(["\'])(.*?)\2/is', $attrs, $matches, PREG_SET_ORDER) !== false) {
            foreach ($matches as $match) {
                $name = strtolower((string) $match[1]);
                $value = trim((string) $match[3]);

                if ($name === '' || str_starts_with($name, 'on')) {
                    continue;
                }

                if (in_array($name, ['href', 'src', 'xlink:href', 'formaction'], true) && $this->isUnsafeUrl($value)) {
                    continue;
                }

                if ($name === 'style') {
                    $value = $this->sanitizeCss($value);
                }

                $safe[$name] = $value;
            }
        }

        foreach ($forced as $name => $value) {
            if ($value !== null && $value !== '') {
                $safe[strtolower($name)] = $value;
            }
        }

        return collect($safe)
            ->map(fn (string $value, string $name): string => ' '.$name.'="'.e($value).'"')
            ->implode('');
    }

    private function attributeValue(string $attrs, string $name): ?string
    {
        if (preg_match('/\s'.preg_quote($name, '/').'\s*=\s*(["\'])(.*?)\1/is', $attrs, $matches) !== 1) {
            return null;
        }

        return (string) $matches[2];
    }

    private function isUnsafeUrl(?string $url): bool
    {
        $url = strtolower(trim((string) $url));

        return $url !== '' && preg_match('/^(javascript|vbscript|data):/i', $url) === 1;
    }

    private function isSafeIframeUrl(?string $url): bool
    {
        $url = trim((string) $url);

        if ($url === '' || $this->isUnsafeUrl($url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host !== '' && (
            str_ends_with($host, 'youtube.com')
            || str_ends_with($host, 'youtu.be')
            || str_ends_with($host, 'vimeo.com')
            || str_ends_with($host, 'google.com')
            || str_ends_with($host, 'openstreetmap.org')
        );
    }
}
