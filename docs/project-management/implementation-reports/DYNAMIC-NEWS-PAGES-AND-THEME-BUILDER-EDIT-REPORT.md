# Dynamic News Pages And Theme Builder Edit Report

## Task Title

Dynamic article content for Home New and News pages, plus Theme Builder template edit option.

## Objective

Make the `home-new` and `news` platform pages read published article content dynamically.

Add an `Edit` option for stored Theme Builder templates.

## Important Constraint

No plugin files were modified.

The existing Art INPA news renderer was used through existing `data-art-news-element` markers only.

## Files Modified

`app/Http/Controllers/Admin/ThemeBuilderController.php`

`routes/web.php`

`resources/views/admin/theme-builder/partials/template-card.blade.php`

## Files Created

`resources/views/admin/theme-builder/edit.blade.php`

## Database Records Updated

`platform_pages`

Updated pages:

`home-new`

`news`

Both pages now contain dynamic Art INPA news markers such as:

`data-art-news-element="hero-news"`

`data-art-news-element="latest-news-grid"`

`data-art-news-element="category-news-block"`

`data-art-news-element="dynamic-categories"`

## Revision Snapshots

Before updating each page, a snapshot was inserted into:

`platform_page_revisions`

Snapshot reason:

`dynamic-news-content-before-update`

Verified snapshots:

`home-new`: 1

`news`: 1

## Home New Behavior

`home-new` now uses dynamic sections for:

Main cover news

Art INPA

Art World

Latest News

Good News

Categories

The page remains `draft`, matching its previous status.

## News Page Behavior

`news` now uses dynamic sections for:

News hero

News article grid

Categories

The page remains `published`.

## Theme Builder Edit Behavior

Added `Edit` button beside `Preview` and `Delete`.

Added edit route:

`GET /admin/theme-builder/templates/{template}/edit`

Added update route:

`PATCH /admin/theme-builder/templates/{template}`

Editable fields:

Template type

Status

Name

Description

HTML

CSS

Page Builder JSON

Optional replacement template file upload

## Verification

PHP syntax checks passed:

`app/Http/Controllers/Admin/ThemeBuilderController.php`

`routes/web.php`

Dynamic page update script

Laravel cache rebuilt successfully:

`php artisan config:cache`

`php artisan route:cache`

`php artisan view:cache`

Route list now includes 7 Theme Builder routes, including edit and update.

Public `News` page check:

HTTP `200`

Rendered output includes dynamic news classes:

`ainpa-news-hero`

`ainpa-news-card`

## Known Notes

`home-new` returns public `404` because it is still draft.

It can be previewed from admin or published later if desired.

No plugin implementation was changed.

## Rollback Notes

Restore either page from its latest page revision snapshot.

Revert the Theme Builder controller, route, partial, and edit view if the edit UI should be removed.

