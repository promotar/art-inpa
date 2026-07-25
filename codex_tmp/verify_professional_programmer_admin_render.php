<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();

$user = User::query()->first();
if (! $user) {
    echo "admin_render_status=no_user\n";
    exit(1);
}

Auth::login($user);

$request = Request::create('/admin/plugins/professional-programmer', 'GET');
$request->setLaravelSession(app('session.store'));
$response = app(HttpKernel::class)->handle($request);

echo 'admin_render_status='.$response->getStatusCode().PHP_EOL;
echo 'admin_render_has_title='.(str_contains((string) $response->getContent(), 'المبرمج المحترف') ? 'yes' : 'no').PHP_EOL;
