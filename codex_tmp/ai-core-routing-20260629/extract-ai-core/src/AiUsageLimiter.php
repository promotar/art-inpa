<?php

namespace Modules\AiCore;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiUsageLimiter
{
    public function canUse(string $toolSlug, ?Authenticatable $user = null, array $context = []): bool
    {
        if (! Schema::hasTable('ai_core_usage_limits') || ! Schema::hasTable('ai_core_requests')) {
            return true;
        }

        $pluginSlug = (string) ($context['plugin'] ?? $context['plugin_slug'] ?? '');
        $roleSlug = (string) ($context['role'] ?? '');

        $limit = DB::table('ai_core_usage_limits')
            ->where('tool_slug', $toolSlug)
            ->where(function ($query) use ($pluginSlug): void {
                $query->whereNull('plugin_slug')->orWhere('plugin_slug', $pluginSlug);
            })
            ->where(function ($query) use ($roleSlug): void {
                $query->whereNull('role_slug')->orWhere('role_slug', $roleSlug);
            })
            ->where('enabled', true)
            ->orderByRaw('role_slug is null')
            ->first();

        if (! $limit || ! $limit->daily_limit) {
            return true;
        }

        $count = DB::table('ai_core_requests')
            ->where('tool_slug', $toolSlug)
            ->when($user, fn ($query) => $query->where('user_id', $user->getAuthIdentifier()))
            ->when(! $user, fn ($query) => $query->whereNull('user_id'))
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        return $count < (int) $limit->daily_limit;
    }
}
