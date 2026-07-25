# AI Assistant Conversation Fix Report

Date: 2026-06-28

## Task

Fix the AI Assistant plugin popup and conversation behavior:

- Frontend chat was returning `CSRF token mismatch`.
- The popup panel did not minimize or close correctly.
- Conversation history disappeared after page refresh.
- Close and minimize needed different behavior.
- Logged-in users and guests needed separated conversation ownership.

## Backup

Server backup created by the plugin install flow:

```text
/root/codex-backups/ai-assistant-plugin-install-20260628-013824
```

The backup includes the previous installed `modules/ai-assistant` directory and available Laravel project backup artifacts. Secret values were not copied into this report.

## Changes

Updated plugin:

```text
ai-assistant
```

Changed files:

```text
modules/ai-assistant/src/AiAssistantController.php
modules/ai-assistant/src/AiGatewayClient.php
modules/ai-assistant/routes/web.php
modules/ai-assistant/resources/views/widget.blade.php
modules/ai-assistant/resources/views/chat-page.blade.php
modules/ai-assistant/resources/assets/js/ai-assistant.js
modules/ai-assistant/resources/assets/css/ai-assistant.css
modules/ai-assistant/module.json
public_html/platform/plugins/ai-assistant/js/ai-assistant.js
public_html/platform/plugins/ai-assistant/css/ai-assistant.css
```

Implementation details:

- Added `GET /ai-assistant/messages` to load the current conversation.
- Added `DELETE /ai-assistant/conversation` to delete the active conversation only when the user clicks close.
- Kept `POST /ai-assistant/message` as the send endpoint.
- Added CSRF token, history URL, and close URL to the widget and full chat page HTML.
- Updated JavaScript to send `credentials: same-origin` and `X-CSRF-TOKEN`.
- Updated JavaScript to load history on boot.
- Updated close button to delete conversation and hide the panel.
- Updated minimize button to hide the panel without deleting the conversation.
- Added a CSS rule so `[hidden]` overrides the panel `display:flex`.
- Bound logged-in users to their latest active conversation by `user_id`.
- Bound guests to their Laravel `session_id`.
- Added user context to the AI Gateway request so the model can distinguish registered users from guests.
- Added new plugin function entries in `module.json` for history and close behavior.

## Verification

Commands and checks run on the server:

```text
php -l modules/ai-assistant/src/AiAssistantController.php
php -l modules/ai-assistant/src/AiGatewayClient.php
php artisan optimize:clear --no-ansi
php artisan route:list --no-ansi
curl frontend CSRF/history/send/delete flow
php artisan test --no-ansi
php artisan view:cache --no-ansi
php artisan route:cache --no-ansi
php artisan config:cache --no-ansi
```

Results:

- Routes registered:
  - `GET /ai-assistant/chat`
  - `GET /ai-assistant/messages`
  - `POST /ai-assistant/message`
  - `DELETE /ai-assistant/conversation`
- Frontend page includes widget, CSRF token, history URL, and close URL.
- Frontend POST to `/ai-assistant/message` succeeds with JSON `ok: true`.
- History endpoint returns the same message after refresh/session reuse.
- Close endpoint returns JSON `ok: true`.
- History is empty after explicit close.
- Published public JS/CSS include the updated close/history logic.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- Laravel view, route, and config caches rebuilt successfully.

## Rollback

Restore the plugin module and assets from:

```text
/root/codex-backups/ai-assistant-plugin-install-20260628-013824
```

Then run:

```text
cd /var/www/store.z4rank.com/laravel
php artisan optimize:clear --no-ansi
php artisan view:cache --no-ansi
php artisan route:cache --no-ansi
php artisan config:cache --no-ansi
```
