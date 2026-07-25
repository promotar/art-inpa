<?php

namespace Modules\AiAssistant;

use App\Http\Controllers\Controller;
use App\Platform\Core\Services\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAssistantAdminController extends Controller
{
    public function edit(AiAssistantSettings $assistantSettings): View
    {
        return view('ai-assistant::settings', [
            'settings' => $assistantSettings->values(),
        ]);
    }

    public function update(Request $request, SettingsRepository $settings): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'show_frontend' => ['nullable', 'boolean'],
            'show_admin' => ['nullable', 'boolean'],
            'gateway_url' => ['required', 'url', 'max:255'],
            'chat_endpoint' => ['required', 'string', 'max:120'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'title' => ['required', 'string', 'max:80'],
            'powered_by' => ['nullable', 'string', 'max:120'],
            'greeting' => ['required', 'string', 'max:120'],
            'headline' => ['required', 'string', 'max:180'],
            'launcher_label' => ['required', 'string', 'max:80'],
            'placeholder' => ['required', 'string', 'max:80'],
            'full_page_title' => ['required', 'string', 'max:120'],
            'system_prompt' => ['required', 'string', 'max:2000'],
            'max_tokens' => ['required', 'integer', 'min:64', 'max:4096'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:2'],
        ]);

        $input = [
            'enabled' => $request->boolean('enabled'),
            'show_frontend' => $request->boolean('show_frontend'),
            'show_admin' => $request->boolean('show_admin'),
            'gateway_url' => $data['gateway_url'],
            'chat_endpoint' => '/'.ltrim((string) $data['chat_endpoint'], '/'),
            'title' => $data['title'],
            'powered_by' => $data['powered_by'] ?? '',
            'greeting' => $data['greeting'],
            'headline' => $data['headline'],
            'launcher_label' => $data['launcher_label'],
            'placeholder' => $data['placeholder'],
            'full_page_title' => $data['full_page_title'],
            'system_prompt' => $data['system_prompt'],
            'max_tokens' => (string) $data['max_tokens'],
            'temperature' => (string) $data['temperature'],
        ];

        if (trim((string) ($data['api_key'] ?? '')) !== '') {
            $input['api_key'] = trim((string) $data['api_key']);
        }

        $settings->update(
            ['ai_assistant' => $input],
            [],
            [],
            [],
            $request->user()?->id,
            'admin.plugins.ai-assistant.settings',
        );

        return back()->with('status', 'AI Assistant settings saved successfully.');
    }
}
