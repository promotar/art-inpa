<x-frontend-layout>
    @php
        $preview = $preview ?? false;
        $relatedPosts = $relatedPosts ?? collect();
        $metaTitle = $post->seo_title ?: $post->meta_title ?: $post->title;
        $metaDescription = $post->seo_description ?: $post->meta_description ?: $post->excerpt;
        $robots = ($post->robots_index ? 'index' : 'noindex').', '.($post->robots_follow ? 'follow' : 'nofollow');
        $imageUrl = $post->featuredImageUrl();
        $canonical = $post->canonical_url ?: route('blog.show', $post->slug);
        $schema = [
            '@'.'context' => 'https://schema.org',
            '@'.'type' => $post->schema_type ?: $post->seo_schema_type ?: 'BlogPosting',
            'headline' => $post->title,
            'description' => $metaDescription,
            'image' => $imageUrl,
            'author' => $post->author?->name ? ['@'.'type' => 'Person', 'name' => $post->author->name] : null,
            'datePublished' => optional($post->published_at)->toIso8601String(),
            'dateModified' => optional($post->updated_at)->toIso8601String(),
            'mainEntityOfPage' => $canonical,
        ];
    @endphp

    <x-slot name="head">
        <title>{{ $metaTitle }}</title>
        <link rel="stylesheet" href="{{ route('blog.styles') }}">
        @if ($metaDescription)
            <meta name="description" content="{{ $metaDescription }}">
        @endif
        <meta name="robots" content="{{ $preview ? 'noindex, nofollow' : $robots }}">
        <link rel="canonical" href="{{ $canonical }}">
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $post->seo_social_title ?: $metaTitle }}">
        @if ($post->seo_social_description ?: $metaDescription)
            <meta property="og:description" content="{{ $post->seo_social_description ?: $metaDescription }}">
        @endif
        @if ($imageUrl)
            <meta property="og:image" content="{{ $imageUrl }}">
        @endif
        <script type="application/ld+json">
            {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </x-slot>

    <article class="blog-single" lang="ar" dir="rtl">
        <div class="blog-shell blog-single__shell">
            @if ($preview)
                <div class="blog-preview-alert" role="status">وضع المعاينة: قد لا يكون هذا المقال منشورًا للعامة.</div>
            @endif

            <header class="blog-single-header">
                <a class="blog-back-link" href="{{ route('blog.index') }}">الأخبار والمقالات</a>

                <div class="blog-post-meta">
                    @if ($post->category)
                        <a href="{{ route('blog.category', $post->category->slug) }}"><bdi>{{ $post->category->name }}</bdi></a>
                    @endif
                    @if ($post->published_at)
                        <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->locale('ar')->translatedFormat('j F Y') }}</time>
                    @endif
                    @if ($post->author)
                        <span><bdi>{{ $post->author->name }}</bdi></span>
                    @endif
                </div>

                <h1><bdi>{{ $post->title }}</bdi></h1>

                @if ($post->excerpt)
                    <p>{{ $post->excerpt }}</p>
                @endif
            </header>

            @if ($imageUrl)
                <figure class="blog-single-media">
                    <img src="{{ $imageUrl }}" alt="{{ $post->featured_image_alt ?: $post->featuredImage?->alt_text ?: $post->title }}" fetchpriority="high" decoding="async">
                    @if ($post->featuredImage?->caption)
                        <figcaption>{{ $post->featuredImage->caption }}</figcaption>
                    @endif
                </figure>
            @endif

            <div class="blog-single-layout">
                <div class="blog-article-body">
                    {!! $post->content !!}
                </div>

                <aside class="blog-single-sidebar" aria-label="معلومات المقال">
                    <div class="blog-sidebar-card">
                        <span>نُشر في</span>
                        <strong><bdi>{{ $post->category?->name ?: 'أخبار Art INPA' }}</bdi></strong>
                    </div>

                    @if ($post->tags->isNotEmpty())
                        <div class="blog-sidebar-card">
                            <span>الوسوم</span>
                            <div class="blog-tag-list">
                                @foreach ($post->tags as $tag)
                                    <a href="{{ route('blog.tag', $tag->slug) }}"><bdi>#{{ $tag->name }}</bdi></a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </article>

    @if ($relatedPosts->isNotEmpty())
        <section class="blog-related" lang="ar" dir="rtl">
            <div class="blog-shell">
                <header class="blog-section-heading">
                    <p class="blog-eyebrow">من التصنيف نفسه</p>
                    <h2>مقالات قد تعجبك</h2>
                </header>

                @include('blog::frontend.partials.post-grid', ['posts' => $relatedPosts])
            </div>
        </section>
    @endif
</x-frontend-layout>
