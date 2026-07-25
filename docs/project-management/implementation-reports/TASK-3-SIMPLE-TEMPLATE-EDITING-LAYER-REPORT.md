# Task 3 - Simple Template Editing Layer Report

## Task Title
Simple Template Editing Layer.

## Objective
Add a locked-template editing layer for non-technical users without rebuilding the existing GrapesJS Admin Pages Builder.

The active builder remains:

- Admin Pages Builder
- Routes: `admin.pages.*`
- Storage table: `platform_pages`
- Main editor view: `resources/views/admin/pages/edit.blade.php`
- Main editor JS: `public/vendor/front-builder/page-builder/page-builder.js`

## Files Created
- `app/Platform/Core/PageBuilder/TemplateEditableRenderer.php`
- `tests/Unit/TemplateEditableRendererTest.php`
- `docs/project-management/implementation-reports/TASK-3-SIMPLE-TEMPLATE-EDITING-LAYER-REPORT.md`

## Files Modified
- `app/Http/Controllers/Admin/PageController.php`
- `app/Platform/Core/PageBuilder/PageBuilderRenderService.php`
- `routes/web.php`
- `resources/views/admin/pages/edit.blade.php`
- `public/vendor/front-builder/page-builder/page-builder.js`
- `public/vendor/front-builder/page-builder/page-builder.css`

## Routes Added
- `PATCH admin/pages/{page}/template-edit-save`
  - Name: `admin.pages.template-edit-save`
  - Controller: `PageController@templateEditSave`

Existing routes remain unchanged:

- `PATCH admin/pages/{page}/builder-save`
- `PATCH admin/pages/{page}/autosave`
- `GET admin/pages/{page}/template/export`
- `POST admin/pages/{page}/template/import`
- `PATCH admin/pages/{page}`

## Services and Classes Added
### `TemplateEditableRenderer`
Path:

`app/Platform/Core/PageBuilder/TemplateEditableRenderer.php`

Responsibilities:

- Read `page_builder_json`.
- Detect `editable_schema`.
- Detect editable sections from `data-pb-section` markup when no schema exists.
- Normalize schema sections and fields.
- Validate `editable_data`.
- Validate section visibility and order.
- Apply editable data to template HTML.
- Apply button text and links.
- Apply image source and alt text.
- Apply section visibility.
- Apply simple section order.
- Return final HTML for preview and public rendering.

## Controller Methods Added or Changed
### Added
- `templateEditSave()`
- `canUseFullBuilder()`
- `jsonInput()`

### Changed
- `edit()`
  - Now passes `simpleEditor`, `simpleModeEnabled`, and `fullBuilderAllowed` to the editor view.

- `exportTemplate()`
  - Now includes `template.editable_schema` when available.

- `templateContentFromPayload()`
  - Now accepts `editable_schema` from template JSON and stores it inside `page_builder_json`.

## JS Functions Added or Changed
File:

`public/vendor/front-builder/page-builder/page-builder.js`

Added simple editor behavior:

- Detects `window.PageBuilderConfig.simpleEditor.enabled`.
- If simple mode is enabled, it does not initialize GrapesJS.
- Renders Page Sections panel.
- Renders Edit Section panel.
- Renders Preview Canvas updates.
- Supports hide/show section.
- Supports move up/down when allowed.
- Supports reset section.
- Supports text, textarea, URL, button, image, toggle, and select controls.
- Supports visual image selection from the existing media JSON endpoint.
- Saves via AJAX to `admin.pages.template-edit-save`.

## Storage Format
Storage remains in:

`platform_pages.page_builder_json`

The following keys are stored inside the JSON:

```json
{
  "template_key": "service-page-01",
  "editable_schema": {},
  "editable_data": {},
  "section_visibility": {},
  "section_order": []
}
```

No new database table was added.

`platform_pages` remains the active storage table.

## Example Editable Schema
```json
{
  "template_key": "service-page-01",
  "template_name": "Service Page",
  "sections": [
    {
      "key": "hero",
      "label": "Hero",
      "locked_layout": true,
      "visible": true,
      "allow_hide": true,
      "allow_reorder": false,
      "allow_duplicate": false,
      "fields": [
        {
          "key": "title",
          "label": "Title",
          "type": "text",
          "default": "Default title",
          "required": true,
          "validation": {
            "max": 120
          }
        },
        {
          "key": "subtitle",
          "label": "Subtitle",
          "type": "textarea",
          "default": "Default subtitle"
        },
        {
          "key": "button",
          "label": "Button",
          "type": "button",
          "default": {
            "text": "Contact Us",
            "url": "/contact"
          }
        },
        {
          "key": "image",
          "label": "Image",
          "type": "image",
          "default": {
            "src": "/image.webp",
            "alt": ""
          }
        }
      ]
    }
  ]
}
```

## Example Editable Data
```json
{
  "template_key": "service-page-01",
  "editable_data": {
    "hero": {
      "title": "SEO Services in Jordan",
      "subtitle": "Grow with optimized pages",
      "button": {
        "text": "Contact Us",
        "url": "/contact"
      },
      "image": {
        "src": "/storage/media/hero.webp",
        "alt": "SEO team"
      }
    }
  },
  "section_visibility": {
    "hero": true
  },
  "section_order": [
    "hero"
  ]
}
```

## Render Flow
1. Public page or preview calls `PageBuilderRenderService`.
2. `PageBuilderRenderService` reads page HTML and `page_builder_json`.
3. `TemplateEditableRenderer` checks for `editable_schema` or `data-pb-section` markup.
4. If no editable schema exists, original HTML is returned.
5. If schema exists, editable data is applied to `data-pb-field` elements.
6. Section visibility is applied.
7. Section order is applied when available.
8. The rendered HTML then continues through existing dynamic field rendering.

## Edit Flow
1. User opens `/admin/pages/{id}/edit`.
2. Controller detects whether the page has an editable template.
3. Admin or super-admin continues to see the full GrapesJS builder.
4. Non-admin users with an editable template see the simple editor.
5. User edits allowed fields only.
6. User saves.
7. Browser sends AJAX request to:
   - `PATCH admin/pages/{page}/template-edit-save`
8. Server validates editable fields.
9. Server updates `page_builder_json`.
10. Server renders final HTML.
11. Existing revision logic creates a snapshot before saving.
12. Server returns JSON response.

## Permissions Behavior
- Admin and super-admin can use the full builder.
- Non-admin users use simple edit mode when the page has `editable_schema`.
- Existing pages without `editable_schema` continue to open in full builder mode.
- Import/export remains protected by the existing `pages.manage` route group.

## Validation Behavior
Implemented validation:

- Required text fields.
- Max text length.
- Safe URL validation.
- Safe image URL validation.
- Button text and URL validation.
- Repeater child field validation.
- Section visibility normalization.
- Section order normalization.

Unsafe `javascript:` URLs are rejected.

## Backward Compatibility
- Existing GrapesJS builder remains installed and active.
- Existing `builder-save`, `autosave`, revisions, restore, media picker, sanitizer, import, and export routes remain available.
- Existing pages without editable schemas are not forced into simple mode.
- `platform_pages` remains the active storage table.
- No ZIP or static HTML import was added in this task.

## Manual Browser Test Steps
1. Import or create a template with `editable_schema`.
2. Login as admin or super-admin.
3. Open `/admin/pages/{id}/edit`.
4. Confirm full GrapesJS builder still opens.
5. Login as a non-admin user with `pages.manage`.
6. Open the same page.
7. Confirm simple editor appears.
8. Edit text field.
9. Edit button text and URL.
10. Choose an image from media.
11. Hide and show a section.
12. Save.
13. Confirm no full page reload occurs.
14. Preview page.
15. Confirm rendered HTML reflects editable data.
16. Try `javascript:alert(1)` as button URL.
17. Confirm save is rejected.

## Verification Performed
- PHP syntax checks:
  - `php -l app/Http/Controllers/Admin/PageController.php`
  - `php -l app/Platform/Core/PageBuilder/PageBuilderRenderService.php`
  - `php -l app/Platform/Core/PageBuilder/TemplateEditableRenderer.php`
  - `php -l routes/web.php`
  - `php -l tests/Unit/TemplateEditableRendererTest.php`

- JavaScript syntax check:
  - `node --check public/vendor/front-builder/page-builder/page-builder.js`

- Route verification:
  - `php artisan route:list --path=admin/pages`
  - Confirmed `admin.pages.template-edit-save`.

- Tests:
  - `php artisan test --filter=TemplateEditableRendererTest`
  - Result: 2 tests passed, 6 assertions.
  - `php artisan test --filter=PageBuilderSanitizerTest`
  - Result: 2 tests passed, 7 assertions.

- Cache:
  - `php artisan optimize:clear`
  - Result: config, cache, compiled, events, routes, and views cleared.

## Known Limitations
- This is intentionally not an Elementor clone.
- The simple editor supports locked template fields only.
- Duplicate section UI is not fully implemented yet, even though schema can mark `allow_duplicate`.
- Repeater rendering is supported when the HTML contains a matching `data-pb-repeatable` container and child field markup.
- Custom `selector` support expects XPath-like selectors internally; normal `data-pb-field` mapping is the recommended MVP path.
- Existing pages without editable schema continue to use full builder mode.

## Rollback Notes
To roll back this task:

1. Remove `TemplateEditableRenderer.php`.
2. Remove `TemplateEditableRendererTest.php`.
3. Revert changes to:
   - `PageController.php`
   - `PageBuilderRenderService.php`
   - `routes/web.php`
   - `edit.blade.php`
   - `page-builder.js`
   - `page-builder.css`
4. Run:
   - `php artisan optimize:clear`

No database migration rollback is needed because no table was added.

## Final Status
Task 3 was implemented as a focused simple template editing layer.

The current GrapesJS builder remains active for admins.

Locked-template editing is now available for non-technical users when a page/template includes editable schema or supported `data-pb-*` markup.
