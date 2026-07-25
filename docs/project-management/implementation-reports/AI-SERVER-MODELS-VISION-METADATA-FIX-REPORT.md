# AI Server Models Vision Metadata Fix Report

## Objective

Fix only the AI Gateway `/models` response so the Vision section reports the actual runtime model used by `/v1/vision/analyze`.

No Vision runtime, Ollama model, Vision Worker code, or model settings were changed.

## Root Cause

The `/models` endpoint returned static interface metadata:

```text
Qwen/Qwen2.5-VL-7B-Instruct
mode: lazy/interface
```

The actual Vision analysis runtime was already using:

```text
llava:7b
backend: vision-worker + ollama
semantic: true
```

This created a reporting conflict: `/models` described a planned/configured interface model, while `/v1/vision/analyze` returned the real runtime model.

## Change Made

Updated:

```text
/srv/ai-server/apps/ai-gateway/app/main.py
```

The `/models` endpoint now asks the Vision Worker health endpoint and reports:

```json
{
  "endpoint": "vision",
  "model": "llava:7b",
  "semantic_enabled": true,
  "runtime_model": "llava:7b",
  "runtime_backend": "ollama",
  "worker": "vision-worker",
  "backend": "vision-worker + ollama",
  "configured_interface_model": "Qwen/Qwen2.5-VL-7B-Instruct",
  "configured_interface_status": "planned_or_disabled"
}
```

## Backup

Backup path on AI Server:

```text
/root/codex-backups/ai-models-vision-metadata-fix-20260629-010517
```

## Deployment

Rebuilt and restarted only the AI Gateway service:

```text
cd /srv/ai-server
docker compose build ai-gateway
docker compose up -d ai-gateway
```

Operational note: Docker Compose also recreated the `vision-worker` dependency container with the same image/config while starting the target service. No Vision Worker code, environment, model, or runtime behavior was modified.

## Verification

`GET /models` now returns:

```json
{
  "backend": "vision-worker + ollama",
  "configured_interface_model": "Qwen/Qwen2.5-VL-7B-Instruct",
  "configured_interface_status": "planned_or_disabled",
  "endpoint": "vision",
  "model": "llava:7b",
  "runtime_backend": "ollama",
  "runtime_model": "llava:7b",
  "semantic_enabled": true,
  "worker": "vision-worker"
}
```

`POST /v1/vision/analyze` returned:

```json
{
  "mode": "description",
  "model": "llava:7b",
  "semantic": true
}
```

Runtime match check:

```text
RUNTIME_MATCH=True
```

## Result

The reporting conflict is fixed. Both `/models` and `/v1/vision/analyze` now agree that the active runtime model is:

```text
llava:7b
```

The Qwen value is now explicitly labeled as a configured interface model with status `planned_or_disabled`.

