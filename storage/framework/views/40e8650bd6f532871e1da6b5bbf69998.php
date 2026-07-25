<?php
    $itemType = old('type', $item->type ?? 'route');
    $isActive = old('is_active', $item->is_active ?? true);
    $style = is_array($item?->metadata ?? null) ? ($item->metadata['style'] ?? []) : [];
    $parentChoices = $parentChoices ?? collect();
?>

<div class="admin-menu-form-grid admin-menu-form-grid-two">
    <label class="admin-field">
        <span class="admin-field-label">Title</span>
        <input name="title" value="<?php echo e(old('title', $item->title ?? '')); ?>" required class="admin-input">
    </label>

    <label class="admin-field">
        <span class="admin-field-label">Label</span>
        <input name="label" value="<?php echo e(old('label', $item->label ?? '')); ?>" class="admin-input">
    </label>
</div>

<div class="admin-menu-form-grid admin-menu-form-grid-three">
    <label class="admin-field">
        <span class="admin-field-label">Parent item</span>
        <select name="parent_id" class="admin-input">
            <option value="">Main item</option>
            <?php $__currentLoopData = $parentChoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $choice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($item && (int) $choice['item']->id === (int) $item->id) continue; ?>
                <option value="<?php echo e($choice['item']->id); ?>" <?php if((int) old('parent_id', $item->parent_id ?? 0) === (int) $choice['item']->id): echo 'selected'; endif; ?>>
                    <?php echo e(str_repeat('— ', (int) $choice['depth'])); ?><?php echo e($choice['item']->title); ?>

                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <span class="admin-field-hint">Use Main item for primary links, or choose a parent to make this a submenu item.</span>
    </label>

    <label class="admin-field">
        <span class="admin-field-label">Required Permission</span>
        <select name="permission" class="admin-input">
            <option value="">No permission</option>
            <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($permission); ?>" <?php if(old('permission', $item->permission ?? '') === $permission): echo 'selected'; endif; ?>><?php echo e($permission); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </label>

    <label class="admin-field admin-menu-check-field">
        <input type="hidden" name="is_active" value="0">
        <span class="admin-menu-check-option">
            <input type="checkbox" name="is_active" value="1" <?php if((bool) $isActive): echo 'checked'; endif; ?>>
            <span>Active</span>
        </span>
    </label>
</div>

<?php if(($activeLocation ?? '') === 'frontend'): ?>
    <section class="admin-menu-style-panel">
        <div class="admin-menu-panel-heading">
            <div>
                <h4 class="admin-menu-panel-title">Frontend Style</h4>
                <p class="admin-menu-panel-description">These values apply to this item in the public frontend navigation.</p>
            </div>
        </div>

        <div class="admin-menu-form-grid admin-menu-form-grid-two">
            <label class="admin-field">
                <span class="admin-field-label">CSS Classes</span>
                <input
                    name="css_class"
                    value="<?php echo e(old('css_class', $style['css_class'] ?? '')); ?>"
                    placeholder="rounded-md px-3 py-2"
                    class="admin-input"
                >
            </label>

            <label class="admin-field">
                <span class="admin-field-label">Font Weight</span>
                <select name="font_weight" class="admin-input">
                    <option value="">Default</option>
                    <?php $__currentLoopData = ['normal' => 'Normal', 'medium' => 'Medium', 'semibold' => 'Semibold', 'bold' => 'Bold']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if(old('font_weight', $style['font_weight'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </label>
        </div>

        <div class="admin-menu-color-grid">
            <label class="admin-field">
                <span class="admin-field-label">Text</span>
                <input type="color" name="text_color" value="<?php echo e(old('text_color', $style['text_color'] ?? '#334155')); ?>" class="admin-color-input">
            </label>

            <label class="admin-field">
                <span class="admin-field-label">Background</span>
                <input type="color" name="background_color" value="<?php echo e(old('background_color', $style['background_color'] ?? '#ffffff')); ?>" class="admin-color-input">
            </label>

            <label class="admin-field">
                <span class="admin-field-label">Hover Text</span>
                <input type="color" name="hover_text_color" value="<?php echo e(old('hover_text_color', $style['hover_text_color'] ?? '#0f172a')); ?>" class="admin-color-input">
            </label>

            <label class="admin-field">
                <span class="admin-field-label">Hover Background</span>
                <input type="color" name="hover_background_color" value="<?php echo e(old('hover_background_color', $style['hover_background_color'] ?? '#f8fafc')); ?>" class="admin-color-input">
            </label>
        </div>

        <div class="admin-menu-form-grid admin-menu-form-grid-two">
            <label class="admin-field">
                <span class="admin-field-label">Border Radius</span>
                <input name="border_radius" value="<?php echo e(old('border_radius', $style['border_radius'] ?? '')); ?>" placeholder="6px" class="admin-input">
            </label>

            <label class="admin-field">
                <span class="admin-field-label">Padding</span>
                <input name="padding" value="<?php echo e(old('padding', $style['padding'] ?? '')); ?>" placeholder="8px 12px" class="admin-input">
            </label>
        </div>
    </section>
<?php endif; ?>

<div x-data="{ itemType: <?php echo \Illuminate\Support\Js::from($itemType)->toHtml() ?> }" class="admin-menu-form-stack">
    <div class="admin-menu-form-grid admin-menu-form-grid-four">
        <label class="admin-field">
            <span class="admin-field-label">Type</span>
            <select name="type" x-model="itemType" class="admin-input">
                <option value="route" <?php if($itemType === 'route'): echo 'selected'; endif; ?>>Route</option>
                <option value="link" <?php if($itemType === 'link'): echo 'selected'; endif; ?>>Link</option>
                <option value="header" <?php if($itemType === 'header'): echo 'selected'; endif; ?>>Header</option>
            </select>
        </label>

        <label class="admin-field">
            <span class="admin-field-label">Icon</span>
            <input name="icon" value="<?php echo e(old('icon', $item->icon ?? '')); ?>" maxlength="24" class="admin-input">
        </label>

        <label class="admin-field">
            <span class="admin-field-label">Sort</span>
            <input name="sort_order" type="number" value="<?php echo e(old('sort_order', $item->sort_order ?? 0)); ?>" class="admin-input">
        </label>

        <label class="admin-field">
            <span class="admin-field-label">Target</span>
            <select name="target" class="admin-input">
                <option value="_self" <?php if(old('target', $item->target ?? '_self') === '_self'): echo 'selected'; endif; ?>>Same tab</option>
                <option value="_blank" <?php if(old('target', $item->target ?? '_self') === '_blank'): echo 'selected'; endif; ?>>New tab</option>
            </select>
        </label>
    </div>

    <div class="admin-menu-form-grid admin-menu-form-grid-two">
        <label class="admin-field" x-show="itemType === 'route'">
            <span class="admin-field-label">Route Name</span>
            <select name="route_name" class="admin-input">
                <option value="">No route</option>
                <?php $__currentLoopData = $routeNames; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $routeName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($routeName); ?>" <?php if(old('route_name', $item->route_name ?? '') === $routeName): echo 'selected'; endif; ?>><?php echo e($routeName); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </label>

        <label class="admin-field" x-show="itemType === 'link'">
            <span class="admin-field-label">URL</span>
            <input name="url" value="<?php echo e(old('url', $item->url ?? '')); ?>" placeholder="/custom-path or https://example.com" class="admin-input">
        </label>

        <div x-show="itemType === 'header'" class="admin-menu-note admin-menu-note-warning">
            Header items are labels only and do not render as links.
        </div>
    </div>
</div>
<?php /**PATH /var/www/html/resources/views/admin/menus/partials/item-fields.blade.php ENDPATH**/ ?>