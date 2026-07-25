<?php

namespace Modules\AiCore;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiCore
{
    public function __construct(
        private readonly AiGatewayClient $gateway,
        private readonly AiIntentRouter $intentRouter,
        private readonly AiAuditLogger $audit,
        private readonly AiPermissionService $permissions,
        private readonly AiTrainingProfileService $trainingProfiles,
    ) {
        //
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function detectIntent(string|array $message, array $context = [], ?Authenticatable $user = null): mixed
    {
        if (is_array($message) && class_exists(\App\Services\Ai\AiIntentRouter::class)) {
            return app(\App\Services\Ai\AiIntentRouter::class)->route($message);
        }

        $context = $this->context($context);

        return $this->intentRouter->classify($message, $context);
    }

    public function canUseIntent(?Authenticatable $user, mixed $intent): bool
    {
        if (class_exists(\App\Services\Ai\AiPermissionChecker::class)) {
            return app(\App\Services\Ai\AiPermissionChecker::class)->canUseIntent($user, $intent);
        }

        return true;
    }

    public function intentDeniedReason(?Authenticatable $user, mixed $intent): string
    {
        if (class_exists(\App\Services\Ai\AiPermissionChecker::class)) {
            return app(\App\Services\Ai\AiPermissionChecker::class)->getDeniedReason($user, $intent);
        }

        return 'This AI feature is not allowed for your account.';
    }

    public function usageAllowed(?Authenticatable $user, mixed $intent): bool
    {
        if (class_exists(\App\Services\Ai\AiUsageLimiter::class)) {
            return app(\App\Services\Ai\AiUsageLimiter::class)->allowed($user, $intent);
        }

        return true;
    }

    public function usageDeniedReason(mixed $intent): string
    {
        if (class_exists(\App\Services\Ai\AiUsageLimiter::class)) {
            return app(\App\Services\Ai\AiUsageLimiter::class)->deniedReason($intent);
        }

        return 'Daily AI usage limit exceeded.';
    }

    public function logUsage(?Authenticatable $user, mixed $intent, string $plugin): void
    {
        if (class_exists(\App\Services\Ai\AiUsageLimiter::class)) {
            app(\App\Services\Ai\AiUsageLimiter::class)->log($user, $intent, $plugin);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function executeSensitiveAction(?Authenticatable $user, mixed $intent, array $data = []): array
    {
        if (! class_exists(\App\Services\Ai\AiActionExecutor::class)) {
            return ['message' => 'Sensitive AI actions are not available.', 'data' => []];
        }

        return app(\App\Services\Ai\AiActionExecutor::class)->execute($user, $intent, $data);
    }

    public function platformDataToolForMessage(string $message): ?string
    {
        if (! class_exists(\App\Services\Ai\AiToolRegistry::class)) {
            return null;
        }

        return app(\App\Services\Ai\AiToolRegistry::class)->toolForMessage($message);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function executePlatformDataTool(string $tool, ?Authenticatable $user, mixed $intent, Request $request, array $input = []): array
    {
        if (! class_exists(\App\Services\Ai\AiDataAccessService::class)) {
            return [
                'ok' => false,
                'authorized' => false,
                'error' => 'Permission-aware platform data tools are not available.',
            ];
        }

        return app(\App\Services\Ai\AiDataAccessService::class)->execute($tool, $user, $intent, $request, $input);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function chat(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->gateway->chatGeneral($this->payload($payload), $this->context($context), $user);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function chatCoding(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->gateway->chatCoding($this->payload($payload), $this->context($context), $user);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function generateImage(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->gateway->generateImage($this->payload($payload), $this->context($context), $user);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function fastGenerateImage(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->gateway->generateFastImage($this->payload($payload), $this->context($context), $user);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function pollImageJob(string $jobId, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->gateway->imageJobStatus($jobId, $this->context($context), $user);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function analyzeImage(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->gateway->analyzeVision($this->payload($payload), $this->context($context), $user);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function searchArtwork(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->gateway->searchArtwork($this->payload($payload), $this->context($context), $user);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function searchRag(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->gateway->ragSearch($this->payload($payload), $this->context($context), $user);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function trainingJobCreate(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        $payload['plugin'] = $payload['plugin'] ?? 'professional-programmer';

        return $this->gateway->trainingJobCreate($payload, $this->context($context + ['plugin' => 'professional-programmer']), $user);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function createTrainingJob(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->trainingJobCreate($payload, $context, $user);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function trainingJobStatus(array $context = [], ?Authenticatable $user = null): array
    {
        return $this->gateway->trainingJobStatus($this->context($context + ['plugin' => 'professional-programmer']), $user);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function getTrainingJobStatus(array $context = [], ?Authenticatable $user = null): array
    {
        return $this->trainingJobStatus($context, $user);
    }

    /**
     * @param array<string, mixed> $context
     * @return array{allowed: bool, reason: string}
     */
    public function checkToolPermission(string $toolSlug, ?Authenticatable $user = null, array $context = []): array
    {
        return $this->permissions->authorizeTool($toolSlug, $user, $this->context($context + ['plugin' => 'professional-programmer']));
    }

    /**
     * @return array<string, mixed>
     */
    public function getTrainingProfile(string $profile = 'professional-programmer'): array
    {
        return $this->trainingProfiles->getProfile($profile);
    }

    public function assertAvailable(): void
    {
        if (! app()->bound(AiGatewayClient::class)) {
            throw new \RuntimeException('AI Core is required and must be active for AI execution.');
        }
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, mixed> $context
     */
    public function logToolResult(string $toolSlug, array $result, array $context = [], ?Authenticatable $user = null): void
    {
        $context = $this->context($context);
        $this->audit->event('tool.result.logged', $context + ['result_summary' => $this->resultSummary($result)], $user, $toolSlug, true);

        if (! Schema::hasTable('ai_core_tool_results')) {
            return;
        }

        DB::table('ai_core_tool_results')->insert([
            'request_id' => $context['request_id'] ?? null,
            'conversation_id' => $context['conversation_id'] ?? null,
            'user_id' => $user?->getAuthIdentifier(),
            'tool_slug' => $toolSlug,
            'result_type' => (string) ($context['result_type'] ?? 'message'),
            'status' => (string) ($context['status'] ?? 'stored'),
            'source_url' => $context['source_url'] ?? data_get($result, 'image_url') ?? data_get($result, 'images.0.url'),
            'result_payload' => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function payload(array $payload): array
    {
        $payload['plugin'] = $payload['plugin'] ?? 'ai-assistant';

        return $payload;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function context(array $context): array
    {
        $context['plugin'] = $context['plugin'] ?? $context['plugin_slug'] ?? 'ai-assistant';

        return $context;
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function resultSummary(array $result): array
    {
        return [
            'ok' => $result['ok'] ?? null,
            'status' => data_get($result, 'data.status') ?? ($result['status'] ?? null),
            'has_data' => isset($result['data']),
            'message' => is_string($result['message'] ?? null) ? mb_substr($result['message'], 0, 300) : null,
            'job_id' => data_get($result, 'data.job_id'),
        ];
    }
}
