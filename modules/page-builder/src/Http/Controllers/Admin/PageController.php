<?php

namespace Modules\PageBuilder\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Platform\Core\Logs\OperationLogger;
use App\Platform\Core\PageBuilder\BuilderSanitizer;
use App\Platform\Core\PageBuilder\PageBuilderDynamicSourceRegistry;
use App\Platform\Core\PageBuilder\PageBuilderRenderService;
use App\Platform\Core\PageBuilder\PageBuilderWidgetRegistry;
use App\Platform\Core\PageBuilder\TemplateEditableRenderer;
use App\Platform\Core\Services\ActivePluginStylesheets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PageController extends Controller
{
    private const BUILDER_SYNC_META_KEY = '_z4rank_builder_sync';

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $pages = DB::table('platform_pages');

        if ($search !== '') {
            $pages->where(function ($query) use ($search): void {
                $query
                    ->where('title', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('content_type', 'like', '%'.$search.'%')
                    ->orWhere('status', 'like', '%'.$search.'%')
                    ->orWhere('seo_title', 'like', '%'.$search.'%');
            });
        }

        return view('page-builder::pages.index', [
            'pages' => $pages
                ->orderByRaw("CASE content_type WHEN 'page' THEN 1 WHEN 'header' THEN 2 WHEN 'footer' THEN 3 WHEN 'block' THEN 4 ELSE 9 END")
                ->latest('updated_at')
                ->latest('id')
                ->get(),
            'search' => $search,
        ]);
    }

    public function store(Request $request, OperationLogger $operations): RedirectResponse
    {
        $contentType = $this->contentType($request->input('content_type', 'page'));
        $title = 'Untitled '.ucfirst($contentType).' '.now()->format('Y-m-d H:i');
        $slug = $this->uniqueSlug($title);
        $now = now();

        $pageId = DB::table('platform_pages')->insertGetId([
            'title' => $title,
            'slug' => $slug,
            'content_type' => $contentType,
            'block_key' => $contentType === 'block' ? $slug : null,
            'parent_id' => null,
            'category' => null,
            'menu_label' => null,
            'show_in_menu' => false,
            'content' => null,
            'page_builder_json' => null,
            'html' => null,
            'css' => null,
            'status' => 'draft',
            'sort_order' => 0,
            'seo_title' => null,
            'meta_description' => null,
            'published_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $operation = $operations->start('admin.pages.create-draft', 'platform-page', (string) $pageId, [
            'slug' => $slug,
            'status' => 'draft',
            'content_type' => $contentType,
        ], $request->user()?->id);
        $operations->success($operation, 'Draft page created from admin pages.');

        return redirect()
            ->route('admin.pages.edit', $pageId)
            ->with('status', 'Draft page created. You can start designing it now.');
    }

    public function edit(int $page, PageBuilderWidgetRegistry $widgets, PageBuilderDynamicSourceRegistry $dynamicSources, TemplateEditableRenderer $templateEditableRenderer): View
    {
        $pageRecord = $this->findPage($page);
        $savedBlocks = $this->savedBlocks($pageRecord->id);
        $simpleEditor = $templateEditableRenderer->editorState($pageRecord);
        $fullBuilderAllowed = $this->canUseFullBuilder(request());

        return view('page-builder::pages.edit', [
            'page' => $pageRecord,
            'builderProject' => $this->builderProject($pageRecord),
            'editorCanvasHtml' => $this->editorCanvasHtml($pageRecord),
            'editorCanvasCss' => $this->editorCanvasCss($pageRecord),
            'builderWidgets' => array_values($widgets->widgetMap()),
            'builderBlocks' => $widgets->blocks($savedBlocks),
            'builderElementRegistry' => $widgets->elementRegistry(),
            'builderDynamicSources' => $dynamicSources->editorSources(request()->user(), $pageRecord),
            'revisions' => $this->revisionSummaries($pageRecord->id),
            'simpleEditor' => $simpleEditor,
            'simpleModeEnabled' => $simpleEditor['enabled'] && ! $fullBuilderAllowed,
            'fullBuilderAllowed' => $fullBuilderAllowed,
            'editorCanvasStyleUrls' => [
                '/admin/pages/'.$pageRecord->id.'/editor-preview.css?v='.md5((string) ($pageRecord->updated_at ?? $pageRecord->id)),
            ],
            'previewUrl' => route('admin.pages.preview', $pageRecord->id),
            'publicUrl' => $pageRecord->content_type === 'page'
                ? route('pages.show', $pageRecord->slug)
                : route('admin.pages.preview', $pageRecord->id),
            'contentTypes' => $this->contentTypes(),
            'parents' => $this->parentOptions($pageRecord->id),
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, int $page, OperationLogger $operations, BuilderSanitizer $sanitizer): RedirectResponse
    {
        $current = $this->findPage($page);
        $data = $this->validated($request, $sanitizer);
        $saved = $this->savePage($page, $current, $data, $request->user()?->id, 'manual-form-save');

        $operation = $operations->start('admin.pages.update-builder', 'platform-page', (string) $page, [
            'old_slug' => $current->slug,
            'new_slug' => $saved['slug'],
            'status' => $data['status'],
            'content_type' => $data['content_type'],
        ], $request->user()?->id);
        $operations->success($operation, 'Page builder content saved.', [
            'public_url' => $data['content_type'] === 'page' ? route('pages.show', $saved['slug']) : null,
            'preview_url' => route('admin.pages.preview', $page),
        ]);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('status', 'Page saved successfully.');
    }

    public function builderSave(Request $request, int $page, OperationLogger $operations, BuilderSanitizer $sanitizer): JsonResponse
    {
        $current = $this->findPage($page);
        $data = $this->validated($request, $sanitizer);
        $saved = $this->savePage($page, $current, $data, $request->user()?->id, 'manual-ajax-save');

        $operation = $operations->start('admin.pages.builder-save', 'platform-page', (string) $page, [
            'old_slug' => $current->slug,
            'new_slug' => $saved['slug'],
            'status' => $data['status'],
            'content_type' => $data['content_type'],
        ], $request->user()?->id);
        $operations->success($operation, 'Page builder content saved through AJAX.', [
            'public_url' => $saved['public_url'],
            'preview_url' => route('admin.pages.preview', $page),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Page saved successfully.',
            'page' => [
                'id' => $page,
                'slug' => $saved['slug'],
                'status' => $data['status'],
                'public_url' => $saved['public_url'],
                'updated_at' => $saved['updated_at'],
            ],
            'revisions' => $this->revisionSummaries($page),
        ]);
    }

    public function autosave(Request $request, int $page, BuilderSanitizer $sanitizer): JsonResponse
    {
        $current = $this->findPage($page);
        $data = $this->validated($request, $sanitizer);
        $data['status'] = $current->status === 'published' ? 'published' : 'draft';

        $saved = $this->savePage($page, $current, $data, $request->user()?->id, 'autosave', createRevision: false, preservePublishedAt: true);

        return response()->json([
            'ok' => true,
            'message' => 'Autosaved.',
            'page' => [
                'id' => $page,
                'slug' => $saved['slug'],
                'status' => $data['status'],
                'updated_at' => $saved['updated_at'],
            ],
        ]);
    }

    public function templateEditSave(Request $request, int $page, OperationLogger $operations, TemplateEditableRenderer $templateEditableRenderer, BuilderSanitizer $sanitizer): JsonResponse
    {
        $current = $this->findPage($page);
        $project = $this->builderProject($current) ?? [];
        $baseHtml = (string) ($current->html ?: $current->content ?: '');

        try {
            $payload = [
                'editable_data' => $this->jsonInput($request, 'editable_data'),
                'section_visibility' => $this->jsonInput($request, 'section_visibility'),
                'section_order' => $this->jsonInput($request, 'section_order'),
            ];

            $project = $templateEditableRenderer->mergeEditablePayload($project, $payload, $baseHtml);
            $renderedHtml = $templateEditableRenderer->render($baseHtml, $project);
            $sanitized = $sanitizer->sanitize($renderedHtml, (string) ($current->css ?? ''), $this->allowsUnsafeBuilderMarkup($request));
            $encodedProject = json_encode($project, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (JsonException $exception) {
            return response()->json([
                'ok' => false,
                'message' => 'Editable template data could not be saved.',
            ], 422);
        }

        $data = [
            'title' => (string) $current->title,
            'slug' => (string) $current->slug,
            'content_type' => (string) ($current->content_type ?? 'page'),
            'block_key' => $current->block_key ?? null,
            'status' => (string) ($current->status ?? 'draft'),
            'sort_order' => (int) ($current->sort_order ?? 0),
            'seo_title' => $current->seo_title ?? null,
            'meta_description' => $current->meta_description ?? null,
            'page_builder_json' => $encodedProject,
            'html' => $sanitized['html'],
            'css' => $sanitized['css'],
        ];

        $saved = $this->savePage($page, $current, $data, $request->user()?->id, 'simple-template-edit-save');
        $operation = $operations->start('admin.pages.simple-template-save', 'platform-page', (string) $page, [
            'slug' => $saved['slug'],
            'template_key' => $project['template_key'] ?? null,
        ], $request->user()?->id);
        $operations->success($operation, 'Simple template editable data saved.', [
            'preview_url' => route('admin.pages.preview', $page),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Template content saved.',
            'page' => [
                'id' => $page,
                'slug' => $saved['slug'],
                'status' => $data['status'],
                'updated_at' => $saved['updated_at'],
            ],
            'simple_editor' => $templateEditableRenderer->editorState((object) array_merge((array) $current, [
                'page_builder_json' => $encodedProject,
                'html' => $sanitized['html'],
                'content' => $sanitized['html'],
            ])),
            'revisions' => $this->revisionSummaries($page),
        ]);
    }

    public function revisions(int $page): JsonResponse
    {
        $this->findPage($page);

        return response()->json([
            'ok' => true,
            'revisions' => $this->revisionSummaries($page),
        ]);
    }

    public function restoreRevision(Request $request, int $page, int $revision, OperationLogger $operations): JsonResponse
    {
        $current = $this->findPage($page);
        $revisionRecord = DB::table('platform_page_revisions')
            ->where('id', $revision)
            ->where('page_id', $page)
            ->first();

        abort_unless($revisionRecord, 404);

        $meta = is_string($revisionRecord->meta ?? null) ? json_decode($revisionRecord->meta, true) : [];
        $meta = is_array($meta) ? $meta : [];

        $syncedProjectJson = $this->syncedBuilderProjectJson(
            is_string($revisionRecord->page_builder_json ?? null) ? $revisionRecord->page_builder_json : '',
            (string) ($revisionRecord->html ?? ''),
            (string) ($revisionRecord->css ?? ''),
        );

        DB::transaction(function () use ($page, $current, $revisionRecord, $meta, $request, $syncedProjectJson): void {
            $this->createRevisionSnapshot($current, $request->user()?->id, 'before-revision-restore');

            DB::table('platform_pages')->where('id', $page)->update([
                'title' => $revisionRecord->title,
                'content' => $revisionRecord->html,
                'html' => $revisionRecord->html,
                'css' => $revisionRecord->css,
                'page_builder_json' => $syncedProjectJson,
                'seo_title' => $meta['seo_title'] ?? $current->seo_title,
                'meta_description' => $meta['meta_description'] ?? $current->meta_description,
                'parent_id' => $meta['parent_id'] ?? $current->parent_id,
                'category' => $meta['category'] ?? $current->category,
                'menu_label' => $meta['menu_label'] ?? $current->menu_label,
                'show_in_menu' => $meta['show_in_menu'] ?? $current->show_in_menu,
                'updated_at' => now(),
            ]);
        });

        $operation = $operations->start('admin.pages.restore-revision', 'platform-page', (string) $page, [
            'revision_id' => $revision,
        ], $request->user()?->id);
        $operations->success($operation, 'Page builder revision restored.');

        return response()->json([
            'ok' => true,
            'message' => 'Revision restored.',
            'revisions' => $this->revisionSummaries($page),
        ]);
    }

    public function exportTemplate(int $page): StreamedResponse
    {
        $pageRecord = $this->findPage($page);
        $project = $this->builderProject($pageRecord);
        $payload = [
            'schema_version' => 'page-builder-template/v1',
            'exported_at' => now()->toIso8601String(),
            'source' => [
                'id' => (int) $pageRecord->id,
                'title' => (string) $pageRecord->title,
                'slug' => (string) $pageRecord->slug,
                'content_type' => (string) ($pageRecord->content_type ?? 'page'),
            ],
            'template' => [
                'page_builder_json' => $project,
                'html' => (string) ($pageRecord->html ?? $pageRecord->content ?? ''),
                'css' => (string) ($pageRecord->css ?? ''),
                'editable_schema' => is_array($project) ? ($project['editable_schema'] ?? null) : null,
            ],
        ];
        $filename = Str::slug((string) $pageRecord->title ?: 'page').'-template-'.now()->format('Ymd-His').'.json';

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }, $filename, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public function importTemplate(Request $request, int $page, OperationLogger $operations, BuilderSanitizer $sanitizer): RedirectResponse
    {
        $current = $this->findPage($page);
        $validated = $request->validate([
            'template_file' => ['required', 'file', 'max:5120'],
        ]);
        $uploaded = $validated['template_file'];
        $contents = file_get_contents($uploaded->getRealPath());

        if (! is_string($contents) || trim($contents) === '') {
            return back()->withErrors(['template_file' => 'Template file is empty.']);
        }

        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($payload)) {
                throw new InvalidArgumentException('Template JSON must contain an object.');
            }

            [$project, $html, $css] = $this->templateContentFromPayload($payload);
            $sanitized = $sanitizer->sanitize($html, $css, $this->allowsUnsafeBuilderMarkup($request));
            $data = [
                'title' => (string) $current->title,
                'slug' => (string) $current->slug,
                'content_type' => (string) ($current->content_type ?? 'page'),
                'block_key' => $current->block_key ?? null,
                'status' => (string) $current->status,
                'sort_order' => (int) ($current->sort_order ?? 0),
                'seo_title' => $current->seo_title ?? null,
                'meta_description' => $current->meta_description ?? null,
                'page_builder_json' => $project !== null
                    ? json_encode($project, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
                    : '',
                'html' => $sanitized['html'],
                'css' => $sanitized['css'],
            ];
        } catch (JsonException $exception) {
            return back()->withErrors(['template_file' => 'Template file is not valid JSON.']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['template_file' => $exception->getMessage()]);
        }

        $saved = $this->savePage($page, $current, $data, $request->user()?->id, 'template-import');
        $operation = $operations->start('admin.pages.import-template', 'platform-page', (string) $page, [
            'slug' => $saved['slug'],
            'has_project' => $data['page_builder_json'] !== '',
            'has_html' => $data['html'] !== '',
            'has_css' => $data['css'] !== '',
        ], $request->user()?->id);
        $operations->success($operation, 'Page builder template imported.');

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('status', 'Template imported successfully.');
    }

    public function preview(int $page): View
    {
        return view('page-builder::public.show', [
            'page' => $this->findPage($page),
            'isPreview' => true,
        ]);
    }

    public function editorPreviewCss(int $page): Response
    {
        $pageRecord = $this->findPage($page);

        return response($this->editorCanvasCss($pageRecord), 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function editorComponentPreview(Request $request, int $page, PageBuilderRenderService $renderer, BuilderSanitizer $sanitizer): JsonResponse
    {
        $pageRecord = $this->findPage($page);
        $data = $request->validate([
            'html' => ['required', 'string'],
        ]);

        $rendered = $renderer->renderHtml($data['html'], $pageRecord);
        $safe = $sanitizer->sanitize($rendered, '', false);

        return response()->json([
            'ok' => true,
            'html' => $safe['html'],
            'inner_html' => $this->firstElementInnerHtml($safe['html']),
        ]);
    }

    public function destroy(Request $request, int $page, OperationLogger $operations): RedirectResponse
    {
        $current = $this->findPage($page);

        DB::table('platform_pages')->where('id', $page)->delete();

        $operation = $operations->start('admin.pages.delete', 'platform-page', (string) $page, [
            'slug' => $current->slug,
            'title' => $current->title,
        ], $request->user()?->id);
        $operations->success($operation, 'Page deleted from admin pages.');

        return back()->with('status', 'Page removed successfully.');
    }

    public function bulkDestroy(Request $request, OperationLogger $operations): RedirectResponse
    {
        $data = $request->validate([
            'pages' => ['required', 'array', 'min:1'],
            'pages.*' => ['integer', 'distinct', 'exists:platform_pages,id'],
        ]);

        $ids = collect($data['pages'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $pages = DB::table('platform_pages')
            ->whereIn('id', $ids)
            ->get(['id', 'title', 'slug', 'content_type', 'status']);

        DB::table('platform_pages')->whereIn('id', $ids)->delete();

        $operation = $operations->start('admin.pages.bulk-delete', 'platform-page', 'bulk', [
            'count' => $pages->count(),
            'ids' => $pages->pluck('id')->values()->all(),
            'slugs' => $pages->pluck('slug')->values()->all(),
        ], $request->user()?->id);
        $operations->success($operation, 'Pages bulk deleted from admin pages.');

        return back()->with('status', $pages->count().' pages removed successfully.');
    }

    /**
     * @return array{title:string,slug:?string,content_type:string,block_key:?string,status:string,sort_order:int,seo_title:?string,meta_description:?string,page_builder_json:string,html:string,css:string}
     */
    private function validated(Request $request, BuilderSanitizer $sanitizer): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content_type' => ['required', 'in:page,header,footer,block'],
            'block_key' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_.:-]+$/'],
            'status' => ['required', 'in:draft,published'],
            'sort_order' => ['nullable', 'integer', 'min:-10000', 'max:10000'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('platform_pages', 'id')->where('content_type', 'page'),
                Rule::notIn(array_filter([(int) $request->route('page')])),
            ],
            'category' => ['nullable', 'string', 'max:120'],
            'menu_label' => ['nullable', 'string', 'max:255'],
            'show_in_menu' => ['nullable', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'page_builder_json' => ['nullable', 'string'],
            'html' => ['nullable', 'string'],
            'css' => ['nullable', 'string'],
        ]) + [
            'page_builder_json' => '',
            'html' => '',
            'css' => '',
        ];

        $data['content_type'] = $this->contentType($data['content_type']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['parent_id'] = $data['content_type'] === 'page' ? ($data['parent_id'] ?? null) : null;
        $data['show_in_menu'] = $data['content_type'] === 'page' && $request->boolean('show_in_menu');
        $sanitized = $sanitizer->sanitize(
            $data['html'],
            $data['css'],
            $this->allowsUnsafeBuilderMarkup($request),
        );
        $data['html'] = $sanitized['html'];
        $data['css'] = $sanitized['css'];

        return $data;
    }

    /**
     * @param  array{title:string,slug:?string,content_type:string,block_key:?string,status:string,sort_order:int,seo_title:?string,meta_description:?string,page_builder_json:string,html:string,css:string}  $data
     * @return array{slug:string,updated_at:string,public_url:?string}
     */
    private function savePage(
        int $page,
        object $current,
        array $data,
        ?int $userId,
        string $reason,
        bool $createRevision = true,
        bool $preservePublishedAt = false,
    ): array {
        $slug = $this->uniqueSlug($data['slug'] ?: $data['title'], $page);
        $now = now();
        $publishedAt = $preservePublishedAt
            ? $current->published_at
            : ($data['status'] === 'published' ? ($current->published_at ?? $now) : null);

        $syncedProjectJson = $this->syncedBuilderProjectJson($data['page_builder_json'], $data['html'], $data['css']);

        DB::transaction(function () use ($page, $current, $data, $slug, $now, $publishedAt, $userId, $reason, $createRevision, $syncedProjectJson): void {
            if ($createRevision) {
                $this->createRevisionSnapshot($current, $userId, $reason);
            }

            DB::table('platform_pages')->where('id', $page)->update([
                'title' => $data['title'],
                'slug' => $slug,
                'content_type' => $data['content_type'],
                'block_key' => $data['content_type'] === 'block' ? ($data['block_key'] ?: $slug) : null,
                'parent_id' => $data['parent_id'],
                'category' => $data['category'] ?? null,
                'menu_label' => $data['menu_label'] ?? null,
                'show_in_menu' => $data['show_in_menu'],
                'content' => $data['html'] !== '' ? $data['html'] : null,
                'page_builder_json' => $syncedProjectJson,
                'html' => $data['html'] !== '' ? $data['html'] : null,
                'css' => $data['css'] !== '' ? $data['css'] : null,
                'status' => $data['status'],
                'sort_order' => $data['sort_order'],
                'seo_title' => $data['seo_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'published_at' => $publishedAt,
                'updated_at' => $now,
            ]);
        });

        return [
            'slug' => $slug,
            'updated_at' => $now->toDateTimeString(),
            'public_url' => $data['content_type'] === 'page' ? route('pages.show', $slug) : null,
        ];
    }

    private function createRevisionSnapshot(object $page, ?int $userId, string $reason): void
    {
        if (! Schema::hasTable('platform_page_revisions')) {
            return;
        }

        DB::table('platform_page_revisions')->insert([
            'page_id' => $page->id,
            'title' => $page->title,
            'html' => $page->html,
            'css' => $page->css,
            'page_builder_json' => $page->page_builder_json,
            'meta' => json_encode([
                'reason' => $reason,
                'slug' => $page->slug,
                'content_type' => $page->content_type ?? 'page',
                'block_key' => $page->block_key ?? null,
                'parent_id' => $page->parent_id ?? null,
                'category' => $page->category ?? null,
                'menu_label' => $page->menu_label ?? null,
                'show_in_menu' => (bool) ($page->show_in_menu ?? false),
                'status' => $page->status,
                'sort_order' => $page->sort_order ?? 0,
                'seo_title' => $page->seo_title ?? null,
                'meta_description' => $page->meta_description ?? null,
                'published_at' => $page->published_at ?? null,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<int, array{id:int,title:string,created_at:string,created_by:?int,restore_url:string,meta:array<string, mixed>}>
     */
    private function revisionSummaries(int $pageId): array
    {
        if (! Schema::hasTable('platform_page_revisions')) {
            return [];
        }

        return DB::table('platform_page_revisions')
            ->where('page_id', $pageId)
            ->latest('created_at')
            ->latest('id')
            ->limit(10)
            ->get(['id', 'title', 'created_at', 'created_by', 'meta'])
            ->map(fn (object $revision): array => [
                'id' => (int) $revision->id,
                'title' => (string) $revision->title,
                'created_at' => (string) $revision->created_at,
                'created_by' => $revision->created_by ? (int) $revision->created_by : null,
                'restore_url' => route('admin.pages.revisions.restore', [$pageId, $revision->id]),
                'meta' => is_string($revision->meta) && json_decode($revision->meta, true)
                    ? json_decode($revision->meta, true)
                    : [],
            ])
            ->all();
    }

    private function allowsUnsafeBuilderMarkup(Request $request): bool
    {
        $user = $request->user();

        return $user && method_exists($user, 'hasRole') && $user->hasRole('super-admin');
    }

    private function canUseFullBuilder(Request $request): bool
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'hasRole')) {
            return false;
        }

        foreach (['super-admin', 'admin'] as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    private function editorCanvasCss(object $page): string
    {
        $html = (string) ($page->html ?: $page->content ?: '');
        $chunks = [(string) ($page->css ?? '')];
        $classTokens = $this->htmlClassTokens($html);

        if ($classTokens !== []) {
            foreach (app(ActivePluginStylesheets::class)->files() as $cssFile) {
                $content = file_get_contents($cssFile);

                if (! is_string($content) || trim($content) === '') {
                    continue;
                }

                foreach ($classTokens as $classToken) {
                    if (str_contains($content, '.'.$classToken)) {
                        $chunks[] = "\n/* Editor preview asset: ".basename($cssFile)." */\n".$content;
                        break;
                    }
                }
            }
        }

        return trim(implode("\n\n", array_filter($chunks, fn (string $chunk): bool => trim($chunk) !== '')));
    }

    private function editorCanvasHtml(object $page): string
    {
        $html = (string) ($page->html ?: $page->content ?: '');

        if ($html === '' || ! str_contains($html, 'data-art-news-element')) {
            return $html;
        }

        try {
            $renderer = app(PageBuilderRenderService::class);
        } catch (\Throwable) {
            return $html;
        }

        return preg_replace_callback(
            '~(?P<open><(?P<tag>section|div|article)(?P<attrs>[^>]*)\sdata-art-news-element=(["\'])(?P<element>.*?)\4(?P<attrs2>[^>]*)>)(?P<body>.*?)(?P<close></\2>)~is',
            function (array $matches) use ($renderer, $page): string {
                $source = (string) ($matches[0] ?? '');
                $attrs = (string) (($matches['attrs'] ?? '').' '.($matches['attrs2'] ?? ''));

                if (str_contains($attrs, 'data-pb-source-type="static"') || str_contains($attrs, "data-pb-source-type='static'")) {
                    return $source;
                }

                try {
                    $rendered = $renderer->renderHtml($source, $page);
                    $inner = $this->firstElementInnerHtml($rendered);
                } catch (\Throwable) {
                    return $source;
                }

                if (trim($inner) === '') {
                    return $source;
                }

                return (string) ($matches['open'] ?? '').$inner.(string) ($matches['close'] ?? '');
            },
            $html,
        ) ?? $html;
    }

    /**
     * @return array<int, string>
     */
    private function htmlClassTokens(string $html): array
    {
        preg_match_all('/class\s*=\s*(["\'])(.*?)\1/is', $html, $matches);
        $tokens = [];

        foreach ($matches[2] ?? [] as $classList) {
            foreach (preg_split('/\s+/', trim((string) $classList)) ?: [] as $token) {
                $token = trim($token);

                if ($token !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $token)) {
                    $tokens[$token] = $token;
                }
            }
        }

        return array_values($tokens);
    }

    private function firstElementInnerHtml(string $html): string
    {
        $html = trim($html);

        if (preg_match('/^<([a-z][a-z0-9:-]*)(?:\s[^>]*)?>(.*)<\/\1>$/is', $html, $matches)) {
            return (string) ($matches[2] ?? '');
        }

        return $html;
    }

    /**
     * @return array<string|int, mixed>
     */
    private function jsonInput(Request $request, string $key): array
    {
        $value = $request->input($key, []);

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    private function findPage(int $page): object
    {
        $record = DB::table('platform_pages')->where('id', $page)->first();
        abort_unless($record, 404);

        return $record;
    }

    private function builderProject(object $page): ?array
    {
        if (! is_string($page->page_builder_json ?? null) || $page->page_builder_json === '') {
            return null;
        }

        $project = json_decode($page->page_builder_json, true);

        $project = $this->normalizeBuilderProject($project);

        if ($project === null || ! $this->builderProjectMatchesSavedContent($project, $page)) {
            return null;
        }

        return $project;
    }

    private function syncedBuilderProjectJson(string $projectJson, string $html, string $css): ?string
    {
        $projectJson = trim($projectJson);

        if ($projectJson === '') {
            return null;
        }

        try {
            $project = json_decode($projectJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $projectJson;
        }

        if (! is_array($project)) {
            return $projectJson;
        }

        $project[self::BUILDER_SYNC_META_KEY] = [
            'content_hash' => $this->builderContentHash($html, $css),
            'synced_at' => now()->toIso8601String(),
        ];

        try {
            return json_encode($project, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $projectJson;
        }
    }

    /**
     * @param  array<string, mixed>  $project
     */
    private function builderProjectMatchesSavedContent(array $project, object $page): bool
    {
        $html = (string) ($page->html ?? $page->content ?? '');
        $css = (string) ($page->css ?? '');

        if (trim($html) === '' && trim($css) === '') {
            return true;
        }

        $meta = $project[self::BUILDER_SYNC_META_KEY] ?? null;

        if (! is_array($meta) || ! is_string($meta['content_hash'] ?? null)) {
            return false;
        }

        return hash_equals($meta['content_hash'], $this->builderContentHash($html, $css));
    }

    private function builderContentHash(string $html, string $css): string
    {
        return hash('sha256', json_encode([
            'html' => $html,
            'css' => $css,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeBuilderProject(mixed $project): ?array
    {
        if (is_string($project)) {
            $project = trim($project);

            if ($project === '') {
                return null;
            }

            $project = json_decode($project, true);
        }

        if (! is_array($project)) {
            return null;
        }

        if (isset($project['template']) && is_array($project['template'])) {
            foreach (['page_builder_json', 'project', 'builderProject'] as $key) {
                if (array_key_exists($key, $project['template'])) {
                    $normalized = $this->normalizeBuilderProject($project['template'][$key]);

                    if ($normalized !== null) {
                        return $normalized;
                    }
                }
            }

            return null;
        }

        foreach (['page_builder_json', 'project', 'builderProject'] as $key) {
            if (array_key_exists($key, $project)) {
                $normalized = $this->normalizeBuilderProject($project[$key]);

                if ($normalized !== null) {
                    return $normalized;
                }
            }
        }

        $looksLikeGrapesProject = isset($project['pages'])
            || isset($project['components'])
            || isset($project['styles'])
            || isset($project['assets']);

        return $looksLikeGrapesProject ? $project : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0:?array<string, mixed>,1:string,2:string}
     */
    private function templateContentFromPayload(array $payload): array
    {
        $template = ($payload['schema_version'] ?? null) === 'page-builder-template/v1'
            ? ($payload['template'] ?? [])
            : $payload;

        if (! is_array($template)) {
            throw new InvalidArgumentException('Template payload is invalid.');
        }

        $project = null;
        $projectValue = $template['page_builder_json'] ?? $template['project'] ?? null;

        if (is_array($projectValue)) {
            $project = $projectValue;
        } elseif (is_string($projectValue) && trim($projectValue) !== '') {
            $decoded = json_decode($projectValue, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                throw new InvalidArgumentException('Template project data is invalid.');
            }
            $project = $decoded;
        } elseif (isset($payload['pages']) || isset($payload['styles']) || isset($payload['assets'])) {
            $project = $payload;
        }

        $html = is_string($template['html'] ?? null) ? $template['html'] : '';
        $css = is_string($template['css'] ?? null) ? $template['css'] : '';

        if ($project === null && trim($html) === '' && trim($css) === '') {
            throw new InvalidArgumentException('Template must include project data, HTML, or CSS.');
        }

        $editableSchema = $template['editable_schema'] ?? $payload['editable_schema'] ?? null;
        if (is_array($editableSchema)) {
            $project = is_array($project) ? $project : [];
            $project['editable_schema'] = $editableSchema;
            $project['template_key'] = (string) ($editableSchema['template_key'] ?? $project['template_key'] ?? '');
        }

        return [$project, $html, $css];
    }

    /**
     * @return array<string, string>
     */
    private function contentTypes(): array
    {
        return [
            'page' => 'Page',
            'header' => 'Header',
            'footer' => 'Footer',
            'block' => 'Block',
        ];
    }

    private function contentType(mixed $value): string
    {
        $value = is_string($value) ? $value : 'page';

        return array_key_exists($value, $this->contentTypes()) ? $value : 'page';
    }

    /**
     * @return array<int, array{id: string, label: string, category: string, content: string}>
     */
    private function savedBlocks(int $currentPageId): array
    {
        return DB::table('platform_pages')
            ->where('content_type', 'block')
            ->where('id', '!=', $currentPageId)
            ->whereNotNull('html')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'html', 'css'])
            ->map(fn (object $block): array => [
                'id' => 'saved-block-'.$block->id,
                'label' => (string) $block->title,
                'category' => 'Saved Blocks',
                'content' => '<section data-saved-block-id="'.$block->id.'">'.($block->html ?? '').'<style>'.($block->css ?? '').'</style></section>',
            ])
            ->all();
    }

    private function uniqueSlug(string $value, ?int $exceptId = null): string
    {
        $base = Str::slug($value) ?: 'page';
        $slug = $base;
        $index = 2;

        while (
            DB::table('platform_pages')
                ->where('slug', $slug)
                ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
                ->exists()
        ) {
            $slug = $base.'-'.$index;
            $index++;
        }

        return $slug;
    }

    private function parentOptions(int $excludeId)
    {
        return DB::table('platform_pages')
            ->where('content_type', 'page')
            ->where('id', '!=', $excludeId)
            ->orderBy('title')
            ->get(['id', 'title', 'menu_label']);
    }

    private function categories()
    {
        return DB::table('platform_pages')
            ->whereNotNull('category')
            ->orderBy('category')
            ->pluck('category')
            ->unique()
            ->values();
    }
}
