# Professional Programmer Evidence-Based Repair Console Report

Date: 2026-06-28

## Objective

Move the Professional Programmer plugin from generic AI debugging answers to evidence-based debugging and a professional Repair Console UI.

## Changes

- Added `ProfessionalProgrammerIncidentAnalyzer`.
- Extracts deterministic evidence before model response:
  - original error
  - file and line
  - SQLSTATE
  - affected table
  - affected column
  - likely cause
  - excluded causes
  - required checks
  - severity
  - repair type
  - migration/code/data-cleanup needs
  - backup and approval requirement
- Added evidence payload into AI context as `evidence_based_diagnosis`.
- Strengthened system prompt so generic answers are prohibited when evidence exists.
- Changed widget from small chat box to wider Repair Console.
- Added fullscreen/expanded mode.
- Added structured diagnosis cards:
  - نص الخطأ الأصلي
  - الملف والسطر
  - الجدول أو العمود
  - السبب المرجح
  - الأدلة
  - أسباب مستبعدة
  - فحص مطلوب قبل الإصلاح
  - نوع الإصلاح والخطورة
- Approval fields are auto-filled from diagnosis evidence.
- Frontend blocks approval click if plan/risk/impact/rollback fields are still empty.
- Backend approval guard remains authoritative.

## Files Changed

```text
professional-programmer-plugin/professional-programmer/src/ProfessionalProgrammerIncidentAnalyzer.php
professional-programmer-plugin/professional-programmer/src/ProfessionalProgrammerServiceProvider.php
professional-programmer-plugin/professional-programmer/src/ProfessionalProgrammerAiService.php
professional-programmer-plugin/professional-programmer/src/ProfessionalProgrammerController.php
professional-programmer-plugin/professional-programmer/resources/views/widget.blade.php
professional-programmer-plugin/professional-programmer/docs/plugin.md
```

## Verification

Production backup before deployment:

```text
/root/codex-backups/professional-programmer-evidence-console-20260628-183958
```

Evidence extraction smoke test inserted a temporary incident with:

```text
SQLSTATE[42S22]
Unknown column 'seo_title'
table: blog_posts
file: modules/Blog/src/Http/Controllers/Admin/PostController.php
line: 221
```

Analyzer result:

```text
column=seo_title
table=blog_posts
file=modules/Blog/src/Http/Controllers/Admin/PostController.php
line=221
repair_type=migration
needs_migration=yes
has_generic_answer=no
```

Focused checks:

```text
php codex_tmp/verify_professional_programmer_admin_render.php
php codex_tmp/verify_professional_programmer_ai.php
php codex_tmp/verify_professional_programmer.php
```

Results:

```text
admin_render_status=200
ai_ok=yes
endpoint=/v1/coding/chat
plugin_status=active
scan_ok=yes
learn_ok=yes
```

Temporary verification incidents were suppressed after the test.

## Result

The plugin now starts diagnosis from concrete log evidence. If a log names a table or column, the Repair Console surfaces that exact table or column as the center of the problem and does not show generic advice such as “check SQL syntax”.

## E2E Evidence-Based Debugging Test - 2026-06-28

### Scope

The Professional Programmer plugin was tested end-to-end with controlled test incidents only. No real repairs were applied and no production data repair was executed.

### Backup Before Test/Change

```text
/root/codex-backups/professional-programmer-e2e-test-20260628-203233
```

Contents:

```text
professional-programmer-module-before.tar.gz
professional-programmer-tables-before.sql
```

### Fixed During Test

The first matrix run exposed one extraction bug:

```text
data_cleanup_needed: table mismatch: 'a'
```

Cause: the table extractor accepted an unquoted word after `update`, so it read the phrase `update a child row` as table `a`.

Fix: table extraction now only accepts clear database evidence such as explicit DB error table names or quoted SQL table names such as an `insert into orders` statement with a quoted table identifier in the SQL log.

### Matrix Results

| Case | SQLSTATE | Table | Column | Repair type | Backup | Migration | Generic answer |
| --- | --- | --- | --- | --- | --- | --- | --- |
| SQL missing column | 42S22 | blog_posts | seo_title | migration | yes | yes | false |
| SQL missing table | 42S02 | artist_profiles | - | migration | yes | yes | false |
| duplicate key / unique constraint | 23000 | users | email | data_cleanup | yes | no | false |
| permission denied | - | - | - | code | yes | no | false |
| pure PHP exception with no SQL evidence | - | - | - | code | yes | no | false |
| malformed SQL syntax without clear table/column | 42000 | - | - | unknown | yes | no | false |
| migration-needed case | 42S22 | blog_posts | published_at | migration | yes | yes | false |
| data-cleanup-needed case | 23000 | orders | user_id | data_cleanup | yes | no | false |
| code-fix-needed case | - | - | - | code | yes | no | false |

### Guard/UI Verification

- Original error extraction passed for all 9 cases.
- File and line extraction passed for all 9 cases.
- SQLSTATE extraction passed when SQLSTATE existed.
- Table and column extraction remained empty when evidence did not support them.
- `malformed_sql_no_clear_target` returned `repair_type=unknown`, empty approval auto-fill, and `insufficient evidence`.
- Backend guard rejected incomplete repair approval requests with missing plan, risk, impact, and rollback fields.
- Repair Console markup contains readable diagnosis cards, approval auto-fill logic, and disabled approval button state.
- Test incidents were cleaned up by setting them to `suppressed`.

### Final Verification

```text
admin_render_status=200
admin_render_has_title=yes
ai_ok=yes
endpoint=/v1/coding/chat
plugin_status=active
scan_ok=yes
learn_ok=yes
e2e_open_test_incidents=0
e2e_suppressed_test_incidents=18
```

### Remaining Risks

- The test uses controlled Laravel-style log strings, not every possible database engine message shape.
- Browser visual verification was covered by server-render and static UI checks; a trusted browser bridge was not available in the earlier widget validation flow.
- Real repair execution remains intentionally outside the browser widget and must continue through a separate documented maintenance/Codex session with a fresh operator backup.
