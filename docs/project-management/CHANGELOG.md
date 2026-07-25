# Changelog

All notable project changes will be documented in this file.

## 2026-06-29

### Changed

- Added a compact admin sidebar mode for Page Builder edit screens so the canvas has more horizontal workspace.
- Added a Page Builder top-bar `Menu` toggle that stores the sidebar state in browser `localStorage`.
- Tightened the Page Builder compact sidebar override so active admin theme `!important` sidebar width rules cannot keep the editor workspace narrow.

## 2026-06-28

### Added

- Added a standalone `theme-manager` plugin package for upload through the existing plugin installer.
- Added plugin package routes, service provider, views, manifest, admin menu declaration, permission declaration, functions, hooks, and documentation.
- Added `theme-manager.zip` in the local package distribution folder and copied it to the owner downloads folder for upload.
- Added plugin-owned Theme model, ThemeUpdate model, ThemeRepository, and guarded migrations to the `theme-manager` package.

### Changed

- Removed the core `/admin/themes` implementation so theme management is available only after the Theme Manager plugin is uploaded and activated.
- Updated the platform view, update, license, asset, route, navigation, registry, and permission layers so they no longer depend on core Theme Manager classes.
- Preserved the existing `theme-editor` plugin because it is separate from the Theme Manager upload/activation plugin.
- Disabled the older manual Theme Editor admin menu item so the active plugin-owned Theme Editor menu item appears only once.

### Removed

- Removed core Theme Manager controller, models, repository, service classes, admin views, and core theme migrations.

## 2026-06-21

### Added

- Added the plugin database foundation with `plugins` and `plugin_updates` migrations.
- Added `App\Platform\Core\Models\Plugin`.
- Added `App\Platform\Core\Repositories\PluginRepository`.
- Added `PluginManifest`, `PluginManifestReader`, and `PluginDependencyChecker`.
- Added the plugin lifecycle orchestration services: `PluginManager`, `PluginLoader`, `PluginInstaller`, `PluginActivator`, `PluginDeactivator`, and `PluginUninstaller`.
- Added dynamic service provider loading for active plugins only.
- Added dynamic web, admin, and API route loading for active plugins only.
- Added the plugin install flow for already discovered plugins, including backup checkpoints, migrations, seeders, permissions, menus, assets, cache clearing, and rollback where possible.
- Added the plugin disable flow for installed plugins.
- Added plugin runtime state tracking for disabled hooks/runtime participation.
- Added the plugin uninstall flow with inactive-status validation, active-dependent blocking, pre-uninstall checkpoints, declared uninstall script execution, declared table removal, plugin-owned registration cleanup, asset cleanup, cache clearing, and structured uninstall results.
- Added the platform Menu Manager with `menus` and `menu_items` tables, Eloquent models, repository/services, plugin menu loading, hierarchy, ordering, and permission-based visibility.
- Added the platform Hook System with action hooks, filter hooks, active-plugin `hooks.php` loading, priority handling, accepted argument limits, and safe hook error logging.
- Added the platform Theme Manager with `themes` table, Theme model, repository, manifest reader/validator, discovery, install, activation, deactivation, and single-active-theme enforcement.
- Added the platform View Resolver with active theme overrides, active plugin view fallback, core view fallback, safe path guards, and Laravel view namespace registration.
- Added the platform Asset Manager with safe plugin/theme asset publishing, published asset removal, URL generation, and filemtime-based cache busting.

### Changed

- Extended the existing server plugin tables additively so the new database layer supports the approved plugin architecture fields without removing legacy plugin data.
- Updated `AppServiceProvider` to register active plugin providers during application boot after Laravel base services are available.
- Replaced the special-case Front Builder route include with the generic active plugin route loader while preserving the existing Front Builder routes.
- Updated manifest reader behavior so custom manifest sections are preserved after required-field validation.
- Updated plugin menu registry entries so disabled plugins can hide menus without deleting menu declarations.
- Updated `PluginUninstaller` and `PluginManager::uninstall()` to use the uninstall flow and return a structured result.
- Updated plugin runtime registry support so uninstall can remove plugin runtime state after successful cleanup.
- Updated plugin menu lifecycle integration so plugin install, activation, disable, and uninstall paths synchronize database-backed menu records through the existing menu registry.
- Updated `AppServiceProvider` to register `HookManager` as a singleton and load active plugin hooks during application boot.
- Updated `AppServiceProvider` to register active theme, core, and active plugin view namespaces during application boot.
- Updated plugin install/uninstall asset services and Theme Manager to use the new Asset Manager.

## 2026-06-09

### Added

- Added and verified the first admin permissions system on the server.
- Added a central `PermissionManager` service, permission middleware, and permission seeder.
- Added default permission sync for platform roles.

### Changed

- Protected admin routes with specific permission middleware.
- Updated admin navigation to show links according to user permissions.
- Updated `DatabaseSeeder` so it seeds permissions without creating a default test user.

## 2026-06-21

### Added

- Added the `modules/PageBuilder` plugin with manifest, provider, routes, controllers, models, migrations, views, renderer, HTML cache, assets, and uninstall support.
- Added Page Builder permissions and admin menu registration through the plugin manifest.
- Added Page Builder implementation reports.
- Added the platform Update System for plugins and themes.
- Added `theme_updates` persistence for theme update checks and installs.
- Added update checkpoints and failed update logs.
- Added the local License System with license records, validation, domain binding, and manifest-driven restrictions.
- Added Backup & Logs System with operation logs, failed operation logs, backup checkpoints, and restore notes.
- Added the Blog plugin validation module with posts, categories, tags, routes, views, permissions, menus, assets, hooks, and uninstall support.
- Added the Store plugin business validation module with products, categories, simple orders, settings, routes, views, permissions, menus, assets, hooks, and uninstall support.
- Added the final full platform testing report for implementation Task 24.

### Changed

- Added Composer autoload support for `Modules\\PageBuilder\\`.
- Refreshed `composer.lock` after the autoload namespace change without installing new packages.
- Added compatibility for legacy `plugin_updates` columns when storing update checks.
- Integrated license checks with plugin activation and plugin/theme update flows when manifests require a license.
- Integrated operation logging and checkpoints with sensitive plugin, theme, update, and asset operations.
- Added Composer autoload support for `Modules\\Blog\\`.
- Added Composer autoload support for `Modules\\Store\\`.
- Fixed inactive plugin view resolution so theme overrides cannot expose disabled plugin views.
- Fixed plugin reactivation so plugin runtime/hooks are re-enabled after disable.
- Fixed plugin migration reinstall handling after uninstall removes plugin-owned tables.

### Verified

- Installed and activated Page Builder through the existing plugin lifecycle.
- Verified Page Builder admin routes, frontend route, tables, permissions, menu, renderer, HTML cache, Composer validation, and safe example tests.
- Verified version comparison, plugin/theme update detection, successful update handling, failed update logging, disabled plugin guard, and safe example tests.
- Verified license validation, domain binding, expired/invalid rejection, licensed activation/update restrictions, and free plugin activation.
- Verified operation logs, failed logs, checkpoints, restore notes, plugin update checkpoints, and failed update checkpoints.
- Verified Blog install, activation, routes, permissions, menus, published/draft behavior, disable hiding, uninstall cleanup, reinstall, and safe example tests.
- Verified Store install, activation, routes, permissions, menus, active/draft product behavior, simple order records, settings, disable hiding, uninstall cleanup, Blog/PageBuilder isolation, reinstall, and safe example tests.
- Verified full platform readiness across plugin lifecycle, themes, views, permissions, menus, hooks, updates, backups/logs, assets, Blog, Store, Page Builder, and core admin routes.

## 2026-06-08

### Added

- Added `docs/project-management/README.md` as an index for project management files.
- Added categorized task-report folders under `reports/tasks/documentation/` and `reports/tasks/implementation/`.
- Added a dedicated report for `Implementation Task 2: Create Core Folder Structure`.
- Added and verified the first admin settings system on the server.

### Changed

- Moved `CHANGELOG.md`, `CODING_STANDARDS.md`, `DECISIONS.md`, `IMPLEMENTATION_LOG.md`, and `MODULE_GUIDE.md` from the project root into `docs/project-management/`.
- Updated the main documentation references to use the new project management paths.
- Reorganized Markdown task reports into documentation and implementation groups.

## 2026-06-08

### Added

- Organized backup placeholders into `backups/local/` and `backups/server/`.
- Added `backups/README.md` to clarify backup usage rules.
- Reorganized Markdown reports under `reports/tasks/`.
- Replaced the old reports index file with `reports/README.md`.
- Added a dedicated task report for the file organization work.

## 2026-06-02

### Added

- Added Phase 0.5 server reconciliation task tracking under `tasks/`.
- Added Phase 0.5 server audit, comparison, backup verification, safe alignment, verification, and final reports.
- Added local `backups/.gitkeep` placeholder and Git ignore rules for backup archives and sensitive server files.
- Added missing server-side Laravel-root documentation/report folders and platform/module placeholders through additive-only alignment.
- Added `All Tasks`, `Done`, and `Pending` tabs to the admin documentation checklist page.
- Added a completed official checklist task for the admin documentation checklist tabs.
- Added a task report for the admin documentation checklist tabs.
- Reflected the checklist tabs task in the published Arabic and English documentation indexes.
- Updated completed checklist tab tasks so they appear at the top of the `Done` tab with full documentation links in their details.
- Fixed the Project Vision checklist row so it is marked done and includes direct documentation links.
- Embedded Project Vision checklist anchors into the normal Arabic and English Core Philosophy documentation context.
- Replaced visible checklist details textareas with a details popup in the admin documentation checklist.
- Added and published the Architecture Overview documentation task.

### Notes

- Phase 0.5 did not overwrite existing server code, routes, `.env`, database tables, Laravel core, vendor files, or public entry files.
- Database backup, server `artisan`, and server `composer validate` were skipped because shell/database access was not available.

## 2026-06-01

### Added

- Created the initial Laravel project.
- Initialized Git for the project.
- Added Phase 0 project documentation.
- Added the initial `app/Platform` folder structure.
- Added placeholder module folders for Blog, LMS, Store, and Exhibition.
- Added `.gitkeep` files for empty platform and module directories.
- Added a documentation migration checklist covering the current English and Arabic documentation website.
- Published the current documentation copy to the server documentation path.
- Added migrated documentation checklist items to the server documentation checklist.
- Added stable section anchors to migrated documentation pages and expanded documentation checklist coverage.
- Added and published the Project Vision documentation task.
- Added task reports for completed work so far.
- Added Arabic Word task reports for completed work so far.
- Regenerated Arabic Word task reports in `reports/word-ar/` with correct Arabic encoding.
- Expanded the Arabic checklist expansion Word report with exact completed items and implementation mechanism details.
- Added a table version of the Arabic checklist expansion report containing all current checklist tasks.

### Notes

- No business features were added.
- No custom migrations were created.
- No Laravel core or vendor files were modified.
- Server credential files are ignored by Git.
