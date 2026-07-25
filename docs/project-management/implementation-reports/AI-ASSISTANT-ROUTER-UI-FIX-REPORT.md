# AI Assistant Router and UI Fix Report

Date: 2026-06-28

## Task

Fix AI Assistant plugin issues:

- Add a visible save button at the top of plugin settings.
- Stop remembering only the last line by preserving routed conversation state.
- Route image design requests through the Laravel AI Intent Router.
- Display generated image results in the chat UI.
- Keep the widget working on frontend and admin pages.
- Improve mobile layout.

## Backup

Server backup:

```text
/root/codex-backups/ai-assistant-router-ui-20260628-032854
```

The backup includes the installed plugin module, available public assets, and a database dump. No secrets were copied into this report.

## Files Changed

```text
modules/ai-assistant/src/AiAssistantController.php
modules/ai-assistant/resources/assets/js/ai-assistant.js
modules/ai-assistant/resources/assets/css/ai-assistant.css
modules/ai-assistant/resources/views/settings.blade.php
modules/ai-assistant/resources/views/widget.blade.php
modules/ai-assistant/resources/views/chat-page.blade.php
public/platform/plugins/ai-assistant/js/ai-assistant.js
public/platform/plugins/ai-assistant/css/ai-assistant.css
```

## Implementation

- Replaced the plugin's old direct general-chat flow with the official Laravel AI services:
  - `AiIntentRouter`
  - `AiConversationService`
  - `AiGatewayClient`
  - `AiPermissionChecker`
  - `AiUsageLimiter`
- The plugin now passes conversation state to the router so follow-up visual commands such as "صممها" and "نعم" can use the previous visual prompt.
- `generate_image` and `fast_generate_image` intents are sent only to image endpoints, not general chat.
- Assistant responses store intent, endpoint, core conversation id, and extracted image URLs in message metadata.
- Chat history now returns image metadata so generated images remain visible after refresh.
- The close button deletes the plugin conversation and linked core AI conversation. Minimize only hides the panel.
- The frontend JavaScript now renders image results returned by the gateway.
- Public plugin assets are now published under:

```text
public/platform/plugins/ai-assistant
```

- The settings page has a top `Save Settings` button linked to the same database-backed settings form.
- Mobile CSS now uses a fixed panel with viewport-safe sizing and better message/image layout.

## Verification

Commands run on the server:

```text
php -l modules/ai-assistant/src/AiAssistantController.php
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
php artisan route:list --path=ai
php artisan test --filter=AiIntentRouterTest
```

Results:

- `AiAssistantController.php`: no syntax errors.
- Routes include:
  - `POST /ai-assistant/message`
  - `GET /ai-assistant/messages`
  - `DELETE /ai-assistant/conversation`
  - `POST /ai/message`
- Public CSS/JS files exist under `public/platform/plugins/ai-assistant`.
- `AiIntentRouterTest`: 16 passed, 40 assertions.
- `operation_logs` entry inserted for this fix.

## Follow-up Fix: Gateway URL and RTL Response Rendering

After the first deployment, the chat still returned the generic unavailable message. The stored assistant metadata showed that Laravel was trying to call:

```text
http://10.10.20.10:8080/v1/general/chat
```

That IP was wrong for this project. The documented AI Gateway is:

```text
http://10.10.0.40:8080
```

Follow-up changes:

- Updated the AI Assistant controller so plugin requests apply the database-backed AI Assistant gateway settings before using the core `AiGatewayClient`.
- Added `AI_GATEWAY_BASE_URL=http://10.10.0.40:8080` to the Laravel `.env` so direct `/ai/message` calls also use the correct gateway.
- Ensured the AI API key is available to the Laravel core gateway client from the deployment environment without printing it.
- Updated the `ai_assistant.gateway_url` setting in `platform_settings` using JSON-safe storage.
- Changed the chat fallback text to Arabic for frontend users.
- Added `unicode-bidi: plaintext` to chat intro, messages, and input to prevent punctuation from flipping in mixed Arabic/English content.
- Updated default chat copy in database from English to Arabic when it was still using the original defaults.

Follow-up verification:

```text
php -l modules/ai-assistant/src/AiAssistantController.php
php artisan optimize:clear
php artisan config:clear
php artisan view:clear
php artisan test --filter=AiIntentRouterTest --no-ansi
```

Additional gateway verification:

```text
app(App\Services\Ai\AiGatewayClient::class)->chatGeneral(...)
```

Result: the Laravel gateway client returned `ok: true` from the AI server.

## Follow-up Fix: Settings Save Button and Visible API Key

User testing showed the save button was not visible in the rendered settings page because the header action did not appear in the current admin layout. The AI API key was also hidden behind a blank password field.

Changes:

- Added a clear `Save Settings` button inside the settings card header.
- Kept a sticky bottom `Save Settings` button so the form can be saved after scrolling.
- Changed the AI API key field from `password` with blank value to a visible `text` input populated from the database setting.
- Rebuilt the local `ai-assistant.zip` package.

Backup:

```text
/root/codex-backups/ai-assistant-settings-save-key-visible-20260628-131142
```

Verification:

```text
php artisan view:clear
php artisan optimize:clear
php -l modules/ai-assistant/src/AiAssistantAdminController.php
```

## Follow-up Fix: Inline Visible Save Buttons

User testing showed the save button still looked missing because the admin layout did not apply the Tailwind utility classes used by the button. The button existed in Blade, but it rendered as an almost invisible control.

Changes:

- Replaced Tailwind-dependent save button styling with explicit inline styles.
- Added visible black `Save Settings` buttons in three places:
  - page header action
  - settings card top action
  - sticky bottom action
- Rebuilt the local `ai-assistant.zip` package.

Backup:

```text
/root/codex-backups/ai-assistant-visible-save-button-inline-20260628-145435
```

Verification:

```text
php artisan view:clear
php artisan optimize:clear
grep -n 'background:#111827' modules/ai-assistant/resources/views/settings.blade.php
```

## Rollback

Restore from:

```text
/root/codex-backups/ai-assistant-router-ui-20260628-032854
```

Then run:

```text
cd /var/www/store.z4rank.com/laravel
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```
