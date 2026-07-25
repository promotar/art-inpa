# Professional Programmer AI Core Scope Audit Report

Date: 2026-06-29

## Objective

Verify and enforce that `professional-programmer` remains a governed Production Repair Console that consumes AI through AI Core only. This task did not build AI Core, did not change AI Assistant, and did not change AI Server.

## Scope

Plugin audited:

```text
professional-programmer
```

Responsibilities confirmed inside the plugin:

```text
Incident scanning
Evidence-based debugging
Repair Console
Production Guard
Approval workflow
Backup checkpoint validation
Rollback plan validation
Risk summary validation
Professional Programmer learning verification
Repair-specific policies
Incident messages
Repair approvals
Professional Programmer dashboard
```

Responsibilities confirmed outside the plugin and owned by AI Core:

```text
central AI runtime connection credentials
model routing
model registry
tool registry
dataset registry
global usage limits
global AI permission matrix
AI audit trail
```

## Changes

Changed files:

```text
professional-programmer-plugin/professional-programmer/module.json
professional-programmer-plugin/professional-programmer/docs/plugin.md
professional-programmer-plugin/professional-programmer/resources/views/dashboard.blade.php
professional-programmer-plugin/professional-programmer/database/migrations/2026_06_29_000004_remove_professional_programmer_direct_ai_settings.php
```

Implementation details:

- Removed old wording that described the chat function as using an internal AI Gateway endpoint.
- Clarified that the plugin uses AI Core coding and training services.
- Changed the settings screen note so it only states that Professional Programmer settings are shown there.
- Added a cleanup migration that removes legacy direct-AI settings from `platform_settings` for this plugin.
- Kept repair-specific tool policies inside `professional_programmer_tool_policies`.
- Kept all backend analyzer, guard, approval, incident, and learning verification logic in the plugin.

## Backups

Local backup:

```text
backups/professional-programmer-ai-core-scope-text-before-20260629-040128.zip
```

Production file backup:

```text
/root/codex-backups/professional-programmer-ai-core-scope-audit-20260629-040333
```

Production database backup before cleanup migration:

```text
/root/codex-backups/professional-programmer-ai-core-scope-audit-20260629-040333/db-before-pp-scope-cleanup.sql.gz
```

## Verification

Static production checks:

```text
no_direct_ai_server_call=yes
no_core_registry_settings_in_runtime_ui_or_code=yes
uses_ai_core_class=yes
checks_tool_permission=yes
uses_ai_core_coding_chat=yes
uses_ai_core_training_create=yes
uses_ai_core_training_status=yes
uses_ai_core_training_profile=yes
fails_closed_without_ai_core=yes
forbidden_hits=none
runtime_registry_hits=none
admin_render_status=200
settings_shows_professional_scope=yes
settings_exposes_core_registry_terms=no
```

Database settings cleanup:

```text
professional_settings_count=18
forbidden_setting_hits=none
```

Remaining Professional Programmer settings:

```text
enabled
admin_widget_enabled
auto_scan_logs_on_admin_request
log_scan_cooldown_seconds
log_tail_bytes
max_admin_alerts
learning_enabled
learning_max_files_per_run
learning_max_file_bytes
system_prompt
repair_requires_admin_approval
require_fresh_training_before_repair
training_fresh_minutes
require_backup_before_repair
require_written_plan_before_repair
web_terminal_write_allowed
suppress_maintenance_noise
backup_roots
```

Runtime smoke test:

```text
plugin_active=yes
scan_ok=yes
scan_created=0
scan_updated=0
scan_suppressed=2
learn_ok=yes
learn_files_seen=402
ai_coding_via_ai_core=yes
ai_coding_response_has_data=yes
ai_core_audit_delta=1
training_endpoint_reachable=yes
learning_verified=yes
generic_answer_count=0
```

## Final Status

```text
Log scan: ok
Incident creation path: ok
Evidence-based diagnosis path: ok
Repair Console: ok
Approval guard: unchanged and active
Backup/rollback validation: unchanged and active
Coding chat: AI Core only
Training status/job bridge: AI Core only
AI Core audit: ok
Direct AI Server call: none
Legacy direct-AI setting: removed
```

## Notes

No repair was executed. The smoke test called the Professional Programmer coding route through AI Core and produced one AI Core audit request. No production source code changes were made through the web console.
