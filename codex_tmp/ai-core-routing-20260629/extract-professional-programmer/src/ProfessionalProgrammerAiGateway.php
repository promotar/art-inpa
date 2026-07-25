<?php

namespace Modules\ProfessionalProgrammer;

use Illuminate\Contracts\Auth\Authenticatable;
use RuntimeException;

class ProfessionalProgrammerAiGateway
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function chatCoding(array $payload, ?object $user = null): array
    {
        $this->assertAiCore();

        $payload['plugin'] = $payload['plugin'] ?? 'professional-programmer';
        $authUser = $user instanceof Authenticatable ? $user : null;
        $aiCore = app(\Modules\AiCore\AiCore::class);
        $permission = $aiCore->checkToolPermission('coding_chat', $authUser, [
            'plugin' => 'professional-programmer',
        ]);

        if (! $permission['allowed']) {
            throw new RuntimeException('AI Core permission denied: '.$permission['reason']);
        }

        return $aiCore->chatCoding($payload, [
            'plugin' => 'professional-programmer',
        ], $authUser);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function trainingJobCreate(array $payload, ?object $user = null): array
    {
        $this->assertAiCore();

        $authUser = $user instanceof Authenticatable ? $user : null;
        $aiCore = app(\Modules\AiCore\AiCore::class);
        $permission = $aiCore->checkToolPermission('training_job_create', $authUser, [
            'plugin' => 'professional-programmer',
        ]);

        if (! $permission['allowed']) {
            throw new RuntimeException('AI Core permission denied: '.$permission['reason']);
        }

        return $aiCore->createTrainingJob($payload, [
            'plugin' => 'professional-programmer',
            'training_profile' => $aiCore->getTrainingProfile('professional-programmer'),
        ], $authUser);
    }

    /**
     * @return array<string, mixed>
     */
    public function trainingJobStatus(?object $user = null): array
    {
        $this->assertAiCore();

        $authUser = $user instanceof Authenticatable ? $user : null;
        $aiCore = app(\Modules\AiCore\AiCore::class);

        return $aiCore->getTrainingJobStatus([
            'plugin' => 'professional-programmer',
            'training_profile' => $aiCore->getTrainingProfile('professional-programmer'),
        ], $authUser);
    }

    private function assertAiCore(): void
    {
        if (! class_exists(\Modules\AiCore\AiCore::class)) {
            throw new RuntimeException('AI Core is required for Professional Programmer AI execution.');
        }

        app(\Modules\AiCore\AiCore::class)->assertAvailable();
    }
}
