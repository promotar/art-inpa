<?php

namespace Modules\AiAssistant;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AiGatewayClient
{
    public function __construct(private readonly AiAssistantSettings $settings)
    {
        //
    }

    /**
     * @param array<int, array{role:string,content:string}> $history
     */
    public function chat(string $message, array $history = [], string $userContext = ''): string
    {
        $settings = $this->settings->values();
        $apiKey = trim((string) ($settings['api_key'] ?? ''));

        if (! ($settings['enabled'] ?? true)) {
            throw new RuntimeException('AI Assistant is disabled.');
        }

        if ($apiKey === '') {
            throw new RuntimeException('AI Assistant API key is not configured.');
        }

        $url = rtrim((string) $settings['gateway_url'], '/').'/'.ltrim((string) $settings['chat_endpoint'], '/');
        $context = collect($history)
            ->take(-8)
            ->map(fn (array $entry): string => strtoupper($entry['role'] ?? 'user').': '.trim((string) ($entry['content'] ?? '')))
            ->filter()
            ->implode("\n");

        $system = trim((string) $settings['system_prompt']);
        $userContext = trim($userContext);

        if ($userContext !== '') {
            $system = trim($system."\n\nUser context:\n".$userContext);
        }

        $payload = [
            'message' => trim($message),
            'system' => $system,
            'temperature' => (float) $settings['temperature'],
            'max_tokens' => (int) $settings['max_tokens'],
        ];

        if ($context !== '') {
            $payload['message'] = "Conversation context:\n".$context."\n\nUser message:\n".$payload['message'];
        }

        $response = Http::timeout(120)
            ->acceptJson()
            ->withHeaders(['X-AI-API-KEY' => $apiKey])
            ->post($url, $payload);

        if (! $response->successful()) {
            throw new RuntimeException('AI Gateway request failed with HTTP '.$response->status().'.');
        }

        $data = $response->json();

        return $this->extractMessage($data);
    }

    private function extractMessage(mixed $data): string
    {
        if (is_string($data)) {
            return trim($data);
        }

        if (! is_array($data)) {
            return 'The AI Gateway returned an empty response.';
        }

        $candidates = [
            data_get($data, 'data.message'),
            data_get($data, 'data.response'),
            data_get($data, 'data.result'),
            data_get($data, 'message'),
            data_get($data, 'response'),
            data_get($data, 'result'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return 'The AI Gateway responded, but no readable message was returned.';
    }
}
