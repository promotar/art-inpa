<?php if (isset($component)) { $__componentOriginalad9a3a6132492030e5bc73fea4481fef = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad9a3a6132492030e5bc73fea4481fef = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-builder-focus-layout','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-builder-focus-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('styles', null, []); ?> 
        <link rel="stylesheet" href="<?php echo e(asset('vendor/front-builder/grapesjs/grapes.min.css')); ?>">
        <link rel="stylesheet" href="<?php echo e(asset('vendor/front-builder/page-builder/page-builder.css')); ?>?v=theme-builder-template-builder">
     <?php $__env->endSlot(); ?>

    <form
        id="page-builder-form"
        method="POST"
        action="<?php echo e(route('admin.theme-builder.templates.update', $template->id)); ?>"
        data-builder-save-url="<?php echo e(route('admin.theme-builder.templates.builder-save', $template->id)); ?>"
        data-builder-autosave-url="<?php echo e(route('admin.theme-builder.templates.autosave', $template->id)); ?>"
        data-builder-revisions-url=""
        data-builder-media-url="<?php echo e(route('admin.media.index')); ?>"
        data-simple-template-save-url=""
        class="page-builder-focus-layout"
    >
        <?php echo csrf_field(); ?>
        <?php echo method_field('PATCH'); ?>
        <input type="hidden" name="page_builder_json" id="page_builder_json">
        <input type="hidden" name="html" id="page_html">
        <input type="hidden" name="css" id="page_css">

        <header class="page-builder-header">
            <div class="page-builder-header-title">
                <span>Theme Template Builder</span>
                <h1><?php echo e($template->name); ?></h1>
            </div>

            <div class="page-builder-header-actions">
                <a href="<?php echo e(route('admin.theme-builder.index', ['tab' => $template->template_type])); ?>" class="page-builder-action page-builder-action--muted">Theme Builder</a>
                <a href="<?php echo e($previewUrl); ?>" target="_blank" class="page-builder-action page-builder-action--muted">Preview</a>
                <button type="button" class="page-builder-action page-builder-action--ghost" data-page-settings-toggle>Template Settings</button>
                <button type="submit" class="page-builder-action page-builder-action--primary">Save</button>
            </div>
        </header>

        <section class="page-builder-statusbar" aria-label="Template status">
            <div class="page-builder-statusbar-path">
                <span>Template:</span>
                <strong><?php echo e($templateTypes[$template->template_type] ?? ucfirst((string) $template->template_type)); ?> / <?php echo e($template->slug); ?></strong>
            </div>

            <?php if(session('status')): ?>
                <div class="page-builder-alert page-builder-alert--success"><?php echo e(session('status')); ?></div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="page-builder-alert page-builder-alert--danger"><?php echo e($errors->first()); ?></div>
            <?php endif; ?>

            <div class="page-builder-alert page-builder-alert--status" data-builder-save-status hidden></div>

            <div class="page-builder-statusbar-actions">
                <span>Preview:</span>
                <a href="<?php echo e($previewUrl); ?>" target="_blank" class="page-builder-public-url"><?php echo e($previewUrl); ?></a>
                <span class="page-builder-statusbar-state">
                    <span>Status:</span>
                    <strong>
                        <span class="page-builder-dot page-builder-dot--<?php echo e(old('status', $template->status) === 'published' ? 'published' : 'draft'); ?>"></span>
                        <?php echo e(ucfirst(old('status', $template->status))); ?>

                    </strong>
                </span>
            </div>
        </section>

        <aside class="page-builder-settings-drawer" data-page-settings-drawer hidden aria-label="Template settings">
            <div class="page-builder-settings-drawer-header">
                <div>
                    <span>Template Settings</span>
                    <strong><?php echo e($templateTypes[old('content_type', $template->template_type)] ?? ucfirst((string) old('content_type', $template->template_type))); ?> · <?php echo e(ucfirst(old('status', $template->status))); ?></strong>
                </div>
                <button type="button" class="page-builder-icon-button" data-page-settings-close aria-label="Close template settings">x</button>
            </div>

            <div class="page-builder-settings-drawer-body">
                <label class="page-setting-field">
                    <span>Template Name</span>
                    <input name="title" value="<?php echo e(old('title', $template->name)); ?>" required>
                </label>

                <label class="page-setting-field">
                    <span>Slug</span>
                    <input name="slug" value="<?php echo e(old('slug', $template->slug)); ?>">
                </label>

                <label class="page-setting-field">
                    <span>Template Type</span>
                    <select name="content_type">
                        <?php $__currentLoopData = $templateTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if(old('content_type', $template->template_type) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </label>

                <label class="page-setting-field">
                    <span>Status</span>
                    <select name="status">
                        <option value="draft" <?php if(old('status', $template->status) === 'draft'): echo 'selected'; endif; ?>>Draft</option>
                        <option value="published" <?php if(old('status', $template->status) === 'published'): echo 'selected'; endif; ?>>Published</option>
                    </select>
                </label>

                <label class="page-setting-field page-setting-field--wide">
                    <span>Description</span>
                    <textarea name="meta_description" rows="4"><?php echo e(old('meta_description', $template->description)); ?></textarea>
                </label>

                <div class="page-setting-url page-setting-field--wide">
                    <span>Storage</span>
                    <strong>platform_theme_builder_templates</strong>
                </div>
            </div>
        </aside>

        <nav class="page-builder-toolbar" aria-label="Builder tools">
            <div class="page-builder-toolbar-group">
                <button type="button" data-builder-panel="blocks" class="is-active">Elements</button>
                <button type="button" data-builder-panel="layers">Layers</button>
                <button type="button" data-builder-panel="traits">Content</button>
                <button type="button" data-builder-panel="style">Style</button>
                <button type="button" data-builder-panel="advanced">Advanced</button>
                <button type="button" data-builder-panel="dynamic">Dynamic</button>
            </div>
            <div class="page-builder-toolbar-group page-builder-toolbar-group--devices">
                <button type="button" data-builder-device="Desktop" class="is-active">Desktop</button>
                <button type="button" data-builder-device="Tablet">Tablet</button>
                <button type="button" data-builder-device="Mobile portrait">Mobile</button>
            </div>
        </nav>

        <section class="page-builder-workspace">
            <div class="page-builder-canvas">
                <div id="gjs"></div>
            </div>

            <aside class="page-builder-inspector page-builder-right-panel" aria-label="Element controls">
                <div class="page-builder-inspector-header page-builder-right-panel-header">
                    <span>Inspector</span>
                    <strong>Elements, layers, content and style</strong>
                </div>
                <div class="page-builder-inspector-body page-builder-right-panel-body" data-builder-inspector-host data-builder-right-panel-host></div>
            </aside>
        </section>
    </form>

    <?php
        $pageBuilderConfig = [
            'builderProject' => $builderProject,
            'initialHtml' => $editorCanvasHtml ?? ($template->html ?? ''),
            'initialCss' => $template->css ?? '',
            'editorCanvasCss' => $editorCanvasCss ?? '',
            'editorCanvasStyleUrls' => $editorCanvasStyleUrls,
            'widgets' => $builderWidgets,
            'blocks' => $builderBlocks,
            'elementRegistry' => $builderElementRegistry,
            'dynamicSources' => $builderDynamicSources,
            'mediaUrl' => route('admin.media.index'),
            'builderSaveUrl' => route('admin.theme-builder.templates.builder-save', $template->id),
            'autosaveUrl' => route('admin.theme-builder.templates.autosave', $template->id),
            'revisionsUrl' => '',
            'editorComponentPreviewUrl' => route('admin.theme-builder.templates.editor-component-preview', $template->id),
            'simpleTemplateSaveUrl' => '',
            'simpleEditor' => [
                'enabled' => false,
                'full_builder_allowed' => true,
                'state' => [],
            ],
            'page' => [
                'id' => $template->id,
                'title' => $template->name,
                'slug' => $template->slug,
                'content_type' => $template->template_type,
            ],
        ];
    ?>

    <script>
        window.PageBuilderConfig = <?php echo json_encode($pageBuilderConfig, 15, 512) ?>;
    </script>
    <script src="<?php echo e(asset('vendor/front-builder/grapesjs/grapes.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/front-builder/page-builder/page-builder.js')); ?>?v=theme-builder-template-builder"></script>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalad9a3a6132492030e5bc73fea4481fef)): ?>
<?php $attributes = $__attributesOriginalad9a3a6132492030e5bc73fea4481fef; ?>
<?php unset($__attributesOriginalad9a3a6132492030e5bc73fea4481fef); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalad9a3a6132492030e5bc73fea4481fef)): ?>
<?php $component = $__componentOriginalad9a3a6132492030e5bc73fea4481fef; ?>
<?php unset($__componentOriginalad9a3a6132492030e5bc73fea4481fef); ?>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/admin/theme-builder/builder.blade.php ENDPATH**/ ?>