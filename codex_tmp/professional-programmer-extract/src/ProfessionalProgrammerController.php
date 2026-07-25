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
        $this->storeMessage($request->user()?->id, $incidentId, 'assistant', (string) $response['message'], $response);

        return response()->json($response);
    }

    public function approve(Request $request): JsonResponse
    {
        $data = $request->validate([
            'incident_id' => ['nullable', 'integer'],
            'requested_action' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! Schema::hasTable('professional_programmer_repair_approvals')) {
            return response()->json(['ok' => false, 'message' => 'Approval table is not ready.'], 422);
        }

        $incidentId = isset($data['incident_id']) ? (int) $data['incident_id'] : null;
        $id = DB::table('professional_programmer_repair_approvals')->insertGetId([
            'user_id' => $request->user()?->id,
            'incident_id' => $incidentId,
            'approval_scope' => $incidentId ? 'incident' : 'general',
            'requested_action' => $data['requested_action'] ?? 'Start professional code repair plan after admin approval.',
            'status' => 'approved_pending_codex',
            'metadata' => json_encode(['note' => $data['note'] ?? null], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
            'message' => 'تم تسجيل موافقة الأدمن. ينتظر الإصلاح الآن بدء جلسة تعديل برمجية من Codex.',
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
