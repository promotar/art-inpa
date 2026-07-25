#!/usr/bin/env bash
set -euo pipefail

cd /var/www/store.z4rank.com/laravel

stamp=$(date +%Y%m%d-%H%M%S)
backup_dir="/root/codex-backups/ai-intent-router-$stamp"
mkdir -p "$backup_dir"

tar -czf "$backup_dir/laravel-ai-router-files-before.tar.gz" \
  app config routes database tests PROJECT_DOCUMENTATION.md project_documentation.md 2>/tmp/codex_ai_router_tar.err || true

php codex_tmp/db_backup.php "$backup_dir/database.sql" >/tmp/codex_ai_router_db_backup.out 2>&1 || true

php <<'PHP'
<?php
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (Schema::hasTable('operation_logs')) {
    $now = now();
    DB::table('operation_logs')->insert([
        'operation_type' => 'ai.intent-router.implementation',
        'target_type' => 'core',
        'target_slug' => 'ai-intent-router',
        'status' => 'started',
        'message' => 'Started Laravel AI Intent Router implementation with permission-aware data access.',
        'context' => json_encode([
            'scope' => 'Laravel-only AI routing layer. External model runtimes remain outside Laravel.',
            'secrets' => 'No secret value copied into logs.',
        ], JSON_UNESCAPED_SLASHES),
        'started_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}
PHP

echo "$backup_dir"
