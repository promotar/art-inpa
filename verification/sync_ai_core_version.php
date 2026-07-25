<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

if (! Schema::hasTable('plugins')) {
    echo "plugins_table_missing\n";
    exit(0);
}

$updated = DB::table('plugins')
    ->where('slug', 'ai-core')
    ->update([
        'version' => '1.0.6',
        'updated_at' => now(),
    ]);

echo 'plugin_version_synced='.$updated.PHP_EOL;
