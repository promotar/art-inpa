<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_assistant_conversations')) {
            Schema::create('ai_assistant_conversations', function (Blueprint $table): void {
                $table->id();
                $table->string('session_id')->nullable()->index();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('source', 40)->default('frontend')->index();
                $table->string('ip_address', 80)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ai_assistant_messages')) {
            Schema::create('ai_assistant_messages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('conversation_id')->constrained('ai_assistant_conversations')->cascadeOnDelete();
                $table->string('role', 40)->index();
                $table->longText('content');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        $this->registerSettings();
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_settings')) {
            DB::table('platform_settings')->where('module', 'ai-assistant')->delete();
        }

        Schema::dropIfExists('ai_assistant_messages');
        Schema::dropIfExists('ai_assistant_conversations');
    }

    private function registerSettings(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        $settings = [
            'enabled' => ['AI Assistant Enabled', 'boolean', true, 'Enable or disable the AI Assistant plugin globally.', 10, 'toggle'],
            'show_frontend' => ['Show On Frontend', 'boolean', true, 'Show the floating AI Assistant widget on frontend pages.', 20, 'toggle'],
            'show_admin' => ['Show On Admin Dashboard', 'boolean', true, 'Show the floating AI Assistant widget on admin dashboard pages.', 30, 'toggle'],
            'gateway_url' => ['AI Gateway URL', 'text', 'http://10.10.0.40:8080', 'Internal Art INPA AI Gateway base URL.', 40, 'text'],
            'chat_endpoint' => ['Chat Endpoint', 'text', '/v1/general/chat', 'Gateway endpoint used for general assistant chat.', 50, 'text'],
            'api_key' => ['AI API Key', 'password', '', 'Sensitive API key sent as X-AI-API-KEY to the internal AI Gateway.', 60, 'password', true],
            'title' => ['Widget Title', 'text', 'Support', 'Header title shown in the floating chat window.', 70, 'text'],
            'powered_by' => ['Powered By Text', 'text', 'Powered by Art INPA AI', 'Small powered-by label shown in the chat header.', 80, 'text'],
            'greeting' => ['Greeting', 'text', 'Hi!', 'Greeting shown when the chat opens.', 90, 'text'],
            'headline' => ['Headline', 'text', 'I can help you with platform support.', 'Main assistant headline shown in the chat window.', 100, 'text'],
            'launcher_label' => ['Launcher Label', 'text', 'Contact Us', 'Text shown next to the floating chat icon.', 110, 'text'],
            'placeholder' => ['Input Placeholder', 'text', 'Type a message...', 'Placeholder for the chat input.', 120, 'text'],
            'full_page_title' => ['Full Page Title', 'text', 'AI Assistant', 'Title shown on the full chat page.', 130, 'text'],
            'system_prompt' => ['System Prompt', 'textarea', 'You are Art INPA AI Assistant. Be concise, helpful, and production-support oriented.', 'Instruction sent to the AI Gateway with each chat request.', 140, 'textarea'],
            'max_tokens' => ['Max Tokens', 'number', '512', 'Maximum generated tokens per response.', 150, 'number'],
            'temperature' => ['Temperature', 'number', '0.3', 'Assistant response creativity level.', 160, 'number'],
        ];

        foreach ($settings as $key => $definition) {
            [$label, $type, $default, $description, $sortOrder, $component, $sensitive] = array_pad($definition, 7, false);

            $payload = [
                'group_key' => 'ai_assistant',
                'setting_key' => $key,
                'label' => $label,
                'type' => $type,
                'default_value' => json_encode($default, JSON_UNESCAPED_SLASHES),
                'options' => null,
                'help_text' => $description,
                'sort_order' => $sortOrder,
                'is_public' => false,
                'validation_rules' => json_encode([]),
                'description' => $description,
                'category' => 'ai_assistant',
                'module' => 'ai-assistant',
                'visibility_level' => $sensitive ? 'sensitive' : 'admin',
                'admin_access_level' => 'manage_settings',
                'editable' => true,
                'required' => in_array($key, ['gateway_url', 'chat_endpoint'], true),
                'sensitive_flag' => (bool) $sensitive,
                'public_exposure_allowed' => false,
                'frontend_available' => in_array($key, ['enabled', 'show_frontend', 'show_admin', 'title', 'powered_by', 'greeting', 'headline', 'launcher_label', 'placeholder', 'full_page_title'], true),
                'cache_enabled' => true,
                'cache_ttl' => null,
                'ui_component' => $component,
                'ui_label' => $label,
                'allowed_values' => json_encode([]),
                'min_value' => in_array($key, ['max_tokens', 'temperature'], true) ? ($key === 'temperature' ? '0' : '64') : null,
                'max_value' => in_array($key, ['max_tokens', 'temperature'], true) ? ($key === 'temperature' ? '2' : '4096') : null,
                'unit' => $key === 'max_tokens' ? 'tokens' : null,
                'depends_on' => json_encode([]),
                'restart_required' => false,
                'approval_required' => $sensitive,
                'status' => 'active',
                'version' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ];

            $payload = collect($payload)
                ->filter(fn (mixed $value, string $column): bool => Schema::hasColumn('platform_settings', $column))
                ->all();

            DB::table('platform_settings')->updateOrInsert(
                ['group_key' => 'ai_assistant', 'setting_key' => $key],
                $payload,
            );
        }
    }
};
