<?php

namespace App\Platform\Core\PageBuilder;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TemplateEditableRenderer
{
    /**
     * @return array<string, mixed>
     */
    public function projectData(?string $json): array
    {
        if (! is_string($json) || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function editorState(object $page): array
    {
        $project = $this->projectData($page->page_builder_json ?? null);
        $html = (string) ($page->html ?: $page->content ?: '');
        $schema = $this->schema($project, $html);

        return [
            'enabled' => $schema !== [],
            'template_key' => (string) ($project['template_key'] ?? $schema['template_key'] ?? ''),
            'template_name' => (string) ($schema['template_name'] ?? $page->title ?? 'Page template'),
            'schema' => $schema,
            'editable_data' => $this->editableData($project, $schema),
            'section_visibility' => $this->sectionVisibility($project, $schema),
            'section_order' => $this->sectionOrder($project, $schema),
            'preview_html' => $this->render($html, $project),
        ];
    }

    /**
     * @param array<string, mixed> $project
     * @return array<string, mixed>
     */
    public function schema(array $project, string $html): array
    {
        $schema = $project['editable_schema'] ?? null;

        if (is_array($schema) && isset($schema['sections']) && is_array($schema['sections'])) {
            return $this->normalizeSchema($schema);
        }

        return $this->schemaFromMarkup($html);
    }

    /**
     * @param array<string, mixed> $project
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function mergeEditablePayload(array $project, array $payload, string $html): array
    {
        $schema = $this->schema($project, $html);

        if ($schema === []) {
            throw new InvalidArgumentException('This page does not include an editable template schema.');
        }

        $validated = $this->validateEditablePayload($schema, $payload);

        $project['template_key'] = (string) ($project['template_key'] ?? $schema['template_key'] ?? '');
        $project['editable_schema'] = $schema;
        $project['editable_data'] = $validated['editable_data'];
        $project['section_visibility'] = $validated['section_visibility'];
        $project['section_order'] = $validated['section_order'];

        return $project;
    }

    /**
     * @param array<string, mixed> $project
     */
    public function render(string $html, array $project): string
    {
        $schema = $this->schema($project, $html);

        if ($schema === []) {
            return $html;
        }

        $data = $this->editableData($project, $schema);
        $visibility = $this->sectionVisibility($project, $schema);
        $order = $this->sectionOrder($project, $schema);
        $document = $this->document($html);
        $xpath = new DOMXPath($document);

        foreach ($schema['sections'] as $section) {
            $sectionKey = (string) $section['key'];
            $nodes = $this->sectionNodes($xpath, $sectionKey);

            foreach ($nodes as $node) {
                if (($visibility[$sectionKey] ?? true) === false) {
                    $node->parentNode?->removeChild($node);
                    continue;
                }

                $sectionData = is_array($data[$sectionKey] ?? null) ? $data[$sectionKey] : [];
                $this->applySectionFields($document, $xpath, $node, $section, $sectionData);
            }
        }

        $this->applySectionOrder($document, $xpath, $order);

        return $this->fragmentHtml($document);
    }

    /**
     * @param array<string, mixed> $schema
     * @param array<string, mixed> $payload
     * @return array{editable_data:array<string, mixed>,section_visibility:array<string, bool>,section_order:array<int, string>}
     */
    public function validateEditablePayload(array $schema, array $payload): array
    {
        $editableData = is_array($payload['editable_data'] ?? null) ? $payload['editable_data'] : [];
        $sectionVisibility = is_array($payload['section_visibility'] ?? null) ? $payload['section_visibility'] : [];
        $sectionOrder = is_array($payload['section_order'] ?? null) ? $payload['section_order'] : [];
        $validatedData = [];
        $validatedVisibility = [];
        $knownSections = [];

        foreach ($schema['sections'] as $section) {
            $sectionKey = (string) $section['key'];
            $knownSections[] = $sectionKey;
            $validatedVisibility[$sectionKey] = array_key_exists($sectionKey, $sectionVisibility)
                ? filter_var($sectionVisibility[$sectionKey], FILTER_VALIDATE_BOOLEAN)
                : (bool) ($section['visible'] ?? true);

            $sectionData = is_array($editableData[$sectionKey] ?? null) ? $editableData[$sectionKey] : [];
            $validatedData[$sectionKey] = [];

            foreach (($section['fields'] ?? []) as $field) {
                $fieldKey = (string) $field['key'];
                $value = $sectionData[$fieldKey] ?? ($field['default'] ?? null);
                $validatedData[$sectionKey][$fieldKey] = $this->validateField($field, $value, $sectionKey);
            }
        }

        $ordered = array_values(array_filter(array_map('strval', $sectionOrder), fn (string $key): bool => in_array($key, $knownSections, true)));

        foreach ($knownSections as $sectionKey) {
            if (! in_array($sectionKey, $ordered, true)) {
                $ordered[] = $sectionKey;
            }
        }

        return [
            'editable_data' => $validatedData,
            'section_visibility' => $validatedVisibility,
            'section_order' => $ordered,
        ];
    }

    /**
     * @param array<string, mixed> $field
     */
    private function validateField(array $field, mixed $value, string $sectionKey): mixed
    {
        $type = (string) ($field['type'] ?? 'text');
        $required = (bool) ($field['required'] ?? false);

        if ($type === 'repeater') {
            $items = is_array($value) ? array_values($value) : [];
            $fields = is_array($field['fields'] ?? null) ? $field['fields'] : [];

            return array_map(function (mixed $item) use ($fields, $sectionKey): array {
                $item = is_array($item) ? $item : [];
                $validated = [];

                foreach ($fields as $childField) {
                    $key = (string) ($childField['key'] ?? '');
                    if ($key === '') {
                        continue;
                    }
                    $validated[$key] = $this->validateField($childField, $item[$key] ?? ($childField['default'] ?? null), $sectionKey);
                }

                return $validated;
            }, $items);
        }

        if ($type === 'button') {
            $button = is_array($value) ? $value : [];
            $text = trim((string) ($button['text'] ?? ''));
            $url = trim((string) ($button['url'] ?? ''));

            if ($required && $text === '') {
                throw new InvalidArgumentException("Button text is required in section {$sectionKey}.");
            }

            if ($url !== '' && ! $this->isSafeUrl($url)) {
                throw new InvalidArgumentException("Button URL is invalid in section {$sectionKey}.");
            }

            return [
                'text' => Str::limit($text, 180, ''),
                'url' => $url,
            ];
        }

        if ($type === 'image') {
            $image = is_array($value) ? $value : ['src' => (string) $value];
            $src = trim((string) ($image['src'] ?? ''));
            $alt = trim((string) ($image['alt'] ?? ''));

            if ($required && $src === '') {
                throw new InvalidArgumentException("Image is required in section {$sectionKey}.");
            }

            if ($src !== '' && ! $this->isSafeImageUrl($src)) {
                throw new InvalidArgumentException("Image URL is invalid in section {$sectionKey}.");
            }

            return [
                'src' => $src,
                'alt' => Str::limit($alt, 255, ''),
            ];
        }

        if ($type === 'toggle') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        $text = is_scalar($value) || $value === null ? trim((string) $value) : '';

        if ($required && $text === '') {
            throw new InvalidArgumentException("Field {$field['key']} is required in section {$sectionKey}.");
        }

        if ($type === 'url' && $text !== '' && ! $this->isSafeUrl($text)) {
            throw new InvalidArgumentException("URL field {$field['key']} is invalid in section {$sectionKey}.");
        }

        $max = (int) Arr::get($field, 'validation.max', $type === 'textarea' || $type === 'rich_text_basic' ? 1000 : 255);

        return Str::limit($text, max(1, $max), '');
    }

    /**
     * @param array<string, mixed> $project
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function editableData(array $project, array $schema): array
    {
        $data = is_array($project['editable_data'] ?? null) ? $project['editable_data'] : [];

        foreach (($schema['sections'] ?? []) as $section) {
            $sectionKey = (string) $section['key'];
            $data[$sectionKey] = is_array($data[$sectionKey] ?? null) ? $data[$sectionKey] : [];

            foreach (($section['fields'] ?? []) as $field) {
                $fieldKey = (string) $field['key'];
                if (! array_key_exists($fieldKey, $data[$sectionKey])) {
                    $data[$sectionKey][$fieldKey] = $field['default'] ?? null;
                }
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $project
     * @param array<string, mixed> $schema
     * @return array<string, bool>
     */
    private function sectionVisibility(array $project, array $schema): array
    {
        $visibility = is_array($project['section_visibility'] ?? null) ? $project['section_visibility'] : [];

        foreach (($schema['sections'] ?? []) as $section) {
            $key = (string) $section['key'];
            $visibility[$key] = array_key_exists($key, $visibility)
                ? filter_var($visibility[$key], FILTER_VALIDATE_BOOLEAN)
                : (bool) ($section['visible'] ?? true);
        }

        return $visibility;
    }

    /**
     * @param array<string, mixed> $project
     * @param array<string, mixed> $schema
     * @return array<int, string>
     */
    private function sectionOrder(array $project, array $schema): array
    {
        $order = is_array($project['section_order'] ?? null) ? array_values(array_map('strval', $project['section_order'])) : [];

        foreach (($schema['sections'] ?? []) as $section) {
            $key = (string) $section['key'];
            if (! in_array($key, $order, true)) {
                $order[] = $key;
            }
        }

        return $order;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSchema(array $schema): array
    {
        $sections = [];

        foreach (($schema['sections'] ?? []) as $section) {
            if (! is_array($section)) {
                continue;
            }

            $key = Str::slug((string) ($section['key'] ?? ''), '-');
            if ($key === '') {
                continue;
            }

            $sections[] = [
                'key' => $key,
                'label' => (string) ($section['label'] ?? Str::headline($key)),
                'locked_layout' => (bool) ($section['locked_layout'] ?? true),
                'visible' => (bool) ($section['visible'] ?? true),
                'allow_hide' => (bool) ($section['allow_hide'] ?? true),
                'allow_reorder' => (bool) ($section['allow_reorder'] ?? false),
                'allow_duplicate' => (bool) ($section['allow_duplicate'] ?? false),
                'fields' => $this->normalizeFields(is_array($section['fields'] ?? null) ? $section['fields'] : []),
            ];
        }

        return [
            'template_key' => (string) ($schema['template_key'] ?? ''),
            'template_name' => (string) ($schema['template_name'] ?? 'Editable template'),
            'sections' => $sections,
        ];
    }

    /**
     * @param array<int, mixed> $fields
     * @return array<int, array<string, mixed>>
     */
    private function normalizeFields(array $fields): array
    {
        $normalized = [];
        $allowed = ['text', 'textarea', 'rich_text_basic', 'image', 'url', 'button', 'repeater', 'toggle', 'select'];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $key = Str::slug((string) ($field['key'] ?? ''), '_');
            if ($key === '') {
                continue;
            }

            $type = in_array(($field['type'] ?? 'text'), $allowed, true) ? (string) $field['type'] : 'text';
            $normalized[] = [
                'key' => $key,
                'label' => (string) ($field['label'] ?? Str::headline($key)),
                'type' => $type,
                'selector' => $field['selector'] ?? null,
                'maps_to' => $field['maps_to'] ?? null,
                'default' => $field['default'] ?? $this->defaultForType($type),
                'required' => (bool) ($field['required'] ?? false),
                'validation' => is_array($field['validation'] ?? null) ? $field['validation'] : [],
                'help_text' => (string) ($field['help_text'] ?? ''),
                'options' => is_array($field['options'] ?? null) ? $field['options'] : [],
                'fields' => $type === 'repeater'
                    ? $this->normalizeFields(is_array($field['fields'] ?? null) ? $field['fields'] : [])
                    : [],
            ];
        }

        return $normalized;
    }

    private function defaultForType(string $type): mixed
    {
        return match ($type) {
            'button' => ['text' => '', 'url' => ''],
            'image' => ['src' => '', 'alt' => ''],
            'repeater' => [],
            'toggle' => true,
            default => '',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function schemaFromMarkup(string $html): array
    {
        if (! str_contains($html, 'data-pb-section')) {
            return [];
        }

        $document = $this->document($html);
        $xpath = new DOMXPath($document);
        $sections = [];

        foreach ($xpath->query('//*[@data-pb-section]') ?: [] as $sectionNode) {
            if (! $sectionNode instanceof DOMElement) {
                continue;
            }

            $sectionKey = Str::slug($sectionNode->getAttribute('data-pb-section'), '-');
            if ($sectionKey === '') {
                continue;
            }

            $fields = [];
            foreach ($xpath->query('.//*[@data-pb-field]', $sectionNode) ?: [] as $fieldNode) {
                if (! $fieldNode instanceof DOMElement) {
                    continue;
                }

                $fieldKey = Str::slug($fieldNode->getAttribute('data-pb-field'), '_');
                if ($fieldKey === '') {
                    continue;
                }

                $fields[$fieldKey] = [
                    'key' => $fieldKey,
                    'label' => Str::headline($fieldKey),
                    'type' => $this->fieldTypeFromNode($fieldNode, $fieldKey),
                    'default' => $this->defaultFromNode($fieldNode),
                    'required' => false,
                    'validation' => [],
                    'help_text' => '',
                ];
            }

            $sections[] = [
                'key' => $sectionKey,
                'label' => Str::headline($sectionKey),
                'locked_layout' => $sectionNode->getAttribute('data-pb-locked') !== 'false',
                'visible' => true,
                'allow_hide' => true,
                'allow_reorder' => false,
                'allow_duplicate' => false,
                'fields' => array_values($fields),
            ];
        }

        if ($sections === []) {
            return [];
        }

        return [
            'template_key' => '',
            'template_name' => 'Detected editable template',
            'sections' => $sections,
        ];
    }

    private function fieldTypeFromNode(DOMElement $node, string $fieldKey): string
    {
        $tag = strtolower($node->tagName);

        if ($tag === 'img' || str_contains($fieldKey, 'image')) {
            return 'image';
        }

        if ($tag === 'a' || $tag === 'button' || str_contains($fieldKey, 'button')) {
            return 'button';
        }

        if (str_contains($fieldKey, 'url') || str_contains($fieldKey, 'link')) {
            return 'url';
        }

        return in_array($tag, ['p', 'div', 'article'], true) ? 'textarea' : 'text';
    }

    private function defaultFromNode(DOMElement $node): mixed
    {
        $tag = strtolower($node->tagName);

        if ($tag === 'img') {
            return [
                'src' => $node->getAttribute('src'),
                'alt' => $node->getAttribute('alt'),
            ];
        }

        if ($tag === 'a' || $tag === 'button') {
            return [
                'text' => trim($node->textContent),
                'url' => $node->getAttribute('href'),
            ];
        }

        return trim($node->textContent);
    }

    /**
     * @param array<string, mixed> $section
     * @param array<string, mixed> $sectionData
     */
    private function applySectionFields(DOMDocument $document, DOMXPath $xpath, DOMElement $sectionNode, array $section, array $sectionData): void
    {
        foreach (($section['fields'] ?? []) as $field) {
            $key = (string) $field['key'];
            $value = $sectionData[$key] ?? ($field['default'] ?? null);
            $selector = is_string($field['selector'] ?? null) && $field['selector'] !== ''
                ? (string) $field['selector']
                : './/*[@data-pb-field="'.$key.'"]';

            if (($field['type'] ?? null) === 'repeater') {
                $this->applyRepeater($document, $xpath, $sectionNode, $field, is_array($value) ? $value : []);
                continue;
            }

            foreach ($xpath->query($selector, $sectionNode) ?: [] as $node) {
                if ($node instanceof DOMElement) {
                    $this->applyFieldValue($document, $node, $field, $value);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $field
     */
    private function applyFieldValue(DOMDocument $document, DOMElement $node, array $field, mixed $value): void
    {
        $type = (string) ($field['type'] ?? 'text');

        if ($type === 'image') {
            $image = is_array($value) ? $value : ['src' => (string) $value, 'alt' => ''];
            $node->setAttribute('src', (string) ($image['src'] ?? ''));
            $node->setAttribute('alt', (string) ($image['alt'] ?? ''));

            return;
        }

        if ($type === 'button') {
            $button = is_array($value) ? $value : ['text' => (string) $value, 'url' => ''];
            $this->replaceChildrenWithText($document, $node, (string) ($button['text'] ?? ''));

            if (strtolower($node->tagName) === 'a') {
                $node->setAttribute('href', (string) ($button['url'] ?? ''));
            }

            return;
        }

        if ($type === 'url') {
            if (strtolower($node->tagName) === 'a') {
                $node->setAttribute('href', (string) $value);
            } else {
                $this->replaceChildrenWithText($document, $node, (string) $value);
            }

            return;
        }

        $this->replaceChildrenWithText($document, $node, (string) $value);
    }

    /**
     * @param array<string, mixed> $field
     * @param array<int, mixed> $items
     */
    private function applyRepeater(DOMDocument $document, DOMXPath $xpath, DOMElement $sectionNode, array $field, array $items): void
    {
        $key = (string) $field['key'];
        $containers = $xpath->query('.//*[@data-pb-repeatable="'.$key.'"]', $sectionNode);
        $container = $containers && $containers->length > 0 ? $containers->item(0) : null;

        if (! $container instanceof DOMElement) {
            return;
        }

        $prototype = null;
        foreach ($container->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $prototype = $child->cloneNode(true);
                break;
            }
        }

        while ($container->firstChild) {
            $container->removeChild($container->firstChild);
        }

        if (! $prototype instanceof DOMElement) {
            return;
        }

        foreach ($items as $item) {
            $item = is_array($item) ? $item : [];
            $node = $prototype->cloneNode(true);
            if (! $node instanceof DOMElement) {
                continue;
            }

            foreach (($field['fields'] ?? []) as $childField) {
                $childKey = (string) $childField['key'];
                foreach ((new DOMXPath($document))->query('.//*[@data-pb-field="'.$childKey.'"]', $node) ?: [] as $fieldNode) {
                    if ($fieldNode instanceof DOMElement) {
                        $this->applyFieldValue($document, $fieldNode, $childField, $item[$childKey] ?? ($childField['default'] ?? null));
                    }
                }
            }

            $container->appendChild($node);
        }
    }

    /**
     * @param array<int, string> $order
     */
    private function applySectionOrder(DOMDocument $document, DOMXPath $xpath, array $order): void
    {
        if ($order === []) {
            return;
        }

        $nodesByKey = [];
        foreach ($xpath->query('//*[@data-pb-section]') ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $nodesByKey[$node->getAttribute('data-pb-section')] = $node;
            }
        }

        foreach ($order as $key) {
            $node = $nodesByKey[$key] ?? null;
            if (! $node instanceof DOMElement || ! $node->parentNode) {
                continue;
            }

            $node->parentNode->appendChild($node);
        }
    }

    /**
     * @return array<int, DOMElement>
     */
    private function sectionNodes(DOMXPath $xpath, string $sectionKey): array
    {
        $nodes = [];
        foreach ($xpath->query('//*[@data-pb-section="'.$sectionKey.'"]') ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    private function document(string $html): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><!DOCTYPE html><html><body>'.$html.'</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    private function fragmentHtml(DOMDocument $document): string
    {
        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body) {
            return $document->saveHTML() ?: '';
        }

        $html = '';
        foreach ($body->childNodes as $child) {
            $html .= $document->saveHTML($child);
        }

        return str_replace('<?xml encoding="UTF-8">', '', $html);
    }

    private function replaceChildrenWithText(DOMDocument $document, DOMElement $node, string $text): void
    {
        while ($node->firstChild) {
            $node->removeChild($node->firstChild);
        }

        $node->appendChild($document->createTextNode($text));
    }

    private function isSafeUrl(string $url): bool
    {
        return $url === ''
            || str_starts_with($url, '/')
            || str_starts_with($url, '#')
            || (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    private function isSafeImageUrl(string $url): bool
    {
        return $this->isSafeUrl($url) && ! preg_match('/^\s*(javascript|vbscript):/i', $url);
    }
}
