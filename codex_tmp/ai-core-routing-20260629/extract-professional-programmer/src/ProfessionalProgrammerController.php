<?php

namespace Modules\ProfessionalProgrammer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProfessionalProgrammerController extends Controller
{
    public function alerts(Request $request, ProfessionalProgrammerLogMonitor $logs, ProfessionalProgrammerSettings $settings): JsonResponse
    {
        $logs->scanForAdminRequest();
        $values = $settings->values();
        $incidents = array_map(fn (object $incident): array => [
            'id' => $incident->id,
            'severity' => $incident->severity,
            'level' => $incident->level,
            'source' => $incident->source,
            'title' => $incident->title,
            'message' => $incident->message,
            'occurrences' => $incident->occurrences,
            'last_seen_at' => $incident->last_seen_at,
        ], $logs->unresolved((int) $values['max_admin_alerts']));

        return response()->json([
            'ok' => true,
            'open' => $incidents !== [],
            'incidents' => $incidents,
        ]);
    }

    public function message(Request $request, ProfessionalProgrammerAiService $ai): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'incident_id' => ['nullable', 'integer'],
        ]);

        $incidentId = isset($data['incident_id']) ? (int) $data['incident_id'] : null;
        $this->storeMessage($request->user()?->id, $incidentId, 'user', $data['message']);
        $response = $ai->answer($data['message'], $incidentId, $request->user());
        $this->storeMessage($request->user()?->id, $incidentId, 'assistant', (string) ($response['assistant_text'] ?? $response['message']), $response);

        return response()->json($response);
    }

    public function approve(
        Request $request,
        ProfessionalProgrammerProductionGuard $guard,
        ProfessionalProgrammerLearningVerificationService $learningVerification,
    ): JsonResponse
    {
        $data = $request->validate([
            'incident_id' => ['nullable', 'integer'],
            'requested_action' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'proposed_plan' => ['nullable', 'string', 'max:8000'],
            'risk_summary' => ['nullable', 'string', 'max:4000'],
            'expected_impact' => ['nullable', 'string', 'max:4000'],
            'rollback_plan' => ['nullable', 'string', 'max:4000'],
        ]);

        $validation = $guard->validateRepairRequest($data);
        if (! $validation['ok']) {
            return response()->json([
                'ok' => false,
                'blocked' => $validation['blocked'],
                'readiness' => $validation['readiness'],
                'message' => "تم إيقاف الموافقة لحماية الإنتاج:\n".$validation['message'],
            ], 422);
        }

        $incidentId = isset($data['incident_id']) ? (int) $data['incident_id'] : null;
        $settings = app(ProfessionalProgrammerSettings::class)->values();
        $backup = null;

        if ($settings['require_backup_before_repair']) {
            $backup = $guard->createBackupCheckpoint(
                $request->user()?->id,
                $incidentId,
                $data['requested_action'] ?? 'Professional Programmer repair approval',
            );

            if (! ($backup['ok'] ?? false)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'تم إيقاف الموافقة لأن إنشاء الباكب فشل: '.($backup['message'] ?? 'unknown'),
                ], 422);
            }
        }

        $payload = [
            'user_id' => $request->user()?->id,
            'incident_id' => $incidentId,
            'training_run_id' => $validation['readiness']['training_run_id'] ?? null,
            'backup_checkpoint_id' => $backup['checkpoint_id'] ?? null,
            'approval_scope' => $incidentId ? 'incident' : 'general',
            'requested_action' => $data['requested_action'] ?? 'Start professional code repair plan after admin approval.',
            'proposed_plan' => $data['proposed_plan'] ?? null,
            'risk_summary' => $data['risk_summary'] ?? null,
            'expected_impact' => $data['expected_impact'] ?? null,
            'rollback_plan' => $data['rollback_plan'] ?? null,
            'blocked_reason' => null,
            'status' => 'approved_pending_codex',
            'metadata' => json_encode([
                'note' => $data['note'] ?? null,
                'readiness' => $validation['readiness'],
                'backup' => $backup,
                'policy' => 'No browser-side code changes. Execution must happen in a documented Codex/maintenance session.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload = array_filter(
            $payload,
            fn (mixed $value, string $column): bool => Schema::hasColumn('professional_programmer_repair_approvals', $column),
            ARRAY_FILTER_USE_BOTH,
        );

        $id = DB::table('professional_programmer_repair_approvals')->insertGetId($payload);
        $trainingSampleId = $learningVerification->storeApprovedSample($id);

        if ($incidentId && Schema::hasTable('professional_programmer_incidents')) {
            DB::table('professional_programmer_incidents')
                ->where('id', $incidentId)
                ->update([
                    'status' => 'awaiting_fix',
                    'acknowledged_by' => $request->user()?->id,
                    'admin_acknowledged_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return response()->json([
            'ok' => true,
            'approval_id' => $id,
            'backup_checkpoint_id' => $backup['checkpoint_id'] ?? null,
            'training_sample_id' => $trainingSampleId,
            'message' => 'تم تسجيل الموافقة بعد تحقق شروط الإنتاج وإنشاء الباكب. التنفيذ ما زال بانتظار جلسة صيانة/Codex موثقة، ولا يوجد تعديل مباشر من الشات.',
        ]);
    }

    private function storeMessage(?int $userId, ?int $incidentId, string $role, string $content, array $metadata = []): void
    {
        if (! Schema::hasTable('professional_programmer_messages')) {
            return;
        }

        DB::table('professional_programmer_messages')->insert([
            'user_id' => $userId,
            'incident_id' => $incidentId,
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
