<?php

namespace Modules\AiCore;

use Illuminate\Contracts\Auth\Authenticatable;

class AiImageJobService
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
    public function create(array $payload, bool $fast = false, array $context = [], ?Authenticatable $user = null): array
    {
        return $fast
            ? $this->gateway->generateFastImage($payload, $context, $user)
            : $this->gateway->generateImage($payload, $context, $user);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function status(string $jobId, array $context = [], ?Authenticatable $user = null): array
    {
        return $this->gateway->imageJobStatus($jobId, $context, $user);
    }
}
