<?php

$base = '/var/www/store.z4rank.com/laravel';

$routes = $base.'/routes/web.php';
$routesContent = file_get_contents($routes);

if (! str_contains($routesContent, 'App\\Http\\Controllers\\Ai\\AiChatController')) {
    $routesContent = str_replace(
        "use App\\Http\\Controllers\\Admin\\UserController;\n",
        "use App\\Http\\Controllers\\Admin\\UserController;\nuse App\\Http\\Controllers\\Ai\\AiChatController;\n",
        $routesContent
    );
}

if (! str_contains($routesContent, "->name('ai.message')")) {
    $needle = "Route::get('/account', function () {\n";
    $insert = "Route::post('/ai/message', [AiChatController::class, 'message'])\n    ->middleware('throttle:60,1')\n    ->name('ai.message');\n\n";
    $routesContent = str_replace($needle, $insert.$needle, $routesContent);
}

file_put_contents($routes, $routesContent);

$registry = $base.'/config/platform_registry.php';
$registryContent = file_get_contents($registry);

if (! str_contains($registryContent, "'ai.message'")) {
    $registryContent = str_replace(
        "        'profile.manage' => ['description' => 'Manage authenticated user profile', 'status' => 'active'],\n",
        "        'profile.manage' => ['description' => 'Manage authenticated user profile', 'status' => 'active'],\n".
        "        'ai.intent.route' => ['description' => 'Route AI messages by intent, permission, usage, and data-access policy', 'status' => 'active'],\n".
        "        'ai.data.tools.execute' => ['description' => 'Execute permission-aware read-only AI data tools', 'status' => 'active'],\n",
        $registryContent
    );

    $registryContent = str_replace(
        "        'front.account' => ['uri' => 'account', 'methods' => ['GET', 'HEAD'], 'description' => 'Authenticated user account', 'status' => 'active'],\n",
        "        'front.account' => ['uri' => 'account', 'methods' => ['GET', 'HEAD'], 'description' => 'Authenticated user account', 'status' => 'active'],\n".
        "        'ai.message' => ['uri' => 'ai/message', 'methods' => ['POST'], 'description' => 'Laravel AI intent routing endpoint', 'status' => 'active'],\n",
        $registryContent
    );
}

file_put_contents($registry, $registryContent);

echo "routes_registry_patched\n";
