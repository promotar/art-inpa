# Blog Editor Validation UX and Public Preview Fix

Date: 2026-06-28

## Objective

Fix Blog editor behavior so invalid input does not fail silently, remove nonfunctional UI controls, and make published posts use a public crawlable route instead of an admin preview URL. Also fix the public article 500 error.

## Root Cause

- The Blog form displayed only a generic validation message and did not highlight the invalid field or scroll to it.
- Several controls were visible without complete behavior or persistence.
- Admin preview always used `/admin/plugins/blog/posts/{id}/preview`, even for published public posts.
- Public Blog pages failed because the frontend layout required `App\Platform\Core\Theme\FrontendStyleBundle`, which was not present.
- Article JSON-LD used raw `@context` keys in Blade and rendered invalid schema output.

## Changes

- Added validation summary links, field-level error messages, invalid field styling, and automatic scroll/focus.
- Removed nonfunctional Screen Options, Help, Add Gallery, fake SEO tabs, Pillar Content, and unused quicktags.
- Kept only visible editor controls that perform real work.
- Redirected published public post preview to `/blog/{slug}`.
- Added `View Public Post` in the editor for publicly viewable posts.
- Made frontend style bundle loading optional and fail-safe.
- Fixed article JSON-LD schema keys.
- Prevented duplicate default SEO meta tags when custom page head content exists.

## Files Changed

```text
modules/Blog/resources/views/admin/posts/form.blade.php
modules/Blog/src/Http/Controllers/Admin/PostController.php
modules/Blog/resources/views/frontend/show.blade.php
resources/views/components/frontend-layout.blade.php
resources/views/layouts/frontend.blade.php
project.txt
project_documentation.md
changes-log.txt
backups-log.txt
connection-method.txt
```

## Backups

```text
/root/codex-backups/blog-editor-ux-preview-fix-20260628-192355
/root/codex-backups/blog-schema-jsonld-fix-20260628-192745
/root/codex-backups/frontend-layout-seo-head-fix-20260628-192901
```

## Verification

```text
php -l modules/Blog/src/Http/Controllers/Admin/PostController.php
php artisan optimize:clear
```

- Blog editor validation UI renders and marks invalid fields.
- Removed fake controls no longer appear in the rendered editor.
- Published post preview redirects to `/blog/mrhb`.
- `http://10.10.0.20/blog/mrhb` returns HTTP 200.
- Public post page has one robots tag with `index, follow`.
- Public post page has canonical URL.
- JSON-LD contains valid `@context` and `@type`.

## Rollback

Restore the listed files from the backup directories and run:

```text
cd /var/www/store.z4rank.com/laravel
php artisan optimize:clear --no-ansi
```

## Credentials

Server access used existing project credentials from `passwords.txt`. No plaintext credential values were copied into this report.
