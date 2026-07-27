<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        if (! Schema::hasTable('platform_pages')) {
            return;
        }

        Schema::table('platform_pages', function (Blueprint $table): void {
            foreach (['show_in_menu', 'menu_label', 'category', 'parent_id'] as $column) {
                if (Schema::hasColumn('platform_pages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
