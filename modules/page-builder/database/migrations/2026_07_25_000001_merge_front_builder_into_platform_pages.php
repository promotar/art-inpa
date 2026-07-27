<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addUnifiedPageColumns();
        $this->mergePlatformMetadata();

        if (! Schema::hasTable('front_builder_pages')) {
            return;
        }

        $idMap = [];

        DB::transaction(function () use (&$idMap): void {
            foreach (DB::table('front_builder_pages')->orderBy('id')->get() as $legacyPage) {
                $slug = $this->uniqueSlug((string) $legacyPage->slug);
                $now = now();

                $idMap[(int) $legacyPage->id] = DB::table('platform_pages')->insertGetId([
                    'title' => $legacyPage->title,
                    'slug' => $slug,
                    'content_type' => 'page',
                    'block_key' => null,
                    'content' => $legacyPage->html,
                    'page_builder_json' => $this->legacyProject($legacyPage),
                    'html' => $legacyPage->html,
                    'css' => $legacyPage->css,
                    'status' => $legacyPage->status,
                    'sort_order' => (int) $legacyPage->sort_order,
                    'seo_title' => null,
                    'meta_description' => null,
                    'parent_id' => null,
                    'category' => $legacyPage->category,
                    'menu_label' => $legacyPage->menu_label,
                    'show_in_menu' => (bool) $legacyPage->show_in_menu,
                    'published_at' => $legacyPage->published_at,
                    'created_at' => $legacyPage->created_at ?? $now,
                    'updated_at' => $legacyPage->updated_at ?? $now,
                ]);
            }

            foreach (DB::table('front_builder_pages')->whereNotNull('parent_id')->get(['id', 'parent_id']) as $legacyPage) {
                $pageId = $idMap[(int) $legacyPage->id] ?? null;
                $parentId = $idMap[(int) $legacyPage->parent_id] ?? null;

                if ($pageId !== null && $parentId !== null && $pageId !== $parentId) {
                    DB::table('platform_pages')->where('id', $pageId)->update(['parent_id' => $parentId]);
                }
            }
        });

        Schema::dropIfExists('front_builder_pages');
    }

    public function down(): void
    {
        if (! Schema::hasTable('platform_pages')) {
            return;
        }

        Schema::table('platform_pages', function (Blueprint $table): void {
            foreach (['parent_id', 'category', 'menu_label', 'show_in_menu'] as $column) {
                if (Schema::hasColumn('platform_pages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function addUnifiedPageColumns(): void
    {
        if (! Schema::hasTable('platform_pages')) {
            return;
        }

        Schema::table('platform_pages', function (Blueprint $table): void {
            if (! Schema::hasColumn('platform_pages', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->index()->after('id');
            }

            if (! Schema::hasColumn('platform_pages', 'category')) {
                $table->string('category', 120)->nullable()->index()->after('block_key');
            }

            if (! Schema::hasColumn('platform_pages', 'menu_label')) {
                $table->string('menu_label')->nullable()->after('category');
            }

            if (! Schema::hasColumn('platform_pages', 'show_in_menu')) {
                $table->boolean('show_in_menu')->default(false)->index()->after('menu_label');
            }
        });
    }

    private function uniqueSlug(string $value): string
    {
        $base = trim($value) !== '' ? trim($value) : 'page';
        $slug = $base;
        $counter = 2;

        while (DB::table('platform_pages')->where('slug', $slug)->exists()) {
            $slug = $base.'-front-builder'.($counter > 2 ? '-'.$counter : '');
            $counter++;
        }

        return $slug;
    }

    private function legacyProject(object $page): ?string
    {
        $components = json_decode((string) ($page->components_json ?? ''), true);
        $styles = json_decode((string) ($page->styles_json ?? ''), true);

        if (! is_array($components) && ! is_array($styles)) {
            return null;
        }

        return json_encode([
            'components' => is_array($components) ? $components : [],
            'styles' => is_array($styles) ? $styles : [],
            'meta' => ['migrated_from' => 'front-builder'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function mergePlatformMetadata(): void
    {
        if (Schema::hasTable('platform_settings')) {
            DB::table('platform_settings')
                ->where('value', 'like', 'front-builder:%')
                ->update([
                    'value' => DB::raw("REPLACE(value, 'front-builder:', 'platform-page:')"),
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('menu_items')) {
            DB::table('menu_items')
                ->where('route_name', 'admin.front-builder.pages.index')
                ->update([
                    'title' => 'Page Builder',
                    'label' => 'Page Builder',
                    'route_name' => 'admin.pages.index',
                    'permission' => 'pages.manage',
                    'updated_at' => now(),
                ]);
        }

        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $legacyPermission = DB::table('permissions')->where('name', 'front-builder.manage')->value('id');
        $pagePermission = DB::table('permissions')->where('name', 'pages.manage')->value('id');

        if (! $legacyPermission || ! $pagePermission) {
            return;
        }

        foreach (DB::table('role_has_permissions')->where('permission_id', $legacyPermission)->get() as $assignment) {
            DB::table('role_has_permissions')->updateOrInsert([
                'permission_id' => $pagePermission,
                'role_id' => $assignment->role_id,
            ]);
        }

        if (Schema::hasTable('model_has_permissions')) {
            foreach (DB::table('model_has_permissions')->where('permission_id', $legacyPermission)->get() as $assignment) {
                DB::table('model_has_permissions')->updateOrInsert([
                    'permission_id' => $pagePermission,
                    'model_type' => $assignment->model_type,
                    'model_id' => $assignment->model_id,
                ]);
            }
        }
    }
};
