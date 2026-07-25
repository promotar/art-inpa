# Professional Programmer AI Core Routing Report

Date: 2026-06-29

## Objective

Remove direct Professional Programmer access to the AI Server and force all AI execution through AI Core.

Required flow:

```text
professional-programmer -> ai-core -> AI Gateway / AI Server
```

## Implementation

AI Core:

- Added Professional Programmer-compatible methods for coding chat, training jobs, training status, permission checks, training profile lookup, and tool-result logging.
- Fixed AI Core role normalization so Laravel role `super-admin` maps to AI Core role `super_admin`.
- Fixed AI Core request audit duration handling so failed requests cannot write negative `duration_ms`.
- Applied the AI Core migration on production.
- Stored the active AI Gateway key in `ai_core_settings`; the secret value is not documented.

Professional Programmer:

- Removed the legacy fallback to `App\Services\Ai\AiGatewayClient`.
- Routed coding chat through `app(Modules\AiCore\AiCore::class)->chatCoding(...)`.
- Routed training job creation/status through AI Core.
- Kept Production Guard, Repair Console, Incident Analyzer, Learning Service, Learning Verification, backup checkpoint logic, approval workflow, evidence rules, risk evaluation, and rollback requirements inside Professional Programmer.
- Changed returned endpoint marker to `ai-core:codingChat`.
- Fixed foreign-key evidence parsing so data cleanup cases extract the affected table from constraint-failure logs.

AI Gateway:

- Registered `router_professional_training` in `main.py`.
- Rebuilt and recreated only `ai-gateway`.
- `GET /v1/coding/training/status` is now reachable through AI Core.

## Backups

```text
/root/codex-backups/professional-programmer-ai-core-routing-20260629-013103
/root/codex-backups/professional-programmer-ai-core-routing-20260629-013103/db-before-ai-core-migration.sql.gz
/root/codex-backups/ai-gateway-professional-training-router-20260629-015752
```

## Verification

Controlled test matrix passed:

- SQL missing column
- SQL missing table
- duplicate key / unique constraint
- permission denied
- pure PHP exception with no SQL evidence
- malformed SQL syntax without clear table/column
- migration-needed case
- data-cleanup-needed case
- code-fix-needed case

Final status:

```text
Admin render: yes
Plugin active: yes
Scan ok: yes
Learn ok: yes
training_endpoint_reachable: yes
learning_verified: yes
generic_answer_count: 0
AI coding endpoint: ai-core:codingChat
AI coding ok: yes
No direct AI Server call in professional-programmer: yes
```

## Safety Notes

- No production repair was executed.
- Test incidents were controlled and suppressed after analysis.
- No raw logs were used as training samples.
- Learning verification remains separate from endpoint reachability.
- Backend guard remains the final authority for approval and repair safety.
