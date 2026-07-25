<?php

namespace Modules\ProfessionalProgrammer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProfessionalProgrammerAiService
{
    public function __construct(
        private readonly ProfessionalProgrammerSettings $settings,
        private readonly ProfessionalProgrammerAiGateway $gateway,
        private readonly ProfessionalProgrammerProductionGuard $guard,
        private readonly ProfessionalProgrammerIncidentAnalyzer $analyzer,
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function answer(string $message, ?int $incidentId, ?object $user): array
    {
        $settings = $this->settings->values();
        $diagnosis = $this->analyzer->analyze($incidentId);

        $context = [
            'plugin' => 'professional-programmer',
            'user' => $user ? ['id' => $user->id ?? null, 'email' => $user->email ?? null] : null,
            'incident' => $incidentId ? $this->incidentContext($incidentId) : null,
            'evidence_based_diagnosis' => $diagnosis,
            'learning_status' => $this->learningStatus(),
            'production_readiness' => $this->guard->readiness(),
            'tool_policies' => $this->toolPolicies(),
            'recent_incidents' => $this->recentIncidents(),
            'repair_policy' => [
                'requires_admin_approval' => (bool) $settings['repair_requires_admin_approval'],
                'requires_fresh_training' => (bool) $settings['require_fresh_training_before_repair'],
                'requires_backup_before_repair' => (bool) $settings['require_backup_before_repair'],
                'requires_written_plan' => (bool) $settings['require_written_plan_before_repair'],
                'no_direct_code_changes_from_web_chat' => true,
                'terminal_write_from_web_chat_allowed' => false,
                'approval_payload_must_include' => ['proposed_plan', 'risk_summary', 'expected_impact', 'rollback_plan'],
            ],
        ];

        try {
            $response = $this->gateway->chatCoding([
                'message' => $message,
                'system' => $this->evidenceSystemPrompt($settings['system_prompt']),
                'plugin' => 'professional-programmer',
                'context' => $context,
            ], $user);

            $assistantMessage = $this->extractMessage($response);

            return [
                'ok' => true,
                'message' => $diagnosis
                    ? 'تم استخراج تشخيص مبني على أدلة السجل. راجع بطاقات Repair Console أدناه قبل أي موافقة.'
                    : $assistantMessage,
                'assistant_text' => $assistantMessage,
                'diagnosis' => $diagnosis,
                'data' => $response['data'] ?? null,
                'endpoint_used' => 'ai-core:codingChat',
            ];
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'message' => 'خدمة المبرمج المحترف غير متاحة حاليًا. تم تسجيل المشكلة ويمكن إعادة المحاولة بعد قليل.',
                'diagnosis' => $diagnosis,
                'error' => $exception->getMessage(),
                'endpoint_used' => 'ai-core:codingChat',
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
     * @return array<int, array<string, mixed>>
     */
    private function toolPolicies(): array
    {
        if (! Schema::hasTable('professional_programmer_tool_policies')) {
            return [];
        }

        return DB::table('professional_programmer_tool_policies')
            ->orderBy('tool_key')
            ->get(['tool_key', 'capability', 'access_level', 'allowed', 'requires_training', 'requires_backup', 'requires_admin_approval', 'execution_surface'])
            ->map(fn (object $policy): array => (array) $policy)
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

    private function evidenceSystemPrompt(string $base): string
    {
        return $base."\n\n".
            "Mandatory evidence-based debugging policy:\n".
            "- Do not give generic debugging advice before using the supplied evidence_based_diagnosis.\n".
            "- Every diagnosis must identify original error text, file/line when available, affected table/column when available, likely cause based on evidence, excluded causes, checks required before repair, severity, repair type, migration/code/data-cleanup need, and backup/approval requirement.\n".
            "- If a SQL log names a table or column, state that table/column as the center of the problem. Do not answer with generic advice like check SQL syntax.\n".
            "- Use Arabic. Be concise. Treat Laravel context as source of truth. Never claim direct write permissions from web chat.";
    }
}
