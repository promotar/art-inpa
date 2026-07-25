# Page Builder Image Actions Report

Date: 2026-06-26

## Task

Add click actions to Image and Dynamic Image elements in the GrapesJS page builder:

- Open a custom link when the image is clicked.
- Open the image in a frontend lightbox.

## Backup

```text
/root/codex-backups/page-builder-image-actions-20260626-193023
```

The backup includes the affected PHP files, Blade view, builder asset backup, project documentation, and database dump.

## Changed Files

- `/var/www/store.z4rank.com/laravel/app/Platform/Core/PageBuilder/PageBuilderWidgetRegistry.php`
- `/var/www/store.z4rank.com/laravel/app/Platform/Core/Rendering/PlatformContentRenderer.php`
- `/var/www/store.z4rank.com/laravel/resources/views/frontend/pages/show.blade.php`

## Implementation

- Added Image and Dynamic Image traits:
  - `data-pb-image-action`: none, link, lightbox
  - `data-pb-link-url`
  - `data-pb-link-target`
  - `data-pb-lightbox-size`
- Added safe frontend rendering for image actions:
  - `link` wraps the image in a safe anchor when the URL is valid.
  - `lightbox` wraps the image in a lightbox trigger.
  - Unsafe URL schemes such as `javascript:` are blocked and stripped from final output.
- Added lightweight frontend lightbox markup, CSS, and JS to the page renderer view.

## Data Rule

The selected image action and its values are saved with the builder output in the database through the existing page builder fields. No editable action setting was moved into config files or hardcoded runtime settings.

## Verification

- PHP syntax checks passed for changed PHP/Blade files.
- Renderer verification:
  - `link_wrap=yes`
  - `link_target=yes`
  - `lightbox_wrap=yes`
  - `lightbox_size=yes`
  - `unsafe_link_blocked=yes`
  - `image_trait_action=yes`
  - `image_trait_link=yes`
  - `dynamic_image_trait_action=yes`
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- `php artisan optimize:clear --no-ansi`: passed.
- `php artisan view:cache --no-ansi`: passed.
- `php artisan route:cache --no-ansi`: passed.
- `php artisan config:cache --no-ansi`: passed.
- HTTP checks: `/` returned 200 and `/login` returned 200.
- Browser check: homepage rendered nonblank, lightbox markup exists, and console errors/warnings were empty.
