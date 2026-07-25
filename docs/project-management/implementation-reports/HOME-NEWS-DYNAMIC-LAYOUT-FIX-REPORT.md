# Home and News Dynamic Layout Fix Report

## Task title
Home page layout repair and News page dynamic content conversion.

## Objective
Repair the broken Home page dynamic layout and convert the News page from static imported HTML into dynamic content pulled from existing blog posts.

## Scope
- No plugin files were modified.
- Changes were limited to `platform_pages` records for:
  - Home page: `slug = home`
  - News page: `slug = news`
- Existing page builder storage remains unchanged.

## Root cause
Home page:
- The page already had the dynamic homepage widget marker.
- The layout appeared broken because the dynamic layout CSS needed stronger final overrides for shell width, grids, cards, media sizing, and responsive behavior.

News page:
- The page was still using a static imported archive template.
- It did not contain the dynamic archive marker used by the platform render service.

## Changes performed
Home page:
- Kept the existing dynamic marker:
  `data-pb-widget="art-inpa-front-news-theme.classic-home-layout"`
- Appended a scoped CSS repair block:
  `Codex: dynamic homepage layout repair`
- The repair normalizes:
  - main shell width
  - hero grid
  - card grids
  - image/object sizing
  - sponsored sidebar
  - responsive behavior

News page:
- Replaced static page HTML with the dynamic archive marker:
  `data-platform-blog-archive="latest-posts"`
- Appended a scoped CSS block:
  `Codex: dynamic news archive layout`
- News now uses the existing platform render flow to pull published blog posts.

## Database records changed
- `platform_pages.id = 83`
  - title: الرئيسية
  - slug: home
- `platform_pages.id = 75`
  - title: الأخبار
  - slug: news

## Backup paths
- `storage/app/codex-file-backups/20260705-120240-home-news-dynamic-layout/page-83-before.json`
- `storage/app/codex-file-backups/20260705-120240-home-news-dynamic-layout/page-75-before.json`

## Verification
Rendered Home page contains:
- `ainpa-classic-home`
- `ainpa-classic-hero-row`

Rendered News page contains:
- `ainpa-dynamic-archive`
- `ainpa-archive-grid`

Static News template markers were removed from the active News page HTML.

## Cache action
- Laravel compiled views were cleared successfully.

## Plugin safety
No plugin files were modified.

## Known limitation
Visual spacing may still need small design tuning after browser review, but the content source and layout foundation are now dynamic.

## Rollback
Restore the page records from the JSON backups listed above if needed.
