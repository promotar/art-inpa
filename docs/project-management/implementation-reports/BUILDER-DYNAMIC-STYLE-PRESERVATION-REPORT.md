# Builder Dynamic Style Preservation Report

Date: 2026-06-26

## Task

Fix the frontend rendering path for Header Builder dynamic elements so styles created in GrapesJS apply on the public frontend.

## Problem

Dynamic Logo and Menu elements were rebuilt during frontend rendering. The old renderer preserved only limited `class` and `style` values and did not preserve GrapesJS-generated `id` attributes. GrapesJS commonly targets generated CSS by element ID, so the saved CSS could not match the rendered frontend elements.

## Changes

- Updated `PlatformContentRenderer` to preserve safe HTML attributes on dynamic Logo and Menu wrappers.
- Preserved `id`, `class`, and inline `style` for generated menu links by link position.
- Prevented duplicate fallback IDs when a menu has more database items than saved preview links.
- Updated the GrapesJS admin editor so menu preview refreshes preserve per-link style attributes instead of copying only the first link.
- Marked dynamic Logo and Menu components as explicitly stylable, draggable, and droppable in GrapesJS.

## Files Changed

- `app/Platform/Core/Rendering/PlatformContentRenderer.php`
- `resources/views/admin/pages/edit.blade.php`

## Backup

```text
/root/codex-backups/builder-dynamic-style-20260626-135303
```

## Verification

- `php -l app/Platform/Core/Rendering/PlatformContentRenderer.php`: passed.
- `php -l resources/views/admin/pages/edit.blade.php`: passed.
- Dynamic render verification: Logo, Menu, and Menu link `id/class/style` attributes are preserved.
- `php artisan optimize:clear --no-ansi`: passed.
- `php artisan test --no-ansi`: 25 passed, 61 assertions after clearing cached config.
- Production caches rebuilt with:
  - `php artisan view:cache --no-ansi`
  - `php artisan route:cache --no-ansi`
  - `php artisan config:cache --no-ansi`
- HTTP checks:
  - `/`: 200
  - `/login`: 200
  - `/admin/pages`: 302, expected unauthenticated admin protection.

## Notes

No editable settings were moved into files. The builder content and CSS remain stored in the database. This change only fixes how saved database HTML/CSS is rendered and previewed.
