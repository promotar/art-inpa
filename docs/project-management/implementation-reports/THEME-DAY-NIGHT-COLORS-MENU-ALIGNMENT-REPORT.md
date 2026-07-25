# Theme Day/Night Colors and Header Menu Alignment Report

## Task Title
Theme-wide day/night colors and header menu alignment fix.

## Objective
Make day/night mode apply across the public website, allow color control from platform settings, and fix the header menu row so it does not drop below the Latest area.

## Scope
The implementation was limited to core platform files and the active Theme Builder header CSS.

No plugin files were modified.

## Files Modified
- `app/Platform/Core/Services/SettingsRepository.php`
- `app/Platform/Core/Rendering/PlatformContentRenderer.php`
- `app/Platform/Core/PageBuilder/PageBuilderRenderService.php`
- `resources/views/admin/settings/index.blade.php`

## Database Records Updated
- Table: `platform_theme_builder_templates`
- Record: `id = 1`
- Template: `Header`
- Updated fields:
  - `css`
  - `page_builder_json` set to `null`
  - `updated_at`

## Settings Added
A new settings group was added:

```text
Theme Settings
```

The following color settings are available:
- `theme.light_background`
- `theme.light_surface`
- `theme.light_text`
- `theme.light_muted_text`
- `theme.dark_background`
- `theme.dark_surface`
- `theme.dark_text`
- `theme.dark_muted_text`
- `theme.accent_color`

## Admin UI
The settings page now supports a `color` field type.

Each color field has:
- Native color picker.
- Hex text preview input.
- Server-side hex validation through settings normalization.

## Public Theme CSS
The renderer now injects global CSS variables:

```css
--art-color-background
--art-color-surface
--art-color-text
--art-color-muted
--art-color-accent
```

When the browser has:

```text
html.art-dark-mode
```

the variables switch to the configured dark colors.

## Public Website Coverage
The global theme CSS is included through:
- `PlatformContentRenderer::layoutCss()`
- `PageBuilderRenderService::layoutCss()`

This covers:
- Dynamic header/footer rendering.
- Page Builder rendered pages.
- Public page previews.

## Header Menu Alignment Fix
The active header menu row was changed from a grid layout to a flex layout.

This keeps:
- Fav icon.
- Latest menu.
- Frontend menu.

on the same row on desktop.

The frontend menu now uses horizontal overflow protection so long menus do not push the row down.

## Backup Created
Before applying the change, backups were created:

```text
storage/app/theme-builder-template-backups/20260703-174012-theme-colors-menu-row
storage/app/code-backups/20260703-theme-colors-menu-row
```

## Verification Performed
- PHP syntax checks passed for:
  - `SettingsRepository.php`
  - `PlatformContentRenderer.php`
  - `PageBuilderRenderService.php`
- Laravel caches rebuilt:
  - `optimize:clear`
  - `config:cache`
  - `route:cache`
  - `view:cache`
- Confirmed `theme` settings were synced into `platform_settings`.
- Confirmed public output contains:
  - `--art-color-background`
  - `html.art-dark-mode`
  - `art-header-primary-menu`
  - `data-art-theme-toggle`

## Known Limitations
- Day/night mode is browser-local using `localStorage`.
- The current implementation provides global theme variables and broad public selectors. Some third-party or deeply custom inline styles may still need manual CSS mapping later.
- The settings page verification by raw HTTP requires an authenticated browser session, so database and public output checks were used from the server.

## Rollback Notes
Restore the backups listed above, or restore the previous header template CSS from `platform_theme_builder_templates` record `id = 1`.

