<?php

namespace Modules\Blog\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Post;
use Modules\Blog\Models\Tag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BlogController extends Controller
{
    public function styles(): BinaryFileResponse
    {
        return response()->file(
            dirname(__DIR__, 3).'/resources/css/blog.css',
            [
                'Content-Type' => 'text/css; charset=UTF-8',
                'Cache-Control' => 'public, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function index()
    {
        $featuredPost = Post::query()
            ->visibleToPublic()
            ->with(['category', 'tags', 'author', 'featuredImage'])
            ->latest('published_at')
            ->latest('id')
            ->first();

        $posts = Post::query()
            ->visibleToPublic()
            ->with(['category', 'tags', 'author', 'featuredImage'])
            ->latest('published_at')
            ->latest('id')
            ->paginate(12);

        $categories = Category::query()
            ->withCount(['posts as published_posts_count' => fn ($query) => $query->visibleToPublic()])
            ->orderByDesc('published_posts_count')
            ->orderBy('name')
            ->limit(10)
            ->get();

        return view('blog::frontend.index', compact('posts', 'featuredPost', 'categories'));
    }

    public function show(string $slug)
    {
        $post = Post::query()
            ->visibleToPublic()
            ->with(['category', 'categories', 'tags', 'author', 'featuredImage'])
            ->where('slug', $slug)
            ->firstOrFail();

        $categoryIds = collect([$post->category_id])
            ->merge($post->categories->pluck('id'))
            ->filter()
            ->unique()
            ->values();

        $relatedPosts = Post::query()
            ->visibleToPublic()
            ->with(['category', 'tags', 'author', 'featuredImage'])
            ->whereKeyNot($post->id)
            ->when(
                $categoryIds->isNotEmpty(),
                fn ($query) => $query->where(function ($query) use ($categoryIds): void {
                    $query
                        ->whereIn('category_id', $categoryIds)
                        ->orWhereHas('categories', fn ($categories) => $categories->whereIn('blog_categories.id', $categoryIds));
                })
            )
            ->latest('published_at')
            ->latest('id')
            ->limit(3)
            ->get();

        return view('blog::frontend.show', [
            'post' => $post,
            'preview' => false,
            'relatedPosts' => $relatedPosts,
        ]);
    }

    public function categories()
    {
        $categories = Category::query()
            ->withCount(['posts as published_posts_count' => fn ($query) => $query->visibleToPublic()])
            ->orderByDesc('published_posts_count')
            ->orderBy('name')
            ->get();

        return view('blog::frontend.categories', compact('categories'));
    }

    public function category(string $slug)
    {
        $category = Category::query()->where('slug', $slug)->firstOrFail();
        $posts = Post::query()
            ->where(function ($query) use ($category): void {
                $query
                    ->where('category_id', $category->id)
                    ->orWhereHas('categories', fn ($categories) => $categories->where('blog_categories.id', $category->id));
            })
            ->visibleToPublic()
            ->with(['category', 'tags', 'author', 'featuredImage'])
            ->latest('published_at')
            ->latest('id')
            ->paginate(12);

        return view('blog::frontend.category', compact('category', 'posts'));
    }

    public function tag(string $slug)
    {
        $tag = Tag::query()->where('slug', $slug)->firstOrFail();
        $posts = $tag->posts()
            ->visibleToPublic()
            ->with(['category', 'tags', 'author', 'featuredImage'])
            ->latest('published_at')
            ->latest('id')
            ->paginate(12);

        return view('blog::frontend.tag', compact('tag', 'posts'));
    }
}
