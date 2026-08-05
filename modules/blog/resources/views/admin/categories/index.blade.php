<x-app-layout>
    <div class="py-8 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Blog Categories</h1>
            <a class="text-sm font-semibold text-blue-700" href="{{ route('admin.plugins.blog.categories.create') }}">New Category</a>
        </div>
        <div class="mt-6 space-y-3">
            @foreach ($categories as $category)
                <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                    <span>{{ $category->name }}</span>
                    <div class="flex items-center gap-4">
                        <a class="text-blue-700" href="{{ route('blog.category', $category->slug) }}" target="_blank" rel="noopener">View</a>
                        <a class="text-blue-700" href="{{ route('admin.plugins.blog.categories.edit', $category) }}">Edit</a>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $categories->links() }}</div>
    </div>
</x-app-layout>
