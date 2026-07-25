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
<html lang="en" dir="ltr">
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

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        <?php echo $__env->make('platform.plugin-assets', ['scope' => 'admin', 'kind' => 'styles'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->yieldPushContent('styles'); ?>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100" style="padding-top: 32px; padding-left: 160px;">
            <?php echo $__env->make('layouts.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <!-- Page Heading -->
            <?php if(isset($header)): ?>
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        <?php echo e($header); ?>

                    </div>
                </header>
            <?php endif; ?>

            <!-- Page Content -->
            <main>
                <?php echo e($slot); ?>

            </main>
        </div>
        <?php echo $__env->make('platform.plugin-assets', ['scope' => 'admin', 'kind' => 'scripts'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </body>
</html>
<?php /**PATH /var/www/html/resources/views/layouts/app.blade.php ENDPATH**/ ?>