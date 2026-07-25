<!DOCTYPE html>
<?php
    try {
        $platformSettings = app(\App\Platform\Core\Services\SettingsRepository::class)->values();
    } catch (\Throwable $exception) {
        $platformSettings = [];
    }

    $siteTitle = $platformSettings['general.site_title'] ?? config('app.name', 'Laravel');
    $tagline = $platformSettings['general.tagline'] ?? '';
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
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        <?php echo $__env->make('platform.plugin-assets', ['scope' => 'admin', 'kind' => 'styles'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo e($styles ?? ''); ?>

        <?php echo $__env->yieldPushContent('styles'); ?>
    </head>
    <body class="font-sans antialiased page-builder-focus-body page-builder-sidebar-compact" data-page-builder-route="admin-pages-edit">
        <script>
            (() => {
                try {
                    if (window.localStorage.getItem('z4-page-builder-sidebar') === 'expanded') {
                        document.body.classList.remove('page-builder-sidebar-compact');
                        document.body.classList.add('page-builder-sidebar-expanded');
                    }
                } catch (error) {
                    //
                }
            })();
        </script>
        <?php echo $__env->make('layouts.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <main class="page-builder-main page-builder-focus-main">
            <?php echo e($slot); ?>

        </main>
        <?php echo $__env->make('platform.plugin-assets', ['scope' => 'admin', 'kind' => 'scripts'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </body>
</html>
<?php /**PATH /var/www/html/resources/views/components/page-builder-focus-layout.blade.php ENDPATH**/ ?>