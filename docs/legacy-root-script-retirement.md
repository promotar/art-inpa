# Legacy Root Script Retirement

Date: 2026-07-23

## Decision

The project root is not an operations or migration workspace. Runtime PHP belongs
to the Laravel core or an enabled plugin. Editable page content belongs to
`platform_pages`; theme behavior and assets belong to the theme plugin.

Twenty-four one-off PHP scripts were archived and removed. None was referenced by
Composer, Laravel bootstrap, routes, providers, plugins, or scheduled commands.

## File Review

| Removed file | Previous behavior | Permanent replacement |
| --- | --- | --- |
| `extract-home-news-css-snippets.php` | Printed selected CSS fragments from the Home page row. | Page Builder, theme CSS, and automated tests. |
| `fix-about-committee-image-links.php` | Replaced four About image URLs directly in the database. | Versioned theme data migration and local plugin images. |
| `fix-about-committee-original-data.php` | Injected hardcoded committee people, remote images, HTML, and CSS. | Editable `platform_pages` content plus theme-owned assets. |
| `fix-about-committee-tabs-style.php` | Appended a CSS marker block to About. | Theme stylesheet and Page Builder content. |
| `fix-about-hero-both-icons-visible.php` | Rewrote hero icons and appended CSS. | Current canonical page content; no request-time patch. |
| `fix-about-hero-icons-visible.php` | Older variant of the hero icon rewrite. | Current canonical page content. |
| `fix-about-icons-in-active-storage.php` | Recursively modified multiple About storage fields. | Page Builder save pipeline and the versioned migration. |
| `fix-home-news-about-refinements.php` | Mutated three pages, exported images, and injected remote URLs. | Theme plugin CSS/assets and database-owned page content. |
| `import-masterstudy-lms.php` | Imported courses and lessons from the legacy MasterStudy API. | Completed one-time migration; native LMS administration owns all current records. |
| `inspect-about-compact.php` | Printed About markers and snippets. | Page Builder, revisions, and normal database diagnostics. |
| `inspect-about-hero-icons.php` | Printed snippets around hero labels. | Browser/tests and Page Builder. |
| `inspect-about-images.php` | Enumerated image tags in About. | Media/Page Builder inspection and standalone tests. |
| `inspect-about-page-storage.php` | Searched every About field for historical markers. | Structured page fields and migration verification. |
| `inspect-about-revisions.php` | Printed current and revision marker states. | Native `platform_page_revisions` UI/data. |
| `inspect-art-inpa-pages-media.php` | Probed page/media tables with raw schema queries. | Core media and page administration. |
| `inspect-news-pages-dynamic-state.php` | Used a hardcoded production path and wrote `/tmp` state. | Local tests and standard Laravel diagnostics. |
| `lms-import-backup.php` | Exported LMS tables as ad hoc JSON. | Standard database backup and recovery process. |
| `phase1e-image-verify.php` | Asserted a hardcoded page and staging URL. | Standalone theme and HTTP verification tests. |
| `remove-about-hero-icon-css-leftovers.php` | Removed historical icon CSS using regular expressions. | Canonical theme stylesheet. |
| `remove-about-hero-icons.php` | Removed icon HTML/CSS from several page fields. | Canonical page content. |
| `restore-about-hero-small-icons.php` | Re-added icons removed by another patch. | Canonical page content; contradictory patches retired. |
| `simple-home-news-inspect.php` | Printed page lengths and markers. | Normal page diagnostics and tests. |
| `verify-about-committee-tabs-style.php` | Checked one historical CSS marker. | Theme contract tests. |
| `verify-home-news-about-refinements.php` | Checked patch markers and image counts. | Standalone theme and HTTP verification tests. |

## Standalone Resolution

- Theme version `1.1.0` owns `home`, `news`, `about`, magazine, and policy pages
  through `frontend.owned_pages` in `module.json`.
- Twenty-one About committee portraits are stored under the plugin package and
  published by the core plugin asset registry.
- Migration `2026_07_23_104500_localize_theme_page_dependencies` replaces known
  WordPress image URLs in all structured page fields and normalizes Home links
  from `10.10.0.20` to relative paths.
- Current LMS data contains 11 courses and 936 lessons with no runtime image,
  lesson, or content references to `learn.art-inpa.com`.
- `PluginRouteLoader` validates route syntax in-process with PHP token parsing.
  It no longer depends on an external PHP CLI executable from PHP-FPM.
- Plugin asset synchronization persists the complete canonical package manifest,
  including version and owned-page metadata, to the plugin database record.
- `PluginArchitectureContractTest` rejects any future root-level PHP script.

## Recovery

Recovery point:

```text
backups/root-script-cleanup-20260723-104344
```

It contains all 24 removed scripts, the affected source files, project memory
files, SHA-256 hashes, and a transaction-safe SQL dump of page, revision, course,
and lesson data.
