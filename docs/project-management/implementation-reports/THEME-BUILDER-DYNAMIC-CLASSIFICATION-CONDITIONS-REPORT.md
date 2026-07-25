# Theme Builder Dynamic Classification and Display Conditions Report

## Task Title
Classify Theme Builder templates and add display conditions.

## Objective
Keep normal content pages in `Pages` and use `Theme Builder` only for dynamic templates that can apply to multiple targets.

## Classification Applied
Theme Builder is now intended for:
- Header
- Footer
- Single Post
- Single Page template
- Archive
- Search Results
- 404 Page

Pages remain in `Pages`:
- Home
- About
- FAQ
- Contact
- Privacy Policy
- Terms of Service
- Sign In
- Sign Up
- Any regular content page

## Files Created
- `database/migrations/2026_07_01_000001_create_platform_theme_builder_conditions_table.php`

## Files Modified
- `app/Http/Controllers/Admin/ThemeBuilderController.php`
- `resources/views/admin/theme-builder/index.blade.php`
- `routes/web.php`
- `config/platform_registry.php`

## Database Table Added
`platform_theme_builder_conditions`

Columns:
- `id`
- `page_id`
- `operator`
- `scope`
- `target_value`
- `created_at`
- `updated_at`

## Routes Added
- `PATCH admin/theme-builder/{page}/conditions`
- Route name: `admin.theme-builder.conditions.update`

## Registry Updated
`admin.theme-builder.*` now allows:
- `GET`
- `PATCH`

## Display Condition Options
Each Theme Builder template can now store:
- Include or Exclude
- Scope:
  - Entire Site
  - Front Page
  - All Pages
  - Specific Pages
  - All Posts
  - Specific Posts
  - Post Categories
  - Archives
  - Search Results
  - 404 Page
- Optional target value such as slug, category, or ID list.

## Filtering Behavior
The `Single Page` tab no longer lists all regular pages.

It only shows records whose title, slug, or block key indicate a reusable page template, such as:
- `single-page`
- `page-template`
- `default-page`
- `page-layout`

## Verification
- PHP syntax checks passed.
- View cache cleared.
- Route cache cleared.
- Config cache cleared.
- Migration ran successfully.
- `platform_theme_builder_conditions` migration status is `Ran`.
- Theme Builder GET and PATCH routes exist.
- Platform Registry accepts the PATCH condition route.
- Theme Builder view rendered successfully.

## Safety Notes
- No plugin files were modified.
- Existing regular pages were not moved or deleted.
- Existing Page Builder storage remains `platform_pages`.
- The display condition table is additive and non-destructive.

## Known Limitations
- This task adds condition storage and UI.
- The final front-end condition resolver can be implemented in a later focused task when global template rendering rules are connected.

## Manual Test Steps
1. Open `/admin/theme-builder`.
2. Open the `Single Page` tab.
3. Confirm regular pages like FAQ and Sign Up are no longer listed there.
4. Open Header or Footer.
5. Change Display Conditions.
6. Save.
7. Confirm the success message appears and the saved condition remains selected.
