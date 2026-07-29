<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Theme Builder</h2>
                <p class="mt-1 text-sm text-gray-500">Choose published Page Builder designs for the website layout.</p>
            </div>
            <a href="{{ route('admin.pages.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Page Builder
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('admin.theme-builder.update') }}" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                @csrf
                @method('PUT')

                <div class="border-b border-gray-200 px-6 py-5">
                    <h3 class="font-semibold text-gray-900">Active frontend composition</h3>
                    <p class="mt-1 text-sm text-gray-500">Theme Builder references Page Builder records directly. It does not copy or redesign them.</p>
                </div>

                <div class="grid gap-6 p-6 md:grid-cols-3">
                    @foreach ([
                        'header_page_id' => ['Header', $headers],
                        'body_page_id' => ['Body', $bodies],
                        'footer_page_id' => ['Footer', $footers],
                    ] as $field => [$label, $options])
                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-gray-800">{{ $label }}</span>
                            <select name="{{ $field }}" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">No {{ strtolower($label) }}</option>
                                @foreach ($options as $design)
                                    <option value="{{ $design->id }}" @selected((string) old($field, $selection->{$field} ?? '') === (string) $design->id)>
                                        {{ $design->title }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($label === 'Body')
                                <small class="mt-2 block text-xs leading-5 text-gray-500">A body can be a complete site body or a layout containing <code>@{{ page_content }}</code>.</small>
                            @else
                                <small class="mt-2 block text-xs leading-5 text-gray-500">Only published {{ strtolower($label) }} designs are available.</small>
                            @endif
                        </label>
                    @endforeach
                </div>

                <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4">
                    <p class="text-xs text-gray-500">Blocks remain reusable inside any Page Builder design.</p>
                    <button class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Save Theme Layout
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
