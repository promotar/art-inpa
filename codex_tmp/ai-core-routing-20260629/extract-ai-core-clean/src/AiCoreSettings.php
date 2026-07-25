<?php

namespace Modules\AiCore;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiCoreSettings
{
    /**
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return Cache::remember('ai_core.settings.values', 300, function (): array {
            $defaults = [
                'gateway_base_url' => env('AI_GATEWAY_BASE_URL', 'http://10.10.0.40:8080'),
                'gateway_api_key' => env('AI_GATEWAY_API_KEY', ''),
                'default_timeout' => (int) env('AI_DEFAULT_TIMEOUT', 60),
                'image_timeout' => (int) env('AI_IMAGE_TIMEOUT', 300),
                'fallback_classifier_enabled' => filter_var(env('AI_FALLBACK_CLASSIFIER_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
                'confidence_threshold' => (float) env('AI_INTENT_CONFIDENCE_THRESHOLD', 0.75),
            ];

            if (! Schema::hasTable('ai_core_settings')) {
                return $defaults;
            }

            $rows = DB::table('ai_core_settings')->where('status', 'active')->get(['key', 'value', 'type']);
            foreach ($rows as $row) {
                $defaults[(string) $row->key] = $this->cast((string) $row->value, (string) $row->type);
            }

            return $defaults;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function publicValues(): array
    {
        $values = $this->values();
        if (array_key_exists('gateway_api_key', $values)) {
            $values['gateway_api_key'] = filled((string) $values['gateway_api_key']) ? 'configured' : 'missing';
        }

        return $values;
    }

    public function gatewayBaseUrl(): string
    {
        return rtrim((string) ($this->values()['gateway_base_url'] ?? ''), '/');
    }

    public function gatewayApiKey(): string
    {
        return (string) ($this->values()['gateway_api_key'] ?? '');
    }

    public function timeout(?string $toolSlug = null): int
    {
        $values = $this->values();
        if ($toolSlug !== null && (str_contains($toolSlug, 'image') || in_array($toolSlug, ['vision_analyze', 'artwork_search'], true))) {
            return max(20, (int) ($values['image_timeout'] ?? 300));
        }

        return max(10, (int) ($values['default_timeout'] ?? 60));
    }

    private function cast(string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
            'integer' => (int) $value,
            'decimal', 'float' => (float) $value,
            'json' => json_decode($value, true) ?: [],
            default => $value,
        };
    }
}
