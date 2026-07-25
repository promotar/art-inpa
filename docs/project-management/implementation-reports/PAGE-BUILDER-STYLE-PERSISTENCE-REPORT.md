# Page Builder Style Persistence Root Fix Report

Date: 2026-06-26

## Task

Fix style settings such as border radius, border, margin, padding, and background not saving or applying to designed elements.

## Root Cause

GrapesJS stores many Style Manager changes in generated CSS selectors such as `#component-id`. The saved HTML could be exported without the matching `id` attributes, so the CSS existed in the database but no longer matched the frontend elements after save/reload.

## Backup

```text
/root/codex-backups/page-builder-style-persistence-20260626-190233
```

## Changes

- Added a save-pipeline guard in `page-builder.js` that walks every renderable component before saving.
- Every renderable component now receives its GrapesJS component id as a real HTML `id` attribute when missing.
- The editor now syncs ids when components are added, selected, loaded, and submitted.
- HTML export now uses `editor.getHtml({ cleanId: false })` so GrapesJS does not strip generated ids from saved HTML.
- The fix is global for all elements, including images, boxes, sections, buttons, menus, and plugin widgets.

## Files Changed

- `public/vendor/front-builder/page-builder/page-builder.js`
- `public_html/vendor/front-builder/page-builder/page-builder.js`

## Verification

- JavaScript syntax check passed.
- Served asset contains:
  - `persistStyleTargets`
  - `ensureComponentIdAttribute`
  - `cleanId: false`
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- Production caches rebuilt.
- `/`: HTTP 200.
- `/vendor/front-builder/page-builder/page-builder.js`: HTTP 200.

## Notes

Existing pages that were saved before this fix may need one save from the builder to materialize missing ids if they already contain CSS selectors without matching HTML ids. New saves preserve the targets automatically.
