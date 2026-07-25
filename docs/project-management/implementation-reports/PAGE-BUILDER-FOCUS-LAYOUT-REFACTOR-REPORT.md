# Page Builder Focus Layout Refactor Report

Date: 2026-06-29

## Objective

Refactor the admin Page Builder edit screen into a dedicated full-height designer workspace for `/admin/pages/{page}/edit`, without affecting normal admin pages.

## Root Cause

The edit screen was rendered inside the normal `x-app-layout`. That layout adds a fixed admin bar/sidebar offset, optional page heading, normal document flow, and page-level vertical scrolling. The Page Builder then added its own command bar, settings block, toolbar, GrapesJS canvas, and right panel inside that flow. This created stacked vertical bars, a canvas that started too low, a squeezed right panel, and nested scrollbars.

## Implementation

- Added a dedicated anonymous Blade component for Page Builder focus mode:

```text
resources/views/components/page-builder-focus-layout.blade.php
```

- Replaced the edit view's normal admin shell with a semantic focus layout:

```text
resources/views/admin/pages/edit.blade.php
```

- Rebuilt the Page Builder CSS as the layout source of truth:

```text
public/vendor/front-builder/page-builder/page-builder.css
```

- Updated Page Builder JS to support the new layout:

```text
public/vendor/front-builder/page-builder/page-builder.js
```

## UX Results

- The builder no longer uses the normal admin page vertical flow.
- Header, status row, toolbar, canvas, and right inspector now occupy a full-height grid.
- Page Settings is closed by default and opens as a floating drawer that does not permanently push the canvas down.
- Settings fields scroll inside the drawer.
- GrapesJS views container is mounted into the dedicated right inspector panel.
- Canvas owns its internal scrolling.
- Right inspector owns its internal scrolling.
- Save, Preview, Pages, status, and public URL remain visible in the compact header/status area.
- Normal admin pages continue using `resources/views/layouts/app.blade.php` unchanged.

## Backup

```text
/root/codex-backups/page-builder-focus-layout-refactor-20260628-234742
```

## Verification

```text
php -l resources/views/admin/pages/edit.blade.php: passed
php -l resources/views/components/page-builder-focus-layout.blade.php: passed
node --check public/vendor/front-builder/page-builder/page-builder.js: passed
php artisan view:clear: passed
php artisan optimize:clear: passed
Blade render check: render-ok page=1 bytes=167856
Published CSS hash matches Laravel public CSS: passed
Published JS hash matches Laravel public JS: passed
Temporary render check script removed: passed
```

Playwright screenshot verification was not executed because Playwright is not installed in the current local workspace.

## Rollback

```text
cd /var/www/store.z4rank.com/laravel
cp /root/codex-backups/page-builder-focus-layout-refactor-20260628-234742/resources/views/admin/pages/edit.blade.php resources/views/admin/pages/edit.blade.php
cp /root/codex-backups/page-builder-focus-layout-refactor-20260628-234742/public/vendor/front-builder/page-builder/page-builder.css public/vendor/front-builder/page-builder/page-builder.css
cp /root/codex-backups/page-builder-focus-layout-refactor-20260628-234742/public/vendor/front-builder/page-builder/page-builder.js public/vendor/front-builder/page-builder/page-builder.js
cp /root/codex-backups/page-builder-focus-layout-refactor-20260628-234742/public_html/vendor/front-builder/page-builder/page-builder.css /var/www/store.z4rank.com/public_html/vendor/front-builder/page-builder/page-builder.css
cp /root/codex-backups/page-builder-focus-layout-refactor-20260628-234742/public_html/vendor/front-builder/page-builder/page-builder.js /var/www/store.z4rank.com/public_html/vendor/front-builder/page-builder/page-builder.js
rm -f resources/views/components/page-builder-focus-layout.blade.php
php artisan optimize:clear --no-ansi
```
