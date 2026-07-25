<?php

namespace Modules\AiCore;

use Illuminate\Contracts\Auth\Authenticatable;

class AiVisionService
{
    public function __construct(private readonly AiGatewayClient $gateway)
    {
        //
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function analyze(array $payload, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->gateway->analyzeVision($payload, $context, $user);
    }
}
