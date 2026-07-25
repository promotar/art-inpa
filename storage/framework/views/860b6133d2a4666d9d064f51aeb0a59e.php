<?php
    $pluginAssetScope = in_array($scope ?? null, ['admin', 'frontend', 'guest'], true) ? $scope : 'frontend';
    $pluginAssetKind = ($kind ?? null) === 'scripts' ? 'scripts' : 'styles';

    try {
        $pluginAssetEntries = app(\App\Platform\Core\Services\PluginAssetRegistry::class)
            ->assets($pluginAssetScope)[$pluginAssetKind] ?? [];
    } catch (\Throwable $exception) {
        report($exception);
        $pluginAssetEntries = [];
    }
?>

<?php $__currentLoopData = $pluginAssetEntries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pluginAsset): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if($pluginAssetKind === 'styles'): ?>
        <link
            rel="stylesheet"
            href="<?php echo e($pluginAsset['url']); ?>"
            data-plugin-asset="<?php echo e($pluginAsset['slug']); ?>"
            data-plugin-asset-scope="<?php echo e($pluginAssetScope); ?>"
            data-plugin-asset-path="<?php echo e($pluginAsset['path']); ?>"
        >
    <?php else: ?>
        <script
            src="<?php echo e($pluginAsset['url']); ?>"
            data-plugin-asset="<?php echo e($pluginAsset['slug']); ?>"
            data-plugin-asset-scope="<?php echo e($pluginAssetScope); ?>"
            data-plugin-asset-path="<?php echo e($pluginAsset['path']); ?>"
            defer
        ></script>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH /var/www/html/resources/views/platform/plugin-assets.blade.php ENDPATH**/ ?>