# Frontend Single Injected Style Bundle Report

Date: 2026-06-26

## Task

Move frontend view-generated styles into one generated CSS file and inject that file in the frontend header.

## Scope

This change targets frontend header/menu styles and Theme Editor CSS overrides. Admin-only page styles, GrapesJS editor content templates, SVG blend styles in the default Laravel welcome view, and Alpine display helpers were not moved because they are not frontend header/theme styles and moving them blindly can break admin/editor behavior.

## Backup

Backup created before editing the live Laravel project:

```text
/root/codex-backups/frontend-style-bundle-20260625-220847
```

The backup includes Core files, Theme Editor files, frontend layouts, documentation, reports, and a database dump.

## Changed Files

- `/var/www/store.z4rank.com/laravel/app/Platform/Core/Theme/FrontendStyleBundle.php`
- `/var/www/store.z4rank.com/laravel/resources/views/layouts/frontend.blade.php`
- `/var/www/store.z4rank.com/laravel/resources/views/components/frontend-layout.blade.php`

## Implementation

- Added `App\Platform\Core\Theme\FrontendStyleBundle`.
- The service generates one frontend CSS bundle at:

```text
/var/www/store.z4rank.com/public_html/theme-overrides/frontend-style-bundle.css
```

- The generated bundle combines:
  - Front menu item style metadata.
  - Front menu hover style metadata.
  - Published Theme Editor CSS overrides.
- Frontend layouts now inject only one generated style link after Vite:

```text
/theme-overrides/frontend-style-bundle.css?v={content_hash}
```

- Removed inline menu `<style>` blocks from frontend layouts.
- Removed inline `style="{{ ... }}"` menu item attributes from frontend layouts.
- Kept allowed custom CSS classes from menu item metadata as classes only.

## Verification

Commands and checks run on the live Laravel project:

```bash
php -l app/Platform/Core/Theme/FrontendStyleBundle.php
php artisan optimize:clear --no-ansi
php artisan test --no-ansi
php artisan config:cache --no-ansi
php artisan route:cache --no-ansi
php artisan view:cache --no-ansi
```

Result:

- PHP syntax check: no syntax errors.
- `php artisan test`: 25 passed, 61 assertions.
- `/`: HTTP 200.
- `/login`: HTTP 200.
- `/admin/plugins/theme-editor`: HTTP 302 for unauthenticated request, expected admin protection.
- Frontend HTML now includes one Theme Editor/frontend style bundle link.
- The generated CSS file exists under Laravel `public_path()` and is served by HTTP 200.
- Frontend layout files no longer contain inline menu `<style>` blocks or menu `style="{{ ... }}"` attributes.

## Notes

Laravel `public_path()` for this deployment resolves to:

```text
/var/www/store.z4rank.com/public_html
```

Therefore the generated CSS bundle is stored under `public_html`, not under `/var/www/store.z4rank.com/laravel/public`.
