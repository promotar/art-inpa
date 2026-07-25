# Blog Save 404 Fix Report

Date: 2026-06-28

## Issue

Saving a Blog post from the internal admin URL returned 404.

## Root Cause

The admin editor was opened through the internal host `10.10.0.20`, but Laravel generated absolute form actions and redirects using the configured application URL `store.z4rank.com`. After save, the browser followed the external host instead of staying on the internal admin host.

A second save-path issue was also found: the content sanitizer checked `platform_settings.key`, but this project uses `platform_settings.setting_key`.

## Fix

- Changed Blog admin post form actions and Ajax URLs to relative admin paths.
- Changed Blog admin post/category redirects to force relative `Location` headers.
- Updated the script-permission setting lookup to use `setting_key` and to verify required columns before querying.

## Files Changed

- `/var/www/store.z4rank.com/laravel/modules/Blog/resources/views/admin/posts/form.blade.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/resources/views/admin/posts/index.blade.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/src/Http/Controllers/Admin/PostController.php`
- `/var/www/store.z4rank.com/laravel/modules/Blog/src/Http/Controllers/Admin/CategoryController.php`

## Verification

- Blog controller PHP syntax checks passed.
- Laravel caches were rebuilt.
- Rendered create form action is now:

```text
/admin/plugins/blog/posts
```

- Store action creates the post successfully.
- Store redirect header is now relative:

```text
/admin/plugins/blog/posts/{id}/edit
```

- Test post data was removed after verification.

## Credential Handling

Server access used existing project credentials from `passwords.txt`. No plaintext secret was copied into project documentation, reports, logs, commits, or public output.
