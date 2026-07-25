<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\AiCore\AiCore;
use Modules\AiCore\AiIntentRouter;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::query()
    ->whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))
    ->first();

$tests = [
    [
        'name' => 'turtle_water_river_forest',
        'message' => 'اعمل صورة سلحفاة تمشي على الماء بجانب النهر في الغابة',
        'required' => ['turtle', 'water', 'river', 'forest'],
    ],
    [
        'name' => 'supercar_racing_street',
        'message' => 'اعطني صورة سيارة سباق خارقة في شارع',
        'required' => ['supercar', 'racing', 'street'],
    ],
    [
        'name' => 'dancer_concert_stage_music',
        'message' => 'صمم صورة راقصة ترقص في حفل غنائي',
        'required' => ['dancer', 'concert', 'stage', 'music'],
    ],
    [
        'name' => 'three_children_public_park',
        'message' => 'ولد صورة 3 أطفال يلعبون في حديقة عامة',
        'required' => ['three', 'children', 'playing', 'public', 'park'],
    ],
];

$core = app(AiCore::class);
$router = app(AiIntentRouter::class);
$results = [];

foreach ($tests as $test) {
    $intent = $router->classify($test['message'], ['plugin' => 'ai-assistant']);
    $response = $core->generateImage([
        'message' => $test['message'],
        'wait' => false,
        'width' => 1024,
        'height' => 1024,
        'steps' => 25,
        'plugin' => 'ai-assistant',
    ], [
        'plugin' => 'ai-assistant',
        'source' => 'codex_ai_core_prompt_acceptance',
        'test_name' => $test['name'],
    ], $user);

    $jobId = (string) data_get($response, 'data.job_id');
    $request = DB::table('ai_core_requests')
        ->where('tool_slug', 'image_generate')
        ->where('endpoint', '/v1/images/generate')
        ->latest('id')
        ->first();

    $payload = is_string($request?->request_payload ?? null)
        ? json_decode($request->request_payload, true)
        : [];
    $context = is_string($request?->context ?? null)
        ? json_decode($request->context, true)
        : [];
    $finalPrompt = (string) ($payload['prompt'] ?? '');
    $lowerPrompt = strtolower($finalPrompt);

    $missing = array_values(array_filter(
        $test['required'],
        fn (string $keyword): bool => ! str_contains($lowerPrompt, strtolower($keyword)),
    ));

    $poll = null;
    $imageUrls = [];
    for ($i = 0; $i < 45; $i++) {
        sleep(3);
        $poll = $core->pollImageJob($jobId, [
            'plugin' => 'ai-assistant',
            'source' => 'codex_ai_core_prompt_acceptance',
            'test_name' => $test['name'],
        ], $user);

        $imageUrls = data_get($poll, 'data.images', data_get($poll, 'data.image_urls', []));
        if ((string) data_get($poll, 'data.status') === 'completed' && is_array($imageUrls) && $imageUrls !== []) {
            break;
        }
    }

    $audit = DB::table('ai_core_audit_logs')
        ->where('event_type', 'image.prompt.normalized')
        ->where('tool_slug', 'image_generate')
        ->latest('id')
        ->first(['id', 'metadata']);

    $results[] = [
        'name' => $test['name'],
        'intent' => $intent,
        'ai_core_request_id' => $request?->id,
        'audit_id' => $audit?->id,
        'job_id' => $jobId,
        'comfyui_prompt_id' => data_get($poll, 'data.comfyui_job_id') ?? data_get($poll, 'data.result.job_id'),
        'original_prompt' => data_get($context, 'original_prompt'),
        'final_prompt_sent' => $finalPrompt,
        'payload_keys' => array_keys(is_array($payload) ? $payload : []),
        'missing_required_keywords' => $missing,
        'status' => data_get($poll, 'data.status'),
        'image_urls' => $imageUrls,
    ];
}

echo json_encode([
    'ok' => collect($results)->every(fn (array $result): bool => $result['missing_required_keywords'] === [] && $result['status'] === 'completed' && $result['image_urls'] !== []),
    'results' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
