<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('professional_programmer_tool_policies')) {
            Schema::create('professional_programmer_tool_policies', function (Blueprint $table): void {
                $table->id();
                $table->string('tool_key', 120)->unique();
                $table->string('capability', 120)->index();
                $table->string('access_level', 80)->default('blocked')->index();
                $table->boolean('allowed')->default(false)->index();
                $table->boolean('requires_training')->default(true);
                $table->boolean('requires_backup')->default(true);
                $table->boolean('requires_admin_approval')->default(true);
                $table->string('execution_surface', 120)->default('codex_maintenance');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('professional_programmer_backup_checkpoints')) {
            Schema::create('professional_programmer_backup_checkpoints', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->index();
                $table->foreignId('incident_id')->nullable()->index();
                $table->string('path', 700);
                $table->string('status', 40)->default('running')->index();
                $table->string('reason', 255)->nullable();
                $table->json('manifest')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('professional_programmer_repair_approvals')) {
            $this->addRepairApprovalColumns();
        }

        $this->registerSettings();
        $this->seedToolPolicies();
    }

    public function down(): void
    {
        if (Schema::hasTable('professional_programmer_repair_approvals')) {
            Schema::table('professional_programmer_repair_approvals', function (Blueprint $table): void {
                foreach (['training_run_id', 'backup_checkpoint_id', 'proposed_plan', 'risk_summary', 'expected_impact', 'rollback_plan', 'blocked_reason'] as $column) {
                    if (Schema::hasColumn('professional_programmer_repair_approvals', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('professional_programmer_backup_checkpoints');
        Schema::dropIfExists('professional_programmer_tool_policies');

        if (Schema::hasTable('platform_settings')) {
            DB::table('platform_settings')
                ->where('group_key', 'professional_programmer')
                ->whereIn('setting_key', [
                    'require_fresh_training_before_repair',
                    'training_fresh_minutes',
                    'require_backup_before_repair',
                    'require_written_plan_before_repair',
                    'web_terminal_write_allowed',
                    'suppress_maintenance_noise',
                    'backup_roots',
                ])
                ->delete();
        }
    }

    private function registerSettings(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        $settings = [
            'require_fresh_training_before_repair' => ['Require Fresh Training Before Repair', 'boolean', true, 'Block repair approval unless the platform learning index is fresh and complete enough for production work.', 130, 'toggle', false, null, null],
            'training_fresh_minutes' => ['Training Fresh Minutes', 'number', '60', 'Maximum age for a completed learning run before repair approval is blocked.', 140, 'number', false, '5', '1440'],
            'require_backup_before_repair' => ['Require Backup Before Repair', 'boolean', true, 'Create a source checkpoint before any repair approval can move to maintenance.', 150, 'toggle', false, null, null],
            'require_written_plan_before_repair' => ['Require Written Plan Before Repair', 'boolean', true, 'Require proposed plan, risk, impact, and rollback text before approving repair.', 160, 'toggle', false, null, null],
            'web_terminal_write_allowed' => ['Web Terminal Write Allowed', 'boolean', false, 'Keep terminal write, file write, DB write, and deploy actions blocked from the browser plugin surface.', 170, 'toggle', false, null, null],
            'suppress_maintenance_noise' => ['Suppress Maintenance Noise', 'boolean', true, 'Suppress known maintenance/test parser errors so admin alerts focus on production issues.', 180, 'toggle', false, null, null],
            'backup_roots' => ['Backup Roots', 'json', ['app', 'modules', 'routes', 'config', 'database/migrations', 'resources/views', 'composer.json', 'composer.lock', 'project_documentation.md', 'project.txt'], 'Critical source roots copied into a pre-repair checkpoint.', 190, 'textarea', false, null, null],
        ];

        foreach ($settings as $key => $definition) {
            [$label, $type, $default, $description, $sortOrder, $component, $sensitive, $min, $max] = $definition;

            $payload = [
                'group_key' => 'professional_programmer',
                'setting_key' => $key,
                'label' => $label,
                'type' => $type,
                'default_value' => json_encode($default, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'options' => null,
                'help_text' => $description,
                'sort_order' => $sortOrder,
                'is_public' => false,
                'validation_rules' => json_encode([]),
                'description' => $description,
                'category' => 'professional_programmer',
                'module' => 'professional-programmer',
                'visibility_level' => $sensitive ? 'sensitive' : 'admin',
                'admin_access_level' => 'professional-programmer.manage',
                'editable' => true,
                'required' => true,
                'sensitive_flag' => (bool) $sensitive,
                'public_exposure_allowed' => false,
                'frontend_available' => false,
                'cache_enabled' => true,
                'cache_ttl' => null,
                'ui_component' => $component,
                'ui_label' => $label,
                'allowed_values' => json_encode([]),
                'min_value' => $min,
                'max_value' => $max,
                'unit' => $key === 'training_fresh_minutes' ? 'minutes' : null,
                'depends_on' => json_encode([]),
                'restart_required' => false,
                'approval_required' => false,
                'status' => 'active',
                'version' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ];

            $payload = collect($payload)
                ->filter(fn (mixed $value, string $column): bool => Schema::hasColumn('platform_settings', $column))
                ->all();

            DB::table('platform_settings')->updateOrInsert(
                ['group_key' => 'professional_programmer', 'setting_key' => $key],
                $payload,
            );
        }
    }

    private function addRepairApprovalColumns(): void
    {
        foreach ([
            'training_run_id' => fn (Blueprint $table) => $table->unsignedBigInteger('training_run_id')->nullable()->after('incident_id'),
            'backup_checkpoint_id' => fn (Blueprint $table) => $table->unsignedBigInteger('backup_checkpoint_id')->nullable()->after('training_run_id'),
            'proposed_plan' => fn (Blueprint $table) => $table->longText('proposed_plan')->nullable()->after('requested_action'),
            'risk_summary' => fn (Blueprint $table) => $table->text('risk_summary')->nullable()->after('proposed_plan'),
            'expected_impact' => fn (Blueprint $table) => $table->text('expected_impact')->nullable()->after('risk_summary'),
            'rollback_plan' => fn (Blueprint $table) => $table->text('rollback_plan')->nullable()->after('expected_impact'),
            'blocked_reason' => fn (Blueprint $table) => $table->text('blocked_reason')->nullable()->after('rollback_plan'),
        ] as $column => $definition) {
            if (! Schema::hasColumn('professional_programmer_repair_approvals', $column)) {
                Schema::table('professional_programmer_repair_approvals', $definition);
            }
        }

        foreach ([
            'training_run_id' => 'pp_approvals_training_idx',
            'backup_checkpoint_id' => 'pp_approvals_backup_idx',
        ] as $column => $index) {
            if (! $this->indexExists('professional_programmer_repair_approvals', $index)) {
                Schema::table('professional_programmer_repair_approvals', function (Blueprint $table) use ($column, $index): void {
                    $table->index($column, $index);
                });
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::connection()->getDatabaseName();
        $rows = DB::select(
            'select index_name from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ? limit 1',
            [$database, $table, $index],
        );

        return $rows !== [];
    }

    private function seedToolPolicies(): void
    {
        if (! Schema::hasTable('professional_programmer_tool_policies')) {
            return;
        }

        $policies = [
            ['terminal.read', 'terminal_read', 'maintenance_read', true, true, false, false, 'codex_maintenance', 'Read-only terminal inspection is allowed only in maintenance/Codex context after platform learning is current.'],
            ['terminal.write', 'terminal_write', 'blocked', false, true, true, true, 'blocked_in_web_plugin', 'Shell writes, package installs, migrations, and service restarts are blocked from the web plugin.'],
            ['filesystem.read', 'filesystem_read', 'maintenance_read', true, true, false, false, 'codex_maintenance', 'Read-only source inspection is allowed for trained maintenance workflows.'],
            ['filesystem.write', 'filesystem_write', 'approval_required', false, true, true, true, 'codex_maintenance', 'Code edits require a fresh learning run, backup checkpoint, written plan, and explicit admin approval.'],
            ['database.read_metadata', 'database_metadata_read', 'maintenance_read', true, true, false, false, 'codex_maintenance', 'Schema/count metadata may be indexed; sensitive values must not be exposed.'],
            ['database.write', 'database_write', 'approval_required', false, true, true, true, 'codex_maintenance', 'Database writes and migrations require external maintenance execution with backup and approval.'],
            ['deploy.run', 'deployment', 'approval_required', false, true, true, true, 'codex_maintenance', 'Deployment must run outside the browser chat with documented rollback.'],
            ['web_chat.repair_approval', 'approval_recording', 'limited', true, true, true, true, 'admin_browser', 'The web chat can record approval requests only after guard checks pass; it cannot modify source code directly.'],
        ];

        foreach ($policies as $policy) {
            [$toolKey, $capability, $accessLevel, $allowed, $requiresTraining, $requiresBackup, $requiresApproval, $surface, $notes] = $policy;

            DB::table('professional_programmer_tool_policies')->updateOrInsert(
                ['tool_key' => $toolKey],
                [
                    'capability' => $capability,
                    'access_level' => $accessLevel,
                    'allowed' => $allowed,
                    'requires_training' => $requiresTraining,
                    'requires_backup' => $requiresBackup,
                    'requires_admin_approval' => $requiresApproval,
                    'execution_surface' => $surface,
                    'notes' => $notes,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
};
