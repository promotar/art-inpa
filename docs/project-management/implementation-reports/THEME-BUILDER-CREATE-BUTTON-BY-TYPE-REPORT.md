# Theme Builder Create Button By Type Report

## Task Title
Theme Builder create button naming by template type.

## Objective
Make template creation clearer by showing create buttons named according to the current Theme Builder template type.

## Files Modified
- `resources/views/admin/theme-builder/index.blade.php`

## What Changed
- Kept the general `Create Template` button in the page header and Templates tab.
- Added type-specific create buttons inside each Theme Builder type tab:
  - `Create Header`
  - `Create Footer`
  - `Create Single Post`
  - `Create Single Page`
  - `Create Archive`
  - `Create Search Results`
  - `Create 404 Page`
- Clicking a type-specific button opens the Templates creation form.
- The creation form automatically selects the matching template type.
- The Templates tab remains a general list for all stored templates.

## Technical Notes
- Added `data-template-type` to type-specific create buttons.
- Updated the Theme Builder JavaScript to set the `template_type` select field before showing the create form.
- Hardened the Blade hidden-state check so it does not fail if `$errors` is unavailable in direct render verification.

## Verification
- Blade view cache passed.
- Direct render verification passed with a super-admin user.
- Confirmed the page contains:
  - `Create Template`
  - `Create Header`
  - `Create Footer`
  - Create form markup
  - All templates list

## Known Limitations
- This task only changes the create button UX.
- It does not change template storage, conditions, preview, or rendering logic.

## Rollback Notes
Restore the previous `resources/views/admin/theme-builder/index.blade.php` to remove type-specific create buttons.
