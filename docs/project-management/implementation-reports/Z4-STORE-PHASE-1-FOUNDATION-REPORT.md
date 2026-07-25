# Z4 Store Phase 1 — Foundation Implementation Report

**Date:** 2026-07-22  
**Server:** `10.10.0.20` (`laravel01`)  
**Active application:** `/var/www/store.z4rank.com/laravel`  
**Public document root:** `/var/www/store.z4rank.com/public_html`  
**Final Store state:** `active`  
**Store version:** `1.1.0`  
**Architecture classification:** Store-owned foundation with a small, generic plugin-lifecycle Core extension

## 1. Summary

The previous Store experiment was replaced with a lifecycle-safe Phase 1 foundation. The active module now contains only the architecture required to support later Store phases:

- an authoritative manifest;
- a dedicated service provider;
- separated Domain, Application, Infrastructure, HTTP/Admin, and HTTP/Storefront layers;
- lifecycle-owned admin and storefront routes;
- lifecycle-owned admin navigation;
- Store capabilities and permissions;
- public Store contracts that do not expose models or tables;
- a payment-method registry with built-in Cash on Delivery;
- read-only foundation configuration;
- lifecycle callbacks;
- events, translations, views, assets, and module-owned tests;
- architectural guardrails against cross-module imports and controller business logic.

No Product, Variant, Cart, Checkout, Orders UI, Inventory, Promotion, Shipping Provider, online gateway, or Exhibitions functionality was implemented.

No Store migration was run and no Store database table was created.

## 2. Review Findings Before Implementation

The existing Store code conflicted with the approved Phase 1 boundary:

- it exposed full Product, Category, Order, Settings, and storefront CRUD routes;
- `StorefrontController` created orders, calculated totals, and persisted records directly;
- controllers queried Eloquent models and contained domain behavior;
- routes had no complete capability/policy boundary;
- the provider registered only a view namespace;
- the manifest did not declare stable capabilities, optional dependencies, lifecycle hooks, or route ownership completely;
- the migration created full product and order tables before the Product Domain documentation phase;
- `uninstall.php` dropped Store tables directly;
- there was no payment abstraction and no built-in COD contract;
- there were no Store-owned automated tests.

The Store was not installed in the production plugin registry and all legacy Store tables were absent. This allowed the old active code tree to be archived safely without deleting production commerce data.

## 3. Final Module Structure

```text
modules/Store/
├── module.json
├── config/store.php
├── database/
│   ├── factories/.gitkeep
│   ├── migrations/.gitkeep
│   └── seeders/.gitkeep
├── routes/
│   ├── admin.php
│   └── storefront.php
├── resources/
│   ├── assets/css/store.css
│   ├── lang/{ar,en}/store.php
│   └── views/
│       ├── admin/{overview,settings}.blade.php
│       └── storefront/index.blade.php
├── src/
│   ├── Application/
│   │   ├── Capabilities/ManifestStoreCapabilities.php
│   │   └── Readiness/DefaultStoreReadiness.php
│   ├── Contracts/
│   │   ├── Capabilities/StoreCapabilities.php
│   │   ├── Payments/{PaymentMethod,PaymentMethodRegistry}.php
│   │   └── Readiness/{StoreReadiness,StoreReadinessData}.php
│   ├── Domain/
│   │   ├── Payments/CashOnDeliveryPaymentMethod.php
│   │   └── Catalog, Pricing, Inventory, Cart, Checkout, Orders, Shipping
│   ├── Events/StoreRuntimeRegistered.php
│   ├── Http/
│   │   ├── Admin/Controllers/{OverviewController,SettingsController}.php
│   │   └── Storefront/Controllers/StorefrontController.php
│   ├── Infrastructure/
│   │   ├── Lifecycle/StoreLifecycle.php
│   │   └── Payments/InMemoryPaymentMethodRegistry.php
│   ├── Policies/.gitkeep
│   ├── Support/StoreModule.php
│   └── StoreServiceProvider.php
├── tests/
│   ├── Architecture/StoreArchitectureTest.php
│   ├── Feature/
│   │   ├── StoreDeactivationReactivationTest.php
│   │   ├── StoreRouteLifecycleTest.php
│   │   ├── StoreServiceProviderTest.php
│   │   └── StoreUninstallRetentionTest.php
│   └── Unit/PaymentMethodRegistryTest.php
├── hooks.php
└── uninstall.php
```

`modules/Store` capitalization was retained because it is the active project convention and the Composer PSR-4 mapping is `Modules\\Store\\ => modules/Store/src/`. Renaming it to `modules/store` on Linux would have introduced an unnecessary deployment and autoload risk.

## 4. Current Store Files

The rebuilt module contains 45 files:

```text
config/store.php
database/factories/.gitkeep
database/migrations/.gitkeep
database/seeders/.gitkeep
hooks.php
module.json
resources/assets/css/store.css
resources/lang/ar/store.php
resources/lang/en/store.php
resources/views/admin/overview.blade.php
resources/views/admin/settings.blade.php
resources/views/storefront/index.blade.php
routes/admin.php
routes/storefront.php
src/Application/Capabilities/ManifestStoreCapabilities.php
src/Application/Readiness/DefaultStoreReadiness.php
src/Contracts/Capabilities/StoreCapabilities.php
src/Contracts/Payments/PaymentMethod.php
src/Contracts/Payments/PaymentMethodRegistry.php
src/Contracts/Readiness/StoreReadiness.php
src/Contracts/Readiness/StoreReadinessData.php
src/Domain/Cart/.gitkeep
src/Domain/Catalog/.gitkeep
src/Domain/Checkout/.gitkeep
src/Domain/Inventory/.gitkeep
src/Domain/Orders/.gitkeep
src/Domain/Payments/CashOnDeliveryPaymentMethod.php
src/Domain/Pricing/.gitkeep
src/Domain/Shipping/.gitkeep
src/Events/StoreRuntimeRegistered.php
src/Http/Admin/Controllers/OverviewController.php
src/Http/Admin/Controllers/SettingsController.php
src/Http/Storefront/Controllers/StorefrontController.php
src/Infrastructure/Lifecycle/StoreLifecycle.php
src/Infrastructure/Payments/InMemoryPaymentMethodRegistry.php
src/Policies/.gitkeep
src/StoreServiceProvider.php
src/Support/StoreModule.php
tests/Architecture/StoreArchitectureTest.php
tests/Feature/StoreDeactivationReactivationTest.php
tests/Feature/StoreRouteLifecycleTest.php
tests/Feature/StoreServiceProviderTest.php
tests/Feature/StoreUninstallRetentionTest.php
tests/Unit/PaymentMethodRegistryTest.php
uninstall.php
```

## 5. Legacy Store Files Removed From the Active Module

These files were removed from the active module and retained in the backup:

- the full product/category/order migration;
- `routes/web.php` and the legacy CRUD content of `routes/admin.php`;
- `CategoryController`, `ProductController`, `OrderController`, `SettingController`, and the legacy `StorefrontController`;
- `Category`, `Product`, `Order`, `OrderItem`, and `Setting` Eloquent models;
- Product, Category, Order, Settings, and legacy storefront Blade views.

No database record or table was deleted.

## 6. Core Files Added or Modified

### Added

- `app/Platform/Core/Services/PluginLifecycleHookRunner.php`

This is a generic runner. It reads lifecycle declarations from any plugin manifest, validates that the declared file stays inside the plugin directory, resolves the class through the container, and invokes the declared method. It contains no Store name, route, class, or business rule.

### Modified

- `app/Platform/Core/Services/PluginInstaller.php`
- `app/Platform/Core/Services/PluginActivator.php`
- `app/Platform/Core/Services/PluginDeactivator.php`
- `app/Platform/Core/Services/PluginManager.php`
- `app/Platform/Core/Services/PluginUninstaller.php`
- `app/Platform/Core/Plugins/Uninstall/PluginUninstallFlow.php`
- `phpunit.xml`

Reasons:

- execute generic `install`, `activate`, `reactivate`, `deactivate`, `uninstall`, and `purge` declarations;
- keep the plugin state unchanged when a lifecycle callback fails;
- make normal uninstall data-retaining by default;
- add a separate explicit `PluginManager::purge()` path for destructive removal;
- preserve tables, settings, and permission assignments during normal uninstall;
- include module-owned Store tests and Store source in PHPUnit configuration.

No Store class, Store route, Store table, or Store-specific conditional was added to Core.

## 7. Routes Owned by Store

| Method | URI | Name | Authorization |
|---|---|---|---|
| `GET/HEAD` | `/store` | `store.index` | Public; active-module runtime gate |
| `GET/HEAD` | `/admin/plugins/store` | `admin.plugins.store.overview` | `web`, `auth`, `staff`, `permission:store.access`, runtime gate |
| `GET/HEAD` | `/admin/plugins/store/settings` | `admin.plugins.store.settings.index` | previous middleware plus `permission:store.settings.view` |

The Store service provider does not load route files. The platform `PluginRouteLoader` loads them only when Store is active.

## 8. Capabilities and Permissions

### Runtime capabilities

- `store.access`
- `store.manage`
- `store.settings.view`
- `store.settings.update`
- `store.payments.cod`

### Registered permissions

- `store.access`
- `store.manage`
- `store.settings.view`
- `store.settings.update`

Capabilities describe active platform behavior. Permissions describe actor authorization. Controllers and views do not inspect roles directly.

## 9. Configuration and Payment

Foundation defaults:

- default currency: `JOD`;
- default country: `JO`;
- default order confirmation: `manual`;
- automatic fulfillment: `false`;
- default payment method: `cash_on_delivery`.

The payment abstraction consists of public `PaymentMethod` and `PaymentMethodRegistry` contracts. Store registers only `CashOnDeliveryPaymentMethod`. No online SDK, credential, callback controller, provider service, or Payment Gateway class exists in Store.

There is deliberately no second `store.enabled` configuration flag. The platform plugin registry is the single source of runtime state.

## 10. Lifecycle Behavior Verified on Production

### Install

- Store installed as version `1.1.0`.
- initial state was `installed` (inactive).
- four permissions and one Store menu definition were registered.
- CSS was published to `public_html/platform/plugins/store/css/store.css`.
- no migration file existed, no migration ran, and no Store table was created.

### Activate

- state changed to `active`;
- provider, contracts, COD, routes, menu, view namespace, translations, and asset became available;
- repeated registration tests remained idempotent.

### Deactivate

- state changed to `disabled`;
- runtime gate returned blocked;
- Store routes disappeared from a fresh application boot;
- Store navigation disappeared;
- Store Page Builder widgets remained zero;
- four Store permissions and the menu record remained stored;
- no tables or files were deleted;
- Core, authentication screen, Blog, and LMS stayed operational.

### Reactivate

- state returned to `active`;
- the same three routes returned;
- navigation returned;
- stored permission/menu records remained;
- COD remained the sole and default payment method.

### Uninstall and Purge

- normal uninstall now preserves declared plugin tables, settings, and permission assignments by default;
- plugin runtime contributions and files are removed/archived through the existing generic Core flow;
- destructive services are not invoked by normal uninstall, proven by automated test;
- purge is a separate explicit method;
- Store Phase 1 declares no tables to purge.

## 11. Disabled Module Verification

Observed while Store was disabled:

```text
status=disabled
gate=blocked
permissions=4
menu_records=1
visible_navigation=no
store.index=absent
admin.plugins.store.overview=absent
```

HTTP smoke results:

```text
/              200
/login         200
/account       302 (expected guest redirect)
/dashboard     302 (expected guest redirect)
/admin/plugins 302 (expected guest redirect)
/blog          200
/courses       200
/store         404
```

The automated disabled-state test also authenticated a test user and confirmed `/account` remained `200`.

## 12. Reactivation Verification

Observed after reactivation:

```text
status=active
gate=allowed
permissions=4
menu_records=1
visible_navigation=yes
default_payment=cash_on_delivery
store.index=store
admin.plugins.store.overview=admin/plugins/store
admin.plugins.store.settings.index=admin/plugins/store/settings
```

HTTP smoke results:

```text
/                   200
/login              200
/blog               200
/courses            200
/store              200
/admin/plugins/store 302 (expected guest redirect)
```

## 13. Automated Tests

Commands run:

```bash
php artisan test modules/Store/tests --colors=never
php artisan test modules/Store/tests \
  tests/Feature/PluginLifecycleContractTest.php \
  tests/Feature/PluginOwnedPathContractTest.php \
  tests/Feature/OptionalPluginCoreFallbackTest.php \
  tests/Unit/PluginDependencyContractTest.php \
  tests/Unit/PageBuilderPluginIsolationTest.php \
  --colors=never
php artisan test --testsuite=Store --colors=never
php artisan test --colors=never
```

Results:

- Store suite: **12 passed, 134 assertions**.
- Store plus relevant Core regression suite: **20 passed, 186 assertions**.
- Existing project suite excluding the newly added Store suite: **58 passed, 2 failed, 177 assertions**.
- The two full-suite failures are unrelated pre-existing Auth expectations:
  - `EmailVerificationTest` expects `/dashboard?verified=1`, while active behavior redirects to `/account?verified=1`;
  - `RegistrationTest` expects immediate authentication, while current registration behavior does not authenticate the new user.
- No Auth file was modified in this task.

Additional checks:

- JSON manifest validation: passed.
- PHP lint: **39 PHP files**, all passed before deployment.
- Linux filename check: no filename containing a literal backslash.
- HTTP active, disabled, and reactivated smoke checks: passed.

## 14. Architecture Guardrails

Automated checks cover:

- no Store import of Payment Gateway, Payment, Exhibitions, Exhibition, Blog, or LMS internals;
- no Product/Order models exposed as public API;
- no query, create, update, delete, DB, or Schema behavior in controllers;
- no hard dependency in the manifest;
- COD availability without optional modules;
- route and navigation registration only while active;
- Core homepage, login, and account behavior with Store inactive;
- deactivation data preservation;
- reactivation runtime restoration;
- non-destructive uninstall behavior;
- idempotent COD registration;
- Linux-compatible module filenames.

## 15. Dependency Confirmations

### Payment Gateway

Confirmed:

- not listed in `dependencies`;
- no Payment Gateway namespace import;
- no provider SDK or credentials;
- COD works with no Payment Gateway plugin installed or active;
- only `cash_on_delivery` was returned by the runtime registry.

### Exhibitions

Confirmed:

- not listed in `dependencies`;
- no Exhibitions namespace import;
- no Exhibition route, controller, model, table, or service in Store;
- disabling Store did not affect unrelated active modules.

## 16. Architectural Decisions

1. Retain the real Linux path `modules/Store` because it is the established Composer/project convention.
2. Create no Store database schema during Phase 1.
3. Remove legacy commerce CRUD from the active module and retain it only in backup.
4. Keep route registration in the generic active-plugin loader, not the Store provider.
5. Use a module-local public payment registry with COD registered by Store.
6. Keep capabilities separate from permissions.
7. Keep the plugin registry as the single enabled-state authority.
8. Add generic manifest-driven lifecycle callbacks rather than Store-specific Core logic.
9. Make uninstall non-destructive by default and purge explicitly destructive.
10. Declare zero Page Builder widgets until real Store widgets exist.

## 17. Deferred to Phase 2 — Product Domain

- product identity and schema;
- product types;
- variant rules;
- categories and taxonomy constraints;
- attributes and options;
- product/media association contracts;
- publication state;
- pricing value objects and policies;
- initial inventory semantics;
- Product application services and repositories;
- Product migrations, factories, and seeders;
- Product admin UI and storefront catalog;
- Product policies, requests, events, and dedicated tests.

Cart, checkout, orders, full COD flow, payments, shipping, promotions, inventory reservations, and Exhibitions integration remain deferred to their documented later phases.

## 18. Backup and Rollback

Backup location:

```text
/var/www/store.z4rank.com/laravel/backups/codex/store-foundation-20260722-first-stage
```

Contents include:

- a full MySQL dump (`database.sql`, 143,356,978 bytes);
- the complete legacy Store module;
- every modified Core file;
- the previous `phpunit.xml`.

The legacy module was moved into the backup, not irreversibly deleted.

## 19. Diff Statistics

The server is not a Git working tree, so `git diff` cannot operate against an index. Equivalent statistics were produced with `git diff --no-index --stat` against the pre-change backup.

```text
Store module: 67 files changed, 1089 insertions(+), 607 deletions(-)
PluginInstaller.php: 3 insertions(+)
PluginActivator.php: 8 insertions(+), 1 deletion(-)
PluginDeactivator.php: 17 insertions(+)
PluginManager.php: 11 insertions(+)
PluginUninstaller.php: 11 insertions(+), 1 deletion(-)
PluginUninstallFlow.php: 50 insertions(+), 32 deletions(-)
PluginLifecycleHookRunner.php: 90 insertions(+)
phpunit.xml: 4 insertions(+)
```

## 20. Remaining Risks and Operational Notes

- The Core plugin state model still exposes the existing stable states `installed`, `active`, and `disabled`; richer transitional/health states from the architecture document remain a future generic Core enhancement.
- Purge is exposed at the service layer but no purge UI was added, intentionally, because full lifecycle administration UI is outside this Store phase.
- The two unrelated Auth tests described above keep the entire pre-existing project suite from being fully green.
- Artisan must be executed as `www-data` on this server because Laravel log files are owned by the web user. One diagnostic command executed as `z4admin` produced a CLI-only log-permission exception referencing Blog route logging; it did not affect web runtime or Store, and all deployment/test commands were subsequently executed as `www-data`.
- No screenshots were required for acceptance because the active/inactive route behavior, placeholder response, navigation state, and Core resilience were verified through feature tests and live HTTP smoke checks.

## 21. Checklist Against `STORE-MASTER-00`

- [x] Store is an independent module.
- [x] Explicit manifest and provider exist.
- [x] No required Payment Gateway dependency.
- [x] COD is built into Store.
- [x] Online payment implementations remain outside Store.
- [x] Exhibitions remains independent.
- [x] Core contains no Store business logic or Store-specific condition.
- [x] Public contracts do not leak Eloquent models or table names.
- [x] Deactivation preserves data and disables runtime behavior.
- [x] Uninstall and purge are separate.
- [x] Routes and admin navigation are lifecycle-aware.
- [x] Jobs and listeners are absent in Phase 1 and cannot execute while inactive.
- [x] Page Builder widgets are absent and therefore cannot leak while inactive.
- [x] Capability absence has safe behavior.
- [x] No WordPress-style post/meta or open hook architecture was introduced.
- [x] Architecture, unit, feature, and lifecycle tests exist and pass.
- [x] Disabled and reactivation scenarios were verified on the active server.

## 22. Final Status

The Store foundation is installed and active. It is independently loadable, safely disableable, reactivatable without record loss, free of Payment Gateway and Exhibitions hard dependencies, and ready for the documented Product Domain phase.
