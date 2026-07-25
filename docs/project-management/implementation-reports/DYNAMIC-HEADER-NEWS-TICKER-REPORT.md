# Dynamic Header News Ticker Report

## Task Title
Dynamic header news ticker from site articles.

## Objective
Make the top red header ticker pull live article titles from the platform blog posts instead of relying on static template text.

## Scope
The change was limited to the core public content rendering layer and the active Theme Builder header template record.

No plugin files were modified.

## Files Modified
- `app/Platform/Core/Rendering/PlatformContentRenderer.php`

## Database Records Updated
- Table: `platform_theme_builder_templates`
- Record: `id = 1`
- Template: `Header`
- Updated fields:
  - `html`
  - `page_builder_json` set to `null`
  - `source_type` set to `page_builder`
  - `updated_at`

## Header Markup Added
The active header template now marks the ticker area with:

```html
data-platform-news-ticker="latest-posts"
data-platform-news-limit="8"
```

## Data Source
The ticker reads from:

```text
blog_posts
```

Only posts matching these rules are included:
- `status = published`
- `deleted_at IS NULL`
- `published_at` is empty or not in the future

The newest posts are selected first.

## Route Behavior
Each ticker item links to the public blog post route:

```text
blog.show
```

Fallback URL format:

```text
/blog/{slug}
```

## Rendering Flow
1. Public HTML is rendered.
2. `PlatformContentRenderer` finds elements with `data-platform-news-ticker="latest-posts"`.
3. Latest published posts are loaded from `blog_posts`.
4. Existing static ticker content is replaced at render time.
5. If no posts are found, the original static ticker content remains as fallback.

## Page Builder Compatibility
The header remains editable through the Theme Builder Page Builder.

The dynamic behavior is attached using a data attribute, so the editable header HTML stays simple while the public renderer injects live article links.

## Backup Created
Before applying the change, a backup checkpoint was created:

```text
storage/app/theme-builder-template-backups/20260703-154819-news-ticker
```

## Verification Performed
- PHP syntax check passed for:
  - `app/Platform/Core/Rendering/PlatformContentRenderer.php`
- Laravel caches rebuilt:
  - `optimize:clear`
  - `config:cache`
  - `route:cache`
  - `view:cache`
- Verified published blog post titles exist in `blog_posts`.
- Verified public home page output contains:
  - `data-platform-news-ticker="latest-posts"`
  - dynamic links generated from actual blog post titles.

## Verified Dynamic Titles
Examples from the live ticker output:
- حفل تخريج دبلوم العلاج بالفن ودبلوم الفن التشكيلي
- تعزية الفقيد السيد محمد السوداني
- تأثير التكنولوجيا على الفن التشكيلي: رحلة عبر التطور والتحديات
- لوحات تحكي أهوال الحرب في قطاع غزة في معرض في عمّان
- افتتاح المعرض الجماعي بنسخته السادسة “صحوة الأحلام”

## Known Limitations
- The ticker currently pulls latest published posts globally.
- Category-specific ticker filtering was not added in this task.
- Ticker animation/styling remains controlled by the editable header template CSS.

## Rollback Notes
Restore the backup folder listed above, or restore the previous `platform_theme_builder_templates` record for header template `id = 1`.

