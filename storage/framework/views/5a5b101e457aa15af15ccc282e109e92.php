<article class="theme-builder-template">
    <div class="theme-builder-template-head">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h5 class="truncate text-base font-semibold text-gray-950"><?php echo e($template->name); ?></h5>
                <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'rounded-full px-2 py-0.5 text-xs font-semibold',
                    'bg-green-100 text-green-700' => $template->status === 'published',
                    'bg-yellow-100 text-yellow-800' => $template->status !== 'published',
                ]); ?>">
                    <?php echo e(ucfirst($template->status)); ?>

                </span>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">
                    <?php echo e(str_replace('_', ' ', $template->template_type)); ?>

                </span>
            </div>
            <div class="mt-2 font-mono text-xs text-gray-500"><?php echo e($template->slug); ?></div>
            <?php if($template->description): ?>
                <div class="mt-2 text-sm text-gray-600"><?php echo e($template->description); ?></div>
            <?php endif; ?>
            <div class="mt-1 text-xs text-gray-400">Source: <?php echo e($template->source_type); ?> · Updated <?php echo e($template->updated_at); ?></div>
        </div>
        <div class="flex flex-wrap justify-end gap-2">
            <a href="<?php echo e(route('admin.theme-builder.templates.builder', $template->id)); ?>" class="rounded-md border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50">
                Edit
            </a>
            <a href="<?php echo e(route('admin.theme-builder.templates.preview', $template->id)); ?>" target="_blank" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                Preview
            </a>
            <form method="POST" action="<?php echo e(route('admin.theme-builder.templates.destroy', $template->id)); ?>" onsubmit="return confirm('Delete this Theme Builder template?');">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button class="rounded-md border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <form method="POST" action="<?php echo e(route('admin.theme-builder.templates.conditions.update', $template->id)); ?>" class="theme-builder-condition">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PATCH'); ?>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <div class="text-sm font-semibold text-gray-900">Display Conditions</div>
                <div class="mt-1 text-xs text-gray-500">Choose where this template should apply.</div>
            </div>
            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-600">
                <?php echo e(ucfirst($template->condition_operator ?? 'include')); ?>:
                <?php echo e($conditionScopes[$template->condition_scope ?? 'entire_site'] ?? 'Entire Site'); ?>

            </span>
        </div>
        <div class="theme-builder-condition-grid">
            <label>
                <span class="theme-builder-field-label">Action</span>
                <select name="operator" class="theme-builder-input">
                    <option value="include" <?php if(($template->condition_operator ?? 'include') === 'include'): echo 'selected'; endif; ?>>Include</option>
                    <option value="exclude" <?php if(($template->condition_operator ?? 'include') === 'exclude'): echo 'selected'; endif; ?>>Exclude</option>
                </select>
            </label>
            <label>
                <span class="theme-builder-field-label">Condition</span>
                <select name="scope" class="theme-builder-input">
                    <?php $__currentLoopData = $conditionScopes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $scope => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($scope); ?>" <?php if(($template->condition_scope ?? 'entire_site') === $scope): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </label>
            <label>
                <span class="theme-builder-field-label">Value</span>
                <input name="target_value" value="<?php echo e($template->condition_target_value); ?>" class="theme-builder-input" placeholder="Optional: slug, category, ID list">
            </label>
            <div class="flex items-end">
                <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                    Save
                </button>
            </div>
        </div>
    </form>
</article>
<?php /**PATH /var/www/html/resources/views/admin/theme-builder/partials/template-card.blade.php ENDPATH**/ ?>