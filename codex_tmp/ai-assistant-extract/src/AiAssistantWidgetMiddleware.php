<?php

namespace Modules\AiAssistant;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AiAssistantWidgetMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldInject($request, $response)) {
            return $response;
        }

        try {
            $settings = app(AiAssistantSettings::class);
            $config = $settings->publicConfig();

            if (! $this->enabledForRequest($request, $config)) {
                return $response;
            }

            $content = (string) $response->getContent();

            if (stripos($content, 'data-ai-assistant-widget') !== false || stripos($content, '</body>') === false) {
                return $response;
            }

            $widget = view('ai-assistant::widget', [
                'config' => $config,
                'context' => $this->context($request),
            ])->render();

            $response->setContent(str_ireplace('</body>', $widget.'</body>', $content));
        } catch (Throwable) {
            return $response;
        }

        return $response;
    }

    private function shouldInject(Request $request, Response $response): bool
    {
        if (! $response->isSuccessful() || $request->expectsJson() || $request->ajax()) {
            return false;
        }

        if ($request->routeIs('ai-assistant.chat') || $request->routeIs('ai-assistant.message')) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        return $contentType === '' || str_contains(strtolower($contentType), 'text/html');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function enabledForRequest(Request $request, array $config): bool
    {
        if (($config['enabled'] ?? false) !== true) {
            return false;
        }

        return $this->context($request) === 'admin'
            ? ($config['show_admin'] ?? true) === true
            : ($config['show_frontend'] ?? true) === true;
    }

    private function context(Request $request): string
    {
        return str_starts_with(trim($request->path(), '/'), 'admin') ? 'admin' : 'frontend';
    }
}
