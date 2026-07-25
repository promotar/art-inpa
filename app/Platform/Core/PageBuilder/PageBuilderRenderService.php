<?php

namespace App\Platform\Core\PageBuilder;

use App\Platform\Core\Rendering\PlatformContentRenderer;
use App\Platform\Core\Services\PluginRuntimeGate;
use App\Platform\Core\Services\SettingsRepository;
use App\Platform\Core\ThemeBuilder\ThemeBuilderTemplateResolver;
use App\Platform\Core\Contracts\LatestContentProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PageBuilderRenderService
{
    public function __construct(
        private readonly PlatformContentRenderer $contentRenderer,
        private readonly SettingsRepository $settings,
        private readonly TemplateEditableRenderer $templateEditableRenderer,
        private readonly ?ThemeBuilderTemplateResolver $themeBuilderTemplates = null,
        private readonly ?PluginRuntimeGate $pluginRuntime = null,
        private readonly ?LatestContentProvider $latestContent = null,
    ) {
        //
    }

    /**
     * @return Collection<int, object>
     */
    public function publishedLayoutSections(string $type, ?object $context = null): Collection
    {
        if (in_array($type, ['header', 'footer'], true)) {
            $themeBuilderSections = $this->themeBuilderTemplates()
                ->matchingLayoutSections($type, $context)
                ->map(function (object $section) use ($type, $context): object {
                    $section->content_type = $type;
                    $section->rendered_html = $this->renderHtml((string) ($section->html ?: $section->content), $context);

                    return $section;
                });

            if ($themeBuilderSections->isNotEmpty()) {
                return $themeBuilderSections;
            }
        }

        if (
            ! in_array($type, ['header', 'footer'], true)
            || ! Schema::hasTable('platform_pages')
            || ! Schema::hasColumn('platform_pages', 'content_type')
        ) {
            return collect();
        }

        return DB::table('platform_pages')
            ->where('content_type', $type)
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'html', 'content', 'css'])
            ->map(function (object $section) use ($type, $context): object {
                $section->content_type = $type;
                $section->rendered_html = $this->renderHtml((string) ($section->html ?: $section->content), $context);

                return $section;
            });
    }

    public function layoutCss(?object $context = null): string
    {
        $themeBuilderCss = $this->themeBuilderTemplates()->matchingCss(
            collect(['header', $this->mainTemplateType($context), 'footer'])
                ->filter()
                ->values()
                ->all(),
            $context,
        );

        if (
            ! Schema::hasTable('platform_pages')
            || ! Schema::hasColumn('platform_pages', 'content_type')
        ) {
            return trim(collect([$this->contentRenderer->themeModeCss(), $themeBuilderCss])
                ->filter(fn (string $css): bool => trim($css) !== '')
                ->implode("\n"));
        }

        $legacyCss = trim(DB::table('platform_pages')
            ->whereIn('content_type', ['header', 'footer'])
            ->where('status', 'published')
            ->orderByRaw("CASE content_type WHEN 'header' THEN 1 WHEN 'footer' THEN 2 ELSE 9 END")
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('css')
            ->filter()
            ->implode("\n"));

        return trim(collect([$this->contentRenderer->themeModeCss(), $themeBuilderCss, $legacyCss])
            ->filter(fn (string $css): bool => trim($css) !== '')
            ->implode("\n"));
    }

    public function renderHtml(?string $html, ?object $context = null): string
    {
        $html = $this->contentRenderer->renderHtml($html);

        if ($html === '') {
            return '';
        }

        $html = $this->removeInactivePluginWidgets($html);
        $html = $this->renderDynamicBlogCollections($html);
        $html = $this->renderDynamicFields($html, $context);
        $html = $this->renderDynamicImages($html);
        $html = $this->renderBreadcrumbs($html, $context);

        return $html;
    }

    /**
     * @return array<string, mixed>
     */
    public function pageViewData(object $page, bool $isPreview = false): array
    {
        $values = $this->values();
        $siteTitle = $values['general.site_title'] ?? config('app.name', 'Laravel');
        $siteTitle = is_string($siteTitle) && trim($siteTitle) !== '' ? $siteTitle : config('app.name', 'Laravel');

        $baseHtml = (string) ($page->html ?: $page->content);
        $project = $this->templateEditableRenderer->projectData($page->page_builder_json ?? null);
        $editableHtml = $this->templateEditableRenderer->render($baseHtml, $project);

        return [
            'platformSettings' => $values,
            'dynamicHeaders' => $this->publishedLayoutSections('header', $page),
            'dynamicFooters' => $this->publishedLayoutSections('footer', $page),
            'dynamicLayoutCss' => $this->layoutCss($page),
            'pageHtml' => $this->renderPageHtml($editableHtml, $page),
            'siteTitle' => $siteTitle,
            'siteIcon' => $values['general.site_icon'] ?? null,
            'title' => $page->seo_title ?: $page->title,
            'description' => $page->meta_description ?? '',
            'isPreview' => $isPreview,
        ];
    }

    private function renderDynamicFields(string $html, ?object $context): string
    {
        return preg_replace_callback(
            '~<(?P<tag>[a-z][a-z0-9:-]*)(?P<attrs>[^>]*)\sdata-dynamic-field=(["\'])(?P<field>.*?)\3(?P<attrs2>[^>]*)>(?P<body>.*?)</\1>~is',
            function (array $matches) use ($context): string {
                $field = trim((string) ($matches['field'] ?? ''));
                $value = $this->dynamicValue($field, $context);
                $attrs = trim((string) (($matches['attrs'] ?? '').' data-dynamic-field="'.$field.'" '.($matches['attrs2'] ?? '')));

                return '<'.$matches['tag'].' '.$attrs.'>'.e($value).'</'.$matches['tag'].'>';
            },
            $html,
        ) ?? $html;
    }

    private function renderDynamicImages(string $html): string
    {
        return preg_replace_callback(
            '~<img(?P<attrs>[^>]*)\sdata-dynamic-field=(["\'])(?P<field>site_logo)\2(?P<attrs2>[^>]*)>~is',
            function (array $matches): string {
                $logo = (string) ($this->values()['general.site_logo'] ?? '');
                $attrs = (string) (($matches['attrs'] ?? '').' '.($matches['attrs2'] ?? ''));
                $attrs = preg_replace('/\ssrc\s*=\s*(["\']).*?\1/is', '', $attrs) ?? $attrs;

                return '<img src="'.e($logo).'" data-dynamic-field="site_logo"'.$attrs.'>';
            },
            $html,
        ) ?? $html;
    }

    private function renderBreadcrumbs(string $html, ?object $context): string
    {
        return preg_replace_callback(
            '~<(?P<tag>[a-z][a-z0-9:-]*)(?P<attrs>[^>]*)\sdata-dynamic-breadcrumb(?P<attrs2>[^>]*)>(?P<body>.*?)</\1>~is',
            function (array $matches) use ($context): string {
                $title = $context ? (string) ($context->title ?? '') : '';
                $attrs = trim((string) (($matches['attrs'] ?? '').' data-dynamic-breadcrumb '.($matches['attrs2'] ?? '')));

                return '<'.$matches['tag'].' '.$attrs.'><a href="'.e(url('/')).'">Home</a> / <span>'.e($title).'</span></'.$matches['tag'].'>';
            },
            $html,
        ) ?? $html;
    }

    private function removeInactivePluginWidgets(string $html): string
    {
        if (! str_contains($html, 'data-pb-widget') && ! str_contains($html, 'data-pb-type')) {
            return $html;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $rootId = 'plugin-widget-root-'.bin2hex(random_bytes(6));
        $loaded = $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div id="'.$rootId.'">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        if (! $loaded) {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);

            return $html;
        }

        $xpath = new \DOMXPath($dom);
        $root = $xpath->query('//*[@id="'.$rootId.'"]')->item(0);
        $nodeList = $xpath->query('//*[@data-pb-widget or @data-pb-type]');
        if (! $root instanceof \DOMElement || $nodeList === false) {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);

            return $html;
        }

        $nodes = [];
        foreach ($nodeList as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }

            $identifiers = array_filter([
                trim($node->getAttribute('data-pb-widget')),
                trim($node->getAttribute('data-pb-type')),
            ]);
            foreach ($identifiers as $identifier) {
                if (preg_match('/^(?<plugin>[a-z0-9][a-z0-9-]*)\./i', $identifier, $matches) !== 1) {
                    continue;
                }

                if ($this->pluginWidgetIsInactive(strtolower((string) $matches['plugin']))) {
                    $nodes[spl_object_id($node)] = $node;
                    break;
                }
            }
        }

        foreach (array_reverse($nodes) as $node) {
            $node->parentNode?->removeChild($node);
        }

        $rendered = '';
        foreach ($root->childNodes as $child) {
            $rendered .= (string) $dom->saveHTML($child);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        return $rendered;
    }
    private function renderDynamicBlogCollections(string $html): string
    {
        if (! $this->contentProvider()->available()) {
            return preg_replace(
                '~<(?P<tag>[a-z][a-z0-9:-]*)(?P<attrs>[^>]*)\\sdata-platform-blog-archive=(["\x27]).*?\\3(?P<attrs2>[^>]*)>.*?</\\1>~is',
                '',
                $html,
            ) ?? $html;
        }

        return preg_replace_callback(
            '~<(?P<tag>[a-z][a-z0-9:-]*)(?P<attrs>[^>]*)\sdata-platform-blog-archive=(["\'])(?P<source>.*?)\3(?P<attrs2>[^>]*)>(?P<body>.*?)</\1>~is',
            function (array $matches): string {
                $attributes = (string) (($matches['attrs'] ?? '').' '.($matches['attrs2'] ?? ''));
                $limit = $this->attributeInt($attributes, 'data-platform-blog-limit', 24);
                $search = trim((string) request('search', request('q', '')));

                return $this->contentProvider()->renderArchive($limit, $search);
            },
            $html,
        ) ?? $html;
    }

    private function contentProvider(): LatestContentProvider
    {
        return $this->latestContent ?? app(LatestContentProvider::class);
    }

    private function pluginIsActive(string $slug): bool
    {
        try {
            return ($this->pluginRuntime ?? app(PluginRuntimeGate::class))->allows($slug);
        } catch (\Throwable) {
            return false;
        }
    }

    private function pluginWidgetIsInactive(string $slug): bool
    {
        try {
            $inspection = ($this->pluginRuntime ?? app(PluginRuntimeGate::class))->inspect($slug);
            $reason = (string) ($inspection['reason'] ?? '');

            return ! ($inspection['allowed'] ?? false)
                && (
                    $reason === 'plugin_disabled'
                    || $reason === 'plugin_runtime_disabled'
                    || $reason === 'plugin_module_missing'
                    || str_starts_with($reason, 'plugin_dependency_disabled:')
                );
        } catch (\Throwable) {
            return false;
        }
    }

    private function attributeInt(string $attributes, string $name, int $default): int
    {
        if (! preg_match('~\s'.preg_quote($name, '~').'\s*=\s*(["\'])(?P<value>\d+)\1~i', $attributes, $matches)) {
            return $default;
        }

        return max(1, (int) $matches['value']);
    }

    private function renderPageHtml(string $html, object $page): string
    {
        $renderedPageHtml = $this->renderHtml($html, $page);
        $templateType = $this->mainTemplateType($page);

        if ($templateType === null) {
            return $renderedPageHtml;
        }

        $template = $this->themeBuilderTemplates()->firstMatchingTemplate($templateType, $page);

        if ($template === null || trim((string) ($template->html ?? '')) === '') {
            return $renderedPageHtml;
        }

        $templateHtml = (string) $template->html;
        $withContent = $this->injectPageContent($templateHtml, $renderedPageHtml);

        if ($withContent === null) {
            return $renderedPageHtml;
        }

        return $this->renderHtml($withContent, $page);
    }

    private function mainTemplateType(?object $context): ?string
    {
        if ($context === null) {
            return null;
        }

        $type = (string) ($context->content_type ?? $context->type ?? '');

        return match ($type) {
            'page' => 'single_page',
            'post', 'blog_post' => 'single_post',
            default => null,
        };
    }

    private function injectPageContent(string $templateHtml, string $pageHtml): ?string
    {
        if (str_contains($templateHtml, '{{ page_content }}')) {
            return str_replace('{{ page_content }}', $pageHtml, $templateHtml);
        }

        $replaced = preg_replace_callback(
            '~<(?P<tag>[a-z][a-z0-9:-]*)(?P<attrs>[^>]*)\sdata-dynamic-field=(["\'])content\3(?P<attrs2>[^>]*)>(?P<body>.*?)</\1>~is',
            function (array $matches) use ($pageHtml): string {
                $attrs = trim((string) (($matches['attrs'] ?? '').' data-dynamic-field="content" '.($matches['attrs2'] ?? '')));

                return '<'.$matches['tag'].' '.$attrs.'>'.$pageHtml.'</'.$matches['tag'].'>';
            },
            $templateHtml,
            1,
            $count,
        );

        if ($count < 1) {
            return null;
        }

        return $replaced ?? $templateHtml;
    }

    private function themeBuilderTemplates(): ThemeBuilderTemplateResolver
    {
        return $this->themeBuilderTemplates ?? app(ThemeBuilderTemplateResolver::class);
    }

    private function dynamicValue(string $field, ?object $context): string
    {
        $values = $this->values();

        return match ($field) {
            'title' => $context ? (string) ($context->title ?? '') : '',
            'slug' => $context ? (string) ($context->slug ?? '') : '',
            'seo_title' => $context ? (string) ($context->seo_title ?? $context->title ?? '') : '',
            'meta_description' => $context ? (string) ($context->meta_description ?? '') : '',
            'site_title' => (string) ($values['general.site_title'] ?? config('app.name', 'Laravel')),
            default => '',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function values(): array
    {
        try {
            return $this->settings->values();
        } catch (\Throwable) {
            return [];
        }
    }
}
