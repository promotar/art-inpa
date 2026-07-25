<?php

namespace Modules\AiCore;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiPermissionService
{
    /**
     * @param array<string, mixed> $context
     * @return array{allowed: bool, reason: string}
     */
    public function authorizeTool(string $toolSlug, ?Authenticatable $user = null, array $context = []): array
    {
        $pluginSlug = (string) ($context['plugin'] ?? $context['plugin_slug'] ?? '');
        $roles = $this->roles($user);

        if (! Schema::hasTable('ai_core_tool_permissions')) {
            return ['allowed' => true, 'reason' => 'ai_core_tool_permissions_table_missing_fallback_allowed'];
        }

        $query = DB::table('ai_core_tool_permissions')->where('tool_slug', $toolSlug);
        if ($pluginSlug !== '') {
            $query->where(function ($nested) use ($pluginSlug): void {
                $nested->whereNull('plugin_slug')->orWhere('plugin_slug', $pluginSlug);
            });
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return ['allowed' => false, 'reason' => 'tool_not_allowed_for_plugin'];
        }

        foreach ($rows as $row) {
            $role = $row->role_slug ? (string) $row->role_slug : null;
            if (($role === null || in_array($role, $roles, true)) && (bool) $row->allowed) {
                return ['allowed' => true, 'reason' => 'allowed_by_ai_core_permission_matrix'];
            }
        }

        return ['allowed' => false, 'reason' => 'user_role_not_allowed_for_tool'];
    }

    /**
     * @return array<int, string>
     */
    public function roles(?Authenticatable $user): array
    {
        if (! $user) {
            return ['guest'];
        }

        $roles = ['user'];
        foreach (['role', 'role_slug', 'type'] as $field) {
            if (isset($user->{$field}) && is_string($user->{$field}) && $user->{$field} !== '') {
                $roles[] = $user->{$field};
            }
        }

        if (method_exists($user, 'hasRole')) {
            foreach (['developer', 'admin', 'super_admin', 'super-admin'] as $role) {
                try {
                    if ($user->hasRole($role)) {
                        $roles[] = $role;
                    }
                } catch (\Throwable) {
                    //
                }
            }
        }

        if (method_exists($user, 'getRoleNames')) {
            try {
                foreach ($user->getRoleNames() as $role) {
                    if (is_string($role) && $role !== '') {
                        $roles[] = $role;
                    }
                }
            } catch (\Throwable) {
                //
            }
        }

        if (isset($user->is_admin) && $user->is_admin) {
            $roles[] = 'admin';
        }

        foreach ($roles as $role) {
            if (is_string($role) && str_contains($role, '-')) {
                $roles[] = str_replace('-', '_', $role);
            }
            if (is_string($role) && str_contains($role, '_')) {
                $roles[] = str_replace('_', '-', $role);
            }
        }

        return array_values(array_unique($roles));
    }
}
