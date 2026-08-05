<x-frontend-layout>
    <x-slot name="head">
        <title>التصنيفات | أخبار Art INPA</title>
        <meta name="description" content="تصفح أخبار ومقالات Art INPA حسب التصنيف والموضوع.">
        <link rel="stylesheet" href="{{ route('blog.styles') }}">
    </x-slot>

    <section class="blog-archive" lang="ar" dir="rtl">
        <div class="blog-shell">
            <header class="blog-archive-heading">
                <p class="blog-eyebrow">استكشف المحتوى</p>
                <h1>التصنيفات</h1>
                <p>تصفح تغطيات Art INPA حسب الموضوع والقسم التحريري والمجال الثقافي.</p>
            </header>

            <div class="blog-category-grid">
                @forelse ($categories as $category)
                    <a href="{{ route('blog.category', $category->slug) }}" class="blog-category-card">
                        <span>{{ $category->published_posts_count }} مقال</span>
                        <strong><bdi>{{ $category->name }}</bdi></strong>
                        @if ($category->description)
                            <p>{{ $category->description }}</p>
                        @else
                            <p>اطلع على أحدث المواد المنشورة ضمن هذا التصنيف.</p>
                        @endif
                    </a>
                @empty
                    <p class="blog-empty">لا توجد تصنيفات متاحة حتى الآن.</p>
                @endforelse
            </div>
        </div>
    </section>
</x-frontend-layout>
