# About Page Courses Card and Image Fit Report

## Task Title
Add courses card and fix cropped About page image.

## Objective
Add a new courses card under the About page "ماذا نقدم؟" section and prevent the image in the "رسالتنا" section from being cropped.

## Page Updated
- Page ID: 87
- Slug: about
- Title: من نحن
- Status: Draft

## Changes Made
- Added a fifth service card:
  - Title: الدورات التعليمية
  - Link: https://learn.art-inpa.com/
  - Button text: زيارة منصة الدورات
- Updated service grid CSS to use responsive auto-fit columns.
- Added a CSS override for the story image:
  - `object-fit: contain`
  - light background behind the image

## Safety
- A database backup was created before the update.
- Backup path:
  `storage/app/codex-file-backups/20260705-103611-about-courses-card/page-87-before.json`

## Files Created Locally
- `Codex Files/add-about-courses-card.php`
- `Codex Files/verify-about-courses-card.php`
- `Codex Files/ABOUT-PAGE-COURSES-CARD-IMAGE-FIT-REPORT.md`

## Verification
- Page found: Yes
- Courses card exists: Yes
- Courses title exists: Yes
- Image crop fix exists: Yes
- `Drop widgets here` placeholder exists: No

## Notes
- No plugin files were modified.
- The update was applied only to the About page database content and CSS.
