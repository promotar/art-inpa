# Admin Theme

Default protected admin dashboard theme for the Art INPA platform.

## Purpose

This plugin changes the admin dashboard visual layer through the existing platform plugin asset pipeline. It does not edit Laravel core views, does not create database settings, and does not require JavaScript.

## Install

1. Upload `admin-theme.zip` from the admin plugin installer.
2. Install the plugin.
3. Activate the plugin.
4. Confirm the CSS was published to:

```text
public/platform/plugins/admin-theme/css/admin-theme.css
```

5. Confirm the admin layout includes a stylesheet link with:

```text
data-plugin-admin-style="admin-theme"
```

## Admin Theme Policy

- `admin-theme` is the default protected admin theme.
- The platform keeps exactly one admin theme active.
- Activating another admin theme disables `admin-theme`.
- Deactivating the active custom admin theme automatically restores
  `admin-theme`.
- Directly deactivating `admin-theme` is blocked while it is the fallback admin
  theme.

## Fast Editing

The main editable file is:

```text
resources/assets/css/admin-theme.css
```

The first section of the file contains CSS variables. Edit those values for quick changes:

- `--ainpa-admin-bg`: page background.
- `--ainpa-admin-sidebar`: sidebar base color.
- `--ainpa-admin-sidebar-deep`: sidebar deep color.
- `--ainpa-admin-primary`: primary blue.
- `--ainpa-admin-primary-strong`: stronger active blue.
- `--ainpa-admin-radius`: default control radius.
- `--ainpa-admin-shadow`: default surface shadow.
- `--ainpa-admin-sidebar-width`: sidebar width. If changed, also verify page padding on desktop.

After editing a deployed CSS file directly, clear browser cache or change the file timestamp. If edited through the platform plugin package, reinstall/update the plugin so the asset publisher republishes the CSS.

## Compatibility

- Uses `assets.admin.styles` in `module.json`.
- Expects `AdminPluginAssetManager::styles()` to inject active admin plugin styles.
- Targets the current admin layout classes such as `z4-admin-bar`, `z4-admin-sidebar`, `z4-admin-link`, and standard Tailwind utility classes used by admin pages.
- Avoids core Blade changes and avoids database-backed settings for v1.

## Verification

Run locally before upload:

```bash
php -l src/ArtInpaAdminProThemeServiceProvider.php
```

After upload and activation, check several admin pages:

- Dashboard
- Plugins
- Theme Editor
- Blog
- Media
- Settings

Confirm sidebars, cards, tables, forms, modals, and floating assistant widgets do not overlap.
