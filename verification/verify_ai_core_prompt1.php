<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AiCore\AiCoreAdminController;

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

$tools = Schema::hasTable('ai_core_tools')
    ? DB::table('ai_core_tools')->get(['slug', 'endpoint', 'plugin_owner', 'risk_level', 'requires_approval', 'enabled'])
    : collect();

$sensitiveTools = ['coding_chat', 'training_job_create', 'rag_index', 'artwork_index'];
$requirements = [
    'ai-assistant' => [
        'tools' => ['general_chat', 'intent_classify', 'image_generate', 'image_fast_generate', 'image_job_poll', 'vision_analyze', 'rag_search', 'artwork_search'],
        'permissions' => ['ai-core.tools.execute'],
        'datasets' => ['assistant_public_knowledge'],
    ],
    'professional-programmer' => [
        'tools' => ['coding_chat', 'training_job_create', 'training_job_status', 'rag_index', 'rag_search'],
        'permissions' => ['professional-programmer.chat', 'professional-programmer.manage'],
        'datasets' => ['programmer_laravel_knowledge'],
    ],
];
$toolRows = Schema::hasTable('ai_core_tools') ? DB::table('ai_core_tools')->get(['slug', 'enabled'])->keyBy('slug') : collect();
$permissionRows = Schema::hasTable('permissions') ? DB::table('permissions')->pluck('name')->flip() : collect();
$datasetRows = Schema::hasTable('ai_core_datasets') ? DB::table('ai_core_datasets')->pluck('slug')->flip() : collect();
$compatibility = collect($requirements)->map(function (array $requirement) use ($toolRows, $permissionRows, $datasetRows): array {
    $missingTools = collect($requirement['tools'])
        ->filter(fn (string $tool): bool => ! $toolRows->has($tool) || ! (bool) ($toolRows[$tool]->enabled ?? false))
        ->values()
        ->all();
    $missingPermissions = collect($requirement['permissions'])
        ->reject(fn (string $permission): bool => $permissionRows->has($permission))
        ->values()
        ->all();
    $missingDatasets = collect($requirement['datasets'])
        ->reject(fn (string $dataset): bool => $datasetRows->has($dataset))
        ->values()
        ->all();

    return [
        'missing_tools' => $missingTools,
        'missing_permissions' => $missingPermissions,
        'missing_datasets' => $missingDatasets,
        'status' => $missingTools === [] && $missingPermissions === [] && $missingDatasets === [] ? 'ok' : 'warning',
    ];
})->all();

echo json_encode([
    'render' => [
        'has_plugin_settings_overview' => str_contains($rendered, 'Plugin Settings Registry / Overview'),
        'has_ai_core_settings_editor' => str_contains($rendered, 'AI Core Settings'),
        'has_compatibility_check' => str_contains($rendered, 'Plugin Compatibility Check'),
        'has_health_panel' => str_contains($rendered, 'Gateway Status')
            && str_contains($rendered, 'Ollama')
            && str_contains($rendered, 'ComfyUI')
            && str_contains($rendered, 'Qdrant')
            && str_contains($rendered, 'Vision Worker')
            && str_contains($rendered, 'Embedding Worker'),
        'has_dataset_view_error' => str_contains($rendered, 'View Error'),
        'has_tool_owner_column' => str_contains($rendered, '<th>Owner</th>'),
        'does_not_render_platform_settings_inputs' => ! str_contains($rendered, 'platform_settings['),
        'does_not_print_api_key_env_name' => ! str_contains($rendered, 'AI_GATEWAY_API_KEY'),
    ],
    'registry' => [
        'tool_count' => $tools->count(),
        'all_tools_have_plugin_owner' => $tools->every(fn ($tool): bool => filled((string) ($tool->plugin_owner ?? ''))),
        'sensitive_tools_have_risk_and_approval' => $tools
            ->whereIn('slug', $sensitiveTools)
            ->every(fn ($tool): bool => in_array((string) $tool->risk_level, ['medium', 'high'], true) && (bool) $tool->requires_approval),
        'datasets_table_exists' => Schema::hasTable('ai_core_datasets'),
        'usage_limits_table_exists' => Schema::hasTable('ai_core_usage_limits'),
        'models_runtime_fields_exist' => Schema::hasColumn('ai_core_models', 'runtime_model')
            && Schema::hasColumn('ai_core_models', 'runtime_backend')
            && Schema::hasColumn('ai_core_models', 'verification_status'),
    ],
    'compatibility' => $compatibility,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
