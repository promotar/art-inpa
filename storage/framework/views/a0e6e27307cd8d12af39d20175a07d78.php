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
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Page Builder</h2>
                <p class="mt-1 text-sm text-gray-500">Manage platform pages stored in the database.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php $__currentLoopData = ['page' => 'Create Page', 'header' => 'Create Header', 'footer' => 'Create Footer', 'block' => 'Create Block']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <form method="POST" action="<?php echo e(route('admin.pages.store')); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="content_type" value="<?php echo e($type); ?>">
                        <button class="rounded-md <?php echo e($type === 'page' ? 'bg-gray-900 text-white hover:bg-gray-800' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50'); ?> px-4 py-2 text-sm font-semibold">
                            <?php echo e($label); ?>

                        </button>
                    </form>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <?php if(session('status')): ?>
                <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?php echo e(session('status')); ?></div>
            <?php endif; ?>

            <section
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
                x-data="{
                    selected: [],
                    allIds: <?php echo \Illuminate\Support\Js::from($pages->pluck('id')->map(fn ($id) => (string) $id)->values())->toHtml() ?>,
                    get allSelected() {
                        return this.allIds.length > 0 && this.selected.length === this.allIds.length;
                    },
                    toggleAll() {
                        this.selected = this.allSelected ? [] : [...this.allIds];
                    },
                    clear() {
                        this.selected = [];
                    }
                }"
            >
                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Pages Table</h3>
                            <?php if(($search ?? '') !== ''): ?>
                                <p class="mt-1 text-sm text-gray-500">Showing results for "<?php echo e($search); ?>".</p>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <form
                                id="bulk-pages-delete-form"
                                method="POST"
                                action="<?php echo e(route('admin.pages.bulk-destroy')); ?>"
                                x-show="selected.length > 0"
                                x-cloak
                                onsubmit="return confirm('Delete selected pages?');"
                                class="flex flex-wrap items-center gap-2"
                            >
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button class="rounded-md border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                                    Delete Selected
                                </button>
                                <button type="button" @click="clear()" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </button>
                                <span class="text-sm text-gray-500" x-text="selected.length + ' selected'"></span>
                            </form>
                            <form method="GET" action="<?php echo e(route('admin.pages.index')); ?>" class="flex flex-wrap items-center justify-end gap-2">
                                <label for="page-search" class="sr-only">Search pages</label>
                                <input
                                    id="page-search"
                                    type="search"
                                    name="search"
                                    value="<?php echo e($search ?? ''); ?>"
                                    placeholder="Search pages..."
                                    class="w-64 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                                    Search
                                </button>
                                <?php if(($search ?? '') !== ''): ?>
                                    <a href="<?php echo e(route('admin.pages.index')); ?>" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                        Clear
                                    </a>
                                <?php endif; ?>
                            </form>
                            <span class="text-sm text-gray-500"><?php echo e($pages->count()); ?> pages</span>
                        </div>
                    </div>
                </div>

                <?php if($pages->isEmpty()): ?>
                    <div class="px-6 py-8 text-sm text-gray-600">
                        <?php if(($search ?? '') !== ''): ?>
                            No pages matched your search.
                        <?php else: ?>
                            No pages created yet.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="w-12 px-6 py-3">
                                        <input
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                            :checked="allSelected"
                                            @change="toggleAll()"
                                            aria-label="Select all pages"
                                        >
                                    </th>
                                    <th class="px-6 py-3">Title</th>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3">Slug</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Updated</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <?php $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $isPublicPage = ($page->content_type ?? 'page') === 'page';
                                        $isPublished = $page->status === 'published';
                                        $viewUrl = $isPublicPage
                                            ? route('pages.show', $page->slug, false)
                                            : route('admin.pages.preview', $page->id, false);
                                        $previewUrl = route('admin.pages.preview', $page->id, false);
                                    ?>
                                    <tr>
                                        <td class="px-6 py-4 align-top">
                                            <input
                                                form="bulk-pages-delete-form"
                                                type="checkbox"
                                                name="pages[]"
                                                value="<?php echo e($page->id); ?>"
                                                x-model="selected"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                aria-label="Select <?php echo e($page->title); ?>"
                                            >
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-gray-900"><?php echo e($page->title); ?></div>
                                            <?php if($page->seo_title): ?>
                                                <div class="mt-1 text-xs text-gray-500"><?php echo e($page->seo_title); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                                <?php echo e(ucfirst($page->content_type ?? 'page')); ?>

                                            </span>
                                            <?php if(($page->content_type ?? 'page') === 'block' && ($page->block_key ?? null)): ?>
                                                <div class="mt-1 font-mono text-xs text-gray-500"><?php echo e($page->block_key); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-xs text-gray-600">/pages/<?php echo e($page->slug); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                                'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                                'bg-green-100 text-green-700' => $page->status === 'published',
                                                'bg-yellow-100 text-yellow-800' => $page->status !== 'published',
                                            ]); ?>">
                                                <?php echo e(ucfirst($page->status)); ?>

                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500"><?php echo e($page->updated_at); ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <?php if(! $isPublicPage || $isPublished): ?>
                                                    <a href="<?php echo e($viewUrl); ?>" target="_blank" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                        View
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?php echo e($previewUrl); ?>" target="_blank" class="rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                                    Preview
                                                </a>
                                                <a href="<?php echo e(route('admin.pages.edit', $page->id)); ?>" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                    Edit
                                                </a>
                                                <form method="POST" action="<?php echo e(route('admin.pages.destroy', $page->id)); ?>" onsubmit="return confirm('Delete this page?');">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
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
<?php /**PATH /var/www/html/modules/PageBuilder/resources/views/pages/index.blade.php ENDPATH**/ ?>