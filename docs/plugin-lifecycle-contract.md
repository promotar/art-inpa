# Plugin Lifecycle Contract

This contract applies to every current and future plugin. Compliance is part of the plugin Definition of Done, not an optional integration guideline.

## Architectural contract

- Core routes, especially `/`, never depend on an optional plugin.
- A plugin owns only its routes, menus, permissions, assets, hooks, providers, widgets, and declared frontend paths.
- Required dependencies belong in `dependencies`; optional integrations belong in `optional_dependencies`.
- Activation is rejected when a required dependency is unavailable.
- Installation, update, and activation are rejected unless the package passes
  `PluginPackageValidator`; the validator must run before filesystem,
  migration, registry, or published-asset mutation.
- Core plugins declare `type: core` or `core: true` in their manifest and cannot be deactivated.
- `Deactivate` disables runtime loading and UI registration, clears application/config/route/view caches, and preserves all data and settings.
- `Uninstall / Purge` is a separate explicit and irreversible operation. It must remove all plugin-owned data, metadata, assets, source files, and package artifacts, leaving only its final dated audit log.
- Install and purge both validate the complete ownership contract before changing state. A missing declaration blocks the lifecycle operation.
- Plugins cannot provide `uninstall.php`. Custom table deletion in package
  scripts is prohibited; Core derives a dependency-safe order from live
  foreign keys and performs all declared resource cleanup.
- Purge is idempotent. A retry after successful removal returns
  `already_absent=true`, performs no filesystem or database mutation, and does
  not create a duplicate purge audit.
- Page Builder blocks use `data-pb-widget="plugin-slug.widget"` or `data-pb-type="plugin-slug.type"`; blocks from installed inactive plugins are pruned without removing the surrounding page.
- A Page Builder namespace is treated as plugin-owned only when its prefix resolves to an installed plugin in the plugin registry. Core namespaces such as `theme-builder.*` must never be inferred as plugin slugs.
- Manual navigation URLs are protected through `frontend.owned_paths` in the manifest. A trailing `/*` owns both the prefix and its descendants.
- Plugin routes referenced by Core must be checked through `PluginOwnedPageGuard::isRouteAvailable()` and must have a Core fallback.
- Plugins may contribute optional homepage sections, but must never own `/`.
- Menus, widgets, hooks, permissions, assets, sitemap entries, and search integrations must be registered dynamically and disappear when their owning plugin is inactive.
- Re-activation must recover the same routes, permissions, UI integrations, data, and settings without manual repair.

## Required lifecycle verification

Every plugin must pass this scenario before release:

1. `Activate` — activate the plugin and verify its runtime, provider, routes, menus, permissions, assets, and integrations.
2. `Verify` — record representative data counts and stable fingerprints, then verify the plugin's public and protected surfaces.
3. `Deactivate` — deactivate through `PluginDeactivator`; never simulate deactivation by hiding navigation only.
4. `Verify Isolation` — confirm plugin routes are unavailable, plugin UI/assets/widgets are absent, Core/theme/homepage and unrelated plugins still work, no links lead to disabled routes, and no `500` response occurs.
5. `Reactivate` — reactivate through `PluginActivator`.
6. `Verify Data and Function Recovery` — confirm all plugin surfaces return automatically and compare data counts/fingerprints with the pre-deactivation baseline.
7. `Purge` — after a separate disposable install, disable and permanently remove the plugin; verify its declared tables, shared records and columns, migration records, settings, permissions, menus, registry entries, updates, assets, owned storage, module source, ZIP package, and checkpoints are absent and exactly one successful purge audit remains.

The automated contract suite is:

```bash
php artisan test \
  tests/Unit/PluginArchitectureContractTest.php \
  tests/Feature/PluginInstallUninstallContractTest.php \
  tests/Unit/PageBuilderPluginIsolationTest.php \
  tests/Unit/PluginDependencyContractTest.php \
  tests/Feature/PluginOwnedPathContractTest.php \
  tests/Feature/PluginLifecycleContractTest.php \
  tests/Feature/OptionalPluginCoreFallbackTest.php
```

Plugin-specific feature tests must supplement this suite with representative public, authenticated, admin, API/AJAX, search, sitemap, and homepage integration checks when those surfaces exist.

## Definition of Done

A plugin is ready only when all of the following are true:

- Its manifest declares ownership, dependencies, provider, routes, permissions, menus, assets, and uninstall scope.
- Every PHP file parses successfully; every provider, route, migration,
  lifecycle, documentation, CSS, and JavaScript path declared by the manifest
  exists inside the package.
- Every route file and CSS/JavaScript source file is represented in its
  manifest catalog. Providers do not register routes directly.
- Its `uninstall` object contains explicit `tables`, `settings`,
  `storage_paths`, `records`, `columns`, and `operation_target_prefixes`
  arrays, even when a category is empty.
- It does not own `/` or another Core route.
- Core code has no unconditional redirect or call to a plugin-owned route or service.
- Deactivation unregisters runtime surfaces and preserves all plugin data and settings.
- The homepage and shared layouts close gaps left by inactive plugin sections.
- Re-activation restores functionality without saving settings or reconnecting data manually.
- Purge removes every declared or centrally registered plugin resource and leaves no recoverable package copy inside the running platform.
- Required cache clearing occurs during activation and deactivation.
- Core plugin deactivation and activation with missing dependencies are rejected with understandable errors.
- The standard lifecycle suite and plugin-specific lifecycle tests pass.
- A release report lists tested routes, dependencies, data before/after, and test results.

Example:

```json
{
  "type": "feature",
  "dependencies": ["required-plugin"],
  "optional_dependencies": ["blog"],
  "frontend": {
    "owned_paths": ["/example/*"]
  },
  "uninstall": {
    "tables": ["example_items"],
    "settings": ["example"],
    "storage_paths": [
      {"disk": "public", "path": "example"}
    ],
    "records": [
      {"table": "platform_pages", "column": "slug", "values": ["example"]}
    ],
    "columns": [
      {"table": "users", "columns": ["example_profile_id"]}
    ],
    "operation_target_prefixes": ["example."]
  }
}
```
