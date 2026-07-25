<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AiCore\AiToolReadinessService;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if (! Schema::hasTable('ai_core_audit_logs')) {
    echo json_encode(['ok' => false, 'error' => 'ai_core_audit_logs table is missing'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(1);
}

$snapshot = [
    'ok' => true,
    'status' => 'degraded',
    'gateway_base_url' => 'verification',
    'health_endpoint' => '/health',
    'models_endpoint' => '/models',
    'health' => [
        'gateway_status' => 'healthy',
        'comfyui_status' => 'healthy',
        'embedding_worker_status' => 'degraded',
        'qdrant_status' => 'unknown',
        'vision_worker_status' => 'unknown',
        'ollama_status' => 'unknown',
        'production_semantic' => 'unknown',
        'overall_status' => 'degraded',
    ],
    'models' => [],
    'error' => null,
    'checked_at' => now()->toDateTimeString(),
];

$auditId = DB::table('ai_core_audit_logs')->insertGetId([
    'event_type' => 'ai-core.health.checked',
    'actor_user_id' => null,
    'plugin_slug' => 'ai-core',
    'tool_slug' => 'ai-core.sync',
    'target_type' => 'verification',
    'target_id' => null,
    'allowed' => true,
    'reason' => null,
    'metadata' => json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    'ip_address' => null,
    'user_agent' => 'codex-verification',
    'created_at' => now(),
    'updated_at' => now(),
]);

try {
    $readiness = app(AiToolReadinessService::class);
    $checks = [
        'image_generate' => $readiness->check('image_generate'),
        'image_fast_generate' => $readiness->check('image_fast_generate'),
        'rag_search' => $readiness->check('rag_search'),
        'vision_analyze' => $readiness->check('vision_analyze'),
        'coding_chat' => $readiness->check('coding_chat'),
    ];

    echo json_encode([
        'ok' => true,
        'image_not_blocked_by_unrelated_deps' => ($checks['image_generate']['ready'] ?? false) === true
            && ($checks['image_fast_generate']['ready'] ?? false) === true,
        'rag_requires_embedding_qdrant' => ($checks['rag_search']['ready'] ?? true) === false
            && ($checks['rag_search']['reason_code'] ?? null) === 'dependency_degraded',
        'vision_requires_worker' => ($checks['vision_analyze']['ready'] ?? true) === false
            && ($checks['vision_analyze']['reason_code'] ?? null) === 'dependency_degraded',
        'coding_requires_ollama' => ($checks['coding_chat']['ready'] ?? true) === false
            && ($checks['coding_chat']['reason_code'] ?? null) === 'dependency_degraded',
        'checks' => $checks,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
} finally {
    DB::table('ai_core_audit_logs')->where('id', $auditId)->delete();
}
