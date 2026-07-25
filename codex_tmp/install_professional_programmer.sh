#!/usr/bin/env bash
set -euo pipefail

cd /var/www/store.z4rank.com/laravel

zip_path="codex_tmp/professional-programmer.zip"
staging="codex_tmp/professional-programmer-extract"
target="modules/professional-programmer"
stamp=$(date +%Y%m%d-%H%M%S)
backup_dir="/root/codex-backups/professional-programmer-install-$stamp"

if [ ! -f "$zip_path" ]; then
  echo "missing_zip"
  exit 1
fi

mkdir -p "$backup_dir" modules codex_tmp
cp -a project_documentation.md "$backup_dir/project_documentation.md" 2>/dev/null || true
if [ -d "$target" ]; then
  cp -a "$target" "$backup_dir/professional-programmer-module-before"
fi

if [ -f codex_tmp/db_backup.php ]; then
  php codex_tmp/db_backup.php "$backup_dir/database.sql" >/tmp/codex_professional_programmer_db_backup.out 2>&1 || true
fi

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

find "$target/src" -name '*.php' -print0 | xargs -0 -n1 php -l
find "$target/database/migrations" -name '*.php' -print0 | xargs -0 -n1 php -l

php artisan migrate --force --no-ansi
php codex_tmp/install_activate_professional_programmer.php
php artisan optimize:clear --no-ansi
php artisan view:cache --no-ansi
php artisan route:cache --no-ansi
php artisan config:cache --no-ansi

echo "$backup_dir"
