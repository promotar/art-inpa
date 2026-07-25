# Builder Menu Preview Report

Date: 2026-06-26

## Scope

Updated the GrapesJS Menu element so the editor canvas shows the selected database menu items instead of the placeholder text `Database menu`.

## Backup

```text
/root/codex-backups/builder-menu-preview-20260626-003855
```

## Changed Files

- `app/Platform/Core/Rendering/PlatformContentRenderer.php`
- `app/Http/Controllers/Admin/PageController.php`
- `resources/views/admin/pages/edit.blade.php`

## Implementation

- Added `PlatformContentRenderer::menuPreviewItems()` to expose frontend menu items to the builder.
- Passed `frontendMenuPreviewItems` from the admin Page Controller to the GrapesJS view.
- The Menu block now renders real menu links from the selected database menu in the canvas.
- Changing the Menu trait dropdown updates the visible menu links immediately inside GrapesJS.
- Existing saved menu elements are refreshed when the builder loads.
- Frontend render now preserves the first preview link `class` and `style` as the template for generated database menu links.

## Verification

- `menus=1`
- `first_menu=platform.frontend`
- `first_menu_items=2`
- `trait_options=1`
- PHP syntax checks passed.
- `php artisan view:cache --no-ansi`: passed.
- `php artisan route:cache --no-ansi`: passed.
- `php artisan config:cache --no-ansi`: passed.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.

## Notes

- Menu records and menu items remain stored in the database.
- The builder receives metadata and preview labels only; executable code remains in the codebase.
