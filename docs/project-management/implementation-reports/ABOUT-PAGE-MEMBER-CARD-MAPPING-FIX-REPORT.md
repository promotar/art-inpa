# About Page Member Card Mapping Fix Report

## Task Title
Fix incorrect About page member card name/image mapping.

## Issue
The displayed member cards were mismatched:
- The podium image did not show `زياد منصور`.
- The white hijab image did not show `بشرى كفاية`.
- Old editable data was able to keep incorrect labels.

## Correct Mapping Applied
1. `بشرى كفاية`
   - White hijab image.
   - Role: `بشرى كفاية المدير التقني لشبكة الفنون التشكيلية للتراث - مستشارة الطريق في شركة 99 Agency`

2. `مؤيد الزاغة`
   - Orange background portrait.
   - Role: `المسؤول التقني والتسويقي في الشبكة الدولية للفنون التشكيلية - صاحب شركة MJ DESIGNS`

3. `زياد منصور`
   - Podium and microphones image.
   - Role: `المدير التقني لمنصة الشبكة الدولية للفن التشكيلي مدير شركة ليرا لإنشاء وادارة المواقع الالكترونية`

## Technical Fix
- Rebuilt the `art-about-members` section in page HTML/content.
- Cleared old `editable_data.members` from `page_builder_json` so stale values do not override the corrected card labels.

## Page Updated
- Page ID: `87`
- Slug: `about`

## Backup
`storage/app/codex-file-backups/20260704-221554-about-member-card-mapping`

## Verification
Confirmed public rendered order:
1. `بشرى كفاية`
2. `مؤيد الزاغة`
3. `زياد منصور`

## Result
Passed.
