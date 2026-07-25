# Art INPA Platform Plugin Architecture

## Platform Contract

- Platform version: `2.5.0`.
- Plugin API: `2.0`.
- Every runtime plugin declares `type` and `platform_version` in `module.json`.
- Supported plugin types are `feature`, `theme`, and `service`.
- The database stores installed plugin metadata and runtime state. Executable code stays inside the plugin package.

## Ownership Boundaries

The core owns lifecycle, registry, security gates, routing, hooks, assets, view namespaces, cache invalidation, platform settings, and extension contracts. The core must not import `Modules\*` classes or register plugin-specific providers directly.

Each plugin owns its controllers, models, migrations, views, translations, assets, services, theme behavior, feature logic, and optional implementations of core extension contracts. A plugin must not edit core files to install or run.

## Runtime Sequence

1. The core reads active plugin rows from the `plugins` table.
2. `PluginRuntimeGate` validates status, runtime registry state, local package path, platform compatibility, and dependencies.
3. `PluginServiceProviderLoader` adds PSR-4 autoloading only for the active plugin and registers its provider.
4. `PluginRouteLoader`, `PluginHookLoader`, asset managers, view resolvers, Page Builder, and middleware use the same gate.
5. Every plugin route receives `EnsurePluginIsActive`, so a runtime status change is enforced even before route cache rebuild finishes.

Composer autoload contains no `Modules\*` namespaces. Plugin namespaces are attached dynamically only after the central gate allows the plugin.

## Disable Contract

Disabling a plugin must:

- mark the database record disabled;
- disable its runtime registry entry and hooks;
- hide its menus;
- clear route, view, config, and application caches;
- stop routes, middleware injection, hooks, views, assets, Page Builder widgets, stylesheets, and extension providers;
- preserve all plugin-owned data.

Long-running workers are restarted after deployment-level code or dependency changes. Current plugins do not define queued jobs; any future plugin job must check `PluginRuntimeGate` before executing domain work.

## Install And Uninstall Contract

- `PluginPackageValidator` is the mandatory preflight for ZIP upload, install,
  update, and activation. Validation completes before a package is moved into
  `modules`, migrations run, database metadata changes, or assets publish.
- ZIP upload and extraction use only `PluginUploadWorkspace` on the explicit
  private `plugin_uploads` disk. They must never depend on the default
  filesystem disk or write temporary packages below `storage/app/public`.
- Upload workspace allocation is part of the handled install flow. Storage
  failures return an actionable form error and cleanup removes temporary,
  extracted, and partially prepared pending-update resources.
- Preflight validates manifest schema and platform compatibility, provider
  declaration, PHP syntax, route and asset catalogs, migration ownership,
  lifecycle/docs paths, blocked package directories, and installed
  dependencies. All detected problems are returned in one actionable error.
- A package rejected during fresh installation leaves no module directory or
  plugin registry row. An installed package that becomes corrupt cannot be
  activated and remains disabled.
- Install, update, activation, rollback, and uninstall use the core asset
  manager; a plugin never writes or deletes another package's public files.
- `ManagedAssetFilesystem` is the only low-level writer/remover for published
  plugin assets.
- Asset publication uses atomic replacement and leaves directories writable by
  both deployment CLI and PHP-FPM processes on the shared Docker bind mount.
- Uninstall performs a writeability preflight before deleting any asset, so a
  permission problem cannot leave the asset tree partially removed.
- Deactivation is the only data-preserving removal from runtime.
- Uninstall is always a permanent purge. It removes declared tables and
  migration records, shared-table records and columns, settings, permissions,
  menus, every registry/update record, assets, owned storage, source
  directories, and old lifecycle artifacts. Canonical plugin ZIP files directly
  under `modules` are retained as reinstallable distributions.
- Every manifest must provide all six `uninstall` ownership arrays: `tables`,
  `settings`, `storage_paths`, `records`, `columns`, and
  `operation_target_prefixes`. Empty arrays are explicit declarations.
- `PluginOwnershipValidator` tokenizes plugin migrations before install and
  purge. Every literal `Schema::create()` table must be declared in
  `uninstall.tables`; shared columns must match a `Schema::table()` migration.
- Core rejects an incomplete ownership contract before migrations, files, or
  data are changed. This rule applies equally to directory packages and ZIP
  distributions.
- Plugin `uninstall.php` scripts are prohibited. Core orders owned tables from
  live foreign-key metadata, drops dependents before referenced tables, and
  blocks purge when an undeclared external table references plugin data.
- A successful purge removes previous plugin-specific operational records and
  leaves one final `plugin.purge` audit log with its completion timestamp.
- Purge removes standalone files from both
  `storage/app/platform/plugin-install-checkpoints` and
  `storage/app/platform/plugin-uninstall-checkpoints`; install recovery
  metadata must not survive permanent deletion.
- Purge does not create plugin recovery checkpoints. The administration UI
  requires explicit destructive confirmation before invoking it.

## Extension Rules

- Routes are declared in `module.json` and loaded only by `PluginRouteLoader`. Providers must not call `Route::` or `loadRoutesFrom()`.
- Platform-required capabilities may be delivered as `core: true` plugins.
  `RequiredCorePluginSynchronizer` discovers these manifests before plugin
  providers and routes, synchronizes the database/runtime registry, and keeps
  them active. Core plugins use the same package validation and migration
  contract as other plugins, but cannot be deactivated or purged.
- Theme and feature CSS used by editor previews comes from `ActivePluginStylesheets`, which returns active plugin assets only.
- Cross-plugin data is exposed through a core contract. For example, Blog implements `LatestContentProvider`; the core uses `NullLatestContentProvider` when Blog is disabled.
- Plugin-specific rendering belongs to the plugin implementation as well as its data access. The Blog archive renderer, search, categories, and post cards live in Blog; Page Builder delegates through the contract and emits nothing when the null provider is active.
- Package paths and manifests are resolved by `PluginFilesystem`. Production absolute paths are safely remapped to the current installation's `modules` directory.
- New editable platform behavior belongs in database settings or registry metadata, not hardcoded operational files.
- The complete package authoring contract is
  `docs/plugin-package-contract.md`.

## Verification

Run the isolated test suite without allowing container database variables to leak into tests:

```bash
docker compose exec -T \
  -e APP_ENV=testing \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=:memory: \
  -e DB_URL= \
  -e CACHE_STORE=array \
  -e SESSION_DRIVER=array \
  -e QUEUE_CONNECTION=sync \
  app ./vendor/bin/phpunit --colors=never
```

The architecture gate is:

```bash
php artisan test \
  tests/Unit/PluginArchitectureContractTest.php \
  tests/Feature/PluginInstallUninstallContractTest.php
```

Before release, also run `composer audit`, `npm audit`, `npm run build`, `php artisan optimize`, and verify that cached plugin routes include `EnsurePluginIsActive`.
