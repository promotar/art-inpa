# Home and News Dynamic Content / Width Fix Report

## Task Title
Fix Home full-width dynamic sections and convert News page from static imported content to dynamic blog content.

## Objective
The Home page had sections escaping the intended page container after recent template work.

The News page still contained static imported HTML from the uploaded template and needed to render current platform blog posts dynamically.

## Scope
This task was implemented in the core page rendering layer and the `platform_pages` records for:

- `home`
- `news`

No plugin code was modified.

## Files Created Locally
- `D:\Codex\Z4Rank Platform\Codex Files\apply-home-news-dynamic.php`
- `D:\Codex\Z4Rank Platform\Codex Files\verify-home-news-dynamic.php`
- `D:\Codex\Z4Rank Platform\Codex Files\HOME-NEWS-DYNAMIC-CONTENT-WIDTH-FIX-REPORT.md`

## Files Modified
- `/var/www/store.z4rank.com/laravel/app/Platform/Core/PageBuilder/PageBuilderRenderService.php`

## Database Records Updated
Table:

- `platform_pages`

Updated pages:

- `home`
- `news`

The `home` page now stores a small dynamic widget placeholder instead of static duplicated layout HTML.

The `news` page now stores a dynamic blog archive placeholder instead of imported static News5 HTML.

## Dynamic Home Flow
The renderer now detects:

```html
data-pb-widget="art-inpa-front-news-theme.classic-home-layout"
```

When this marker exists, the system:

1. Loads published rows from `blog_posts`.
2. Joins `blog_categories` and `users` when available.
3. Generates the Home news layout using real post data.
4. Keeps all generated sections inside a constrained shell container.
5. Uses consistent card image ratios and responsive grids.

## Dynamic News Flow
The renderer now detects:

```html
data-platform-blog-archive="latest-posts"
```

When this marker exists, the system:

1. Loads published posts from `blog_posts`.
2. Renders a dynamic News archive grid.
3. Renders category links from `blog_categories`.
4. Renders a sidebar with most-read style links and category chips.
5. Removes dependency on static imported template article content.

## Width Fix
The Home layout now uses a shared shell:

```css
width: min(calc(100% - 48px), 1120px);
max-width: 1120px;
margin-inline: auto;
```

This keeps Home sections inside the same visual page width instead of letting parts become full width.

## Backup Created
Service backup:

- `/var/www/store.z4rank.com/laravel/storage/app/codex-file-backups/20260704-120329-home-news-dynamic-service/PageBuilderRenderService.php`

Page HTML/CSS backup:

- `/var/www/store.z4rank.com/laravel/storage/app/codex-file-backups/20260704-120329-home-news-dynamic/home.html`
- `/var/www/store.z4rank.com/laravel/storage/app/codex-file-backups/20260704-120329-home-news-dynamic/home.css`
- `/var/www/store.z4rank.com/laravel/storage/app/codex-file-backups/20260704-120329-home-news-dynamic/news.html`
- `/var/www/store.z4rank.com/laravel/storage/app/codex-file-backups/20260704-120329-home-news-dynamic/news.css`

## Commands Executed
Syntax checks:

```bash
php -l /tmp/PageBuilderRenderService.php
php -l /tmp/apply-home-news-dynamic.php
php -l app/Platform/Core/PageBuilder/PageBuilderRenderService.php
```

Laravel cache rebuild:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Verification script:

```bash
php /tmp/verify-home-news-dynamic.php
```

## Verification Result
Database verification:

- `home` page contains dynamic home widget: yes
- `news` page contains dynamic archive marker: yes
- static News5 markers removed from `news`: yes

Public render verification:

- `/` loaded successfully.
- `/pages/news` loaded successfully.
- public Home contains the dynamic classic layout.
- public News contains the dynamic archive layout.
- old static News5 labels such as `Archive - Modern Layout` and `Politics` were not detected.

## Known Limitations
- This is a server-side dynamic renderer, not a full visual layout editor for the generated news blocks.
- The generated layout uses the latest published posts globally.
- Category-specific archives can be expanded later if needed.

## Rollback Notes
To roll back, restore:

1. `PageBuilderRenderService.php` from the service backup folder.
2. `home.html`, `home.css`, `news.html`, and `news.css` from the page backup folder into the corresponding `platform_pages` records.
3. Rebuild Laravel caches.

## Final Status
Completed.
