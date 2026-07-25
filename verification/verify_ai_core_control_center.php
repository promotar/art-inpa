<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\AiCore\AiCoreAdminController;
use Modules\AiCore\AiServerSyncService;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::query()
    ->when(method_exists(User::class, 'role'), fn ($query) => $query->role('super-admin'))
    ->first() ?: User::query()->first();

if ($user) {
    Auth::login($user);
}

$rendered = $user ? (string) app()->call([app(AiCoreAdminController::class), 'index']) : '';
$finalEndpoints = [
    '/v1/router/intent',
    '/v1/general/chat',
    '/v1/coding/chat',
    '/v1/images/generate',
    '/v1/images/fast-generate',
    '/v1/images/jobs/{job_id}',
    '/v1/vision/analyze',
    '/v1/text/embed',
    '/v1/rag/index',
    '/v1/rag/search',
    '/v1/artwork/index',
    '/v1/artwork/search',
    '/v1/coding/training/status',
    '/v1/coding/training/jobs',
    '/v1/coding/training/jobs/{job_id}',
];

$dbEndpoints = Schema::hasTable('ai_core_tools')
    ? DB::table('ai_core_tools')->pluck('endpoint')->all()
    : [];

$syncResult = null;
try {
    $syncResult = app(AiServerSyncService::class)->sync($user);
} catch (Throwable $exception) {
    $syncResult = ['ok' => false, 'error' => $exception->getMessage()];
}

echo json_encode([
    'routes' => [
        'index' => Route::has('admin.plugins.ai-core.index'),
        'settings_update' => Route::has('admin.plugins.ai-core.settings.update'),
        'sync' => Route::has('admin.plugins.ai-core.sync'),
        'usage_limits_update' => Route::has('admin.plugins.ai-core.usage-limits.update'),
        'dataset_index' => Route::has('admin.plugins.ai-core.datasets.index'),
    ],
    'columns' => [
        'models_runtime_model' => Schema::hasColumn('ai_core_models', 'runtime_model'),
        'models_verification_status' => Schema::hasColumn('ai_core_models', 'verification_status'),
        'tools_model_slug' => Schema::hasColumn('ai_core_tools', 'model_slug'),
        'tools_allowed_roles' => Schema::hasColumn('ai_core_tools', 'allowed_roles'),
        'datasets_qdrant_collection' => Schema::hasColumn('ai_core_datasets', 'qdrant_collection'),
        'limits_plan_slug' => Schema::hasColumn('ai_core_usage_limits', 'plan_slug'),
        'limits_monthly_limit' => Schema::hasColumn('ai_core_usage_limits', 'monthly_limit'),
        'limits_cooldown_seconds' => Schema::hasColumn('ai_core_usage_limits', 'cooldown_seconds'),
    ],
    'render' => [
        'has_health_panel' => str_contains($rendered, 'Health Check'),
        'has_sync_button' => str_contains($rendered, 'Sync From AI Server'),
        'has_usage_limits' => str_contains($rendered, 'Usage Limits'),
        'has_dataset_actions' => str_contains($rendered, 'Index Now') && str_contains($rendered, 'Clear Index'),
        'api_key_not_printed' => ! str_contains($rendered, 'AI_GATEWAY_API_KEY'),
    ],
    'registry' => [
        'tool_count' => Schema::hasTable('ai_core_tools') ? DB::table('ai_core_tools')->count() : 0,
        'model_count' => Schema::hasTable('ai_core_models') ? DB::table('ai_core_models')->count() : 0,
        'usage_limit_count' => Schema::hasTable('ai_core_usage_limits') ? DB::table('ai_core_usage_limits')->count() : 0,
        'endpoints_are_final' => empty(array_diff($dbEndpoints, $finalEndpoints)),
        'bad_endpoints' => array_values(array_diff($dbEndpoints, $finalEndpoints)),
        'sensitive_tools_have_risk' => DB::table('ai_core_tools')
            ->whereIn('slug', ['rag_index', 'artwork_index', 'training_job_create', 'coding_chat'])
            ->where(function ($query): void {
                $query->whereNull('risk_level')->orWhere('risk_level', '')->orWhere('requires_approval', false);
            })
            ->count() === 0,
    ],
    'sync' => [
        'ok' => $syncResult['ok'] ?? false,
        'error' => $syncResult['error'] ?? null,
        'has_health_endpoint' => ($syncResult['health_endpoint'] ?? null) === '/health',
        'has_models_endpoint' => ($syncResult['models_endpoint'] ?? null) === '/models',
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
