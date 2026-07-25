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
        <div class="ainpa-page-toolbar">
            <h2 class="ainpa-page-title">Plugins</h2>
            <a href="<?php echo e(route('admin.plugins.create')); ?>" class="ainpa-button ainpa-button-primary">Install Plugin</a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="ainpa-admin-page">
        <div class="ainpa-page-container">
            <?php if(session('status')): ?>
                <div class="ainpa-alert ainpa-alert-success"><?php echo e(session('status')); ?></div>
            <?php endif; ?>
            <?php if($errors->any()): ?>
                <div class="ainpa-alert ainpa-alert-danger">
                    <?php echo e($errors->first()); ?>

                </div>
            <?php endif; ?>

            <div class="ainpa-card ainpa-table-card">
                <table class="ainpa-data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Version</th>
                            <th>Status</th>
                            <th>Installed</th>
                            <th>Settings</th>
                            <th>Path</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $plugins; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plugin): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="ainpa-table-primary"><?php echo e($plugin->name); ?></td>
                                <td><span class="ainpa-code"><?php echo e($plugin->slug); ?></span></td>
                                <td><?php echo e($plugin->version); ?></td>
                                <td>
                                    <span class="ainpa-status-badge ainpa-status-<?php echo e($plugin->status === 'active' ? 'active' : 'inactive'); ?>">
                                        <?php echo e($plugin->status); ?>

                                    </span>
                                    <?php if($plugin->isCore()): ?>
                                        <span class="ainpa-status-badge ainpa-status-active" title="Required by the platform and protected from deactivation or uninstall.">core</span>
                                    <?php endif; ?>
                                </td>
                                <td class="ainpa-table-muted">
                                    <?php echo e(optional($plugin->installed_at)->format('Y-m-d H:i') ?? 'Not installed'); ?>

                                </td>
                                <td>
                                    <?php ($settings = $plugin->admin_settings_link); ?>
                                    <?php if($settings['available']): ?>
                                        <a href="<?php echo e($settings['url']); ?>" class="ainpa-table-link">
                                            <?php echo e($settings['label']); ?>

                                        </a>
                                    <?php else: ?>
                                        <div class="ainpa-table-note">
                                            <span class="ainpa-table-note-title"><?php echo e($settings['label']); ?></span>
                                            <div><?php echo e($settings['note']); ?></div>
                                            <?php if($settings['route']): ?>
                                                <div class="ainpa-code"><?php echo e($settings['route']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="ainpa-table-path"><?php echo e($plugin->installed_path); ?></td>
                                <td class="ainpa-table-actions">
                                    <?php if($plugin->status === 'active' && ! $plugin->isCore()): ?>
                                        <form method="POST" action="<?php echo e(route('admin.plugins.deactivate', $plugin->slug)); ?>" class="ainpa-action-form">
                                            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                            <button class="ainpa-button ainpa-button-danger ainpa-button-compact">Deactivate</button>
                                        </form>
                                    <?php elseif($plugin->status === 'active'): ?>
                                        <button class="ainpa-button ainpa-button-compact" type="button" disabled title="Core plugins are required by the platform.">Core plugin</button>
                                    <?php else: ?>
                                        <form method="POST" action="<?php echo e(route('admin.plugins.activate', $plugin->slug)); ?>" class="ainpa-action-form">
                                            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                            <button class="ainpa-button ainpa-button-success ainpa-button-compact">Activate</button>
                                        </form>
                                        <?php if (! ($plugin->isCore())): ?>
                                            <form method="POST" action="<?php echo e(route('admin.plugins.destroy', $plugin->slug)); ?>" class="ainpa-action-form" onsubmit="if (this.dataset.submitted === 'true') return false; if (!confirm('Permanently delete <?php echo e($plugin->slug); ?>? All plugin data, database tables, settings, permissions, assets, source files, and package archives will be deleted. This cannot be undone.')) return false; this.dataset.submitted = 'true'; const button = this.querySelector('button[type=submit]'); button.disabled = true; button.textContent = 'Deleting...';">
                                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                                <input type="hidden" name="purge_confirmation" value="<?php echo e($plugin->slug); ?>">
                                                <button type="submit" class="ainpa-button ainpa-button-danger ainpa-button-compact">Purge / Delete data</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="ainpa-empty-table">No plugins installed yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
<?php /**PATH /var/www/html/resources/views/admin/plugins/index.blade.php ENDPATH**/ ?>