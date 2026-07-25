# AI Assistant Governance Memory Report

Date: 2026-06-28

## Objective

Stabilize Art INPA AI Assistant as a governed Laravel assistant instead of a generic text chat. The assistant must keep a fixed platform identity, route intent before talking, remember tool results, reuse visual context, and avoid claiming capabilities that are not actually enabled.

## Changes

- Added tool-result memory support to `AiConversationService`.
- Stored generated and uploaded images in `ai_conversations.metadata.tool_results`.
- Added `last_tool_result`, `last_visual_result`, and `last_visual_prompt` metadata.
- Extended router state to include recent message metadata and attachments.
- Added visual-context routing:
  - `حللها`, `وين الفنان`, `شو فيها` use the last saved image and route to `vision_analyze`.
  - `اعملها مرة ثانية`, `الصورة مش صحيحة`, `regenerate it` reuse the last visual prompt and route to image generation.
- Added deterministic Laravel identity response for Art INPA AI Assistant.
- Added deterministic unavailable-tool responses for NFT workflow, image upscaling, and image editing when those tools are not enabled.
- Strengthened the gateway system instruction so the model does not invent provider identity, data access, or unavailable tools.

## Files Changed

- `app/Services/Ai/AiConversationService.php`
- `app/Services/Ai/AiIntentRouter.php`
- `modules/ai-assistant/src/AiAssistantController.php`
- `tests/Feature/AiIntentRouterTest.php`
- `project.txt`
- `project_documentation.md`
- `changes-log.txt`
- `backups-log.txt`

## Tool Result Memory

Every saved visual tool result is stored as metadata:

```json
{
  "tool_results": [
    {
      "id": "uuid",
      "type": "image",
      "source": "generated",
      "url": "http://...",
      "prompt": "اعمل صورة لطفل في الغابة",
      "tool": "/v1/images/generate",
      "intent": "generate_image",
      "created_at": "..."
    }
  ],
  "last_visual_result": {},
  "last_visual_prompt": "..."
}
```

Uploaded images are stored by private Laravel storage path and can be reused by later visual analysis requests.

## Verification

Server checks passed:

```text
php -l app/Services/Ai/AiConversationService.php
php -l app/Services/Ai/AiIntentRouter.php
php -l modules/ai-assistant/src/AiAssistantController.php
php artisan test --filter=AiIntentRouterTest --no-ansi
```

Result:

```text
21 passed, 51 assertions
```

Visual-memory smoke check passed:

```json
{"intent":"vision_analyze","use_last_visual_result":true}
```

Cache rebuild passed:

```text
php artisan optimize:clear --no-ansi
php artisan view:cache --no-ansi
php artisan route:cache --no-ansi
php artisan config:cache --no-ansi
```

## Backup

```text
/root/codex-backups/ai-assistant-governance-20260628-201005
```

## Local Plugin Package

The local plugin ZIP was rebuilt:

```text
ai-assistant.zip
```

## Rollback

Restore the files from the backup directory to their original paths:

```text
cd /var/www/store.z4rank.com/laravel
cp /root/codex-backups/ai-assistant-governance-20260628-201005/AiConversationService.php app/Services/Ai/AiConversationService.php
cp /root/codex-backups/ai-assistant-governance-20260628-201005/AiIntentRouter.php app/Services/Ai/AiIntentRouter.php
cp /root/codex-backups/ai-assistant-governance-20260628-201005/AiAssistantController.php modules/ai-assistant/src/AiAssistantController.php
cp /root/codex-backups/ai-assistant-governance-20260628-201005/AiIntentRouterTest.php tests/Feature/AiIntentRouterTest.php
php artisan optimize:clear --no-ansi
```

## Notes

- This change does not install models in Laravel.
- Laravel still only routes, checks permissions, stores memory, and calls the external AI Gateway.
- Image editing, upscaling, and NFT workflows are intentionally reported as unavailable until real tools are connected.
- Full semantic image understanding still depends on the active AI Server vision capability.
