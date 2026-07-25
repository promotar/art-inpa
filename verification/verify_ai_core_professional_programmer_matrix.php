<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\AiCore\AiPermissionService;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tools = [
    'coding_chat' => ['risk' => 'high', 'approval' => true],
    'training_job_create' => ['risk' => 'high', 'approval' => true],
    'training_job_status' => ['risk' => 'medium', 'approval' => false],
    'training_job_poll' => ['risk' => 'medium', 'approval' => false],
    'rag_search' => ['risk' => 'medium', 'approval' => false],
];

$user = null;
try {
    $user = User::query()
        ->whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))
        ->first();
} catch (Throwable) {
    $user = null;
}
$user = $user ?: User::query()->first();

$permissions = app(AiPermissionService::class);
$roles = $permissions->roles($user);
$toolRows = Schema::hasTable('ai_core_tools')
    ? DB::table('ai_core_tools')->whereIn('slug', array_keys($tools))->get()->keyBy('slug')
    : collect();

$matrixRows = Schema::hasTable('ai_core_tool_permissions')
    ? DB::table('ai_core_tool_permissions')
        ->where('plugin_slug', 'professional-programmer')
        ->whereIn('tool_slug', array_keys($tools))
        ->whereIn('role_slug', ['super-admin', 'super_admin'])
        ->get()
    : collect();

$authorization = [];
foreach (array_keys($tools) as $tool) {
    $authorization[$tool] = $permissions->authorizeTool($tool, $user, ['plugin' => 'professional-programmer']);
}

$dataset = Schema::hasTable('ai_core_datasets')
    ? DB::table('ai_core_datasets')->where('slug', 'programmer_laravel_knowledge')->first()
    : null;

$allowedTools = $dataset && is_string($dataset->allowed_tools ?? null)
    ? (json_decode($dataset->allowed_tools, true) ?: [])
    : [];
$allowedRoles = $dataset && is_string($dataset->allowed_roles ?? null)
    ? (json_decode($dataset->allowed_roles, true) ?: [])
    : [];

echo json_encode([
    'user' => [
        'id' => $user?->getAuthIdentifier(),
        'roles' => $roles,
        'has_super_admin_role' => in_array('super-admin', $roles, true),
    ],
    'tools' => collect($tools)->mapWithKeys(function (array $expected, string $tool) use ($toolRows): array {
        $row = $toolRows->get($tool);

        return [$tool => [
            'exists' => (bool) $row,
            'enabled' => $row ? (bool) $row->enabled : false,
            'plugin_owner' => $row->plugin_owner ?? null,
            'risk_level' => $row->risk_level ?? null,
            'requires_approval' => $row ? (bool) $row->requires_approval : null,
            'audit_required' => $row ? (bool) ($row->audit_required ?? false) : false,
            'risk_matches' => $row ? (string) $row->risk_level === $expected['risk'] : false,
            'approval_matches' => $row ? (bool) $row->requires_approval === $expected['approval'] : false,
        ]];
    })->all(),
    'permission_matrix' => [
        'super_admin_rows' => $matrixRows->count(),
        'training_job_poll_exists' => $matrixRows
            ->where('tool_slug', 'training_job_poll')
            ->where('allowed', true)
            ->isNotEmpty(),
    ],
    'authorization' => $authorization,
    'dataset' => [
        'exists' => (bool) $dataset,
        'owner_plugin' => $dataset->owner_plugin ?? null,
        'super_admin_allowed' => in_array('super-admin', $allowedRoles, true) || in_array('super_admin', $allowedRoles, true),
        'tools_allowed' => empty(array_diff(array_keys($tools), $allowedTools)),
        'indexing_status' => $dataset->indexing_status ?? null,
    ],
    'audit' => [
        'audit_logs_table_exists' => Schema::hasTable('ai_core_audit_logs'),
        'gateway_records_permission_checked' => file_exists(base_path('modules/ai-core/src/AiGatewayClient.php'))
            && str_contains((string) file_get_contents(base_path('modules/ai-core/src/AiGatewayClient.php')), "permission.checked"),
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
