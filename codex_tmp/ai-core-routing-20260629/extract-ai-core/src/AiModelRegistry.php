<?php

namespace Modules\AiCore;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiModelRegistry
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        if (! Schema::hasTable('ai_core_models')) {
            return [];
        }

        return DB::table('ai_core_models')->orderBy('slug')->get()->map(fn (object $row): array => $this->normalize((array) $row))->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $slug): ?array
    {
        if (! Schema::hasTable('ai_core_models')) {
            return null;
        }

        $row = DB::table('ai_core_models')->where('slug', $slug)->first();

        return $row ? $this->normalize((array) $row) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function forTool(string $toolSlug): ?array
    {
        $map = [
            'general_chat' => 'general_chat',
            'coding_chat' => 'coding_chat',
            'image_generate' => 'image_generation',
            'image_fast_generate' => 'fast_image_generation',
            'vision_analyze' => 'vision_analysis',
            'rag_index' => 'embedding',
            'rag_search' => 'embedding',
            'artwork_index' => 'artwork_similarity',
            'artwork_search' => 'artwork_similarity',
        ];

        return isset($map[$toolSlug]) ? $this->find($map[$toolSlug]) : null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalize(array $row): array
    {
        foreach (['allowed_plugins', 'allowed_roles', 'context_policy', 'dataset_policy'] as $key) {
            $row[$key] = is_string($row[$key] ?? null) ? (json_decode($row[$key], true) ?: []) : ($row[$key] ?? []);
        }

        return $row;
    }
}
