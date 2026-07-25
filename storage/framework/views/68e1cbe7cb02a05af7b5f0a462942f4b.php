<?php
    try {
        $settings = app(\App\Platform\Core\Services\SettingsRepository::class)->values();
    } catch (\Throwable $exception) {
        $settings = [];
    }

    $isArabicLanguage = false;
    $t = fn (string $english, string $arabic): string => $isArabicLanguage ? $arabic : $english;

    $cards = [
        [
            'label' => $t('Settings', 'الإعدادات'),
            'description' => $t('Manage platform settings.', 'إدارة إعدادات المنصة.'),
            'route' => 'admin.settings.index',
            'permission' => 'settings.manage',
        ],
        [
            'label' => $t('Media', 'الوسائط'),
            'description' => $t('Upload and review media library files.', 'رفع ومراجعة ملفات مكتبة الوسائط.'),
            'route' => 'admin.media.index',
            'permission' => 'media.manage',
        ],
        [
            'label' => $t('Pages', 'الصفحات'),
            'description' => $t('Create and manage content pages.', 'إنشاء وإدارة صفحات المحتوى.'),
            'route' => 'admin.pages.index',
            'permission' => 'pages.manage',
        ],
        [
            'label' => $t('Themes', 'القوالب'),
            'description' => $t('Upload frontend and admin themes.', 'رفع وتفعيل قوالب الواجهة ولوحة الإدارة.'),
            'route' => 'admin.themes.index',
            'permission' => 'themes.manage',
        ],
        [
            'label' => $t('Plugins', 'الإضافات'),
            'description' => $t('View installed plugins.', 'عرض الإضافات المثبتة.'),
            'route' => 'admin.plugins.index',
            'permission' => 'plugins.view',
        ],
        [
            'label' => $t('Install Plugins', 'تثبيت الإضافات'),
            'description' => $t('Install discovered plugins.', 'تثبيت الإضافات المكتشفة.'),
            'route' => 'admin.plugins.create',
            'permission' => 'plugins.install',
        ],
        [
            'label' => $t('Users', 'المستخدمون'),
            'description' => $t('Create and update users.', 'إنشاء وتحديث المستخدمين.'),
            'route' => 'admin.users.index',
            'permission' => 'users.manage',
        ],
        [
            'label' => $t('Roles', 'الأدوار'),
            'description' => $t('Manage roles and role permissions.', 'إدارة الأدوار وصلاحياتها.'),
            'route' => 'admin.roles.index',
            'permission' => 'roles.manage',
        ],
        [
            'label' => $t('Permissions', 'الصلاحيات'),
            'description' => $t('Create and review platform permissions.', 'إنشاء ومراجعة صلاحيات المنصة.'),
            'route' => 'admin.permissions.index',
            'permission' => 'permissions.manage',
        ],
        [
            'label' => $t('Admin', 'الإدارة'),
            'description' => $t('Manage registry, documentation, logs, reports, and backups.', 'إدارة السجل والوثائق والسجلات والتقارير والنسخ الاحتياطية.'),
            'route' => 'admin.platform-registry.index',
            'super_admin_only' => true,
        ],
    ];

    $routeAccess = app(\App\Platform\Core\Access\RouteAccessGate::class);
    $visibleCards = collect($cards)->filter(fn (array $card): bool =>
        \Illuminate\Support\Facades\Route::has($card['route'])
        && $routeAccess->allowsRouteName(auth()->user(), $card['route'])
    );
?>

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
        <h2 class="ainpa-page-title">
            <?php echo e($t('Dashboard', 'لوحة التحكم')); ?>

        </h2>
     <?php $__env->endSlot(); ?>

    <div class="ainpa-dashboard-page">
        <div class="ainpa-page-container">
            <?php if(session('status')): ?>
                <div class="ainpa-alert ainpa-alert-warning">
                    <?php echo e(session('status')); ?>

                </div>
            <?php endif; ?>

            <div class="ainpa-dashboard-hero">
                <h3 class="ainpa-section-title"><?php echo e($t('Available Tools', 'الأدوات المتاحة')); ?></h3>
                <p class="ainpa-section-description">
                    <?php echo e($t('Only tools allowed by your permissions are shown here.', 'تظهر هنا فقط الأدوات المسموحة حسب صلاحياتك.')); ?>

                </p>
            </div>

            <?php if($visibleCards->isEmpty()): ?>
                <div class="ainpa-empty-state">
                    <?php echo e($t('No admin tools are currently assigned to your account.', 'لا توجد أدوات إدارية مخصصة لحسابك حاليًا.')); ?>

                </div>
            <?php else: ?>
                <div class="ainpa-tool-grid">
                    <?php $__currentLoopData = $visibleCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route($card['route'])); ?>" class="ainpa-tool-card">
                            <h3 class="ainpa-tool-card-title"><?php echo e($card['label']); ?></h3>
                            <p class="ainpa-tool-card-description"><?php echo e($card['description']); ?></p>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
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
<?php /**PATH /var/www/html/resources/views/dashboard.blade.php ENDPATH**/ ?>