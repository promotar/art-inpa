<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$console = $app->make(Illuminate\Contracts\Console\Kernel::class);
$console->bootstrap();

$user = User::query()
    ->whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))
    ->first();

auth()->login($user);

$request = Request::create('/admin/plugins/ai-core', 'GET');
$request->setUserResolver(fn () => $user);
$response = $app->make(Kernel::class)->handle($request);
$content = (string) $response->getContent();

echo json_encode([
    'status' => $response->getStatusCode(),
    'contains_ai_core_heading' => str_contains($content, '<h1>AI Core</h1>'),
    'contains_tool_readiness' => str_contains($content, 'Tool Readiness'),
    'contains_gateway_status' => str_contains($content, 'Gateway Status'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
