<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Theme Builder Template</h2>
                <p class="mt-1 text-sm text-gray-500">Update a dynamic template stored separately from Pages.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.theme-builder.templates.preview', $template->id) }}" target="_blank" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Preview</a>
                <a href="{{ route('admin.theme-builder.index') }}#templates" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Theme Builder</a>
            </div>
        </div>
    </x-slot>

    <style>
        .theme-template-edit {
            background: #ffffff;
            border: 1px solid #d8dfe8;
            border-radius: 12px;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .theme-template-edit__section {
            padding: 24px;
        }

        .theme-template-edit__grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .theme-template-edit__label {
            color: #475569;
            display: block;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .theme-template-edit__input {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            color: #0f172a;
            font-size: 14px;
            min-height: 40px;
            padding: 9px 11px;
            width: 100%;
        }

        .theme-template-edit__code {
            direction: ltr;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            min-height: 240px;
            resize: vertical;
            white-space: pre;
        }

        @media (max-width: 900px) {
            .theme-template-edit__grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.theme-builder.templates.update', $template->id) }}" enctype="multipart/form-data" class="theme-template-edit">
                @csrf
                @method('PATCH')

                <div class="border-b border-slate-200 px-6 py-5">
                    <h3 class="text-lg font-semibold text-slate-950">{{ $template->name }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Slug: {{ $template->slug }} · Source: {{ $template->source_type }}</p>
                </div>

                <div class="theme-template-edit__section space-y-5">
                    <div class="theme-template-edit__grid">
                        <label>
                            <span class="theme-template-edit__label">Template Type</span>
                            <select name="template_type" class="theme-template-edit__input" required>
                                @foreach ($templateTypes as $value => $label)
                                    <option value="{{ $value }}" @selected($template->template_type === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span class="theme-template-edit__label">Status</span>
                            <select name="status" class="theme-template-edit__input" required>
                                <option value="draft" @selected($template->status === 'draft')>Draft</option>
                                <option value="published" @selected($template->status === 'published')>Published</option>
                            </select>
                        </label>
                        <label>
                            <span class="theme-template-edit__label">Name</span>
                            <input name="name" value="{{ old('name', $template->name) }}" class="theme-template-edit__input" required>
                        </label>
                        <label>
                            <span class="theme-template-edit__label">Replace From File</span>
                            <input type="file" name="template_file" accept=".json,.html,.htm,.txt" class="theme-template-edit__input">
                        </label>
                    </div>

                    <label>
                        <span class="theme-template-edit__label">Description</span>
                        <input name="description" value="{{ old('description', $template->description) }}" class="theme-template-edit__input">
                    </label>
                </div>

                <div class="theme-template-edit__section space-y-5 border-t border-slate-200 bg-slate-50">
                    <label>
                        <span class="theme-template-edit__label">HTML</span>
                        <textarea name="html" class="theme-template-edit__input theme-template-edit__code">{{ old('html', $template->html) }}</textarea>
                    </label>

                    <label>
                        <span class="theme-template-edit__label">CSS</span>
                        <textarea name="css" class="theme-template-edit__input theme-template-edit__code">{{ old('css', $template->css) }}</textarea>
                    </label>

                    <label>
                        <span class="theme-template-edit__label">Page Builder JSON</span>
                        <textarea name="page_builder_json" class="theme-template-edit__input theme-template-edit__code">{{ old('page_builder_json', $template->page_builder_json) }}</textarea>
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-6 py-5">
                    <a href="{{ route('admin.theme-builder.index') }}#templates" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button class="rounded-md bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-gray-800">
                        Save Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
