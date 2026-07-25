<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\AiCore\AiCore;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::query()
    ->whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))
    ->first();

$beforeAuditId = (int) (DB::table('ai_core_audit_logs')->max('id') ?? 0);
$beforeRequestId = (int) (DB::table('ai_core_requests')->max('id') ?? 0);

$response = app(AiCore::class)->chat([
    'message' => 'مرحبا، هل تستطيع مساعدتي في المنصة؟',
    'plugin' => 'ai-assistant',
], [
    'plugin' => 'ai-assistant',
    'source' => 'codex_semantic_planner_shadow_smoke',
    'recent_messages' => [
        ['role' => 'user', 'content' => 'مرحبا'],
    ],
], $user);

$plannerAudit = DB::table('ai_core_audit_logs')
    ->where('id', '>', $beforeAuditId)
    ->whereIn('event_type', ['semantic_planner.shadow', 'semantic_planner.invalid_json', 'semantic_planner.failed'])
    ->orderByDesc('id')
    ->first();

$request = DB::table('ai_core_requests')
    ->where('id', '>', $beforeRequestId)
    ->where('tool_slug', 'general_chat')
    ->orderByDesc('id')
    ->first();

$context = is_string($request?->context ?? null) ? json_decode($request->context, true) : [];
$metadata = is_string($plannerAudit?->metadata ?? null) ? json_decode($plannerAudit->metadata, true) : [];

echo json_encode([
    'ok' => $plannerAudit !== null && $request !== null && isset($context['semantic_planner']),
    'response_ok' => (bool) ($response['ok'] ?? true),
    'planner_event_id' => $plannerAudit?->id,
    'planner_event_type' => $plannerAudit?->event_type,
    'planner_reason' => $plannerAudit?->reason,
    'planner_decision_summary' => data_get($metadata, 'decision_summary'),
    'planner_recommended_tool' => data_get($metadata, 'recommended_tool'),
    'request_id' => $request?->id,
    'request_tool_slug' => $request?->tool_slug,
    'request_context_semantic_planner' => $context['semantic_planner'] ?? null,
    'runtime_unchanged' => ($request?->tool_slug === 'general_chat') && (bool) data_get($context, 'semantic_planner.runtime_unchanged'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
