<?php

namespace Modules\ProfessionalProgrammer;

use App\Services\Ai\AiGatewayClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProfessionalProgrammerAiService
{
    public function __construct(
        private readonly ProfessionalProgrammerSettings $settings,
        private readonly AiGatewayClient $gateway,
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function answer(string $message, ?int $incidentId, ?object $user): array
    {
        $settings = $this->settings->values();
        config(['ai.gateway_base_url' => $settings['gateway_url']]);

        $context = [
            'plugin' => 'professional_programmer',
            'user' => $user ? ['id' => $user->id ?? null, 'email' => $user->email ?? null] : null,
            'incident' => $incidentId ? $this->incidentContext($incidentId) : null,
            'learning_status' => $this->learningStatus(),
            'recent_incidents' => $this->recentIncidents(),
            'repair_policy' => [
                'requires_admin_approval' => (bool) $settings['repair_requires_admin_approval'],
                'no_direct_code_changes_from_web_chat' => true,
            ],
        ];

        try {
            $response = $this->gateway->chatCoding([
                'message' => $message,
                'system' => $settings['system_prompt'],
                'plugin' => 'professional_programmer',
                'context' => $context,
            ]);

            return [
                'ok' => true,
                'message' => $this->extractMessage($response),
                'data' => $response['data'] ?? null,
                'endpoint_used' => '/v1/coding/chat',
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'خدمة المبرمج المحترف غير متاحة حاليًا. تم تسجيل المشكلة ويمكن إعادة المحاولة بعد قليل.',
                'error' => $exception->getMessage(),
                'endpoint_used' => '/v1/coding/chat',
            ];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function incidentContext(int $incidentId): ?array
    {
        if (! Schema::hasTable('professional_programmer_incidents')) {
            return null;
        }

        $incident = DB::table('professional_programmer_incidents')->where('id', $incidentId)->first();
        if (! $incident) {
            return null;
        }

        return [
            'id' => $incident->id,
            'severity' => $incident->severity,
            'level' => $incident->level,
            'source' => $incident->source,
            'title' => $incident->title,
            'message' => $incident->message,
            'occurrences' => $incident->occurrences,
            'last_seen_at' => $incident->last_seen_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function learningStatus(): array
    {
        if (! Schema::hasTable('professional_programmer_learning_sources')) {
            return [];
        }

        return [
            'indexed_sources' => DB::table('professional_programmer_learning_sources')->count(),
            'code_sources' => DB::table('professional_programmer_learning_sources')->where('source_type', 'code')->count(),
            'last_run' => Schema::hasTable('professional_programmer_learning_runs')
                ? DB::table('professional_programmer_learning_runs')->latest('id')->first()
                : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentIncidents(): array
    {
        if (! Schema::hasTable('professional_programmer_incidents')) {
            return [];
        }

        return DB::table('professional_programmer_incidents')
            ->whereIn('status', ['open', 'acknowledged', 'awaiting_fix'])
            ->latest('last_seen_at')
            ->limit(5)
            ->get(['id', 'severity', 'level', 'source', 'title', 'occurrences', 'last_seen_at'])
            ->map(fn (object $incident): array => (array) $incident)
            ->all();
    }

    /**
     * @param array<string, mixed> $response
     */
    private function extractMessage(array $response): string
    {
        foreach ([
            data_get($response, 'data.message'),
            data_get($response, 'data.response'),
            data_get($response, 'message'),
            data_get($response, 'response'),
            data_get($response, 'result'),
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return 'تم تحليل الطلب. راجع التفاصيل في لوحة المبرمج المحترف.';
    }
}
