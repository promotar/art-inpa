<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('platform_pages')
            || ! Schema::hasTable('platform_theme_builder_templates')
            || ! Schema::hasTable('page_builder_theme_settings')
        ) {
            return;
        }

        $typeMap = [
            'header' => 'header',
            'footer' => 'footer',
            'single_page' => 'page',
        ];
        $selected = [
            'header_page_id' => null,
            'body_page_id' => null,
            'footer_page_id' => null,
        ];

        DB::transaction(function () use ($typeMap, &$selected): void {
            foreach (DB::table('platform_theme_builder_templates')->orderBy('id')->get() as $template) {
                $pageType = $typeMap[(string) $template->template_type] ?? null;

                if ($pageType === null) {
                    continue;
                }

                $marker = 'legacy-theme-template:'.$template->id;
                $existing = DB::table('platform_pages')
                    ->where('category', $marker)
                    ->value('id');
                $pageId = $existing ? (int) $existing : $this->importTemplate($template, $pageType, $marker);

                if ((string) $template->status !== 'published') {
                    continue;
                }

                $setting = match ((string) $template->template_type) {
                    'header' => 'header_page_id',
                    'footer' => 'footer_page_id',
                    'single_page' => 'body_page_id',
                    default => null,
                };

                if ($setting !== null && $selected[$setting] === null) {
                    $selected[$setting] = $pageId;
                }
            }

            $now = now();
            DB::table('page_builder_theme_settings')->updateOrInsert(
                ['id' => 1],
                $selected + ['created_at' => $now, 'updated_at' => $now],
            );
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_pages')) {
            DB::table('platform_pages')
                ->where('category', 'like', 'legacy-theme-template:%')
                ->delete();
        }

        if (Schema::hasTable('page_builder_theme_settings')) {
            DB::table('page_builder_theme_settings')->where('id', 1)->delete();
        }
    }

    private function importTemplate(object $template, string $pageType, string $marker): int
    {
        $html = (string) ($template->html ?? '');
        $css = (string) ($template->css ?? '');
        $project = $this->syncedProject($template->page_builder_json ?? null, $html, $css);
        $slug = $this->uniqueSlug('theme-'.($template->slug ?: Str::slug((string) $template->name)));
        $now = now();

        return DB::table('platform_pages')->insertGetId([
            'title' => (string) $template->name,
            'slug' => $slug,
            'content_type' => $pageType,
            'block_key' => null,
            'parent_id' => null,
            'category' => $marker,
            'menu_label' => null,
            'show_in_menu' => false,
            'content' => $html,
            'page_builder_json' => $project,
            'html' => $html,
            'css' => $css,
            'status' => (string) $template->status === 'published' ? 'published' : 'draft',
            'sort_order' => 0,
            'seo_title' => null,
            'meta_description' => $template->description ?? null,
            'published_at' => (string) $template->status === 'published' ? $now : null,
            'created_at' => $template->created_at ?? $now,
            'updated_at' => $template->updated_at ?? $now,
        ]);
    }

    private function syncedProject(mixed $projectJson, string $html, string $css): ?string
    {
        $project = is_string($projectJson) ? json_decode($projectJson, true) : null;

        if (! is_array($project)) {
            return null;
        }

        $project['_z4rank_builder_sync'] = [
            'content_hash' => hash('sha256', json_encode([
                'html' => $html,
                'css' => $css,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            'synced_at' => now()->toIso8601String(),
        ];

        return json_encode($project, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function uniqueSlug(string $value): string
    {
        $base = Str::slug($value) ?: 'theme-design';
        $slug = $base;
        $index = 2;

        while (DB::table('platform_pages')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$index;
            $index++;
        }

        return $slug;
    }
};
