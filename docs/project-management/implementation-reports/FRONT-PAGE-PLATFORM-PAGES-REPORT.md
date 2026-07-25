# Front Page Platform Pages Settings Report

Date: 2026-06-26

## Scope

Fixed the Front Page settings dropdown so it lists pages created by the platform GrapesJS page builder from `platform_pages`.

## Backup

```text
/root/codex-backups/front-page-platform-pages-20260625-235704
```

## Changed Files

- `app/Platform/Core/Services/SettingsRepository.php`
- `routes/web.php`
- `resources/views/admin/settings/index.blade.php`

## Implementation

- Added `platform_pages` records with `content_type = page` to the `front_page.front_page` select options.
- Kept legacy `front_builder_pages` options for backward compatibility.
- Added `platform-page:{slug}` as the stored setting value for platform page selections.
- Updated the `/` route to redirect to `pages.show` when `front_page.front_page_mode = static` and the selected value is a published platform page.
- Kept draft pages selectable in admin settings but protected public rendering by requiring `status = published`.
- Updated the settings help text translation to reference platform pages instead of Front Builder pages.

## Verification

- `platform_pages=1`
- `platform_front_page_options=1`
- `php -l app/Platform/Core/Services/SettingsRepository.php`: passed.
- `php -l routes/web.php`: passed.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- `php artisan config:cache --no-ansi`: passed.
- `php artisan route:cache --no-ansi`: passed.
- `php artisan view:cache --no-ansi`: passed.
- `/admin/settings#settings-front_page`: HTTP 302 when unauthenticated, expected admin protection.

## Notes

- The setting remains stored in the database through the platform settings system.
- No executable code is stored in the database.
