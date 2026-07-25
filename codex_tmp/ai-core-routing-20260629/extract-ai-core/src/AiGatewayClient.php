<?php

namespace Modules\AiCore;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class AiGatewayClient
{
    public function __construct(
        private readonly AiCoreSettings $settings,
        private readonly AiToolRegistry $tools,
        private readonly AiModelRegistry $models,
        private readonly AiPermissionService $permissions,
        private readonly AiUsageLimiter $usage,
        private readonly AiAuditLogger $audit,
    ) {
        //
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function executeTool(string $toolSlug, array $payload = [], array $context = [], ?Authenticatable $user = null): array
    {
        $tool = $this->tools->find($toolSlug);
        if (! $tool || ! (bool) ($tool['enabled'] ?? false)) {
            throw new RuntimeException('AI Core tool is not registered or disabled: '.$toolSlug);
        }

        $model = $this->models->forTool($toolSlug);
        if ($model && ! (bool) ($model['enabled'] ?? false)) {
            throw new RuntimeException('AI Core model is disabled for tool: '.$toolSlug);
        }

        $context['model_slug'] = $context['model_slug'] ?? ($model['slug'] ?? null);
        $context['method'] = $context['method'] ?? 'POST';

        $permission = $this->permissions->authorizeTool($toolSlug, $user, $context);
        $this->audit->event('permission.checked', $context, $user, $toolSlug, $permission['allowed'], $permission['reason']);
        if (! $permission['allowed']) {
            throw new RuntimeException('AI Core permission denied: '.$permission['reason']);
        }

        if (! $this->usage->canUse($toolSlug, $user, $context)) {
            $this->audit->event('usage.denied', $context, $user, $toolSlug, false, 'daily_limit_exceeded');
            throw new RuntimeException('AI Core usage limit exceeded for tool: '.$toolSlug);
        }

        $endpoint = $this->resolveEndpoint((string) $tool['endpoint'], $payload, $context);
        $requestId = $this->audit->startRequest($toolSlug, $endpoint, $payload, $context, $user);

        try {
            $response = strtoupper((string) $context['method']) === 'GET'
                ? $this->get($endpoint, $toolSlug)
                : $this->post($endpoint, $payload, $toolSlug);

            $this->audit->finishRequest($requestId, true, $response, 200);
            $this->storeToolResult($requestId, $toolSlug, $response, $context, $user);

            return $response;
        } catch (\Throwable $exception) {
            $this->audit->finishRequest($requestId, false, ['error' => $exception->getMessage()], null, $exception->getMessage());
            $this->audit->event('request.failed', array_merge($context, ['error' => $exception->getMessage()]), $user, $toolSlug, false, $exception->getMessage());
            throw $exception;
        }
    }

    public function chatGeneral(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->executeTool('general_chat', $payload, array_merge(['plugin' => $payload['plugin'] ?? 'unknown'], $context), $user);
    }

    public function chatCoding(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->executeTool('coding_chat', $payload, array_merge(['plugin' => $payload['plugin'] ?? 'professional-programmer'], $context), $user);
    }

    public function classifyIntent(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->executeTool('intent_classify', $payload, array_merge(['plugin' => $payload['plugin'] ?? 'unknown'], $context), $user);
    }

    public function generateImage(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        $payload['wait'] = $payload['wait'] ?? false;

        return $this->executeTool('image_generate', $payload, array_merge(['plugin' => $payload['plugin'] ?? 'unknown'], $context), $user);
    }

    public function generateFastImage(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        $payload['wait'] = $payload['wait'] ?? false;

        return $this->executeTool('image_fast_generate', $payload, array_merge(['plugin' => $payload['plugin'] ?? 'unknown'], $context), $user);
    }

    public function imageJobStatus(string $jobId, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->executeTool('image_job_poll', ['job_id' => $jobId], array_merge(['method' => 'GET', 'plugin' => 'ai-assistant'], $context), $user);
    }

    public function analyzeVision(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->executeTool('vision_analyze', $payload, array_merge(['plugin' => $payload['plugin'] ?? 'unknown'], $context), $user);
    }

    public function searchArtwork(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->executeTool('artwork_search', $payload, array_merge(['plugin' => $payload['plugin'] ?? 'unknown'], $context), $user);
    }

    public function ragSearch(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->executeTool('rag_search', $payload, array_merge(['plugin' => $payload['plugin'] ?? 'unknown'], $context), $user);
    }

    public function ragIndex(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->executeTool('rag_index', $payload, array_merge(['plugin' => $payload['plugin'] ?? 'unknown'], $context), $user);
    }

    public function trainingJobCreate(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->executeTool('training_job_create', $payload, array_merge(['plugin' => 'professional-programmer'], $context), $user);
    }

    public function trainingJobStatus(array $context = [], ?Authenticatable $user = null): array
    {
        return $this->executeTool('training_job_status', [], array_merge(['method' => 'GET', 'plugin' => 'professional-programmer'], $context), $user);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function post(string $endpoint, array $payload, string $toolSlug): array
    {
        $response = $this->baseRequest($toolSlug)->post($this->settings->gatewayBaseUrl().$endpoint, $payload);

        return $this->validatedJson($response, $endpoint);
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $endpoint, string $toolSlug): array
    {
        $response = $this->baseRequest($toolSlug)->get($this->settings->gatewayBaseUrl().$endpoint);

        return $this->validatedJson($response, $endpoint);
    }

    private function baseRequest(string $toolSlug): \Illuminate\Http\Client\PendingRequest
    {
        $baseUrl = $this->settings->gatewayBaseUrl();
        if ($baseUrl === '') {
            throw new RuntimeException('AI Core Gateway base URL is not configured.');
        }

        $apiKey = $this->settings->gatewayApiKey();

        return Http::timeout($this->settings->timeout($toolSlug))
            ->retry(2, 250)
            ->acceptJson()
            ->when($apiKey !== '', fn ($request) => $request->withHeaders(['X-AI-API-KEY' => $apiKey]));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedJson(Response $response, string $endpoint): array
    {
        if (! $response->successful()) {
            throw new RuntimeException('AI Gateway request failed for '.$endpoint.' with HTTP '.$response->status().'.');
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('AI Gateway returned invalid JSON for '.$endpoint.'.');
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     */
    private function resolveEndpoint(string $endpoint, array $payload, array $context): string
    {
        if (str_contains($endpoint, '{job_id}')) {
            $jobId = (string) ($payload['job_id'] ?? $context['job_id'] ?? '');
            if ($jobId === '') {
                throw new RuntimeException('AI Core tool endpoint requires job_id.');
            }

            return str_replace('{job_id}', rawurlencode($jobId), $endpoint);
        }

        return $endpoint;
    }

    /**
     * @param array<string, mixed> $response
     * @param array<string, mixed> $context
     */
    private function storeToolResult(?int $requestId, string $toolSlug, array $response, array $context, ?Authenticatable $user): void
    {
        if (! Schema::hasTable('ai_core_tool_results')) {
            return;
        }

        $resultType = match ($toolSlug) {
            'image_generate', 'image_fast_generate', 'image_job_poll' => 'image',
            'vision_analyze' => 'vision',
            'rag_search' => 'rag',
            'artwork_search' => 'artwork_similarity',
            default => 'message',
        };

        DB::table('ai_core_tool_results')->insert([
            'request_id' => $requestId,
            'conversation_id' => $context['conversation_id'] ?? null,
            'user_id' => $user?->getAuthIdentifier(),
            'tool_slug' => $toolSlug,
            'result_type' => $resultType,
            'status' => 'stored',
            'source_url' => data_get($response, 'data.image_url') ?? data_get($response, 'data.images.0.url'),
            'result_payload' => json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (Schema::hasTable('ai_core_jobs') && in_array($toolSlug, ['image_generate', 'image_fast_generate'], true)) {
            DB::table('ai_core_jobs')->insert([
                'job_uuid' => (string) Str::uuid(),
                'external_job_id' => data_get($response, 'data.job_id'),
                'tool_slug' => $toolSlug,
                'user_id' => $user?->getAuthIdentifier(),
                'plugin_slug' => $context['plugin'] ?? $context['plugin_slug'] ?? null,
                'status' => (string) (data_get($response, 'data.status') ?? 'submitted'),
                'payload' => null,
                'result' => json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
