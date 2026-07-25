<?php

namespace Modules\AiAssistant;

use App\Platform\Core\Services\SettingsRepository;
use Illuminate\Support\Facades\Route;

class AiAssistantSettings
{
    public function __construct(private readonly SettingsRepository $settings)
    {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function values(): array
    {
        $values = $this->settings->values();

        return [
            'enabled' => $this->bool($this->setting($values, 'enabled', true)),
            'show_frontend' => $this->bool($this->setting($values, 'show_frontend', true)),
            'show_admin' => $this->bool($this->setting($values, 'show_admin', true)),
            'gateway_url' => rtrim((string) $this->setting($values, 'gateway_url', 'http://10.10.0.40:8080'), '/'),
            'api_key' => (string) $this->setting($values, 'api_key', ''),
            'chat_endpoint' => '/'.ltrim((string) $this->setting($values, 'chat_endpoint', '/v1/general/chat'), '/'),
            'title' => (string) $this->setting($values, 'title', 'Support'),
            'powered_by' => (string) $this->setting($values, 'powered_by', 'Powered by Art INPA AI'),
            'greeting' => (string) $this->setting($values, 'greeting', 'Hi!'),
            'headline' => (string) $this->setting($values, 'headline', 'I can help you with platform support.'),
            'launcher_label' => (string) $this->setting($values, 'launcher_label', 'Contact Us'),
            'placeholder' => (string) $this->setting($values, 'placeholder', 'Type a message...'),
            'full_page_title' => (string) $this->setting($values, 'full_page_title', 'AI Assistant'),
            'system_prompt' => (string) $this->setting($values, 'system_prompt', 'You are Art INPA AI Assistant. Be concise, helpful, and production-support oriented.'),
            'max_tokens' => max(64, min(4096, (int) $this->setting($values, 'max_tokens', 512))),
            'temperature' => max(0, min(2, (float) $this->setting($values, 'temperature', 0.3))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publicConfig(): array
    {
        $values = $this->values();
        unset($values['api_key'], $values['system_prompt']);

        $values['chat_url'] = Route::has('ai-assistant.chat') ? route('ai-assistant.chat') : url('/ai-assistant/chat');
        $values['message_url'] = Route::has('ai-assistant.message') ? route('ai-assistant.message') : url('/ai-assistant/message');

        return $values;
    }

    private function bool(mixed $value): bool
    {
        $value = $this->decodeScalar($value);

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function setting(array $values, string $key, mixed $default): mixed
    {
        return $this->decodeScalar($values['ai_assistant.'.$key] ?? $default);
    }

    private function decodeScalar(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = $this->stripBom(trim($value));

        if ($trimmed === '') {
            return '';
        }

        $decoded = json_decode($trimmed, true);

        if (json_last_error() === JSON_ERROR_NONE && ! is_array($decoded)) {
            return is_string($decoded) ? $this->stripBom(trim($decoded)) : $decoded;
        }

        return $trimmed;
    }

    private function stripBom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF|\x{FEFF}/u', '', $value) ?? $value;
    }
}
