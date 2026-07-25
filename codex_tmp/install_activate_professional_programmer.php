<?php

use App\Platform\Core\Services\PluginManager;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$manager = app(PluginManager::class);
$plugin = $manager->install(base_path('modules/professional-programmer'));
$plugin = $manager->activate($plugin);

echo "plugin_status=".$plugin->status."\n";
