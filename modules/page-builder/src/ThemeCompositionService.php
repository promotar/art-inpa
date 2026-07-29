<?php

namespace Modules\PageBuilder;

use App\Platform\Core\PageBuilder\PageBuilderRenderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ThemeCompositionService
{
    public function __construct(private readonly PageBuilderRenderService $renderer) {}

    /**
     * @return array<string, mixed>
     */
    public function pageViewData(object $page, bool $isPreview = false): array
    {
        $data = $this->renderer->pageViewData($page, $isPreview);
        $selection = $this->selection();

        if ($selection === null) {
            return $data;
        }

        $header = $this->selectedDesign($selection->header_page_id ?? null, 'header');
        $body = $this->selectedDesign($selection->body_page_id ?? null, 'page');
        $footer = $this->selectedDesign($selection->footer_page_id ?? null, 'footer');
        $pageHtml = $this->renderer->renderHtml((string) ($page->html ?: $page->content), $page);

        $data['dynamicHeaders'] = $header ? collect([$this->renderedSection($header, 'header', $page)]) : collect();
        $data['dynamicFooters'] = $footer ? collect([$this->renderedSection($footer, 'footer', $page)]) : collect();
        $data['pageHtml'] = $this->renderBody($body, $pageHtml, $page);
        $data['dynamicLayoutCss'] = collect([
            $header->css ?? null,
            $body->css ?? null,
            $footer->css ?? null,
        ])->filter(fn (mixed $css): bool => is_string($css) && trim($css) !== '')
            ->implode("\n");

        return $data;
    }

    private function selection(): ?object
    {
        if (! Schema::hasTable('page_builder_theme_settings')) {
            return null;
        }

        return DB::table('page_builder_theme_settings')->where('id', 1)->first();
    }

    private function selectedDesign(mixed $id, string $type): ?object
    {
        if (! is_numeric($id)) {
            return null;
        }

        return DB::table('platform_pages')
            ->where('id', (int) $id)
            ->where('content_type', $type)
            ->where('status', 'published')
            ->first();
    }

    private function renderedSection(object $design, string $type, object $context): object
    {
        $section = clone $design;
        $section->content_type = $type;
        $section->rendered_html = $this->renderer->renderHtml(
            (string) ($design->html ?: $design->content),
            $context,
        );

        return $section;
    }

    private function renderBody(?object $body, string $pageHtml, object $context): string
    {
        if ($body === null) {
            return $pageHtml;
        }

        $bodyHtml = (string) ($body->html ?: $body->content);

        if ((int) $body->id === (int) $context->id) {
            return $this->renderer->renderHtml($bodyHtml, $context);
        }

        if (str_contains($bodyHtml, '{{ page_content }}')) {
            return $this->renderer->renderHtml(
                str_replace('{{ page_content }}', $pageHtml, $bodyHtml),
                $context,
            );
        }

        $withContent = preg_replace_callback(
            '~<(?P<tag>[a-z][a-z0-9:-]*)(?P<attrs>[^>]*)\sdata-dynamic-field=(["\'])content\3(?P<attrs2>[^>]*)>(?P<body>.*?)</\1>~is',
            function (array $matches) use ($pageHtml): string {
                $attrs = trim((string) (($matches['attrs'] ?? '').' data-dynamic-field="content" '.($matches['attrs2'] ?? '')));

                return '<'.$matches['tag'].' '.$attrs.'>'.$pageHtml.'</'.$matches['tag'].'>';
            },
            $bodyHtml,
            1,
            $count,
        );

        return $this->renderer->renderHtml(
            $count > 0 ? (string) $withContent : $bodyHtml,
            $context,
        );
    }
}
