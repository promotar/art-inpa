<?php

namespace Modules\ProfessionalProgrammer;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProfessionalProgrammerMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (! $this->shouldInject($request, $response)) {
            return $response;
        }

        try {
            $settings = app(ProfessionalProgrammerSettings::class)->values();
            if (! $settings['enabled'] || ! $settings['admin_widget_enabled']) {
                return $response;
            }

            app(ProfessionalProgrammerLogMonitor::class)->scanForAdminRequest();

            $html = view('professional-programmer::widget', [
                'alertsUrl' => Route::has('admin.plugins.professional-programmer.alerts') ? route('admin.plugins.professional-programmer.alerts') : url('/admin/plugins/professional-programmer/alerts'),
                'messageUrl' => Route::has('admin.plugins.professional-programmer.message') ? route('admin.plugins.professional-programmer.message') : url('/admin/plugins/professional-programmer/message'),
                'approveUrl' => Route::has('admin.plugins.professional-programmer.approve') ? route('admin.plugins.professional-programmer.approve') : url('/admin/plugins/professional-programmer/approve'),
                'dashboardUrl' => Route::has('admin.plugins.professional-programmer.index') ? route('admin.plugins.professional-programmer.index') : url('/admin/plugins/professional-programmer'),
            ])->render();

            $content = (string) $response->getContent();
            $content = str_contains($content, '</body>')
                ? str_replace('</body>', $html.'</body>', $content)
                : $content.$html;
            $response->setContent($content);
        } catch (Throwable) {
            return $response;
        }

        return $response;
    }

    private function shouldInject(Request $request, mixed $response): bool
    {
        if (! $response instanceof Response || ! Schema::hasTable('professional_programmer_incidents')) {
            return false;
        }

        if (! $request->user() || ! $request->is('admin*') || $request->expectsJson() || $request->ajax()) {
            return false;
        }

        if ($request->is('admin/plugins/professional-programmer/alerts') || $request->is('admin/plugins/professional-programmer/message')) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html') || $contentType === '';
    }
}
