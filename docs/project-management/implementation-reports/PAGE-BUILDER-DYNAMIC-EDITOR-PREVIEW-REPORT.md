# Page Builder Dynamic Editor Preview Report

## Task Title
Show rendered frontend-style dynamic content inside the Page Builder edit canvas.

## Objective
The editor page must show the same visual structure used by preview/public rendering so the owner can edit elements while seeing the real layout context.

## Problem
The edit canvas loaded raw `platform_pages.html`, while preview/public rendering passes the same HTML through the active `PageBuilderRenderService`.

For Art INPA pages, the active frontend theme plugin extends that renderer and transforms elements such as:

`data-art-news-element="hero-news"`

into full news cards, images, category/date metadata, and styled layout.

## Correct Approach
Do not replace stored page HTML with fully rendered preview HTML.

Instead:
- keep the original builder root element and its `data-pb-*` / `data-art-news-*` attributes
- render only a visual preview for the inside of the dynamic element
- keep save/storage compatible with `platform_pages`
- do not edit plugin files

## Files Modified
- `app/Http/Controllers/Admin/PageController.php`
- `routes/web.php`
- `resources/views/admin/pages/edit.blade.php`
- `public/vendor/front-builder/page-builder/page-builder.js`

## Routes Added
- `POST /admin/pages/{page}/editor-component-preview`
- Route name:
  `admin.pages.editor-component-preview`

## Controller Method Added
- `PageController::editorComponentPreview()`

This method:
- loads the requested page
- receives a component HTML fragment
- renders it through the active `PageBuilderRenderService`
- sanitizes preview HTML for safe canvas display
- returns JSON containing:
  - `html`
  - `inner_html`

## JavaScript Flow
`page-builder.js` now:
- detects components with `data-art-news-element`
- skips static-source elements unless explicitly supported
- sends the component HTML to the new preview endpoint
- receives rendered preview HTML
- injects only the rendered inner HTML into the component
- preserves the original root attributes for future saves
- suppresses dirty/autosave state while preview HTML is being injected

## Storage Behavior
Storage remains unchanged:
- active table:
  `platform_pages`
- active fields:
  `html`
  `css`
  `page_builder_json`

No plugin storage or architecture was changed.

## Plugin Boundary
No files under `modules/` were modified.

The Core Page Builder only uses the active renderer through Laravel service resolution, which is the correct platform extension point.

## Verification Performed
- `php -l app/Http/Controllers/Admin/PageController.php`
- `php -l routes/web.php`
- `node --check public/vendor/front-builder/page-builder/page-builder.js`
- route list confirmed:
  `admin.pages.editor-component-preview`
- Laravel cache cleared:
  `php artisan optimize:clear`
- server-side render probe confirmed page `72` first dynamic element:
  - source element:
    `hero-news`
  - rendered output includes:
    `ainpa-news-card`
    `ainpa-news-shell`

## Expected Browser Result
After hard refresh on:

`/admin/pages/72/edit`

dynamic Art INPA sections should show rendered news-card preview content inside the editor canvas, while keeping the page editable and save-safe.

## Known Limitation
Text inside dynamically generated post cards is generated from Blog data. Editing that generated card text directly inside the canvas is not the correct persistence path. Dynamic block behavior should be changed through the element controls such as category, limit, order, show image, show date, and similar settings.

## Rollback Notes
To roll back this change:
- remove route `admin.pages.editor-component-preview`
- remove `editorComponentPreview()` from `PageController`
- remove `editorComponentPreviewUrl` from the page builder config
- remove dynamic preview rendering logic from `page-builder.js`

## Follow-up Fix: GrapesJS Visual CSS Injection
The editor now injects frontend theme CSS into GrapesJS for visual editing only. The injected CSS is wrapped with internal markers and stripped during builder serialization so it is not saved into `platform_pages.css` or `page_builder_json`.

Desktop canvas width was set to `1280px` so the editor shows the desktop layout instead of forcing the theme into a narrow responsive breakpoint.
