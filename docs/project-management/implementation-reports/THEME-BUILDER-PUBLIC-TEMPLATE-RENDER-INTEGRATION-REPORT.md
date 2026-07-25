# Theme Builder Public Template Render Integration Report

## Task Title

Theme Builder public template render integration.

## Objective

Make published Theme Builder templates apply on the public site according to saved display conditions.

The immediate issue was that a Header template preview worked inside Theme Builder, but the public site still rendered the old header.

## Scope

This task was implemented in Core only.

No plugin files were modified.

## Root Cause

Theme Builder templates are now stored in:

`platform_theme_builder_templates`

and their display rules are stored in:

`platform_theme_builder_template_conditions`

The public page renderer was still loading only legacy header and footer records from:

`platform_pages`

Therefore the uploaded Header template could preview correctly, but it was not applied on the public site.

## Files Created

`app/Platform/Core/ThemeBuilder/ThemeBuilderTemplateResolver.php`

## Files Modified

`app/Platform/Core/PageBuilder/PageBuilderRenderService.php`

`app/Platform/Core/Rendering/PlatformContentRenderer.php`

## Plugin Files Modified

None.

## Resolver Behavior

The new resolver loads published Theme Builder templates by type.

Supported template types:

`header`

`footer`

`single_post`

`single_page`

`archive`

`search_results`

`error_404`

The resolver checks display conditions from:

`platform_theme_builder_template_conditions`

Supported condition scopes:

`entire_site`

`front_page`

`all_pages`

`specific_pages`

`all_posts`

`specific_posts`

`post_categories`

`archives`

`search_results`

`not_found`

## Public Render Flow

1. Public page opens.

2. Core page render service loads the page from `platform_pages`.

3. Header lookup checks Theme Builder templates first.

4. If a matching published Header exists, it is rendered.

5. Footer lookup checks Theme Builder templates first.

6. If no matching Theme Builder template exists, the old `platform_pages` header/footer fallback remains active.

7. CSS from matching Theme Builder templates is included in the public page.

## Page Template Flow

For page body templates such as `single_page`, the renderer checks Theme Builder for a matching template.

The template is applied only if it contains one of these content placeholders:

`{{ page_content }}`

or

`data-dynamic-field="content"`

This prevents a dynamic template from accidentally replacing the whole page content without a clear insertion point.

## Compatibility Fix

`PageBuilderRenderService` and `PlatformContentRenderer` now accept the resolver as an optional dependency.

This keeps compatibility with existing subclasses that still call the old constructor signature.

No plugin subclass was modified.

## Verification Performed

PHP syntax checks passed for:

`app/Platform/Core/ThemeBuilder/ThemeBuilderTemplateResolver.php`

`app/Platform/Core/PageBuilder/PageBuilderRenderService.php`

`app/Platform/Core/Rendering/PlatformContentRenderer.php`

Laravel cache commands passed:

`php artisan optimize:clear`

`php artisan config:cache`

`php artisan route:cache`

`php artisan view:cache`

Render check result:

`published_header_templates: 1`

`resolver_header_sections: 1`

`resolver_header_name: Header`

`page_dynamic_headers: 1`

`layout_css_length: 11836`

`header_contains_news: true`

Public homepage HTTP check:

`200`

The rendered HTML includes Header template text such as:

`Latest`

`Politics`

## Known Limitations

Header and Footer are now directly applied on public pages.

Single Page templates require an explicit content placeholder.

Archive, Search Results, Single Post, and 404 templates are resolved by the shared resolver, but their final visual application depends on the matching public render endpoint calling the resolver.

No plugin render files were changed in this task.

## Rollback Notes

To roll back this integration:

1. Remove `ThemeBuilderTemplateResolver.php`.

2. Revert the two Core renderer files.

3. Clear and rebuild Laravel caches.

No database rollback is required for this render integration.

