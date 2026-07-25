# System Backup Tab Report

## Task Title
Add Backup tab under System.

## Objective
Add a dedicated System Backup page in the admin sidebar, using the existing platform backup checkpoint logic.

## Files Created
- `app/Http/Controllers/Admin/BackupController.php`
- `resources/views/admin/backups/index.blade.php`

## Files Modified
- `routes/web.php`
- `app/Platform/Core/Menus/MenuManager.php`
- `config/platform_registry.php`
- `resources/views/layouts/navigation.blade.php`

## Routes Added
- `GET admin/backups`
- `POST admin/backups`
- `GET admin/backups/{backup}/location`
- `POST admin/backups/{backup}/restore`
- `DELETE admin/backups/{backup}`

## Menu Change
Added `Backup` under the `System` admin group.

Visibility:
- Super Admin only.

## Behavior
- Shows recent backup checkpoints.
- Allows manual backup creation.
- Opens backup folder in File Browser.
- Records restore confirmation notes.
- Removes backup checkpoint records and safe checkpoint files.

## Storage
Uses existing table:
- `backup_checkpoints`

Uses existing backup file location:
- `storage/app/platform/backup-checkpoints`

## Registry
Added:
- `admin.backups.*`

Allowed methods:
- `GET`
- `POST`
- `DELETE`

## Verification
- PHP syntax checks passed.
- View cache cleared.
- Route cache cleared.
- Config cache cleared.
- All admin backup routes are registered.
- Platform Registry accepts `admin.backups.index`.
- Backup page rendered successfully in a super-admin test context.
- Backup menu item is visible under System for super-admin.

## Safety Notes
- No plugin files were modified.
- No migrations were added or run.
- No destructive command was run.
- Existing backup logic was reused.

## Manual Test Steps
1. Login as super-admin.
2. Open the `System` group in the sidebar.
3. Click `Backup`.
4. Confirm `/admin/backups` opens.
5. Create a manual backup.
6. Confirm the backup appears in the table.
7. Test Location opens File Browser in a new tab.

## Known Limitations
- Restore currently records a restore note for checkpoint review.
- Automatic data restore remains intentionally conservative.
