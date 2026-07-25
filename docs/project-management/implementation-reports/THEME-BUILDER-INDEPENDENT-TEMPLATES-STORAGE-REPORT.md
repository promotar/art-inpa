# Theme Builder Independent Templates Storage Report

## Task Title
Theme Builder independent template storage and Templates tab.

## Objective
Allow uploading and storing multiple Theme Builder templates for each dynamic layout type without saving them in `platform_pages`.

## Requirement Summary
Theme Builder templates should be separate from normal Pages. The user can upload or create multiple templates for Header, Footer, Single Post, Single Page, Archive, Search Results, and 404 Page, then assign display conditions to each template.

## Files Created
- `database/migrations/2026_07_02_000001_create_platform_theme_builder_templates_table.php`
- `resources/views/admin/theme-builder/partials/template-card.blade.php`
- `docs/project-management/implementation-reports/THEME-BUILDER-INDEPENDENT-TEMPLATES-STORAGE-REPORT.md`

## Files Modified
- `app/Http/Controllers/Admin/ThemeBuilderController.php`
- `resources/views/admin/theme-builder/index.blade.php`
- `routes/web.php`

## Database Tables Added
### `platform_theme_builder_templates`
Stores Theme Builder templates independently from `platform_pages`.

Important columns:
- `id`
- `template_type`
- `name`
- `slug`
- `description`
- `status`
- `source_type`
- `html`
- `css`
- `page_builder_json`
- `metadata`
- `created_by`
- `created_at`
- `updated_at`

### `platform_theme_builder_template_conditions`
Stores display conditions for Theme Builder templates.

Important columns:
- `id`
- `template_id`
- `operator`
- `scope`
- `target_value`
- `created_at`
- `updated_at`

## Routes Added
- `POST /admin/theme-builder/templates`
- `GET /admin/theme-builder/templates/{template}/preview`
- `PATCH /admin/theme-builder/templates/{template}/conditions`
- `DELETE /admin/theme-builder/templates/{template}`

## Routes Kept
- `GET /admin/theme-builder`

## UI Changes
- Added a new `Templates` tab inside Theme Builder.
- Added a form to create blank templates or upload template files.
- Supported upload file types:
  - JSON
  - HTML
  - HTM
  - TXT
- Dynamic template type options:
  - Header
  - Footer
  - Single Post
  - Single Page
  - Archive
  - Search Results
  - 404 Page
- Each template card includes:
  - Preview
  - Delete
  - Display condition controls

## Storage Behavior
Theme Builder templates are no longer loaded from `platform_pages`.

Normal pages such as Home, About, FAQ, Contact, and other static/content pages remain in `platform_pages`.

Dynamic Theme Builder templates are stored in:

`platform_theme_builder_templates`

Display conditions are stored in:

`platform_theme_builder_template_conditions`

## Display Conditions
Each template can have one display condition row.

Supported action values:
- `include`
- `exclude`

Supported scopes:
- Entire Site
- Front Page
- All Pages
- Specific Pages
- All Posts
- Specific Posts
- Post Categories
- Archives
- Search Results
- 404 Page

## Verification Performed
- PHP syntax check passed for `ThemeBuilderController.php`.
- PHP syntax check passed for `routes/web.php`.
- PHP syntax check passed for the new migration.
- Migration ran successfully.
- Route list confirmed Theme Builder template routes.
- Blade view cache passed.
- Render verification passed with a super-admin user.
- Confirmed Theme Builder controller/view no longer reference `platform_pages`.

## Important Notes
- This task creates the independent storage and management UI.
- It does not yet wire template selection into public rendering.
- It does not add a full visual editor for Theme Builder templates.
- Uploaded templates can be previewed and assigned conditions.
- Editing template content after upload should be implemented as a separate focused task.

## Rollback Notes
To roll back, remove the new routes and restore the previous `ThemeBuilderController` and view. The new migration can be rolled back with `php artisan migrate:rollback` if no production templates have been stored yet.
