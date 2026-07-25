# Professional Programmer Strict AI Core Acceptance Report

Date: 2026-06-29

## Objective

Run strict acceptance checks for `professional-programmer` and prove that it is a Production Repair Console consumer of AI Core only. No AI Assistant, AI Core implementation, or AI Server behavior was changed.

## Files Changed

```text
professional-programmer-plugin/professional-programmer/src/ProfessionalProgrammerAiGateway.php
professional-programmer-plugin/professional-programmer/src/ProfessionalProgrammerAiService.php
professional-programmer-plugin/professional-programmer/module.json
professional-programmer-plugin/professional-programmer/docs/plugin.md
professional-programmer-plugin/professional-programmer/resources/views/dashboard.blade.php
professional-programmer-plugin/professional-programmer/database/migrations/2026_06_29_000004_remove_professional_programmer_direct_ai_settings.php
```

## Direct AI Server References Removed

The stale database setting below was removed from `platform_settings`:

```text
professional_programmer.gateway_url
```

The plugin now has no stored direct-AI connection setting and no direct endpoint/client reference.

## AI Core Methods Used

`ProfessionalProgrammerAiGateway` uses:

```text
Modules\AiCore\AiCore::assertAvailable()
Modules\AiCore\AiCore::checkToolPermission()
Modules\AiCore\AiCore::chatCoding()
Modules\AiCore\AiCore::createTrainingJob()
Modules\AiCore\AiCore::getTrainingJobStatus()
Modules\AiCore\AiCore::getTrainingProfile()
```

`ProfessionalProgrammerAiService` uses AI Core only through the gateway adapter for AI execution. It additionally calls:

```text
Modules\AiCore\AiCore::logToolResult()
```

for `repair_analysis` success audit and controlled AI Core-unavailable failure audit when AI Core is reachable enough to accept a log event.

## Settings Kept

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

## Settings Removed Or Blocked

The cleanup migration removes or prevents stale Professional Programmer ownership of:

```text
direct AI runtime URL settings
direct AI runtime API credential settings
model registry fields
tool registry fields
dataset registry fields
global usage-limit fields
```

AI Core owns those central concerns.

## Fallback Behavior

If AI Core is missing, disabled, unhealthy, or denies execution:

```text
No direct AI Server fallback is attempted.
The response says: AI Core unavailable.
The incident scanner remains usable.
The dashboard remains usable.
The Repair Console remains usable.
Evidence diagnosis still returns when local incident evidence exists.
The failure is written to Laravel logs.
If AI Core is reachable enough, the failure is also logged through AiCore::logToolResult().
```

## Grep Proof

Production scan results:

```text
scan_10.10.0.40=absent
scan_AI_GATEWAY=absent
scan_AI Gateway URL=absent
scan_AI Gateway API Key=absent
scan_/v1/coding/chat=absent
scan_/v1/coding/training=absent
scan_direct_http_Http=absent
scan_direct_http_Guzzle=absent
scan_direct_http_curl=absent
scan_hardcoded_v1_endpoint=absent
scan_gateway_url_setting=absent
scan_api_key_setting=absent
scan_ai_gateway_url_setting=absent
scan_ai_gateway_api_key_setting=absent
```

## Settings Proof

```text
settings_count=18
settings_forbidden_hits=none
```

## AI Core Audit Proof

Runtime smoke test:

```text
coding_chat_ok=yes
training_status_ok=yes
ai_core_request_delta=2
ai_core_tool_result_delta=3
ai_core_audit_delta=3
latest_request_plugin=professional-programmer
latest_request_tool=training_job_status
latest_request_user_id=20
latest_request_status=completed
latest_request_has_role=yes
latest_request_has_request_summary=yes
latest_request_duration_ms=47
latest_request_error=
latest_result_tool=repair_analysis
latest_result_user_id=20
latest_result_status=completed
latest_result_has_response_summary=yes
```

Controlled fallback test:

```text
fallback_ok_false=yes
fallback_message_has_ai_core_unavailable=yes
fallback_endpoint=ai-core:codingChat
fallback_keeps_diagnosis_key=yes
fallback_tool_result_delta=1
```

## Learning Verification Boundary

Professional Programmer owns:

```text
UI
policy
approval state
displayed verification result
admin-approved training samples
local training verification records
```

AI Core owns:

```text
dataset permission
training request routing
audit
limits
training status bridge
```

AI Server owns:

```text
actual model training
model evaluation execution
candidate promotion result returned to AI Core
```

## Backups

Local backups:

```text
backups/professional-programmer-ai-core-scope-text-before-20260629-040128.zip
backups/professional-programmer-strict-fallback-before-20260629-041156.zip
```

Production backups:

```text
/root/codex-backups/professional-programmer-ai-core-scope-audit-20260629-040333
/root/codex-backups/professional-programmer-ai-core-scope-audit-20260629-040333/db-before-pp-scope-cleanup.sql.gz
/root/codex-backups/professional-programmer-strict-acceptance-20260629-041338
```

## Rollback

Restore plugin files:

```text
cd /var/www/store.z4rank.com/laravel
cp -a /root/codex-backups/professional-programmer-strict-acceptance-20260629-041338/professional-programmer modules/professional-programmer
if [ -d /root/codex-backups/professional-programmer-strict-acceptance-20260629-041338/public-assets ]; then
  cp -a /root/codex-backups/professional-programmer-strict-acceptance-20260629-041338/public-assets /var/www/store.z4rank.com/public_html/platform/plugins/professional-programmer
fi
php artisan optimize:clear --no-ansi
php artisan view:cache --no-ansi
php artisan route:cache --no-ansi
php artisan config:cache --no-ansi
```

Database rollback should only be used during a controlled maintenance window if the removed stale direct-AI setting must be restored:

```text
gunzip -c /root/codex-backups/professional-programmer-ai-core-scope-audit-20260629-040333/db-before-pp-scope-cleanup.sql.gz | mysql <database>
```

## Final Status

```text
Professional Programmer AI execution: AI Core only
Direct AI Server calls: none
Direct endpoint references: none
Direct AI runtime settings: removed
Settings screen scope: Professional Programmer only
AI Core audit: verified
Fallback: controlled AI Core unavailable response
Repair Console/scanner/dashboard: usable without AI response
```
