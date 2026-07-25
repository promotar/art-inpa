# Menu Link Target Root Cause Fix

## Task

Fix frontend menu links that appeared in the menu builder but rendered as `No menu items` in the page builder and frontend when the saved menu items had no stored target.

## Root Cause

Menu item saving allowed `type=route` with an empty `route_name`. If an admin typed a URL while the item type stayed `route`, the controller discarded the URL because `itemPayload()` persisted URLs only for `type=link`.

The renderer and page builder were correctly refusing to output links because the database rows had neither `url` nor `route_name`.

## Backup

```text
/root/codex-backups/menu-links-root-cause-20260626-220228
```

## Files Changed

- `app/Http/Controllers/Admin/MenuSettingsController.php`
- `resources/views/admin/menus/partials/item-fields.blade.php`

## Changes

- Added target validation so route items must have a registered `route_name`.
- Added target validation so link items must have a URL.
- Added safe type normalization:
  - `route` plus URL and no route becomes `link`.
  - `link` plus route and no URL becomes `route`.
- Updated the menu item form so Route and URL fields switch by selected item type, reducing accidental no-target saves.

## Data Note

Existing rows such as `foter-blok1` already have no stored `url` or `route_name`, so their original targets cannot be recovered from the database. Reopen those items, choose `Link` or `Route`, enter the target, and save them once. New saves now preserve and validate the target.

## Verification

- `php -l app/Http/Controllers/Admin/MenuSettingsController.php`: passed.
- `php -l resources/views/admin/menus/partials/item-fields.blade.php`: passed.
- `php codex_tmp/menu-links-root/verify_menu_target_save.php`: `menu_target_save=passed`.
- `php artisan route:list --no-ansi`: menu, page, and login routes present.
- `php artisan migrate:status --no-ansi`: latest migrations ran.
- `php artisan optimize:clear --no-ansi`: passed.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- `php artisan view:cache --no-ansi`: passed.
- `php artisan route:cache --no-ansi`: passed.
- `php artisan config:cache --no-ansi`: passed.
- HTTP `/`: 200.
- HTTP `/login`: 200.
- HTTP `/admin/menus`: 302 to login for unauthenticated request, expected admin protection.
- `foter-blok1` post-fix check: 3 builder preview items found and 3 frontend anchors rendered. Current stored href values are `#`, so replace them from the admin menu item form when real destinations are needed.
