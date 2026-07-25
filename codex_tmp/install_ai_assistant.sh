#!/usr/bin/env bash
set -euo pipefail

cd /var/www/store.z4rank.com/laravel

zip_path="codex_tmp/ai-assistant.zip"
staging="codex_tmp/ai-assistant-extract"
target="modules/ai-assistant"
stamp=$(date +%Y%m%d-%H%M%S)
backup_dir="/root/codex-backups/ai-assistant-plugin-install-$stamp"

if [ ! -f "$zip_path" ]; then
  echo "missing_zip"
  exit 1
fi

mkdir -p "$backup_dir" modules codex_tmp
cp -a project_documentation.md "$backup_dir/project_documentation.md" 2>/dev/null || true
if [ -d "$target" ]; then
  cp -a "$target" "$backup_dir/ai-assistant-module-before"
fi

php codex_tmp/db_backup.php "$backup_dir/database.sql" >/tmp/codex_ai_assistant_db_backup.out 2>&1 || true

rm -rf "$staging"
mkdir -p "$staging"
unzip -q "$zip_path" -d "$staging"

if [ ! -f "$staging/module.json" ]; then
  echo "missing_module_json"
  exit 1
fi

resolved_target=$(realpath -m "$target")
resolved_modules=$(realpath -m modules)
case "$resolved_target" in
  "$resolved_modules"/*) ;;
  *) echo "unsafe_target"; exit 1 ;;
esac

rm -rf "$target"
mkdir -p "$target"
cp -a "$staging"/. "$target"/

php -l "$target/src/AiAssistantServiceProvider.php"
php -l "$target/src/AiAssistantController.php"
php -l "$target/src/AiAssistantAdminController.php"
php codex_tmp/install_activate_ai_assistant.php

echo "$backup_dir"
