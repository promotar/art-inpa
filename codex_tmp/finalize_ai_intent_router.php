<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$base = '/var/www/store.z4rank.com/laravel';

$projectDoc = $base.'/PROJECT_DOCUMENTATION.md';
$append = $base.'/codex_tmp/PROJECT_DOCUMENTATION_APPEND.md';

if (is_file($append)) {
    $current = is_file($projectDoc) ? file_get_contents($projectDoc) : "# Project Documentation\n\n";
    $section = file_get_contents($append);

    if (! str_contains($current, 'Laravel AI Intent Router')) {
        file_put_contents($projectDoc, rtrim($current)."\n\n".$section."\n");
    }

    $lower = $base.'/project_documentation.md';
    if (is_file($lower)) {
        $lowerCurrent = file_get_contents($lower);
        if (! str_contains($lowerCurrent, 'Laravel AI Intent Router')) {
            file_put_contents($lower, rtrim($lowerCurrent)."\n\n".$section."\n");
        }
    }
}

require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (Schema::hasTable('operation_logs')) {
    $now = now();
    DB::table('operation_logs')->insert([
        'operation_type' => 'ai.intent-router.implementation',
        'target_type' => 'core',
        'target_slug' => 'ai-intent-router',
        'status' => 'completed',
        'message' => 'Completed Laravel AI Intent Router with permission-aware data access and tests.',
        'context' => json_encode([
            'backup_path' => '/root/codex-backups/ai-intent-router-20260628-022237',
            'route' => 'POST /ai/message',
            'tests' => '36 passed, 84 assertions',
            'security' => [
                'no_ai_database_access' => true,
                'whitelisted_tools_only' => true,
                'sensitive_actions_require_confirmation' => true,
            ],
            'secrets' => 'No secret value copied into logs or reports.',
        ], JSON_UNESCAPED_SLASHES),
        'started_at' => $now,
        'finished_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

echo "ai_intent_router_finalized\n";
