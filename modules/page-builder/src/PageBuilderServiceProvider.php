<?php

namespace Modules\PageBuilder;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class PageBuilderServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::addNamespace('page-builder', dirname(__DIR__).'/resources/views');
    }
}
