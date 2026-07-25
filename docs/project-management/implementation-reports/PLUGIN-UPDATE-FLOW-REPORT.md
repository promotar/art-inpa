# Plugin Update Flow Report

Date: 2026-06-29

## Objective

Change Laravel core plugin upload behavior so uploading a ZIP for an already installed plugin no longer fails immediately with the duplicate-slug error. Instead, matching plugin packages get a review/update flow.

## Previous Behavior

When `modules/{slug}` already existed, `Admin\PluginController::store()` threw:

```text
A plugin with this slug already exists in the modules directory. Remove or rename the existing module before installing again.
```

This blocked normal plugin updates.

## New Behavior

If an uploaded plugin ZIP has the same slug as an installed plugin:

1. The platform reads and validates `module.json`.
2. The platform compares:
   - `slug`
   - plugin `name`
   - owner/author
   - old and new `version`
3. If name or owner do not match, the update is rejected.
4. If identity matches, the ZIP is stored as a pending update.
5. The admin is redirected to a review page.
6. The review page shows installed plugin info, uploaded plugin info, version comparison, and migration files.
7. After admin confirmation:
   - old plugin runtime is deactivated if it was active
   - old module files are moved out of `modules/{slug}` to a backup path
   - new module files are moved into `modules/{slug}`
   - PluginManager runs a plugin update
   - migrations run forward only through Laravel `migrate --path`
   - existing plugin database data is preserved
   - if the old plugin was active, the updated plugin is reactivated

## Data Preservation

The update flow does not call the plugin uninstall flow.

`PluginInstaller` now supports an update mode where migration rollback is skipped on update failure. This prevents a plugin migration `down()` method from deleting plugin data during an update rollback. Fresh installs still keep the previous rollback behavior.

## Files Changed

```text
app/Http/Controllers/Admin/PluginController.php
app/Platform/Core/Services/PluginInstaller.php
app/Platform/Core/Services/PluginManager.php
routes/web.php
resources/views/admin/plugins/create.blade.php
resources/views/admin/plugins/update.blade.php
```

## Backup

```text
/root/codex-backups/plugin-update-flow-20260629-000000
```

## Verification

```text
php -l app/Platform/Core/Services/PluginInstaller.php
php -l app/Platform/Core/Services/PluginManager.php
php -l app/Http/Controllers/Admin/PluginController.php
php artisan optimize:clear --no-ansi
php artisan view:cache --no-ansi
php artisan route:cache --no-ansi
php artisan config:cache --no-ansi
php artisan route:list --name=admin.plugins.update --no-ansi
```

Result:

```text
GET  admin/plugins/update/{token}  admin.plugins.update.review
POST admin/plugins/update/{token}  admin.plugins.update.confirm
```

Operation log:

```text
operation_logs.id = 246
```

## Notes

- The old plugin files are removed from the active modules directory but kept in the backup path for rollback.
- The update page warns if the uploaded version is lower than the installed version.
- The system allows same-version replacement for local development/plugin rebuilds as long as name and owner match.
