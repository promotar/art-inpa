<?php

namespace Modules\ProfessionalProgrammer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Throwable;

class ProfessionalProgrammerLearningVerificationService
{
    public function __construct(
        private readonly ProfessionalProgrammerSettings $settings,
        private readonly ProfessionalProgrammerIncidentAnalyzer $analyzer,
        private readonly ProfessionalProgrammerAiGateway $gateway,
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function status(?object $user = null): array
    {
        $latestJob = Schema::hasTable('professional_programmer_training_jobs')
            ? DB::table('professional_programmer_training_jobs')->latest('id')->first()
            : null;

        $gatewayStatus = $this->gatewayStatus($user);
        $latestResponse = $latestJob?->response ? json_decode((string) $latestJob->response, true) : [];

        return [
            'training_endpoint_reachable' => (bool) ($gatewayStatus['training_endpoint_reachable'] ?? false),
            'learning_verified' => (bool) ($latestJob?->learning_verified ?? false),
            'job_status' => $latestJob->status ?? 'not_started',
            'active_model_version' => $gatewayStatus['active_model_version'] ?? data_get($latestResponse, 'active_model_version'),
            'candidate_model_version' => $latestJob->candidate_model_version ?? ($gatewayStatus['candidate_model_version'] ?? null),
            'model_version_before' => $latestJob->model_version_before ?? null,
            'model_version_after' => $latestJob->model_version_after ?? null,
            'before_score' => $latestJob?->before_score !== null ? (float) $latestJob->before_score : null,
            'after_score' => $latestJob?->after_score !== null ? (float) $latestJob->after_score : null,
            'improvement_percent' => $latestJob?->improvement_percent !== null ? (float) $latestJob->improvement_percent : null,
            'generic_answer_count' => $latestJob?->generic_answer_count !== null ? (int) $latestJob->generic_answer_count : null,
            'regression_found' => $latestJob?->regression_found === null ? null : (bool) $latestJob->regression_found,
            'promoted' => (bool) ($latestJob?->promoted ?? false),
            'samples_approved' => Schema::hasTable('professional_programmer_training_samples')
                ? DB::table('professional_programmer_training_samples')->where('status', 'approved')->count()
                : 0,
            'samples_sent' => (int) ($latestJob->samples_sent ?? 0),
            'latest_job' => $latestJob,
            'gateway_error' => $gatewayStatus['error'] ?? null,
        ];
    }

    public function storeApprovedSample(int $approvalId): ?int
    {
        if (! Schema::hasTable('professional_programmer_training_samples')
            || ! Schema::hasTable('professional_programmer_repair_approvals')) {
            return null;
        }

        $approval = DB::table('professional_programmer_repair_approvals')->where('id', $approvalId)->first();
        if (! $approval || ! $approval->incident_id) {
            return null;
        }

        foreach (['proposed_plan', 'risk_summary', 'expected_impact', 'rollback_plan'] as $field) {
            if (trim((string) ($approval->{$field} ?? '')) === '') {
                return null;
            }
        }

        $diagnosis = $this->analyzer->analyze((int) $approval->incident_id);
        if (! $diagnosis) {
            return null;
        }

        $repairOutcome = [
            'requested_action' => $approval->requested_action ?? null,
            'proposed_plan' => $approval->proposed_plan ?? null,
            'risk_summary' => $approval->risk_summary ?? null,
            'expected_impact' => $approval->expected_impact ?? null,
            'rollback_plan' => $approval->rollback_plan ?? null,
            'approval_status' => $approval->status ?? null,
        ];
        $sourceHash = sha1(json_encode([
            'incident_id' => $approval->incident_id,
            'approval_id' => $approval->id,
            'diagnosis' => $diagnosis,
            'repair_outcome' => $repairOutcome,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: (string) $approval->id);

        DB::table('professional_programmer_training_samples')->updateOrInsert(
            ['source_hash' => $sourceHash],
            [
                'incident_id' => $approval->incident_id,
                'repair_approval_id' => $approval->id,
                'approved_by' => $approval->user_id ?? null,
                'status' => 'approved',
                'diagnosis' => json_encode($this->trainingDiagnosis($diagnosis), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'repair_outcome' => json_encode($repairOutcome, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'metadata' => json_encode([
                    'policy' => 'admin-approved diagnosis and repair outcome only; raw logs are not training data',
                    'golden_set_excluded' => true,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'approved_at' => $approval->approved_at ?? now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return (int) DB::table('professional_programmer_training_samples')->where('source_hash', $sourceHash)->value('id');
    }

    /**
     * @return array<string, mixed>
     */
    public function runVerification(?int $userId = null): array
    {
        if (! Schema::hasTable('professional_programmer_training_jobs')
            || ! Schema::hasTable('professional_programmer_training_samples')) {
            return ['ok' => false, 'reason' => 'training_verification_tables_missing'];
        }

        $samples = DB::table('professional_programmer_training_samples')
            ->where('status', 'approved')
            ->orderBy('id')
            ->limit(50)
            ->get();

        if ($samples->isEmpty()) {
            return ['ok' => false, 'reason' => 'no_approved_training_samples'];
        }

        $requestId = 'pp-laravel-'.now()->format('YmdHis').'-'.substr(sha1((string) microtime(true)), 0, 8);
        $jobId = DB::table('professional_programmer_training_jobs')->insertGetId([
            'user_id' => $userId,
            'request_id' => $requestId,
            'status' => 'running',
            'samples_sent' => $samples->count(),
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $user = $this->resolveUser($userId);
            $response = $this->postTrainingJob([
                'request_id' => $requestId,
                'plugin' => 'professional_programmer',
                'samples' => $samples->map(fn (object $sample): array => [
                    'sample_id' => (string) $sample->id,
                    'source_hash' => (string) $sample->source_hash,
                    'incident_id' => $sample->incident_id,
                    'approval_id' => $sample->repair_approval_id,
                    'diagnosis' => json_decode((string) $sample->diagnosis, true) ?: [],
                    'repair_outcome' => json_decode((string) $sample->repair_outcome, true) ?: [],
                    'approved_at' => $sample->approved_at,
                ])->values()->all(),
            ], $user);

            $data = (array) ($response['data'] ?? []);
            $verified = $this->learningVerified($data);

            DB::table('professional_programmer_training_jobs')->where('id', $jobId)->update([
                'ai_job_id' => $data['job_id'] ?? null,
                'status' => (string) ($data['status'] ?? ($verified ? 'completed' : 'blocked')),
                'training_endpoint_reachable' => true,
                'learning_verified' => $verified,
                'model_version_before' => $data['model_version_before'] ?? null,
                'model_version_after' => $data['model_version_after'] ?? null,
                'candidate_model_version' => $data['candidate_model_version'] ?? null,
                'before_score' => $data['before_score'] ?? null,
                'after_score' => $data['after_score'] ?? null,
                'improvement_percent' => $data['improvement_percent'] ?? null,
                'generic_answer_count' => $data['generic_answer_count'] ?? null,
                'regression_found' => $data['regression_found'] ?? null,
                'golden_set_used_for_training' => (bool) ($data['golden_set_used_for_training'] ?? true),
                'promoted' => (bool) ($data['promoted'] ?? false),
                'verification_rules' => json_encode($data['verification_rules'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'response' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('professional_programmer_training_samples')
                ->whereIn('id', $samples->pluck('id')->all())
                ->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);

            return ['ok' => $verified, 'job_id' => $jobId, 'ai_job_id' => $data['job_id'] ?? null, 'data' => $data];
        } catch (Throwable $exception) {
            DB::table('professional_programmer_training_jobs')->where('id', $jobId)->update([
                'status' => 'failed',
                'training_endpoint_reachable' => false,
                'error' => $exception->getMessage(),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            return ['ok' => false, 'job_id' => $jobId, 'error' => $exception->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $diagnosis
     * @return array<string, mixed>
     */
    private function trainingDiagnosis(array $diagnosis): array
    {
        return [
            'schema_version' => $diagnosis['schema_version'] ?? null,
            'original_error' => $diagnosis['original_error'] ?? null,
            'file' => $diagnosis['file'] ?? null,
            'line' => $diagnosis['line'] ?? null,
            'sqlstate' => $diagnosis['sqlstate'] ?? null,
            'database_table' => $diagnosis['database_table'] ?? null,
            'database_column' => $diagnosis['database_column'] ?? null,
            'likely_cause' => $diagnosis['likely_cause'] ?? null,
            'excluded_causes' => $diagnosis['excluded_causes'] ?? [],
            'required_checks' => $diagnosis['required_checks'] ?? [],
            'severity' => $diagnosis['severity'] ?? null,
            'repair_type' => $diagnosis['repair_type'] ?? 'unknown',
            'needs_migration' => (bool) ($diagnosis['needs_migration'] ?? false),
            'needs_code_change' => (bool) ($diagnosis['needs_code_change'] ?? false),
            'needs_data_cleanup' => (bool) ($diagnosis['needs_data_cleanup'] ?? false),
            'backup_and_approval_required' => (bool) ($diagnosis['backup_and_approval_required'] ?? true),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function learningVerified(array $data): bool
    {
        return ($data['samples_received'] ?? 0) > 0
            && ($data['status'] ?? null) === 'completed'
            && filled($data['model_version_before'] ?? null)
            && filled($data['model_version_after'] ?? null)
            && ! (bool) ($data['golden_set_used_for_training'] ?? true)
            && (bool) data_get($data, 'evaluation_before.executed')
            && (bool) data_get($data, 'evaluation_after.executed')
            && (float) ($data['after_score'] ?? 0) > (float) ($data['before_score'] ?? 0)
            && (int) ($data['generic_answer_count'] ?? 1) === 0
            && (bool) ($data['regression_found'] ?? true) === false
            && (bool) ($data['promoted'] ?? false) === true;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function postTrainingJob(array $payload, ?object $user = null): array
    {
        if (class_exists(\Modules\AiCore\AiCore::class)) {
            return $this->gateway->trainingJobCreate($payload, $user);
        }

        throw new \RuntimeException('AI Core is required for Professional Programmer training jobs.');
    }

    /**
     * @return array<string, mixed>
     */
    private function gatewayStatus(?object $user = null): array
    {
        try {
            if (class_exists(\Modules\AiCore\AiCore::class)) {
                $response = $this->gateway->trainingJobStatus($user);
                $data = is_array($response) ? (array) ($response['data'] ?? []) : [];

                return array_merge(['training_endpoint_reachable' => true], $data);
            }

            return ['training_endpoint_reachable' => false, 'error' => 'ai_core_required'];
        } catch (Throwable $exception) {
            return ['training_endpoint_reachable' => false, 'error' => $exception->getMessage()];
        }
    }

    private function resolveUser(?int $userId): ?Authenticatable
    {
        if (! $userId) {
            return null;
        }

        $model = (string) config('auth.providers.users.model', \App\Models\User::class);
        if (! class_exists($model)) {
            return null;
        }

        $user = $model::query()->find($userId);

        return $user instanceof Authenticatable ? $user : null;
    }
}
