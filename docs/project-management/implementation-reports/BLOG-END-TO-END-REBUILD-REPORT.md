# Blog End-to-End Rebuild Report

Date: 2026-06-28

## Objective

Rebuild the Blog Posts plugin as a real end-to-end publishing system instead of a visual-only WordPress-like UI.

## Backup

Server backup:

```text
/root/codex-backups/blog-end-to-end-rebuild-20260628-054956
```

## Files Changed

- `/var/www/store.z4rank.com/laravel/modules/Blog/module.json`
- `/var/www/store.z4rank.com/laravel/modules/Blog/routes/admin.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/routes/web.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/src/BlogServiceProvider.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/src/Http/Controllers/Admin/PostController.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/src/Http/Controllers/Admin/CategoryController.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/src/Http/Controllers/BlogController.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/src/Models/Post.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/src/Models/Category.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/src/Models/Tag.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/src/Models/Media.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/src/Models/Revision.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/src/Models/PostMeta.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/resources/views/admin/posts/form.blade.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/resources/views/admin/posts/index.blade.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/resources/views/frontend/show.blade.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/resources/views/frontend/index.blade.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/resources/views/frontend/category.blade.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/resources/views/frontend/tag.blade.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/resources/views/frontend/partials/post-grid.blade.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/docs/plugin.md`

## Migration Added

- `2026_06_28_070000_rebuild_blog_editor_storage.php`

The migration adds:

- missing post columns: `password`, `scheduled_at`, `author_id`, `featured_image_id`, `template`, `layout`, `seo_title`, `seo_description`, `focus_keyword`, `schema_type`, `deleted_at`
- `blog_media`
- `blog_post_revisions`
- `blog_post_meta`

## Routes Added

Admin:

- `GET /admin/plugins/blog/posts`
- `GET /admin/plugins/blog/posts/create`
- `POST /admin/plugins/blog/posts`
- `GET /admin/plugins/blog/posts/{post}/edit`
- `PUT/PATCH /admin/plugins/blog/posts/{post}`
- `DELETE /admin/plugins/blog/posts/{post}`
- `GET /admin/plugins/blog/posts/{post}/preview`
- `POST /admin/plugins/blog/posts/autosave`
- `POST /admin/plugins/blog/posts/slug`
- `POST /admin/plugins/blog/posts/{post}/revisions/{revision}/restore`
- `GET /admin/plugins/blog/media`
- `POST /admin/plugins/blog/media`
- `PATCH /admin/plugins/blog/media/{media}`
- `DELETE /admin/plugins/blog/media/{media}`
- `POST /admin/plugins/blog/categories/quick`

Frontend:

- `GET /blog`
- `GET /blog/{slug}`
- `GET /blog/category/{slug}`
- `GET /blog/tag/{slug}`

All new routes, controllers, and functions were registered in `module.json` and in the platform registry tables.

## Editor Implementation

- TinyMCE visual editor is loaded from jsDelivr.
- Code tab edits raw HTML.
- Visual and Code tabs synchronize before save, preview, autosave, and publish.
- Add Media opens a real modal.
- Media upload writes to the Laravel public disk and creates `blog_media` rows.
- Media can be inserted into the editor or set as featured image.
- SEO score/checklist updates live in JavaScript and saves to the database.
- Categories can be created from the post editor via Ajax.
- Tags are created/synced on save.
- Revisions are stored and can be restored.
- Autosave stores revision records.

## Verification

Passed:

- PHP syntax check for Blog PHP files.
- `php artisan migrate --force --no-ansi`.
- `php artisan optimize:clear --no-ansi`.
- `php artisan view:cache --no-ansi`.
- `php artisan route:cache --no-ansi`.
- `php artisan config:cache --no-ansi`.
- `/blog` returns HTTP 200.
- `/admin/plugins/blog/posts/create` returns 302 to login for guests.
- Laravel rendered the Blog create view successfully.
- Smoke checks verified:
  - create draft data path
  - publish path
  - scheduled post hidden until time
  - private post hidden publicly
  - category relation saved
  - tag relation saved
  - revision saved
  - frontend article renders HTML
  - schema output exists
  - script tag stripped
  - template/layout saved
  - media upload creates `blog_media`
  - media can be set as featured image
  - featured image renders on frontend
- Visual screenshot captured from rendered admin editor:

```text
D:\codex_progects\inpa-server-proxmox\remote-edit\blog-rebuild\rendered-blog-editor-v120.png
```

## Test Suite Note

The full existing `php artisan test --no-ansi` suite was run and failed in unrelated Auth/AI tests. Observed failures included CSRF 419 responses and existing Spatie `RoleAlreadyExists` errors in `AiIntentRouterTest`. These failures were outside the Blog plugin smoke checks and are documented as existing test-suite risk.

## Cleanup

Temporary Codex smoke-test posts, test media, test tag, and test category were removed after verification.

## Not Completed

- TinyMCE is loaded from CDN, not vendored locally.
- A dedicated PHPUnit Blog feature test file was not added because the current production test suite is already failing in unrelated Auth/AI areas; manual Blog smoke verification was documented instead.
- Script execution inside content remains disabled by default. The code only allows a future super-admin DB setting path; no public script execution was enabled.

## Rollback

```bash
cp -a /root/codex-backups/blog-end-to-end-rebuild-20260628-054956/Blog /var/www/store.z4rank.com/laravel/modules/Blog
cd /var/www/store.z4rank.com/laravel
php artisan optimize:clear
```

Database rollback would require manually reversing the migration or restoring from a database backup if data created after this deployment must be removed.

## Credential Handling

Server access used existing project credentials from `passwords.txt`. No plaintext secret was copied into project documentation, reports, logs, commits, or public output.
