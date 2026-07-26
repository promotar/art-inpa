<?php

namespace Modules\ArtInpaAdminProTheme;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ArtInpaAdminProThemeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer(['layouts.app', 'components.page-builder-focus-layout'], function (): void {
            $css = $this->stylesheet();

            if ($css === null) {
                return;
            }

            view()->startPush('styles');
            echo '<style data-plugin-admin-style="admin-theme">'.PHP_EOL.$css.PHP_EOL.'</style>';
            view()->stopPush();
        });
    }

    private function stylesheet(): ?string
    {
        $path = __DIR__.'/../resources/css/admin-theme.css';

        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        return (string) file_get_contents($path);
    }
}
