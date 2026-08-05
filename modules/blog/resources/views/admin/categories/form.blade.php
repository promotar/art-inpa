<x-app-layout>
    <div class="py-8 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold">{{ $category->exists ? 'Edit Category' : 'New Category' }}</h1>
        <form class="mt-6 space-y-4" method="POST" action="{{ $category->exists ? route('admin.plugins.blog.categories.update', $category) : route('admin.plugins.blog.categories.store') }}">
            @csrf
            @if ($category->exists)
                @method('PATCH')
            @endif
            <input class="w-full rounded border-gray-300" name="name" value="{{ old('name', $category->name) }}" placeholder="Name" required>
            <input class="w-full rounded border-gray-300" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="Slug">
            <select class="w-full rounded border-gray-300" name="parent_id">
                <option value="">No parent</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @selected((int) old('parent_id', $category->parent_id) === $parent->id)>{{ $parent->name }}</option>
                @endforeach
            </select>
            <input class="w-full rounded border-gray-300" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}" placeholder="Sort order">
            <textarea class="w-full rounded border-gray-300" name="description" rows="4" placeholder="Description">{{ old('description', $category->description) }}</textarea>
            <button class="px-4 py-2 bg-gray-900 text-white rounded" type="submit">Save</button>
        </form>
    </div>
</x-app-layout>
