# Page Builder Editor Canvas Styles Fix Report

## Task Title
Page Builder editor must display the same styling context used by page preview.

## Issue Summary
The page preview rendered the Art INPA frontend theme correctly, but the edit canvas showed raw unstyled HTML. The page itself had no CSS stored in `platform_pages.css`; styling comes from the active frontend theme plugin assets.

## Root Cause
The editor canvas CSS endpoint was generated with Laravel `route()`, which uses `APP_URL`. On this server `APP_URL` is `http://store.z4rank.com`, while the owner is editing through `http://10.10.0.20`. The GrapesJS iframe could request the stylesheet from a different host, losing the current admin session and preventing the CSS from loading.

## Implementation
Changed the Page Builder editor canvas stylesheet URL to a same-origin relative path:

`/admin/pages/{page}/editor-preview.css`

This keeps the editor request on the exact host currently used in the browser.

## Files Modified
- `app/Http/Controllers/Admin/PageController.php`

## Core-Only Boundary
No plugin files were modified. The active Art INPA theme plugin CSS is only read by the existing editor preview CSS endpoint so the core editor can display the active frontend styling context.

## Verification
- PHP syntax check passed for `PageController.php`.
- Route `admin.pages.editor-preview-css` is registered.
- Laravel cache cleared with `php artisan optimize:clear`.
- Editor CSS generation for page `72` returned CSS that includes Art INPA classes such as `ainpa-news-main` and `ainpa-news-hero`.

## Expected Result
After refreshing `/admin/pages/72/edit`, the GrapesJS canvas should load the Art INPA frontend theme CSS and display styled content closer to the preview page.

## Known Limitation
This fix applies the frontend CSS context inside the editor. Dynamic theme-generated content is still controlled by the existing Page Builder source HTML and plugin renderer, so the editor remains safe for saving builder content without converting plugin-rendered output into static HTML.

## Recommended Test
Open:

`/admin/pages/72/edit`

Hard refresh if the browser has cached the old editor config, then confirm the canvas is no longer raw unstyled HTML.
