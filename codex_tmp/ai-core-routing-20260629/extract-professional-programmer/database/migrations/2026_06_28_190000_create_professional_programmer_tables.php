<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('professional_programmer_learning_runs')) {
            Schema::create('professional_programmer_learning_runs', function (Blueprint $table): void {
                $table->id();
                $table->string('status', 40)->default('running')->index();
                $table->string('triggered_by', 80)->default('manual')->index();
                $table->foreignId('user_id')->nullable()->index();
                $table->unsignedInteger('code_files_seen')->default(0);
                $table->unsignedInteger('code_files_changed')->default(0);
                $table->unsignedInteger('conversation_rows_seen')->default(0);
                $table->unsignedInteger('log_incidents_seen')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('professional_programmer_learning_sources')) {
            Schema::create('professional_programmer_learning_sources', function (Blueprint $table): void {
                $table->id();
                $table->string('source_type', 40)->index();
                $table->string('source_key', 500);
                $table->string('path', 500)->nullable();
                $table->string('hash', 80)->nullable()->index();
                $table->string('title', 255)->nullable();
                $table->text('summary')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('last_seen_at')->nullable()->index();
                $table->timestamps();
                $table->unique(['source_type', 'source_key'], 'pp_sources_type_key_unique');
            });
        }

        if (! Schema::hasTable('professional_programmer_incidents')) {
            Schema::create('professional_programmer_incidents', function (Blueprint $table): void {
                $table->id();
                $table->string('fingerprint', 80)->unique();
                $table->string('source', 255)->index();
                $table->string('level', 40)->index();
                $table->string('severity', 40)->index();
                $table->string('title', 255);
                $table->longText('message');
                $table->json('context')->nullable();
                $table->unsignedInteger('occurrences')->default(1);
                $table->timestamp('first_seen_at')->nullable();
                $table->timestamp('last_seen_at')->nullable()->index();
                $table->string('status', 40)->default('open')->index();
                $table->foreignId('acknowledged_by')->nullable()->index();
                $table->timestamp('admin_acknowledged_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('professional_programmer_messages')) {
            Schema::create('professional_programmer_messages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->index();
                $table->foreignId('incident_id')->nullable()->index();
                $table->string('role', 40)->index();
                $table->longText('content');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('professional_programmer_repair_approvals')) {
            Schema::create('professional_programmer_repair_approvals', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->index();
                $table->foreignId('incident_id')->nullable()->index();
                $table->string('approval_scope', 80)->default('incident')->index();
                $table->string('requested_action', 255);
                $table->string('status', 40)->default('approved_pending_codex')->index();
                $table->json('metadata')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        $this->registerSettings();
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_settings')) {
            DB::table('platform_settings')->where('module', 'professional-programmer')->delete();
        }

        Schema::dropIfExists('professional_programmer_repair_approvals');
        Schema::dropIfExists('professional_programmer_messages');
        Schema::dropIfExists('professional_programmer_incidents');
        Schema::dropIfExists('professional_programmer_learning_sources');
        Schema::dropIfExists('professional_programmer_learning_runs');
    }

    private function registerSettings(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        $settings = [
            'enabled' => ['Enabled', 'boolean', true, 'Enable the Professional Programmer plugin.', 10, 'toggle', false, null, null],
            'admin_widget_enabled' => ['Admin Widget Enabled', 'boolean', true, 'Open the coding assistant widget on admin pages when incidents exist.', 20, 'toggle', false, null, null],
            'auto_scan_logs_on_admin_request' => ['Auto Scan Logs On Admin Request', 'boolean', true, 'Scan configured logs when an admin enters the dashboard, using the scan cooldown.', 30, 'toggle', false, null, null],
            'log_scan_cooldown_seconds' => ['Log Scan Cooldown', 'number', '60', 'Minimum seconds between automatic log scans.', 40, 'number', false, '15', '3600'],
            'log_tail_bytes' => ['Log Tail Bytes', 'number', '262144', 'Bytes read from the end of each configured log file.', 50, 'number', false, '16384', '2097152'],
            'max_admin_alerts' => ['Max Admin Alerts', 'number', '5', 'Maximum unresolved incidents shown in the instant admin widget.', 60, 'number', false, '1', '20'],
            'learning_enabled' => ['Learning Enabled', 'boolean', true, 'Enable database-backed code and conversation learning snapshots.', 70, 'toggle', false, null, null],
            'learning_max_files_per_run' => ['Learning Max Files Per Run', 'number', '600', 'Maximum code files indexed during one learning run.', 80, 'number', false, '25', '5000'],
            'learning_max_file_bytes' => ['Learning Max File Bytes', 'number', '524288', 'Maximum size of one source file to fingerprint.', 90, 'number', false, '1024', '2097152'],
            'system_prompt' => ['Coding System Prompt', 'textarea', 'You are Art INPA Professional Programmer. Explain incidents in Arabic, rank severity, identify likely root cause, propose a safe repair plan, and always ask for admin approval before code changes.', 'System instruction sent to the AI coding endpoint.', 110, 'textarea', false, null, null],
            'repair_requires_admin_approval' => ['Repair Requires Admin Approval', 'boolean', true, 'Code repair workflows must wait for explicit admin approval.', 120, 'toggle', false, null, null],
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
                'required' => $key === 'enabled',
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
                'unit' => str_contains($key, 'seconds') ? 'seconds' : null,
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
};
