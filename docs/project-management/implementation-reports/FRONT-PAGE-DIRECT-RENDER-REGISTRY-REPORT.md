# Front Page Direct Render and Registry Report

Date: 2026-06-26

## Scope

Fixed selected platform pages so the homepage renders the selected page directly on `/` without redirecting to `/pages/{slug}`. Registered the public page render route in a database-backed platform registry table.

## Backup

```text
/root/codex-backups/front-page-registry-render-20260626-000938
```

## Changed Files

- `routes/web.php`
- `app/Platform/Core/Registry/PlatformRegistry.php`
- `database/migrations/2026_06_26_000006_create_platform_registry_entries_table.php`

## Database Changes

Added `platform_registry_entries` for database-backed core registry entries.

Seeded route entry:

- `registry_type`: `routes`
- `registry_key`: `pages.show`
- `uri`: `pages/{slug}`
- `methods`: `GET`, `HEAD`
- `status`: `active`

## Implementation

- `/` now reads `front_page.front_page` from the official settings system.
- When the selected value is `platform-page:{slug}`, the homepage loads the matching published `platform_pages` record.
- The selected page is rendered directly through `frontend.pages.show` on `/`.
- No redirect is issued for selected platform pages.
- `PlatformRegistry` now merges active entries from `platform_registry_entries` with existing config and plugin manifest registry entries.
- `/pages/{slug}` is registered in the database registry as `pages.show`, fixing the 403 registry block.

## Verification

- `registry_pages_show=active`
- `front_page_mode=static`
- `front_page=platform-page:test-page-ziad`
- `route_registered=yes`
- `php -l routes/web.php`: passed.
- `php -l app/Platform/Core/Registry/PlatformRegistry.php`: passed.
- `php -l database/migrations/2026_06_26_000006_create_platform_registry_entries_table.php`: passed.
- `php artisan migrate --force --no-ansi`: passed.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- `php artisan config:cache --no-ansi`: passed.
- `php artisan route:cache --no-ansi`: passed.
- `php artisan view:cache --no-ansi`: passed.
- `GET /`: HTTP 200, no redirect header.
- `GET /pages/test-page-ziad`: HTTP 200, registry no longer blocks the route.

## Notes

- The selected front page remains editable from the database settings system.
- Page content remains stored in `platform_pages`.
- The registry metadata is stored in the database; executable code remains in the codebase.
