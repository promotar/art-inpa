<div class="blog-post-grid" lang="ar" dir="rtl">
    @forelse ($posts as $post)
        @php
            $imageUrl = $post->featuredImageUrl();
            $categoryName = $post->category?->name ?: 'Art INPA';
            $categoryUrl = $post->category ? route('blog.category', $post->category->slug) : route('blog.index');
            $authorName = $post->author?->name ?: 'Art INPA';
            $authorInitial = function_exists('mb_substr')
                ? mb_substr($authorName, 0, 1, 'UTF-8')
                : substr($authorName, 0, 1);
            $commentCount = $post->comments_count ?? 0;
            $commentLabel = $commentCount === 1 ? 'تعليق واحد' : $commentCount.' تعليقات';
        @endphp

        <article class="blog-post-card">
            <div class="blog-post-card__media-wrap">
                <a href="{{ route('blog.show', $post->slug) }}" class="blog-post-card__media" aria-label="قراءة: {{ $post->title }}">
                    @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $post->featured_image_alt ?: $post->title }}" loading="lazy" decoding="async">
                    @else
                        <span class="blog-post-card__placeholder"><bdi>{{ $categoryName }}</bdi></span>
                    @endif
                </a>

                <a class="blog-post-card__category" href="{{ $categoryUrl }}"><bdi>{{ $categoryName }}</bdi></a>
            </div>

            <div class="blog-post-card__body">
                <h2><a href="{{ route('blog.show', $post->slug) }}"><bdi>{{ $post->title }}</bdi></a></h2>

                <div class="blog-post-meta">
                    <span class="blog-post-author">
                        <span class="blog-post-author__avatar" aria-hidden="true">{{ $authorInitial }}</span>
                        <span><bdi>{{ $authorName }}</bdi></span>
                    </span>

                    @if ($post->published_at)
                        <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->locale('ar')->translatedFormat('j F Y') }}</time>
                    @endif

                    <span class="blog-post-comments" aria-label="{{ $commentLabel }}">
                        <span class="blog-post-comments__icon" aria-hidden="true"></span>
                        <span>{{ $commentCount }}</span>
                    </span>
                </div>
            </div>
        </article>
    @empty
        <p class="blog-empty">لا توجد مقالات منشورة حتى الآن.</p>
    @endforelse
</div>

@if (method_exists($posts, 'links') && $posts->hasPages())
    @php
        $currentPage = $posts->currentPage();
        $lastPage = $posts->lastPage();
        $windowStart = max(1, $currentPage - 1);
        $windowEnd = min($lastPage, $currentPage + 1);
    @endphp

    <nav class="blog-pagination" aria-label="التنقل بين صفحات المقالات">
        @if ($posts->onFirstPage())
            <span class="blog-pagination__item blog-pagination__item--disabled" aria-hidden="true">&rsaquo;</span>
        @else
            <a class="blog-pagination__item" href="{{ $posts->previousPageUrl() }}" rel="prev" aria-label="الصفحة السابقة">&rsaquo;</a>
        @endif

        @if ($windowStart > 1)
            <a class="blog-pagination__item" href="{{ $posts->url(1) }}">1</a>
            @if ($windowStart > 2)
                <span class="blog-pagination__item blog-pagination__item--dots">...</span>
            @endif
        @endif

        @for ($page = $windowStart; $page <= $windowEnd; $page++)
            @if ($page === $currentPage)
                <span class="blog-pagination__item blog-pagination__item--active" aria-current="page">{{ $page }}</span>
            @else
                <a class="blog-pagination__item" href="{{ $posts->url($page) }}">{{ $page }}</a>
            @endif
        @endfor

        @if ($windowEnd < $lastPage)
            @if ($windowEnd < $lastPage - 1)
                <span class="blog-pagination__item blog-pagination__item--dots">...</span>
            @endif
            <a class="blog-pagination__item" href="{{ $posts->url($lastPage) }}">{{ $lastPage }}</a>
        @endif

        @if ($posts->hasMorePages())
            <a class="blog-pagination__item" href="{{ $posts->nextPageUrl() }}" rel="next" aria-label="الصفحة التالية">&lsaquo;</a>
        @else
            <span class="blog-pagination__item blog-pagination__item--disabled" aria-hidden="true">&lsaquo;</span>
        @endif
    </nav>
@endif
