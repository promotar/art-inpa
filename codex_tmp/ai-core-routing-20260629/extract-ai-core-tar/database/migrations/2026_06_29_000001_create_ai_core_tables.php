<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_core_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type')->default('string');
            $table->longText('default_value')->nullable();
            $table->json('validation_rules')->nullable();
            $table->text('description')->nullable();
            $table->string('category')->default('ai_core');
            $table->string('module')->default('ai-core');
            $table->string('visibility_level')->default('admin');
            $table->string('admin_access_level')->default('super_admin');
            $table->boolean('editable')->default(true);
            $table->boolean('required')->default(false);
            $table->boolean('sensitive_flag')->default(false);
            $table->boolean('public_exposure_allowed')->default(false);
            $table->boolean('frontend_available')->default(false);
            $table->boolean('cache_enabled')->default(true);
            $table->unsignedInteger('cache_ttl')->default(300);
            $table->string('ui_component')->default('text');
            $table->string('ui_label')->nullable();
            $table->json('allowed_values')->nullable();
            $table->decimal('min_value', 20, 4)->nullable();
            $table->decimal('max_value', 20, 4)->nullable();
            $table->string('unit')->nullable();
            $table->json('depends_on')->nullable();
            $table->boolean('restart_required')->default(false);
            $table->boolean('approval_required')->default(false);
            $table->string('status')->default('active');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('ai_core_models', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('type');
            $table->string('backend');
            $table->string('endpoint');
            $table->boolean('enabled')->default(true);
            $table->json('allowed_plugins')->nullable();
            $table->json('allowed_roles')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->string('risk_level')->default('low');
            $table->json('context_policy')->nullable();
            $table->json('dataset_policy')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_core_datasets', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('owner_plugin');
            $table->string('source_type');
            $table->json('allowed_roles')->nullable();
            $table->json('allowed_tools')->nullable();
            $table->string('rag_collection')->nullable();
            $table->string('privacy_level')->default('internal');
            $table->string('indexing_status')->default('not_indexed');
            $table->timestamp('last_indexed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_core_tools', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('plugin_owner')->default('ai-core');
            $table->string('endpoint');
            $table->json('input_schema')->nullable();
            $table->json('output_schema')->nullable();
            $table->string('required_permission')->nullable();
            $table->string('risk_level')->default('low');
            $table->boolean('requires_approval')->default(false);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('ai_core_tool_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('tool_slug')->index();
            $table->string('plugin_slug')->nullable()->index();
            $table->string('role_slug')->nullable()->index();
            $table->string('permission')->nullable();
            $table->boolean('allowed')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->json('limits')->nullable();
            $table->timestamps();
            $table->unique(['tool_slug', 'plugin_slug', 'role_slug'], 'ai_core_tool_perm_unique');
        });

        Schema::create('ai_core_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('request_uuid')->unique();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('plugin_slug')->nullable()->index();
            $table->string('tool_slug')->index();
            $table->string('model_slug')->nullable()->index();
            $table->string('dataset_slug')->nullable()->index();
            $table->string('endpoint');
            $table->string('method')->default('POST');
            $table->string('status')->default('started');
            $table->json('request_payload')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_core_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_id')->nullable()->index();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->boolean('ok')->default(false);
            $table->json('response_payload')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->decimal('cost_units', 12, 4)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_core_tool_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_id')->nullable()->index();
            $table->foreignId('conversation_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('tool_slug')->index();
            $table->string('result_type')->index();
            $table->string('status')->default('stored');
            $table->string('source_url')->nullable();
            $table->json('result_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_core_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('job_uuid')->unique();
            $table->string('external_job_id')->nullable()->index();
            $table->string('tool_slug')->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('plugin_slug')->nullable()->index();
            $table->string('status')->default('queued')->index();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_core_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type')->index();
            $table->foreignId('actor_user_id')->nullable()->index();
            $table->string('plugin_slug')->nullable()->index();
            $table->string('tool_slug')->nullable()->index();
            $table->string('target_type')->nullable();
            $table->string('target_id')->nullable();
            $table->boolean('allowed')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_core_usage_limits', function (Blueprint $table): void {
            $table->id();
            $table->string('tool_slug')->index();
            $table->string('role_slug')->nullable()->index();
            $table->string('plugin_slug')->nullable()->index();
            $table->unsignedInteger('daily_limit')->nullable();
            $table->unsignedInteger('hourly_limit')->nullable();
            $table->unsignedInteger('minute_limit')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['tool_slug', 'role_slug', 'plugin_slug'], 'ai_core_usage_unique');
        });

        Schema::create('ai_core_rag_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('dataset_slug')->index();
            $table->string('owner_plugin')->index();
            $table->string('source_type');
            $table->string('source_identifier')->nullable();
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('last_indexed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_core_rag_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_id')->nullable()->index();
            $table->string('dataset_slug')->index();
            $table->string('document_hash')->unique();
            $table->string('title')->nullable();
            $table->longText('content_excerpt')->nullable();
            $table->json('metadata')->nullable();
            $table->string('indexing_status')->default('pending');
            $table->timestamps();
        });

        Schema::create('ai_core_training_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('owner_plugin')->index();
            $table->string('model_slug')->index();
            $table->string('dataset_slug')->nullable()->index();
            $table->string('status')->default('inactive');
            $table->json('policy')->nullable();
            $table->json('evaluation_rules')->nullable();
            $table->timestamp('last_training_at')->nullable();
            $table->timestamps();
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        foreach ([
            'ai_core_training_profiles',
            'ai_core_rag_documents',
            'ai_core_rag_sources',
            'ai_core_usage_limits',
            'ai_core_audit_logs',
            'ai_core_jobs',
            'ai_core_tool_results',
            'ai_core_responses',
            'ai_core_requests',
            'ai_core_tool_permissions',
            'ai_core_tools',
            'ai_core_datasets',
            'ai_core_models',
            'ai_core_settings',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function seedDefaults(): void
    {
        $now = now();

        foreach ($this->settings() as $row) {
            DB::table('ai_core_settings')->updateOrInsert(['key' => $row['key']], array_merge($row, [
                'updated_at' => $now,
                'created_at' => $now,
            ]));
        }

        foreach ($this->models() as $row) {
            DB::table('ai_core_models')->updateOrInsert(['slug' => $row['slug']], array_merge($row, [
                'updated_at' => $now,
                'created_at' => $now,
            ]));
        }

        foreach ($this->datasets() as $row) {
            DB::table('ai_core_datasets')->updateOrInsert(['slug' => $row['slug']], array_merge($row, [
                'updated_at' => $now,
                'created_at' => $now,
            ]));
        }

        foreach ($this->tools() as $row) {
            DB::table('ai_core_tools')->updateOrInsert(['slug' => $row['slug']], array_merge($row, [
                'updated_at' => $now,
                'created_at' => $now,
            ]));
        }

        foreach ($this->toolPermissions() as $row) {
            DB::table('ai_core_tool_permissions')->updateOrInsert([
                'tool_slug' => $row['tool_slug'],
                'plugin_slug' => $row['plugin_slug'],
                'role_slug' => $row['role_slug'],
            ], array_merge($row, [
                'updated_at' => $now,
                'created_at' => $now,
            ]));
        }

        foreach ($this->usageLimits() as $row) {
            DB::table('ai_core_usage_limits')->updateOrInsert([
                'tool_slug' => $row['tool_slug'],
                'role_slug' => $row['role_slug'],
                'plugin_slug' => $row['plugin_slug'],
            ], array_merge($row, [
                'updated_at' => $now,
                'created_at' => $now,
            ]));
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function settings(): array
    {
        return [
            $this->setting('gateway_base_url', 'http://10.10.0.40:8080', 'url', 'AI Gateway base URL.', 'AI Gateway URL', true),
            $this->setting('gateway_api_key', '', 'password', 'AI Gateway API key. Stored only in the database settings system.', 'AI Gateway API Key', true, true),
            $this->setting('default_timeout', '60', 'integer', 'Default AI Gateway timeout in seconds.', 'Default Timeout', true),
            $this->setting('image_timeout', '300', 'integer', 'Image, vision, and artwork timeout in seconds.', 'Image Timeout', true),
            $this->setting('fallback_classifier_enabled', '1', 'boolean', 'Allow AI Gateway fallback intent classification.', 'Fallback Classifier', true),
            $this->setting('confidence_threshold', '0.75', 'decimal', 'Minimum classifier confidence before clarification.', 'Confidence Threshold', true),
        ];
    }

    private function setting(string $key, string $value, string $type, string $description, string $label, bool $editable, bool $sensitive = false): array
    {
        return [
            'key' => $key,
            'value' => $value,
            'type' => $type,
            'default_value' => $value,
            'validation_rules' => json_encode([]),
            'description' => $description,
            'category' => 'ai_core',
            'module' => 'ai-core',
            'visibility_level' => 'admin',
            'admin_access_level' => 'super_admin',
            'editable' => $editable,
            'required' => $key !== 'gateway_api_key',
            'sensitive_flag' => $sensitive,
            'public_exposure_allowed' => false,
            'frontend_available' => false,
            'cache_enabled' => true,
            'cache_ttl' => 300,
            'ui_component' => $type === 'password' ? 'password' : 'text',
            'ui_label' => $label,
            'allowed_values' => null,
            'min_value' => null,
            'max_value' => null,
            'unit' => $type === 'integer' ? 'seconds' : null,
            'depends_on' => null,
            'restart_required' => false,
            'approval_required' => $sensitive,
            'status' => 'active',
            'version' => 1,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function models(): array
    {
        return [
            $this->model('general_chat', 'chat', 'ollama', '/v1/general/chat', ['ai-assistant'], ['user', 'admin', 'super_admin'], 'low', 'qwen3:8b'),
            $this->model('coding_chat', 'coding', 'ollama', '/v1/coding/chat', ['professional-programmer'], ['admin', 'developer', 'super_admin'], 'high', 'qwen2.5-coder:7b-instruct', true),
            $this->model('image_generation', 'image', 'comfyui', '/v1/images/generate', ['ai-assistant', 'page-builder'], ['user', 'admin', 'super_admin'], 'medium', 'SDXL / ComfyUI'),
            $this->model('fast_image_generation', 'image', 'comfyui', '/v1/images/fast-generate', ['ai-assistant', 'page-builder'], ['user', 'admin', 'super_admin'], 'medium', 'SDXL-Lightning'),
            $this->model('vision_analysis', 'vision', 'ollama', '/v1/vision/analyze', ['ai-assistant', 'artwork-commerce-ai'], ['user', 'admin', 'super_admin'], 'medium', 'llava:7b'),
            $this->model('embedding', 'embedding', 'sentence-transformers', '/v1/rag/index', ['ai-core', 'professional-programmer', 'ai-assistant'], ['admin', 'super_admin'], 'medium', 'paraphrase-multilingual-MiniLM-L12-v2'),
            $this->model('artwork_similarity', 'similarity', 'clip', '/v1/artwork/search', ['ai-assistant', 'artwork-commerce-ai'], ['user', 'admin', 'super_admin'], 'medium', 'clip-ViT-B-32'),
        ];
    }

    private function model(string $slug, string $type, string $backend, string $endpoint, array $plugins, array $roles, string $risk, string $modelName, bool $approval = false): array
    {
        return [
            'slug' => $slug,
            'type' => $type,
            'backend' => $backend,
            'endpoint' => $endpoint,
            'enabled' => true,
            'allowed_plugins' => json_encode($plugins),
            'allowed_roles' => json_encode($roles),
            'requires_approval' => $approval,
            'risk_level' => $risk,
            'context_policy' => json_encode(['model' => $modelName, 'must_preserve_conversation_context' => true]),
            'dataset_policy' => json_encode(['database_direct_access' => false, 'authorized_laravel_context_only' => true]),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function datasets(): array
    {
        return [
            $this->dataset('assistant_public_knowledge', 'ai-assistant', 'platform_content', ['guest', 'user', 'admin', 'super_admin'], ['general_chat', 'rag_search'], 'assistant_public_knowledge', 'public'),
            $this->dataset('programmer_laravel_knowledge', 'professional-programmer', 'codebase_metadata', ['admin', 'developer', 'super_admin'], ['coding_chat', 'training_job_create', 'training_job_status'], 'programmer_laravel_knowledge', 'restricted'),
            $this->dataset('artwork_market_knowledge', 'artwork-commerce-ai', 'market_data', ['user', 'admin', 'super_admin'], ['rag_search', 'artwork_search'], 'artwork_market_knowledge', 'internal'),
            $this->dataset('staff_reports_knowledge', 'staff-reports-ai', 'reports', ['admin', 'super_admin'], ['rag_search', 'general_chat'], 'staff_reports_knowledge', 'confidential'),
        ];
    }

    private function dataset(string $slug, string $owner, string $source, array $roles, array $tools, string $collection, string $privacy): array
    {
        return [
            'slug' => $slug,
            'owner_plugin' => $owner,
            'source_type' => $source,
            'allowed_roles' => json_encode($roles),
            'allowed_tools' => json_encode($tools),
            'rag_collection' => $collection,
            'privacy_level' => $privacy,
            'indexing_status' => 'not_indexed',
            'last_indexed_at' => null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tools(): array
    {
        return [
            $this->tool('general_chat', '/v1/general/chat', 'ai-core.tools.execute', 'low'),
            $this->tool('coding_chat', '/v1/coding/chat', 'professional-programmer.chat', 'high', true),
            $this->tool('image_generate', '/v1/images/generate', 'ai-core.tools.execute', 'medium'),
            $this->tool('image_fast_generate', '/v1/images/fast-generate', 'ai-core.tools.execute', 'medium'),
            $this->tool('image_job_poll', '/v1/images/jobs/{job_id}', 'ai-core.tools.execute', 'low'),
            $this->tool('vision_analyze', '/v1/vision/analyze', 'ai-core.tools.execute', 'medium'),
            $this->tool('rag_index', '/v1/rag/index', 'ai-core.manage', 'high', true),
            $this->tool('rag_search', '/v1/rag/search', 'ai-core.tools.execute', 'medium'),
            $this->tool('artwork_index', '/v1/artwork/index', 'ai-core.manage', 'high', true),
            $this->tool('artwork_search', '/v1/artwork/search', 'ai-core.tools.execute', 'medium'),
            $this->tool('intent_classify', '/v1/router/intent', 'ai-core.tools.execute', 'low'),
            $this->tool('training_job_create', '/v1/coding/training/jobs', 'professional-programmer.manage', 'high', true),
            $this->tool('training_job_status', '/v1/coding/training/status', 'professional-programmer.manage', 'medium'),
        ];
    }

    private function tool(string $slug, string $endpoint, string $permission, string $risk, bool $approval = false): array
    {
        return [
            'slug' => $slug,
            'plugin_owner' => 'ai-core',
            'endpoint' => $endpoint,
            'input_schema' => json_encode(['type' => 'object']),
            'output_schema' => json_encode(['type' => 'object']),
            'required_permission' => $permission,
            'risk_level' => $risk,
            'requires_approval' => $approval,
            'enabled' => true,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function toolPermissions(): array
    {
        $rows = [];
        foreach (['ai-assistant', 'professional-programmer', 'page-builder', 'artwork-commerce-ai', 'staff-reports-ai'] as $plugin) {
            foreach (['admin', 'super_admin'] as $role) {
                foreach (['general_chat', 'image_generate', 'image_fast_generate', 'image_job_poll', 'vision_analyze', 'rag_search', 'artwork_search', 'intent_classify'] as $tool) {
                    $rows[] = $this->toolPermission($tool, $plugin, $role, true);
                }
            }
        }

        foreach (['coding_chat', 'training_job_create', 'training_job_status', 'rag_index', 'artwork_index'] as $tool) {
            foreach (['developer', 'admin', 'super_admin'] as $role) {
                $rows[] = $this->toolPermission($tool, 'professional-programmer', $role, true, in_array($tool, ['coding_chat', 'training_job_create', 'rag_index', 'artwork_index'], true));
            }
        }

        foreach (['general_chat', 'image_generate', 'image_fast_generate', 'image_job_poll', 'vision_analyze', 'rag_search', 'artwork_search', 'intent_classify'] as $tool) {
            $rows[] = $this->toolPermission($tool, 'ai-assistant', 'user', true);
            $rows[] = $this->toolPermission($tool, 'ai-assistant', 'guest', $tool !== 'artwork_search');
        }

        return $rows;
    }

    private function toolPermission(string $tool, ?string $plugin, ?string $role, bool $allowed, bool $approval = false): array
    {
        return [
            'tool_slug' => $tool,
            'plugin_slug' => $plugin,
            'role_slug' => $role,
            'permission' => null,
            'allowed' => $allowed,
            'requires_approval' => $approval,
            'limits' => json_encode([]),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function usageLimits(): array
    {
        return [
            $this->usage('general_chat', null, 'ai-assistant', 100),
            $this->usage('image_generate', null, 'ai-assistant', 4),
            $this->usage('image_fast_generate', null, 'ai-assistant', 10),
            $this->usage('vision_analyze', null, 'ai-assistant', 20),
            $this->usage('artwork_search', null, 'ai-assistant', 20),
            $this->usage('coding_chat', 'admin', 'professional-programmer', 100),
            $this->usage('training_job_create', 'admin', 'professional-programmer', 5),
        ];
    }

    private function usage(string $tool, ?string $role, ?string $plugin, int $daily): array
    {
        return [
            'tool_slug' => $tool,
            'role_slug' => $role,
            'plugin_slug' => $plugin,
            'daily_limit' => $daily,
            'hourly_limit' => null,
            'minute_limit' => null,
            'enabled' => true,
        ];
    }
};
