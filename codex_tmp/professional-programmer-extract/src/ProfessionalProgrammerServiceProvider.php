<?php

namespace Modules\ProfessionalProgrammer;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class ProfessionalProgrammerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        require_once __DIR__.'/ProfessionalProgrammerSettings.php';
        require_once __DIR__.'/ProfessionalProgrammerLogMonitor.php';
        require_once __DIR__.'/ProfessionalProgrammerLearningService.php';
        require_once __DIR__.'/ProfessionalProgrammerAiService.php';
        require_once __DIR__.'/ProfessionalProgrammerAdminController.php';
        require_once __DIR__.'/ProfessionalProgrammerController.php';
        require_once __DIR__.'/ProfessionalProgrammerMiddleware.php';

        $this->loadViewsFrom(dirname(__DIR__).'/resources/views', 'professional-programmer');
        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');

        $this->app->make(Kernel::class)->appendMiddlewareToGroup('web', ProfessionalProgrammerMiddleware::class);
    }
}
