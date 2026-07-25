<?php

namespace Modules\AiCore;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AiCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach (glob(__DIR__.'/*.php') ?: [] as $file) {
            if ($file !== __FILE__) {
                require_once $file;
            }
        }

        $this->app->singleton(AiCoreSettings::class);
        $this->app->singleton(AiModelRegistry::class);
        $this->app->singleton(AiToolRegistry::class);
        $this->app->singleton(AiPermissionService::class);
        $this->app->singleton(AiUsageLimiter::class);
        $this->app->singleton(AiAuditLogger::class);
        $this->app->singleton(AiCore::class);
        $this->app->singleton(AiGatewayClient::class);
        $this->app->singleton(AiIntentRouter::class);
        $this->app->singleton(AiConversationBridge::class);
        $this->app->singleton(AiRagService::class);
        $this->app->singleton(AiImageJobService::class);
        $this->app->singleton(AiVisionService::class);
        $this->app->singleton(AiArtworkSimilarityService::class);
        $this->app->singleton(AiTrainingProfileService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ai-core');

        $routeFile = __DIR__.'/../routes/admin.php';
        if (is_file($routeFile)) {
            Route::middleware(['web', 'auth', 'staff'])
                ->prefix('admin/plugins/ai-core')
                ->name('admin.plugins.ai-core.')
                ->group($routeFile);
        }
    }
}
