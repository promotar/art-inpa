# Page Builder Dynamic Image Style Persistence Report

Date: 2026-06-26

## Task

Fix the GrapesJS page builder issue where styles applied to a logo/image, such as border radius, padding, margin, border, or background-related styling, were lost after saving and reloading the editor.

## Root Cause

The style save path was preserving GrapesJS CSS, but dynamic logo rendering was replacing the inner `img` element in two places:

- The admin builder preview regenerated the logo image with fresh HTML.
- The frontend renderer regenerated the logo image with fresh HTML.

That replacement dropped the image `id`, `class`, and `style` attributes. GrapesJS CSS targets depend on stable element selectors, so the saved style could no longer attach to the image after reload.

## Backup

```text
/root/codex-backups/page-builder-dynamic-image-style-20260626-191239
```

The backup includes the renderer, builder JavaScript assets, project documentation, and a database dump.

## Changed Files

- `/var/www/store.z4rank.com/laravel/app/Platform/Core/Rendering/PlatformContentRenderer.php`
- `/var/www/store.z4rank.com/laravel/public/vendor/front-builder/page-builder/page-builder.js`
- `/var/www/store.z4rank.com/public_html/vendor/front-builder/page-builder/page-builder.js`

## Implementation

- Added image attribute preservation to the builder logo preview.
- Preserved dynamic logo image `id`, `class`, and `style` when replacing the logo source from database settings.
- Kept unsafe event attributes blocked through the existing safe attribute filtering path.
- Kept editable style values in `platform_pages.page_builder_json`, `platform_pages.html`, and `platform_pages.css`; no editable setting was moved to code files.

## Verification

- `node --check page-builder.js`: passed locally.
- `php -l PlatformContentRenderer.php`: passed locally and on server.
- Dynamic renderer test preserved `img_id`, `img_class`, `img_style`, and wrapper ID.
- `php artisan optimize:clear --no-ansi`: passed.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- `php artisan view:cache --no-ansi`: passed.
- `php artisan route:cache --no-ansi`: passed.
- `php artisan config:cache --no-ansi`: passed.
- Served asset check confirmed `componentImageAttributes` exists in the public JavaScript.
- HTTP checks: `/` returned 200 and `/login` returned 200.
- Browser check: `http://10.10.0.20/` rendered nonblank, displayed the logo/menu/page content, and had no console errors.

## Note

If a page was already saved before this fix after the image selector was lost, the old missing selector cannot be reconstructed automatically. Reopen that page/header, apply the image style again if needed, and save once; future reloads should preserve the image style target.
