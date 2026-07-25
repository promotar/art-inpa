<?php

namespace Modules\ProfessionalProgrammer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class ProfessionalProgrammerLearningService
{
    public function __construct(private readonly ProfessionalProgrammerSettings $settings)
    {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function run(string $trigger = 'manual', ?int $userId = null): array
    {
        if (! Schema::hasTable('professional_programmer_learning_runs')) {
            return ['ok' => false, 'reason' => 'tables_missing'];
        }

        $settings = $this->settings->values();
        if (! $settings['learning_enabled']) {
            return ['ok' => false, 'reason' => 'learning_disabled'];
        }

        $runId = DB::table('professional_programmer_learning_runs')->insertGetId([
            'status' => 'running',
            'triggered_by' => $trigger,
            'user_id' => $userId,
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $seen = 0;
        $changed = 0;

        try {
            foreach ($this->sourceFiles((int) $settings['learning_max_files_per_run'], (int) $settings['learning_max_file_bytes']) as $file) {
                $seen++;
                $changed += $this->upsertCodeSource($file) ? 1 : 0;
            }

            $conversationRows = $this->snapshotConversationSources();
            $incidentRows = $this->snapshotIncidentSources();

            DB::table('professional_programmer_learning_runs')->where('id', $runId)->update([
                'status' => 'completed',
                'code_files_seen' => $seen,
                'code_files_changed' => $changed,
                'conversation_rows_seen' => $conversationRows,
                'log_incidents_seen' => $incidentRows,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            return ['ok' => true, 'run_id' => $runId, 'files_seen' => $seen, 'files_changed' => $changed, 'conversation_rows' => $conversationRows, 'incident_rows' => $incidentRows];
        } catch (Throwable $exception) {
            DB::table('professional_programmer_learning_runs')->where('id', $runId)->update([
                'status' => 'failed',
                'metadata' => json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            return ['ok' => false, 'run_id' => $runId, 'error' => $exception->getMessage()];
        }
    }

    /**
     * @return array<int, object>
     */
    public function recentRuns(int $limit = 5): array
    {
        if (! Schema::hasTable('professional_programmer_learning_runs')) {
            return [];
        }

        return DB::table('professional_programmer_learning_runs')->latest('id')->limit($limit)->get()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        if (! Schema::hasTable('professional_programmer_learning_sources')) {
            return ['sources' => 0, 'last_run' => null];
        }

        return [
            'sources' => DB::table('professional_programmer_learning_sources')->count(),
            'code_sources' => DB::table('professional_programmer_learning_sources')->where('source_type', 'code')->count(),
            'conversation_sources' => DB::table('professional_programmer_learning_sources')->where('source_type', 'conversation')->count(),
            'last_run' => Schema::hasTable('professional_programmer_learning_runs')
                ? DB::table('professional_programmer_learning_runs')->latest('id')->first()
                : null,
        ];
    }

    /**
     * @return iterable<int, SplFileInfo>
     */
    private function sourceFiles(int $limit, int $maxBytes): iterable
    {
        $roots = ['app', 'modules', 'routes', 'config', 'database/migrations', 'resources/views'];
        $extensions = ['php', 'blade.php', 'json', 'js', 'css', 'md'];
        $count = 0;

        foreach ($roots as $root) {
            $path = base_path($root);
            if (! is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            foreach ($iterator as $file) {
                if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                    continue;
                }

                $relative = str_replace(base_path().'/', '', $file->getPathname());
                if ($this->excluded($relative) || $file->getSize() > $maxBytes || ! $this->allowedExtension($relative, $extensions)) {
                    continue;
                }

                yield $file;
                $count++;
                if ($count >= $limit) {
                    return;
                }
            }
        }
    }

    private function upsertCodeSource(SplFileInfo $file): bool
    {
        $relative = str_replace(base_path().'/', '', $file->getPathname());
        $hash = sha1_file($file->getPathname()) ?: null;
        $existingHash = DB::table('professional_programmer_learning_sources')
            ->where('source_type', 'code')
            ->where('source_key', $relative)
            ->value('hash');

        $metadata = [
            'bytes' => $file->getSize(),
            'extension' => pathinfo($relative, PATHINFO_EXTENSION),
            'lines' => $this->lineCount($file->getPathname()),
        ];

        DB::table('professional_programmer_learning_sources')->updateOrInsert(
            ['source_type' => 'code', 'source_key' => $relative],
            [
                'path' => $relative,
                'hash' => $hash,
                'title' => basename($relative),
                'summary' => $this->summaryForPath($relative),
                'metadata' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'last_seen_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return $existingHash !== $hash;
    }

    private function snapshotConversationSources(): int
    {
        $total = 0;
        foreach (['ai_messages', 'ai_assistant_messages'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $count = DB::table($table)->count();
            $latest = DB::table($table)->latest('id')->value('created_at');
            $total += $count;

            DB::table('professional_programmer_learning_sources')->updateOrInsert(
                ['source_type' => 'conversation', 'source_key' => $table],
                [
                    'path' => $table,
                    'hash' => sha1($table.'|'.$count.'|'.$latest),
                    'title' => $table,
                    'summary' => 'Conversation table available for coding assistant context. Rows: '.$count.'.',
                    'metadata' => json_encode(['rows' => $count, 'latest_at' => $latest], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'last_seen_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        return $total;
    }

    private function snapshotIncidentSources(): int
    {
        if (! Schema::hasTable('professional_programmer_incidents')) {
            return 0;
        }

        $count = DB::table('professional_programmer_incidents')->count();
        $open = DB::table('professional_programmer_incidents')->where('status', 'open')->count();

        DB::table('professional_programmer_learning_sources')->updateOrInsert(
            ['source_type' => 'log', 'source_key' => 'professional_programmer_incidents'],
            [
                'path' => 'professional_programmer_incidents',
                'hash' => sha1($count.'|'.$open),
                'title' => 'Monitored log incidents',
                'summary' => 'Log incident index. Total: '.$count.'. Open: '.$open.'.',
                'metadata' => json_encode(['rows' => $count, 'open' => $open], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'last_seen_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return $count;
    }

    private function excluded(string $relative): bool
    {
        foreach (['vendor/', 'storage/', 'bootstrap/cache/', 'node_modules/'] as $part) {
            if (str_contains($relative, $part)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $extensions
     */
    private function allowedExtension(string $relative, array $extensions): bool
    {
        foreach ($extensions as $extension) {
            if (str_ends_with($relative, '.'.$extension)) {
                return true;
            }
        }

        return false;
    }

    private function lineCount(string $path): int
    {
        $count = 0;
        $handle = fopen($path, 'rb');
        if (! $handle) {
            return 0;
        }

        while (! feof($handle)) {
            $count += substr_count((string) fgets($handle), "\n");
        }
        fclose($handle);

        return $count;
    }

    private function summaryForPath(string $relative): string
    {
        if (str_starts_with($relative, 'modules/')) {
            return 'Module source file used by the plugin architecture.';
        }

        if (str_starts_with($relative, 'app/Services/')) {
            return 'Application service layer file.';
        }

        if (str_starts_with($relative, 'routes/')) {
            return 'Route definition file.';
        }

        return 'Platform source file indexed for coding assistant context.';
    }
}
