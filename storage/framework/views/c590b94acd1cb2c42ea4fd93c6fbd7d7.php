<?php
    $user = Auth::user();
    $routeAccess = app(\App\Platform\Core\Access\RouteAccessGate::class);
    $hasAdminAccess = $routeAccess->allowsRouteName($user, 'dashboard');

    try {
        $platformSettings = app(\App\Platform\Core\Services\SettingsRepository::class)->values();
    } catch (\Throwable $exception) {
        $platformSettings = [];
    }

    $isArabicLanguage = false;
    $translations = [
        'Dashboard' => 'لوحة التحكم',
        'Admin' => 'الإدارة',
        'Platform Registry' => 'سجل المنصة',
        'Documentation' => 'التوثيق',
        'Menus' => 'القوائم',
        'Front Builder' => 'منشئ الواجهة',
        'Docs' => 'الوثائق',
        'Media' => 'الوسائط',
        'Pages' => 'الصفحات',
        'Themes' => 'القوالب',
        'Settings' => 'الإعدادات',
        'Backup' => 'النسخ الاحتياطي',
        'Plugins' => 'الإضافات',
        'Install Plugin' => 'تثبيت إضافة',
        'Theme Manager' => 'إدارة الثيمات',
        'Theme Builder' => 'منشئ الثيم',
        'Theme Editor' => 'محرر الثيم',
        'Users' => 'المستخدمون',
        'Roles' => 'الأدوار',
        'Permissions' => 'الصلاحيات',
        'Blog' => 'المدونة',
        'All Posts' => 'كل المقالات',
        'Add New Post' => 'إضافة مقال',
        'Categories' => 'التصنيفات',
        'Add Category' => 'إضافة تصنيف',
        'Blog Settings' => 'إعدادات المدونة',
        'AI Core' => 'نواة الذكاء',
        'AI Assistant' => 'مساعد الذكاء',
        'Professional Programmer' => 'المبرمج المحترف',
        'Professional Programmer Alerts' => 'تنبيهات المبرمج المحترف',
        'Overview' => 'نظرة عامة',
        'Content Management' => 'إدارة المحتوى',
        'Platform' => 'المنصة',
        'AI Tools' => 'أدوات الذكاء',
        'Users & Access' => 'المستخدمون والصلاحيات',
        'System' => 'النظام',
        'Home' => 'الرئيسية',
        'My Account' => 'حسابي',
        'Profile' => 'الملف الشخصي',
        'Log Out' => 'تسجيل الخروج',
        'Menu' => 'القائمة',
        'Close' => 'إغلاق',
    ];
    $t = fn (string $text): string => $isArabicLanguage ? ($translations[$text] ?? $text) : $text;

    $menuItems = [];

    if ($hasAdminAccess) {
        try {
            $storedAdminItems = app(\App\Platform\Core\Menus\MenuManager::class)->getAdminMenu($user);
            $hasPlatformAdminMenu = \Illuminate\Support\Facades\Schema::hasTable('menus')
                && \App\Platform\Core\Models\Menu::query()
                    ->where('key', 'platform.admin')
                    ->where('location', 'admin')
                    ->exists();

            if ($hasPlatformAdminMenu && $storedAdminItems !== []) {
                $mapStoredAdminItem = function (array $item) use ($t, &$mapStoredAdminItem): ?array {
                    $routeName = $item['route_name'] ?? null;
                    $url = $item['url'] ?? null;
                    $href = null;

                    if (is_string($routeName) && \Illuminate\Support\Facades\Route::has($routeName)) {
                        $href = route($routeName, $item['route_params'] ?? []);
                    } elseif (is_string($url) && trim($url) !== '') {
                        $href = str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
                            ? $url
                            : url($url);
                    }

                    $children = collect($item['children'] ?? [])
                        ->map($mapStoredAdminItem)
                        ->filter()
                        ->values()
                        ->all();

                    if ($href === null && $children === []) {
                        return null;
                    }

                    $directActive = false;

                    if (is_string($routeName)) {
                        $directActive = request()->routeIs($routeName) || request()->routeIs(rtrim($routeName, '.').'.*');
                    } elseif (is_string($url) && trim($url) !== '') {
                        $directActive = request()->is(trim((string) $url, '/').'*');
                    }

                    $childActive = collect($children)->contains(fn (array $child): bool => (bool) ($child['active'] ?? false));
                    $label = (string) ($item['label'] ?: $item['title']);

                    return [
                        'label' => $t($label),
                        'href' => $href,
                        'active' => $directActive || $childActive,
                        'icon' => (string) ($item['icon'] ?: strtoupper(substr((string) $item['title'], 0, 1))),
                        'visible' => true,
                        'type' => (string) ($item['type'] ?? 'link'),
                        'target' => $item['target'] ?? null,
                        'children' => $children,
                    ];
                };

                $menuItems = collect($storedAdminItems)
                    ->map($mapStoredAdminItem)
                    ->filter()
                    ->values()
                    ->all();
            }
        } catch (\Throwable $exception) {
            //
        }
    }

    if ($menuItems === []) {
        if ($hasAdminAccess) {
            $menuItems = [
                [
                    'label' => $t('Overview'),
                    'href' => null,
                    'active' => request()->routeIs('dashboard') || request()->routeIs('admin.documentation.*'),
                    'icon' => 'O',
                    'visible' => true,
                    'type' => 'group',
                    'children' => [
                        [
                            'label' => $t('Dashboard'),
                            'href' => route('dashboard'),
                            'active' => request()->routeIs('dashboard'),
                            'icon' => 'D',
                            'visible' => true,
                            'type' => 'link',
                            'children' => [],
                        ],
                        [
                            'label' => $t('Documentation'),
                            'href' => route('admin.documentation.index'),
                            'active' => request()->routeIs('admin.documentation.*'),
                            'icon' => 'O',
                            'visible' => $routeAccess->allowsRouteName($user, 'admin.documentation.index'),
                            'type' => 'link',
                            'children' => [],
                        ],
                    ],
                ],
            ];
        } else {
            $menuItems = [
                [
                    'label' => $t('Home'),
                    'href' => route('front.home'),
                    'active' => request()->routeIs('front.home'),
                    'icon' => 'H',
                    'visible' => true,
                    'type' => 'link',
                    'children' => [],
                ],
                [
                    'label' => $t('My Account'),
                    'href' => route('front.account'),
                    'active' => request()->routeIs('front.account'),
                    'icon' => 'A',
                    'visible' => true,
                    'type' => 'link',
                    'children' => [],
                ],
            ];
        }
    }

    $activeAdminSection = collect($menuItems)->first(fn (array $item): bool =>
        (bool) ($item['active'] ?? false)
        && (($item['type'] ?? null) === 'group' || ! empty($item['children'] ?? []))
    );
    $activeAdminSectionKey = $activeAdminSection
        ? 'admin-section-'.\Illuminate\Support\Str::slug($activeAdminSection['label'])
        : null;
?>

<nav x-data="{ mobileOpen: false }" class="z4-admin-nav">
    <div class="z4-admin-bar">
        <div class="z4-admin-bar-main">
            <a href="<?php echo e(route('front.home')); ?>" class="z4-admin-brand">
                <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['class' => 'z4-admin-brand-logo']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'z4-admin-brand-logo']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $attributes = $__attributesOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $component = $__componentOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__componentOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
                <span><?php echo e($platformSettings['general.site_title'] ?? 'Z4Rank'); ?></span>
            </a>

            <a href="<?php echo e(route('front.home')); ?>" class="z4-admin-home-link">
                <?php echo e($t('Home')); ?>

            </a>

            <button
                type="button"
                class="z4-builder-sidebar-toggle"
                title="Toggle admin sidebar"
                aria-label="Toggle admin sidebar"
                @click="
                    const body = document.body;
                    const expanded = body.classList.toggle('page-builder-sidebar-expanded');
                    body.classList.toggle('page-builder-sidebar-compact', ! expanded);
                    try { window.localStorage.setItem('z4-page-builder-sidebar', expanded ? 'expanded' : 'compact'); } catch (error) {}
                "
            ><?php echo e($t('Menu')); ?></button>
        </div>

        <div class="z4-admin-bar-actions">
            <a href="<?php echo e(route('profile.edit')); ?>" class="z4-admin-profile-link"><?php echo e($user->name); ?></a>
            <form method="POST" action="<?php echo e(route('logout')); ?>" class="z4-admin-logout-form">
                <?php echo csrf_field(); ?>
                <button type="submit" class="z4-admin-bar-button"><?php echo e($t('Log Out')); ?></button>
            </form>
            <button type="button" @click="mobileOpen = ! mobileOpen" class="z4-mobile-menu-button">
                <?php echo e($t('Menu')); ?>

            </button>
        </div>
    </div>

    <aside class="z4-admin-sidebar" aria-label="Admin navigation">
        <div class="z4-admin-sidebar-scroll" x-data="{ openSection: <?php echo \Illuminate\Support\Js::from($activeAdminSectionKey)->toHtml() ?> }">
            <?php $__currentLoopData = $menuItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(! ($item['visible'] ?? true)) continue; ?>

                <?php
                    $children = $item['children'] ?? [];
                    $hasChildren = $children !== [];
                    $isGroup = ($item['type'] ?? null) === 'group' || $hasChildren;
                    $sectionKey = 'admin-section-'.\Illuminate\Support\Str::slug($item['label']);
                    $activeSubmenu = collect($children)->first(fn (array $child): bool =>
                        (bool) ($child['active'] ?? false) && ! empty($child['children'] ?? [])
                    );
                    $activeSubmenuKey = $activeSubmenu
                        ? $sectionKey.'-'.\Illuminate\Support\Str::slug($activeSubmenu['label'])
                        : null;
                ?>

                <?php if($isGroup): ?>
                    <section
                        class="z4-admin-section <?php echo e($item['active'] ? 'is-active is-open' : ''); ?>"
                        x-data="{
                            openSubmenu: <?php echo \Illuminate\Support\Js::from($activeSubmenuKey)->toHtml() ?>,
                            positionSectionFlyout(section) {
                                if (! window.matchMedia('(min-width: 769px)').matches) return;

                                const panel = section.querySelector(':scope > .z4-admin-section-body');
                                if (! panel) return;

                                requestAnimationFrame(() => {
                                    const sectionRect = section.getBoundingClientRect();
                                    const visiblePanelRect = panel.getBoundingClientRect();
                                    const panelHeight = Math.min(visiblePanelRect.height || panel.scrollHeight || 320, window.innerHeight - 58);
                                    const panelWidth = Math.min(Math.max(visiblePanelRect.width || panel.scrollWidth || 250, 250), 310);
                                    const panelLeft = Math.max(4, Math.min(sectionRect.right + 2, window.innerWidth - panelWidth - 8));
                                    const panelTop = Math.max(42, Math.min(sectionRect.top, window.innerHeight - panelHeight - 12));

                                    panel.style.setProperty('left', panelLeft + 'px', 'important');
                                    panel.style.setProperty('top', panelTop + 'px', 'important');
                                });
                            },
                            positionSubmenuFlyout(group) {
                                if (! window.matchMedia('(min-width: 769px)').matches) return;

                                const panel = group.querySelector(':scope > .z4-admin-submenu');
                                if (! panel) return;

                                requestAnimationFrame(() => {
                                    const groupRect = group.getBoundingClientRect();
                                    const visiblePanelRect = panel.getBoundingClientRect();
                                    const panelHeight = Math.min(visiblePanelRect.height || panel.scrollHeight || 260, window.innerHeight - 58);
                                    const panelWidth = Math.min(Math.max(visiblePanelRect.width || panel.scrollWidth || 220, 220), 300);
                                    const panelLeft = Math.max(4, Math.min(groupRect.right + 2, window.innerWidth - panelWidth - 8));
                                    const panelTop = Math.max(42, Math.min(groupRect.top, window.innerHeight - panelHeight - 12));

                                    panel.style.setProperty('left', panelLeft + 'px', 'important');
                                    panel.style.setProperty('top', panelTop + 'px', 'important');
                                });
                            }
                        }"
                        :class="{ 'is-open': openSection === <?php echo \Illuminate\Support\Js::from($sectionKey)->toHtml() ?> }"
                        @mouseenter="if (openSection !== <?php echo \Illuminate\Support\Js::from($sectionKey)->toHtml() ?>) positionSectionFlyout($el)"
                        @focusin="if (openSection !== <?php echo \Illuminate\Support\Js::from($sectionKey)->toHtml() ?>) positionSectionFlyout($el)"
                    >
                        <button
                            type="button"
                            class="z4-admin-section-toggle"
                            @click="openSection = openSection === <?php echo \Illuminate\Support\Js::from($sectionKey)->toHtml() ?> ? null : <?php echo \Illuminate\Support\Js::from($sectionKey)->toHtml() ?>"
                            :aria-expanded="(openSection === <?php echo \Illuminate\Support\Js::from($sectionKey)->toHtml() ?>).toString()"
                            aria-controls="<?php echo e($sectionKey); ?>"
                        >
                            <span class="z4-admin-icon"><?php echo e($item['icon']); ?></span>
                            <span class="z4-admin-section-label"><?php echo e($item['label']); ?></span>
                            <span class="z4-admin-chevron" aria-hidden="true"></span>
                        </button>

                        <div id="<?php echo e($sectionKey); ?>" class="z4-admin-section-body" x-show="openSection === <?php echo \Illuminate\Support\Js::from($sectionKey)->toHtml() ?>">
                            <?php $__currentLoopData = $children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if(! ($child['visible'] ?? true)) continue; ?>

                                <?php
                                    $grandChildren = $child['children'] ?? [];
                                    $hasGrandChildren = $grandChildren !== [];
                                    $childKey = $sectionKey.'-'.\Illuminate\Support\Str::slug($child['label']);
                                ?>

                                <?php if($hasGrandChildren): ?>
                                    <div
                                        class="z4-admin-menu-group <?php echo e($child['active'] ? 'is-active is-open' : ''); ?>"
                                        :class="{ 'is-open': openSubmenu === <?php echo \Illuminate\Support\Js::from($childKey)->toHtml() ?> }"
                                        @mouseenter="if (openSubmenu !== <?php echo \Illuminate\Support\Js::from($childKey)->toHtml() ?>) positionSubmenuFlyout($el)"
                                        @focusin="if (openSubmenu !== <?php echo \Illuminate\Support\Js::from($childKey)->toHtml() ?>) positionSubmenuFlyout($el)"
                                    >
                                        <div class="z4-admin-parent-row">
                                            <?php if($child['href']): ?>
                                                <a href="<?php echo e($child['href']); ?>" class="z4-admin-link z4-admin-parent-link <?php echo e($child['active'] ? 'is-active' : ''); ?>" <?php if($child['target'] ?? null): ?> target="<?php echo e($child['target']); ?>" <?php endif; ?>>
                                                    <span class="z4-admin-icon"><?php echo e($child['icon']); ?></span>
                                                    <span class="z4-admin-link-label"><?php echo e($child['label']); ?></span>
                                                </a>
                                            <?php else: ?>
                                                <span class="z4-admin-link z4-admin-parent-link <?php echo e($child['active'] ? 'is-active' : ''); ?>">
                                                    <span class="z4-admin-icon"><?php echo e($child['icon']); ?></span>
                                                    <span class="z4-admin-link-label"><?php echo e($child['label']); ?></span>
                                                </span>
                                            <?php endif; ?>
                                            <button
                                                type="button"
                                                class="z4-admin-submenu-toggle"
                                                @click="openSubmenu = openSubmenu === <?php echo \Illuminate\Support\Js::from($childKey)->toHtml() ?> ? null : <?php echo \Illuminate\Support\Js::from($childKey)->toHtml() ?>"
                                                :aria-expanded="(openSubmenu === <?php echo \Illuminate\Support\Js::from($childKey)->toHtml() ?>).toString()"
                                                aria-controls="<?php echo e($childKey); ?>"
                                            >
                                                <span class="z4-admin-chevron" aria-hidden="true"></span>
                                            </button>
                                        </div>

                                        <div id="<?php echo e($childKey); ?>" class="z4-admin-submenu" x-show="openSubmenu === <?php echo \Illuminate\Support\Js::from($childKey)->toHtml() ?>">
                                            <?php $__currentLoopData = $grandChildren; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grandChild): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php if(! ($grandChild['visible'] ?? true)) continue; ?>
                                                <a href="<?php echo e($grandChild['href']); ?>" class="z4-admin-submenu-link <?php echo e($grandChild['active'] ? 'is-active' : ''); ?>" <?php if($grandChild['target'] ?? null): ?> target="<?php echo e($grandChild['target']); ?>" <?php endif; ?>>
                                                    <?php echo e($grandChild['label']); ?>

                                                </a>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <a href="<?php echo e($child['href']); ?>" class="z4-admin-link <?php echo e($child['active'] ? 'is-active' : ''); ?>" <?php if($child['target'] ?? null): ?> target="<?php echo e($child['target']); ?>" <?php endif; ?>>
                                        <span class="z4-admin-icon"><?php echo e($child['icon']); ?></span>
                                        <span class="z4-admin-link-label"><?php echo e($child['label']); ?></span>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </section>
                <?php else: ?>
                    <a href="<?php echo e($item['href']); ?>" class="z4-admin-link <?php echo e($item['active'] ? 'is-active' : ''); ?>" <?php if($item['target'] ?? null): ?> target="<?php echo e($item['target']); ?>" <?php endif; ?>>
                        <span class="z4-admin-icon"><?php echo e($item['icon']); ?></span>
                        <span class="z4-admin-link-label"><?php echo e($item['label']); ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="z4-admin-sidebar-footer">
            <a href="<?php echo e(route('profile.edit')); ?>" class="z4-admin-link">
                <span class="z4-admin-link-label"><?php echo e($t('Profile')); ?></span>
            </a>
            <form method="POST" action="<?php echo e(route('logout')); ?>" class="z4-admin-footer-form">
                <?php echo csrf_field(); ?>
                <button type="submit" class="z4-admin-link z4-admin-footer-button">
                    <span class="z4-admin-link-label"><?php echo e($t('Log Out')); ?></span>
                </button>
            </form>
        </div>
    </aside>

    <div x-show="mobileOpen" x-cloak class="z4-mobile-overlay" @click="mobileOpen = false"></div>

    <aside x-show="mobileOpen" x-cloak class="z4-mobile-drawer" aria-label="Mobile admin navigation">
        <div class="z4-mobile-drawer-header">
            <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['class' => 'z4-mobile-logo']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'z4-mobile-logo']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $attributes = $__attributesOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $component = $__componentOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__componentOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
            <button type="button" @click="mobileOpen = false" class="z4-mobile-close-button"><?php echo e($t('Close')); ?></button>
        </div>

        <div class="z4-mobile-drawer-body">
            <?php $__currentLoopData = $menuItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(! ($item['visible'] ?? true)) continue; ?>

                <?php
                    $children = $item['children'] ?? [];
                    $hasChildren = $children !== [];
                ?>

                <div class="z4-mobile-section <?php echo e($item['active'] ? 'is-active' : ''); ?>">
                    <?php if($hasChildren): ?>
                        <div class="z4-mobile-section-title"><?php echo e($item['label']); ?></div>
                        <?php $__currentLoopData = $children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if(! ($child['visible'] ?? true)) continue; ?>
                            <a href="<?php echo e($child['href']); ?>" class="z4-mobile-link <?php echo e($child['active'] ? 'is-active' : ''); ?>" <?php if($child['target'] ?? null): ?> target="<?php echo e($child['target']); ?>" <?php endif; ?>>
                                <span class="z4-admin-icon"><?php echo e($child['icon']); ?></span>
                                <span><?php echo e($child['label']); ?></span>
                            </a>
                            <?php $__currentLoopData = ($child['children'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grandChild): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if(! ($grandChild['visible'] ?? true)) continue; ?>
                                <a href="<?php echo e($grandChild['href']); ?>" class="z4-mobile-sublink <?php echo e($grandChild['active'] ? 'is-active' : ''); ?>" <?php if($grandChild['target'] ?? null): ?> target="<?php echo e($grandChild['target']); ?>" <?php endif; ?>>
                                    <?php echo e($grandChild['label']); ?>

                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                        <a href="<?php echo e($item['href']); ?>" class="z4-mobile-link <?php echo e($item['active'] ? 'is-active' : ''); ?>" <?php if($item['target'] ?? null): ?> target="<?php echo e($item['target']); ?>" <?php endif; ?>>
                            <span class="z4-admin-icon"><?php echo e($item['icon']); ?></span>
                            <span><?php echo e($item['label']); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </aside>
</nav>
<?php /**PATH /var/www/html/resources/views/layouts/navigation.blade.php ENDPATH**/ ?>