<?php

namespace Modules\Blog;

use App\Platform\Core\Services\PluginRuntimeGate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class BlogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->pluginEnabled()) {
            return;
        }

        require_once __DIR__.'/Http/Controllers/BlogController.php';
        require_once __DIR__.'/Http/Controllers/Admin/BlogAdminController.php';
        require_once __DIR__.'/Http/Controllers/Admin/PostController.php';
        require_once __DIR__.'/Http/Controllers/Admin/CategoryController.php';
        require_once __DIR__.'/Models/Post.php';
        require_once __DIR__.'/Models/Category.php';
        require_once __DIR__.'/Models/Tag.php';
        require_once __DIR__.'/Models/Media.php';
        require_once __DIR__.'/Models/Revision.php';
        require_once __DIR__.'/Models/PostMeta.php';

        View::addNamespace('blog', dirname(__DIR__).'/resources/views');
        $this->loadViewsFrom(dirname(__DIR__).'/resources/views', 'blog');
        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
    }

    private function pluginEnabled(): bool
    {
        return $this->app->make(PluginRuntimeGate::class)->allows('blog');
    }
}
