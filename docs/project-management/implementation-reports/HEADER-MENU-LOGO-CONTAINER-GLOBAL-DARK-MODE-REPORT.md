# Header Menu Logo Container and Global Dark Mode Report

## Task Title
Header menu alignment and global day/night mode update.

## Objective
Move the frontend menu into the same header container as the logo, and make the day/night toggle affect the full public website instead of only the header.

## Files Modified
- `app/Platform/Core/Rendering/PlatformContentRenderer.php`
- `platform_theme_builder_templates` database record:
  - `id`: 1
  - `template_type`: header
  - `slug`: header

## Files Not Modified
- No plugin files were modified.
- No other Theme Builder templates were modified.

## Implementation Details
- Moved `art-header-primary-menu` from `art-header-menu-row` into `art-header-main-row`.
- Kept `Latest` and the favicon trigger in the upper row.
- Updated the header grid to support:
  - left tools
  - logo
  - primary menu
  - account/search/theme actions
- Added responsive spacing adjustments for medium screens.
- Strengthened global dark mode CSS variables and selectors in the core renderer.
- Dark mode now applies to:
  - body
  - main page wrapper
  - page builder output
  - public page containers
  - cards/articles/asides
  - common light utility backgrounds
  - form controls

## Backup
A backup was created before replacing the header template record.

Backup path:
`/var/www/store.z4rank.com/laravel/storage/app/theme-builder-template-backups/20260703-180447-header-menu-logo-container-dark-global`

## Commands Executed
- Uploaded updated header HTML/CSS to `/tmp`.
- Uploaded updated `PlatformContentRenderer.php` to `/tmp`.
- Ran PHP syntax checks on the uploaded PHP files.
- Copied `PlatformContentRenderer.php` into the Laravel core renderer path.
- Updated the Theme Builder header template record.
- Ran Laravel cache rebuild commands:
  - `php artisan optimize:clear`
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`

## Verification
- PHP syntax check passed for `PlatformContentRenderer.php`.
- Theme Builder header template `id=1` was updated.
- Public homepage contains:
  - `art-header-main-row`
  - `art-header-primary-menu`
  - `data-art-theme-toggle`
  - `html.art-dark-mode`
  - `--art-color-background`
- HTML order verification confirmed the primary menu appears inside the main header row after the logo.

## Known Limitations
- Existing static template sections with highly specific hardcoded background colors may still need targeted CSS cleanup if they override global variables with stronger selectors.
- The day/night toggle stores the selected mode in browser `localStorage` using `art-theme-mode`.

## Rollback Notes
- Restore the backed up JSON for template `id=1`.
- Restore the previous `PlatformContentRenderer.php` if needed.
