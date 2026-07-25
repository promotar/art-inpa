<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Platform\Core\Logs\OperationLogger;
use App\Platform\Core\Models\Menu;
use App\Platform\Core\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class MenuSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureDefaultMenus();

        $activeLocation = in_array($request->query('location'), ['frontend', 'admin'], true)
            ? (string) $request->query('location')
            : 'admin';
        $activeMenuId = (int) $request->query('menu', 0);
        $menus = Menu::query()
            ->with(['items' => fn ($query) => $query->orderByRaw('parent_id IS NOT NULL')->orderBy('parent_id')->orderBy('sort_order')->orderBy('id')])
            ->whereIn('location', ['frontend', 'admin'])
            ->orderBy('location')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('location');

        return view('admin.menus.index', [
            'activeLocation' => $activeLocation,
            'activeMenuId' => $activeMenuId,
            'menus' => $menus,
            'builderContent' => $this->builderContent(),
            'permissions' => Permission::query()->orderBy('name')->pluck('name'),
            'routeNames' => collect(Route::getRoutes())
                ->map(fn ($route) => $route->getName())
                ->filter()
                ->unique()
                ->sort()
                ->values(),
        ]);
    }

    public function store(Request $request, string $location): RedirectResponse
    {
        abort_unless(in_array($location, ['frontend', 'admin'], true), 404);
        $menu = $this->menuFor($location);

        return $this->storeItem($request, $menu);
    }

    public function storeForMenu(Request $request, Menu $menu): RedirectResponse
    {
        abort_unless(in_array($menu->location, ['frontend', 'admin'], true), 404);

        return $this->storeItem($request, $menu);
    }

    public function storeMenu(Request $request, string $location): RedirectResponse
    {
        abort_unless($location === 'frontend', 404);

        $data = $this->validatedMenu($request);
        $key = $this->uniqueMenuKey($data['key'] ?: $data['name'], $location);

        $menu = Menu::query()->create([
            'key' => $key,
            'name' => $data['name'],
            'location' => $location,
            'description' => $data['description'] ?? null,
            'source' => 'platform',
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        $operation = app(OperationLogger::class)->start('admin.menus.create', 'menu', (string) $menu->id, [
            'location' => $location,
            'key' => $menu->key,
            'name' => $menu->name,
        ], $request->user()?->id);
        app(OperationLogger::class)->success($operation, 'Frontend menu created from menu settings.');

        return redirect()
            ->route('admin.menus.index', ['location' => $location, 'menu' => $menu->id])
            ->with('status', 'Frontend menu created.');
    }

    public function updateMenu(Request $request, Menu $menu): RedirectResponse
    {
        abort_unless($menu->location === 'frontend', 404);

        $data = $this->validatedMenu($request);
        $menu->update([
            'key' => $this->uniqueMenuKey($data['key'] ?: $data['name'], $menu->location, $menu->id),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        $operation = app(OperationLogger::class)->start('admin.menus.update', 'menu', (string) $menu->id, [
            'location' => $menu->location,
            'key' => $menu->key,
            'name' => $menu->name,
        ], $request->user()?->id);
        app(OperationLogger::class)->success($operation, 'Frontend menu updated from menu settings.');

        return redirect()
            ->route('admin.menus.index', ['location' => $menu->location, 'menu' => $menu->id])
            ->with('status', 'Frontend menu updated.');
    }

    public function destroyMenu(Menu $menu): RedirectResponse
    {
        abort_unless($menu->location === 'frontend', 404);

        $operation = app(OperationLogger::class)->start('admin.menus.delete', 'menu', (string) $menu->id, [
            'location' => $menu->location,
            'key' => $menu->key,
            'name' => $menu->name,
        ], request()->user()?->id);
        $menu->delete();
        app(OperationLogger::class)->success($operation, 'Frontend menu removed from menu settings.');

        return redirect()
            ->route('admin.menus.index', ['location' => 'frontend'])
            ->with('status', 'Frontend menu removed.');
    }

    private function storeItem(Request $request, Menu $menu): RedirectResponse
    {
        $data = $this->validated($request);

        $item = $menu->items()->create($this->itemPayload($data, $menu));

        $operation = app(OperationLogger::class)->start('admin.menus.items.create', 'menu-item', (string) $item->id, [
            'menu_id' => $menu->id,
            'menu_key' => $menu->key,
            'location' => $menu->location,
            'title' => $item->title,
        ], $request->user()?->id);
        app(OperationLogger::class)->success($operation, 'Menu item created from menu settings.');

        return redirect()
            ->route('admin.menus.index', ['location' => $menu->location, 'menu' => $menu->id])
            ->with('status', 'Menu item created.');
    }

    public function update(Request $request, MenuItem $item): RedirectResponse
    {
        $data = $this->validated($request);
        $item->update($this->itemPayload($data, $item->menu, $item));

        $operation = app(OperationLogger::class)->start('admin.menus.items.update', 'menu-item', (string) $item->id, [
            'menu_id' => $item->menu_id,
            'location' => $item->menu->location,
            'title' => $item->title,
        ], $request->user()?->id);
        app(OperationLogger::class)->success($operation, 'Menu item updated from menu settings.');

        return redirect()
            ->route('admin.menus.index', ['location' => $item->menu->location, 'menu' => $item->menu_id])
            ->with('status', 'Menu item updated.');
    }

    public function destroy(MenuItem $item): RedirectResponse
    {
        $location = $item->menu->location;
        $menuId = $item->menu_id;
        $itemId = $item->id;
        $title = $item->title;
        $operation = app(OperationLogger::class)->start('admin.menus.items.delete', 'menu-item', (string) $itemId, [
            'menu_id' => $menuId,
            'location' => $location,
            'title' => $title,
        ], request()->user()?->id);
        $item->delete();
        app(OperationLogger::class)->success($operation, 'Menu item removed from menu settings.');

        return redirect()
            ->route('admin.menus.index', ['location' => $location, 'menu' => $menuId])
            ->with('status', 'Menu item removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedMenu(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_.:-]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:-10000', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:link,route,header'],
            'url' => ['nullable', 'string', 'max:255'],
            'route_name' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:24'],
            'target' => ['nullable', 'in:_self,_blank'],
            'permission' => ['nullable', 'string', 'exists:permissions,name'],
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'sort_order' => ['nullable', 'integer', 'min:-10000', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
            'css_class' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_:\\-\\s\\/\\[\\]\\.]*$/'],
            'text_color' => ['nullable', 'string', 'max:32'],
            'background_color' => ['nullable', 'string', 'max:32'],
            'hover_text_color' => ['nullable', 'string', 'max:32'],
            'hover_background_color' => ['nullable', 'string', 'max:32'],
            'font_weight' => ['nullable', 'in:normal,medium,semibold,bold'],
            'border_radius' => ['nullable', 'string', 'max:32'],
            'padding' => ['nullable', 'string', 'max:32'],
        ]);

        $data['url'] = $this->nullableTrim($data['url'] ?? null);
        $data['route_name'] = $this->nullableTrim($data['route_name'] ?? null);
        $data['type'] = $this->normalizeItemType((string) $data['type'], $data['url'], $data['route_name']);

        $errors = [];

        if ($data['type'] === 'route') {
            if ($data['route_name'] === null) {
                $errors['route_name'] = 'Choose a route for this menu item.';
            } elseif (! Route::has($data['route_name'])) {
                $errors['route_name'] = 'The selected route is not registered.';
            }
        }

        if ($data['type'] === 'link' && $data['url'] === null) {
            $errors['url'] = 'Enter a URL for this menu item.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function itemPayload(array $data, Menu $menu, ?MenuItem $item = null): array
    {
        $type = (string) $data['type'];

        return [
            'title' => $data['title'],
            'label' => $data['label'] ?? null,
            'type' => $type,
            'url' => $type === 'link' ? ($data['url'] ?? null) : null,
            'route_name' => $type === 'route' ? ($data['route_name'] ?? null) : null,
            'icon' => $data['icon'] ?? null,
            'target' => $data['target'] ?? '_self',
            'permission' => $data['permission'] ?? null,
            'parent_id' => $this->resolvedParentId($data, $menu, $item),
            'metadata' => [
                'style' => $this->stylePayload($data),
            ],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function normalizeItemType(string $type, ?string $url, ?string $routeName): string
    {
        if ($type === 'header') {
            return 'header';
        }

        if ($type === 'route' && $routeName === null && $url !== null) {
            return 'link';
        }

        if ($type === 'link' && $url === null && $routeName !== null) {
            return 'route';
        }

        return $type;
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function stylePayload(array $data): array
    {
        return array_filter([
            'css_class' => $this->cleanClass((string) ($data['css_class'] ?? '')),
            'text_color' => $this->cleanColor((string) ($data['text_color'] ?? '')),
            'background_color' => $this->cleanColor((string) ($data['background_color'] ?? '')),
            'hover_text_color' => $this->cleanColor((string) ($data['hover_text_color'] ?? '')),
            'hover_background_color' => $this->cleanColor((string) ($data['hover_background_color'] ?? '')),
            'font_weight' => $this->cleanToken((string) ($data['font_weight'] ?? ''), ['normal', 'medium', 'semibold', 'bold']),
            'border_radius' => $this->cleanCssSize((string) ($data['border_radius'] ?? '')),
            'padding' => $this->cleanCssSizeList((string) ($data['padding'] ?? '')),
        ], fn (string $value): bool => $value !== '');
    }

    private function cleanClass(string $value): string
    {
        return trim(preg_replace('/[^A-Za-z0-9_:\-\s\/\[\]\.]/', '', $value) ?? '');
    }

    private function cleanColor(string $value): string
    {
        $value = trim($value);

        if (preg_match('/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^(transparent|currentColor|inherit)$/', $value) === 1) {
            return $value;
        }

        return '';
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function cleanToken(string $value, array $allowed): string
    {
        $value = trim($value);

        return in_array($value, $allowed, true) ? $value : '';
    }

    private function cleanCssSize(string $value): string
    {
        $value = trim($value);

        return preg_match('/^(0|[0-9]{1,3}(px|rem|em|%))$/', $value) === 1 ? $value : '';
    }

    private function cleanCssSizeList(string $value): string
    {
        $parts = preg_split('/\s+/', trim($value)) ?: [];
        $parts = array_values(array_filter($parts, fn (string $part): bool => $this->cleanCssSize($part) !== ''));

        return count($parts) >= 1 && count($parts) <= 4 ? implode(' ', $parts) : '';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolvedParentId(array $data, Menu $menu, ?MenuItem $item = null): ?int
    {
        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : 0;

        if ($parentId <= 0) {
            return null;
        }

        $parent = MenuItem::query()
            ->where('menu_id', $menu->id)
            ->whereKey($parentId)
            ->first();

        if ($parent === null) {
            throw ValidationException::withMessages([
                'parent_id' => 'Choose a parent item from the same menu.',
            ]);
        }

        if ($item !== null && $parent->id === $item->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'A menu item cannot be its own parent.',
            ]);
        }

        if ($item !== null && $this->isDescendantOf($parent, $item)) {
            throw ValidationException::withMessages([
                'parent_id' => 'A menu item cannot be moved under one of its children.',
            ]);
        }

        return $parent->id;
    }

    private function isDescendantOf(MenuItem $candidate, MenuItem $ancestor): bool
    {
        $current = $candidate;
        $guard = 0;

        while ($current->parent_id !== null && $guard < 100) {
            if ((int) $current->parent_id === (int) $ancestor->id) {
                return true;
            }

            $current = MenuItem::query()->find($current->parent_id);

            if ($current === null) {
                return false;
            }

            $guard++;
        }

        return false;
    }

    private function ensureDefaultMenus(): void
    {
        $front = Menu::query()->firstOrCreate([
            'key' => 'platform.frontend',
            'location' => 'frontend',
        ], [
            'name' => 'Frontend Menu',
            'description' => 'Editable frontend navigation menu.',
            'source' => 'platform',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        if ($front->wasRecentlyCreated) {
            $front->items()->createMany([
                ['title' => 'Home', 'type' => 'route', 'route_name' => 'front.home', 'icon' => 'H', 'is_active' => true, 'sort_order' => 10],
                ['title' => 'My Account', 'type' => 'route', 'route_name' => 'front.account', 'icon' => 'A', 'permission' => null, 'is_active' => true, 'sort_order' => 20],
            ]);
        }

        $admin = Menu::query()->firstOrCreate([
            'key' => 'platform.admin',
            'location' => 'admin',
        ], [
            'name' => 'Admin Menu',
            'description' => 'Editable admin sidebar menu.',
            'source' => 'platform',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        if ($admin->wasRecentlyCreated) {
            $admin->items()->createMany($this->defaultAdminItems());
        }

        $this->ensureOperationalAdminItems($admin);
    }

    private function menuFor(string $location): Menu
    {
        $this->ensureDefaultMenus();

        return Menu::query()
            ->where('key', 'platform.'.$location)
            ->where('location', $location)
            ->firstOrFail();
    }

    private function uniqueMenuKey(string $value, string $location, ?int $exceptId = null): string
    {
        $base = 'platform.'.trim(strtolower(preg_replace('/[^A-Za-z0-9_.:-]+/', '-', $value) ?? 'menu'), '-');
        $base = $base !== 'platform.' ? $base : 'platform.menu';
        $key = $base;
        $index = 2;

        while (
            Menu::query()
                ->where('location', $location)
                ->where('key', $key)
                ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
                ->exists()
        ) {
            $key = $base.'-'.$index;
            $index++;
        }

        return $key;
    }

    /**
     * @return Collection<int, object>
     */
    private function builderContent()
    {
        if (
            ! Schema::hasTable('platform_pages')
            || ! Schema::hasColumn('platform_pages', 'content_type')
        ) {
            return collect();
        }

        return DB::table('platform_pages')
            ->whereIn('content_type', ['header', 'footer', 'block'])
            ->orderByRaw("CASE content_type WHEN 'header' THEN 1 WHEN 'footer' THEN 2 WHEN 'block' THEN 3 ELSE 9 END")
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'content_type', 'status', 'updated_at']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defaultAdminItems(): array
    {
        return [
            ['title' => 'Dashboard', 'type' => 'route', 'route_name' => 'dashboard', 'icon' => 'D', 'is_active' => true, 'sort_order' => 10],
            ['title' => 'Documentation', 'type' => 'route', 'route_name' => 'admin.documentation.index', 'icon' => 'O', 'permission' => 'documentation.manage', 'is_active' => true, 'sort_order' => 15],
            ['title' => 'Platform Registry', 'type' => 'route', 'route_name' => 'admin.platform-registry.index', 'icon' => 'A', 'permission' => 'platform-registry.view', 'is_active' => true, 'sort_order' => 20],
            ['title' => 'Menus', 'type' => 'route', 'route_name' => 'admin.menus.index', 'icon' => 'N', 'permission' => 'menus.manage', 'is_active' => true, 'sort_order' => 25],
            ['title' => 'Media', 'type' => 'route', 'route_name' => 'admin.media.index', 'icon' => 'M', 'permission' => 'media.manage', 'is_active' => true, 'sort_order' => 40],
            ['title' => 'Page Builder', 'type' => 'route', 'route_name' => 'admin.pages.index', 'icon' => 'G', 'permission' => 'pages.manage', 'is_active' => true, 'sort_order' => 30],
            ['title' => 'Theme Builder', 'type' => 'route', 'route_name' => 'admin.theme-builder.index', 'icon' => 'T', 'permission' => 'theme-builder.manage', 'is_active' => true, 'sort_order' => 55],
            ['title' => 'Settings', 'type' => 'route', 'route_name' => 'admin.settings.index', 'icon' => 'S', 'permission' => 'settings.manage', 'is_active' => true, 'sort_order' => 60],
            ['title' => 'Plugins', 'type' => 'route', 'route_name' => 'admin.plugins.index', 'icon' => 'P', 'permission' => 'plugins.view', 'is_active' => true, 'sort_order' => 70],
            ['title' => 'Users', 'type' => 'route', 'route_name' => 'admin.users.index', 'icon' => 'U', 'permission' => 'users.manage', 'is_active' => true, 'sort_order' => 80],
            ['title' => 'Roles', 'type' => 'route', 'route_name' => 'admin.roles.index', 'icon' => 'L', 'permission' => 'roles.manage', 'is_active' => true, 'sort_order' => 90],
            ['title' => 'Permissions', 'type' => 'route', 'route_name' => 'admin.permissions.index', 'icon' => 'K', 'permission' => 'permissions.manage', 'is_active' => true, 'sort_order' => 100],
        ];
    }

    private function ensureOperationalAdminItems(Menu $admin): void
    {
        $this->ensureMenuItem($admin, [
            'title' => 'Install Plugin',
            'label' => 'Install Plugin',
            'type' => 'route',
            'route_name' => 'admin.plugins.create',
            'icon' => 'I',
            'permission' => 'plugins.install',
            'is_active' => true,
            'sort_order' => 75,
            'metadata' => ['admin_group' => 'Platform', 'admin_sort_order' => 30],
        ]);

        $blog = MenuItem::query()->where('route_name', 'admin.plugins.blog.index')->first();

        if ($blog !== null) {
            $this->ensureMenuItem($blog->menu, [
                'parent_id' => $blog->id,
                'plugin_id' => $blog->plugin_id,
                'title' => 'Blog Settings',
                'label' => 'Blog Settings',
                'type' => 'route',
                'route_name' => 'admin.plugins.blog.settings.edit',
                'icon' => 'B',
                'permission' => 'blog.update',
                'is_active' => true,
                'sort_order' => 50,
                'metadata' => ['admin_group' => 'Content Management', 'admin_sort_order' => 50],
            ]);
        }

        $professionalProgrammer = MenuItem::query()->where('route_name', 'admin.plugins.professional-programmer.index')->first();

        if ($professionalProgrammer !== null) {
            $this->ensureMenuItem($professionalProgrammer->menu, [
                'parent_id' => $professionalProgrammer->id,
                'plugin_id' => $professionalProgrammer->plugin_id,
                'title' => 'Professional Programmer Alerts',
                'label' => 'Professional Programmer Alerts',
                'type' => 'route',
                'route_name' => 'admin.plugins.professional-programmer.alerts',
                'icon' => 'PA',
                'permission' => 'professional-programmer.manage',
                'is_active' => true,
                'sort_order' => 40,
                'metadata' => ['admin_group' => 'AI Tools', 'admin_sort_order' => 40],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function ensureMenuItem(Menu $menu, array $attributes): void
    {
        $routeName = $attributes['route_name'] ?? null;

        if (is_string($routeName) && ! Route::has($routeName)) {
            return;
        }

        if (! is_string($routeName) || $routeName === '') {
            return;
        }

        MenuItem::query()->firstOrCreate([
            'menu_id' => $menu->id,
            'route_name' => $routeName,
        ], [
            'parent_id' => $attributes['parent_id'] ?? null,
            'plugin_id' => $attributes['plugin_id'] ?? null,
            'title' => $attributes['title'],
            'label' => $attributes['label'] ?? null,
            'type' => $attributes['type'] ?? 'route',
            'url' => null,
            'route_params' => null,
            'icon' => $attributes['icon'] ?? null,
            'target' => '_self',
            'permission' => $attributes['permission'] ?? null,
            'metadata' => $attributes['metadata'] ?? [],
            'is_active' => (bool) ($attributes['is_active'] ?? true),
            'sort_order' => (int) ($attributes['sort_order'] ?? 0),
        ]);
    }
}
