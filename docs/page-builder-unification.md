# Unified PageBuilder

## Purpose

`modules/PageBuilder` is the only page-building runtime. It combines the
former Front Builder page hierarchy and navigation metadata with the platform's
existing schema-first editor, revisions, sanitization, rendering, and dynamic
content support.

The plugin is a required platform capability. Its manifest declares
`core: true`, so it is installed and upgraded through the normal plugin
contract but cannot be deactivated, uninstalled, or purged.

## Source Of Truth

Pages use `platform_pages`. The plugin does not create a parallel page schema.
The table stores:

- page identity, slug, title, status, publication state, and SEO fields;
- schema-first layout, free-form HTML/CSS, builder components, and styles;
- hierarchy through `parent_id`;
- navigation metadata through `category`, `menu_label`, and `show_in_menu`.

Revision history remains in `platform_page_revisions`.

## Routes

Canonical routes are declared by the plugin:

- `admin.pages.*` for administration;
- `pages.show` at `/pages/{slug}` for public rendering.

`/admin/front-builder/pages`, `/admin/plugins/page-builder/pages`, and
`/page/{slug}` are compatibility redirects. They contain no page-building
logic and may be removed only after stored legacy links are retired.

## Lifecycle

The merge migration and `PageBuilderLifecycle` are idempotent. They:

1. add the unified hierarchy/navigation columns;
2. import `front_builder_pages` when an older installation contains it;
3. preserve content and generate a deterministic unique slug on collision;
4. migrate legacy home-page settings, permissions, and menu routes;
5. remove the imported legacy table and duplicate plugin registry/menu rows.

Core runs `RequiredCorePluginSynchronizer` before plugin provider and route
discovery. If the PageBuilder database/runtime state is manually disabled, the
synchronizer restores the manifest-declared required state.

## Upgrade And Recovery

Package updates validate the complete plugin before changing runtime state.
An active core plugin skips deactivation during update and is reactivated after
installation so routes and providers always converge on the new package.

The pre-merge local recovery point is:

`backups/page-builder-unification-20260725-001752`

Verify `SHA256SUMS.txt` before restoring. Restore source and original packages
first; import `database.sql` only for a complete local database rollback. Then
rebuild autoload, clear/optimize Laravel caches, synchronize plugin assets, and
restart the application services.
