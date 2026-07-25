<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo json_encode(
    DB::table('ai_core_settings')
        ->whereIn('key', [
            'planner_enabled',
            'planner_shadow_mode',
            'planner_model',
            'planner_context_window',
            'planner_confidence_threshold',
            'planner_debug_for_super_admin',
        ])
        ->orderBy('key')
        ->get(['key', 'value', 'type', 'editable', 'status'])
        ->map(fn (object $row): array => (array) $row)
        ->all(),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
).PHP_EOL;
