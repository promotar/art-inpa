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
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Theme Builder</h2>
                <p class="mt-1 text-sm text-gray-500">Manage dynamic layout templates with display conditions.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-create-template-trigger class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                    Create Template
                </button>
                <a href="<?php echo e(route('admin.pages.index')); ?>" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Pages
                </a>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <style>
        .theme-builder-shell {
            background: #ffffff;
            border: 1px solid #d8dfe8;
            border-radius: 12px;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .theme-builder-tabs {
            align-items: center;
            background: #f8fafc;
            border-bottom: 1px solid #d8dfe8;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 14px 18px 0;
        }

        .theme-builder-tab {
            border: 1px solid transparent;
            border-bottom: 0;
            border-radius: 8px 8px 0 0;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
            line-height: 1;
            min-height: 42px;
            padding: 13px 16px;
            white-space: nowrap;
        }

        .theme-builder-tab:hover {
            color: #0f172a;
        }

        .theme-builder-tab.is-active {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #0f172a;
            margin-bottom: -1px;
        }

        .theme-builder-panel {
            padding: 28px;
        }

        .theme-builder-form-card,
        .theme-builder-summary,
        .theme-builder-template,
        .theme-builder-empty {
            border: 1px solid #dbe3ee;
            border-radius: 10px;
        }

        .theme-builder-form-card {
            background: #f8fafc;
            padding: 22px;
        }

        .theme-builder-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .theme-builder-part {
            display: grid;
            gap: 24px;
            grid-template-columns: minmax(220px, 300px) minmax(0, 1fr);
        }

        .theme-builder-summary {
            background: #f8fafc;
            padding: 22px;
        }

        .theme-builder-list {
            display: grid;
            gap: 12px;
        }

        .theme-builder-template {
            background: #ffffff;
            display: block;
            padding: 16px;
        }

        .theme-builder-template:hover {
            border-color: #94a3b8;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .theme-builder-empty {
            background: #f8fafc;
            color: #64748b;
            padding: 26px;
            text-align: center;
        }

        .theme-builder-template-head {
            align-items: center;
            display: grid;
            gap: 16px;
            grid-template-columns: minmax(0, 1fr) auto;
        }

        .theme-builder-condition {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-top: 14px;
            padding: 14px;
        }

        .theme-builder-condition-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: 140px minmax(180px, 1fr) minmax(180px, 1fr) auto;
        }

        .theme-builder-field-label {
            color: #475569;
            display: block;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .theme-builder-input {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            color: #0f172a;
            font-size: 13px;
            min-height: 38px;
            padding: 8px 10px;
            width: 100%;
        }

        @media (max-width: 900px) {
            .theme-builder-panel {
                padding: 18px;
            }

            .theme-builder-grid,
            .theme-builder-part,
            .theme-builder-template-head,
            .theme-builder-condition-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <?php if(session('status')): ?>
                <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?php echo e(session('status')); ?></div>
            <?php endif; ?>

            <?php if(! $storageReady): ?>
                <div class="rounded-md border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-900">
                    Theme Builder template storage is not ready. Run the pending migration before adding templates.
                </div>
            <?php endif; ?>

            <section class="theme-builder-shell" data-theme-builder>
                <div class="border-b border-gray-200 px-6 py-5">
                    <h3 class="text-lg font-semibold text-gray-900">Theme Builder Templates</h3>
                    <p class="mt-1 text-sm text-gray-500">Dynamic templates such as headers, footers, single posts, archives, search results, and 404 layouts are stored separately from Pages.</p>
                </div>

                <div class="theme-builder-tabs" role="tablist" aria-label="Theme Builder sections">
                    <button type="button" id="theme-builder-tab-templates" class="theme-builder-tab is-active" data-theme-tab="templates" role="tab" aria-selected="true" aria-controls="theme-builder-panel-templates">
                        Templates
                    </button>
                    <?php $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <button
                            type="button"
                            id="theme-builder-tab-<?php echo e($section['key']); ?>"
                            class="theme-builder-tab"
                            data-theme-tab="<?php echo e($section['key']); ?>"
                            role="tab"
                            aria-selected="false"
                            aria-controls="theme-builder-panel-<?php echo e($section['key']); ?>"
                        >
                            <?php echo e($section['label']); ?>

                        </button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <div id="theme-builder-panel-templates" class="theme-builder-panel" data-theme-panel="templates" role="tabpanel" aria-labelledby="theme-builder-tab-templates">
                    <div class="theme-builder-form-card mb-8" data-create-template-form <?php if(! (isset($errors) && $errors->any())): ?> hidden <?php endif; ?>>
                        <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h4 class="text-xl font-semibold text-gray-950">Create Theme Builder Template</h4>
                                <p class="mt-1 text-sm text-gray-600">Create a blank template or upload a JSON/HTML template. This will not create a page record.</p>
                            </div>
                            <button type="button" data-create-template-close class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                        </div>

                        <form method="POST" action="<?php echo e(route('admin.theme-builder.templates.store')); ?>" enctype="multipart/form-data" class="space-y-5">
                            <?php echo csrf_field(); ?>
                            <div class="theme-builder-grid">
                                <label>
                                    <span class="theme-builder-field-label">Template Type</span>
                                    <select name="template_type" class="theme-builder-input" required>
                                        <?php $__currentLoopData = $templateTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </label>
                                <label>
                                    <span class="theme-builder-field-label">Status</span>
                                    <select name="status" class="theme-builder-input" required>
                                        <option value="draft">Draft</option>
                                        <option value="published">Published</option>
                                    </select>
                                </label>
                                <label>
                                    <span class="theme-builder-field-label">Name</span>
                                    <input name="name" class="theme-builder-input" placeholder="Example: Main Header">
                                </label>
                                <label>
                                    <span class="theme-builder-field-label">Template File</span>
                                    <input type="file" name="template_file" accept=".json,.html,.htm,.txt" class="theme-builder-input">
                                </label>
                            </div>
                            <label>
                                <span class="theme-builder-field-label">Description</span>
                                <input name="description" class="theme-builder-input" placeholder="Optional internal note">
                            </label>
                            <div class="flex flex-wrap items-center gap-2">
                                <button class="rounded-md bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-800">
                                    Create Template
                                </button>
                                <button type="button" data-create-template-close class="rounded-md border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>

                    <div>
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-950">All Stored Templates</h4>
                                <p class="mt-1 text-sm text-gray-500">General list for all Theme Builder templates.</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <button type="button" data-create-template-trigger class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                                    Create Template
                                </button>
                                <span class="text-sm text-gray-500"><?php echo e($templates->count()); ?> templates</span>
                            </div>
                        </div>
                        <div class="theme-builder-list">
                            <?php $__empty_1 = true; $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php echo $__env->make('admin.theme-builder.partials.template-card', ['template' => $template], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="theme-builder-empty">
                                    <div class="font-semibold text-gray-700">No Theme Builder templates yet.</div>
                                    <div class="mt-1 text-sm">Click Create Template to add the first one.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div id="theme-builder-panel-<?php echo e($section['key']); ?>" class="theme-builder-panel" data-theme-panel="<?php echo e($section['key']); ?>" role="tabpanel" aria-labelledby="theme-builder-tab-<?php echo e($section['key']); ?>" hidden>
                        <div class="theme-builder-part">
                            <aside class="theme-builder-summary">
                                <div class="text-xs font-semibold uppercase tracking-wide text-blue-700"><?php echo e(str_replace('_', '-', $section['key'])); ?></div>
                                <h4 class="mt-2 text-2xl font-semibold text-gray-950"><?php echo e($section['label']); ?></h4>
                                <p class="mt-3 text-sm leading-6 text-gray-600"><?php echo e($section['description']); ?></p>
                                <div class="mt-5">
                                    <span class="text-xs text-gray-500"><?php echo e($section['templates']->count()); ?> templates</span>
                                </div>
                                <button
                                    type="button"
                                    data-create-template-trigger
                                    data-template-type="<?php echo e($section['key']); ?>"
                                    class="mt-6 w-full rounded-md bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-gray-800"
                                >
                                    Create <?php echo e($section['label']); ?>

                                </button>
                            </aside>

                            <div class="theme-builder-list">
                                <?php $__empty_1 = true; $__currentLoopData = $section['templates']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php echo $__env->make('admin.theme-builder.partials.template-card', ['template' => $template], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <div class="theme-builder-empty">
                                        <div class="font-semibold text-gray-700">No templates created for this part yet.</div>
                                        <div class="mt-1 text-sm">Use the Templates tab to add one.</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </section>
        </div>
    </div>

    <script>
        (() => {
            const root = document.querySelector('[data-theme-builder]');

            if (! root) {
                return;
            }

            const tabs = Array.from(root.querySelectorAll('[data-theme-tab]'));
            const panels = Array.from(root.querySelectorAll('[data-theme-panel]'));
            const createForm = root.querySelector('[data-create-template-form]');
            const templateTypeInput = createForm ? createForm.querySelector('select[name="template_type"]') : null;
            const createTriggers = Array.from(document.querySelectorAll('[data-create-template-trigger]'));
            const createCloseButtons = Array.from(root.querySelectorAll('[data-create-template-close]'));

            const activate = (key, updateHash = true) => {
                tabs.forEach((tab) => {
                    const active = tab.dataset.themeTab === key;
                    tab.classList.toggle('is-active', active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                panels.forEach((panel) => {
                    panel.hidden = panel.dataset.themePanel !== key;
                });

                if (updateHash) {
                    history.replaceState(null, '', `#${key}`);
                }
            };

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => activate(tab.dataset.themeTab));
            });

            createTriggers.forEach((button) => {
                button.addEventListener('click', () => {
                    const templateType = button.dataset.templateType;

                    if (templateTypeInput && templateType) {
                        templateTypeInput.value = templateType;
                    }

                    activate('templates');

                    if (createForm) {
                        createForm.hidden = false;
                        createForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });

            createCloseButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    if (createForm) {
                        createForm.hidden = true;
                    }
                });
            });

            const initial = decodeURIComponent((window.location.hash || '').replace('#', ''));
            const initialTab = tabs.find((tab) => tab.dataset.themeTab === initial);

            if (initialTab) {
                activate(initial, false);
            }
        })();
    </script>
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
<?php /**PATH /var/www/html/resources/views/admin/theme-builder/index.blade.php ENDPATH**/ ?>