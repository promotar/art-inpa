<?php

use App\Models\User;
use Modules\AiCore\AiCore;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::query()
    ->whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))
    ->first();

try {
    $result = app(AiCore::class)->generateImage(
        [
            'prompt' => 'AI Core smoke test: simple blue square icon',
            'wait' => false,
            'plugin' => 'ai-assistant',
        ],
        [
            'plugin' => 'ai-assistant',
            'source' => 'codex_smoke_test',
        ],
        $user,
    );

    echo json_encode([
        'ok' => true,
        'status' => data_get($result, 'data.status'),
        'job_id' => data_get($result, 'data.job_id'),
        'has_data' => isset($result['data']),
        'keys' => array_keys($result),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
} catch (Throwable $exception) {
    echo json_encode([
        'ok' => false,
        'class' => get_class($exception),
        'message' => $exception->getMessage(),
        'reason' => method_exists($exception, 'reasonCode') ? $exception->reasonCode() : null,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
}
