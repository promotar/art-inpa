<?php

namespace Modules\AiCore;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiTrainingProfileService
{
    public function __construct(private readonly AiGatewayClient $gateway)
    {
        //
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function createJob(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->gateway->trainingJobCreate($payload, $context, $user);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function status(array $context = [], ?Authenticatable $user = null): array
    {
        return $this->gateway->trainingJobStatus($context, $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function getProfile(string $slug): array
    {
        if (! Schema::hasTable('ai_core_training_profiles')) {
            return [
                'slug' => $slug,
                'status' => 'unavailable',
                'reason' => 'ai_core_training_profiles_table_missing',
            ];
        }

        $row = DB::table('ai_core_training_profiles')->where('slug', $slug)->first();
        if (! $row) {
            return [
                'slug' => $slug,
                'status' => 'missing',
                'reason' => 'training_profile_not_registered',
            ];
        }

        $profile = (array) $row;
        foreach (['policy', 'evaluation_rules'] as $key) {
            $profile[$key] = is_string($profile[$key] ?? null)
                ? (json_decode((string) $profile[$key], true) ?: [])
                : ($profile[$key] ?? []);
        }

        return $profile;
    }
}
