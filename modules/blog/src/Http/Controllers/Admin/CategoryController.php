<?php

namespace Modules\Blog\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Blog\Models\Category;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()->withCount('posts')->orderBy('sort_order')->orderBy('name')->paginate(20);

        return view('blog::admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('blog::admin.categories.form', [
            'category' => new Category,
            'parents' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = Category::query()->create($this->attributes($request));

        return $this->relativeRedirect($this->adminCategoryPath($category, 'edit'), 'Category created.');
    }

    public function quickStore(Request $request): JsonResponse
    {
        $category = Category::query()->create($this->attributes($request));

        return response()->json([
            'ok' => true,
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
        ]);
    }

    public function edit(Category $category): View
    {
        return view('blog::admin.categories.form', [
            'category' => $category,
            'parents' => Category::query()->whereKeyNot($category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->attributes($request, $category));

        return $this->relativeRedirect($this->adminCategoryPath($category, 'edit'), 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return $this->relativeRedirect('/admin/plugins/blog/categories', 'Category deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(Request $request, ?Category $category = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('blog_categories', 'slug')->ignore($category?->id)],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $validated['slug'] = $validated['slug'] ?: $this->uniqueSlug($validated['name'], $category?->id);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        return $validated;
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'category';
        $slug = $base;
        $index = 2;

        while (Category::query()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$index;
            $index++;
        }

        return $slug;
    }

    private function adminCategoryPath(Category $category, string $action): string
    {
        return match ($action) {
            'edit' => '/admin/plugins/blog/categories/'.$category->getKey().'/edit',
            default => '/admin/plugins/blog/categories',
        };
    }

    private function relativeRedirect(string $path, ?string $status = null): RedirectResponse
    {
        $response = redirect()->to($path);

        if ($status !== null) {
            $response->with('status', $status);
        }

        $response->headers->set('Location', $path);

        return $response;
    }
}
