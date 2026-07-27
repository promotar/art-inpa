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
        <div class="admin-theme-settings-heading">
            <div>
                <span>Appearance</span>
                <h2>Admin Theme</h2>
            </div>

            <a href="<?php echo e(route('admin.plugins.index')); ?>">Back to plugins</a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="admin-theme-settings-page">
        <?php if(session('status')): ?>
            <div class="admin-theme-settings-notice"><?php echo e(session('status')); ?></div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="admin-theme-settings-notice admin-theme-settings-notice--error">
                <?php echo e($errors->first()); ?>

            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('admin.plugins.admin-theme.settings.update')); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>

            <section class="admin-theme-settings-panel">
                <div class="admin-theme-settings-status">
                    <span class="admin-theme-settings-mark">A</span>
                    <div>
                        <h3>Art INPA Admin Theme</h3>
                        <p>Changes are applied across the administration dashboard after saving.</p>
                    </div>
                    <strong>Version <?php echo e($themeVersion); ?></strong>
                </div>

                <div class="admin-theme-settings-grid">
                    <?php $__currentLoopData = [
                        'sidebar_width',
                        'sidebar_background',
                        'sidebar_text_color',
                        'active_menu_color',
                        'primary_color',
                        'page_background',
                        'card_background',
                        'card_padding',
                        'card_margin',
                        'border_color',
                        'border_size',
                        'font_family',
                        'base_font_size',
                        'border_radius',
                        'content_padding',
                        'header_height',
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $definition = $definitions[$key];
                            $value = old($key, $values[$key]);
                        ?>

                        <label class="admin-theme-setting-field">
                            <span><?php echo e($definition['label']); ?></span>

                            <?php if($definition['type'] === 'color'): ?>
                                <span class="admin-theme-color-control">
                                    <input type="color" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
                                    <output><?php echo e($value); ?></output>
                                </span>
                            <?php elseif($definition['type'] === 'select'): ?>
                                <select name="<?php echo e($key); ?>">
                                    <?php $__currentLoopData = $definition['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionValue => $optionLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($optionValue); ?>" <?php if($value === $optionValue): echo 'selected'; endif; ?>>
                                            <?php echo e($optionLabel); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            <?php else: ?>
                                <span class="admin-theme-number-control">
                                    <input
                                        type="number"
                                        name="<?php echo e($key); ?>"
                                        value="<?php echo e($value); ?>"
                                        min="<?php echo e($definition['min']); ?>"
                                        max="<?php echo e($definition['max']); ?>"
                                        step="1"
                                    >
                                    <small><?php echo e($definition['unit']); ?></small>
                                </span>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <label class="admin-theme-css-field">
                    <span>Custom CSS editor</span>
                    <textarea
                        name="custom_css"
                        rows="14"
                        spellcheck="false"
                        placeholder="/* Add administration-only CSS overrides here. */"
                    ><?php echo e(old('custom_css', $values['custom_css'])); ?></textarea>
                </label>

                <div class="admin-theme-settings-actions">
                    <button type="submit">Save changes</button>
                    <span>All numeric layout values use pixels.</span>
                </div>
            </section>
        </form>

        <form
            method="POST"
            action="<?php echo e(route('admin.plugins.admin-theme.settings.reset')); ?>"
            class="admin-theme-reset-form"
            onsubmit="return confirm('Restore all admin theme settings to their defaults?')"
        >
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="submit">Restore defaults</button>
        </form>
    </div>

    <script>
        document.querySelectorAll('.admin-theme-color-control input[type="color"]').forEach((input) => {
            input.addEventListener('input', () => {
                input.nextElementSibling.textContent = input.value.toUpperCase();
            });
        });
    </script>
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
<?php /**PATH /var/www/html/modules/admin-theme/resources/views/settings.blade.php ENDPATH**/ ?>