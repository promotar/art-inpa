<?php if (isset($component)) { $__componentOriginal49728f76f6574eefb81a3aaa880242ed = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal49728f76f6574eefb81a3aaa880242ed = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.frontend-layout','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('frontend-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <section class="bg-white">
        <div class="mx-auto flex min-h-[70vh] max-w-7xl items-center justify-center px-4 py-16 sm:px-6 lg:px-8">
            <div class="w-full max-w-4xl border border-slate-200 bg-slate-950 px-8 py-20 text-center text-white sm:px-14">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-red-400">International Network for Plastic Arts</p>
                <h1 class="mt-5 text-5xl font-black tracking-tight sm:text-7xl">ART INPA</h1>
                <p class="mx-auto mt-6 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">
                    Art, culture, education, community, and exhibitions.
                </p>
            </div>
        </div>
    </section>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal49728f76f6574eefb81a3aaa880242ed)): ?>
<?php $attributes = $__attributesOriginal49728f76f6574eefb81a3aaa880242ed; ?>
<?php unset($__attributesOriginal49728f76f6574eefb81a3aaa880242ed); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal49728f76f6574eefb81a3aaa880242ed)): ?>
<?php $component = $__componentOriginal49728f76f6574eefb81a3aaa880242ed; ?>
<?php unset($__componentOriginal49728f76f6574eefb81a3aaa880242ed); ?>
<?php endif; ?><?php /**PATH /var/www/html/resources/views/frontend/home.blade.php ENDPATH**/ ?>