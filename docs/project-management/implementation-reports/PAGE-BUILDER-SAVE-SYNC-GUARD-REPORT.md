# Page Builder Save Sync Guard Report

## Task Title
Prevent Admin Pages Builder from reopening or saving stale builder JSON over the latest page HTML.

## Issue Summary
The About page had newer `html` / `content`, but an older `page_builder_json`.
When the page builder opened, it preferred the older JSON project and could overwrite the latest page edits during save.

## Fix Applied
- Added a builder sync metadata key to saved page builder JSON.
- The metadata stores a content hash generated from the saved HTML and CSS.
- On editor load, `page_builder_json` is accepted only when its stored hash matches the current saved HTML and CSS.
- If the JSON is missing sync metadata or does not match, the editor falls back to the latest saved HTML and CSS.
- Revision restore also re-syncs restored JSON with restored HTML/CSS.

## Files Modified
- `/var/www/store.z4rank.com/laravel/app/Http/Controllers/Admin/PageController.php`

## Files Not Modified
- No plugin files were modified.
- No database schema was changed.
- No routes were changed.
- No migrations were run.

## Backup Created
- `/var/www/store.z4rank.com/laravel/storage/app/codex-file-backups/20260706-104921-PageController-before-builder-sync-guard.php`

## Verification
- PHP syntax check passed for the staged controller.
- PHP syntax check passed after installing the controller.
- Laravel cache was cleared with `php artisan optimize:clear`.
- Runtime verification:
  - Current About page without JSON: `fallback_html`
  - Old stale revision JSON: `fallback_html`
  - Newly synced JSON: `loaded`

## Expected Behavior
- Opening the page builder now uses the latest saved HTML/CSS when JSON is stale.
- Saving from the page builder creates JSON with a fresh sync hash.
- Future editor opens will load JSON only when it matches the latest saved content.

## Rollback Notes
Restore the backup controller file if this guard needs to be reverted.
