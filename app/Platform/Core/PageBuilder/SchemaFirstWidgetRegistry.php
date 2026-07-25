<?php

namespace App\Platform\Core\PageBuilder;

class SchemaFirstWidgetRegistry
{
    public function __construct(
        private readonly SchemaFirstWidgetSchemas $schemas,
        private readonly SchemaFirstStyleEngine $styleEngine,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function widgets(): array
    {
        $widgets = [];

        foreach ($this->schemas->definitions() as $type => $definition) {
            $definition['type'] = (string) ($definition['type'] ?? $type);

            if (! $this->hasRequiredSchema($definition)) {
                continue;
            }

            $definition['id'] = $definition['type'];
            $definition['component_type'] = (string) ($definition['component_type'] ?? ($definition['type'] === 'image'
                ? 'pb_image'
                : 'pb-schema-'.$definition['type']));
            $definition['schema_first'] = true;
            $definition['content'] = $this->editorPreviewHtml($definition);
            $definition['style_targets'] = $this->styleEngine->styles($definition, $definition['default_props'] ?? []);

            $widgets[$definition['type']] = $definition;
        }

        return array_values($widgets);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function widgetMap(): array
    {
        $map = [];

        foreach ($this->widgets() as $widget) {
            $map[(string) $widget['type']] = $widget;
        }

        return $map;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function blocks(): array
    {
        return array_map(fn (array $widget): array => [
            'id' => $widget['type'],
            'label' => $widget['label'],
            'category' => $widget['category'],
            'media' => $widget['icon'],
            'content' => $widget['content'],
        ], $this->widgets());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function elementRegistry(): array
    {
        $registry = [];

        foreach ($this->widgets() as $widget) {
            $registry[(string) $widget['type']] = [
                'type' => $widget['type'],
                'label' => $widget['label'],
                'icon' => $widget['icon'],
                'category' => $widget['category'],
                'default_props' => $widget['default_props'],
                'component_type' => $widget['component_type'],
                'tabs' => ['general', 'style', 'advanced'],
                'controls' => $this->controls($widget),
                'supports' => $widget['supports'],
                'editor_preview_renderer' => $widget['editor_preview_renderer'],
                'frontend_renderer_reference' => $widget['frontend_renderer_reference'],
                'schema_first' => true,
            ];
        }

        return $registry;
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function hasRequiredSchema(array $definition): bool
    {
        foreach (['general_schema', 'style_schema', 'advanced_schema'] as $key) {
            if (! isset($definition[$key]) || ! is_array($definition[$key]) || $definition[$key] === []) {
                return false;
            }
        }

        return trim((string) ($definition['type'] ?? '')) !== ''
            && trim((string) ($definition['label'] ?? '')) !== '';
    }

    /**
     * @param array<string, mixed> $widget
     * @return array<int, array<string, mixed>>
     */
    private function controls(array $widget): array
    {
        return array_values(array_merge(
            $widget['general_schema'],
            $widget['style_schema'],
            $widget['advanced_schema'],
        ));
    }

    /**
     * @param array<string, mixed> $widget
     */
    private function editorPreviewHtml(array $widget): string
    {
        $type = (string) $widget['type'];
        $props = is_array($widget['default_props'] ?? null) ? $widget['default_props'] : [];
        $attrs = $this->dataAttributes($type, (string) ($widget['component_type'] ?? ''), $props);

        return match ($type) {
            'container' => '<section'.$attrs.'><div data-pb-placeholder="true">Drop widgets here</div></section>',
            'heading' => '<h2'.$attrs.'>Heading text</h2>',
            'image' => '<figure'.$attrs.'><img src="" alt=""><figcaption></figcaption></figure>',
            'text' => '<p'.$attrs.'>Text content</p>',
            'button' => '<div'.$attrs.'><a href="#" class="pb-button-link">Button</a></div>',
            'divider' => '<div'.$attrs.'><span class="pb-divider-line"></span></div>',
            default => '<div'.$attrs.'></div>',
        };
    }

    /**
     * @param array<string, mixed> $props
     */
    private function dataAttributes(string $type, string $componentType, array $props): string
    {
        $attrs = [
            'data-pb-widget' => $type,
            'data-pb-type' => $type,
            'data-pb-component-type' => $componentType !== '' ? $componentType : ($type === 'image' ? 'pb_image' : 'pb-schema-'.$type),
            'data-pb-schema-first' => 'true',
        ];

        foreach ($props as $key => $value) {
            $attrs['data-pb-'.$this->kebab((string) $key)] = is_bool($value)
                ? ($value ? 'true' : 'false')
                : (string) $value;
        }

        return collect($attrs)
            ->map(fn (string $value, string $name): string => ' '.$name.'="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'"')
            ->implode('');
    }

    private function kebab(string $value): string
    {
        return str_replace('_', '-', $value);
    }
}
