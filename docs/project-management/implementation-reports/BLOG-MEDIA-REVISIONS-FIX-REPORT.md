# Blog Media Library, Code Tab, and Revisions Fix

Date: 2026-06-28

## Objective

Use the platform Media Library in the Blog editor, simplify image upload, fix Code tab editor overlap, and cap revisions at 4 with individual deletion.

## Root Cause

- Blog editor used Blog-specific media endpoints and a separate modal instead of the platform `admin.media.*` library.
- Upload required unnecessary separate upload/select/use steps.
- Code tab used `tinymce.hide()`, which revealed TinyMCE's original textarea and produced overlapping editors.
- Revisions were not capped in storage and had no delete action.

## Changes

- Added JSON support to the existing platform `MediaController@index` and `MediaController@store`.
- Changed Blog editor media routes to `admin.media.index` and `admin.media.store`.
- Rebuilt the Blog media modal to use Upload and Media Library tabs with immediate upload on file selection.
- Added one context-aware use button: Insert into editor or Set Featured Image.
- New featured image selections store the global media URL in `featured_image`.
- Fixed Code tab by hiding TinyMCE's container directly.
- Added `DELETE admin/plugins/blog/posts/{post}/revisions/{revision}`.
- Added `destroyRevision` controller action.
- Added registry entry `admin.plugins.blog.posts.revisions.destroy`.
- Kept only latest 4 revisions per post and pruned existing extra revisions.

## Files Changed

```text
app/Http/Controllers/Admin/MediaController.php
modules/Blog/resources/views/admin/posts/form.blade.php
modules/Blog/src/Http/Controllers/Admin/PostController.php
modules/Blog/routes/admin.php
modules/Blog/module.json
project.txt
project_documentation.md
changes-log.txt
backups-log.txt
```

## Backup

```text
/root/codex-backups/blog-media-revisions-fix-20260628-202927
```

## Verification

```text
php -l app/Http/Controllers/Admin/MediaController.php
php -l modules/Blog/src/Http/Controllers/Admin/PostController.php
php -l modules/Blog/routes/admin.php
php artisan optimize:clear
```

Live checks:

```text
media_json_index=ok
media_json_upload=ok
uses_global_media=ok
auto_upload_ui=ok
code_no_tinymce_hide=ok
revision_delete_ui=ok
max_revisions=4
```

The temporary test upload file was deleted after verification.

## Rollback

Restore changed files from:

```text
/root/codex-backups/blog-media-revisions-fix-20260628-202927
```

Then run:

```text
cd /var/www/store.z4rank.com/laravel
php artisan optimize:clear --no-ansi
```

Older revision rows were intentionally pruned. Restoring them requires a database backup.
