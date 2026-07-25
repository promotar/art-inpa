<?php

namespace Modules\ProfessionalProgrammer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use RecursiveCallbackFilterIterator;
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

            $routeRows = $this->snapshotRouteSources();
            $schemaRows = $this->snapshotSchemaSources();
            $pluginRows = $this->snapshotPluginSources();
            $settingRows = $this->snapshotSettingSources();
            $permissionRows = $this->snapshotPermissionSources();
            $documentationRows = $this->snapshotDocumentationSources();
            $migrationRows = $this->snapshotMigrationSources();
            $conversationRows = $this->snapshotConversationSources();
            $incidentRows = $this->snapshotIncidentSources();

            DB::table('professional_programmer_learning_runs')->where('id', $runId)->update([
                'status' => 'completed',
                'code_files_seen' => $seen,
                'code_files_changed' => $changed,
                'conversation_rows_seen' => $conversationRows,
                'log_incidents_seen' => $incidentRows,
                'metadata' => json_encode([
                    'route_rows' => $routeRows,
                    'schema_rows' => $schemaRows,
                    'plugin_rows' => $pluginRows,
                    'setting_rows' => $settingRows,
                    'permission_rows' => $permissionRows,
                    'documentation_rows' => $documentationRows,
                    'migration_rows' => $migrationRows,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'ok' => true,
                'run_id' => $runId,
                'files_seen' => $seen,
                'files_changed' => $changed,
                'conversation_rows' => $conversationRows,
                'incident_rows' => $incidentRows,
                'route_rows' => $routeRows,
                'schema_rows' => $schemaRows,
                'plugin_rows' => $pluginRows,
                'setting_rows' => $settingRows,
                'permission_rows' => $permissionRows,
                'documentation_rows' => $documentationRows,
                'migration_rows' => $migrationRows,
            ];
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

        $sourceTypes = DB::table('professional_programmer_learning_sources')
            ->select('source_type', DB::raw('COUNT(*) as count'))
            ->groupBy('source_type')
            ->pluck('count', 'source_type')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();

        $lastCompletedRun = Schema::hasTable('professional_programmer_learning_runs')
            ? DB::table('professional_programmer_learning_runs')->where('status', 'completed')->latest('id')->first()
            : null;

        return [
            'sources' => DB::table('professional_programmer_learning_sources')->count(),
            'code_sources' => DB::table('professional_programmer_learning_sources')->where('source_type', 'code')->count(),
            'conversation_sources' => DB::table('professional_programmer_learning_sources')->where('source_type', 'conversation')->count(),
            'source_types' => $sourceTypes,
            'last_completed_run' => $lastCompletedRun,
            'last_completed_at' => $lastCompletedRun?->completed_at ? \Illuminate\Support\Carbon::parse($lastCompletedRun->completed_at) : null,
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

            $directory = new RecursiveDirectoryIterator($path);
            $filter = new RecursiveCallbackFilterIterator($directory, function (SplFileInfo $current): bool {
                $relative = str_replace(base_path().'/', '', $current->getPathname());

                return ! $this->excluded($relative);
            });
            $iterator = new RecursiveIteratorIterator($filter);
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

    private function snapshotRouteSources(): int
    {
        $routes = [];
        foreach (Route::getRoutes() as $route) {
            $routes[] = [
                'methods' => $route->methods(),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
                'middleware' => $route->gatherMiddleware(),
            ];
        }

        usort($routes, fn (array $a, array $b): int => strcmp(($a['uri'] ?? '').($a['name'] ?? ''), ($b['uri'] ?? '').($b['name'] ?? '')));

        DB::table('professional_programmer_learning_sources')->updateOrInsert(
            ['source_type' => 'route', 'source_key' => 'laravel_route_collection'],
            [
                'path' => 'routes',
                'hash' => sha1(json_encode($routes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: ''),
                'title' => 'Laravel route collection',
                'summary' => 'Runtime route map indexed for platform flow awareness. Routes: '.count($routes).'.',
                'metadata' => json_encode(['routes' => count($routes)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'last_seen_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return count($routes);
    }

    private function snapshotSchemaSources(): int
    {
        $count = 0;
        foreach ($this->tableNames() as $table) {
            $columns = Schema::getColumnListing($table);
            DB::table('professional_programmer_learning_sources')->updateOrInsert(
                ['source_type' => 'schema', 'source_key' => $table],
                [
                    'path' => $table,
                    'hash' => sha1($table.'|'.implode('|', $columns)),
                    'title' => 'Database table: '.$table,
                    'summary' => 'Database metadata only. Columns indexed: '.count($columns).'. Values are not copied.',
                    'metadata' => json_encode(['columns' => $columns], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'last_seen_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
            $count++;
        }

        return $count;
    }

    private function snapshotPluginSources(): int
    {
        $count = 0;
        $paths = array_merge(
            glob(base_path('modules/*/module.json')) ?: [],
            glob(base_path('professional-programmer-plugin/*/module.json')) ?: [],
        );

        sort($paths);

        foreach ($paths as $path) {
            if (! is_file($path)) {
                continue;
            }

            $relative = str_replace(base_path().'/', '', $path);
            $json = json_decode((string) file_get_contents($path), true);
            DB::table('professional_programmer_learning_sources')->updateOrInsert(
                ['source_type' => 'plugin', 'source_key' => $relative],
                [
                    'path' => $relative,
                    'hash' => sha1_file($path) ?: null,
                    'title' => (string) ($json['name'] ?? basename(dirname($relative))),
                    'summary' => 'Plugin/module manifest indexed for permissions, menus, routes, and provider boundaries.',
                    'metadata' => json_encode([
                        'slug' => $json['slug'] ?? null,
                        'version' => $json['version'] ?? null,
                        'permissions' => $json['permissions'] ?? [],
                        'routes' => array_keys((array) ($json['routes'] ?? [])),
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'last_seen_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
            $count++;
        }

        return $count;
    }

    private function snapshotSettingSources(): int
    {
        if (! Schema::hasTable('platform_settings')) {
            return 0;
        }

        $rows = DB::table('platform_settings')
            ->select('group_key', 'setting_key', 'type', 'category', 'module', 'visibility_level', 'editable', 'sensitive_flag')
            ->orderBy('group_key')
            ->orderBy('setting_key')
            ->get();

        DB::table('professional_programmer_learning_sources')->updateOrInsert(
            ['source_type' => 'setting', 'source_key' => 'platform_settings_registry'],
            [
                'path' => 'platform_settings',
                'hash' => sha1($rows->toJson(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
                'title' => 'Platform settings registry',
                'summary' => 'Settings registry metadata indexed without copying setting values.',
                'metadata' => json_encode(['rows' => $rows->count()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'last_seen_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return $rows->count();
    }

    private function snapshotPermissionSources(): int
    {
        $tables = ['permissions', 'roles', 'role_has_permissions', 'model_has_roles'];
        $metadata = [];
        $count = 0;

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $metadata[$table] = [
                'rows' => DB::table($table)->count(),
                'columns' => Schema::getColumnListing($table),
            ];
            $count++;
        }

        DB::table('professional_programmer_learning_sources')->updateOrInsert(
            ['source_type' => 'permission', 'source_key' => 'permission_registry_metadata'],
            [
                'path' => 'permissions',
                'hash' => sha1(json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: ''),
                'title' => 'Permission and role metadata',
                'summary' => 'Permission table metadata and counts indexed for access-flow awareness.',
                'metadata' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'last_seen_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return $count;
    }

    private function snapshotDocumentationSources(): int
    {
        $count = 0;
        foreach (['project.txt', 'project_documentation.md', 'changes-log.txt', 'connection-method.txt', 'backups-log.txt', 'professional-programmer-plugin/professional-programmer/docs/plugin.md'] as $relative) {
            $path = base_path($relative);
            if (! is_file($path) || ! is_readable($path)) {
                continue;
            }

            DB::table('professional_programmer_learning_sources')->updateOrInsert(
                ['source_type' => 'documentation', 'source_key' => $relative],
                [
                    'path' => $relative,
                    'hash' => sha1_file($path) ?: null,
                    'title' => basename($relative),
                    'summary' => 'Project operational memory indexed for platform learning.',
                    'metadata' => json_encode(['bytes' => filesize($path) ?: 0, 'lines' => $this->lineCount($path)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'last_seen_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
            $count++;
        }

        return $count;
    }

    private function snapshotMigrationSources(): int
    {
        if (! Schema::hasTable('migrations')) {
            return 0;
        }

        $rows = DB::table('migrations')->select('migration', 'batch')->orderBy('batch')->orderBy('migration')->get();
        DB::table('professional_programmer_learning_sources')->updateOrInsert(
            ['source_type' => 'migration', 'source_key' => 'migration_ledger'],
            [
                'path' => 'migrations',
                'hash' => sha1($rows->toJson(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
                'title' => 'Migration ledger',
                'summary' => 'Applied migration ledger indexed for deployment and rollback awareness.',
                'metadata' => json_encode(['rows' => $rows->count()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'last_seen_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return $rows->count();
    }

    /**
     * @return array<int, string>
     */
    private function tableNames(): array
    {
        try {
            $database = DB::connection()->getDatabaseName();
            $rows = DB::select('select table_name from information_schema.tables where table_schema = ? order by table_name', [$database]);

            return array_values(array_map(fn (object $row): string => (string) ($row->table_name ?? $row->TABLE_NAME ?? ''), $rows));
        } catch (Throwable) {
            return [
                'migrations',
                'platform_settings',
                'permissions',
                'roles',
                'professional_programmer_learning_runs',
                'professional_programmer_learning_sources',
                'professional_programmer_incidents',
            ];
        }
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
