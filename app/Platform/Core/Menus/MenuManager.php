<?php

namespace App\Platform\Core\Menus;

use App\Models\User;
use App\Platform\Core\Access\RouteAccessGate;
use App\Platform\Core\Models\MenuItem;
use App\Platform\Core\Models\Plugin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class MenuManager
{
    public function __construct(
        private readonly MenuRepository $menus,
        private readonly MenuRegistrar $registrar,
        private readonly MenuVisibilityResolver $visibility,
        private readonly RouteAccessGate $routeAccess,
    ) {
        //
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMenu(string $location, ?User $user = null): array
    {
        return $this->menus->activeByLocation($location)
            ->flatMap(fn ($menu) => $this->tree($menu->items, $user))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAdminMenu(?User $user = null): array
    {
        $platformAdminMenu = $this->menus->activeByKey('platform.admin', 'admin');

        if ($platformAdminMenu !== null) {
            $platformItems = $this->tree($platformAdminMenu->items, $user);
            $pluginItems = $this->menus->activeByLocation('admin')
                ->reject(fn ($menu): bool => $menu->key === 'platform.admin')
                ->flatMap(fn ($menu) => $this->tree($menu->items, $user))
                ->values()
                ->all();

            return $this->groupAdminItems(array_values([...$platformItems, ...$pluginItems]), $user);
        }

        return $this->groupAdminItems($this->getMenu('admin', $user), $user);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFrontendMenu(?User $user = null): array
    {
        return $this->getMenu('frontend', $user);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFrontendMenuByKey(string $key, ?User $user = null): array
    {
        $menu = $this->menus->activeByKey($key, 'frontend');

        return $menu !== null ? $this->tree($menu->items, $user) : [];
    }

    /**
     * @param  array<string, mixed>  $menuDefinition
     */
    public function register(array $menuDefinition): void
    {
        $this->registrar->register($menuDefinition);
    }

    /**
     * @param  array<int, mixed>  $menus
     */
    public function registerPluginMenus(Plugin $plugin, array $menus): void
    {
        $this->syncPluginMenus($plugin, $menus);
    }

    /**
     * @param  array<int, mixed>  $menus
     */
    public function syncPluginMenus(Plugin $plugin, array $menus): void
    {
        $this->registrar->syncPluginMenus($plugin, $menus);
    }

    public function removePluginMenus(Plugin $plugin): int
    {
        return $this->menus->deletePluginMenus($plugin);
    }

    public function hidePluginMenus(Plugin $plugin): int
    {
        return $this->menus->setPluginMenusActive($plugin, false);
    }

    public function showPluginMenus(Plugin $plugin): int
    {
        return $this->menus->setPluginMenusActive($plugin, true);
    }

    /**
     * @param  iterable<int, MenuItem>  $items
     * @return array<int, array<string, mixed>>
     */
    private function tree(iterable $items, ?User $user): array
    {
        $grouped = collect($items)->groupBy(fn (MenuItem $item): int => $item->parent_id ?? 0);

        return $this->childrenFor(0, $grouped, $user);
    }

    /**
     * @param  Collection<int|string, Collection<int, MenuItem>>  $grouped
     * @return array<int, array<string, mixed>>
     */
    private function childrenFor(int $parentId, $grouped, ?User $user): array
    {
        return ($grouped->get($parentId) ?? collect())
            ->sortBy([
                ['sort_order', 'asc'],
                ['id', 'asc'],
            ])
            ->map(function (MenuItem $item) use ($grouped, $user): ?array {
                $children = $this->childrenFor($item->id, $grouped, $user);
                $visible = $this->visibility->visible($item, $user);
                $hasTarget = $item->url !== null || $item->route_name !== null;

                if (! $visible) {
                    return null;
                }

                if (! $hasTarget && $children === []) {
                    return null;
                }

                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'label' => $item->label ?: $item->title,
                    'type' => $item->type,
                    'url' => $item->url,
                    'route_name' => $item->route_name,
                    'route_params' => $item->route_params ?? [],
                    'icon' => $item->icon,
                    'target' => $item->target,
                    'permission' => $item->permission,
                    'metadata' => $item->metadata ?? [],
                    'sort_order' => $item->sort_order,
                    'children' => $children,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function groupAdminItems(array $items, ?User $user): array
    {
        $items = $this->withSyntheticAdminItems($items, $user);
        $groups = $this->adminGroupDefinitions();

        foreach ($items as $item) {
            $groupKey = $this->adminGroupKeyFor($item);

            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = $this->adminGroupDefinition(
                    $groupKey,
                    Str::headline(str_replace(['-', '_'], ' ', $groupKey)),
                    strtoupper(substr($groupKey, 0, 1)),
                    900
                );
            }

            $groups[$groupKey]['children'][] = $item;
        }

        return collect($groups)
            ->map(function (array $group): ?array {
                $children = collect($group['children'])
                    ->map(fn (array $item): array => $this->sortAdminItemChildren($item))
                    ->sortBy(fn (array $item): array => [
                        $this->adminItemSort($item),
                        (int) ($item['id'] ?? 0),
                        (string) ($item['label'] ?? $item['title'] ?? ''),
                    ])
                    ->values()
                    ->all();

                if ($children === []) {
                    return null;
                }

                $group['children'] = $children;

                return $group;
            })
            ->filter()
            ->sortBy('sort_order')
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function adminGroupDefinitions(): array
    {
        return [
            'overview' => $this->adminGroupDefinition('overview', 'Overview', 'O', 10),
            'content-management' => $this->adminGroupDefinition('content-management', 'Content Management', 'C', 20),
            'platform' => $this->adminGroupDefinition('platform', 'Platform', 'P', 30),
            'ai-tools' => $this->adminGroupDefinition('ai-tools', 'AI Tools', 'AI', 40),
            'users-access' => $this->adminGroupDefinition('users-access', 'Users & Access', 'U', 50),
            'system' => $this->adminGroupDefinition('system', 'System', 'S', 60),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminGroupDefinition(string $key, string $label, string $icon, int $sortOrder): array
    {
        return [
            'id' => 'admin-group-'.$key,
            'title' => $label,
            'label' => $label,
            'type' => 'group',
            'url' => null,
            'route_name' => null,
            'route_params' => [],
            'icon' => $icon,
            'target' => null,
            'permission' => null,
            'metadata' => [
                'admin_group_key' => $key,
                'is_admin_group' => true,
            ],
            'sort_order' => $sortOrder,
            'children' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function adminGroupKeyFor(array $item): string
    {
        $metadata = is_array($item['metadata'] ?? null) ? $item['metadata'] : [];
        $explicit = $metadata['admin_group'] ?? $metadata['group'] ?? null;

        if (is_string($explicit) && trim($explicit) !== '') {
            return Str::slug($explicit);
        }

        return match ((string) ($item['route_name'] ?? '')) {
            'dashboard',
            'admin.documentation.index' => 'overview',
            'admin.pages.index',
            'admin.theme-builder.index',
            'admin.menus.index',
            'admin.media.index',
            'admin.plugins.blog.index' => 'content-management',
            'admin.platform-registry.index',
            'admin.plugins.index',
            'admin.plugins.create',
            'admin.plugins.theme-manager.index',
            'admin.plugins.theme-editor.index' => 'platform',
            'admin.plugins.ai-core.index',
            'admin.plugins.ai-assistant.settings.edit',
            'admin.plugins.professional-programmer.index',
            'admin.plugins.professional-programmer.alerts' => 'ai-tools',
            'admin.users.index',
            'admin.roles.index',
            'admin.permissions.index' => 'users-access',
            'admin.settings.index',
            'admin.backups.index' => 'system',
            default => str_starts_with((string) ($item['route_name'] ?? ''), 'admin.plugins.')
                ? 'platform'
                : 'system',
        };
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function adminItemSort(array $item): int
    {
        $metadata = is_array($item['metadata'] ?? null) ? $item['metadata'] : [];

        foreach (['admin_sort_order', 'admin_order', 'group_sort_order'] as $key) {
            if (isset($metadata[$key]) && is_numeric($metadata[$key])) {
                return (int) $metadata[$key];
            }
        }

        return match ((string) ($item['route_name'] ?? '')) {
            'dashboard' => 10,
            'admin.documentation.index' => 20,
            'admin.pages.index' => 10,
            'admin.theme-builder.index' => 15,
            'admin.menus.index' => 20,
            'admin.media.index' => 30,
            'admin.plugins.blog.index' => 40,
            'admin.plugins.blog.posts.index' => 10,
            'admin.plugins.blog.posts.create' => 20,
            'admin.plugins.blog.categories.index' => 30,
            'admin.plugins.blog.categories.create' => 40,
            'admin.plugins.blog.settings.edit' => 50,
            'admin.platform-registry.index' => 10,
            'admin.plugins.index' => 20,
            'admin.plugins.create' => 30,
            'admin.plugins.theme-manager.index' => 40,
            'admin.plugins.theme-editor.index' => 50,
            'admin.plugins.ai-core.index' => 10,
            'admin.plugins.ai-assistant.settings.edit' => 20,
            'admin.plugins.professional-programmer.index' => 30,
            'admin.plugins.professional-programmer.alerts' => 40,
            'admin.users.index' => 10,
            'admin.roles.index' => 20,
            'admin.permissions.index' => 30,
            'admin.settings.index' => 10,
            'admin.backups.index' => 20,
            default => (int) ($item['sort_order'] ?? 500),
        };
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function sortAdminItemChildren(array $item): array
    {
        if (! is_array($item['children'] ?? null) || $item['children'] === []) {
            return $item;
        }

        $item['children'] = collect($item['children'])
            ->map(fn (array $child): array => $this->sortAdminItemChildren($child))
            ->sortBy(fn (array $child): array => [
                $this->adminItemSort($child),
                (int) ($child['id'] ?? 0),
                (string) ($child['label'] ?? $child['title'] ?? ''),
            ])
            ->values()
            ->all();

        return $item;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function withSyntheticAdminItems(array $items, ?User $user): array
    {
        $items = $this->ensureAdminItem($items, $user, [
            'title' => 'Install Plugin',
            'label' => 'Install Plugin',
            'route_name' => 'admin.plugins.create',
            'permission' => 'plugins.install',
            'icon' => 'I',
            'sort_order' => 30,
            'metadata' => ['admin_group' => 'Platform', 'admin_sort_order' => 30],
        ]);

        $items = $this->ensureAdminChild($items, $user, 'admin.plugins.blog.index', [
            'title' => 'Blog Settings',
            'label' => 'Blog Settings',
            'route_name' => 'admin.plugins.blog.settings.edit',
            'permission' => 'blog.update',
            'icon' => 'B',
            'sort_order' => 50,
            'metadata' => ['admin_group' => 'Content Management', 'admin_sort_order' => 50],
        ]);

        $items = $this->ensureAdminItem($items, $user, [
            'title' => 'Theme Builder',
            'label' => 'Theme Builder',
            'route_name' => 'admin.theme-builder.index',
            'permission' => 'theme-builder.manage',
            'icon' => 'T',
            'sort_order' => 15,
            'metadata' => ['admin_group' => 'Content Management', 'admin_sort_order' => 15],
        ]);

        $items = $this->ensureAdminItem($items, $user, [
            'title' => 'Backup',
            'label' => 'Backup',
            'route_name' => 'admin.backups.index',
            'permission' => 'platform-registry.view',
            'icon' => 'B',
            'sort_order' => 20,
            'metadata' => ['admin_group' => 'System', 'admin_sort_order' => 20],
        ]);

        return $this->ensureAdminItem($items, $user, [
            'title' => 'Professional Programmer Alerts',
            'label' => 'Professional Programmer Alerts',
            'route_name' => 'admin.plugins.professional-programmer.alerts',
            'permission' => 'professional-programmer.manage',
            'icon' => 'PA',
            'sort_order' => 40,
            'metadata' => ['admin_group' => 'AI Tools', 'admin_sort_order' => 40],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $item
     * @return array<int, array<string, mixed>>
     */
    private function ensureAdminItem(array $items, ?User $user, array $item): array
    {
        if (! $this->canSeeSyntheticItem($item, $user) || ! $this->adminRouteAvailable($item)) {
            return $items;
        }

        if ($this->containsRoute($items, (string) $item['route_name'])) {
            return $items;
        }

        $items[] = $this->syntheticAdminItem($item);

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $child
     * @return array<int, array<string, mixed>>
     */
    private function ensureAdminChild(array $items, ?User $user, string $parentRoute, array $child): array
    {
        if (! $this->canSeeSyntheticItem($child, $user) || ! $this->adminRouteAvailable($child)) {
            return $items;
        }

        if ($this->containsRoute($items, (string) $child['route_name'])) {
            return $items;
        }

        foreach ($items as &$item) {
            if (($item['route_name'] ?? null) !== $parentRoute) {
                continue;
            }

            $item['children'] = is_array($item['children'] ?? null) ? $item['children'] : [];
            $item['children'][] = $this->syntheticAdminItem($child);

            return $items;
        }

        return $items;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function containsRoute(array $items, string $routeName): bool
    {
        foreach ($items as $item) {
            if (($item['route_name'] ?? null) === $routeName) {
                return true;
            }

            if (is_array($item['children'] ?? null) && $this->containsRoute($item['children'], $routeName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function adminRouteAvailable(array $item): bool
    {
        $routeName = $item['route_name'] ?? null;

        return ! is_string($routeName) || Route::has($routeName);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function canSeeSyntheticItem(array $item, ?User $user): bool
    {
        if (! $this->routeAccess->allowsRouteName($user, $item['route_name'] ?? null)) {
            return false;
        }

        $permission = $item['permission'] ?? null;

        if (! is_string($permission) || $permission === '') {
            return true;
        }

        return $user !== null && ($user->hasRole('super-admin') || $user->can($permission));
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function syntheticAdminItem(array $item): array
    {
        return [
            'id' => 'synthetic-'.Str::slug((string) ($item['route_name'] ?? $item['title'])),
            'title' => (string) ($item['title'] ?? $item['label'] ?? 'Untitled'),
            'label' => $item['label'] ?? $item['title'] ?? null,
            'type' => (string) ($item['type'] ?? 'link'),
            'url' => $item['url'] ?? null,
            'route_name' => $item['route_name'] ?? null,
            'route_params' => $item['route_params'] ?? [],
            'icon' => $item['icon'] ?? null,
            'target' => $item['target'] ?? null,
            'permission' => $item['permission'] ?? null,
            'metadata' => is_array($item['metadata'] ?? null) ? $item['metadata'] : [],
            'sort_order' => (int) ($item['sort_order'] ?? 500),
            'children' => [],
        ];
    }
}
