<x-frontend-layout>
    <x-slot name="head">
        <link rel="stylesheet" href="{{ route('blog.styles') }}">
        <title>{{ $category->name }} | أخبار Art INPA</title>
        @if ($category->description)
            <meta name="description" content="{{ $category->description }}">
        @endif
    </x-slot>

    <section class="blog-archive" lang="ar" dir="rtl">
        <div class="blog-shell">
            <header class="blog-archive-heading">
                <a class="blog-back-link" href="{{ route('blog.categories') }}">كل التصنيفات</a>
                <p class="blog-eyebrow">التصنيف</p>
                <h1><bdi>{{ $category->name }}</bdi></h1>
                @if ($category->description)
                    <p>{{ $category->description }}</p>
                @else
                    <p>المقالات والتحديثات المنشورة ضمن هذا التصنيف.</p>
                @endif
            </header>

            @include('blog::frontend.partials.post-grid', ['posts' => $posts])
        </div>
    </section>
</x-frontend-layout>
