<!DOCTYPE html>
<?php
    try {
        $pageBuilderRouteReady = \Illuminate\Support\Facades\Route::has('pages.show');
        $pageBuilderMenuPages = $pageBuilderRouteReady
            && \Illuminate\Support\Facades\Schema::hasTable('platform_pages')
            && \Illuminate\Support\Facades\Schema::hasColumn('platform_pages', 'show_in_menu')
            ? \Illuminate\Support\Facades\DB::table('platform_pages')
                ->where('content_type', 'page')
                ->where('status', 'published')
                ->where('show_in_menu', true)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get()
            : collect();
    } catch (\Throwable $exception) {
        $pageBuilderMenuPages = collect();
    }

    $pageBuilderMenu = $pageBuilderMenuPages->groupBy('parent_id');

    try {
        $platformSettings = app(\App\Platform\Core\Services\SettingsRepository::class)->values();
    } catch (\Throwable $exception) {
        $platformSettings = [];
    }

    $siteTitle = $platformSettings['general.site_title'] ?? config('app.name', 'Z4Rank Store');
    $tagline = $platformSettings['general.tagline'] ?? '';
    $browserTitle = $tagline !== '' ? $siteTitle.' - '.$tagline : $siteTitle;
    $seoTitle = $platformSettings['seo.seo_title'] ?? $siteTitle;
    $seoDescription = $platformSettings['seo.seo_description'] ?? $tagline;
    $seoKeywords = $platformSettings['seo.seo_keywords'] ?? '';
    $robots = (($platformSettings['seo.robots_index'] ?? true) ? 'index' : 'noindex').','.(($platformSettings['seo.robots_follow'] ?? true) ? 'follow' : 'nofollow');
    $ogTitle = $platformSettings['seo.open_graph_title'] ?? $seoTitle;
    $ogDescription = $platformSettings['seo.open_graph_description'] ?? $seoDescription;
    $ogImage = $platformSettings['seo.open_graph_image'] ?? null;
    $siteIcon = $platformSettings['general.site_icon'] ?? null;
    $siteLogo = $platformSettings['general.site_logo'] ?? null;
    $siteLanguage = ($platformSettings['general.site_language'] ?? 'ar') === 'en' ? 'en' : 'ar';
    $isArabicLanguage = $siteLanguage === 'ar';
    $htmlDirection = $isArabicLanguage ? 'rtl' : 'ltr';
    $translations = [
        'Home' => 'الرئيسية',
        'My Account' => 'حسابي',
        'Dashboard' => 'لوحة التحكم',
        'Admin' => 'الإدارة',
        'Log Out' => 'تسجيل الخروج',
        'Log In' => 'تسجيل الدخول',
        'Register' => 'إنشاء حساب',
    ];
    $t = fn (string $text): string => $isArabicLanguage ? ($translations[$text] ?? $text) : $text;

    try {
        $frontendMenuItems = app(\App\Platform\Core\Menus\MenuManager::class)->getFrontendMenu(auth()->user());
    } catch (\Throwable $exception) {
        $frontendMenuItems = [];
    }

    if ($frontendMenuItems === []) {
        $frontendMenuItems = [[
            'id' => 'fallback-home',
            'title' => 'Home',
            'label' => 'Home',
            'route_name' => 'front.home',
            'route_params' => [],
            'target' => '_self',
            'metadata' => [],
        ]];
    }

    $frontendMenuRouteNames = collect($frontendMenuItems)
        ->pluck('route_name')
        ->filter()
        ->values()
        ->all();

    $frontendMenuHref = function (array $item): ?string {
        $routeName = $item['route_name'] ?? null;
        $url = $item['url'] ?? null;

        if (is_string($routeName) && \Illuminate\Support\Facades\Route::has($routeName)) {
            return route($routeName, $item['route_params'] ?? []);
        }

        if (is_string($url) && trim($url) !== '') {
            return str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
                ? $url
                : url($url);
        }

        return null;
    };

    $frontendMenuClass = function (array $item): string {
        $style = data_get($item, 'metadata.style', []);
        $cssClass = is_array($style) ? (string) ($style['css_class'] ?? '') : '';
        $cssClass = trim(preg_replace('/[^A-Za-z0-9_:\-\s\/\[\]\.]/', '', $cssClass) ?? '');

        return trim('text-slate-700 hover:text-slate-950 '.$cssClass);
    };

    $frontendStyleBundle = null;
    $frontendStyleBundleUrl = null;
    if (class_exists(\App\Platform\Core\Theme\FrontendStyleBundle::class)) {
        try {
            $frontendStyleBundle = app(\App\Platform\Core\Theme\FrontendStyleBundle::class);
            $frontendStyleBundleUrl = $frontendStyleBundle->url($frontendMenuItems);
        } catch (\Throwable $exception) {
            report($exception);
            $frontendStyleBundle = null;
            $frontendStyleBundleUrl = null;
        }
    }

    $contentRenderer = app(\App\Platform\Core\Rendering\PlatformContentRenderer::class);
    $dynamicHeaders = collect();
    $dynamicFooters = collect();
    $dynamicLayoutCss = '';

    try {
        $dynamicHeaders = $contentRenderer->publishedLayoutSections('header');
        $dynamicFooters = $contentRenderer->publishedLayoutSections('footer');
        $dynamicLayoutCss = $contentRenderer->layoutCss();
    } catch (\Throwable $exception) {
        $dynamicHeaders = collect();
        $dynamicFooters = collect();
        $dynamicLayoutCss = '';
    }

?>
<html lang="<?php echo e($siteLanguage); ?>" dir="<?php echo e($htmlDirection); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <?php if (! (isset($head))): ?>
            <title><?php echo e($browserTitle); ?></title>
            <meta name="description" content="<?php echo e($seoDescription); ?>">
            <meta name="keywords" content="<?php echo e($seoKeywords); ?>">
            <meta name="robots" content="<?php echo e($robots); ?>">
            <meta property="og:title" content="<?php echo e($ogTitle); ?>">
            <meta property="og:description" content="<?php echo e($ogDescription); ?>">
            <?php if($ogImage): ?>
                <meta property="og:image" content="<?php echo e(url($ogImage)); ?>">
            <?php endif; ?>
        <?php endif; ?>
        <?php if($siteIcon): ?>
            <link rel="icon" href="<?php echo e($siteIcon); ?>">
        <?php else: ?>
            <link rel="icon" href="data:,">
        <?php endif; ?>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        <?php if($frontendStyleBundleUrl): ?>
            <link rel="stylesheet" href="<?php echo e($frontendStyleBundleUrl); ?>">
        <?php endif; ?>
        <?php echo $__env->make('platform.plugin-assets', ['scope' => 'frontend', 'kind' => 'styles'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php if($dynamicLayoutCss !== ''): ?>
            <style data-platform-layout-css><?php echo $dynamicLayoutCss; ?></style>
        <?php endif; ?>
        <?php if(isset($head)): ?>
            <?php echo e($head); ?>

        <?php endif; ?>
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-950">
        <div class="min-h-screen">
            <?php if($dynamicHeaders->isNotEmpty()): ?>
                <?php $__currentLoopData = $dynamicHeaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dynamicHeader): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div data-platform-content-type="header" data-platform-content-id="<?php echo e($dynamicHeader->id); ?>">
                        <?php echo $dynamicHeader->rendered_html; ?>

                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php else: ?>
                <header class="border-b border-slate-200 bg-white">
                    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                        <a href="<?php echo e(route('front.home')); ?>" class="flex items-center text-lg font-bold tracking-tight text-slate-950">
                            <?php if($siteLogo): ?>
                                <img src="<?php echo e($siteLogo); ?>" alt="<?php echo e($siteTitle); ?>" class="h-10 w-auto object-contain">
                            <?php else: ?>
                                <?php echo e($siteTitle); ?>

                            <?php endif; ?>
                        </a>

                        <nav class="flex items-center gap-3 text-sm font-medium">
                            <?php $__currentLoopData = $frontendMenuItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $frontendMenuItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $href = $frontendMenuHref($frontendMenuItem);
                                    $target = $frontendMenuItem['target'] ?? '_self';
                                ?>
                                <?php if($href): ?>
                                    <a
                                        href="<?php echo e($href); ?>"
                                    class="<?php echo e($frontendStyleBundle ? $frontendStyleBundle->menuItemClass($frontendMenuItem) : ''); ?> <?php echo e($frontendMenuClass($frontendMenuItem)); ?>"
                                        <?php if($target === '_blank'): ?> target="_blank" rel="noopener" <?php endif; ?>
                                    >
                                        <?php echo e($t((string) ($frontendMenuItem['label'] ?: $frontendMenuItem['title']))); ?>

                                    </a>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php $__currentLoopData = $pageBuilderMenu->get('', collect()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $menuPage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $children = $pageBuilderMenu->get($menuPage->id, collect()); ?>
                                <?php if($children->isNotEmpty()): ?>
                                    <div class="relative group">
                                        <a href="<?php echo e(route('pages.show', $menuPage->slug)); ?>" class="text-slate-700 hover:text-slate-950">
                                            <?php echo e($menuPage->menu_label ?: $menuPage->title); ?>

                                        </a>
                                        <div class="absolute left-0 top-full z-20 hidden min-w-48 border border-slate-200 bg-white py-2 shadow-lg group-hover:block">
                                            <?php $__currentLoopData = $children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $childPage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <a href="<?php echo e(route('pages.show', $childPage->slug)); ?>" class="block px-4 py-2 text-slate-700 hover:bg-slate-50 hover:text-slate-950">
                                                    <?php echo e($childPage->menu_label ?: $childPage->title); ?>

                                                </a>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <a href="<?php echo e(route('pages.show', $menuPage->slug)); ?>" class="text-slate-700 hover:text-slate-950">
                                        <?php echo e($menuPage->menu_label ?: $menuPage->title); ?>

                                    </a>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if(auth()->guard()->check()): ?>
                                <?php if(! in_array('front.account', $frontendMenuRouteNames, true)): ?>
                                    <a href="<?php echo e(route('front.account')); ?>" class="text-slate-700 hover:text-slate-950"><?php echo e($t('My Account')); ?></a>
                                <?php endif; ?>
                                <?php if(auth()->user()->hasAnyRole(['super-admin', 'admin', 'staff', 'employee']) || auth()->user()->getAllPermissions()->isNotEmpty()): ?>
                                    <a href="<?php echo e(route('dashboard')); ?>" class="text-slate-700 hover:text-slate-950"><?php echo e($t('Dashboard')); ?></a>
                                <?php endif; ?>
                                <?php if(auth()->user()->hasRole('super-admin')): ?>
                                    <a href="<?php echo e(route('admin.platform-registry.index')); ?>" class="text-slate-700 hover:text-slate-950"><?php echo e($t('Admin')); ?></a>
                                <?php endif; ?>
                                <form method="POST" action="<?php echo e(route('logout')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="rounded-md bg-slate-950 px-3 py-2 text-white hover:bg-slate-800"><?php echo e($t('Log Out')); ?></button>
                                </form>
                            <?php else: ?>
                                <a href="<?php echo e(route('login')); ?>" class="text-slate-700 hover:text-slate-950"><?php echo e($t('Log In')); ?></a>
                                <a href="<?php echo e(route('register')); ?>" class="rounded-md bg-slate-950 px-3 py-2 text-white hover:bg-slate-800"><?php echo e($t('Register')); ?></a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </header>
            <?php endif; ?>

            <main>
                <?php if(session('status')): ?>
                    <div class="mx-auto mt-6 max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            <?php echo e(session('status')); ?>

                        </div>
                    </div>
                <?php endif; ?>

                <?php echo e($slot); ?>

            </main>

            <?php $__currentLoopData = $dynamicFooters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dynamicFooter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div data-platform-content-type="footer" data-platform-content-id="<?php echo e($dynamicFooter->id); ?>">
                    <?php echo $dynamicFooter->rendered_html; ?>

                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php echo $__env->make('platform.plugin-assets', ['scope' => 'frontend', 'kind' => 'scripts'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </body>
</html>
<?php /**PATH /var/www/html/resources/views/components/frontend-layout.blade.php ENDPATH**/ ?>