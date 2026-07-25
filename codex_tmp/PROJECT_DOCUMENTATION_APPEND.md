# Laravel AI Intent Router

Date: 2026-06-28

## Purpose

The Laravel AI Intent Router is a Laravel-only orchestration layer. It receives AI messages from plugins and decides the correct safe execution path:

- general chat
- RAG / knowledge search
- image generation
- fast image generation
- vision / image analysis
- artwork similarity search
- profile/account update request
- coding assistant for admins/developers only
- permission-aware platform data query
- admin report query
- unknown / needs clarification

Laravel does not install or run AI models. Laravel only detects intent, checks permissions and limits, executes authorized Laravel tools/actions, and calls the external AI VPS Gateway.

## Flow

```text
Laravel Plugin
-> AiIntentRouter
-> AiPermissionChecker
-> AiUsageLimiter
-> AiActionExecutor for confirmed internal Laravel actions
OR AiDataAccessService for whitelisted read-only data tools
OR AiGatewayClient for external AI VPS Gateway requests
```

## Files Created

```text
app/Enums/AiIntent.php
app/Enums/AiTaskType.php
app/Data/AiIntentResult.php
app/Services/Ai/AiIntentRouter.php
app/Services/Ai/AiGatewayClient.php
app/Services/Ai/AiPermissionChecker.php
app/Services/Ai/AiUsageLimiter.php
app/Services/Ai/AiActionExecutor.php
app/Services/Ai/AiConversationService.php
app/Services/Ai/AiPromptSanitizer.php
app/Services/Ai/AiDataAccessService.php
app/Services/Ai/AiToolRegistry.php
app/Services/Ai/AiAdminReportService.php
app/Http/Controllers/Ai/AiChatController.php
app/Http/Requests/Ai/AiMessageRequest.php
app/Policies/UserPolicy.php
config/ai.php
database/migrations/2026_06_28_060001_create_ai_conversations_table.php
database/migrations/2026_06_28_060002_create_ai_messages_table.php
database/migrations/2026_06_28_060003_create_ai_usage_logs_table.php
database/migrations/2026_06_28_060004_create_ai_tool_audit_logs_table.php
tests/Feature/AiIntentRouterTest.php
```

## Files Updated

```text
routes/web.php
config/platform_registry.php
PROJECT_DOCUMENTATION.md
project_documentation.md
```

## Config Variables

```text
AI_GATEWAY_BASE_URL
AI_GATEWAY_API_KEY
AI_DEFAULT_TIMEOUT
AI_IMAGE_TIMEOUT
AI_FALLBACK_CLASSIFIER_ENABLED
AI_INTENT_CONFIDENCE_THRESHOLD
```

Do not write real secrets into documentation. The API key must remain in secure environment/config management and must never be exposed to the frontend.

## Route

```text
POST /ai/message
```

Route name:

```text
ai.message
```

The route is registered in the platform registry.

## Example Request

```json
{
  "message": "اعمل صورة بوستر عن معرض فني",
  "plugin": "page_builder",
  "action": "generate_image",
  "conversation_id": 1,
  "context": {},
  "attachments": []
}
```

## Example Response

```json
{
  "ok": true,
  "data": {
    "intent": "generate_image",
    "requires_confirmation": false,
    "message": "...",
    "endpoint_used": "/v1/images/generate",
    "conversation_id": 1
  }
}
```

## Permission-Aware AI Data Access

The AI model and AI Gateway never access the Laravel database directly.

All platform data access happens through:

```text
AiToolRegistry
AiDataAccessService
Laravel policies / Spatie permissions
```

Whitelisted tools:

- `users_registered_last_24h`
- `user_own_profile`
- `platform_basic_stats`

Normal users can only access their own profile and future own-account scoped tools. They cannot list users, access admin stats, read financial reports, or inspect system logs.

Admins can access supported admin tools only after Laravel permission checks pass.

Every tool attempt is logged in:

```text
ai_tool_audit_logs
```

## Security Rules

- Never execute AI-generated SQL.
- Never expose arbitrary table access.
- Never send passwords, remember tokens, two-factor secrets, API tokens, private notes, or sensitive metadata to AI.
- Never allow the AI model to update database records directly.
- Sensitive actions require confirmation before Laravel executes them.
- AI Gateway responses are validated before use.
- Prompt and attachments are sanitized before being sent.
- Full base64 images are not logged.

## Sensitive Action Flow

For `update_profile` and `update_order`:

1. Router detects the sensitive intent.
2. Laravel returns `requires_confirmation=true`.
3. User confirms through the UI.
4. Laravel executes the action through `AiActionExecutor`.
5. The action is logged.

## Adding A New Plugin Intent

1. Add the intent to `AiIntent` if it is a new platform-level category.
2. Add keyword/plugin/action mapping to `AiIntentRouter`.
3. Add permission checks to `AiPermissionChecker`.
4. Add usage limit to `config/ai.php`.
5. If it needs data, register a whitelisted tool in `AiToolRegistry`.
6. Add an audit log path if it touches platform data.
7. Add tests.
8. Register new route/function metadata in the platform registry if required.

## How To Test

```bash
php artisan migrate --force
php artisan test --filter=AiIntentRouterTest
php artisan route:list --name=ai.message
php artisan optimize:clear
```

## Current Placeholder Assumptions

- Subscription feature checks are implemented as clean placeholders inside `AiPermissionChecker`.
- Plan-specific image/vision/artwork limits can be connected later to the platform subscription system.
- `update_order` is detected as sensitive but not executed until a dedicated confirmed Laravel action exists.
