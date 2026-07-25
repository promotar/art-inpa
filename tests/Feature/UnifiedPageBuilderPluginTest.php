<?php

namespace Tests\Feature;

use App\Platform\Core\Models\Plugin;
use App\Platform\Core\Services\RequiredCorePluginSynchronizer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UnifiedPageBuilderPluginTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_builder_data_migrates_into_the_protected_page_builder_schema(): void
    {
        DB::table('platform_pages')->insert([
            'title' => 'Existing page',
            'slug' => 'legacy-page',
            'content' => null,
            'status' => 'published',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('front_builder_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->nullable();
            $table->string('status')->default('draft');
            $table->string('menu_label')->nullable();
            $table->boolean('show_in_menu')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->longText('html')->nullable();
            $table->longText('css')->nullable();
            $table->longText('components_json')->nullable();
            $table->longText('styles_json')->nullable();
            $table->json('settings_json')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        DB::table('front_builder_pages')->insert([
            'title' => 'Legacy visual page',
            'slug' => 'legacy-page',
            'category' => 'Landing',
            'status' => 'published',
            'menu_label' => 'Legacy',
            'show_in_menu' => true,
            'sort_order' => 12,
            'html' => '<section>Legacy visual content</section>',
            'css' => '.legacy { color: red; }',
            'components_json' => '[{"type":"text","content":"Legacy"}]',
            'styles_json' => '[]',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require base_path(
            'modules/PageBuilder/database/migrations/2026_07_25_000001_merge_front_builder_into_platform_pages.php',
        );
        $migration->up();

        $this->assertFalse(Schema::hasTable('front_builder_pages'));
        $this->assertTrue(Schema::hasColumn('platform_pages', 'parent_id'));
        $this->assertTrue(Schema::hasColumn('platform_pages', 'category'));
        $this->assertTrue(Schema::hasColumn('platform_pages', 'menu_label'));
        $this->assertTrue(Schema::hasColumn('platform_pages', 'show_in_menu'));
        $this->assertDatabaseHas('platform_pages', [
            'title' => 'Legacy visual page',
            'slug' => 'legacy-page-front-builder',
            'category' => 'Landing',
            'menu_label' => 'Legacy',
            'show_in_menu' => true,
            'html' => '<section>Legacy visual content</section>',
        ]);
    }

    public function test_unified_page_builder_manifest_is_core_and_has_no_parallel_page_tables(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(base_path('modules/PageBuilder/module.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $plugin = new Plugin([
            'slug' => $manifest['slug'],
            'manifest' => $manifest,
        ]);

        $this->assertSame('page-builder', $manifest['slug']);
        $this->assertTrue($plugin->isCore());
        $this->assertSame([], $manifest['uninstall']['tables']);
        $this->assertDirectoryDoesNotExist(base_path('modules/front-builder'));
        $this->assertFileDoesNotExist(base_path('modules/front-builder.zip'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Admin/PageController.php'));
        $this->assertFileExists(base_path('modules/PageBuilder/src/Http/Controllers/Admin/PageController.php'));
    }

    public function test_required_page_builder_is_reactivated_when_registry_state_is_tampered_with(): void
    {
        app(RequiredCorePluginSynchronizer::class)->synchronize();

        $plugin = Plugin::query()->where('slug', 'page-builder')->firstOrFail();
        $plugin->update(['status' => Plugin::STATUS_DISABLED]);

        DB::table('platform_plugin_registry_entries')->updateOrInsert(
            ['registry_type' => 'runtime', 'plugin_slug' => 'page-builder'],
            [
                'payload' => json_encode(['runtime_enabled' => false]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        app(RequiredCorePluginSynchronizer::class)->synchronize();

        $this->assertSame(Plugin::STATUS_ACTIVE, $plugin->fresh()->status);
        $this->assertTrue($plugin->fresh()->isCore());
        $this->assertTrue((bool) data_get(
            json_decode((string) DB::table('platform_plugin_registry_entries')
                ->where('registry_type', 'runtime')
                ->where('plugin_slug', 'page-builder')
                ->value('payload'), true),
            'runtime_enabled',
        ));
    }
}
