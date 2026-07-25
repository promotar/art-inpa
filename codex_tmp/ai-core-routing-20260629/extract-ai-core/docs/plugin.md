# AI Core Plugin

AI Core is the central AI execution layer for Art INPA Laravel plugins.

It is not a chat widget and not a repair console. It owns shared AI infrastructure:

- AI Gateway connection and API key
- model registry
- dataset and knowledge registry
- tool registry
- permission matrix
- usage limits and rate limits
- request, response, and audit logs
- tool results
- RAG requests
- image generation jobs
- vision requests
- artwork similarity requests
- training profile requests

All AI plugins must call the AI Server through AI Core:

```text
Plugin -> AI Core -> AI Server 10.10.0.40:8080
```

## Database Source of Truth

Editable AI operational settings are stored in `ai_core_settings`.

The Gateway API key must not be stored in child plugins. Child plugins may keep display or widget copy settings, but AI Gateway connection values belong to AI Core.

## Default Models

| Function | Model |
| --- | --- |
| general_chat | qwen3:8b |
| coding_chat | qwen2.5-coder:7b-instruct |
| image_generation | SDXL / ComfyUI |
| fast_image_generation | SDXL-Lightning |
| vision_analysis | llava:7b |
| embedding | paraphrase-multilingual-MiniLM-L12-v2 |
| artwork_similarity | clip-ViT-B-32 |

## Default Tools

- general_chat
- coding_chat
- image_generate
- image_fast_generate
- image_job_poll
- vision_analyze
- rag_index
- rag_search
- artwork_index
- artwork_search
- intent_classify
- training_job_create
- training_job_status

## Permission and Audit Flow

Every tool execution follows this sequence:

1. Load tool registry row.
2. Load model profile for the tool.
3. Check AI Core permission matrix.
4. Check usage limit.
5. Create `ai_core_requests` row.
6. Send request to the AI Gateway.
7. Store `ai_core_responses`.
8. Store `ai_core_tool_results` when relevant.
9. Write audit events for permission checks and failures.

## Child Plugin Integration

Child plugins should resolve `Modules\AiCore\AiCore` from the Laravel container and call named AI Core operations:

```php
app(\Modules\AiCore\AiCore::class)->chat($payload, [
    'plugin' => 'ai-assistant',
], $user);
```

Specialized consumer methods are also available:

- `detectIntent`
- `chat`
- `chatCoding`
- `generateImage`
- `fastGenerateImage`
- `pollImageJob`
- `analyzeImage`
- `searchArtwork`
- `searchRag`
- `logToolResult`
- `trainingJobCreate`
- `trainingJobStatus`

## Safety Rules

- AI Server API keys are never exposed to frontend code.
- Child plugins must not call the AI Server directly after AI Core is installed.
- Sensitive tools require permission and audit.
- Laravel remains responsible for platform data access and permission checks.
- The AI Gateway never receives raw database access.
- Large base64 payloads and secrets are redacted from audit logs.
