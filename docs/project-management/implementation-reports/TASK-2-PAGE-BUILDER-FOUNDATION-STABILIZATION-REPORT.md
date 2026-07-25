# Task 2 - Page Builder Foundation Stabilization Report

## Task Title
Page Builder Foundation Stabilization.

## Objective
Stabilize the active Admin Pages Builder before template-based editing without rebuilding it from scratch.

The active builder remains:

- Admin Pages Builder
- Route namespace: `admin.pages.*`
- Storage table: `platform_pages`
- Main editor view: `resources/views/admin/pages/edit.blade.php`
- Main editor JavaScript: `public/vendor/front-builder/page-builder/page-builder.js`

## Files Created
- `app/Platform/Core/PageBuilder/BuilderSanitizer.php`
- `database/migrations/2026_06_29_000001_create_platform_page_revisions_table.php`
- `docs/project-management/PAGE-BUILDER-ACTIVE-BOUNDARY.md`
- `tests/Unit/PageBuilderSanitizerTest.php`
- `docs/project-management/implementation-reports/TASK-2-PAGE-BUILDER-FOUNDATION-STABILIZATION-REPORT.md`

## Files Modified
- `app/Http/Controllers/Admin/PageController.php`
- `routes/web.php`
- `resources/views/admin/pages/edit.blade.php`
- `public/vendor/front-builder/page-builder/page-builder.js`
- `public/vendor/front-builder/page-builder/page-builder.css`

## Migration Added
Migration:

`2026_06_29_000001_create_platform_page_revisions_table.php`

Table:

`platform_page_revisions`

Columns:

- `id`
- `page_id`
- `title`
- `html`
- `css`
- `page_builder_json`
- `meta`
- `created_by`
- `created_at`

## Routes Added
The following routes were added under the existing `permission:pages.manage` admin boundary:

- `PATCH admin/pages/{page}/builder-save`
  - Name: `admin.pages.builder-save`
  - Controller: `PageController@builderSave`

- `PATCH admin/pages/{page}/autosave`
  - Name: `admin.pages.autosave`
  - Controller: `PageController@autosave`

- `GET admin/pages/{page}/revisions`
  - Name: `admin.pages.revisions.index`
  - Controller: `PageController@revisions`

- `POST admin/pages/{page}/revisions/{revision}/restore`
  - Name: `admin.pages.revisions.restore`
  - Controller: `PageController@restoreRevision`

The original fallback route remains active:

- `PATCH admin/pages/{page}`
  - Name: `admin.pages.update`
  - Controller: `PageController@update`

## Controller Methods Added or Changed
Controller:

`app/Http/Controllers/Admin/PageController.php`

Added methods:

- `builderSave()`
- `autosave()`
- `revisions()`
- `restoreRevision()`
- `savePage()`
- `createRevisionSnapshot()`
- `revisionSummaries()`
- `allowsUnsafeBuilderMarkup()`

Changed methods:

- `edit()`
  - Now passes revision summaries to the editor view.

- `update()`
  - Still supports normal form PATCH fallback.
  - Now creates a revision before manual save.
  - Now runs server-side builder sanitization.

- `validated()`
  - Now accepts `BuilderSanitizer`.
  - Sanitizes `html` and `css` before saving.

## JavaScript Functions Added or Changed
File:

`public/vendor/front-builder/page-builder/page-builder.js`

Added or changed behavior:

- AJAX manual save support.
- Autosave support.
- Dirty-state tracking.
- Save status messages.
- Revision restore button binding.
- Media picker integration for image controls.
- Media URL selection from the existing media library JSON endpoint.
- Image alt text support on image controls.

Main functions added:

- `setSaveStatus()`
- `fetchMediaItems()`
- `openMediaPicker()`
- `closeMediaPicker()`
- `serializeBuilder()`
- `formPayload()`
- `refreshRevisions()`
- `submitBuilder()`
- `scheduleAutosave()`
- `markDirty()`
- `bindRevisionButtons()`

Changed functions:

- `schemaTraitType()`
  - Maps media traits to visual media controls.

- `createControlInput()`
  - Adds media picker UI for media fields.

- `applyControlValue()`
  - Marks the builder dirty after control updates.

## Database Storage
Active page data remains stored in:

`platform_pages`

Revision snapshots are stored in:

`platform_page_revisions`

No builder data was moved to another table.

Legacy modules were not deleted or reactivated.

## Exact Manual Save Flow
1. User edits the page in the existing Admin Pages Builder.
2. JavaScript marks the builder as dirty.
3. User clicks Save.
4. JavaScript serializes:
   - title
   - slug
   - status
   - content type
   - block key
   - content
   - html
   - css
   - page builder JSON
   - SEO title
   - meta description
5. Browser sends AJAX request to:
   - `PATCH admin/pages/{page}/builder-save`
6. Server validates the payload.
7. Server sanitizes HTML and CSS based on the current user's role.
8. Server creates a revision snapshot of the current page before writing changes.
9. Server updates `platform_pages`.
10. Server returns JSON with the updated page metadata and revision list.
11. UI shows saved state without leaving the current editor position.

## Exact Autosave Flow
1. User changes builder content.
2. JavaScript marks the page dirty.
3. Autosave timer starts only when dirty changes exist.
4. After the delay, JavaScript sends AJAX request to:
   - `PATCH admin/pages/{page}/autosave`
5. Server validates and sanitizes the draft builder payload.
6. Server saves builder HTML, CSS, JSON, and draft metadata.
7. Autosave does not create a revision snapshot.
8. Autosave does not publish a draft page.
9. UI shows an autosaved status message.

## Exact Revision Restore Flow
1. User opens Page Settings in the builder.
2. Recent revisions are listed.
3. User clicks Restore on a revision.
4. JavaScript sends POST request to:
   - `admin/pages/{page}/revisions/{revision}/restore`
5. Server verifies the revision belongs to the current page.
6. Server creates a safety snapshot before restore.
7. Server restores:
   - title
   - html
   - css
   - page builder JSON
   - SEO title
   - meta description
8. Server returns JSON.
9. Browser reloads the editor to show the restored content.

## Security and Sanitization Behavior
Service:

`App\Platform\Core\PageBuilder\BuilderSanitizer`

For non-super-admin users:

- Removes `<script>` tags.
- Removes unsafe inline event attributes such as `onclick`.
- Removes unsafe `javascript:` URLs.
- Removes unsafe `vbscript:` URLs.
- Removes unsafe `data:` URLs in link and media attributes.
- Removes unsafe iframes.
- Allows iframe URLs only from safe known hosts:
  - YouTube
  - Vimeo
  - Google
  - OpenStreetMap
- Strips unsafe CSS patterns:
  - `@import`
  - `expression()`
  - `javascript:`
  - `behavior:`
  - `-moz-binding:`

For super-admin users:

- Existing trusted builder markup can be kept.
- The override is explicit and role-based.

## Media Picker Integration
The builder now uses the existing media library capability.

Endpoint:

`admin.media.index`

When requested as JSON, it returns media `items`.

Builder behavior:

- Image/media controls show a visual `Choose Image` button.
- The user can select an existing media item.
- The selected image URL is applied to the component.
- Alt text can be edited when an image control exists.
- The existing media library page remains unchanged.

## Manual Test Steps
1. Open an existing page editor:
   - `/admin/pages/{id}/edit`
2. Change content in the builder.
3. Confirm save state changes to unsaved.
4. Click Save.
5. Confirm the editor stays on the same page and shows saved state.
6. Confirm a new row is created in `platform_page_revisions`.
7. Change content again and wait for autosave.
8. Confirm autosave status appears.
9. Open Page Settings.
10. Restore a revision.
11. Confirm page reloads with restored content.
12. Add or select an image control.
13. Click Choose Image.
14. Select an image from the media library.
15. Confirm URL and alt text behavior.
16. Attempt unsafe HTML as non-super-admin and confirm sanitizer strips unsafe content.

## Verification Performed
- JavaScript syntax check:
  - `node --check public/vendor/front-builder/page-builder/page-builder.js`

- PHP syntax checks:
  - `php -l app/Http/Controllers/Admin/PageController.php`
  - `php -l app/Platform/Core/PageBuilder/BuilderSanitizer.php`
  - `php -l database/migrations/2026_06_29_000001_create_platform_page_revisions_table.php`
  - `php -l routes/web.php`
  - `php -l tests/Unit/PageBuilderSanitizerTest.php`

- Migration:
  - `php artisan migrate --force`
  - Result: migration ran successfully.

- Route verification:
  - `php artisan route:list --path=admin/pages`
  - Result: builder-save, autosave, revisions list, and revision restore routes are registered.

- Sanitizer test:
  - `php artisan test --filter=PageBuilderSanitizerTest`
  - Result: 2 tests passed, 7 assertions passed.

- Cache:
  - `php artisan optimize:clear`
  - Result: config, cache, compiled, events, routes, and views cleared.

## Known Limitations
- Autosave stores the current builder payload in `platform_pages`; it does not yet use a separate draft table.
- Autosave does not create revision snapshots by design.
- Revision restore reloads the editor after success to keep the UI state simple and reliable.
- Media picker uses the existing media library endpoint and does not introduce a full media modal editor inside the builder.
- Template import was intentionally not implemented in this task.

## Rollback Notes
To roll back this task:

1. Revert the modified files listed above.
2. Remove the added service and test files.
3. Roll back the migration:
   - `php artisan migrate:rollback --path=database/migrations/2026_06_29_000001_create_platform_page_revisions_table.php`
4. Clear Laravel cache:
   - `php artisan optimize:clear`

Do not delete `platform_pages`.

## Final Status
Task 2 was implemented within the existing Admin Pages Builder boundary.

The builder was stabilized with:

- active boundary documentation
- revision storage
- AJAX save
- autosave
- role-aware sanitization
- media picker integration
- verification tests
