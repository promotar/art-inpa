<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$apiKey = trim((string) stream_get_contents(STDIN));
if ($apiKey === '') {
    fwrite(STDERR, "missing_api_key\n");
    exit(1);
}

if (! Schema::hasTable('ai_core_settings')) {
    fwrite(STDERR, "ai_core_settings_missing\n");
    exit(1);
}

DB::table('ai_core_settings')->updateOrInsert(
    ['key' => 'gateway_api_key'],
    [
        'value' => $apiKey,
        'type' => 'password',
        'default_value' => '',
        'validation_rules' => json_encode([]),
        'description' => 'AI Gateway API key. Stored only in the database settings system.',
        'category' => 'ai_core',
        'module' => 'ai-core',
        'visibility_level' => 'admin',
        'admin_access_level' => 'super_admin',
        'editable' => true,
        'required' => false,
        'sensitive_flag' => true,
        'public_exposure_allowed' => false,
        'frontend_available' => false,
        'cache_enabled' => true,
        'cache_ttl' => 300,
        'ui_component' => 'password',
        'ui_label' => 'AI Gateway API Key',
        'allowed_values' => null,
        'min_value' => null,
        'max_value' => null,
        'unit' => null,
        'depends_on' => null,
        'restart_required' => false,
        'approval_required' => true,
        'status' => 'active',
        'version' => 1,
        'updated_at' => now(),
        'created_at' => now(),
    ],
);

Cache::forget('ai_core.settings.values');
echo "ai_core_gateway_key_configured\n";
