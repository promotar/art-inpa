# Page Builder Current State Audit Report

Date: 2026-06-29

Project root audited:
`/var/www/store.z4rank.com/laravel`

Audit mode:
Read-only code and runtime inspection.

No application code, migrations, database data, or architecture files were modified for this audit.

---

## 1. Executive Summary

Current status:
`Partially functional / not yet production-complete`

The current Admin Page Builder is more than a visual prototype.
It has real admin routes, a real editor screen, real database persistence, public preview/rendering, dynamic header/footer rendering, widgets, element controls, and media metadata support through the separate media library.

However, it is not yet a complete production page builder.
Major missing or partial areas include revisions/autosave, AJAX save, integrated media picker inside builder controls, robust sanitization, template import normalization, page-builder-specific tests, rollback/versioning, and mature responsive editing.

Main evidence:

- Admin pages are stored in `platform_pages` with `page_builder_json`, `html`, and `css` columns.
- The edit UI loads GrapesJS and custom `page-builder.js`.
- Save writes generated HTML, CSS, and project JSON to `platform_pages`.
- Public page rendering reads published `platform_pages` rows and renders HTML/CSS.
- Page-builder-specific tables such as `page_builder_pages` and `page_builder_revisions` are missing in the active database.

---

## 2. Inventory

| Area | File | Status | Evidence |
|---|---|---:|---|
| Admin controller | `app/Http/Controllers/Admin/PageController.php` | Present | Creates, edits, saves, previews, deletes pages. Lines 17-255. |
| Public controller | `app/Http/Controllers/PageController.php` | Present | Loads published page by slug. Lines 10-23. |
| Admin edit view | `resources/views/admin/pages/edit.blade.php` | Present | Loads editor assets and form. Lines 1-173. |
| Admin index view | `resources/views/admin/pages/index.blade.php` | Present | Lists pages and creates page/header/footer/block records. |
| Front render view | `resources/views/frontend/pages/show.blade.php` | Present | Renders page HTML/CSS and dynamic header/footer. Lines 1-172. |
| Focus layout | `resources/views/components/page-builder-focus-layout.blade.php` | Present | Builder-specific compact full-screen layout. |
| JS editor | `public/vendor/front-builder/page-builder/page-builder.js` | Present | Initializes GrapesJS and saves editor state. Lines 289-969. |
| CSS editor | `public/vendor/front-builder/page-builder/page-builder.css` | Present | Full builder UI layout and inspector styling. |
| GrapesJS vendor | `public/vendor/front-builder/grapesjs/grapes.min.js` | Present | Used by edit view. |
| Widget registry | `app/Platform/Core/PageBuilder/PageBuilderWidgetRegistry.php` | Present | Core widgets and plugin widget manifest reader. Lines 10-645. |
| Dynamic sources | `app/Platform/Core/PageBuilder/PageBuilderDynamicSourceRegistry.php` | Present | Menus, pages, blocks, current page fields. Lines 21-107. |
| Render service | `app/Platform/Core/PageBuilder/PageBuilderRenderService.php` | Present | Page/render data and dynamic placeholders. Lines 23-173. |
| Content renderer | `app/Platform/Core/Rendering/PlatformContentRenderer.php` | Present | Logo, menu, and image action rendering. Lines 70-230. |

Legacy or separate builder modules:

| Module | Status In Active Routes | Evidence |
|---|---:|---|
| `modules/front-builder` | Not shown in active route introspection | `modules/front-builder/routes/web.php` exists, but active route list did not include `admin/front-builder`. |
| `modules/PageBuilder` | Legacy/inactive in active DB/routes | Files exist, but active tables `page_builder_pages`, `page_builder_revisions`, etc. are missing. |
| `theme-editor` plugin | Active but separate from Page Builder | `plugins` table: `theme-editor | active`. |

Conclusion:
The currently used builder is the core `admin/pages` builder backed by `platform_pages`, not the old `modules/PageBuilder` schema.

---

## 3. Routes Audit

Runtime route inspection showed these relevant routes:

| Method | URI | Name | Controller | Middleware |
|---|---|---|---|---|
| GET | `admin/pages` | `admin.pages.index` | `Admin\PageController@index` | `web, auth, staff, permission:pages.manage` |
| POST | `admin/pages` | `admin.pages.store` | `Admin\PageController@store` | `web, auth, staff, permission:pages.manage` |
| GET | `admin/pages/{page}/edit` | `admin.pages.edit` | `Admin\PageController@edit` | `web, auth, staff, permission:pages.manage` |
| PATCH | `admin/pages/{page}` | `admin.pages.update` | `Admin\PageController@update` | `web, auth, staff, permission:pages.manage` |
| DELETE | `admin/pages/{page}` | `admin.pages.destroy` | `Admin\PageController@destroy` | `web, auth, staff, permission:pages.manage` |
| GET | `admin/pages/{page}/preview` | `admin.pages.preview` | `Admin\PageController@preview` | `web, auth, staff, permission:pages.manage` |
| GET | `pages/{slug}` | `pages.show` | `PageController@show` | `web` |

Code evidence:

- `routes/web.php` defines admin page routes under `permission:pages.manage`.
- `routes/web.php` defines public route `pages/{slug}`.
- Runtime route introspection confirmed middleware and controller mapping.

Assessment:
Routes are functional and permission-protected on the admin side.
Public route only shows published content.

---

## 4. Controller Audit

Admin controller:
`app/Http/Controllers/Admin/PageController.php`

Functional items:

- `index()` lists rows from `platform_pages`.
  Evidence: lines 17-25.
- `store()` creates a draft page/header/footer/block.
  Evidence: lines 28-63.
- `edit()` loads the page record, widgets, saved blocks, element registry, dynamic sources, preview URL, public URL, and content types.
  Evidence: lines 65-82.
- `update()` validates and writes `title`, `slug`, `content_type`, `block_key`, `content`, `page_builder_json`, `html`, `css`, `status`, `sort_order`, SEO fields, and timestamps.
  Evidence: lines 85-123.
- `preview()` renders the frontend page view in preview mode.
  Evidence: lines 125-130.
- `destroy()` deletes the page row.
  Evidence: lines 133-145.

Important gap:
There is no revision creation, autosave endpoint, rollback endpoint, or AJAX save response.
The save flow redirects back to edit page.
Evidence: `update()` returns `redirect()->route('admin.pages.edit', $page)` at lines 120-122.

Public controller:
`app/Http/Controllers/PageController.php`

Functional items:

- Looks up `platform_pages` by `slug`.
- Requires `content_type = page`.
- Requires `status = published`.
- Renders `frontend.pages.show`.

Evidence:
Lines 10-23.

Assessment:
Public page serving is functional for published normal pages only.
Headers, footers, and blocks are not directly served through this public route.

---

## 5. Hooks, Providers, and Registry Audit

Widget registry:
`app/Platform/Core/PageBuilder/PageBuilderWidgetRegistry.php`

Current capabilities:

- Core widgets are defined in PHP.
  Evidence: `widgets()` starts at line 10 and defines widgets such as section, container, box, grid, columns, heading, text, button, image, icon, video, etc.
- Editor blocks are generated from widgets and saved blocks.
  Evidence: `blocks()` lines 282-295.
- Element inspector schemas are defined in `elementRegistry()`.
  Evidence: lines 300-330 and further schema definitions.
- Plugin widgets can be read from `modules/*/module.json`.
  Evidence: `pluginWidgets()` lines 600-645.

Important limitation:
Plugin widget loading reads `modules/*/module.json` directly from filesystem.
It does not prove that only installed and active plugins from the `plugins` table contribute widgets.

Dynamic sources:
`app/Platform/Core/PageBuilder/PageBuilderDynamicSourceRegistry.php`

Current dynamic sources:

- Menus
- Menu preview items
- Default menu key
- Site logo
- Site title
- Pages
- Blocks
- Current page fields
- Dynamic field list

Evidence:
`editorSources()` lines 21-33.

Rendering hooks:
`app/Platform/Core/Rendering/PlatformContentRenderer.php`

Functional rendering transforms:

- Logo placeholders: `data-platform-logo`.
  Evidence: lines 157-180.
- Menu placeholders: `data-platform-menu-key`.
  Evidence: lines 183-197.
- Image actions: link/lightbox wrapping.
  Evidence: lines 200-230.

Legacy hook module:
`modules/PageBuilder/hooks.php`

Evidence:
It only adds a filter returning content unchanged:
`$hooks->addFilter('content.render', fn ($content) => $content);`

Assessment:
Core rendering hooks are functional.
Legacy module hook is minimal and does not represent the current active builder behavior.

---

## 6. Database and Storage Audit

Runtime schema inspection showed:

`platform_pages` exists with these columns:

- `id`
- `title`
- `slug`
- `content_type`
- `block_key`
- `content`
- `page_builder_json`
- `html`
- `css`
- `status`
- `sort_order`
- `seo_title`
- `meta_description`
- `published_at`
- `created_at`
- `updated_at`

`platform_media_metadata` exists with these columns:

- `id`
- `url`
- `alt_text`
- `title`
- `caption`
- `description`
- `created_at`
- `updated_at`

Missing active page-builder-specific tables:

- `front_builder_pages`
- `page_builder_pages`
- `page_builder_sections`
- `page_builder_blocks`
- `page_builder_templates`
- `page_builder_revisions`

Current content counts in `platform_pages`:

- `footer/published: 1`
- `header/published: 1`
- `page/published: 2`

Migration evidence:

- `database/migrations/2026_06_25_000001_create_platform_pages_table.php`
- `database/migrations/2026_06_26_000004_add_builder_columns_to_platform_pages_table.php`
- `database/migrations/2026_06_26_000005_add_content_type_to_platform_pages_table.php`
- `database/migrations/2026_06_26_000001_create_platform_media_metadata_table.php`

Assessment:
Persistence is real and database-backed.
The design is currently flat: one `platform_pages` table stores page metadata, builder JSON, HTML, and CSS.
No revision/history storage exists in the active schema.

---

## 7. UI Audit

Admin edit UI:
`resources/views/admin/pages/edit.blade.php`

Functional UI areas:

- Full page builder form.
  Evidence: lines 7-12.
- Header with Pages, Preview, Page Settings, Save.
  Evidence: lines 14-25.
- Status bar with path, public URL, status, and flash messages.
  Evidence: lines 28-52.
- Page settings drawer.
  Evidence: lines 55-117.
- Builder toolbar tabs:
  Elements, Layers, Content, Style, Advanced, Dynamic.
  Evidence: lines 119-127.
- Device buttons:
  Desktop, Tablet, Mobile.
  Evidence: lines 128-132.
- GrapesJS canvas and inspector panel.
  Evidence: lines 135-146.

Layout evidence:

- `resources/views/components/page-builder-focus-layout.blade.php` uses `page-builder-focus-body` and `page-builder-sidebar-compact`.
- `public/vendor/front-builder/page-builder/page-builder.css` fixes builder layout and canvas/inspector areas.

Assessment:
The UI is functional and purpose-built, not just a placeholder.
However, some workflows are form-based and reload the page after save.

---

## 8. Element and Widget System Audit

Core widget capability:

- Registry defines many widgets in PHP.
- Widgets are passed to JavaScript through `window.PageBuilderConfig`.
- JavaScript registers each widget as a GrapesJS component type.

Evidence:

- PHP widgets: `PageBuilderWidgetRegistry::widgets()` lines 10+.
- Blocks returned: lines 282-295.
- Config passed to JS: `edit.blade.php` lines 150-170.
- JS component registration: `page-builder.js` lines 863-884.

Inspector schemas:

- `elementRegistry()` defines schema controls with tabs, groups, cssProperty, target, condition, and sanitize metadata.
  Evidence: `PageBuilderWidgetRegistry.php` lines 300-330.

Limitation:
The `sanitize` metadata is present in PHP schemas, but JavaScript currently uses it mostly as metadata.
There is no server-side schema sanitizer applied when saving raw HTML/CSS/JSON.

---

## 9. Inspector and Settings Audit

JavaScript inspector behavior:

- Moves GrapesJS panels into the custom right panel.
  Evidence: `page-builder.js` lines 319-336.
- Creates custom schema settings panel.
  Evidence: lines 338-354.
- Supports tabs mapped from toolbar:
  Content, Style, Advanced, Dynamic.
  Evidence: lines 909-927.
- Reads and applies CSS controls.
  Evidence: lines 448-523.
- Reads and applies attribute controls.
  Evidence: lines 525-596.
- Renders grouped controls dynamically.
  Evidence: lines 653-706.

Assessment:
The inspector is functional for registered elements.
For unregistered/imported arbitrary HTML, it falls back to GrapesJS default behavior or shows no schema controls.

---

## 10. Save, Preview, and Publish Flow Audit

Save flow:

1. User clicks Save.
2. JavaScript serializes HTML, CSS, and project JSON into hidden fields.
3. Standard form submit sends PATCH request.
4. Controller stores values in `platform_pages`.
5. Browser redirects back to edit screen.

Evidence:

- Hidden inputs: `edit.blade.php` lines 10-12.
- JS submit handler: `page-builder.js` lines 963-969.
- Controller update: `PageController.php` lines 92-107.
- Redirect after save: lines 120-122.

Preview flow:

- Preview link opens `admin.pages.preview` in a new tab.
- Preview renders `frontend.pages.show` with `isPreview = true`.

Evidence:

- Preview button: `edit.blade.php` line 22.
- Preview method: `PageController.php` lines 125-130.
- Preview banner: `frontend/pages/show.blade.php` lines 92-95.

Publish flow:

- Publishing is controlled by `status` field in Page Settings.
- If status is `published`, `published_at` is set.

Evidence:

- Status select: `edit.blade.php` lines 84-90.
- `published_at` update logic: `PageController.php` line 105.
- Public controller requires `status = published`: `PageController.php` lines 12-16.

Missing:

- No separate publish/unpublish endpoints for current core builder.
- No scheduled publishing.
- No revision checkpoint before publish.
- No autosave.
- No AJAX save.

---

## 11. Responsive Editing Audit

What exists:

- GrapesJS device manager has Desktop, Tablet, Mobile portrait.
  Evidence: `page-builder.js` lines 300-305.
- Toolbar device buttons call `editor.setDevice()`.
  Evidence: lines 938-943.
- Style sectors include a Responsive sector.
  Evidence: lines 251-287.
- Many widget controls include responsive-related schema metadata.
  Evidence: `PageBuilderWidgetRegistry.php` control metadata has `responsive` boolean at lines 300-326.

Limitations:

- No clear per-device value storage layer was found beyond GrapesJS CSS output.
- No explicit breakpoint manager UI beyond device switching.
- No audit evidence of per-device custom controls persisting separately.

Assessment:
Responsive preview exists.
Responsive editing is partial.

---

## 12. Media Integration Audit

Media library page:
`app/Http/Controllers/Admin/MediaController.php`

Functional capabilities:

- Lists uploaded public storage images.
  Evidence: `mediaLibrary()` reads `Storage::disk('public')->allFiles()`, lines 129-158.
- Supports JSON response.
  Evidence: `index()` lines 16-28.
- Uploads images to `storage/app/public/media/YYYY/MM`.
  Evidence: `store()` lines 31-90.
- Stores SEO metadata.
  Evidence: `saveMediaMetadata()` lines 191-212.
- Deletes media and metadata.
  Evidence: `destroy()` lines 108-128.

Admin media UI:

- Grid/list toggle.
- Add new media file.
- Bulk select, delete selected, cancel.
- Image details panel with URL and SEO fields.

Evidence:
`resources/views/admin/media/index.blade.php` lines 248-520.

Builder media integration:

- Image widgets have URL/alt/image-action fields.
  Evidence: `PageBuilderWidgetRegistry.php` lines 47-56.
- JavaScript maps media controls to text inputs unless custom media UI exists.
  Evidence: `schemaTraitType()` maps `media` to `text`, `page-builder.js` lines 75-81.

Assessment:
Media library exists and works separately.
Direct builder-integrated media picker is missing or incomplete.

---

## 13. Static Template Compatibility Audit

Evidence of static HTML handling:

- Builder can initialize from `initialHtml` and `initialCss`.
  Evidence: `edit.blade.php` lines 151-155 and `page-builder.js` lines 946-950.
- GrapesJS can load raw HTML into editor components.
  Evidence: `editor.setComponents(config.initialHtml ...)` at line 949.

Limitations:

- No evidence found of a robust static-template import pipeline in the current core page builder.
- No evidence found of automatic conversion of arbitrary HTML sections into platform-editable registered elements.
- No evidence found of automatic asset rewriting for imported external templates inside the core page builder flow.
- Imported static HTML may display/edit as raw GrapesJS components, but it will not automatically gain full platform schema controls unless it uses recognized `data-pb-widget` attributes.

Assessment:
Static HTML compatibility is partial.
The system can host/render saved HTML, but deep conversion into platform builder components is not complete.

---

## 14. Permissions and Security Audit

Admin page builder is protected by:

- `auth`
- `staff`
- `permission:pages.manage`

Evidence:
Runtime route introspection and `routes/web.php` page route group.

Permission definition:
`app/Platform/Core/Services/PermissionManager.php`

- `pages.manage` = Manage content pages.
- `media.manage` = Manage media library.
- `front-builder.manage` = Manage Front Builder pages.

Evidence:
Lines 21-30.

Positive controls:

- Public pages require published status.
  Evidence: `app/Http/Controllers/PageController.php` lines 12-16.
- Public URL rendering has some safe URL handling for image actions.
  Evidence: `PlatformContentRenderer::safeFrontendUrl()` lines 313-321.
- Placeholder rendering strips event-handler attributes in selected transforms.
  Evidence: `safeAttributes()` lines 376-397.

Risk:

- Saved page HTML and CSS are rendered raw in `frontend.pages.show`.
  Evidence: `{!! $pageHtml !!}` line 104 and `{!! $page->css ?? '' !!}` line 87.
- Controller validation accepts `html`, `css`, and `page_builder_json` as nullable strings without server-side sanitization.
  Evidence: `Admin\PageController::validated()` lines 153-164.

Assessment:
Permissions exist and are meaningful.
Security still needs a formal HTML/CSS sanitization policy, especially before allowing non-super-admin users to use advanced HTML or custom attributes.

---

## 15. Feature Status Matrix

| Feature | Status | Evidence |
|---|---:|---|
| Admin page list | Working | `PageController@index`, `admin.pages.index`. |
| Create draft page/header/footer/block | Working | `PageController@store`, lines 28-63. |
| Visual editor loads | Working | GrapesJS assets in edit view, lines 1-4 and 171-172. |
| Widgets/blocks | Working | `PageBuilderWidgetRegistry`, `blocks()`, JS registration. |
| Inspector controls | Working/partial | JS schema panel lines 653-706. |
| Save HTML/CSS/JSON | Working | JS lines 963-969, controller lines 92-107. |
| Public published page render | Working | Public controller and frontend view. |
| Preview | Working | `PageController@preview`, lines 125-130. |
| Header/footer dynamic layout | Working | `PageBuilderRenderService`, lines 23-64. |
| Dynamic logo/menu render | Working | `PlatformContentRenderer`, lines 157-197. |
| Image lightbox/link action | Working/partial | Renderer lines 200-230 and frontend JS lines 117-170. |
| Media library page | Working | `MediaController` and `admin.media.index`. |
| Builder media picker | Missing/partial | Builder maps media controls to text. |
| Autosave | Missing | No autosave route or JS timer found. |
| Revision history | Missing | Active `page_builder_revisions` table missing. |
| AJAX save | Missing | Form POST redirects; no JSON save endpoint. |
| Per-device responsive values | Partial | Device preview exists; separate responsive value persistence not found. |
| Template ZIP/static import into editable components | Missing/partial | No core conversion pipeline found. |
| Tests for builder | Missing/unknown | No builder-specific test evidence found during audit. |

---

## 16. Critical Bugs, Gaps, and Risks

1. Raw HTML/CSS rendering risk.

Evidence:
`frontend/pages/show.blade.php` renders `{!! $pageHtml !!}` and `{!! $page->css ?? '' !!}`.

Impact:
Any user with `pages.manage` can potentially persist unsafe HTML/CSS if not otherwise restricted.

2. No revision or rollback table in active DB.

Evidence:
Runtime schema check: `page_builder_revisions` missing.

Impact:
Accidental save can overwrite prior content with no native recovery.

3. No autosave.

Evidence:
No autosave route or JS timer found; save is form submit only.

Impact:
Browser/network interruption can lose unsaved work.

4. No AJAX save.

Evidence:
`PageController@update` redirects after save.

Impact:
Save reloads the editor and interrupts editing flow.

5. Builder media picker is incomplete.

Evidence:
`media` control becomes text in JS mapping; media library exists separately.

Impact:
Editors must manually paste media URLs inside builder controls.

6. Legacy builder modules may confuse architecture.

Evidence:
`modules/PageBuilder` and `modules/front-builder` exist, but active DB/routes do not show their current use.

Impact:
Future work can accidentally target the wrong builder layer.

7. Static template import is not a true page-builder conversion pipeline.

Evidence:
Core builder can load HTML, but no importer/converter was found that maps arbitrary static HTML into platform element schemas.

Impact:
Uploaded HTML templates may render but remain hard to edit in structured controls.

---

## 17. Recommended Next Architecture Steps

1. Freeze the current builder boundary.

Define the active builder as:

`Admin Pages Builder`

Backed by:

`platform_pages`

Routes:

`admin.pages.*`

2. Deprecate or isolate legacy builders.

Mark `modules/PageBuilder` and `modules/front-builder` as legacy/inactive unless intentionally reactivated.

3. Add revision storage.

Recommended table:

`platform_page_revisions`

Suggested fields:

- `id`
- `page_id`
- `title`
- `html`
- `css`
- `page_builder_json`
- `meta`
- `created_by`
- `created_at`

4. Add AJAX save and autosave.

Suggested endpoints:

- `PATCH admin/pages/{page}/autosave`
- `PATCH admin/pages/{page}/builder-save`

5. Add server-side sanitizer policy.

Suggested approach:

- Allow trusted super-admin raw HTML if required.
- Restrict custom HTML for lower roles.
- Sanitize attributes, scripts, iframes, and unsafe URLs server-side.

6. Integrate media picker into builder controls.

Use existing `admin.media.index` JSON capability as the base.

7. Add template import/conversion layer.

For static HTML templates:

- Unpack safely.
- Rewrite local assets.
- Detect sections.
- Add `data-pb-widget` wrappers where possible.
- Store converted pages in `platform_pages`.
- Keep original source readonly.

8. Add tests.

Recommended tests:

- Admin pages CRUD authorization.
- Save builder JSON/HTML/CSS.
- Public only renders published pages.
- Header/footer render order.
- Media metadata update/delete.
- Sanitization policy.

---

## 18. Evidence Appendix

Commands executed for audit:

```bash
php artisan route:list
```

Filtered runtime route introspection was used for page/media routes.

Temporary read-only schema script checked:

- `platform_pages`
- `platform_media_metadata`
- `front_builder_pages`
- `page_builder_pages`
- `page_builder_sections`
- `page_builder_blocks`
- `page_builder_templates`
- `page_builder_revisions`
- `plugins`

No migrations were run.
No database rows were changed.
No application code was edited.

Key evidence files:

- `routes/web.php`
- `app/Http/Controllers/Admin/PageController.php`
- `app/Http/Controllers/PageController.php`
- `resources/views/admin/pages/edit.blade.php`
- `resources/views/admin/pages/index.blade.php`
- `resources/views/frontend/pages/show.blade.php`
- `resources/views/components/page-builder-focus-layout.blade.php`
- `public/vendor/front-builder/page-builder/page-builder.js`
- `public/vendor/front-builder/page-builder/page-builder.css`
- `app/Platform/Core/PageBuilder/PageBuilderWidgetRegistry.php`
- `app/Platform/Core/PageBuilder/PageBuilderDynamicSourceRegistry.php`
- `app/Platform/Core/PageBuilder/PageBuilderRenderService.php`
- `app/Platform/Core/Rendering/PlatformContentRenderer.php`
- `app/Http/Controllers/Admin/MediaController.php`
- `resources/views/admin/media/index.blade.php`
- `app/Platform/Core/Services/PermissionManager.php`
- `modules/PageBuilder/module.json`
- `modules/front-builder/routes/web.php`

Final decision:

The Page Builder is functional for basic admin page creation, editing, saving, previewing, and public rendering.

It should be treated as a working MVP, not a finished production-grade builder.

Ready for demo content:
`Yes, with caution`

Ready for real editorial production:
`No, not before revisions, sanitization, autosave/AJAX save, media-picker integration, and template conversion are completed.`
