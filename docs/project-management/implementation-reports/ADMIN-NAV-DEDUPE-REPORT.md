# Admin Navigation Duplicate Theme Editor Fix Report

Date: 2026-06-26

## Task

Fix the admin sidebar showing Theme Editor twice while `/admin/menus` showed it once.

## Cause

The admin sidebar called `MenuManager::getAdminMenu()`, which returned every active menu with `location = admin`. That included:

- The central platform admin menu: `platform.admin`
- Plugin-owned admin menus such as `theme-editor.admin`

The `/admin/menus` page edits the central `platform.admin` menu, so the sidebar was not fully respecting that settings page as the single source of truth.

## Backup

Backup created before editing the live Laravel project:

```text
/root/codex-backups/admin-nav-dedupe-20260625-222623
```

The backup includes menu core files, navigation view, documentation, reports, and a database dump.

## Changed Files

- `/var/www/store.z4rank.com/laravel/app/Platform/Core/Menus/MenuRepository.php`
- `/var/www/store.z4rank.com/laravel/app/Platform/Core/Menus/MenuManager.php`

## Data Adjustment

Updated the central `platform.admin` menu item for `admin.plugins.theme-editor.index`:

- `title`: `Theme Editor`
- `label`: `Theme Editor`

## Implementation

- Added `MenuRepository::activeByKey()`.
- Updated `MenuManager::getAdminMenu()`:
  - If active `platform.admin` exists, return only its items.
  - If `platform.admin` does not exist, fall back to all active admin menus for compatibility.
- This keeps plugin menus registered in the platform but prevents plugin-owned menus from duplicating sidebar entries when the central admin menu exists.

## Verification

Commands and checks run on the live Laravel project:

```bash
php -l app/Platform/Core/Menus/MenuRepository.php
php -l app/Platform/Core/Menus/MenuManager.php
php artisan optimize:clear --no-ansi
php artisan test --no-ansi
php artisan config:cache --no-ansi
php artisan route:cache --no-ansi
php artisan view:cache --no-ansi
```

Result:

- PHP syntax checks: no syntax errors.
- Admin menu item count: 13.
- Theme Editor count in admin menu: 1.
- Admin menu labels:
  - Dashboard
  - Documentation
  - Platform Registry
  - Menus
  - Front Builder
  - Media
  - Pages
  - Settings
  - Plugins
  - Users
  - Roles
  - Permissions
  - Theme Editor
- `php artisan test`: 25 passed, 61 assertions.
- `/`: HTTP 200.
- `/login`: HTTP 200.
- `/admin/menus`: HTTP 302 for unauthenticated request, expected admin protection.
- No temporary `codex_*.php` files remain in the Laravel root.
