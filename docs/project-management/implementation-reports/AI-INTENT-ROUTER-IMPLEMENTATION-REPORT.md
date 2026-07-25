# AI Intent Router Implementation Report

Date: 2026-06-28

## Summary

Implemented a Laravel-only AI Intent Routing layer that lets plugins send AI requests to one endpoint while Laravel decides intent, permissions, limits, data access, sensitive action confirmation, and external AI VPS Gateway routing.

## Backup

```text
/root/codex-backups/ai-intent-router-20260628-022237
```

## Route Added

```text
POST /ai/message
```

Route name:

```text
ai.message
```

## Key Security Decisions

- AI models do not access the database.
- AI Gateway does not query Laravel database.
- Laravel uses whitelisted tools only.
- Sensitive database changes require confirmation.
- Admin data access is permission checked and audited.
- API keys are read from config/environment and are never exposed to frontend.

## Gateway Endpoints

- `general_chat` -> `/v1/general/chat`
- `rag_question` -> `/v1/rag/search` then `/v1/general/chat`
- `generate_image` -> `/v1/images/generate`
- `fast_generate_image` -> `/v1/images/fast-generate`
- `vision_analyze` -> `/v1/vision/analyze`
- `artwork_similarity` -> `/v1/artwork/search`
- `coding_assistant` -> `/v1/coding/chat`

## Permission-Aware Data Tools

- `users_registered_last_24h`
- `user_own_profile`
- `platform_basic_stats`

All data tool attempts are logged in `ai_tool_audit_logs`.

## Tests Added

```text
tests/Feature/AiIntentRouterTest.php
```

Test coverage includes:

- action-based image intent
- image upload vision intent
- artwork similarity keywords
- profile update confirmation
- coding assistant blocked for normal users
- fallback classifier use
- low-confidence clarification
- normal user denied from listing users
- admin allowed to list users
- audit logging for allowed and denied data access
- sensitive fields not returned
- arbitrary database table request ignored

## Integration Notes

Plugins should call `POST /ai/message` with `message`, optional `plugin`, optional `action`, optional `conversation_id`, optional `context`, and optional sanitized attachment metadata.

Subscription integration is currently a placeholder in `AiPermissionChecker` and can be connected later to the final plan/package system.
