<?php

namespace Modules\AiCore;

class AiIntentRouter
{
    public function __construct(private readonly AiGatewayClient $gateway)
    {
        //
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function classify(string $message, array $context = []): array
    {
        return $this->gateway->classifyIntent([
            'message' => $message,
            'plugin' => $context['plugin'] ?? 'unknown',
            'context' => $context,
            'has_image' => (bool) ($context['has_image'] ?? false),
            'allowed_intents' => $context['allowed_intents'] ?? [],
        ], $context);
    }
}
