# Header Menu Latest Row and Dark Mode Cleanup Report

## Task Title
Move frontend menu back to the Latest row and clean up dark mode behavior.

## Objective
Place the primary frontend menu in the same header container as the `Latest` control, and reduce overly aggressive dark mode styling that affected cards and article blocks in an uneven way.

## Files Modified
- `app/Platform/Core/Rendering/PlatformContentRenderer.php`
- `platform_theme_builder_templates` database record:
  - `id`: 1
  - `template_type`: header
  - `slug`: header

## Files Not Modified
- No plugin files were modified.
- No unrelated Theme Builder templates were modified.

## Changes Made
- Moved `art-header-primary-menu` back into `art-header-menu-row`.
- Kept logo, burger, live button, account, search, and day/night toggle in `art-header-main-row`.
- Restored header menu row layout to `justify-content: space-between`.
- Restored main header row grid to the original three-column layout.
- Removed broad dark mode rules that forced every card/article/aside to dark surface colors.
- Kept global dark mode variables and page/body background behavior.
- Kept form field dark mode support.

## Backup
Backup created before updating the header template:
`/var/www/store.z4rank.com/laravel/storage/app/theme-builder-template-backups/20260703-181948-header-menu-logo-container-dark-global`

## Verification
- PHP syntax check passed for `PlatformContentRenderer.php`.
- Laravel caches rebuilt successfully.
- Public homepage HTML confirms:
  - `art-header-menu-row` exists.
  - `art-header-primary-menu` is inside the menu/latest row.
  - `art-header-main-row` follows after the menu row.
  - `data-art-theme-toggle` exists.
  - `html.art-dark-mode` CSS rules exist.

## Known Limitations
- Some static template sections may still use their own hardcoded colors. Those should be adjusted section-by-section if a fully custom dark palette is required.

## Rollback Notes
- Restore template `id=1` from the backup JSON.
- Restore the previous core renderer file if needed.
