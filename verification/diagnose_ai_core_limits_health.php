<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AiCore\AiCoreSettings;
use Modules\AiCore\AiPermissionService;
use Modules\AiCore\AiServerSyncService;
use Modules\AiCore\AiToolReadinessService;
use Modules\AiCore\AiUsageLimiter;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$permissions = app(AiPermissionService::class);
$limiter = app(AiUsageLimiter::class);
$settings = app(AiCoreSettings::class);

$user = null;
try {
    $user = User::query()
        ->whereHas('roles', fn ($query) => $query->whereIn('name', ['super-admin', 'super_admin']))
        ->first();
} catch (Throwable) {
    $user = null;
}
$user = $user ?: User::query()->first();

$toolsToCheck = ['generate_image', 'image_generate', 'fast_generate_image', 'image_fast_generate'];
$contexts = [
    'ai-assistant' => ['plugin' => 'ai-assistant'],
    'ai_assistant' => ['plugin' => 'ai_assistant'],
];

$decisions = [];
foreach ($contexts as $label => $context) {
    foreach ($toolsToCheck as $tool) {
        $decisions[$label][$tool] = method_exists($limiter, 'decision')
            ? $limiter->decision($tool, $user, $context)
            : ['allowed' => $limiter->canUse($tool, $user, $context)];
    }
}

$limitRows = Schema::hasTable('ai_core_usage_limits')
    ? DB::table('ai_core_usage_limits')
        ->whereIn('tool_slug', $toolsToCheck)
        ->orderBy('tool_slug')
        ->orderBy('role_slug')
        ->orderBy('plugin_slug')
        ->orderBy('plan_slug')
        ->get()
        ->map(fn (object $row) => (array) $row)
        ->all()
    : [];

$duplicates = [];
if (Schema::hasTable('ai_core_usage_limits')) {
    $duplicates = DB::table('ai_core_usage_limits')
        ->selectRaw('tool_slug, role_slug, plugin_slug, plan_slug, COUNT(*) as duplicate_count')
        ->groupBy('tool_slug', 'role_slug', 'plugin_slug', 'plan_slug')
        ->havingRaw('COUNT(*) > 1')
        ->get()
        ->map(fn (object $row) => (array) $row)
        ->all();
}

$rawHealth = null;
try {
    $rawHealth = app(Modules\AiCore\AiGatewayClient::class)->health();
} catch (Throwable $exception) {
    $rawHealth = ['ok' => false, 'error' => $exception->getMessage()];
}

$latestSnapshot = app(AiServerSyncService::class)->latestSnapshot();
$readiness = [];
foreach (['image_generate', 'image_fast_generate', 'rag_search', 'vision_analyze', 'general_chat', 'coding_chat'] as $tool) {
    $readiness[$tool] = app(AiToolReadinessService::class)->check($tool);
}

echo json_encode([
    'gateway_configured' => [
        'base_url' => $settings->gatewayBaseUrl(),
        'api_key' => $settings->gatewayApiKey() !== '' ? 'configured' : 'missing',
    ],
    'user' => [
        'id' => $user?->getAuthIdentifier(),
        'roles' => $permissions->roles($user),
    ],
    'usage_decisions' => $decisions,
    'image_limit_rows' => $limitRows,
    'duplicate_usage_rows' => $duplicates,
    'raw_health' => $rawHealth,
    'latest_snapshot' => $latestSnapshot,
    'tool_readiness' => $readiness,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
