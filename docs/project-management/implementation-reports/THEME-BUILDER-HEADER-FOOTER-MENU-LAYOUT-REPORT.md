# Theme Builder Header And Footer Menu Layout Report

## Task Title
Add editable header menu and four-column footer menu layout.

## Objective
Update the active Theme Builder Header and Footer templates so they remain editable with the Page Builder while using database-backed platform menus.

## Scope
- Updated Theme Builder template records only.
- No plugin files were modified.
- No normal Pages records were changed.
- Header and Footer remain stored in `platform_theme_builder_templates`.

## Header Changes
- Kept the red top bar as a news ticker.
- Added a dynamic Menu widget in the white header row where the blue marked line was shown.
- The menu uses:
  `data-platform-menu-key="platform.frontend"`
- The public renderer replaces the placeholder links with the database menu items.
- Header logo uses:
  `data-platform-logo="site"`
- Header remains editable through:
  `/admin/theme-builder/templates/1/builder`

## Footer Changes
- Rebuilt the first footer section as four columns.
- Column 1 contains the site logo and a short Art INPA description.
- Column 2 uses:
  `data-platform-menu-key="platform.foter-blok1"`
- Column 3 uses:
  `data-platform-menu-key="platform.foter-blok2"`
- Column 4 uses:
  `data-platform-menu-key="platform.foter-blok3"`
- Footer remains editable through:
  `/admin/theme-builder/templates/3/builder`

## Storage Updated
- Header template:
  - ID: 1
  - Table: `platform_theme_builder_templates`
  - Fields updated: `html`, `css`, `page_builder_json`, `source_type`, `updated_at`
- Footer template:
  - ID: 3
  - Table: `platform_theme_builder_templates`
  - Fields updated: `html`, `css`, `page_builder_json`, `source_type`, `updated_at`

## Page Builder Compatibility
- `page_builder_json` was set to `null` for Header and Footer so the builder loads the current HTML/CSS.
- On the next visual save, the Page Builder will generate a fresh builder project JSON.
- The templates remain editable using the same Theme Builder Page Builder flow.

## Backup
- A backup of the old Header and Footer HTML, CSS, and builder JSON was created before updating.
- Backup location:
  `storage/app/theme-builder-template-backups/20260703-150133`

## Verification
- Laravel cache cleared and rebuilt.
- Config cache rebuilt.
- Route cache rebuilt.
- View cache rebuilt.
- Public homepage render checked with `curl`.
- Confirmed rendered header contains:
  `data-platform-menu-key="platform.frontend"`
- Confirmed rendered footer contains:
  `platform.foter-blok1`
  `platform.foter-blok2`
  `platform.foter-blok3`
- Confirmed Header and Footer builder routes are protected and redirect unauthenticated requests to login.

## Known Limitations
- The red news ticker is currently static text inside the template.
- The footer menu labels depend on the current menu records and can be changed from Menus.
- A fresh Page Builder JSON project will be generated only after opening and saving each template in the visual builder.

## Rollback Notes
- Restore the backup files from:
  `storage/app/theme-builder-template-backups/20260703-150133`
- Update the Header and Footer records in `platform_theme_builder_templates`.
- Clear Laravel caches after rollback.
