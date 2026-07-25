<?php

namespace Modules\AiAssistant;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class AiAssistantServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        require_once __DIR__.'/AiAssistantSettings.php';
        require_once __DIR__.'/AiGatewayClient.php';
        require_once __DIR__.'/AiAssistantController.php';
        require_once __DIR__.'/AiAssistantAdminController.php';
        require_once __DIR__.'/AiAssistantWidgetMiddleware.php';

        $this->loadViewsFrom(dirname(__DIR__).'/resources/views', 'ai-assistant');
        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');

        $this->app->make(Kernel::class)->appendMiddlewareToGroup('web', AiAssistantWidgetMiddleware::class);
    }
}
