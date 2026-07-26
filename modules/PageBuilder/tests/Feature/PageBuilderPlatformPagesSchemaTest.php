<?php

namespace Modules\PageBuilder\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PageBuilderPlatformPagesSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_plugin_repair_migration_adds_missing_columns_to_existing_table(): void
    {
        Schema::dropIfExists('platform_page_revisions');
        Schema::dropIfExists('platform_theme_builder_conditions');
        Schema::dropIfExists('platform_theme_builder_template_conditions');
        Schema::dropIfExists('platform_theme_builder_templates');
        Schema::drop('platform_pages');
        Schema::create('platform_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('content_type', 40)->default('page')->index();
            $table->string('block_key')->nullable()->index();
            $table->longText('content')->nullable();
            $table->longText('page_builder_json')->nullable();
            $table->longText('html')->nullable();
            $table->longText('css')->nullable();
            $table->string('status')->default('draft')->index();
            $table->integer('sort_order')->default(0)->index();
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        $this->assertFalse(Schema::hasColumn('platform_pages', 'parent_id'));

        $migration = require base_path(
            'modules/PageBuilder/database/migrations/2026_07_27_000002_ensure_platform_page_hierarchy_columns.php'
        );
        $migration->up();

        foreach (['parent_id', 'category', 'menu_label', 'show_in_menu'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('platform_pages', $column),
                "Expected platform_pages.{$column} to be repaired.",
            );
        }
    }
}
