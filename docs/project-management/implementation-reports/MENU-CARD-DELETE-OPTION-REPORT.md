# Menu Card Delete Option Report

## Task Title
Add visible delete option for frontend menus.

## Objective
Make each frontend menu card show a direct delete option so menu records can be removed from the Menus screen without opening hidden settings first.

## Scope Completed
- Added a visible `Delete` button to every frontend menu card.
- Kept the existing confirmation prompt before deleting.
- Reused the existing `admin.menus.destroy` route.
- Kept menu item deletion unchanged.
- Kept admin/plugin menu deletion protected from this UI to avoid breaking the admin sidebar or plugin-provided menus.

## File Modified
- `/var/www/store.z4rank.com/laravel/resources/views/admin/menus/index.blade.php`

## Backup Created
- `/var/www/store.z4rank.com/laravel/storage/app/codex-file-backups/20260704-menu-delete-option/index.blade.php`

## Route Used
- `DELETE /admin/menus/menus/{menu}`
- Route name: `admin.menus.destroy`

## Verification
- Blade view cache rebuilt successfully.
- Confirmed the template contains the visible delete form and button.

## Commands Executed
- Backed up the previous Blade file.
- Replaced the Menus index Blade file.
- `php artisan view:clear`
- `php artisan view:cache`

## Plugin Safety
No plugin files were modified.

## Notes
The direct delete option applies to frontend menus shown under:

Admin > Menus > Frontend Menus
