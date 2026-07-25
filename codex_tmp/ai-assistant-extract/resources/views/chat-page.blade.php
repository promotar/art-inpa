<!DOCTYPE html>
@php
    $displayName = auth()->check()
        ? trim((string) (auth()->user()->name ?: auth()->user()->email))
        : '';
    $displayGreeting = $displayName !== '' ? 'Hi '.$displayName.'!' : $config['greeting'];
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $config['full_page_title'] }}</title>
        <link rel="stylesheet" href="{{ asset('platform/plugins/ai-assistant/css/ai-assistant.css') }}">
    </head>
    <body class="ai-assistant-page-body">
        <main
            class="ai-assistant-full-page"
            data-ai-assistant-full
            data-csrf-token="{{ csrf_token() }}"
            data-message-url="{{ $config['message_url'] }}"
            data-history-url="{{ route('ai-assistant.messages') }}"
            data-close-url="{{ route('ai-assistant.close') }}"
        >
            <header class="ai-assistant-full-header">
                <div>
                    <p>{{ $config['powered_by'] }}</p>
                    <h1>{{ $config['full_page_title'] }}</h1>
                </div>
                <a href="{{ url('/') }}">Back to site</a>
            </header>

            <section class="ai-assistant-full-shell">
                <div class="ai-assistant-intro">
                    <p>{{ $displayGreeting }}</p>
                    <strong>{{ $config['headline'] }}</strong>
                </div>
                <div class="ai-assistant-messages" data-ai-assistant-messages aria-live="polite"></div>
                <form class="ai-assistant-form ai-assistant-full-form" data-ai-assistant-form>
                    <input type="text" name="message" autocomplete="off" maxlength="4000" placeholder="{{ $config['placeholder'] }}" required autofocus>
                    <button type="submit" aria-label="Send message">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="m21 3-8.5 18-2.7-7.8L2 10.5 21 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="m10 13 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </button>
                </form>
            </section>
        </main>
        <script src="{{ asset('platform/plugins/ai-assistant/js/ai-assistant.js') }}" defer></script>
    </body>
</html>
