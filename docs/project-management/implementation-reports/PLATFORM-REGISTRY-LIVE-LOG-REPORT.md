# Platform Registry Live Log Report

Date: 2026-06-30

## Objective

Add a Live Log tab to `/admin/platform-registry` so super-admin users can watch the latest 500 log lines from Laravel and active plugin log folders while the page is open.

## Implementation

- Added `App\Platform\Core\Logs\LiveLogReader`.
- Added `GET admin/platform-registry/live-log` as a super-admin JSON endpoint.
- Added a `Live Log` tab in `resources/views/admin/platform-registry/index.blade.php`.
- The tab polls the endpoint every 2 seconds, displays detected log sources, and renders the latest 500 log entries safely using text binding.

## Log Source Rules

The reader only scans safe local log paths:

- `storage/logs/*.log`
- `modules/{active-plugin}/logs/*.log`
- `modules/{active-plugin}/storage/logs/*.log`

Inactive plugin directories are not scanned.

## Files Changed

- `app/Platform/Core/Logs/LiveLogReader.php`
- `app/Http/Controllers/Admin/PlatformRegistryController.php`
- `resources/views/admin/platform-registry/index.blade.php`
- `routes/web.php`

## Verification

Commands executed on the Laravel server:

```text
php -l app/Platform/Core/Logs/LiveLogReader.php
php -l app/Http/Controllers/Admin/PlatformRegistryController.php
php artisan optimize:clear --no-ansi
php artisan view:cache --no-ansi
php artisan route:cache --no-ansi
php artisan config:cache --no-ansi
php artisan route:list --name=admin.platform-registry --no-ansi
php artisan tinker --execute='app(App\Platform\Core\Logs\LiveLogReader::class)->latest(20)'
```

Verification result:

- PHP syntax passed.
- Laravel caches rebuilt.
- Route `admin.platform-registry.live-log` registered.
- Live log reader returned 20 entries from 3 detected sources:
  - `storage/logs/platform-error.log`
  - `storage/logs/laravel.log`
  - `storage/logs/platform-success.log`

## Backup

Production backup created before deployment:

```text
/root/codex-backups/platform-registry-live-log-20260630-033201
```

## Rollback

Restore the backed-up controller, route file, and Blade view from:

```text
/root/codex-backups/platform-registry-live-log-20260630-033201
```

Then remove:

```text
app/Platform/Core/Logs/LiveLogReader.php
```

Finally rebuild caches:

```text
php artisan optimize:clear --no-ansi
php artisan view:cache --no-ansi
php artisan route:cache --no-ansi
php artisan config:cache --no-ansi
```
