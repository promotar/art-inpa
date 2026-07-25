<?php

namespace Modules\ProfessionalProgrammer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class ProfessionalProgrammerProductionGuard
{
    public function __construct(
        private readonly ProfessionalProgrammerSettings $settings,
        private readonly ProfessionalProgrammerLearningService $learning,
    ) {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function readiness(): array
    {
        $settings = $this->settings->values();
        $learning = $this->learning->status();
        $lastRun = $learning['last_completed_run'] ?? null;
        $ageMinutes = isset($learning['last_completed_at'])
            ? now()->diffInMinutes($learning['last_completed_at'], true)
            : null;

        $requiredTypes = ['code', 'route', 'schema', 'plugin', 'setting', 'documentation'];
        $sourceTypes = $learning['source_types'] ?? [];
        $missingTypes = array_values(array_filter($requiredTypes, fn (string $type): bool => empty($sourceTypes[$type])));
        $fresh = $lastRun
            && $ageMinutes !== null
            && $ageMinutes <= (int) $settings['training_fresh_minutes']
            && $missingTypes === [];

        $reasons = [];
        if (! $lastRun) {
            $reasons[] = 'لم يتم تشغيل تدريب مكتمل بعد.';
        }
        if ($ageMinutes !== null && $ageMinutes > (int) $settings['training_fresh_minutes']) {
            $reasons[] = 'التدريب أقدم من الحد المسموح: '.$ageMinutes.' دقيقة.';
        }
        if ($missingTypes !== []) {
            $reasons[] = 'مصادر تدريب ناقصة: '.implode(', ', $missingTypes).'.';
        }
        if ($settings['web_terminal_write_allowed']) {
            $reasons[] = 'تحذير: الكتابة من واجهة الويب مفعلة، وهذا غير مناسب للإنتاج.';
        }

        return [
            'training_required' => (bool) $settings['require_fresh_training_before_repair'],
            'training_fresh' => (bool) $fresh,
            'training_age_minutes' => $ageMinutes,
            'training_run_id' => $lastRun->id ?? null,
            'missing_source_types' => $missingTypes,
            'requires_backup' => (bool) $settings['require_backup_before_repair'],
            'requires_written_plan' => (bool) $settings['require_written_plan_before_repair'],
            'web_terminal_write_allowed' => (bool) $settings['web_terminal_write_allowed'],
            'ready_for_repair_approval' => (! $settings['require_fresh_training_before_repair'] || $fresh)
                && ! $settings['web_terminal_write_allowed'],
            'reasons' => $reasons,
            'learning' => $learning,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function validateRepairRequest(array $data): array
    {
        $settings = $this->settings->values();
        $readiness = $this->readiness();
        $blocked = [];

        if (! Schema::hasTable('professional_programmer_repair_approvals')) {
            $blocked[] = 'جدول موافقات الإصلاح غير جاهز.';
        }

        if ($settings['require_fresh_training_before_repair'] && ! $readiness['training_fresh']) {
            $blocked[] = 'لا يمكن اعتماد أي إصلاح قبل تشغيل تدريب حديث على كود وفلو المنصة.';
        }

        if ($settings['web_terminal_write_allowed']) {
            $blocked[] = 'سياسة الإنتاج تمنع تفعيل كتابة التيرمنل من واجهة الويب.';
        }

        if ($settings['require_written_plan_before_repair']) {
            foreach ([
                'proposed_plan' => 'خطة التعديل',
                'risk_summary' => 'المخاطر والحساسية',
                'expected_impact' => 'الأثر المتوقع',
                'rollback_plan' => 'خطة الرجوع',
            ] as $field => $label) {
                if (trim((string) ($data[$field] ?? '')) === '') {
                    $blocked[] = 'الحقل مطلوب قبل الموافقة: '.$label.'.';
                }
            }
        }

        return [
            'ok' => $blocked === [],
            'blocked' => $blocked,
            'readiness' => $readiness,
            'message' => $blocked === []
                ? 'جاهز لتسجيل موافقة إصلاح مشروطة بالباكب والتنفيذ عبر جلسة صيانة.'
                : implode("\n", $blocked),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createBackupCheckpoint(?int $userId, ?int $incidentId, string $reason): array
    {
        if (! Schema::hasTable('professional_programmer_backup_checkpoints')) {
            return ['ok' => false, 'message' => 'جدول الباكب غير جاهز.'];
        }

        $settings = $this->settings->values();
        $stamp = now()->format('Ymd-His');
        $relative = 'professional-programmer/backups/pre-repair-'.$stamp;
        $target = storage_path('app/'.$relative);
        $checkpointId = DB::table('professional_programmer_backup_checkpoints')->insertGetId([
            'user_id' => $userId,
            'incident_id' => $incidentId,
            'path' => $target,
            'status' => 'running',
            'reason' => mb_substr($reason, 0, 255),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            File::ensureDirectoryExists($target);
            $manifest = [
                'created_at' => now()->toIso8601String(),
                'base_path' => base_path(),
                'reason' => $reason,
                'roots' => [],
                'files' => [],
                'database_snapshot' => $this->databaseSnapshot(),
                'notes' => [
                    'Source checkpoint created before repair approval.',
                    'No .env or database values are exported by this web checkpoint.',
                    'Production maintenance should still create an operator-level DB backup before migrations or data changes.',
                ],
            ];

            foreach ($settings['backup_roots'] as $root) {
                $source = base_path($root);
                if (! File::exists($source)) {
                    continue;
                }

                $manifest['roots'][] = $root;
                if (File::isDirectory($source)) {
                    $this->copyDirectory($source, $target.'/'.$root, $root, $manifest);
                } else {
                    $this->copyFile($source, $target.'/'.$root, $root, $manifest);
                }
            }

            File::put($target.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            File::put($target.'/database_snapshot.json', json_encode($manifest['database_snapshot'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            DB::table('professional_programmer_backup_checkpoints')->where('id', $checkpointId)->update([
                'status' => 'completed',
                'manifest' => json_encode([
                    'relative_path' => $relative,
                    'files' => count($manifest['files']),
                    'roots' => $manifest['roots'],
                    'database_snapshot_tables' => count($manifest['database_snapshot']['tables']),
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

            return [
                'ok' => true,
                'checkpoint_id' => $checkpointId,
                'path' => $target,
                'relative_path' => $relative,
                'files' => count($manifest['files']),
            ];
        } catch (Throwable $exception) {
            DB::table('professional_programmer_backup_checkpoints')->where('id', $checkpointId)->update([
                'status' => 'failed',
                'manifest' => json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

            return ['ok' => false, 'checkpoint_id' => $checkpointId, 'message' => $exception->getMessage()];
        }
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function copyDirectory(string $source, string $target, string $relativeRoot, array &$manifest): void
    {
        File::ensureDirectoryExists($target);
        $directory = new RecursiveDirectoryIterator($source);
        $filter = new RecursiveCallbackFilterIterator($directory, function (SplFileInfo $current) use ($source, $relativeRoot): bool {
            $relative = str_replace('\\', '/', $relativeRoot.'/'.ltrim(str_replace($source, '', $current->getPathname()), DIRECTORY_SEPARATOR));

            return ! $this->excludedFromBackup($relative);
        });
        $iterator = new RecursiveIteratorIterator($filter);

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $relative = str_replace('\\', '/', $relativeRoot.'/'.ltrim(str_replace($source, '', $file->getPathname()), DIRECTORY_SEPARATOR));
            if ($this->excludedFromBackup($relative)) {
                continue;
            }

            $this->copyFile($file->getPathname(), $target.'/'.ltrim(str_replace($source, '', $file->getPathname()), DIRECTORY_SEPARATOR), $relative, $manifest);
        }
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function copyFile(string $source, string $target, string $relative, array &$manifest): void
    {
        File::ensureDirectoryExists(dirname($target));
        File::copy($source, $target);
        $manifest['files'][] = [
            'path' => str_replace('\\', '/', $relative),
            'bytes' => filesize($source) ?: 0,
            'hash' => sha1_file($source) ?: null,
        ];
    }

    private function excludedFromBackup(string $relative): bool
    {
        foreach (['/vendor/', '/node_modules/', '/storage/', '/bootstrap/cache/', '.env'] as $part) {
            if (str_contains('/'.$relative, $part)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseSnapshot(): array
    {
        $tables = [];
        foreach ([
            'migrations',
            'platform_settings',
            'permissions',
            'roles',
            'model_has_roles',
            'role_has_permissions',
            'professional_programmer_learning_runs',
            'professional_programmer_learning_sources',
            'professional_programmer_incidents',
            'professional_programmer_repair_approvals',
            'professional_programmer_tool_policies',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $tables[$table] = [
                'rows' => DB::table($table)->count(),
                'columns' => Schema::getColumnListing($table),
            ];
        }

        return [
            'created_at' => now()->toIso8601String(),
            'connection' => DB::connection()->getName(),
            'tables' => $tables,
        ];
    }
}
