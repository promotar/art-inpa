<?php

namespace Modules\ProfessionalProgrammer;

use App\Http\Controllers\Controller;
use App\Platform\Core\Services\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProfessionalProgrammerAdminController extends Controller
{
    public function index(
        Request $request,
        ProfessionalProgrammerSettings $settings,
        ProfessionalProgrammerLogMonitor $logs,
        ProfessionalProgrammerLearningService $learning,
        ProfessionalProgrammerLearningVerificationService $learningVerification,
        ProfessionalProgrammerProductionGuard $guard,
    ): View {
        return view('professional-programmer::dashboard', [
            'settings' => $settings->values(),
            'incidents' => $logs->unresolved(30),
            'learningStatus' => $learning->status(),
            'learningVerification' => $learningVerification->status($request->user()),
            'readiness' => $guard->readiness(),
            'learningRuns' => $learning->recentRuns(8),
            'approvals' => Schema::hasTable('professional_programmer_repair_approvals')
                ? DB::table('professional_programmer_repair_approvals')->latest('id')->limit(10)->get()
                : collect(),
            'toolPolicies' => Schema::hasTable('professional_programmer_tool_policies')
                ? DB::table('professional_programmer_tool_policies')->orderBy('tool_key')->get()
                : collect(),
            'backupCheckpoints' => Schema::hasTable('professional_programmer_backup_checkpoints')
                ? DB::table('professional_programmer_backup_checkpoints')->latest('id')->limit(6)->get()
                : collect(),
        ]);
    }

    public function update(Request $request, SettingsRepository $settings): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'admin_widget_enabled' => ['nullable', 'boolean'],
            'auto_scan_logs_on_admin_request' => ['nullable', 'boolean'],
            'learning_enabled' => ['nullable', 'boolean'],
            'repair_requires_admin_approval' => ['nullable', 'boolean'],
            'require_fresh_training_before_repair' => ['nullable', 'boolean'],
            'require_backup_before_repair' => ['nullable', 'boolean'],
            'require_written_plan_before_repair' => ['nullable', 'boolean'],
            'web_terminal_write_allowed' => ['nullable', 'boolean'],
            'suppress_maintenance_noise' => ['nullable', 'boolean'],
            'log_scan_cooldown_seconds' => ['required', 'integer', 'min:15', 'max:3600'],
            'log_tail_bytes' => ['required', 'integer', 'min:16384', 'max:2097152'],
            'max_admin_alerts' => ['required', 'integer', 'min:1', 'max:20'],
            'learning_max_files_per_run' => ['required', 'integer', 'min:25', 'max:5000'],
            'learning_max_file_bytes' => ['required', 'integer', 'min:1024', 'max:2097152'],
            'training_fresh_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'backup_roots' => ['required', 'string', 'max:2000'],
            'system_prompt' => ['required', 'string', 'max:3000'],
        ]);

        $settings->update(
            ['professional_programmer' => [
                'enabled' => $request->boolean('enabled'),
                'admin_widget_enabled' => $request->boolean('admin_widget_enabled'),
                'auto_scan_logs_on_admin_request' => $request->boolean('auto_scan_logs_on_admin_request'),
                'learning_enabled' => $request->boolean('learning_enabled'),
                'repair_requires_admin_approval' => $request->boolean('repair_requires_admin_approval'),
                'require_fresh_training_before_repair' => $request->boolean('require_fresh_training_before_repair'),
                'require_backup_before_repair' => $request->boolean('require_backup_before_repair'),
                'require_written_plan_before_repair' => $request->boolean('require_written_plan_before_repair'),
                'web_terminal_write_allowed' => $request->boolean('web_terminal_write_allowed'),
                'suppress_maintenance_noise' => $request->boolean('suppress_maintenance_noise'),
                'log_scan_cooldown_seconds' => (string) $data['log_scan_cooldown_seconds'],
                'log_tail_bytes' => (string) $data['log_tail_bytes'],
                'max_admin_alerts' => (string) $data['max_admin_alerts'],
                'learning_max_files_per_run' => (string) $data['learning_max_files_per_run'],
                'learning_max_file_bytes' => (string) $data['learning_max_file_bytes'],
                'training_fresh_minutes' => (string) $data['training_fresh_minutes'],
                'backup_roots' => json_encode(array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $data['backup_roots']) ?: []))), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'system_prompt' => $data['system_prompt'],
            ]],
            [],
            [],
            [],
            $request->user()?->id,
            'admin.plugins.professional-programmer.settings',
        );

        return back()->with('status', 'Professional Programmer settings saved.');
    }

    public function learn(Request $request, ProfessionalProgrammerLearningService $learning): RedirectResponse
    {
        $result = $learning->run('admin_manual', $request->user()?->id);

        return back()->with('status', ($result['ok'] ?? false)
            ? 'Learning run completed. Files seen: '.$result['files_seen'].'. Changed: '.$result['files_changed'].'.'
            : 'Learning run failed: '.($result['reason'] ?? $result['error'] ?? 'unknown'));
    }

    public function verifyLearning(Request $request, ProfessionalProgrammerLearningVerificationService $learningVerification): RedirectResponse
    {
        $result = $learningVerification->runVerification($request->user()?->id);

        if ($result['ok'] ?? false) {
            return back()->with('status', 'Learning verification completed and candidate model was promoted safely.');
        }

        return back()->with('status', 'Learning verification failed: '.($result['reason'] ?? $result['error'] ?? 'verification rules did not pass'));
    }

    public function scan(ProfessionalProgrammerLogMonitor $logs): RedirectResponse
    {
        $result = $logs->scanLatest('admin_manual');

        return back()->with('status', 'Log scan completed. Created: '.($result['created'] ?? 0).'. Updated: '.($result['updated'] ?? 0).'. Suppressed: '.($result['suppressed'] ?? 0).'.');
    }

    public function resolve(int $incident): RedirectResponse
    {
        if (Schema::hasTable('professional_programmer_incidents')) {
            DB::table('professional_programmer_incidents')
                ->where('id', $incident)
                ->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return back()->with('status', 'Incident marked resolved.');
    }
}
