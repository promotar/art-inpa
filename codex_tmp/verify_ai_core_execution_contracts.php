<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\AiCore\AiCore;
use Modules\AiCore\AiGatewayClient;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::query()
    ->whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))
    ->first();

$core = app(AiCore::class);
$gateway = app(AiGatewayClient::class);
$results = [];

$imageRequestCount = fn (): int => DB::table('ai_core_requests')
    ->whereIn('tool_slug', ['image_generate', 'image_fast_generate'])
    ->count();

$latestContractAudit = fn (): ?object => DB::table('ai_core_audit_logs')
    ->whereIn('event_type', ['execution_blocked_by_contract', 'execution_contract.passed'])
    ->orderByDesc('id')
    ->first();

$blockedCase = function (string $name, string $message, array $context = []) use ($core, $user, $imageRequestCount, $latestContractAudit): array {
    $before = $imageRequestCount();
    $blocked = false;
    $reason = null;
    $error = null;

    try {
        $core->generateImage([
            'message' => $message,
            'plugin' => 'ai-assistant',
            'wait' => false,
        ], array_merge([
            'plugin' => 'ai-assistant',
            'source' => 'codex_execution_contract_acceptance',
            'test_name' => $name,
        ], $context), $user);
    } catch (Throwable $exception) {
        $blocked = true;
        $error = $exception->getMessage();
        $reason = method_exists($exception, 'reasonCode') ? $exception->reasonCode() : null;
    }

    $audit = $latestContractAudit();

    return [
        'name' => $name,
        'blocked' => $blocked,
        'reason' => $reason,
        'image_requests_before' => $before,
        'image_requests_after' => $imageRequestCount(),
        'no_image_request_created' => $imageRequestCount() === $before,
        'audit_id' => $audit?->id,
        'audit_event' => $audit?->event_type,
        'audit_reason' => $audit?->reason,
        'error_excerpt' => is_string($error) ? mb_substr($error, 0, 220) : null,
    ];
};

$results[] = $blockedCase('vague_image_design_request', 'بدي اصمم صورة');
$results[] = $blockedCase('instructional_image_design_question', 'كيف أقدر أصمم صورة احترافية؟');
$results[] = $blockedCase('complaint_without_previous_context', 'الصورة غلط ومش هيك');

$beforeComplete = $imageRequestCount();
$completeResponse = $core->generateImage([
    'message' => 'اعمل صورة سلحفاة تمشي على الماء بجانب النهر في الغابة',
    'plugin' => 'ai-assistant',
    'wait' => false,
    'width' => 1024,
    'height' => 1024,
    'steps' => 25,
], [
    'plugin' => 'ai-assistant',
    'source' => 'codex_execution_contract_acceptance',
    'test_name' => 'complete_image_request',
], $user);
$jobId = (string) data_get($completeResponse, 'data.job_id');
$completeAudit = $latestContractAudit();

$poll = null;
$imageUrls = [];
if ($jobId !== '') {
    for ($i = 0; $i < 45; $i++) {
        sleep(3);
        $poll = $core->pollImageJob($jobId, [
            'plugin' => 'ai-assistant',
            'source' => 'codex_execution_contract_acceptance',
            'test_name' => 'complete_image_request',
        ], $user);
        $imageUrls = data_get($poll, 'data.images', data_get($poll, 'data.image_urls', []));
        if ((string) data_get($poll, 'data.status') === 'completed' && is_array($imageUrls) && $imageUrls !== []) {
            break;
        }
    }
}

$results[] = [
    'name' => 'complete_image_request',
    'blocked' => false,
    'image_requests_before' => $beforeComplete,
    'image_requests_after' => $imageRequestCount(),
    'image_request_created' => $imageRequestCount() > $beforeComplete,
    'job_id' => $jobId,
    'status' => data_get($poll, 'data.status'),
    'image_urls' => $imageUrls,
    'audit_id' => $completeAudit?->id,
    'audit_event' => $completeAudit?->event_type,
];

$disabledBlocked = false;
$disabledReason = null;
try {
    $gateway->executeTool('not_registered_tool', [], ['plugin' => 'ai-assistant'], $user);
} catch (Throwable $exception) {
    $disabledBlocked = true;
    $disabledReason = method_exists($exception, 'reasonCode') ? $exception->reasonCode() : null;
}
$results[] = [
    'name' => 'disabled_or_unknown_tool_blocked',
    'blocked' => $disabledBlocked,
    'reason' => $disabledReason,
];

$limitBlocked = false;
$limitReason = null;
DB::beginTransaction();
try {
    DB::table('ai_core_usage_limits')->insert([
        'tool_slug' => 'image_fast_generate',
        'role_slug' => 'super-admin',
        'plugin_slug' => 'ai-assistant',
        'plan_slug' => 'contract-test-plan',
        'daily_limit' => 0,
        'monthly_limit' => 0,
        'cooldown_seconds' => 0,
        'enabled' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    try {
        $core->fastGenerateImage([
            'message' => 'اعمل صورة سيارة سباق خارقة في شارع',
            'plugin' => 'ai-assistant',
            'wait' => false,
        ], [
            'plugin' => 'ai-assistant',
            'plan' => 'contract-test-plan',
            'source' => 'codex_execution_contract_acceptance',
            'test_name' => 'limit_exceeded_remains_blocked',
        ], $user);
    } catch (Throwable $exception) {
        $limitBlocked = true;
        $limitReason = method_exists($exception, 'reasonCode') ? $exception->reasonCode() : null;
    }
} finally {
    DB::rollBack();
}
$results[] = [
    'name' => 'exceeded_limit_remains_blocked',
    'blocked' => $limitBlocked,
    'reason' => $limitReason,
];

echo json_encode([
    'ok' => collect($results)->every(function (array $result): bool {
        return match ($result['name']) {
            'vague_image_design_request',
            'instructional_image_design_question',
            'complaint_without_previous_context' => ($result['blocked'] ?? false) === true && ($result['no_image_request_created'] ?? false) === true,
            'complete_image_request' => ($result['image_request_created'] ?? false) === true && ($result['job_id'] ?? '') !== '' && ($result['status'] ?? '') === 'completed' && ($result['image_urls'] ?? []) !== [],
            'disabled_or_unknown_tool_blocked' => ($result['blocked'] ?? false) === true && ($result['reason'] ?? '') === 'tool_disabled',
            'exceeded_limit_remains_blocked' => ($result['blocked'] ?? false) === true && ($result['reason'] ?? '') === 'limit_exceeded',
            default => false,
        };
    }),
    'results' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
