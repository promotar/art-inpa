# Professional Programmer Plugin

Admin-only developer intelligence plugin for Art INPA.

## Purpose

- Learn the Laravel platform structure continuously through database-backed code fingerprints and operational metadata.
- Include conversation table metadata in the learning index so the assistant understands active AI usage patterns.
- Monitor Laravel logs and register deduplicated incidents with severity.
- Open an admin chat immediately when unresolved incidents exist.
- Explain severity, likely root cause, and repair plan in Arabic.
- Record admin approval before any code repair workflow begins.

## Safety

The web plugin does not modify source files directly. Repair approval records are stored in `professional_programmer_repair_approvals` with status `approved_pending_codex`. A Codex/server maintenance session must still perform the actual code changes after reviewing the approved incident.

## Data Tables

- `professional_programmer_learning_runs`
- `professional_programmer_learning_sources`
- `professional_programmer_incidents`
- `professional_programmer_messages`
- `professional_programmer_repair_approvals`

## Settings

All editable behavior is registered in `platform_settings` under group `professional_programmer`, including enabled flags, log scan cooldown, learning limits, AI Gateway URL, and the coding system prompt.

## Admin Routes

- `GET /admin/plugins/professional-programmer`
- `PATCH /admin/plugins/professional-programmer/settings`
- `POST /admin/plugins/professional-programmer/learn`
- `POST /admin/plugins/professional-programmer/scan`
- `GET /admin/plugins/professional-programmer/alerts`
- `POST /admin/plugins/professional-programmer/message`
- `POST /admin/plugins/professional-programmer/approve`

## Operational Notes

The middleware injects the widget into authenticated admin HTML pages only. It scans logs with a cooldown to avoid heavy request overhead.
