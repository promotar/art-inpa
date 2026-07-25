#!/usr/bin/env bash
set -euo pipefail

cd /var/www/store.z4rank.com/laravel

stamp=$(date +%Y%m%d-%H%M%S)
backup="/root/codex-backups/ai-intent-router-followup-image-${stamp}"
mkdir -p "$backup"

cp -a \
  app/Services/Ai/AiIntentRouter.php \
  app/Services/Ai/AiConversationService.php \
  app/Http/Controllers/Ai/AiChatController.php \
  tests/Feature/AiIntentRouterTest.php \
  project_documentation.md \
  PROJECT_DOCUMENTATION.md \
  "$backup"/ 2>/dev/null || true

php codex_tmp/db_backup.php "$backup/database.sql" >/tmp/codex_ai_followup_db_backup.out 2>&1 || true

echo "$backup"
