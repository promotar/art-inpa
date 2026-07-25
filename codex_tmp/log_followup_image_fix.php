<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! Schema::hasTable('operation_logs')) {
    echo "operation_logs_missing\n";
    exit(0);
}

$now = now();

DB::table('operation_logs')->insert([
    'operation_type' => 'ai.intent-router.followup-image-fix',
    'target_type' => 'core',
    'target_slug' => 'ai-intent-router',
    'status' => 'completed',
    'message' => 'Fixed image-generation hard matching and visual follow-up routing for Laravel AI Intent Router.',
    'context' => json_encode([
        'backup_path' => '/root/codex-backups/ai-intent-router-followup-image-20260628-025141',
        'route' => 'POST /ai/message',
        'tests' => [
            'AiIntentRouterTest' => '16 passed, 40 assertions',
            'full_suite' => '41 passed, 101 assertions',
        ],
        'routing' => [
            'generate_image_endpoint' => '/v1/images/generate',
            'follow_up_state' => 'ai_conversations.metadata',
        ],
        'secrets' => 'No secret value copied into logs or reports.',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    'started_at' => $now,
    'finished_at' => $now,
    'created_at' => $now,
    'updated_at' => $now,
]);

echo "operation_log_inserted\n";
