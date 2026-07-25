# Page Builder Schema Settings Panel Report

Date: 2026-06-26

## Task

Refactor the Laravel + GrapesJS page builder settings panel to be schema-driven, with element-specific settings instead of one universal panel for all elements.

## Backup

```text
/root/codex-backups/page-builder-schema-settings-20260626-195533
```

The backup includes the affected PHP, Blade, JS, CSS files, project documentation, and a database dump.

## Changed Files

- `/var/www/store.z4rank.com/laravel/app/Platform/Core/PageBuilder/PageBuilderWidgetRegistry.php`
- `/var/www/store.z4rank.com/laravel/app/Http/Controllers/Admin/PageController.php`
- `/var/www/store.z4rank.com/laravel/resources/views/admin/pages/edit.blade.php`
- `/var/www/store.z4rank.com/laravel/public/vendor/front-builder/page-builder/page-builder.js`
- `/var/www/store.z4rank.com/laravel/public/vendor/front-builder/page-builder/page-builder.css`
- `/var/www/store.z4rank.com/public_html/vendor/front-builder/page-builder/page-builder.js`
- `/var/www/store.z4rank.com/public_html/vendor/front-builder/page-builder/page-builder.css`

## Implementation

- Added `PageBuilderWidgetRegistry::elementRegistry()`.
- Added 12 schema-driven element definitions:
  - container
  - box
  - heading
  - text
  - image
  - button
  - icon
  - icon_list
  - divider
  - spacer
  - video
  - gallery_carousel
- Added missing builder widgets for:
  - box
  - icon_list
  - gallery_carousel
- Passed `elementRegistry` into the builder view config.
- Replaced the universal Content/Style/Advanced/Dynamic panel path with a schema panel:
  - Content maps to General.
  - Style maps to Style.
  - Advanced maps to Advanced/Custom.
  - Dynamic maps to Special.
- Added schema panel CSS.
- Controls now include the required metadata:
  - key
  - label
  - tab
  - group
  - type
  - default
  - responsive
  - cssProperty
  - target
  - condition
  - sanitize

## Data Rule

The schemas define code-owned builder capabilities. User-edited values remain stored in the database through the existing page builder fields: `platform_pages.page_builder_json`, `platform_pages.html`, and `platform_pages.css`.

## Acceptance Verification

The registry acceptance script confirmed:

- Image exposes media library, alt, caption, image size, object-fit, link, and lightbox.
- Image does not expose rich text editor or drop cap.
- Text exposes rich text editor, typography, text color, link color, columns, and drop cap.
- Text does not expose image library, object-fit, image resolution, or lightbox.
- Button exposes text, URL/action, icon, icon position, typography, padding, background, border, and hover state.
- Button does not expose image object-fit or gallery controls.
- Container exposes layout/flex, width, min-height, gap, background, border, and children settings.
- Container does not expose media library or rich text editor.
- Spacer exposes height and basic advanced wrapper settings.
- Image lightbox is conditional on `link_type=media_file`.

## Verification

- PHP syntax checks passed for changed PHP/Blade files.
- `node --check page-builder.js`: passed on server.
- `schema_acceptance=passed`.
- `schemas=12`.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- `php artisan optimize:clear --no-ansi`: passed.
- `php artisan view:cache --no-ansi`: passed.
- `php artisan route:cache --no-ansi`: passed.
- `php artisan config:cache --no-ansi`: passed.
- Served asset checks:
  - `served_js_schema=yes`
  - `served_css_schema=yes`
- HTTP checks:
  - `/`: 200
  - `/login`: 200
- Browser check:
  - Public homepage rendered nonblank with no console errors.
  - Admin editor route redirected to login because the browser session was not authenticated, so visual admin-panel verification was limited to server-side render/assets/schema tests.
