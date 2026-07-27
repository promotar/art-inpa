<?php

use App\Http\Middleware\EnforceRoutePermission;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsurePlatformInstalled;
use App\Http\Middleware\EnsureRegisteredRoute;
use App\Http\Middleware\EnsureStaffUser;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Installation\RuntimeEnvironment;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

RuntimeEnvironment::load();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(EnsurePlatformInstalled::class);
        $middleware->trustProxies(
            at: ['127.0.0.1', '::1', '10.10.0.2', '192.168.1.195'],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX
        );

        $middleware->alias([
            'staff' => EnsureStaffUser::class,
            'super-admin' => EnsureSuperAdmin::class,
            'permission' => EnsurePermission::class,
            'registered-route' => EnsureRegisteredRoute::class,
        ]);

        $middleware->web(append: [
            EnsureRegisteredRoute::class,
            EnforceRoutePermission::class,
        ]);

        $middleware->api(append: [EnforceRoutePermission::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
