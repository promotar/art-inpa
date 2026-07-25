<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Modules\ProfessionalProgrammer\ProfessionalProgrammerAiService;
use Modules\ProfessionalProgrammer\ProfessionalProgrammerIncidentAnalyzer;
use Modules\ProfessionalProgrammer\ProfessionalProgrammerLearningVerificationService;
use Modules\ProfessionalProgrammer\ProfessionalProgrammerProductionGuard;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$userModel = (string) config('auth.providers.users.model', App\Models\User::class);
$user = class_exists($userModel) ? $userModel::query()->where('email', 'admin@z4rank.com')->first() : null;
if (! $user && class_exists($userModel)) {
    $user = $userModel::query()->first();
}

$cases = [
    'sql_missing_column' => [
        'severity' => 'high',
        'message' => "Illuminate\\Database\\QueryException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'seo_title' in 'field list' (Connection: mysql, SQL: update `blog_posts` set `seo_title` = test where `id` = 14) in /var/www/store.z4rank.com/laravel/modules/Blog/src/Http/Controllers/Admin/PostController.php:221",
        'expect' => ['repair_type' => 'migration', 'table' => 'blog_posts', 'column' => 'seo_title', 'needs_migration' => true],
    ],
    'sql_missing_table' => [
        'severity' => 'high',
        'message' => "Illuminate\\Database\\QueryException: SQLSTATE[42S02]: Base table or view not found: 1146 Table 'store.professional_programmer_jobs' doesn't exist (Connection: mysql, SQL: select * from `professional_programmer_jobs`) in /var/www/store.z4rank.com/laravel/modules/ProfessionalProgrammer/src/ProfessionalProgrammerLearningService.php:77",
        'expect' => ['repair_type' => 'migration', 'table' => 'professional_programmer_jobs', 'column' => null, 'needs_migration' => true],
    ],
    'duplicate_key' => [
        'severity' => 'medium',
        'message' => "Illuminate\\Database\\QueryException: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'home' for key 'pages_slug_unique' (Connection: mysql, SQL: insert into `pages` (`slug`) values (home)) in /var/www/store.z4rank.com/laravel/app/Http/Controllers/PageController.php:88",
        'expect' => ['repair_type' => 'data_cleanup', 'table' => 'pages', 'column' => 'slug', 'needs_data_cleanup' => true],
    ],
    'permission_denied' => [
        'severity' => 'high',
        'message' => "file_put_contents(/var/www/store.z4rank.com/laravel/storage/framework/cache/data/x): Failed to open stream: Permission denied in /var/www/store.z4rank.com/laravel/app/Services/CacheWriter.php:42",
        'expect' => ['repair_type' => 'code', 'table' => null, 'column' => null, 'needs_code_change' => true],
    ],
    'pure_php_exception' => [
        'severity' => 'medium',
        'message' => "ErrorException: Undefined variable \$post in /var/www/store.z4rank.com/laravel/modules/Blog/resources/views/admin/posts/form.blade.php:55",
        'expect' => ['repair_type' => 'code', 'table' => null, 'column' => null, 'needs_code_change' => true],
    ],
    'malformed_sql_no_table_column' => [
        'severity' => 'medium',
        'message' => "Illuminate\\Database\\QueryException: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax near 'FROM WHERE' at line 1 (Connection: mysql, SQL: select FROM WHERE) in /var/www/store.z4rank.com/laravel/app/Services/ReportService.php:19",
        'expect' => ['repair_type' => 'unknown', 'table' => null, 'column' => null],
    ],
    'migration_needed' => [
        'severity' => 'high',
        'message' => "Illuminate\\Database\\QueryException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'training_endpoint_reachable' in 'field list' (Connection: mysql, SQL: update `professional_programmer_training_jobs` set `training_endpoint_reachable` = 1) in /var/www/store.z4rank.com/laravel/modules/ProfessionalProgrammer/src/ProfessionalProgrammerLearningVerificationService.php:166",
        'expect' => ['repair_type' => 'migration', 'table' => 'professional_programmer_training_jobs', 'column' => 'training_endpoint_reachable', 'needs_migration' => true],
    ],
    'data_cleanup_needed' => [
        'severity' => 'medium',
        'message' => "Illuminate\\Database\\QueryException: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`store`.`orders`, CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)) in /var/www/store.z4rank.com/laravel/app/Services/OrderService.php:91",
        'expect' => ['repair_type' => 'data_cleanup', 'table' => 'orders', 'column' => 'user_id', 'needs_data_cleanup' => true],
    ],
    'code_fix_needed' => [
        'severity' => 'medium',
        'message' => "BadMethodCallException: Call to undefined method App\\Models\\Post::publishNow() in /var/www/store.z4rank.com/laravel/modules/Blog/src/Http/Controllers/Admin/PostController.php:309",
        'expect' => ['repair_type' => 'code', 'table' => null, 'column' => null, 'needs_code_change' => true],
    ],
];

$analyzer = app(ProfessionalProgrammerIncidentAnalyzer::class);
$results = [];
$ids = [];

foreach ($cases as $name => $case) {
    $id = DB::table('professional_programmer_incidents')->insertGetId([
        'fingerprint' => 'codex-ai-core-routing-'.$name.'-'.uniqid(),
        'source' => 'codex_ai_core_routing_acceptance',
        'level' => 'error',
        'severity' => $case['severity'],
        'title' => $name,
        'message' => $case['message'],
        'context' => json_encode(['controlled_test' => true, 'no_real_repair' => true]),
        'occurrences' => 1,
        'first_seen_at' => now(),
        'last_seen_at' => now(),
        'status' => 'open',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $ids[] = $id;
    $diagnosis = $analyzer->analyze($id) ?: [];
    $expect = $case['expect'];
    $checks = [
        'original_error' => trim((string) ($diagnosis['original_error'] ?? '')) !== '',
        'file_line_extracted' => (($diagnosis['file'] ?? null) !== null && ($diagnosis['line'] ?? null) !== null),
        'sqlstate_ok' => str_contains($case['message'], 'SQLSTATE[') ? (($diagnosis['sqlstate'] ?? null) !== null) : (($diagnosis['sqlstate'] ?? null) === null),
        'table_ok' => (($diagnosis['database_table'] ?? null) === $expect['table']),
        'column_ok' => (($diagnosis['database_column'] ?? null) === $expect['column']),
        'repair_type_ok' => (($diagnosis['repair_type'] ?? null) === $expect['repair_type']),
        'probable_cause_evidence_based' => str_starts_with((string) ($diagnosis['likely_cause'] ?? ''), 'من السجل يظهر') || str_starts_with((string) ($diagnosis['likely_cause'] ?? ''), 'insufficient evidence'),
        'excluded_causes_present' => ! empty($diagnosis['excluded_causes'] ?? []),
        'required_checks_present' => ! empty($diagnosis['required_checks'] ?? []),
        'needs_backup' => (($diagnosis['backup_and_approval_required'] ?? false) === true),
        'has_generic_answer_false' => ! str_contains((string) ($diagnosis['likely_cause'] ?? ''), 'قد يكون'),
    ];

    foreach (['needs_migration', 'needs_code_change', 'needs_data_cleanup'] as $flag) {
        if (array_key_exists($flag, $expect)) {
            $checks[$flag.'_ok'] = ((bool) ($diagnosis[$flag] ?? false) === (bool) $expect[$flag]);
        }
    }

    $results[$name] = [
        'pass' => ! in_array(false, $checks, true),
        'checks' => $checks,
        'repair_type' => $diagnosis['repair_type'] ?? null,
        'table' => $diagnosis['database_table'] ?? null,
        'column' => $diagnosis['database_column'] ?? null,
        'sqlstate' => $diagnosis['sqlstate'] ?? null,
    ];
}

if ($ids !== []) {
    DB::table('professional_programmer_incidents')->whereIn('id', $ids)->update([
        'status' => 'suppressed',
        'updated_at' => now(),
    ]);
}

$blocked = app(ProfessionalProgrammerProductionGuard::class)->validateRepairRequest([
    'incident_id' => $ids[0] ?? null,
    'requested_action' => 'controlled dry-run',
]);
$verification = app(ProfessionalProgrammerLearningVerificationService::class)->status($user);
$ai = app(ProfessionalProgrammerAiService::class)->answer(
    'اختبار ربط AI Core: اعطني حالة مختصرة بدون تنفيذ اصلاح.',
    null,
    $user,
);
$directScan = trim(shell_exec("grep -R -n -E '(/v1/coding/chat|/v1/coding/training|Http::|App\\\\Services\\\\Ai\\\\AiGatewayClient|LegacyAiGatewayClient|gateway_url|gateway_api_key|10\\.10\\.0\\.40)' modules/professional-programmer/src modules/professional-programmer/resources modules/professional-programmer/database 2>/dev/null") ?? '');

echo json_encode([
    'test_matrix_pass' => ! in_array(false, array_column($results, 'pass'), true),
    'cases' => $results,
    'approval_gate_blocks_empty_fields' => (($blocked['ok'] ?? true) === false),
    'approval_block_count' => count($blocked['blocked'] ?? []),
    'training_endpoint_reachable' => (bool) ($verification['training_endpoint_reachable'] ?? false),
    'learning_verified' => (bool) ($verification['learning_verified'] ?? false),
    'active_model_version' => $verification['active_model_version'] ?? null,
    'candidate_model_version' => $verification['candidate_model_version'] ?? null,
    'generic_answer_count' => $verification['generic_answer_count'] ?? null,
    'regression_found' => $verification['regression_found'] ?? null,
    'promoted' => (bool) ($verification['promoted'] ?? false),
    'ai_coding_ok' => (bool) ($ai['ok'] ?? false),
    'ai_endpoint_used' => $ai['endpoint_used'] ?? null,
    'no_direct_ai_server_call' => $directScan === '',
    'direct_scan_output' => $directScan,
    'user_for_permission' => $user ? ($user->email ?? ('id:'.$user->getAuthIdentifier())) : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
