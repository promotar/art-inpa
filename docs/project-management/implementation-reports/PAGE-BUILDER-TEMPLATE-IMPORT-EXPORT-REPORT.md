# Page Builder Template Import/Export Report

Date: 2026-06-29

## Objective

Add Page Builder template export and upload/import for admin pages.

## User-Facing Behavior

- `Export Template` downloads the current page builder design as JSON.
- `Upload Template` accepts a JSON template file and applies its builder project, HTML, and CSS to the current page.
- Upload keeps the page title, slug, content type, status, sort order, SEO title, and meta description unchanged.
- Upload creates a revision snapshot before replacing builder content through the existing `savePage` workflow.

## Template Format

```json
{
  "schema_version": "page-builder-template/v1",
  "exported_at": "ISO-8601 timestamp",
  "source": {
    "id": 1,
    "title": "Page title",
    "slug": "page-slug",
    "content_type": "page"
  },
  "template": {
    "page_builder_json": {},
    "html": "",
    "css": ""
  }
}
```

The importer also accepts compatible raw GrapesJS project JSON containing project-like keys such as `pages`, `styles`, or `assets`.

## Files Changed

```text
app/Http/Controllers/Admin/PageController.php
routes/web.php
resources/views/admin/pages/edit.blade.php
public/vendor/front-builder/page-builder/page-builder.css
/var/www/store.z4rank.com/public_html/vendor/front-builder/page-builder/page-builder.css
```

## Routes Added

```text
GET  /admin/pages/{page}/template/export  admin.pages.template.export
POST /admin/pages/{page}/template/import  admin.pages.template.import
```

Both routes remain inside the existing `permission:pages.manage` group.

## Backup

```text
/root/codex-backups/page-builder-template-import-export-20260629-120045
```

## Verification

```text
php -l app/Http/Controllers/Admin/PageController.php: passed
php -l routes/web.php: passed
php -l resources/views/admin/pages/edit.blade.php: passed
php artisan optimize:clear --no-ansi: passed
php artisan view:cache --no-ansi: passed
php artisan route:cache --no-ansi: passed
php artisan config:cache --no-ansi: passed
CSS hash matches between Laravel public and public_html: passed
Route list shows template export/import routes: passed
Smoke test: template-export-import-ok page=1 redirect=http://store.z4rank.com/admin/pages/1/edit
Smoke import rollback: passed
Temporary verification script removed: passed
```

## Rollback

```text
cd /var/www/store.z4rank.com/laravel
cp /root/codex-backups/page-builder-template-import-export-20260629-120045/app/Http/Controllers/Admin/PageController.php app/Http/Controllers/Admin/PageController.php
cp /root/codex-backups/page-builder-template-import-export-20260629-120045/routes/web.php routes/web.php
cp /root/codex-backups/page-builder-template-import-export-20260629-120045/resources/views/admin/pages/edit.blade.php resources/views/admin/pages/edit.blade.php
cp /root/codex-backups/page-builder-template-import-export-20260629-120045/public/vendor/front-builder/page-builder/page-builder.css public/vendor/front-builder/page-builder/page-builder.css
cp /root/codex-backups/page-builder-template-import-export-20260629-120045/public_html/vendor/front-builder/page-builder/page-builder.css /var/www/store.z4rank.com/public_html/vendor/front-builder/page-builder/page-builder.css
php artisan optimize:clear --no-ansi
php artisan view:cache --no-ansi
php artisan route:cache --no-ansi
php artisan config:cache --no-ansi
```
