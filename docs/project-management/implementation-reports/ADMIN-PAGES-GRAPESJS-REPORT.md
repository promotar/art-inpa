# Admin Pages GrapesJS Builder Report

Date: 2026-06-26

## Objective

Update `/admin/pages` so it displays a pages table with View, Edit, and Delete actions, creates new draft pages from a top button, and provides a full-screen GrapesJS editor that stores page builder data in the database.

## Backup

Backup directory:

```text
/root/codex-backups/admin-pages-grapesjs-20260625-231628
```

## Database Changes

Added these columns to `platform_pages`:

- `page_builder_json`
- `html`
- `css`

The page builder flow now stores:

```text
GrapesJS Editor
-> platform_pages.page_builder_json
-> platform_pages.html
-> platform_pages.css
-> frontend render
```

## Route Changes

- `GET /admin/pages`
- `POST /admin/pages`
- `GET /admin/pages/{page}/edit`
- `PATCH /admin/pages/{page}`
- `GET /admin/pages/{page}/preview`
- `DELETE /admin/pages/{page}`
- `GET /pages/{slug}`

## Behavior

- `/admin/pages` now shows a table.
- The top Create Page button creates a draft page and redirects to the builder editor.
- View opens an admin preview route so drafts can be viewed safely.
- Edit opens a large GrapesJS builder surface inside the admin layout.
- Delete removes the page from `platform_pages`.
- Published public pages render from `/pages/{slug}`.

## Changed Files

- `routes/web.php`
- `app/Http/Controllers/Admin/PageController.php`
- `app/Http/Controllers/PageController.php`
- `resources/views/admin/pages/index.blade.php`
- `resources/views/admin/pages/edit.blade.php`
- `resources/views/frontend/pages/show.blade.php`
- `database/migrations/2026_06_26_000004_add_builder_columns_to_platform_pages_table.php`

## Verification

- PHP syntax checks passed.
- Migration completed successfully.
- `php artisan route:list --path=admin/pages --no-ansi` shows the admin pages routes.
- `php artisan route:list --path=pages --no-ansi` shows `pages.show`.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- Internal transaction render check: `page_views_rendered=yes`.
- Production caches rebuilt:
  - `php artisan config:cache --no-ansi`
  - `php artisan route:cache --no-ansi`
  - `php artisan view:cache --no-ansi`
- HTTP checks:
  - `/`: 200
  - `/login`: 200
  - `/admin/pages`: 302 unauthenticated redirect, expected admin protection.
