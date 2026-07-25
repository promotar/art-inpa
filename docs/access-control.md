# Art INPA Mandatory Route Access

## Security Model

Platform version `2.1.0` uses two required permission layers for protected routes:

1. Capability middleware such as `permission:settings.manage` enforces the domain policy.
2. An exact database permission such as `route.admin.settings.index` authorizes the individual route.

Both layers must pass. `super-admin` is the only exact-route bypass and remains protected by its database role assignment. Protected routes fail closed when their exact permission is missing.

`EnforceRoutePermission` runs for every web request. `RouteAccessGate` reloads the current user's roles and effective permissions for each request, so session state or an earlier page load cannot preserve revoked access.

## Route Synchronization

`RoutePermissionCatalog` discovers named routes from the active Laravel route collection. It includes core and active plugin routes that are administrative or use `staff`, `super-admin`, or `permission:*` middleware.

Opening `/admin/roles` synchronizes missing route permissions automatically and displays routes grouped by core or plugin. Synchronization creates missing permissions but never grants new routes automatically.

Operators can run:

```bash
php artisan platform:sync-route-permissions
```

Use `--grant-existing` only for the initial migration. It preserves access for known administrative roles based on the previous middleware rules. It does not promote frontend roles such as student or instructor.

## Interface Visibility

- Admin menu items are checked against the same `RouteAccessGate` used by request enforcement.
- Dashboard cards are shown only when their target route is assigned.
- Server-rendered links and forms targeting protected routes are removed when the user lacks the exact route permission.
- Hidden interface elements are usability protection only; the mandatory server middleware remains the security authority.

## Verification

- The count of discovered protected routes must equal stored `route.*` permissions.
- A normal role with a capability but without its exact route permission must receive HTTP 403.
- An exact route permission without the required capability must also receive HTTP 403.
- Frontend roles must have zero administrative route permissions unless explicitly assigned later by an administrator.

## Test Isolation

`tests/TestCase.php` forces test-specific Laravel cache paths and boots only against SQLite `:memory:`. It throws before database refresh work when the resolved connection is not isolated SQLite. This guard remains effective when production config and route caches exist, so `RefreshDatabase` cannot reset the local MySQL database.
