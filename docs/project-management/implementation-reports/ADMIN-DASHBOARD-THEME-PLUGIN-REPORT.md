# Admin Dashboard Theme Plugin Support

## Task

Create a plugin-based admin dashboard theme system. The theme must be a plugin and inject its stylesheet into the admin panel head when active.

## Backup

```text
/root/codex-backups/admin-dashboard-theme-core-20260627-013730
```

## Core Changes

- Added `App\Platform\Core\Services\AdminPluginAssetManager`.
- Updated `resources/views/layouts/app.blade.php` to inject active plugin admin styles into `<head>`.

## Plugin Artifact

Created local plugin ZIP:

```text
D:\codex_progects\inpa-server-proxmox\admin-dashboard-theme.zip
```

Plugin root:

```text
admin-dashboard-theme/
├── module.json
├── src/AdminDashboardThemeServiceProvider.php
├── resources/assets/css/admin-dashboard-theme.css
└── docs/plugin.md
```

## Manifest Standard

The admin dashboard theme plugin declares its stylesheet using:

```json
{
  "assets": {
    "source": "resources/assets",
    "admin": {
      "styles": [
        "css/admin-dashboard-theme.css"
      ]
    }
  }
}
```

## Behavior

- CSS is published by the existing plugin asset publisher into `public/platform/plugins/{slug}`.
- Only active plugins are considered.
- Only local `.css` files from safe relative paths are injected.
- Dangerous paths, absolute paths, missing files, non-CSS files, and traversal paths are ignored.
- The admin layout adds a cache-busted `<link>` tag with `data-plugin-admin-style="{slug}"`.

## Verification

- `php -l app/Platform/Core/Services/AdminPluginAssetManager.php`: passed.
- `php -l resources/views/layouts/app.blade.php`: passed.
- `php -l src/AdminDashboardThemeServiceProvider.php`: passed.
- `php codex_tmp/verify_admin_plugin_asset_injection.php`: `admin_plugin_asset_injection=passed`.
- `php artisan optimize:clear --no-ansi`: passed.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- `php artisan view:cache --no-ansi`: passed.
- `php artisan route:cache --no-ansi`: passed.
- `php artisan config:cache --no-ansi`: passed.
- HTTP `/`: 200.
- HTTP `/login`: 200.
- HTTP `/admin/plugins`: 302 to login for unauthenticated request, expected admin protection.
- ZIP check: `module.json` exists at ZIP root.

## Usage

Upload `admin-dashboard-theme.zip` from `/admin/plugins/install`, install it, then activate it. Once active, its stylesheet will be injected into the admin panel head automatically.
