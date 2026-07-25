# Header Latest Row Menu Vertical Alignment Report

## Task Title
Align primary menu inside the Latest row.

## Objective
Keep the frontend primary menu in the same visual container as the `Latest` control and prevent it from dropping to the lower border of the header row.

## Files Modified
- `platform_theme_builder_templates` database record:
  - `id`: 1
  - `template_type`: header
  - `slug`: header

## Files Not Modified
- No plugin files were modified.
- No unrelated templates were modified.

## Changes Made
- Updated the header CSS for `.art-header-primary-menu`.
- The menu is now positioned absolutely inside `.art-header-menu-row`.
- The menu uses:
  - `top: 0`
  - `bottom: 0`
  - `right: 40px`
  - `width: calc(100% - 320px)`
  - `align-items: center`
- This keeps the menu vertically aligned with `Latest`.
- Mobile layout resets the menu to normal static flow.

## Backup
Backup created before updating the header template:
`/var/www/store.z4rank.com/laravel/storage/app/theme-builder-template-backups/20260703-182613-header-menu-logo-container-dark-global`

## Verification
- Laravel caches rebuilt successfully.
- Public homepage CSS confirms `.art-header-primary-menu` is positioned inside the Latest row.
- Header HTML still keeps the menu inside `.art-header-menu-row`.

## Rollback Notes
- Restore template `id=1` from the backup JSON if needed.
