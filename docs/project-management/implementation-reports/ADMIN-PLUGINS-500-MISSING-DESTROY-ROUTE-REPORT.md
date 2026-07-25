# Admin Plugins 500 Missing Destroy Route Report

Date: 2026-07-03

## Objective

Fix `500 Server Error` on:

```text
https://staging.art-inpa.com/admin/plugins
```

## Root Cause

The plugins index Blade view renders an uninstall form using:

```php
route('admin.plugins.destroy', $plugin->slug)
```

The controller already had:

```php
PluginController::destroy(string $slug, PluginManager $plugins)
```

but `routes/web.php` did not register the matching route. Laravel threw:

```text
Route [admin.plugins.destroy] not defined.
```

This happened only when the authenticated plugins page was rendered.

## Change Made

Updated:

```text
routes/web.php
```

Added:

```php
Route::delete('/plugins/{slug}', [PluginController::class, 'destroy'])
    ->middleware('permission:plugins.install')
    ->name('plugins.destroy');
```

No plugin logic, uninstall logic, controller logic, or database schema was changed.

## Commands Run

```text
php artisan optimize:clear --no-ansi
php artisan route:cache --no-ansi
php artisan view:clear --no-ansi
php artisan view:cache --no-ansi
php artisan route:list --name=admin.plugins.destroy --no-ansi
```

## Verification

- Route `admin.plugins.destroy` is registered.
- Unauthenticated request to `/admin/plugins` correctly returns `302` to `/login`.
- Direct authenticated controller/view render in Laravel succeeds:

```text
PLUGIN_INDEX_RENDER_OK
```

## Backup

```text
/root/codex-backups/admin-plugins-500-20260703-005603
```

## Rollback

Restore:

```text
/root/codex-backups/admin-plugins-500-20260703-005603/web.php
```

to:

```text
/var/www/store.z4rank.com/laravel/routes/web.php
```

Then rebuild route/view caches.
