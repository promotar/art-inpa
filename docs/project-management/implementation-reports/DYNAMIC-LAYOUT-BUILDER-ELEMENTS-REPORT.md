# Dynamic Layout Builder Elements Report

Date: 2026-06-26

## Scope

Fixed published platform headers/footers so they render on the frontend, and made the Page Builder Logo/Menu elements database-aware.

## Backup

```text
/root/codex-backups/dynamic-layout-builder-20260626-002412
```

## Changed Files

- `app/Platform/Core/Rendering/PlatformContentRenderer.php`
- `app/Platform/Core/Menus/MenuManager.php`
- `app/Http/Controllers/Admin/PageController.php`
- `resources/views/admin/pages/edit.blade.php`
- `resources/views/frontend/pages/show.blade.php`
- `resources/views/components/frontend-layout.blade.php`

## Implementation

- Added `PlatformContentRenderer` as the central rendering layer for dynamic page-builder content.
- Frontend page rendering now loads all published `header` records from `platform_pages`, ordered by `sort_order` then `id`.
- Frontend page rendering now loads all published `footer` records from `platform_pages`, ordered by `sort_order` then `id`.
- Header/footer CSS from all published layout records is injected into the frontend page style output.
- Page HTML, header HTML, and footer HTML now pass through dynamic placeholder rendering.
- Added `MenuManager::getFrontendMenuByKey()` so a specific frontend menu can be rendered by database key.
- The GrapesJS Menu block now saves `data-platform-menu-key` and exposes a Menu select trait populated from database frontend menus.
- The GrapesJS Logo block now saves `data-platform-logo="site"` and previews the current logo from the official settings database.
- At render time, `data-platform-logo="site"` is replaced with the current site logo from settings.
- At render time, `data-platform-menu-key="..."` is replaced with the selected database menu.

## Verification

- `published_headers=1`
- `published_footers=0`
- `frontend_menus=1`
- `root_header_markers=1`
- `root_footer_markers=0`
- PHP syntax checks passed.
- `php artisan view:cache --no-ansi`: passed.
- `php artisan route:cache --no-ansi`: passed.
- `php artisan config:cache --no-ansi`: passed.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- `GET /`: HTTP 200.

## Notes

- Existing header content made with older static Logo/Menu blocks will still render as saved.
- New Logo/Menu elements dragged from the builder are dynamic and are resolved from database settings/menus at render time.
- No executable code is stored in the database.
