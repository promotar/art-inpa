# Page Builder Template Upload Fix Report

Date: 2026-06-29

## Objective

Fix the Page Builder template upload failure shown as:

```text
The template file failed to upload.
```

## Root Cause

Apache PHP was still using PHP's default upload limit:

```text
upload_max_filesize=2M
post_max_size=8M
```

Page Builder template JSON files can exceed 2 MB because they include builder project JSON, HTML, CSS, asset references, and editable template data.

## Changes

```text
app/Http/Controllers/Admin/PageController.php
resources/views/admin/pages/edit.blade.php
/etc/php/8.2/apache2/conf.d/99-art-inpa-upload.ini
```

## Implementation

- Raised Laravel template import validation from 5 MB to 20 MB.
- Added explicit validation messages for missing, unreadable, oversized, and failed template uploads.
- Added a dedicated Apache PHP override:

```text
/etc/php/8.2/apache2/conf.d/99-art-inpa-upload.ini
```

with:

```text
upload_max_filesize = 25M
post_max_size = 26M
max_input_time = 120
```

- Updated the Page Settings drawer note to state that JSON templates up to 20 MB are accepted.
- Expanded the file accept list to tolerate common JSON MIME handling by browsers.

## Backup

```text
/root/codex-backups/page-builder-template-upload-fix-20260629-123900
```

## Verification

```text
apache2ctl configtest: Syntax OK
Apache reload: passed
php -l app/Http/Controllers/Admin/PageController.php: passed
php -l resources/views/admin/pages/edit.blade.php: passed
php artisan optimize:clear --no-ansi: passed
php artisan route:cache --no-ansi: passed
php artisan view:cache --no-ansi: passed
php artisan config:cache --no-ansi: passed
Apache PHP upload_max_filesize=25M: passed
Apache PHP post_max_size=26M: passed
Large template import smoke test: passed with 3,146,129-byte JSON
Large import transaction rollback: passed
Temporary server verification files removed: passed
```

## Rollback

```text
cd /var/www/store.z4rank.com/laravel
cp /root/codex-backups/page-builder-template-upload-fix-20260629-123900/app/Http/Controllers/Admin/PageController.php app/Http/Controllers/Admin/PageController.php
cp /root/codex-backups/page-builder-template-upload-fix-20260629-123900/resources/views/admin/pages/edit.blade.php resources/views/admin/pages/edit.blade.php
rm -f /etc/php/8.2/apache2/conf.d/99-art-inpa-upload.ini
systemctl reload apache2
php artisan optimize:clear --no-ansi
php artisan route:cache --no-ansi
php artisan view:cache --no-ansi
php artisan config:cache --no-ansi
```
