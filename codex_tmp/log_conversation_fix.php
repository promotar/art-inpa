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
    'operation_type' => 'plugin.ai-assistant.conversation-fix',
    'target_type' => 'plugin',
    'target_slug' => 'ai-assistant',
    'status' => 'completed',
    'message' => 'AI Assistant frontend CSRF, popup close/minimize, and conversation persistence fixed.',
    'context' => json_encode([
        'backup_path' => '/root/codex-backups/ai-assistant-plugin-install-20260628-013824',
        'routes' => [
            'GET /ai-assistant/messages',
            'DELETE /ai-assistant/conversation',
            'POST /ai-assistant/message',
        ],
        'behavior' => [
            'frontend_csrf' => 'widget carries csrf token and JS sends same-origin credentials',
            'minimize' => 'hides panel and keeps conversation active',
            'close' => 'deletes active conversation and clears session pointer',
            'authenticated_users' => 'conversation bound by user_id',
            'guests' => 'conversation bound by session_id',
        ],
        'verified' => [
            'frontend_post' => true,
            'history_persisted' => true,
            'close_deleted' => true,
            'tests' => '25 passed, 61 assertions',
        ],
        'secrets' => 'No secret value copied into logs or reports.',
    ], JSON_UNESCAPED_SLASHES),
    'started_at' => $now,
    'finished_at' => $now,
    'created_at' => $now,
    'updated_at' => $now,
]);

echo "operation_log_inserted\n";
