<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$requestId = (int) ($argv[1] ?? 0);
$auditId = (int) ($argv[2] ?? 0);

$request = DB::table('ai_core_requests')->where('id', $requestId)->first();
$audit = $auditId > 0 ? DB::table('ai_core_audit_logs')->where('id', $auditId)->first() : null;

$payload = is_string($request?->request_payload ?? null) ? json_decode($request->request_payload, true) : [];
$context = is_string($request?->context ?? null) ? json_decode($request->context, true) : [];
$metadata = is_string($audit?->metadata ?? null) ? json_decode($audit->metadata, true) : [];
$prompt = (string) ($payload['prompt'] ?? '');

echo json_encode([
    'request_id' => $requestId,
    'audit_id' => $auditId,
    'payload_prompt' => $prompt,
    'payload_prompt_hex' => bin2hex($prompt),
    'context' => $context,
    'audit_metadata' => $metadata,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
