#!/usr/bin/env bash
set -euo pipefail

cd /var/www/store.z4rank.com/laravel

STAMP="${1:-$(date +%Y%m%d-%H%M%S)}"
BACKUP="/root/codex-backups/ai-core-control-center-${STAMP}"

mkdir -p "$BACKUP"
cp -a modules/ai-core "$BACKUP/ai-core"

read_env() {
  php -r '$e=parse_ini_file(".env", false, INI_SCANNER_RAW); echo $e[$argv[1]] ?? $argv[2] ?? "";' "$1" "${2:-}"
}

DB_HOST="$(read_env DB_HOST 127.0.0.1)"
DB_PORT="$(read_env DB_PORT 3306)"
DB_DATABASE="$(read_env DB_DATABASE)"
DB_USERNAME="$(read_env DB_USERNAME)"
DB_PASSWORD="$(read_env DB_PASSWORD)"

MYSQL_PWD="$DB_PASSWORD" mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" \
  | gzip > "$BACKUP/db-before-ai-core-control-center.sql.gz"

echo "$BACKUP"
