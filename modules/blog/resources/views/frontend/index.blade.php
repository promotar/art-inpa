<x-frontend-layout>
    <x-slot name="head">
        <title>الأخبار والمقالات | Art INPA</title>
        <meta name="description" content="آخر أخبار ومقالات Art INPA والتغطيات الفنية والثقافية.">
        <link rel="stylesheet" href="{{ route('blog.styles') }}">
    </x-slot>

    <section class="blog-archive blog-archive--classic" lang="ar" dir="rtl">
        <div class="blog-shell">
            <nav class="blog-archive-breadcrumb" aria-label="مسار التنقل">
                <a href="{{ route('front.home') }}">الرئيسية</a>
                <span aria-hidden="true">/</span>
                <span>الأخبار والمقالات</span>
            </nav>

            <header class="blog-archive-title">
                <h1>الأخبار والمقالات</h1>
            </header>

            @include('blog::frontend.partials.post-grid', ['posts' => $posts])
        </div>
    </section>
</x-frontend-layout>
