<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\AiCore\AiCoreAdminController;
use Modules\AiCore\AiCoreSettings;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$permission = 'ai-core.settings.update';
$role = Role::where('name', 'super-admin')->first();
$user = User::query()
    ->when(method_exists(User::class, 'role'), fn ($query) => $query->role('super-admin'))
    ->first();

if (! $user) {
    $user = User::query()->first();
}

if ($user) {
    Auth::login($user);
}

$rendered = '';

if ($user) {
    $rendered = (string) app()->call([app(AiCoreAdminController::class), 'index']);
}

$updateSmoke = 'not_run';

try {
    DB::beginTransaction();
    app(AiCoreSettings::class)->update(['default_timeout' => '61'], $user?->id, 'verification.ai-core.settings');
    DB::rollBack();
    $updateSmoke = 'ok_rolled_back';
} catch (Throwable $exception) {
    DB::rollBack();
    $updateSmoke = 'failed: '.$exception->getMessage();
}

echo json_encode([
    'permission_exists' => Permission::where('name', $permission)->exists(),
    'super_admin_role_linked' => $role ? $role->hasPermissionTo($permission) : false,
    'user_id_checked' => $user?->id,
    'user_has_permission' => $user && method_exists($user, 'hasPermissionTo') ? $user->hasPermissionTo($permission) : false,
    'post_route_exists' => Route::has('admin.plugins.ai-core.settings.update'),
    'render_has_save_button' => str_contains($rendered, 'Save Editable Settings'),
    'render_has_permission_warning' => str_contains($rendered, 'Requires ai-core.settings.update'),
    'update_smoke' => $updateSmoke,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
