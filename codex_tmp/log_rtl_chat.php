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
    'operation_type' => 'plugin.ai-assistant.rtl-chat',
    'target_type' => 'plugin',
    'target_slug' => 'ai-assistant',
    'status' => 'completed',
    'message' => 'AI Assistant conversation text changed to always render RTL and right-aligned for Arabic audience.',
    'context' => json_encode([
        'backup_path' => '/root/codex-backups/ai-assistant-rtl-chat-20260628-044501',
        'files' => [
            'modules/ai-assistant/resources/assets/css/ai-assistant.css',
            'modules/ai-assistant/resources/views/widget.blade.php',
            'modules/ai-assistant/resources/views/chat-page.blade.php',
            'public_html/platform/plugins/ai-assistant/css/ai-assistant.css',
        ],
        'verified' => [
            'css_version' => 'ai-assistant.css?v=20260628-rtl1',
            'rtl_rules' => true,
        ],
        'secrets' => 'No secret value copied into logs or reports.',
    ], JSON_UNESCAPED_SLASHES),
    'started_at' => $now,
    'finished_at' => $now,
    'created_at' => $now,
    'updated_at' => $now,
]);

echo "operation_log_inserted\n";
