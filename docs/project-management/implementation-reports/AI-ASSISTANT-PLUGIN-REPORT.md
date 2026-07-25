# AI Assistant Plugin Report

Date: 2026-06-28

## Task

Create and install a plugin that adds an AI support chat assistant to both the frontend and admin dashboard. The UI follows a floating contact/support style with a popup chat panel and a full chat page link.

## Plugin

```text
ai-assistant
```

Local package:

```text
D:\codex_progects\inpa-server-proxmox\ai-assistant.zip
```

Installed path:

```text
/var/www/store.z4rank.com/laravel/modules/ai-assistant
```

## Backup

```text
/root/codex-backups/ai-assistant-plugin-install-20260628-010957
```

## AI Backend

The plugin uses the internal Art INPA AI Gateway:

```text
http://10.10.0.40:8080
```

Chat endpoint:

```text
/v1/general/chat
```

Authentication uses the `X-AI-API-KEY` header. The API key is stored only in `platform_settings` as a sensitive setting and was not written into plugin files or reports.

## Main Features

- Floating chat button on frontend pages.
- Floating chat button on admin dashboard pages.
- Popup chat panel above the button.
- Full chat page at `/ai-assistant/chat`.
- Admin settings page at `/admin/plugins/ai-assistant/settings`.
- Laravel backend proxy route at `POST /ai-assistant/message`.
- Conversation and message storage in database tables.
- Database-backed settings under module `ai-assistant`.
- CSS/JS assets published through the platform plugin asset path.

## Database

Created:

- `ai_assistant_conversations`
- `ai_assistant_messages`

Registered 16 settings rows in `platform_settings` with module `ai-assistant`.

## Important Fix During Verification

The AI API key value had a hidden BOM marker after being piped through PowerShell/SSH. The plugin settings reader now strips BOM markers and decodes JSON scalar values before using settings. This prevents invalid API headers from being sent to the AI Gateway.

## Verification

```text
php -l plugin PHP files
php artisan optimize:clear --no-ansi
php artisan migrate:status --no-ansi
php artisan route:list --no-ansi
php artisan test --no-ansi
php artisan view:cache --no-ansi
php artisan route:cache --no-ansi
php artisan config:cache --no-ansi
```

Results:

- Plugin status: active.
- Migration status: ran.
- Routes registered:
  - `GET /ai-assistant/chat`
  - `POST /ai-assistant/message`
  - `GET /admin/plugins/ai-assistant/settings`
  - `PATCH /admin/plugins/ai-assistant/settings`
- `/ai-assistant/chat`: HTTP 200.
- `/admin/plugins/ai-assistant/settings`: HTTP 302 to login when unauthenticated.
- Home page contains `data-ai-assistant-widget`.
- Full chat page contains `data-ai-assistant-full`.
- Published assets:
  - `public_html/platform/plugins/ai-assistant/css/ai-assistant.css`
  - `public_html/platform/plugins/ai-assistant/js/ai-assistant.js`
- AI Gateway client test returned `OK`.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.

## Security and Source-of-Truth Notes

- No AI API key was committed into plugin files.
- No secret value was printed into reports.
- Editable values are stored in `platform_settings`.
- Chat execution code remains in the plugin codebase.
- Conversation data is stored in the database.
