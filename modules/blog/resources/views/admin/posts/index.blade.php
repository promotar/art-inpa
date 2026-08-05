<x-app-layout>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('platform/plugins/blog/css/blog.css') }}?v={{ is_file(public_path('platform/plugins/blog/css/blog.css')) ? filemtime(public_path('platform/plugins/blog/css/blog.css')) : time() }}">
    @endpush

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-950">Blog Posts</h1>
                    <p class="mt-1 text-sm text-gray-600">Create, edit, schedule, preview, and publish SEO-ready posts.</p>
                </div>
                <a class="rounded-sm bg-blue-600 px-4 py-2 text-sm font-semibold text-white" href="{{ route('admin.plugins.blog.posts.create', [], false) }}">Add New Post</a>
            </div>

            @if (session('status'))
                <div class="mb-4 border-l-4 border-green-600 bg-white p-3 text-sm text-gray-700">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-4 border-l-4 border-red-600 bg-white p-3 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="GET" class="mb-4 flex flex-wrap gap-2 bg-white p-3 shadow-sm">
                <input class="min-w-64 border border-gray-300 px-3 py-2 text-sm" name="search" value="{{ request('search') }}" placeholder="Search posts">
                <select class="border border-gray-300 px-3 py-2 text-sm" name="status">
                    <option value="">All statuses</option>
                    @foreach (['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled', 'private' => 'Private'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="border border-blue-700 px-3 py-2 text-sm font-semibold text-blue-700">Filter</button>
            </form>

            <div class="overflow-hidden bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Tags</th>
                            <th class="px-4 py-3">Author</th>
                            <th class="px-4 py-3">SEO</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($posts as $post)
                            <tr>
                                <td class="px-4 py-3">
                                    <a class="font-semibold text-blue-700" href="{{ route('admin.plugins.blog.posts.edit', $post, false) }}">{{ $post->title }}</a>
                                    <div class="mt-1 text-xs text-gray-500">/{{ $post->slug }}</div>
                                </td>
                                <td class="px-4 py-3">{{ $post->category?->name ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $post->tags->pluck('name')->implode(', ') ?: '-' }}</td>
                                <td class="px-4 py-3">{{ $post->author?->name ?: $post->creator?->name ?: '-' }}</td>
                                <td class="px-4 py-3">{{ (int) $post->seo_score }}/100</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-sm bg-gray-100 px-2 py-1 text-xs font-semibold">{{ ucfirst($post->status) }}</span>
                                    @if ($post->visibility !== 'public')
                                        <span class="rounded-sm bg-yellow-100 px-2 py-1 text-xs font-semibold">{{ ucfirst($post->visibility) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ optional($post->published_at ?: $post->updated_at)->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a class="text-blue-700" href="{{ route('admin.plugins.blog.posts.edit', $post, false) }}">Edit</a>
                                        <a class="text-blue-700" href="{{ route('admin.plugins.blog.posts.preview', $post, false) }}" target="_blank">Preview</a>
                                        <button
                                            type="button"
                                            class="text-red-700 hover:text-red-900"
                                            data-delete-post
                                            data-title="{{ $post->title }}"
                                            data-status="{{ ucfirst($post->status) }}"
                                            data-action="{{ route('admin.plugins.blog.posts.destroy', $post, false) }}"
                                        >Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">No posts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $posts->links() }}</div>
        </div>
    </div>

    <div id="delete-post-modal" class="blog-admin-modal" aria-hidden="true">
        <div class="blog-admin-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="delete-post-modal-title">
            <div class="blog-admin-modal__header">
                <h2 id="delete-post-modal-title">Move post to trash?</h2>
                <p>This keeps the post recoverable in the database but removes it from active Blog lists.</p>
            </div>
            <div class="blog-admin-modal__body">
                <dl class="blog-admin-modal__details">
                    <div>
                        <dt>Post</dt>
                        <dd id="delete-post-title"></dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd id="delete-post-status"></dd>
                    </div>
                </dl>
            </div>
            <form id="delete-post-form" method="POST" class="blog-admin-modal__actions">
                @csrf
                @method('DELETE')
                <button type="button" id="delete-post-cancel" class="blog-admin-modal__button blog-admin-modal__button--secondary">Cancel</button>
                <button type="submit" class="blog-admin-modal__button blog-admin-modal__button--danger">Move to Trash</button>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('delete-post-modal');
            const form = document.getElementById('delete-post-form');
            const title = document.getElementById('delete-post-title');
            const status = document.getElementById('delete-post-status');
            const cancel = document.getElementById('delete-post-cancel');

            function closeModal() {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                form.removeAttribute('action');
            }

            document.querySelectorAll('[data-delete-post]').forEach(button => {
                button.addEventListener('click', () => {
                    title.textContent = button.dataset.title || 'Untitled post';
                    status.textContent = button.dataset.status || '-';
                    form.setAttribute('action', button.dataset.action);
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    cancel.focus();
                });
            });

            cancel.addEventListener('click', closeModal);
            modal.addEventListener('click', event => {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
            });
        })();
    </script>
</x-app-layout>
