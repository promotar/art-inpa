# Page Builder Image Link Clickable Fix

## Task

Fix page builder images that show a custom link URL in the editor settings but are not clickable on the frontend.

## Root Cause

Saved images could contain `data-pb-image-action="custom"` with a valid `data-pb-link-url`. The frontend renderer only recognized `link` and `lightbox`, so legacy/custom image action values were ignored and the image was rendered as a plain `<img>`.

The builder also read/wrote image link action state inconsistently between the selected component and the real image target.

## Backup

```text
/root/codex-backups/image-link-clickable-20260627-011439
```

## Files Changed

- `app/Platform/Core/Rendering/PlatformContentRenderer.php`
- `public/vendor/front-builder/page-builder/page-builder.js`

## Changes

- Added canonical image action handling in the frontend renderer:
  - `custom`, `custom_url`, and `link` render as clickable image links.
  - `media_file` and `lightbox` render as lightbox links.
- Updated the page builder settings panel to read/write image action values on the real image component target.
- Added builder-side normalization so future saves write canonical `link`/`lightbox` values.

## Verification

- `php -l app/Platform/Core/Rendering/PlatformContentRenderer.php`: passed.
- `node --check public/vendor/front-builder/page-builder/page-builder.js`: passed.
- `php codex_tmp/verify_image_link_render.php`: `image_link_render=passed`.
- `php artisan optimize:clear --no-ansi`: passed.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- `php artisan view:cache --no-ansi`: passed.
- `php artisan route:cache --no-ansi`: passed.
- `php artisan config:cache --no-ansi`: passed.
- HTTP `/`: 200.
- HTTP `/login`: 200.
- Root page HTML contains `pb-image-action pb-image-link`.
- Browser DOM verification: `/` has one `a.pb-image-action.pb-image-link` containing an image with `href="http://10.10.0.20/"`; console errors/warnings: none.

## Note

Existing saved pages with `data-pb-image-action="custom"` now work without manually resaving. When the page is opened and saved again, the builder normalizes the action value to the canonical `link`.
