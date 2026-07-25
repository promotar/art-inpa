# Plugin Package Contract

This contract is mandatory for every feature, theme, and service plugin on
Art INPA Platform `2.5.0`. The same preflight runs for extracted ZIP uploads,
directory installs, updates, and activation.

## Validation Boundary

`PluginPackageValidator` completes before Core moves files into `modules`, runs
migrations, writes plugin metadata, or publishes assets. It aggregates all
detected defects into one `PluginPackageValidationException`, which the admin
upload flow returns under the plugin ZIP field.

An invalid fresh package leaves no installed module directory and no `plugins`
row. An already installed package that is missing or corrupt remains disabled
when activation validation fails.

Uploaded archives are limited to 5,000 entries and 200 MB after extraction.
Absolute/traversal paths, executable or secret-bearing file extensions,
dependency trees, and archives containing more than one `module.json` are
rejected before extraction or package validation.

## Required Manifest

The package root contains exactly one readable `module.json`. These fields are
required:

- `name`: human-readable name, at most 120 characters.
- `slug`: lowercase kebab-case stable identifier.
- `version`: semantic version such as `1.2.0`.
- `type`: one of `feature`, `theme`, or `service`.
- `platform_version`: supported platform constraint, for example
  `>=2.5.0 <3.0.0`.
- `description` and `author`.
- `provider`: fully qualified namespaced Laravel service provider.
- `provider_file`: safe package-relative PHP path to the provider.
- `uninstall`: complete ownership catalog with all six arrays present:
  `tables`, `settings`, `storage_paths`, `records`, `columns`, and
  `operation_target_prefixes`.

`dependencies` contains required plugin slugs. Every dependency must already
be installed during install/update and active during activation. Optional
integrations belong in `optional_dependencies`.

## Package Files

Core parses every PHP file with `TOKEN_PARSE`. Symlinks and `.git`, `vendor`,
and `node_modules` directories are rejected. A distributable package contains
its own application source and resources but not generated dependencies.

The provider file must declare the exact class from `provider`, extend Laravel
`ServiceProvider`, and must not call `Route::` or `loadRoutesFrom()`. Core alone
loads plugin routes through the route catalog and runtime gate.

When the plugin declares database tables, its migration directory must exist
and contain PHP migration files. Every literal `Schema::create()` table must
appear in `uninstall.tables`, and every declared owned table must have a
matching migration. Columns added through `Schema::table()` to shared tables
must be declared in `uninstall.columns`.

Declared lifecycle and documentation files must exist inside the package.
Paths are relative, contained within the package, and cannot include `..`.

## Route Catalog

Route definitions use the `routes.web`, `routes.admin`, and `routes.api`
objects. Each declared scope requires a `file`. Every PHP file under `routes`
must be declared; undeclared route files reject the package.

```json
{
  "routes": {
    "admin": {
      "file": "routes/admin.php",
      "prefix": "admin/plugins/example",
      "name": "admin.plugins.example.",
      "middleware": ["web", "auth"]
    }
  }
}
```

Core attaches the active-plugin middleware gate. A plugin provider never
bypasses this loader.

## Asset Catalog

If `resources/assets` contains CSS or JavaScript, `assets` is required. Every
source CSS/JS file must be listed under an `admin`, `frontend`, or `guest`
scope, and every listed file must exist.

```json
{
  "assets": {
    "source": "resources/assets",
    "admin": {
      "styles": ["admin/plugin.css"],
      "scripts": ["admin/plugin.js"]
    },
    "frontend": {
      "styles": ["frontend/plugin.css"],
      "scripts": []
    }
  }
}
```

Core publishes cataloged files through `PluginAssetRegistry` and emits them
only while `PluginRuntimeGate` allows the plugin.

## Error Contract

Validation errors identify the exact field, file, dependency, or ownership
entry that failed. A package with several defects returns all known reasons in
one message, for example:

```text
Plugin package validation failed: 1) Declared provider file [...] is missing.
2) Declared admin route file [...] is missing. 3) Required plugin dependencies
are not installed: example-foundation.
```

Package authors fix every listed reason and upload a new package. Core does not
partially install or attempt to repair an invalid third-party package.

## Release Verification

Run:

```bash
php artisan test tests/Feature/PluginPackageValidatorTest.php
```

The suite validates a correct fixture, aggregated failures, uncataloged assets,
activation after package corruption, all source modules, canonical
distributions, and actionable acceptance/rejection for every ZIP retained
under `modules`. Validation never deletes an archive merely because it is
incompatible.
