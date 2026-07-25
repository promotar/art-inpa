# Database First Settings Policy Report

Date: 2026-06-26

## Objective

Apply the platform rule that all editable, operational, administrative, UI, permission, policy, feature, package, pricing, limits, workflow, and platform behavior settings must use the database as the single source of truth.

## Backup

Backup directory:

```text
/root/codex-backups/db-first-settings-policy-20260625-223348
```

Additional file backups for the registry/audit extension:

```text
/root/codex-backups/db-first-settings-policy-20260625-223348/registry-audit-extension
```

## Database Changes

- Created `platform_media_metadata` to replace runtime media metadata JSON writes.
- Created `platform_plugin_registry_entries` to replace runtime plugin registry/menu JSON writes.
- Extended `platform_settings` with the required settings registry columns:
  `validation_rules`, `description`, `category`, `module`, `visibility_level`, `admin_access_level`, `editable`, `required`, `sensitive_flag`, `public_exposure_allowed`, `frontend_available`, `cache_enabled`, `cache_ttl`, `ui_component`, `ui_label`, `allowed_values`, `min_value`, `max_value`, `unit`, `depends_on`, `restart_required`, `approval_required`, `status`, `version`.
- Stored the policy record in `platform_settings.platform_policy.database_first_settings`.

## Code Changes

- `SettingsRepository` remains the official settings read/write path.
- `SettingsRepository::update()` now:
  - skips settings that are not part of the submitted form payload,
  - records setting changes in `operation_logs`,
  - includes key, old value, new value, changed by, timestamp, and source,
  - hides old/new values for settings marked `sensitive_flag`,
  - clears application cache and view cache after a real setting change.
- `SettingsRepository::syncDefinitions()` now fills registry metadata into `platform_settings` when registry columns exist.
- `SettingsController` passes the authenticated admin id and source name into `SettingsRepository`.
- `PlatformSetting` supports the new registry columns and casts.
- `MediaController` and media settings metadata now use database storage instead of runtime JSON writes.
- `PluginSettingsRemover` removes plugin settings from `platform_settings` instead of editing JSON files.
- `PluginRuntimeRegistry` stores runtime and hook enablement state in `platform_plugin_registry_entries`.
- `PluginMenuRegistry` stores plugin menu registry state in `platform_plugin_registry_entries`.
- Legacy `plugin-runtime.json` and `plugin-menus.json` are read only for first import when the DB table is empty.

## Verification

- PHP syntax checks: passed.
- Migrations: passed.
- `platform_settings.approval_required`: exists.
- `platform_settings` rows without `module`: `0`.
- `platform_settings` rows with `module = core`: `27`.
- `platform_settings` total rows: `27`.
- `platform_plugin_registry_entries` migration: ran.
- Audit smoke test: `audit_logs_created_in_transaction=1`, then rolled back without leaving a test setting change.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- Production cache rebuilt:
  - `php artisan config:cache --no-ansi`
  - `php artisan route:cache --no-ansi`
  - `php artisan view:cache --no-ansi`
- HTTP checks:
  - `/`: 200
  - `/login`: 200
  - `/admin/settings`: 302 for unauthenticated request, expected admin protection.

## Remaining Standardization Notes

- Static install-time defaults may remain in code only as seed definitions for first synchronization.
- Runtime values and admin-editable values must be persisted in `platform_settings` or module-owned database tables.
- Future scans should continue converting any editable values found in config files, Blade files, plugin JSON files, or static arrays into database registry entries.
- Remaining file writes found by scan are backup/log/update artifacts or Theme Editor override content. Theme Editor overrides are the next DB-first conversion target because they intentionally store editable UI content.
