# Professional Programmer Plugin

Admin-only developer intelligence plugin for Art INPA.

## Purpose

- Learn the Laravel platform structure continuously through database-backed code fingerprints, route maps, schema metadata, plugin manifests, settings metadata, permissions metadata, documentation, migration state, and operational metadata.
- Include conversation table metadata in the learning index so the assistant understands active AI usage patterns.
- Monitor Laravel logs and register deduplicated incidents with severity.
- Open an admin chat immediately when unresolved incidents exist.
- Explain severity, likely root cause, and repair plan in Arabic.
- Record guarded admin approval before any code repair workflow begins.

## Safety

The web plugin does not modify source files directly. Repair approval records are stored in `professional_programmer_repair_approvals` with status `approved_pending_codex`. A Codex/server maintenance session must still perform the actual code changes after reviewing the approved incident.

Approval is blocked unless:

- the latest learning run is fresh enough for production work
- required learning source types are present
- the admin provides a written plan, risk summary, expected impact, and rollback plan
- a pre-repair backup checkpoint is created successfully
- browser-side terminal/file/database/deploy writes remain blocked

Tool permissions are recorded in `professional_programmer_tool_policies`.

## Evidence-Based Debugging

Incident analysis must start from stored log evidence, not generic model advice. `ProfessionalProgrammerIncidentAnalyzer` extracts:

- original error text
- file and line when available
- SQLSTATE when available
- affected table and column when available
- likely cause based on evidence
- excluded causes
- checks required before repair
- severity
- repair type: migration, code, data cleanup, or unknown
- backup and approval requirement

The widget renders this as a Repair Console with cards instead of a normal long chat paragraph. Approval fields are auto-filled from the extracted evidence and still validated server-side before any approval is recorded.

## Data Tables

- `professional_programmer_learning_runs`
- `professional_programmer_learning_sources`
- `professional_programmer_incidents`
- `professional_programmer_messages`
- `professional_programmer_repair_approvals`
- `professional_programmer_tool_policies`
- `professional_programmer_backup_checkpoints`

## Settings

All editable behavior is registered in `platform_settings` under group `professional_programmer`, including enabled flags, log scan cooldown, learning limits, production guard requirements, backup roots, and the coding system prompt. AI Gateway connection, API key, model routing, and tool permission settings are owned by AI Core.

## Admin Routes

- `GET /admin/plugins/professional-programmer`
- `PATCH /admin/plugins/professional-programmer/settings`
- `POST /admin/plugins/professional-programmer/learn`
- `POST /admin/plugins/professional-programmer/scan`
- `GET /admin/plugins/professional-programmer/alerts`
- `POST /admin/plugins/professional-programmer/message`
- `POST /admin/plugins/professional-programmer/approve`

## Operational Notes

The middleware injects the widget into authenticated admin HTML pages only. It scans logs with a cooldown and bounded tail processing to avoid heavy request overhead. If the production learning index is stale, admin entry triggers a guarded auto refresh with cache cooldown. Maintenance parser/test noise is suppressed so admin alerts focus on production issues.
