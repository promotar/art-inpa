@php
    $assetBase = asset('platform/plugins/ai-assistant');
    $displayName = auth()->check()
        ? trim((string) (auth()->user()->name ?: auth()->user()->email))
        : '';
    $displayGreeting = $displayName !== '' ? 'Hi '.$displayName.'!' : $config['greeting'];
@endphp
<link rel="stylesheet" href="{{ $assetBase }}/css/ai-assistant.css" data-ai-assistant-asset="css">
<div
    class="ai-assistant-widget"
    data-ai-assistant-widget
    data-context="{{ $context }}"
    data-csrf-token="{{ csrf_token() }}"
    data-message-url="{{ $config['message_url'] }}"
    data-history-url="{{ route('ai-assistant.messages') }}"
    data-close-url="{{ route('ai-assistant.close') }}"
    data-chat-url="{{ $config['chat_url'] }}"
>
    <button class="ai-assistant-launcher" type="button" aria-expanded="false" aria-controls="ai-assistant-panel">
        <span class="ai-assistant-launcher-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v8A2.5 2.5 0 0 1 17.5 16H9l-5 4v-4.5A2.5 2.5 0 0 1 2 13V5.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                <path d="M7 8h10M7 11h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="ai-assistant-launcher-label">{{ $config['launcher_label'] }}</span>
    </button>

    <section class="ai-assistant-panel" id="ai-assistant-panel" hidden>
        <header class="ai-assistant-header">
            <div>
                <h2>{{ $config['title'] }}</h2>
                @if (! empty($config['powered_by']))
                    <p>{{ $config['powered_by'] }}</p>
                @endif
            </div>
            <div class="ai-assistant-header-actions">
                <a href="{{ $config['chat_url'] }}" class="ai-assistant-icon-button" title="Open full chat page" aria-label="Open full chat page">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M8 4h12v12M20 4 9 15M5 8v12h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <button class="ai-assistant-icon-button" type="button" data-ai-assistant-minimize title="Minimize" aria-label="Minimize">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <button class="ai-assistant-icon-button" type="button" data-ai-assistant-close title="Close" aria-label="Close">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
        </header>

        <div class="ai-assistant-body">
            <div class="ai-assistant-intro">
                <p>{{ $displayGreeting }}</p>
                <strong>{{ $config['headline'] }}</strong>
            </div>
            <div class="ai-assistant-messages" data-ai-assistant-messages aria-live="polite"></div>
        </div>

        <form class="ai-assistant-form" data-ai-assistant-form>
            <input type="text" name="message" autocomplete="off" maxlength="4000" placeholder="{{ $config['placeholder'] }}" required>
            <button type="submit" aria-label="Send message">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="m21 3-8.5 18-2.7-7.8L2 10.5 21 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                    <path d="m10 13 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </button>
        </form>
        <p class="ai-assistant-disclaimer">AI can make mistakes. Review important answers.</p>
    </section>
</div>
<script src="{{ $assetBase }}/js/ai-assistant.js" defer data-ai-assistant-asset="js"></script>
