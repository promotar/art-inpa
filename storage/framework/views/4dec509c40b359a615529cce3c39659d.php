<!DOCTYPE html>
<?php
    try {
        $platformSettings = app(\App\Platform\Core\Services\SettingsRepository::class)->values();
    } catch (\Throwable $exception) {
        $platformSettings = [];
    }

    $siteTitle = $platformSettings['general.site_title'] ?? config('app.name', 'Art INPA');
    $tagline = $platformSettings['general.tagline'] ?? 'International Network for Plastic Art';
    $browserTitle = $tagline !== '' ? $siteTitle.' - '.$tagline : $siteTitle;
    $siteIcon = $platformSettings['general.site_icon'] ?? null;
?>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e($browserTitle); ?></title>
        <?php if($siteIcon): ?>
            <link rel="icon" href="<?php echo e($siteIcon); ?>">
        <?php else: ?>
            <link rel="icon" href="data:,">
        <?php endif; ?>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        <link rel="stylesheet" href="<?php echo e(asset('auth-brand-overrides.css')); ?>?v=20260721-account-responsive-audit-3">
        <?php echo $__env->make('platform.plugin-assets', ['scope' => 'guest', 'kind' => 'styles'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </head>
    <body class="ainpa-auth-body">
        <main class="ainpa-auth-stage">
            <section class="ainpa-auth-card" aria-label="<?php echo e($siteTitle); ?> authentication">
                <a href="<?php echo e(route('front.home')); ?>" class="ainpa-auth-logo" aria-label="<?php echo e($siteTitle); ?>">
                    <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
                    <span class="ainpa-auth-logo-fallback">
                        <strong>I.N.P.A</strong>
                        <small>International Network for Plastic Art</small>
                    </span>
                </a>

                <?php echo e($slot); ?>

            </section>
        </main>
        <?php echo $__env->make('platform.plugin-assets', ['scope' => 'guest', 'kind' => 'scripts'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </body>
</html>
<?php /**PATH /var/www/html/resources/views/layouts/guest.blade.php ENDPATH**/ ?>