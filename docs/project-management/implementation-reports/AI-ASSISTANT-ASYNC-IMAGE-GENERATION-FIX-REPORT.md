# AI Assistant Async Image Generation Fix

Date: 2026-06-28

## Objective

Fix the AI Assistant image-generation flow after the user reported that design/image requests waited for about 15 minutes and never returned an image.

## Root Cause

Two root causes were found:

1. Laravel was calling image generation synchronously and waiting for the AI Gateway/ComfyUI response. PHP production execution stopped the request after 30 seconds, while image generation could take longer.
2. The intent router used exact Arabic phrases only. Some natural Arabic requests such as `اعملي صورة...` or combined phrases like `اعمل صورة سريعة...` could fall through to general chat depending on input encoding/wording.

## Changes

### AI Gateway

- Added in-memory asynchronous image job tracking.
- `/v1/images/generate` and `/v1/images/fast-generate` now return a job immediately by default.
- Added `/v1/images/jobs/{job_id}` for job status polling.
- Kept optional `wait=true` behavior available for direct synchronous diagnostics.
- Background image jobs use the existing ComfyUI workflow and GPU lock.
- Job statuses now include `queued`, `processing`, `completed`, `failed`, and `timeout`.

### Laravel Core AI Client

- Image generation calls now set `wait=false`.
- Image generation timeout for the initial request is reduced to avoid PHP request hangs.
- Added `imageJobStatus()` to poll the AI Gateway safely from Laravel.

### AI Assistant Plugin

- Added `GET /ai-assistant/image-job/{jobId}` as a Laravel-side safe proxy for job polling.
- The browser never receives the AI Gateway API key.
- The first chat response now returns immediately with a Laravel `poll_url`.
- The frontend polls the job and updates the same assistant message with the generated image when completed.
- Completed image jobs are saved into the AI Assistant conversation and remembered as visual tool results.
- Added cache busting for `ai-assistant.js` so browsers load the new polling code.
- Registered `ai-assistant.image-job.status` in the plugin manifest.

### Intent Router

- Added Arabic/English text normalization.
- Added direct visual action detection using action words plus visual target words.
- Added stronger routing for natural Arabic image requests like:
  - `اعمل صورة سريعة ...`
  - `اعملي صورة ...`
  - `صمملي بوستر ...`
  - `ارسم ...`
- Added regression tests for these cases.

## Files Changed

- `remote-edit/ai-server-gateway/app/services/image_jobs.py`
- `remote-edit/ai-server-gateway/app/router_images.py`
- `remote-edit/ai-server-gateway/app/schemas.py`
- `remote-edit/ai-intent-router/files/app/Services/Ai/AiGatewayClient.php`
- `remote-edit/ai-intent-router/files/app/Services/Ai/AiIntentRouter.php`
- `remote-edit/ai-intent-router/files/tests/Feature/AiIntentRouterTest.php`
- `ai-assistant-plugin/ai-assistant/src/AiAssistantController.php`
- `ai-assistant-plugin/ai-assistant/routes/web.php`
- `ai-assistant-plugin/ai-assistant/resources/assets/js/ai-assistant.js`
- `ai-assistant-plugin/ai-assistant/resources/views/widget.blade.php`
- `ai-assistant-plugin/ai-assistant/resources/views/chat-page.blade.php`
- `ai-assistant-plugin/ai-assistant/module.json`
- `ai-assistant.zip`

## Production Backups

- AI Gateway:
  `/root/codex-backups/ai-image-async-fix-20260628-211947`
- Laravel AI Assistant:
  `/root/codex-backups/ai-assistant-image-async-fix-20260628-211947`
- Laravel AI Router:
  `/root/codex-backups/ai-router-visual-intent-fix-20260628-211947`
- Local plugin ZIP:
  `backups/ai-assistant-before-image-async-20260628-211947.zip`

## Verification

- PHP syntax passed for the AI Assistant controller and Laravel AI Gateway client.
- JavaScript syntax passed for `ai-assistant.js`.
- Python syntax passed for AI Gateway image files.
- AI Gateway container rebuilt and restarted.
- Laravel route/view/config caches rebuilt.
- `php artisan test --filter=AiIntentRouterTest --no-ansi`: 23 passed, 53 assertions.
- Direct AI Gateway image request returned a job immediately.
- Direct AI Gateway job polling completed and returned:
  `http://10.10.0.40:8080/generated/art_inpa_sdxl_lightning_00001_.png`
- Laravel Core client started an image job in about `0.04` seconds.
- Laravel AI Assistant proxy returned completed image:
  `http://10.10.0.40:8080/generated/art_inpa_sdxl_lightning_00002_.png`
- Full chat HTTP flow returned in about `0.31` seconds with:
  - intent: `fast_generate_image`
  - message: `بدأت إنشاء الصورة. سأعرضها هنا تلقائيًا عند اكتمالها.`
  - Laravel poll URL
- Chat job polling completed and returned:
  `http://10.10.0.40:8080/generated/art_inpa_sdxl_lightning_00003_.png`
- The generated image URL returned HTTP 200.
- Laravel `operation_logs` entry recorded with ID `229`.

## Operational Notes

- Image job tracking is currently in AI Gateway memory. If the gateway container restarts while a job is running, the job status is lost even if ComfyUI may still finish the image. A future hardening step should persist image jobs in Redis or a database.
- The current approach is production-safer than synchronous image generation because Laravel no longer waits for long GPU work inside the web request.

## Rollback

Laravel:

```text
cd /var/www/store.z4rank.com/laravel
cp /root/codex-backups/ai-assistant-image-async-fix-20260628-211947/AiAssistantController.php modules/ai-assistant/src/AiAssistantController.php
cp /root/codex-backups/ai-assistant-image-async-fix-20260628-211947/web.php modules/ai-assistant/routes/web.php
cp /root/codex-backups/ai-assistant-image-async-fix-20260628-211947/module.json modules/ai-assistant/module.json
cp /root/codex-backups/ai-assistant-image-async-fix-20260628-211947/AiGatewayClient.php app/Services/Ai/AiGatewayClient.php
cp /root/codex-backups/ai-assistant-image-async-fix-20260628-211947/ai-assistant.js modules/ai-assistant/resources/assets/js/ai-assistant.js
php artisan optimize:clear --no-ansi
php artisan route:cache --no-ansi
php artisan view:cache --no-ansi
php artisan config:cache --no-ansi
```

AI Gateway:

```text
cd /srv/ai-server
cp /root/codex-backups/ai-image-async-fix-20260628-211947/router_images.py apps/ai-gateway/app/router_images.py
cp /root/codex-backups/ai-image-async-fix-20260628-211947/schemas.py apps/ai-gateway/app/schemas.py
rm -f apps/ai-gateway/app/services/image_jobs.py
docker compose build ai-gateway
docker compose up -d ai-gateway
```
