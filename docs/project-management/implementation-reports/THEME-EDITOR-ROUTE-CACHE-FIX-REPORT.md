# Theme Editor Route Cache Fix Report

Date: 2026-06-27

## Task

Fix the issue where saving a file in the Theme Editor plugin clears caches and causes `/admin/plugins/theme-editor` to return `404 Not Found`.

## Root Cause

`PluginRouteLoader` checked plugin route files with `PHP_BINARY`:

```text
exec(PHP_BINARY -l route-file)
```

In the web/FPM runtime, `PHP_BINARY` was empty or not usable. After Theme Editor save triggered cache clearing, the syntax-check command failed with:

```text
sh: 1: : Permission denied
```

The loader treated the route file as invalid and skipped loading the Theme Editor admin routes, so the route disappeared until cache state changed again.

## Backup

```text
/root/codex-backups/theme-editor-route-cache-fix-20260627-015007
/root/codex-backups/admin-dashboard-theme-path-fix-20260627-015306
```

## Changes

- Updated `app/Platform/Core/Services/PluginRouteLoader.php`.
- Added safe PHP CLI resolution for route syntax checks:
  - `PHP_BINARY`
  - `PHP_BINDIR/php`
  - `PHP_BINDIR/php8.2`
  - `/usr/bin/php`
  - `/usr/bin/php8.2`
- If no PHP CLI can be resolved, route loading now continues into the existing guarded `require` flow and logs a warning instead of silently dropping valid plugin routes.
- Corrected the installed `admin-dashboard-theme` module paths from Windows-style archive names to Linux directories.
- Rebuilt local `admin-dashboard-theme.zip` with forward-slash archive paths.

## Verification

```text
php -l app/Platform/Core/Services/PluginRouteLoader.php
php artisan optimize:clear --no-ansi
curl http://10.10.0.20/admin/plugins/theme-editor
php artisan route:list --no-ansi
php artisan test --no-ansi
php artisan view:cache --no-ansi
php artisan route:cache --no-ansi
php artisan config:cache --no-ansi
```

Results:

- Theme Editor route remains registered after cache clearing.
- `/admin/plugins/theme-editor` returns `302` to `/login` for unauthenticated requests, not `404`.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- Production caches rebuilt successfully.
- Admin dashboard theme provider file exists and loads from the corrected path.

## Notes

- No secrets were printed, moved, deleted, or modified.
- No editable settings were moved into files.
- This fix keeps executable plugin code in the codebase and leaves runtime state in the database-backed plugin registry.
