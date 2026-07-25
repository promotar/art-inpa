# Header Search Cleanup and Lower Ticker Removal Report

## Task Title
Remove unwanted header controls and fix public news search.

## Objective
Apply the requested header cleanup:

- Remove the day/night icon marked with X.
- Remove the lower post ticker/list section below the header.
- Fix search so it returns dynamic article results.

## Scope
Changes were made in:

- Active Theme Builder header template.
- Core Page Builder render service.
- Dynamic Home/News page placeholders and CSS.

No plugin files were modified.

## Database Updated
Header template:

- Table: `platform_theme_builder_templates`
- Record: `id = 1`
- Type: `header`

Pages refreshed:

- Table: `platform_pages`
- Slugs: `home`, `news`

## Files Modified
- `/var/www/store.z4rank.com/laravel/app/Platform/Core/PageBuilder/PageBuilderRenderService.php`

## Behavior Changes
Header:

- Removed `art-header-mode-toggle` button from the header HTML.
- Search form now submits to:

```text
/pages/news?search=...
```

Home:

- Removed the generated `ainpa-classic-breaking` lower ticker element from the dynamic Home layout.

News:

- News archive now reads the `search` query parameter.
- Search filters published posts by:
  - title
  - excerpt
  - content
- Search results show the query text in the page heading area.

## Backups Created
Header backup:

- `/var/www/store.z4rank.com/laravel/storage/app/codex-file-backups/20260704-143828-header-single-row-layout`

Home/News page backup:

- `/var/www/store.z4rank.com/laravel/storage/app/codex-file-backups/20260704-143828-home-news-dynamic`

Render service backup:

- `/var/www/store.z4rank.com/laravel/storage/app/codex-file-backups/20260704-143900-header-search-cleanup-service/PageBuilderRenderService.php`

## Verification
Passed checks:

- Header mode toggle button removed.
- Header search form points to `/pages/news`.
- Homepage loads.
- Lower ticker element is not rendered.
- Mode toggle button is not rendered.
- Search URL loads.
- Search page renders `Search Results`.
- Search query text appears in rendered output.

## Cache Commands
Executed:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Rollback Notes
Restore the backed up header HTML/CSS, restore the backed up `PageBuilderRenderService.php`, restore Home/News page backups if needed, then rebuild Laravel caches.

## Final Status
Completed.
