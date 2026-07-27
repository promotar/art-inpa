<x-page-builder-focus-layout>
    <x-slot name="styles">
        <link rel="stylesheet" href="{{ asset('vendor/front-builder/grapesjs/grapes.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/front-builder/page-builder/page-builder.css') }}?v=20260725-unified-page-builder">
    </x-slot>

    <form
        id="page-builder-form"
        method="POST"
        action="{{ route('admin.pages.update', $page->id) }}"
        data-builder-save-url="{{ route('admin.pages.builder-save', $page->id) }}"
        data-builder-autosave-url="{{ route('admin.pages.autosave', $page->id) }}"
        data-builder-revisions-url="{{ route('admin.pages.revisions.index', $page->id) }}"
        data-builder-media-url="{{ route('admin.media.index') }}"
        data-simple-template-save-url="{{ route('admin.pages.template-edit-save', $page->id) }}"
        class="page-builder-focus-layout"
    >
        @csrf
        @method('PATCH')
        <input type="hidden" name="page_builder_json" id="page_builder_json">
        <input type="hidden" name="html" id="page_html">
        <input type="hidden" name="css" id="page_css">

        <header class="page-builder-header">
            <div class="page-builder-header-title">
                <span>{{ ucfirst($page->content_type ?? 'page') }} Builder</span>
                <h1>{{ $page->title }}</h1>
            </div>

            <div class="page-builder-header-actions">
                <a href="{{ route('admin.pages.index') }}" class="page-builder-action page-builder-action--muted">Pages</a>
                <a href="{{ $previewUrl }}" target="_blank" class="page-builder-action page-builder-action--muted">Preview</a>
                <button type="button" class="page-builder-action page-builder-action--ghost" data-page-settings-toggle>Page Settings</button>
                <button type="button" class="page-builder-action page-builder-action--primary" data-builder-publish>Publish</button>
                <button type="submit" class="page-builder-action page-builder-action--primary">Save</button>
            </div>
        </header>

        <section class="page-builder-statusbar" aria-label="Page status">
            <div class="page-builder-statusbar-path">
                <span>Page Path:</span>
                <strong>/pages/{{ $page->slug }}</strong>
            </div>

            @if (session('status'))
                <div class="page-builder-alert page-builder-alert--success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="page-builder-alert page-builder-alert--danger">{{ $errors->first() }}</div>
            @endif

            <div class="page-builder-alert page-builder-alert--status" data-builder-save-status hidden></div>

            <div class="page-builder-statusbar-actions">
                <span>Public URL:</span>
                <a href="{{ $publicUrl }}" target="_blank" class="page-builder-public-url">{{ $publicUrl }}</a>
                <span class="page-builder-statusbar-state">
                    <span>Status:</span>
                    <strong>
                        <span class="page-builder-dot page-builder-dot--{{ old('status', $page->status) === 'published' ? 'published' : 'draft' }}"></span>
                        {{ ucfirst(old('status', $page->status)) }}
                    </strong>
                </span>
            </div>
        </section>

        <aside class="page-builder-settings-drawer" data-page-settings-drawer hidden aria-label="Page settings">
            <div class="page-builder-settings-drawer-header">
                <div>
                    <span>Page Settings</span>
                    <strong>{{ ucfirst(old('content_type', $page->content_type ?? 'page')) }} · {{ ucfirst(old('status', $page->status)) }}</strong>
                </div>
                <button type="button" class="page-builder-icon-button" data-page-settings-close aria-label="Close page settings">×</button>
            </div>

            <div class="page-builder-settings-drawer-body">
                <label class="page-setting-field">
                    <span>Title</span>
                    <input name="title" value="{{ old('title', $page->title) }}" required>
                </label>

                <label class="page-setting-field">
                    <span>Slug</span>
                    <input name="slug" value="{{ old('slug', $page->slug) }}">
                </label>

                <label class="page-setting-field">
                    <span>Type</span>
                    <select name="content_type">
                        @foreach ($contentTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('content_type', $page->content_type ?? 'page') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="page-setting-field">
                    <span>Status</span>
                    <select name="status">
                        <option value="draft" @selected(old('status', $page->status) === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $page->status) === 'published')>Published</option>
                    </select>
                </label>

                <label class="page-setting-field">
                    <span>SEO Title</span>
                    <input name="seo_title" value="{{ old('seo_title', $page->seo_title) }}">
                </label>

                <label class="page-setting-field">
                    <span>Block Key</span>
                    <input name="block_key" value="{{ old('block_key', $page->block_key ?? '') }}" placeholder="hero.primary">
                </label>

                <label class="page-setting-field">
                    <span>Sort Order</span>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order ?? 0) }}">
                </label>

                <label class="page-setting-field">
                    <span>Parent Page</span>
                    <select name="parent_id">
                        <option value="">No parent</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('parent_id', $page->parent_id ?? '') === (string) $parent->id)>
                                {{ $parent->menu_label ?: $parent->title }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="page-setting-field">
                    <span>Category</span>
                    <input name="category" value="{{ old('category', $page->category ?? '') }}" list="page-builder-categories">
                    <datalist id="page-builder-categories">
                        @foreach ($categories as $category)
                            <option value="{{ $category }}">
                        @endforeach
                    </datalist>
                </label>

                <label class="page-setting-field">
                    <span>Menu Label</span>
                    <input name="menu_label" value="{{ old('menu_label', $page->menu_label ?? '') }}">
                </label>

                <label class="page-setting-field">
                    <span>Navigation</span>
                    <span>
                        <input type="hidden" name="show_in_menu" value="0">
                        <input type="checkbox" name="show_in_menu" value="1" @checked((bool) old('show_in_menu', $page->show_in_menu ?? false))>
                        Show this page in the frontend menu
                    </span>
                </label>

                <label class="page-setting-field page-setting-field--wide">
                    <span>Meta Description</span>
                    <textarea name="meta_description" rows="4">{{ old('meta_description', $page->meta_description) }}</textarea>
                </label>

                <div class="page-setting-url page-setting-field--wide">
                    <span>Public URL</span>
                    <a href="{{ $publicUrl }}" target="_blank">{{ $publicUrl }}</a>
                </div>

                <div class="page-setting-field page-setting-field--wide page-builder-template-tools">
                    <span>Templates</span>
                    <div class="page-builder-template-actions">
                        <a href="{{ route('admin.pages.template.export', $page->id) }}" class="page-builder-action page-builder-action--muted">
                            Export Template
                        </a>
                        <label class="page-builder-template-file">
                            <span>JSON file</span>
                            <input type="file" name="template_file" accept=".json,application/json" form="page-template-import-form" required>
                        </label>
                        <button type="submit" class="page-builder-action page-builder-action--primary" form="page-template-import-form">
                            Upload Template
                        </button>
                    </div>
                    <small class="page-builder-template-note">Exports and imports the builder project, HTML, and CSS. Page title, slug, status, and SEO settings stay unchanged on upload.</small>
                </div>

                <div class="page-setting-field page-setting-field--wide page-builder-revisions">
                    <span>Revisions</span>
                    <div class="page-builder-revision-list" data-builder-revisions-list>
                        @forelse ($revisions as $revision)
                            <div class="page-builder-revision-item" data-revision-id="{{ $revision['id'] }}">
                                <div>
                                    <strong>{{ $revision['title'] }}</strong>
                                    <small>{{ $revision['created_at'] }} · {{ $revision['meta']['reason'] ?? 'snapshot' }}</small>
                                </div>
                                <button type="button" class="page-builder-action page-builder-action--compact page-builder-action--muted" data-revision-restore-url="{{ $revision['restore_url'] }}">
                                    Restore
                                </button>
                            </div>
                        @empty
                            <p class="page-builder-revision-empty">No revisions yet. The first manual save will create one.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </aside>

        @if ($simpleModeEnabled)
            <section class="page-builder-simple-editor" data-simple-template-editor>
                <aside class="page-builder-simple-panel page-builder-simple-sections">
                    <div class="page-builder-simple-panel-header">
                        <span>Page Sections</span>
                        <strong>{{ $simpleEditor['template_name'] }}</strong>
                    </div>
                    <div class="page-builder-simple-section-list" data-simple-section-list></div>
                </aside>

                <section class="page-builder-simple-panel page-builder-simple-fields">
                    <div class="page-builder-simple-panel-header">
                        <span>Edit Section</span>
                        <strong data-simple-active-section>Choose a section</strong>
                    </div>
                    <div class="page-builder-simple-fields-body" data-simple-fields></div>
                </section>

                <section class="page-builder-simple-panel page-builder-simple-preview">
                    <div class="page-builder-simple-panel-header">
                        <span>Preview Canvas</span>
                        <strong>Layout locked</strong>
                    </div>
                    <div class="page-builder-simple-preview-canvas" data-simple-preview>
                        {!! $simpleEditor['preview_html'] ?? '' !!}
                    </div>
                </section>
            </section>
        @endif

        @unless ($simpleModeEnabled)
        <nav class="page-builder-toolbar page-builder-toolbar--devices-only" aria-label="Builder device tools">
            <div class="page-builder-toolbar-group page-builder-toolbar-group--devices">
                <button type="button" data-builder-device="Desktop" class="is-active">Desktop</button>
                <button type="button" data-builder-device="Tablet">Tablet</button>
                <button type="button" data-builder-device="Mobile portrait">Mobile</button>
            </div>
        </nav>

        <section class="page-builder-workspace">
            <aside class="page-builder-inspector page-builder-left-panel" aria-label="Builder inspector">
                <div class="page-builder-inspector-header">
                    <span>Builder</span>
                    <strong data-builder-sidebar-title>Elements</strong>
                </div>
                <div class="page-builder-inspector-body" data-builder-inspector-host></div>
            </aside>

            <div class="page-builder-canvas">
                <div id="gjs"></div>
            </div>
        </section>
        @endunless
    </form>

    <form
        id="page-template-import-form"
        method="POST"
        action="{{ route('admin.pages.template.import', $page->id) }}"
        enctype="multipart/form-data"
        class="page-builder-template-import-form"
    >
        @csrf
    </form>

    @php
        $pageBuilderConfig = [
            'builderProject' => $builderProject,
            'initialHtml' => $editorCanvasHtml ?? ($page->html ?? $page->content ?? ''),
            'initialCss' => $page->css ?? '',
            'editorCanvasCss' => $editorCanvasCss ?? '',
            'editorCanvasStyleUrls' => $editorCanvasStyleUrls,
            'widgets' => $builderWidgets,
            'blocks' => $builderBlocks,
            'elementRegistry' => $builderElementRegistry,
            'dynamicSources' => $builderDynamicSources,
            'mediaUrl' => route('admin.media.index'),
            'builderSaveUrl' => route('admin.pages.builder-save', $page->id),
            'autosaveUrl' => route('admin.pages.autosave', $page->id),
            'revisionsUrl' => route('admin.pages.revisions.index', $page->id),
            'editorComponentPreviewUrl' => '/admin/pages/'.$page->id.'/editor-component-preview',
            'simpleTemplateSaveUrl' => route('admin.pages.template-edit-save', $page->id),
            'simpleEditor' => [
                'enabled' => $simpleModeEnabled,
                'full_builder_allowed' => $fullBuilderAllowed,
                'state' => $simpleEditor,
            ],
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'content_type' => $page->content_type ?? 'page',
            ],
        ];
    @endphp

    <script>
        window.PageBuilderConfig = @json($pageBuilderConfig);
    </script>
    <script src="{{ asset('vendor/front-builder/grapesjs/grapes.min.js') }}"></script>
    <script src="{{ asset('vendor/front-builder/page-builder/page-builder.js') }}?v=20260725-unified-page-builder"></script>
</x-page-builder-focus-layout>
