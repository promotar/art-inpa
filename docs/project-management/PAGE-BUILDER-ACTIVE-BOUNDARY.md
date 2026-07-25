# Page Builder Active Boundary

Date: 2026-06-29

## Active Builder

The active page builder is the Admin Pages Builder.

Active routes:

- `admin.pages.index`
- `admin.pages.store`
- `admin.pages.edit`
- `admin.pages.update`
- `admin.pages.builder-save`
- `admin.pages.autosave`
- `admin.pages.revisions.index`
- `admin.pages.revisions.restore`
- `admin.pages.preview`
- `admin.pages.destroy`

Active storage:

- `platform_pages`
- `platform_page_revisions`

Main editor view:

- `resources/views/admin/pages/edit.blade.php`

Main editor JavaScript:

- `public/vendor/front-builder/page-builder/page-builder.js`

Main editor CSS:

- `public/vendor/front-builder/page-builder/page-builder.css`

## Legacy Builders

The following modules are legacy/inactive for the current active builder unless explicitly reactivated in a future approved task:

- `modules/PageBuilder`
- `modules/front-builder`

Do not delete these modules as part of builder stabilization.

Do not move the active builder away from `platform_pages` unless a future architecture task explicitly approves that change.

## Current Rule

All stabilization work must extend the current Admin Pages Builder boundary.

Template import, template conversion, marketplace theme loading, or a full builder rebuild are outside this boundary.
