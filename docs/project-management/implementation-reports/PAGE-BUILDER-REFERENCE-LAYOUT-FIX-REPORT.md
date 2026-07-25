# Page Builder Reference Layout Fix Report

Date: 2026-06-29

## Objective

Align `/admin/pages/{page}/edit` with the supplied reference layout: fixed admin sidebar, builder content starting after the sidebar, no page-level scroll, full-height canvas workspace, fixed-width inspector, settings overlay, and no floating AI widget.

## Root Cause

The active admin theme override sets:

```text
--ainpa-admin-sidebar-width: 250px
```

The Page Builder focus layout still used an internal `160px` sidebar assumption. As a result, the builder main area started at `x=160` while the real sidebar ended at `x=250`, leaving the builder under the sidebar by about `90px`.

The AI Assistant widget was also injected globally by `AiAssistantWidgetMiddleware`, so route-specific CSS alone was not enough.

## Files Changed

```text
resources/views/components/page-builder-focus-layout.blade.php
resources/views/admin/pages/edit.blade.php
public/vendor/front-builder/page-builder/page-builder.css
public/vendor/front-builder/page-builder/page-builder.js
modules/ai-assistant/src/AiAssistantWidgetMiddleware.php
/var/www/store.z4rank.com/public_html/vendor/front-builder/page-builder/page-builder.css
/var/www/store.z4rank.com/public_html/vendor/front-builder/page-builder/page-builder.js
```

## Implementation

- Added the semantic `page-builder-main` wrapper class.
- Kept `page-builder-focus-layout`, `page-builder-header`, `page-builder-statusbar`, `page-builder-toolbar`, `page-builder-workspace`, `page-builder-canvas`, `page-builder-inspector`, and `page-builder-settings-drawer`.
- Made the builder main area fixed to the viewport with:

```text
height: calc(100dvh - var(--pb-admin-bar-height))
left: var(--pb-admin-sidebar-width)
top: var(--pb-admin-bar-height)
overflow: hidden
```

- Mapped Page Builder sidebar/topbar variables to the active admin theme variables:

```text
--pb-admin-sidebar-width: var(--ainpa-admin-sidebar-width, 160px)
--pb-admin-bar-height: var(--ainpa-admin-topbar-height, 32px)
```

- Set the workspace to `canvas 1fr + inspector 340px`.
- Made the canvas scroll owner the GrapesJS canvas only.
- Made the inspector body the inspector scroll owner.
- Kept Page Settings as a fixed overlay drawer that does not affect workspace geometry.
- Disabled AI Assistant injection at the middleware level for `admin.pages.edit` and `/admin/pages/*/edit`.
- Added CSS safety hiding for AI widget inside the builder focus body.

## Backup

```text
/root/codex-backups/page-builder-layout-reference-fix-20260629-005116
```

## Verification

Server checks:

```text
php -l resources/views/admin/pages/edit.blade.php: passed
php -l resources/views/components/page-builder-focus-layout.blade.php: passed
php -l modules/ai-assistant/src/AiAssistantWidgetMiddleware.php: passed
node --check public/vendor/front-builder/page-builder/page-builder.js: passed
php artisan optimize:clear --no-ansi: passed
CSS/JS public and public_html hashes match: passed
Render/middleware check: reference-layout-ok page=1 bytes=168125 ai_widget=not_injected
```

Browser verification at `1920x1080` using a temporary static render:

```text
sidebar: x=0 width=250 right=250
main: x=250 width=1670 height=1046 position=fixed
workspace: x=250 y=164 width=1670 height=916
canvas: x=250 width=1330 height=916
inspector: x=1580 width=340 height=916
body/document scrollHeight: 1080
body overflow: hidden
GrapesJS canvas overflow: auto
inspector body overflow: auto
AI widget count: 0
Page Settings drawer opened without changing workspace y or height
```

Temporary verification files were removed from the server.

## Rollback

```text
cd /var/www/store.z4rank.com/laravel
cp /root/codex-backups/page-builder-layout-reference-fix-20260629-005116/resources/views/admin/pages/edit.blade.php resources/views/admin/pages/edit.blade.php
cp /root/codex-backups/page-builder-layout-reference-fix-20260629-005116/resources/views/components/page-builder-focus-layout.blade.php resources/views/components/page-builder-focus-layout.blade.php
cp /root/codex-backups/page-builder-layout-reference-fix-20260629-005116/public/vendor/front-builder/page-builder/page-builder.css public/vendor/front-builder/page-builder/page-builder.css
cp /root/codex-backups/page-builder-layout-reference-fix-20260629-005116/public/vendor/front-builder/page-builder/page-builder.js public/vendor/front-builder/page-builder/page-builder.js
cp /root/codex-backups/page-builder-layout-reference-fix-20260629-005116/public_html/vendor/front-builder/page-builder/page-builder.css /var/www/store.z4rank.com/public_html/vendor/front-builder/page-builder/page-builder.css
cp /root/codex-backups/page-builder-layout-reference-fix-20260629-005116/public_html/vendor/front-builder/page-builder/page-builder.js /var/www/store.z4rank.com/public_html/vendor/front-builder/page-builder/page-builder.js
cp /root/codex-backups/page-builder-layout-reference-fix-20260629-005116/modules/ai-assistant/src/AiAssistantWidgetMiddleware.php modules/ai-assistant/src/AiAssistantWidgetMiddleware.php
php artisan optimize:clear --no-ansi
```
