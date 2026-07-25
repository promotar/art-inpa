<?php

namespace Modules\AiCore;

use Illuminate\Contracts\Auth\Authenticatable;

class AiConversationBridge
{
    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function contextForPlugin(string $pluginSlug, ?Authenticatable $user = null, array $context = []): array
    {
        return array_merge($context, [
            'plugin' => $pluginSlug,
            'user' => $user ? [
                'id' => $user->getAuthIdentifier(),
                'name' => $user->name ?? null,
                'email' => $user->email ?? null,
            ] : null,
            'ai_core_policy' => [
                'database_direct_access' => false,
                'tool_execution_layer' => 'ai-core',
                'trust_user_claims' => false,
            ],
        ]);
    }
}
