<x-frontend-layout>
    <x-slot name="head">
        <link rel="stylesheet" href="{{ route('blog.styles') }}">
        <title>#{{ $tag->name }} | أخبار Art INPA</title>
        <meta name="description" content="مقالات Art INPA المرتبطة بوسم {{ $tag->name }}.">
    </x-slot>

    <section class="blog-archive" lang="ar" dir="rtl">
        <div class="blog-shell">
            <header class="blog-archive-heading">
                <a class="blog-back-link" href="{{ route('blog.index') }}">الأخبار والمقالات</a>
                <p class="blog-eyebrow">الوسم</p>
                <h1><bdi>#{{ $tag->name }}</bdi></h1>
                <p>القصص والملاحظات والتحديثات التحريرية المرتبطة بهذا الموضوع.</p>
            </header>

            @include('blog::frontend.partials.post-grid', ['posts' => $posts])
        </div>
    </section>
</x-frontend-layout>
