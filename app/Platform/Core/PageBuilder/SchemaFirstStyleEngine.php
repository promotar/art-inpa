<?php

namespace App\Platform\Core\PageBuilder;

class SchemaFirstStyleEngine
{
    /**
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $props
     * @return array<string, array<string, string>>
     */
    public function styles(array $schema, array $props): array
    {
        $styles = [];

        foreach ($this->controls($schema) as $control) {
            $property = (string) ($control['cssProperty'] ?? '');

            if ($property === '') {
                continue;
            }

            $key = (string) ($control['key'] ?? '');
            $target = (string) ($control['target'] ?? 'wrapper');
            $value = $props[$key] ?? $control['default'] ?? null;
            $value = $this->cssValue($value, $control);

            if ($value === '') {
                continue;
            }

            $styles[$target] ??= [];
            $styles[$target][$property] = $value;
        }

        return $styles;
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<int, array<string, mixed>>
     */
    private function controls(array $schema): array
    {
        $groups = [
            $schema['general_schema'] ?? [],
            $schema['style_schema'] ?? [],
            $schema['advanced_schema'] ?? [],
        ];

        $controls = [];

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }

            foreach ($group as $control) {
                if (is_array($control)) {
                    $controls[] = $control;
                }
            }
        }

        return $controls;
    }

    /**
     * @param array<string, mixed> $control
     */
    private function cssValue(mixed $value, array $control = []): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return '';
        }

        $property = (string) ($control['cssProperty'] ?? '');

        if (in_array($property, ['line-height', 'font-weight', 'opacity'], true) && preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            return $value;
        }

        if (preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            return $value.'px';
        }

        return $value;
    }
}
