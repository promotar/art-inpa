# Platform Registry Backups Tab Removal Report

## Task Title
Remove Backup tab and backup routes from Platform Registry after moving backup management to System.

## Objective
Keep backup management available only from the dedicated System Backup page and remove the duplicated backup surface from Admin Platform Registry.

## Files Modified
- `resources/views/admin/platform-registry/index.blade.php`
- `app/Http/Controllers/Admin/PlatformRegistryController.php`
- `routes/web.php`

## What Changed
- Removed the `Backups` tab button from the Platform Registry page.
- Removed the backup checkpoints panel from the Platform Registry page.
- Removed backup data loading from `PlatformRegistryController@index`.
- Removed unused Platform Registry backup methods:
  - `storeBackup()`
  - `showBackupLocation()`
  - `restoreBackup()`
  - `destroyBackup()`
- Removed old Platform Registry backup routes:
  - `POST /admin/platform-registry/backups`
  - `GET /admin/platform-registry/backups/{backup}/location`
  - `POST /admin/platform-registry/backups/{backup}/restore`
  - `DELETE /admin/platform-registry/backups/{backup}`
- Kept the dedicated System Backup routes active under `/admin/backups`.

## Current Backup Location
Backup management is now available only from:

`/admin/backups`

## Routes After Change
Platform Registry routes:
- `GET /admin/platform-registry`
- `GET /admin/platform-registry/live-log`

Backup routes:
- `GET /admin/backups`
- `POST /admin/backups`
- `GET /admin/backups/{backup}/location`
- `POST /admin/backups/{backup}/restore`
- `DELETE /admin/backups/{backup}`

## Safety Notes
- No backup records were deleted.
- No backup files were deleted.
- No database migrations were run.
- No plugin files were modified.
- The existing System Backup controller remains responsible for listing, creating, restoring, locating, and removing backups.

## Verification
- PHP syntax check passed for `PlatformRegistryController.php`.
- PHP syntax check passed for `routes/web.php`.
- Laravel route cache was cleared.
- Laravel compiled views were cleared.
- `php artisan route:list --path=admin/platform-registry` shows only Platform Registry and Live Log routes.
- `php artisan route:list --path=admin/backups` confirms the dedicated backup routes remain available.
- Local search confirmed no `platform-registry.backups` references remain in the modified files.

## Known Limitations
- This task only removes backup management from Platform Registry.
- It does not change backup retention, backup file contents, or restore behavior.

## Rollback Notes
To restore the old duplicated Platform Registry backup tab, re-add the removed Blade section, restore the removed controller methods, and re-register the old `/admin/platform-registry/backups` routes. This rollback is not recommended because System Backup is now the canonical backup area.
