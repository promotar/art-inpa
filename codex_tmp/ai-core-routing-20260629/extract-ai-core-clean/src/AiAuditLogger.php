<?php

namespace Modules\AiCore;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AiAuditLogger
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     */
    public function startRequest(string $toolSlug, string $endpoint, array $payload, array $context = [], ?Authenticatable $user = null): ?int
    {
        if (! Schema::hasTable('ai_core_requests')) {
            return null;
        }

        return (int) DB::table('ai_core_requests')->insertGetId([
            'request_uuid' => (string) Str::uuid(),
            'user_id' => $user?->getAuthIdentifier(),
            'plugin_slug' => $context['plugin'] ?? $context['plugin_slug'] ?? null,
            'tool_slug' => $toolSlug,
            'model_slug' => $context['model_slug'] ?? null,
            'dataset_slug' => $context['dataset_slug'] ?? null,
            'endpoint' => $endpoint,
            'method' => (string) ($context['method'] ?? 'POST'),
            'status' => 'started',
            'request_payload' => json_encode($this->sanitizePayload($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'context' => json_encode($this->sanitizePayload($context), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'started_at' => now(),
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 1000),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param array<string, mixed> $response
     */
    public function finishRequest(?int $requestId, bool $ok, array $response, ?int $statusCode = null, ?string $error = null): void
    {
        if (! $requestId || ! Schema::hasTable('ai_core_requests')) {
            return;
        }

        $request = DB::table('ai_core_requests')->where('id', $requestId)->first();
        $duration = $request?->started_at ? abs((int) now()->diffInMilliseconds($request->started_at, false)) : null;

        DB::table('ai_core_requests')->where('id', $requestId)->update([
            'status' => $ok ? 'completed' : 'failed',
            'finished_at' => now(),
            'duration_ms' => $duration,
            'error_message' => $error,
            'updated_at' => now(),
        ]);

        if (Schema::hasTable('ai_core_responses')) {
            DB::table('ai_core_responses')->insert([
                'request_id' => $requestId,
                'status_code' => $statusCode,
                'ok' => $ok,
                'response_payload' => json_encode($this->sanitizePayload($response), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'tokens_used' => data_get($response, 'data.tokens_used'),
                'cost_units' => data_get($response, 'data.cost_units'),
                'error_message' => $error,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function event(string $eventType, array $metadata = [], ?Authenticatable $user = null, ?string $toolSlug = null, ?bool $allowed = null, ?string $reason = null): void
    {
        if (! Schema::hasTable('ai_core_audit_logs')) {
            return;
        }

        DB::table('ai_core_audit_logs')->insert([
            'event_type' => $eventType,
            'actor_user_id' => $user?->getAuthIdentifier(),
            'plugin_slug' => $metadata['plugin'] ?? $metadata['plugin_slug'] ?? null,
            'tool_slug' => $toolSlug,
            'target_type' => $metadata['target_type'] ?? null,
            'target_id' => $metadata['target_id'] ?? null,
            'allowed' => $allowed,
            'reason' => $reason,
            'metadata' => json_encode($this->sanitizePayload($metadata), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 1000),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            $lower = strtolower((string) $key);
            if (str_contains($lower, 'api_key') || str_contains($lower, 'token') || str_contains($lower, 'password') || str_contains($lower, 'secret')) {
                $payload[$key] = '[redacted]';
                continue;
            }

            if (is_string($value) && strlen($value) > 2000 && preg_match('/^[A-Za-z0-9+\/=]+$/', $value)) {
                $payload[$key] = '[large-base64-redacted]';
                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->sanitizePayload($value);
            }
        }

        return $payload;
    }
}
