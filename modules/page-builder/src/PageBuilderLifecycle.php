<?php

namespace Modules\PageBuilder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PageBuilderLifecycle
{
    public function consolidate(): void
    {
        $pluginId = Schema::hasTable('plugins')
            ? DB::table('plugins')->where('slug', 'page-builder')->value('id')
            : null;

        if ($pluginId && Schema::hasTable('menu_items')) {
            DB::table('menu_items')
                ->where('route_name', 'admin.pages.index')
                ->where(function ($query) use ($pluginId): void {
                    $query->whereNull('plugin_id')->orWhere('plugin_id', '!=', $pluginId);
                })
                ->delete();

            DB::table('menu_items')
                ->where('plugin_id', $pluginId)
                ->where('route_name', 'admin.pages.index')
                ->update([
                    'title' => 'Page Builder',
                    'label' => 'Page Builder',
                    'permission' => 'pages.manage',
                    'sort_order' => 10,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
        }

        $this->migrateDirectPermissions();

        if (Schema::hasTable('platform_settings')) {
            DB::table('platform_settings')
                ->where('value', 'like', 'front-builder:%')
                ->update([
                    'value' => DB::raw("REPLACE(value, 'front-builder:', 'platform-page:')"),
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('platform_plugin_registry_entries')) {
            DB::table('platform_plugin_registry_entries')
                ->where('plugin_slug', 'front-builder')
                ->delete();
        }
    }

    private function migrateDirectPermissions(): void
    {
        if (
            ! Schema::hasTable('permissions')
            || ! Schema::hasTable('model_has_permissions')
        ) {
            return;
        }

        $legacyPermission = DB::table('permissions')->where('name', 'front-builder.manage')->value('id');
        $pagePermission = DB::table('permissions')->where('name', 'pages.manage')->value('id');

        if (! $legacyPermission || ! $pagePermission) {
            return;
        }

        foreach (DB::table('model_has_permissions')->where('permission_id', $legacyPermission)->get() as $assignment) {
            DB::table('model_has_permissions')->updateOrInsert([
                'permission_id' => $pagePermission,
                'model_type' => $assignment->model_type,
                'model_id' => $assignment->model_id,
            ]);
        }
    }
}
