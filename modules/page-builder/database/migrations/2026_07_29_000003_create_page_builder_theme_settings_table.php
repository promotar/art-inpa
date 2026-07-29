<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('page_builder_theme_settings')) {
            return;
        }

        Schema::create('page_builder_theme_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('header_page_id')->nullable()->index();
            $table->unsignedBigInteger('body_page_id')->nullable()->index();
            $table->unsignedBigInteger('footer_page_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_builder_theme_settings');
    }
};
