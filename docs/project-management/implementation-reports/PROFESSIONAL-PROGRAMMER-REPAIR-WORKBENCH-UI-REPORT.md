# Professional Programmer Repair Workbench UI Report

Date: 2026-06-29

## Objective

Refactor the `professional-programmer` Repair Console UI/UX only. Backend logic, analyzer behavior, approval guard, routes, and AI behavior were not changed.

## Scope

Changed only the widget presentation layer:

- `professional-programmer-plugin/professional-programmer/resources/views/widget.blade.php`
- `professional-programmer-plugin/professional-programmer/resources/assets/css/professional-programmer.css`
- `professional-programmer-plugin/professional-programmer/resources/assets/js/professional-programmer.js`
- `professional-programmer-plugin/professional-programmer/module.json`

## Backups

Local source backup:

```text
backups/professional-programmer-ui-before-workbench-20260629-023630.zip
```

Production backup:

```text
/root/codex-backups/professional-programmer-workbench-ui-20260629-024152
```

## Implementation

- Replaced the small chat-style popup with a full-screen Repair Workbench.
- Default workbench size is `96vw` by `92vh`, centered with no page-level empty vertical space.
- Added sticky top header with title, status badge, fullscreen/close controls, dashboard link, and selected incident severity.
- Added a three-column layout:
  - left incident list at 28%
  - center diagnosis and conversation at 44%
  - right repair plan and approval panel at 28%
- Moved incident rendering into readable cards with severity, source path, short error text, and occurrence count.
- Added a center conversation stream where new user questions, assistant responses, and diagnosis reports are appended at the bottom.
- Added auto-scroll to the latest assistant response/report.
- Added a sticky center composer with a 90px textarea.
- Added a sticky approval footer with an inline reason when approval is disabled.
- Moved widget CSS and JS into isolated Professional Programmer asset files.
- Kept all selectors scoped under `#pp-widget` / `ppw__*` to avoid affecting other AI chat plugins.

## Verification

Local syntax and package checks:

```text
php -l professional-programmer-plugin/professional-programmer/resources/views/widget.blade.php
node --check professional-programmer-plugin/professional-programmer/resources/assets/js/professional-programmer.js
module.json valid JSON
widget Blade contains no <style> block and no style= attributes
```

Production deploy checks:

```text
php -l modules/professional-programmer/resources/views/widget.blade.php
node --check modules/professional-programmer/resources/assets/js/professional-programmer.js
php artisan optimize:clear --no-ansi
php artisan view:cache --no-ansi
php artisan route:cache --no-ansi
php artisan config:cache --no-ansi
```

Published asset hashes matched:

```text
modules/professional-programmer/resources/assets/css/professional-programmer.css
/var/www/store.z4rank.com/public_html/platform/plugins/professional-programmer/css/professional-programmer.css
public/platform/plugins/professional-programmer/css/professional-programmer.css
```

Static UI assertions passed:

```text
blade_loads_css_asset=yes
blade_loads_js_asset=yes
no_inline_style_block=yes
fullscreen_96vw=yes
fullscreen_92vh=yes
three_column_grid=yes
center_composer_90px=yes
left_scroll_list=yes
right_sticky_approval_footer=yes
latest_response_appended=yes
latest_scrolls_into_view=yes
diagnosis_appended_to_stream=yes
approval_visible_reason=yes
approval_backend_payload_unchanged=yes
routes_unchanged_in_widget=yes
status=passed
```

Controlled Playwright fixture results:

```text
incidentCount=24
rtl=rtl
panelWidthRatio=96
panelHeightRatio=92
listScrollable=true
approvalDisabled=true
warningVisible=true
noPageGiantBlank=true
message order=assistant intro > user question > assistant answer > diagnosis report
latestIsReport=true
approvalEnabled=true after safe auto-fill
repairType=migration
phpSummary=Pure PHP exception in admin view
fullscreen widthRatio=99
fullscreen heightRatio=98
```

Production route and asset checks:

```text
admin/plugins/professional-programmer routes still present: 9
asset_css=yes
asset_js=yes
```

Admin render check:

```text
admin_render_status=200
contains_repair_console=yes
contains_workbench_asset=yes
contains_workbench_js=yes
```

## Acceptance Status

- Long SQL error: passed in controlled UI fixture.
- PHP exception: passed in controlled UI fixture.
- 20+ incidents: passed with 24 controlled incidents.
- Arabic RTL text: passed.
- Fullscreen mode: passed.
- No result appears at top after sending: passed.
- No giant empty area remains: passed.
- Latest AI response/report auto-scrolls into view: passed.
- Approval disabled reason visible: passed.
- Backend routes and payloads unchanged: passed.

## Remaining Risk

This task did not change analyzer or backend approval behavior by design. Browser verification used a controlled local fixture for UI behavior and server-side Laravel render for production availability. No production repair or database mutation was executed.
