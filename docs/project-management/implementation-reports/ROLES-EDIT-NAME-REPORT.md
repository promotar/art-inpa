# Roles Edit Name Report

Date: 2026-06-29

## Objective

Enable admins to rename existing roles from `/admin/roles` while preserving the existing permission assignment workflow.

## Changes

- Updated `app/Http/Controllers/Admin/RoleController.php`.
  - `update()` now validates and saves the role `name`.
  - Role name uniqueness is checked within the same `guard_name`.
  - Existing permission sync remains unchanged.
  - Spatie permission cache is cleared after role update.
- Updated `resources/views/admin/roles/index.blade.php`.
  - Added a role name input inside each role accordion.
  - Changed the action button from `Save Permissions` to `Save Role`.
  - Added a small warning that role names are platform keys.

## Backup

Server backup path:

```text
/root/codex-backups/roles-edit-name-20260629-025942
```

## Verification

Executed on Laravel server:

```text
php -l app/Http/Controllers/Admin/RoleController.php
php artisan optimize:clear --no-ansi
php artisan view:cache --no-ansi
php artisan route:cache --no-ansi
php artisan config:cache --no-ansi
php artisan route:list --name=admin.roles --no-ansi
```

Result:

- Controller syntax passed.
- Blade cache passed.
- Route cache passed.
- Config cache passed.
- `admin.roles.update` remains available as `PATCH admin/roles/{role}`.

## Rollback

Restore the two backed-up files from:

```text
/root/codex-backups/roles-edit-name-20260629-025942
```
