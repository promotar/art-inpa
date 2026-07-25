<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$requests = DB::table('ai_core_requests')
    ->whereIn('tool_slug', ['image_generate', 'image_fast_generate', 'image_job_poll'])
    ->latest('id')
    ->limit(12)
    ->get(['id', 'tool_slug', 'endpoint', 'status', 'request_payload', 'context', 'created_at']);

$responses = DB::table('ai_core_responses')
    ->whereIn('request_id', $requests->pluck('id')->all())
    ->latest('id')
    ->get(['id', 'request_id', 'ok', 'response_payload', 'error_message']);

$jobs = DB::table('ai_core_jobs')
    ->latest('id')
    ->limit(12)
    ->get(['id', 'external_job_id', 'tool_slug', 'status', 'payload', 'result', 'created_at']);

echo json_encode([
    'requests' => $requests,
    'responses' => $responses,
    'jobs' => $jobs,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
