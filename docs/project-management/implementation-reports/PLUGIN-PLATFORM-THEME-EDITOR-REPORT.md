# Plugin Platform Standard And Theme Editor Package

Date: 2026-06-25

## Summary

Implemented the Core plugin-platform standard updates and prepared a `theme-editor` plugin ZIP for manual admin upload.

## Backup

Before code changes, a Laravel source and database backup was created at:

```text
/root/codex-backups/plugin-platform-theme-editor-20260625-011141
```

## Changes

- Updated `Admin\PluginController` to use the Core `PluginManager` lifecycle.
- Added safer plugin ZIP validation for path traversal and dangerous/sensitive files.
- Updated dynamic provider loading so uploaded plugins can load provider files from their own plugin directory.
- Added dynamic manifest-backed functions, hooks, and routes to `PlatformRegistry`.
- Expanded `/admin/documentation` into a Documentation Center.
- Added safe view override path support under `storage/app/theme-overrides`.
- Added fallback defaults in `SettingsRepository` when `platform_settings` is absent during test/bootstrap.
- Added clean missing-table guards for plugin/theme loaders in test/bootstrap contexts.
- Built `theme-editor.zip` as a manual-upload plugin package.

## Theme Editor ZIP

Local artifact:

```text
D:\codex_progects\inpa-server-proxmox\theme-editor.zip
```

Server copy:

```text
/var/www/store.z4rank.com/laravel/storage/app/plugin_uploads/packages/theme-editor.zip
```

The plugin saves overrides under `storage/app/theme-overrides` and does not modify original Core files.

## Verification

- Plugin ZIP extracted cleanly on Linux.
- Plugin PHP files passed `php -l`.
- Plugin manifest loaded through `PluginManifestReader` as `theme-editor`.
- Changed Core PHP files passed `php -l`.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- `php artisan config:cache`: passed.
- `php artisan route:cache`: passed.
- `php artisan view:cache`: passed.
- HTTP checks:
  - `/`: 200
  - `/login`: 200
  - `/admin/documentation`: 302 to login while unauthenticated

## Note

Authenticated `/admin/documentation` verification could not be completed because the existing stored web login credential redirected back to `/login`. No credential was printed, changed, or reset.
