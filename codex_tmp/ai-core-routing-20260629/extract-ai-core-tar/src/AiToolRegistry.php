<?php

namespace Modules\AiCore;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiToolRegistry
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        if (! Schema::hasTable('ai_core_tools')) {
            return [];
        }

        return DB::table('ai_core_tools')->orderBy('slug')->get()->map(fn (object $row): array => $this->normalize((array) $row))->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $slug): ?array
    {
        if (! Schema::hasTable('ai_core_tools')) {
            return $this->fallbackTool($slug);
        }

        $row = DB::table('ai_core_tools')->where('slug', $slug)->first();

        return $row ? $this->normalize((array) $row) : $this->fallbackTool($slug);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalize(array $row): array
    {
        foreach (['input_schema', 'output_schema'] as $key) {
            $row[$key] = is_string($row[$key] ?? null) ? (json_decode($row[$key], true) ?: []) : ($row[$key] ?? []);
        }

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fallbackTool(string $slug): ?array
    {
        $endpoints = [
            'general_chat' => '/v1/general/chat',
            'coding_chat' => '/v1/coding/chat',
            'image_generate' => '/v1/images/generate',
            'image_fast_generate' => '/v1/images/fast-generate',
            'image_job_poll' => '/v1/images/jobs/{job_id}',
            'vision_analyze' => '/v1/vision/analyze',
            'rag_search' => '/v1/rag/search',
            'rag_index' => '/v1/rag/index',
            'artwork_search' => '/v1/artwork/search',
            'artwork_index' => '/v1/artwork/index',
            'intent_classify' => '/v1/router/intent',
            'training_job_create' => '/v1/coding/training/jobs',
            'training_job_status' => '/v1/coding/training/status',
        ];

        return isset($endpoints[$slug])
            ? ['slug' => $slug, 'endpoint' => $endpoints[$slug], 'enabled' => true, 'risk_level' => 'unknown', 'requires_approval' => false]
            : null;
    }
}
