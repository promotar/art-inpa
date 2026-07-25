<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <h2 class="ainpa-page-title">Menu Settings</h2>
     <?php $__env->endSlot(); ?>

    <?php
        $locations = [
            'admin' => 'Admin Menu',
            'frontend' => 'Frontend Menus',
        ];

        $activeMenus = $menus->get($activeLocation, collect());
        $activeMenu = $activeMenuId ? $activeMenus->firstWhere('id', $activeMenuId) : null;
        $activeMenu = $activeMenu ?: ($activeLocation === 'admin'
            ? ($activeMenus->firstWhere('key', 'platform.admin') ?: $activeMenus->first())
            : $activeMenus->first());

        $contentTypeLabels = [
            'header' => 'Headers',
            'footer' => 'Footers',
            'block' => 'Reusable Blocks',
        ];
        $builderGroups = $builderContent->groupBy('content_type');

        $orderedItems = collect();
        $parentChoices = collect();

        if ($activeMenu) {
            $itemsByParent = $activeMenu->items
                ->sortBy([
                    ['sort_order', 'asc'],
                    ['id', 'asc'],
                ])
                ->groupBy(fn ($menuItem) => $menuItem->parent_id ?: 0);

            $walkItems = function ($parentId = 0, $depth = 0) use (&$walkItems, $itemsByParent) {
                return ($itemsByParent->get($parentId) ?: collect())
                    ->flatMap(function ($menuItem) use (&$walkItems, $depth) {
                        return collect([
                            ['item' => $menuItem, 'depth' => $depth],
                        ])->merge($walkItems($menuItem->id, $depth + 1));
                    });
            };

            $orderedItems = $walkItems();
            $parentChoices = $orderedItems;
        }
    ?>

    <div class="ainpa-admin-page admin-menu-page">
        <div class="ainpa-page-container admin-menu-container">
            <?php if(session('status')): ?>
                <div class="ainpa-alert ainpa-alert-success"><?php echo e(session('status')); ?></div>
            <?php endif; ?>

            <?php if(isset($errors) && $errors->any()): ?>
                <div class="ainpa-alert ainpa-alert-danger"><?php echo e($errors->first()); ?></div>
            <?php endif; ?>

            <section class="admin-menu-hero">
                <div>
                    <p class="admin-menu-eyebrow">Navigation Builder</p>
                    <h3 class="admin-menu-title">Admin and frontend menu structure</h3>
                    <p class="admin-menu-subtitle">
                        Manage primary items, submenu items, permissions, routes, plugin entries, and frontend menu styling from the database-backed menu builder.
                    </p>
                </div>

                <div class="admin-menu-hero-stats">
                    <span class="admin-menu-stat">
                        <strong><?php echo e($activeMenus->count()); ?></strong>
                        <span><?php echo e($activeLocation === 'admin' ? 'admin sources' : 'frontend menus'); ?></span>
                    </span>
                    <span class="admin-menu-stat">
                        <strong><?php echo e($activeMenu?->items->count() ?? 0); ?></strong>
                        <span>items in view</span>
                    </span>
                </div>
            </section>

            <nav class="admin-menu-tabs" aria-label="Menu locations">
                <?php $__currentLoopData = $locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a
                        href="<?php echo e(route('admin.menus.index', ['location' => $location])); ?>"
                        class="admin-menu-tab <?php echo e($activeLocation === $location ? 'is-active' : ''); ?>"
                    >
                        <?php echo e($label); ?>

                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </nav>

            <section class="admin-menu-card">
                <div class="admin-menu-card-header">
                    <div>
                        <h3 class="admin-menu-card-title"><?php echo e($activeLocation === 'admin' ? 'Admin menu sources' : 'Frontend menus'); ?></h3>
                        <p class="admin-menu-card-subtitle">
                            <?php echo e($activeLocation === 'admin'
                                ? 'The sidebar is built from the platform admin menu plus active plugin admin menus.'
                                : 'Frontend menus can be created, configured, and used by public layouts.'); ?>

                        </p>
                    </div>

                    <?php if($activeLocation === 'frontend'): ?>
                        <button
                            type="button"
                            class="ainpa-button ainpa-button-primary"
                            x-data
                            @click="$dispatch('open-menu-create')"
                        >
                            New Menu
                        </button>
                    <?php endif; ?>
                </div>

                <?php if($activeLocation === 'frontend'): ?>
                    <div x-data="{ createOpen: false, editMenu: null }" @open-menu-create.window="createOpen = ! createOpen; editMenu = null">
                        <div x-show="createOpen" x-cloak class="admin-menu-panel">
                            <form method="POST" action="<?php echo e(route('admin.menus.store', 'frontend')); ?>" class="admin-menu-form">
                                <?php echo csrf_field(); ?>
                                <div class="admin-menu-form-grid admin-menu-form-grid-six">
                                    <label class="admin-field admin-menu-span-two">
                                        <span class="admin-field-label">Name</span>
                                        <input name="name" required placeholder="Main Menu" class="admin-input">
                                    </label>
                                    <label class="admin-field admin-menu-span-two">
                                        <span class="admin-field-label">Key</span>
                                        <input name="key" placeholder="main" class="admin-input">
                                    </label>
                                    <label class="admin-field">
                                        <span class="admin-field-label">Sort</span>
                                        <input type="number" name="sort_order" value="0" class="admin-input">
                                    </label>
                                    <label class="admin-field admin-menu-check-field">
                                        <span class="admin-menu-check-option">
                                            <input type="checkbox" name="is_active" value="1" checked>
                                            <span>Active</span>
                                        </span>
                                    </label>
                                </div>
                                <div class="admin-menu-form-grid admin-menu-form-grid-action">
                                    <label class="admin-field">
                                        <span class="admin-field-label">Description</span>
                                        <input name="description" class="admin-input">
                                    </label>
                                    <div class="admin-menu-actions">
                                        <button class="ainpa-button ainpa-button-primary">Create Menu</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <?php if($activeMenus->isEmpty()): ?>
                            <div class="admin-menu-empty">No frontend menus yet.</div>
                        <?php else: ?>
                            <div class="admin-menu-source-grid">
                                <?php $__currentLoopData = $activeMenus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <article class="admin-menu-source-card <?php echo e($activeMenu?->id === $menu->id ? 'is-active' : ''); ?>">
                                        <div class="admin-menu-source-main">
                                            <div>
                                                <h4 class="admin-menu-source-title"><?php echo e($menu->name); ?></h4>
                                                <p class="admin-menu-source-meta"><?php echo e($menu->key); ?> · <?php echo e($menu->items->count()); ?> items · Sort <?php echo e($menu->sort_order); ?></p>
                                            </div>
                                            <span class="ainpa-status-badge <?php echo e($menu->is_active ? 'ainpa-status-active' : 'ainpa-status-inactive'); ?>">
                                                <?php echo e($menu->is_active ? 'Active' : 'Inactive'); ?>

                                            </span>
                                        </div>

                                        <div class="admin-menu-source-actions">
                                            <a href="<?php echo e(route('admin.menus.index', ['location' => 'frontend', 'menu' => $menu->id])); ?>" class="ainpa-button ainpa-button-compact">Manage Items</a>
                                            <button type="button" @click="editMenu = editMenu === <?php echo e($menu->id); ?> ? null : <?php echo e($menu->id); ?>; createOpen = false" class="ainpa-button ainpa-button-compact">Settings</button>
                                            <form method="POST" action="<?php echo e(route('admin.menus.destroy', $menu)); ?>" onsubmit="return confirm('Remove this frontend menu and all of its items?');">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button class="ainpa-button ainpa-button-danger ainpa-button-compact">Delete</button>
                                            </form>
                                        </div>

                                        <div x-show="editMenu === <?php echo e($menu->id); ?>" x-cloak class="admin-menu-card-edit">
                                            <form method="POST" action="<?php echo e(route('admin.menus.update', $menu)); ?>" class="admin-menu-form">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <div class="admin-menu-form-grid admin-menu-form-grid-two">
                                                    <label class="admin-field">
                                                        <span class="admin-field-label">Name</span>
                                                        <input name="name" value="<?php echo e($menu->name); ?>" required class="admin-input">
                                                    </label>
                                                    <label class="admin-field">
                                                        <span class="admin-field-label">Key</span>
                                                        <input name="key" value="<?php echo e(preg_replace('/^platform\./', '', $menu->key)); ?>" class="admin-input">
                                                    </label>
                                                </div>
                                                <div class="admin-menu-form-grid admin-menu-form-grid-settings">
                                                    <label class="admin-field">
                                                        <span class="admin-field-label">Sort</span>
                                                        <input type="number" name="sort_order" value="<?php echo e($menu->sort_order); ?>" class="admin-input">
                                                    </label>
                                                    <label class="admin-field admin-menu-check-field">
                                                        <span class="admin-menu-check-option">
                                                            <input type="checkbox" name="is_active" value="1" <?php if($menu->is_active): echo 'checked'; endif; ?>>
                                                            <span>Active</span>
                                                        </span>
                                                    </label>
                                                    <label class="admin-field admin-menu-description-field">
                                                        <span class="admin-field-label">Description</span>
                                                        <input name="description" value="<?php echo e($menu->description); ?>" class="admin-input">
                                                    </label>
                                                </div>
                                                <div class="admin-menu-actions">
                                                    <button class="ainpa-button">Save</button>
                                                </div>
                                            </form>

                                            <form method="POST" action="<?php echo e(route('admin.menus.destroy', $menu)); ?>" onsubmit="return confirm('Remove this frontend menu and its items?');" class="admin-menu-danger-form">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button class="ainpa-button ainpa-button-danger">Remove Menu</button>
                                            </form>
                                        </div>
                                    </article>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php if($activeMenus->isEmpty()): ?>
                        <div class="admin-menu-empty">No admin menu sources are registered.</div>
                    <?php else: ?>
                        <div class="admin-menu-source-grid">
                            <?php $__currentLoopData = $activeMenus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a
                                    href="<?php echo e(route('admin.menus.index', ['location' => 'admin', 'menu' => $menu->id])); ?>"
                                    class="admin-menu-source-card admin-menu-source-link <?php echo e($activeMenu?->id === $menu->id ? 'is-active' : ''); ?>"
                                >
                                    <div class="admin-menu-source-main">
                                        <div>
                                            <h4 class="admin-menu-source-title"><?php echo e($menu->name); ?></h4>
                                            <p class="admin-menu-source-meta"><?php echo e($menu->key); ?> · <?php echo e($menu->source); ?> · <?php echo e($menu->items->count()); ?> items</p>
                                        </div>
                                        <span class="ainpa-status-badge <?php echo e($menu->is_active ? 'ainpa-status-active' : 'ainpa-status-inactive'); ?>">
                                            <?php echo e($menu->is_active ? 'Active' : 'Inactive'); ?>

                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <?php if($activeMenu): ?>
                <section class="admin-menu-card" x-data="{ open: null, addOpen: false }">
                    <div class="admin-menu-card-header">
                        <div>
                            <h3 class="admin-menu-card-title"><?php echo e($activeMenu->name); ?></h3>
                            <p class="admin-menu-card-subtitle">
                                <?php echo e($activeMenu->items->count()); ?> menu items · <?php echo e($activeMenu->key); ?> · <?php echo e(ucfirst($activeMenu->source)); ?> source
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="addOpen = ! addOpen; open = null"
                            class="ainpa-button ainpa-button-primary"
                        >
                            Add Item
                        </button>
                    </div>

                    <div x-show="addOpen" x-cloak class="admin-menu-panel">
                        <form method="POST" action="<?php echo e(route('admin.menus.items.store-for-menu', $activeMenu)); ?>" class="admin-menu-form">
                            <?php echo csrf_field(); ?>
                            <?php echo $__env->make('admin.menus.partials.item-fields', [
                                'item' => null,
                                'activeLocation' => $activeLocation,
                                'permissions' => $permissions,
                                'routeNames' => $routeNames,
                                'parentChoices' => $parentChoices,
                            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <div class="admin-menu-actions">
                                <button class="ainpa-button ainpa-button-primary">Add Item</button>
                            </div>
                        </form>
                    </div>

                    <?php if($orderedItems->isEmpty()): ?>
                        <div class="admin-menu-empty">No menu items yet.</div>
                    <?php else: ?>
                        <div class="admin-menu-accordion">
                            <?php $__currentLoopData = $orderedItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $item = $entry['item'];
                                    $depth = (int) $entry['depth'];
                                    $panelId = 'item-'.$item->id;
                                    $targetLabel = $item->route_name ?: ($item->url ?: 'No target');
                                    $badgeLabel = $depth > 0 ? 'Sub item' : 'Main item';
                                ?>

                                <article class="admin-menu-accordion-item <?php echo e($depth > 0 ? 'is-child' : 'is-root'); ?>">
                                    <div class="admin-menu-accordion-row flex items-stretch gap-3">
                                        <button
                                            type="button"
                                            @click="open = open === '<?php echo e($panelId); ?>' ? null : '<?php echo e($panelId); ?>'; addOpen = false"
                                            class="admin-menu-accordion-button flex-1"
                                            :aria-expanded="open === '<?php echo e($panelId); ?>'"
                                        >
                                            <span class="admin-menu-level admin-menu-level-<?php echo e(min($depth, 4)); ?>"></span>
                                            <span class="admin-menu-item-icon"><?php echo e($item->icon ?: strtoupper(substr($item->title, 0, 1))); ?></span>
                                            <span class="admin-menu-item-main">
                                                <span class="admin-menu-item-title"><?php echo e($item->title); ?></span>
                                                <span class="admin-menu-item-target"><?php echo e($targetLabel); ?></span>
                                            </span>
                                            <span class="admin-menu-item-meta">
                                                <span class="admin-menu-badge"><?php echo e($badgeLabel); ?></span>
                                                <span class="admin-menu-badge"><?php echo e($item->type); ?></span>
                                                <span class="ainpa-status-badge <?php echo e($item->is_active ? 'ainpa-status-active' : 'ainpa-status-inactive'); ?>">
                                                    <?php echo e($item->is_active ? 'Active' : 'Inactive'); ?>

                                                </span>
                                                <span class="admin-menu-sort">Sort <?php echo e($item->sort_order); ?></span>
                                                <span class="admin-menu-toggle-text" x-show="open !== '<?php echo e($panelId); ?>'">Open</span>
                                                <span class="admin-menu-toggle-text" x-show="open === '<?php echo e($panelId); ?>'" x-cloak>Close</span>
                                            </span>
                                        </button>

                                        <form method="POST" action="<?php echo e(route('admin.menus.items.destroy', $item)); ?>" onsubmit="return confirm('Remove this menu item?');" class="admin-menu-row-delete-form flex items-center pr-3">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button class="ainpa-button ainpa-button-danger ainpa-button-compact">Delete</button>
                                        </form>
                                    </div>

                                    <div x-show="open === '<?php echo e($panelId); ?>'" x-cloak class="admin-menu-panel">
                                        <form method="POST" action="<?php echo e(route('admin.menus.items.update', $item)); ?>" class="admin-menu-form">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>
                                            <?php echo $__env->make('admin.menus.partials.item-fields', [
                                                'item' => $item,
                                                'activeLocation' => $activeLocation,
                                                'permissions' => $permissions,
                                                'routeNames' => $routeNames,
                                                'parentChoices' => $parentChoices,
                                            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                            <div class="admin-menu-actions">
                                                <button class="ainpa-button">Save Item</button>
                                            </div>
                                        </form>

                                        <p class="admin-menu-inline-help">Use the row Delete button to remove this menu item.</p>
                                    </div>
                                </article>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if($activeLocation === 'frontend'): ?>
                <section class="admin-menu-card">
                    <div class="admin-menu-card-header">
                        <div>
                            <h3 class="admin-menu-card-title">Header, Footer & Blocks Builder</h3>
                            <p class="admin-menu-card-subtitle">Saved in the pages table with content type header, footer, or block.</p>
                        </div>
                        <div class="admin-menu-builder-actions">
                            <?php $__currentLoopData = ['header' => 'New Header', 'footer' => 'New Footer', 'block' => 'New Block']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <form method="POST" action="<?php echo e(route('admin.pages.store')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="content_type" value="<?php echo e($type); ?>">
                                    <button class="ainpa-button ainpa-button-compact"><?php echo e($label); ?></button>
                                </form>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>

                    <div class="admin-menu-builder-grid">
                        <?php $__currentLoopData = $contentTypeLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="admin-menu-builder-column">
                                <h4 class="admin-menu-builder-title"><?php echo e($label); ?></h4>
                                <div class="admin-menu-builder-list">
                                    <?php $__empty_1 = true; $__currentLoopData = $builderGroups->get($type, collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $content): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <div class="admin-menu-builder-item">
                                            <div>
                                                <div class="admin-menu-builder-item-title"><?php echo e($content->title); ?></div>
                                                <div class="admin-menu-builder-item-meta"><?php echo e(ucfirst($content->status)); ?> · <?php echo e($content->updated_at); ?></div>
                                            </div>
                                            <a href="<?php echo e(route('admin.pages.edit', $content->id)); ?>" class="admin-menu-inline-link">Edit</a>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <p class="admin-menu-empty-small">No <?php echo e(strtolower($label)); ?> yet.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/admin/menus/index.blade.php ENDPATH**/ ?>