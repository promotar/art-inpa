<?php

namespace App\Platform\Core\PageBuilder;

use App\Models\User;
use App\Platform\Core\Rendering\PlatformContentRenderer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PageBuilderDynamicSourceRegistry
{
    public function __construct(
        private readonly PlatformContentRenderer $renderer,
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function editorSources(?User $user = null, ?object $page = null): array
    {
        return [
            'menus' => $this->renderer->menuTraitOptions(),
            'menuPreviewItems' => $this->renderer->menuPreviewItems($user),
            'defaultMenuKey' => $this->renderer->defaultMenuKey(),
            'siteLogo' => $this->renderer->siteLogo(),
            'siteTitle' => $this->renderer->siteTitle(),
            'pages' => $this->pages(),
            'blocks' => $this->blocks(),
            'currentPage' => $page ? $this->currentPage($page) : null,
            'fields' => $this->fields(),
        ];
    }

    /**
     * @return array<int, array{value: string, name: string}>
     */
    private function pages(): array
    {
        if (! Schema::hasTable('platform_pages')) {
            return [];
        }

        return DB::table('platform_pages')
            ->where('content_type', 'page')
            ->orderBy('title')
            ->get(['slug', 'title', 'status'])
            ->map(fn (object $page): array => [
                'value' => (string) $page->slug,
                'name' => (string) $page->title.($page->status === 'published' ? '' : ' (Draft)'),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, name: string}>
     */
    private function blocks(): array
    {
        if (! Schema::hasTable('platform_pages')) {
            return [];
        }

        return DB::table('platform_pages')
            ->where('content_type', 'block')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['block_key', 'title', 'status'])
            ->map(fn (object $block): array => [
                'value' => (string) ($block->block_key ?: $block->title),
                'name' => (string) $block->title.($block->status === 'published' ? '' : ' (Draft)'),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string|null>
     */
    private function currentPage(object $page): array
    {
        return [
            'title' => (string) ($page->title ?? ''),
            'slug' => (string) ($page->slug ?? ''),
            'content_type' => (string) ($page->content_type ?? 'page'),
            'seo_title' => $page->seo_title ?? null,
            'meta_description' => $page->meta_description ?? null,
            'status' => (string) ($page->status ?? 'draft'),
        ];
    }

    /**
     * @return array<int, array{value: string, name: string}>
     */
    private function fields(): array
    {
        return [
            ['value' => 'title', 'name' => 'Current Page Title'],
            ['value' => 'slug', 'name' => 'Current Page Slug'],
            ['value' => 'seo_title', 'name' => 'SEO Title'],
            ['value' => 'meta_description', 'name' => 'Meta Description'],
            ['value' => 'site_logo', 'name' => 'Site Logo'],
            ['value' => 'site_title', 'name' => 'Site Title'],
        ];
    }
}
