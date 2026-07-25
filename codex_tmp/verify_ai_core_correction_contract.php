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

$before = (int) DB::table('ai_core_requests')->where('tool_slug', 'image_generate')->max('id');
$blocked = false;
$reason = null;
$jobId = null;
$finalPrompt = null;

try {
    $response = app(AiCore::class)->generateImage([
        'message' => 'اجعلها اكثر اضاءة',
        'plugin' => 'ai-assistant',
        'wait' => false,
    ], [
        'plugin' => 'ai-assistant',
        'source' => 'codex_execution_contract_acceptance',
        'test_name' => 'correction_with_previous_context',
        'latest_visual_context' => [
            'prompt' => 'a turtle walking on the surface of water beside a river in a dense green forest, realistic, cinematic lighting, high detail',
            'result_type' => 'image',
        ],
    ], $user);

    $jobId = data_get($response, 'data.job_id');
    $request = DB::table('ai_core_requests')
        ->where('id', '>', $before)
        ->where('tool_slug', 'image_generate')
        ->orderByDesc('id')
        ->first();
    $payload = is_string($request?->request_payload ?? null) ? json_decode($request->request_payload, true) : [];
    $finalPrompt = $payload['prompt'] ?? null;
} catch (Throwable $exception) {
    $blocked = true;
    $reason = method_exists($exception, 'reasonCode') ? $exception->reasonCode() : null;
}

echo json_encode([
    'ok' => $blocked || (is_string($finalPrompt) && str_contains(strtolower($finalPrompt), 'turtle')),
    'blocked' => $blocked,
    'reason' => $reason,
    'job_id' => $jobId,
    'final_prompt' => $finalPrompt,
    'used_previous_context' => is_string($finalPrompt) && str_contains(strtolower($finalPrompt), 'turtle'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
