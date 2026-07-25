# AI Assistant Memory, Access, and Gateway Context Fix

Date: 2026-06-28

## Objective

Fix the Art INPA AI Assistant so it behaves as a governed platform assistant instead of a stateless generic chat model. The user reported that the assistant did not remember earlier messages, did not understand the current user, and answered role/permission questions without checking Laravel platform data.

## Root Cause

- The AI Assistant plugin sent the current user message to the AI Gateway without enough conversation history.
- User identity, role, permission, and conversation-memory questions were delegated to the model instead of being answered from Laravel's authenticated user/session data.
- The AI Gateway accepted only the current message for general and coding chat, so local Ollama models had no reliable conversation state.
- The gateway system prompt did not explicitly define history ordering or Laravel context authority.

## Changes

### Laravel AI Assistant Plugin

- Added conversation history to the gateway payload.
- Added conversation memory context from Laravel conversation metadata.
- Added deterministic Laravel responses before gateway calls for:
  - first message / first word / conversation summary questions
  - current user name and email
  - current user roles and permissions
  - super admin/admin checks
  - assistant platform role/capability questions
- Kept sensitive access decisions inside Laravel instead of trusting the model.

### AI Gateway

- Added `history`, `context`, `authorized_data`, `rag_results`, and `attachments` support to general chat requests.
- Added `context` and `history` support to coding chat requests.
- Updated `/v1/general/chat` to build messages as:
  - system governance prompt
  - chronological history
  - current user message
- Updated `/v1/coding/chat` to include Laravel context and recent history.
- Strengthened the gateway governance prompt:
  - history is chronological oldest to newest
  - first/earliest questions must use the earliest user message
  - Laravel-provided context/data is authoritative for platform facts
  - no invented platform access or hidden capability claims

## Files Changed

- `ai-assistant-plugin/ai-assistant/src/AiAssistantController.php`
- `remote-edit/ai-server-gateway/app/schemas.py`
- `remote-edit/ai-server-gateway/app/router_general.py`
- `remote-edit/ai-server-gateway/app/router_code.py`
- `ai-assistant.zip`
- `project.txt`
- `project_documentation.md`
- `changes-log.txt`
- `backups-log.txt`

## Production Backups

- Laravel plugin controller backup:
  `/root/codex-backups/ai-assistant-memory-access-fix-20260628-204247`
- AI Gateway history/context backup:
  `/root/codex-backups/ai-gateway-history-context-20260628-204751`
- AI Gateway prompt backup:
  `/root/codex-backups/ai-gateway-governance-prompt-20260628-205140`
- Local plugin ZIP backup:
  `backups/ai-assistant-before-memory-access-20260628-205401.zip`

## Deployment

### Laravel Server

Target:

```text
10.10.0.20:/var/www/store.z4rank.com/laravel
```

Actions:

```text
php -l modules/ai-assistant/src/AiAssistantController.php
php artisan optimize:clear --no-ansi
php artisan view:cache --no-ansi
php artisan route:cache --no-ansi
php artisan config:cache --no-ansi
```

### AI Server

Target:

```text
10.10.0.40:/srv/ai-server
```

Actions:

```text
python3 -m py_compile apps/ai-gateway/app/router_general.py apps/ai-gateway/app/router_code.py apps/ai-gateway/app/schemas.py
docker compose build ai-gateway
docker compose up -d ai-gateway
docker compose restart ai-gateway
```

## Verification

- Laravel controller syntax passed.
- Laravel cache rebuild passed.
- Laravel deterministic memory smoke returned:
  - first message: `lvpfm`
  - first word: `lvpfm`
- Laravel authenticated user access smoke found a super admin user and returned roles from Laravel data.
- AI Gateway direct history test returned:
  - `الكلمة الأولى التي كتبتها في المحادثة كانت "lvpfm".`
- AI Gateway container is running on `10.10.0.40:8080`.
- Rebuilt local `ai-assistant.zip` with the updated plugin source.
- Operation log recorded in Laravel `operation_logs` with ID `228`.

## Important Architecture Note

This does not create a Codex/GPT-5-class model from scratch. The practical production path on the user's servers is:

```text
Laravel governance + permissions + tool registry + conversation memory
↓
Local AI Gateway
↓
Local models and tools such as Ollama, ComfyUI, Qdrant, and workers
```

The assistant becomes stronger by combining deterministic Laravel authority with local models, tools, RAG, and memory. This is the correct safe architecture for a private, self-hosted platform assistant.

## Remaining Improvements

- Add more whitelisted Laravel data tools for admin reports.
- Add a UI-visible debug/audit page for AI intents and tool calls.
- Evaluate larger local reasoning models if server GPU/RAM allows.
- Add regression tests around the plugin controller once module test bootstrapping is stable.
