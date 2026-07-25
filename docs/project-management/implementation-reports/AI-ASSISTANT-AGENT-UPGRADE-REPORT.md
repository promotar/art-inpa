# AI Assistant Agent Upgrade Report

Date: 2026-06-28

## Objective

Upgrade the AI Assistant chat so it behaves like a professional platform agent:

- Shift+Enter inserts a new line and Enter sends.
- Images and files can be uploaded from the floating widget and full chat page.
- Uploaded text files are analyzed through authorized attachment context.
- Uploaded images route to vision analysis.
- Intent routing better distinguishes chat, image generation, vision, site search, user/role/admin data, and coding.
- Platform data access remains permission checked and whitelist only.
- The assistant receives authenticated user identity, roles, and permissions in context.

## Main Changes

- Replaced single-line chat inputs with auto-growing textareas.
- Added upload controls for images and common document/text files.
- Switched frontend sending to multipart FormData when files are attached.
- Added selected-file chips and user-message attachment chips.
- Stored uploaded files privately under Laravel storage.
- Sent base64 image data only at runtime to the AI Gateway and removed it from stored metadata.
- Added strict context that user claims are untrusted and Laravel-authorized data is the source of truth.
- Added role and permission context for authenticated users.
- Added text attachment fallback to `/v1/general/chat` using bounded excerpts when RAG is not required.
- Fixed vision payload mode values to match the AI Gateway schema.
- Extended the AI data tool registry with public site/blog search and admin-only roles/users tools.
- Extended permission checks for new tools.
- Added deterministic technical image analysis in the AI Server vision worker instead of returning 501.

## Files Changed

```text
ai-assistant-plugin/ai-assistant/src/AiAssistantController.php
ai-assistant-plugin/ai-assistant/resources/views/widget.blade.php
ai-assistant-plugin/ai-assistant/resources/views/chat-page.blade.php
ai-assistant-plugin/ai-assistant/resources/assets/js/ai-assistant.js
ai-assistant-plugin/ai-assistant/resources/assets/css/ai-assistant.css
remote-edit/ai-intent-router/files/app/Services/Ai/AiIntentRouter.php
remote-edit/ai-intent-router/files/app/Services/Ai/AiToolRegistry.php
remote-edit/ai-intent-router/files/app/Services/Ai/AiDataAccessService.php
remote-edit/ai-intent-router/files/app/Services/Ai/AiPermissionChecker.php
remote-edit/ai-intent-router/files/app/Http/Controllers/Ai/AiChatController.php
remote-edit/ai-intent-router/files/tests/Feature/AiIntentRouterTest.php
remote-edit/ai-server-gateway/apps/vision-worker/app/main.py
ops/verify_ai_assistant_agent_upgrade.sh
ops/verify_ai_assistant_vision_upload.sh
ops/read_ai_assistant_last_system_error.php
ai-assistant.zip
```

## Server Deployment

Laravel deployed paths:

```text
/var/www/store.z4rank.com/laravel/modules/ai-assistant
/var/www/store.z4rank.com/laravel/app/Services/Ai
/var/www/store.z4rank.com/laravel/app/Http/Controllers/Ai
/var/www/store.z4rank.com/laravel/tests/Feature/AiIntentRouterTest.php
/var/www/store.z4rank.com/public_html/platform/plugins/ai-assistant
```

AI Server deployed path:

```text
/srv/ai-server/apps/vision-worker/app/main.py
```

## Backups

```text
/root/codex-backups/ai-assistant-agent-upgrade-20260628-191012
/root/codex-backups/ai-vision-worker-basic-analysis-20260628-192100
```

## Verification

Passed:

```text
php -l modules/ai-assistant/src/AiAssistantController.php
php -l app/Services/Ai/AiIntentRouter.php
php -l app/Services/Ai/AiToolRegistry.php
php -l app/Services/Ai/AiDataAccessService.php
php -l app/Services/Ai/AiPermissionChecker.php
php -l app/Http/Controllers/Ai/AiChatController.php
php artisan test --filter=AiIntentRouterTest --no-ansi
/tmp/verify_ai_assistant_agent_upgrade.sh
/tmp/verify_ai_assistant_vision_upload.sh
```

Results:

- `AiIntentRouterTest`: 19 passed, 47 assertions.
- Text file upload returned `intent=rag_question` and endpoint `/v1/general/chat (attachment context)`.
- Image upload returned `intent=vision_analyze` and endpoint `/v1/vision/analyze`.
- Full chat page renders textarea and file upload input.
- Settings Blade still contains visible `Save Settings` buttons.
- Laravel caches were rebuilt.

Full suite note:

```text
php artisan test --no-ansi
```

Result: 30 passed, 14 failed. The failures are existing Auth/Profile test issues around 419 CSRF/session behavior and password reset notifications. The focused AI Assistant and AI Router tests passed.

## Remaining Notes

- The AI Server vision worker now returns real technical image analysis using Pillow. Full semantic image understanding and OCR still require enabling a real vision/OCR model on the AI Server.
- Database access remains whitelist-only. The AI Gateway still cannot query Laravel database directly.
- Normal users cannot list users or roles; denied attempts are audited.
