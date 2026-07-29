# Page Builder Reference UI Redesign

Date: 2026-07-29

## Scope

All project changes are contained inside `modules/page-builder`.

## Recovery Point

Backup:

`modules/page-builder/.backups/20260729-page-builder-reference-redesign`

The backup contains the pre-change `edit.blade.php` and `editor-ui.css`.

## Implementation

- Replaced the unrelated frontend navigation above the editor with a compact Page Builder header.
- Added a reference-aligned three-panel workspace:
  - searchable element and layer browser on the left;
  - responsive GrapesJS canvas in the center;
  - persistent settings and style inspector on the right.
- Added working Desktop, Tablet, and Mobile device controls.
- Added Undo and Redo controls backed by the GrapesJS undo manager.
- Kept Save, Preview, Template Settings, import/export, revisions, autosave, and dynamic components on their existing backend workflows.
- Added module-scoped design tokens for colors, spacing, radii, typography, panels, and responsive behavior.
- Added an empty selection state and element-search filtering.

## Verification

- PHP syntax check: passed.
- Blade view cache compilation: passed.
- Inline integration JavaScript syntax check: passed.
- Page Builder route inventory: 17 routes present.
- Docker application health: passed on the local container.
- `PageBuilderSanitizerTest`: passed.
- `TemplateEditableRendererTest`: passed.
- Broader Page Builder tests were attempted but the test bootstrap rejected the environment because it did not resolve to isolated SQLite memory storage.
- Browser visual verification was blocked because the connected browser could not navigate to the local `127.0.0.1` service, while direct HTTP health verification returned 200.

## Theme Builder Unification and Creation Repair

### Root causes

- Page, Header, Footer, and Block creation returned HTTP 500 because the Page Builder migrations were pending and `platform_pages.parent_id` did not exist.
- The old Theme Builder stored separate template records and opened a different builder workflow.
- The module editor stylesheet route resolved `resources/css` from the wrong parent directory and returned 404, causing the properties panel to fall below the element panel.

### Database protection

- Full pre-migration MySQL backup:
  `modules/page-builder/.backups/20260729-theme-builder-logic/art_inpa_test-before-theme-builder.sql`
- Applied Page Builder migrations for hierarchy columns and the new theme selection table.

### New source-of-truth model

- Page Builder is the only visual editor.
- `platform_pages.content_type` owns the design type:
  - `page` = Body / Page
  - `header` = Header
  - `footer` = Footer
  - `block` = reusable Block
- Theme Builder stores only the selected Page Builder record IDs for Header, Body, and Footer.
- Frontend composition resolves the selected records directly; no design copy is created during normal Theme Builder use.
- Body designs can be complete layouts or inject page content through `{{ page_content }}` or `data-dynamic-field="content"`.
- The legacy published `MAin Hedar` template was imported once into Page Builder and selected as the active Header.

### Runtime verification

- Transactional creation test passed for Page, Header, Footer, and Block; all test records were rolled back.
- Transactional composition test passed for selected Header, Body content injection, Footer, and selected CSS; all test records were rolled back.
- `/admin/theme-builder` now resolves to the Page Builder module selection controller.
- Browser verification confirmed the Theme Builder selection UI and the migrated active Header.
- Browser verification confirmed the migrated Header opens in the same Page Builder editor.
- Editor CSS returns valid rules and the workspace measures as three columns: 248px / flexible canvas / 310px.
