<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">AI Assistant Settings</h2>
            <p class="mt-1 text-sm text-gray-500">Configure the floating AI support assistant for the admin dashboard and frontend.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.plugins.ai-assistant.settings.update') }}" class="space-y-6 rounded-md border border-gray-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PATCH')

                <section>
                    <h3 class="text-base font-semibold text-gray-900">Visibility</h3>
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        @foreach ([
                            'enabled' => 'Enable assistant',
                            'show_frontend' => 'Show on frontend',
                            'show_admin' => 'Show on admin dashboard',
                        ] as $key => $label)
                            <label class="flex items-center gap-3 rounded-md border border-gray-200 p-3">
                                <input type="hidden" name="{{ $key }}" value="0">
                                <input type="checkbox" name="{{ $key }}" value="1" @checked((bool) ($settings[$key] ?? false))>
                                <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="gateway_url">AI Gateway URL</label>
                        <input id="gateway_url" name="gateway_url" value="{{ old('gateway_url', $settings['gateway_url'] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                        <p class="mt-1 text-xs text-gray-500">Internal gateway, usually http://10.10.0.40:8080.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="chat_endpoint">Chat Endpoint</label>
                        <input id="chat_endpoint" name="chat_endpoint" value="{{ old('chat_endpoint', $settings['chat_endpoint'] ?? '/v1/general/chat') }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700" for="api_key">AI API Key</label>
                        <input id="api_key" name="api_key" type="password" value="" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" placeholder="{{ ! empty($settings['api_key']) ? 'Configured. Leave blank to keep current key.' : 'Not configured' }}">
                        <p class="mt-1 text-xs text-gray-500">Stored only in database settings as a sensitive value. It is never printed here.</p>
                    </div>
                </section>

                <section>
                    <h3 class="text-base font-semibold text-gray-900">Widget Copy</h3>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        @foreach ([
                            'title' => 'Header title',
                            'powered_by' => 'Powered by text',
                            'greeting' => 'Greeting',
                            'headline' => 'Headline',
                            'launcher_label' => 'Launcher label',
                            'placeholder' => 'Input placeholder',
                            'full_page_title' => 'Full page title',
                        ] as $key => $label)
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="{{ $key }}">{{ $label }}</label>
                                <input id="{{ $key }}" name="{{ $key }}" value="{{ old($key, $settings[$key] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700" for="system_prompt">System Prompt</label>
                        <textarea id="system_prompt" name="system_prompt" rows="5" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" required>{{ old('system_prompt', $settings['system_prompt'] ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="max_tokens">Max Tokens</label>
                        <input id="max_tokens" name="max_tokens" type="number" min="64" max="4096" value="{{ old('max_tokens', $settings['max_tokens'] ?? 512) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="temperature">Temperature</label>
                        <input id="temperature" name="temperature" type="number" min="0" max="2" step="0.1" value="{{ old('temperature', $settings['temperature'] ?? 0.3) }}" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" required>
                    </div>
                </section>

                @if ($errors->any())
                    <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                    <a href="{{ route('ai-assistant.chat') }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">Open full chat page</a>
                    <button type="submit" class="rounded-md bg-gray-950 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
