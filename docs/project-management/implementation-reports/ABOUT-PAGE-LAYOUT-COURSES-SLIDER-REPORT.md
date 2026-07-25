# About Page Layout, Courses Card, and People Slider Report

## Task Title
Art INPA About Page Layout Refinement

## Objective
Apply the requested visual/content changes to the draft About page without modifying plugin files:

- Remove the action link/icon from the courses card only.
- Keep service card numbers unchanged.
- Make the mission/story section use two equal columns.
- Make the story image fill its column.
- Convert the people/contributors section into a horizontal slider.
- Remove Bushra from the people section.
- Add Nemer Rabah and Maryam Al-Sudani cards.

## Scope
Database page content only:

- Table: `platform_pages`
- Page ID: `87`
- Slug: `about`
- Status kept as: `draft`

No plugin files were modified.

## Backups Created
- `storage/app/codex-file-backups/20260705-110323-about-layout-courses-slider/page-87-before.json`
- `storage/app/codex-file-backups/20260705-110835-about-people-slider/page-87-before.json`

## Changes Applied
- Removed the `زيارة منصة الدورات` button/link from the courses card.
- Preserved the courses card title and description.
- Updated the story section CSS so both columns use 50% width on desktop.
- Updated the story image CSS to use full width and height with `object-fit: cover`.
- Replaced the people grid with `art-about-people-slider`.
- Removed `بشرى كفاية`.
- Added:
  - `الأستاذ نمر رباح`
  - `الفنانة مريم السوداني`

## Verification
Verification script result:

```json
{
  "page_found": true,
  "page_id": 87,
  "status": "draft",
  "courses_button_removed": true,
  "people_slider": true,
  "bushra_removed": true,
  "nemer_added": true,
  "maryam_added": true,
  "story_two_columns": true,
  "story_image_full_cover": true,
  "plugin_files_modified": false
}
```

## Commands Executed
- Uploaded safe one-time update scripts to the server.
- Executed scripts as `www-data`.
- Cleared compiled views with `php artisan view:clear`.

## Known Limitations
- The people slider is currently a horizontal scroll slider with visual arrows.
- It does not yet auto-slide or use clickable arrow controls.

## Rollback Notes
Restore page ID `87` from either backup JSON if needed.
