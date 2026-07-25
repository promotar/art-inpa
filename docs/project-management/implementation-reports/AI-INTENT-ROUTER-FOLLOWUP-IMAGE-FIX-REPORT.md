# AI Intent Router Follow-up Image Fix Report

Date: 2026-06-28

## Problem

Image generation requests were being handled like general chat in some flows, causing the model to describe the image instead of routing to the image generation endpoint. Follow-up commands such as `صمم الصورة`, `صممها`, `اعملها`, `نفذ`, `ابدأ`, and `نعم` were not connected to the previous visual request.

## Fix

Updated the existing Laravel AI Intent Router, without rebuilding the system:

- Added hard-match image generation detection before general chat, RAG, and fallback classifier.
- Added more Arabic and English image generation phrases:
  - `اعمل صورة`
  - `اعمللي صورة`
  - `صمم صورة`
  - `صمملي صورة`
  - `ولد صورة`
  - `ولّد صورة`
  - `انشئ صورة`
  - `أنشئ صورة`
  - `بوستر`
  - `اعلان`
  - `إعلان`
  - `poster`
  - `generate image`
  - `create image`
  - `design image`
- Added `AiIntentRouter::resolveFollowUpIntent()` for visual follow-up commands.
- Added conversation state loading from `ai_conversations.metadata`.
- Added conversation state persistence for:
  - `last_router_intent`
  - `pending_intent`
  - `pending_visual_prompt`
  - `awaiting_visual_execution`
- Updated `AiChatController` so image generation uses the effective visual prompt from conversation state instead of short follow-up text.
- Ensured `generate_image` always routes to `AiGatewayClient->generateImage()` and endpoint `/v1/images/generate`.

## Files Modified

```text
app/Services/Ai/AiIntentRouter.php
app/Services/Ai/AiConversationService.php
app/Http/Controllers/Ai/AiChatController.php
tests/Feature/AiIntentRouterTest.php
project_documentation.md
```

## Conversation State Example

```json
{
  "last_router_intent": "generate_image",
  "pending_intent": "generate_image",
  "pending_visual_prompt": "اعمل صورة لطفل في الغابة",
  "awaiting_visual_execution": true
}
```

## Backup

```text
/root/codex-backups/ai-intent-router-followup-image-20260628-025141
```

## Tests

Added coverage for:

- `اعمل صورة لطفل في الغابة` => `generate_image`
- `اعمل صورة بوستر عن معرض فني` => `generate_image`
- previous visual request + `صمم الصورة` => `generate_image`
- previous visual request + `نعم` => `generate_image`
- `شو معنى هذه الكلمة` => `general_chat`
- `شو في الصورة؟` with image attachment => `vision_analyze`

Results:

```text
php artisan test --filter=AiIntentRouterTest --no-ansi
16 passed, 40 assertions

php artisan test --no-ansi
41 passed, 101 assertions
```

## Before / After

Before:

```json
{
  "message": "اعمل صورة لطفل في الغابة",
  "result": "general_chat",
  "endpoint_used": "/v1/general/chat"
}
```

After:

```json
{
  "message": "اعمل صورة لطفل في الغابة",
  "result": "generate_image",
  "endpoint_used": "/v1/images/generate"
}
```

Follow-up after previous visual prompt:

```json
{
  "message": "صمم الصورة",
  "conversation_state": {
    "pending_visual_prompt": "اعمل صورة لطفل في الغابة"
  },
  "result": "generate_image",
  "endpoint_used": "/v1/images/generate",
  "effective_prompt": "اعمل صورة لطفل في الغابة"
}
```
