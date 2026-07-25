# AI Assistant Plugin

## Purpose

Adds a floating AI support chat widget to both the frontend and admin dashboard. The widget opens above the launcher button and includes a link to a full chat page.

## AI Backend

The plugin calls the internal Art INPA AI Gateway through Laravel, not directly from the browser.

Default gateway:

```text
http://10.10.0.40:8080
```

Default chat endpoint:

```text
/v1/general/chat
```

Authentication header:

```text
X-AI-API-KEY
```

The real API key must be stored in `platform_settings` under `ai_assistant.api_key`. It must not be stored in plugin files.

## Routes

Frontend:

- `GET /ai-assistant/chat`
- `POST /ai-assistant/message`

Admin:

- `GET /admin/plugins/ai-assistant/settings`
- `PATCH /admin/plugins/ai-assistant/settings`

## Database

The plugin creates:

- `ai_assistant_conversations`
- `ai_assistant_messages`

Editable settings are registered in `platform_settings` with module `ai-assistant`.

## Widget Injection

The plugin registers a web middleware that appends the widget to successful HTML responses when enabled. It supports:

- Frontend pages
- Admin dashboard pages

The middleware does not modify JSON responses and does not inject into the full AI Assistant chat page.

## Security

- The browser never receives the AI API key.
- Laravel proxies chat messages to the internal gateway.
- Public message route uses Laravel CSRF protection and throttling.
- Sensitive settings are marked with `sensitive_flag`.

## Operations

After installing and activating the plugin:

1. Open `Plugins`.
2. Click `AI Assistant Settings`.
3. Configure the API key and visibility.
4. Test `/ai-assistant/chat`.
5. Verify the floating widget on admin and frontend pages.
