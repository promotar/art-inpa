# Theme Builder Page Builder Editing Report

## Task Title
Theme Builder templates edited through the active Page Builder.

## Objective
Allow Theme Builder templates such as Header, Footer, Single Post, Single Page, Archive, Search Results, and 404 Page to be edited with the same visual Page Builder used by normal pages, while keeping Theme Builder template storage separate from `platform_pages`.

## Scope
- Theme Builder templates remain stored in `platform_theme_builder_templates`.
- Display conditions remain stored in `platform_theme_builder_template_conditions`.
- No plugin files were modified.
- No template records were moved into `platform_pages`.
- Normal content pages remain managed through Pages.

## Files Created
- `resources/views/admin/theme-builder/builder.blade.php`

## Files Modified
- `app/Http/Controllers/Admin/ThemeBuilderController.php`
- `routes/web.php`
- `resources/views/admin/theme-builder/partials/template-card.blade.php`

## Routes Added
- `GET /admin/theme-builder/templates/{template}/builder`
- `PATCH /admin/theme-builder/templates/{template}/builder-save`
- `PATCH /admin/theme-builder/templates/{template}/autosave`
- `GET /admin/theme-builder/templates/{template}/editor-preview.css`
- `POST /admin/theme-builder/templates/{template}/editor-component-preview`

## Controller Methods Added
- `builder()`
- `builderSave()`
- `autosave()`
- `editorPreviewCss()`
- `editorComponentPreview()`

## Helper Methods Added
- `builderContext()`
- `builderProject()`
- `saveBuilderTemplate()`
- `uniqueTemplateSlugForUpdate()`
- `allowsUnsafeBuilderMarkup()`
- `firstElementInnerHtml()`

## Edit Flow
1. Admin opens Theme Builder.
2. Admin clicks `Edit` on any Theme Builder template.
3. The system opens the active Page Builder UI at:
   `/admin/theme-builder/templates/{template}/builder`
4. The builder loads the template HTML, CSS, and `page_builder_json`.
5. The template can be edited visually using the same builder controls used by Pages.

## Save Flow
1. The builder serializes the current GrapesJS project.
2. Save sends AJAX to:
   `PATCH /admin/theme-builder/templates/{template}/builder-save`
3. The controller sanitizes HTML/CSS.
4. The template record is updated in:
   `platform_theme_builder_templates`
5. JSON response returns saved status without redirecting to Pages.

## Autosave Flow
1. Unsaved builder changes trigger the existing autosave behavior.
2. Autosave sends AJAX to:
   `PATCH /admin/theme-builder/templates/{template}/autosave`
3. The same dedicated Theme Builder table is updated.
4. No `platform_pages` record is created.

## Security Behavior
- Builder HTML/CSS is passed through the existing `BuilderSanitizer`.
- Non-super-admin users do not get unsafe markup override.
- Super-admin keeps the existing unsafe-markup allowance used by the core builder.

## Storage Behavior
- Template name maps to `name`.
- Template slug maps to `slug`.
- Template type maps to `template_type`.
- Template description maps to `description`.
- Builder HTML maps to `html`.
- Builder CSS maps to `css`.
- Builder project JSON maps to `page_builder_json`.
- Metadata tracks the latest builder save reason.

## Verification Performed
- PHP syntax check passed for:
  - `app/Http/Controllers/Admin/ThemeBuilderController.php`
  - `routes/web.php`
- Laravel cache cleared.
- Route list confirmed the new Theme Builder builder routes.
- Config cache rebuilt.
- Route cache rebuilt.
- View cache rebuilt.

## Known Limitations
- Theme Builder template revisions are not implemented in this step.
- The legacy raw edit route still exists as a fallback, but the visible `Edit` button now opens the Page Builder.
- Full browser interaction was not automated in this report.

## Rollback Notes
- Revert the added Theme Builder builder routes.
- Revert the `Edit` button back to the raw edit route.
- Remove `resources/views/admin/theme-builder/builder.blade.php`.
- Restore the previous `ThemeBuilderController.php`.
