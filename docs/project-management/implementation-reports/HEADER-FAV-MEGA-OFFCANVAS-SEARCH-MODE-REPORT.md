# Header Fav, Mega Menu, Offcanvas, Search, and Mode Toggle Report

## Task Title
Enhance active Theme Builder header controls.

## Objective
Update the active public header so it supports:
- Dynamic favicon-style icon beside Latest.
- Latest hover mega menu with article cards.
- Left offcanvas menu from platform menus.
- Working search icon.
- Working day/night mode icon.
- Account icon linked to My Account.

## Scope
The work was limited to the active Theme Builder header template and the core platform content renderer.

No plugin files were modified.

## Files Modified
- `app/Platform/Core/Rendering/PlatformContentRenderer.php`

## Database Records Updated
- Table: `platform_theme_builder_templates`
- Record: `id = 1`
- Template: `Header`
- Updated fields:
  - `html`
  - `css`
  - `page_builder_json` set to `null`
  - `source_type` set to `page_builder`
  - `updated_at`

## Header Features Added

### Dynamic Fav Icon
The icon beside `Latest` now uses:

```html
data-platform-site-icon="favicon"
```

The renderer resolves it from `general.site_icon`.

If no icon exists, it falls back to the site logo.

### Latest Mega Menu
The `Latest` item now contains a hover mega menu:

```html
data-platform-latest-mega="latest-posts"
data-platform-news-limit="8"
```

It pulls latest published articles from:

```text
blog_posts
```

Each card includes:
- Featured image.
- Article title.
- Link to the public article.

The layout shows 4 cards at once and slides forward by 1 card per step using CSS animation.

### Offcanvas Menu
The hamburger icon opens a left offcanvas panel.

The offcanvas contains:
- Site logo.
- Dynamic menu rendered from:

```html
data-platform-menu-key="platform.frontend"
```

This allows menu selection and management from the existing Menus system.

### Search
The search icon opens a search overlay.

The form submits to:

```text
/blog?search={query}
```

### Day/Night Mode
The moon icon now toggles a dark mode class:

```text
html.art-dark-mode
```

The selected mode is stored in browser localStorage.

### Account Icon
The account icon links to:

```text
/account
```

## Backup Created
Before applying the change, a backup checkpoint was created:

```text
storage/app/theme-builder-template-backups/20260703-170822-header-offcanvas-mega
```

## Verification Performed
- PHP syntax check passed for:
  - `app/Platform/Core/Rendering/PlatformContentRenderer.php`
- Laravel caches rebuilt:
  - `optimize:clear`
  - `config:cache`
  - `route:cache`
  - `view:cache`
- Public page HTML was checked for:
  - `data-platform-site-icon`
  - `art-header-offcanvas`
  - `art-header-search`
  - `data-platform-latest-mega`
  - `art-header-mega-card`
  - `data-art-theme-toggle`
  - `/account`

All markers were present.

## Page Builder Compatibility
The header remains editable through Theme Builder / Page Builder.

The dynamic behavior is attached using `data-platform-*` attributes so the layout remains editable while the renderer injects live content on public output.

## Known Limitations
- Search currently submits to the blog listing using a `search` query parameter.
- The mega menu slider is CSS-based and pauses on hover.
- Category-specific latest filtering was not added in this task.

## Rollback Notes
Restore the backup folder listed above, or restore the previous `platform_theme_builder_templates` record for header template `id = 1`.

