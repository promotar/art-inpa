<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Platform\Core\PageBuilder\BuilderSanitizer;
use App\Platform\Core\PageBuilder\PageBuilderDynamicSourceRegistry;
use App\Platform\Core\PageBuilder\PageBuilderRenderService;
use App\Platform\Core\PageBuilder\PageBuilderWidgetRegistry;
use App\Platform\Core\Services\ActivePluginStylesheets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use JsonException;

class ThemeBuilderController extends Controller
{
    public function index(): View
    {
        $templates = $this->templates();

        return view('admin.theme-builder.index', [
            'sections' => $this->sections($this->withConditions($templates)),
            'templates' => $this->withConditions($templates),
            'conditionScopes' => $this->conditionScopes(),
            'templateTypes' => $this->templateTypes(),
            'storageReady' => Schema::hasTable('platform_theme_builder_templates'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('platform_theme_builder_templates'), 500, 'Theme Builder template storage is not ready.');

        $data = $request->validate([
            'template_type' => ['required', Rule::in(array_keys($this->templateTypes()))],
            'name' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'template_file' => ['nullable', 'file', 'max:20480', 'mimes:json,html,htm,txt'],
        ]);

        $payload = $this->templatePayload($request);
        $name = trim((string) ($data['name'] ?? '')) ?: $this->defaultTemplateName($data['template_type']);
        $slug = $this->uniqueTemplateSlug($name);
        $now = now();

        DB::table('platform_theme_builder_templates')->insert([
            'template_type' => $data['template_type'],
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'source_type' => $payload['source_type'],
            'html' => $payload['html'],
            'css' => $payload['css'],
            'page_builder_json' => $payload['page_builder_json'],
            'metadata' => json_encode($payload['metadata'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_by' => $request->user()?->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return redirect()
            ->route('admin.theme-builder.index')
            ->with('status', 'Theme Builder template saved in dedicated template storage.');
    }

    public function edit(int $template): View
    {
        return view('admin.theme-builder.edit', [
            'template' => $this->findTemplate($template),
            'templateTypes' => $this->templateTypes(),
        ]);
    }

    public function builder(int $template, PageBuilderWidgetRegistry $widgets, PageBuilderDynamicSourceRegistry $dynamicSources): View
    {
        $templateRecord = $this->findTemplate($template);
        $builderContext = $this->builderContext($templateRecord);

        return view('admin.theme-builder.builder', [
            'template' => $templateRecord,
            'page' => $builderContext,
            'builderProject' => $this->builderProject($templateRecord),
            'editorCanvasHtml' => (string) ($templateRecord->html ?? ''),
            'editorCanvasCss' => $this->editorCanvasCss($templateRecord),
            'editorCanvasStyleUrls' => [
                route('admin.theme-builder.templates.editor-preview-css', $templateRecord->id).'?v='.md5((string) ($templateRecord->updated_at ?? $templateRecord->id)),
            ],
            'builderWidgets' => array_values($widgets->widgetMap()),
            'builderBlocks' => $widgets->blocks([]),
            'builderElementRegistry' => $widgets->elementRegistry(),
            'builderDynamicSources' => $dynamicSources->editorSources(request()->user(), $builderContext),
            'templateTypes' => $this->templateTypes(),
            'previewUrl' => route('admin.theme-builder.templates.preview', $templateRecord->id),
        ]);
    }

    public function update(Request $request, int $template): RedirectResponse
    {
        $template = $this->findTemplate($template);

        $data = $request->validate([
            'template_type' => ['required', Rule::in(array_keys($this->templateTypes()))],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'html' => ['nullable', 'string'],
            'css' => ['nullable', 'string'],
            'page_builder_json' => ['nullable', 'string'],
            'template_file' => ['nullable', 'file', 'max:20480', 'mimes:json,html,htm,txt'],
        ]);

        $payload = $request->hasFile('template_file')
            ? $this->templatePayload($request)
            : [
                'source_type' => (string) ($template->source_type ?? 'manual'),
                'html' => (string) ($data['html'] ?? ''),
                'css' => (string) ($data['css'] ?? ''),
                'page_builder_json' => $this->nullableJson($data['page_builder_json'] ?? null),
                'metadata' => $this->metadata($template),
            ];

        DB::table('platform_theme_builder_templates')
            ->where('id', $template->id)
            ->update([
                'template_type' => $data['template_type'],
                'name' => trim((string) $data['name']),
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
                'source_type' => $payload['source_type'],
                'html' => $payload['html'],
                'css' => $payload['css'],
                'page_builder_json' => $payload['page_builder_json'],
                'metadata' => json_encode(array_merge($payload['metadata'], [
                    'last_updated_from' => $request->hasFile('template_file') ? 'file_upload' : 'manual_edit',
                ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.theme-builder.templates.edit', $template->id)
            ->with('status', 'Theme Builder template updated.');
    }

    public function builderSave(Request $request, int $template, BuilderSanitizer $sanitizer): JsonResponse
    {
        $templateRecord = $this->findTemplate($template);
        $saved = $this->saveBuilderTemplate($request, $templateRecord, $sanitizer, 'manual-builder-save');

        return response()->json([
            'ok' => true,
            'message' => 'Theme Builder template saved successfully.',
            'page' => [
                'id' => $templateRecord->id,
                'slug' => $saved['slug'],
                'status' => $saved['status'],
                'public_url' => route('admin.theme-builder.templates.preview', $templateRecord->id),
                'updated_at' => $saved['updated_at'],
            ],
            'revisions' => [],
        ]);
    }

    public function autosave(Request $request, int $template, BuilderSanitizer $sanitizer): JsonResponse
    {
        $templateRecord = $this->findTemplate($template);
        $saved = $this->saveBuilderTemplate($request, $templateRecord, $sanitizer, 'autosave');

        return response()->json([
            'ok' => true,
            'message' => 'Autosaved.',
            'page' => [
                'id' => $templateRecord->id,
                'slug' => $saved['slug'],
                'status' => $saved['status'],
                'updated_at' => $saved['updated_at'],
            ],
        ]);
    }

    public function editorPreviewCss(int $template): Response
    {
        $templateRecord = $this->findTemplate($template);

        return response($this->editorCanvasCss($templateRecord), 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function editorComponentPreview(Request $request, int $template, PageBuilderRenderService $renderer, BuilderSanitizer $sanitizer): JsonResponse
    {
        $templateRecord = $this->findTemplate($template);
        $data = $request->validate([
            'html' => ['required', 'string'],
        ]);

        $rendered = $renderer->renderHtml($data['html'], $this->builderContext($templateRecord));
        $safe = $sanitizer->sanitize($rendered, '', false);

        return response()->json([
            'ok' => true,
            'html' => $safe['html'],
            'inner_html' => $this->firstElementInnerHtml($safe['html']),
        ]);
    }

    public function preview(int $template): Response
    {
        $template = $this->findTemplate($template);
        $html = (string) ($template->html ?? '');
        $css = (string) ($template->css ?? '');

        return response(<<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$this->escape((string) $template->name)}</title>
    <style>{$css}</style>
</head>
<body>
{$html}
</body>
</html>
HTML);
    }

    public function updateConditions(Request $request, int $template): RedirectResponse
    {
        $template = $this->findTemplate($template);

        $data = $request->validate([
            'operator' => ['required', Rule::in(['include', 'exclude'])],
            'scope' => ['required', Rule::in(array_keys($this->conditionScopes()))],
            'target_value' => ['nullable', 'string', 'max:500'],
        ]);

        DB::table('platform_theme_builder_template_conditions')->updateOrInsert(
            ['template_id' => $template->id],
            [
                'operator' => $data['operator'],
                'scope' => $data['scope'],
                'target_value' => $data['target_value'] !== null ? trim($data['target_value']) : null,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return back()->with('status', 'Display condition saved.');
    }

    public function destroy(int $template): RedirectResponse
    {
        $template = $this->findTemplate($template);

        DB::table('platform_theme_builder_template_conditions')
            ->where('template_id', $template->id)
            ->delete();

        DB::table('platform_theme_builder_templates')
            ->where('id', $template->id)
            ->delete();

        return back()->with('status', 'Theme Builder template removed.');
    }

    /**
     * @return Collection<int, object>
     */
    private function templates(): Collection
    {
        if (! Schema::hasTable('platform_theme_builder_templates')) {
            return collect();
        }

        return DB::table('platform_theme_builder_templates')
            ->orderByRaw("CASE template_type WHEN 'header' THEN 1 WHEN 'footer' THEN 2 WHEN 'single_post' THEN 3 WHEN 'single_page' THEN 4 WHEN 'archive' THEN 5 WHEN 'search_results' THEN 6 WHEN 'error_404' THEN 7 ELSE 9 END")
            ->latest('updated_at')
            ->latest('id')
            ->get([
                'id',
                'template_type',
                'name',
                'slug',
                'description',
                'status',
                'source_type',
                'updated_at',
            ]);
    }

    private function findTemplate(int $id): object
    {
        abort_unless(Schema::hasTable('platform_theme_builder_templates'), 404);

        $template = DB::table('platform_theme_builder_templates')->where('id', $id)->first();

        abort_if($template === null, 404);

        return $template;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sections(Collection $templates): array
    {
        return [
            $this->section('header', 'Header', 'Global top area used across public pages.', $templates->where('template_type', 'header')->values()),
            $this->section('footer', 'Footer', 'Global footer area used across public pages.', $templates->where('template_type', 'footer')->values()),
            $this->section('single_post', 'Single Post', 'Layout pattern for one news or blog article.', $templates->where('template_type', 'single_post')->values()),
            $this->section('single_page', 'Single Page', 'Reusable layout pattern for regular pages. Actual pages remain under Pages.', $templates->where('template_type', 'single_page')->values()),
            $this->section('archive', 'Archive', 'Category, tag, author, and listing templates.', $templates->where('template_type', 'archive')->values()),
            $this->section('search_results', 'Search Results', 'Template used when visitors search site content.', $templates->where('template_type', 'search_results')->values()),
            $this->section('error_404', '404 Page', 'Template shown when no matching page is found.', $templates->where('template_type', 'error_404')->values()),
        ];
    }

    /**
     * @param Collection<int, object> $templates
     * @return array<string, mixed>
     */
    private function section(string $key, string $label, string $description, Collection $templates): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'description' => $description,
            'templates' => $templates,
        ];
    }

    /**
     * @param Collection<int, object> $templates
     * @return Collection<int, object>
     */
    private function withConditions(Collection $templates): Collection
    {
        $conditions = Schema::hasTable('platform_theme_builder_template_conditions')
            ? DB::table('platform_theme_builder_template_conditions')->get()->keyBy('template_id')
            : collect();

        return $templates->map(function (object $template) use ($conditions): object {
            $condition = $conditions->get($template->id);

            $template->condition_operator = $condition->operator ?? 'include';
            $template->condition_scope = $condition->scope ?? 'entire_site';
            $template->condition_target_value = $condition->target_value ?? null;

            return $template;
        });
    }

    /**
     * @return array<string, string>
     */
    private function templateTypes(): array
    {
        return [
            'header' => 'Header',
            'footer' => 'Footer',
            'single_post' => 'Single Post',
            'single_page' => 'Single Page',
            'archive' => 'Archive',
            'search_results' => 'Search Results',
            'error_404' => '404 Page',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function conditionScopes(): array
    {
        return [
            'entire_site' => 'Entire Site',
            'front_page' => 'Front Page',
            'all_pages' => 'All Pages',
            'specific_pages' => 'Specific Pages',
            'all_posts' => 'All Posts',
            'specific_posts' => 'Specific Posts',
            'post_categories' => 'Post Categories',
            'archives' => 'Archives',
            'search_results' => 'Search Results',
            'not_found' => '404 Page',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function templatePayload(Request $request): array
    {
        if (! $request->hasFile('template_file')) {
            return [
                'source_type' => 'blank',
                'html' => '',
                'css' => '',
                'page_builder_json' => null,
                'metadata' => ['created_from' => 'blank'],
            ];
        }

        $file = $request->file('template_file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $content = (string) file_get_contents($file->getRealPath());

        if (in_array($extension, ['html', 'htm'], true)) {
            return [
                'source_type' => 'html',
                'html' => $content,
                'css' => '',
                'page_builder_json' => null,
                'metadata' => [
                    'created_from' => 'html_upload',
                    'original_name' => $file->getClientOriginalName(),
                ],
            ];
        }

        if ($extension === 'json') {
            try {
                $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                abort(422, 'Uploaded template JSON is invalid.');
            }

            $template = is_array($json['template'] ?? null) ? $json['template'] : $json;

            return [
                'source_type' => 'json',
                'html' => (string) ($template['html'] ?? ''),
                'css' => (string) ($template['css'] ?? ''),
                'page_builder_json' => json_encode($template['page_builder_json'] ?? $json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'metadata' => [
                    'created_from' => 'json_upload',
                    'original_name' => $file->getClientOriginalName(),
                    'schema_version' => $json['schema_version'] ?? null,
                ],
            ];
        }

        return [
            'source_type' => 'text',
            'html' => $content,
            'css' => '',
            'page_builder_json' => null,
            'metadata' => [
                'created_from' => 'text_upload',
                'original_name' => $file->getClientOriginalName(),
            ],
        ];
    }

    private function defaultTemplateName(string $type): string
    {
        return ($this->templateTypes()[$type] ?? 'Theme').' Template '.now()->format('Y-m-d H:i');
    }

    private function uniqueTemplateSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'theme-template';
        $slug = $base;
        $counter = 2;

        while (DB::table('platform_theme_builder_templates')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function nullableJson(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            abort(422, 'Page Builder JSON must be valid JSON.');
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(object $template): array
    {
        $metadata = json_decode((string) ($template->metadata ?? ''), true);

        return is_array($metadata) ? $metadata : [];
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function editorCanvasCss(object $template): string
    {
        $html = (string) ($template->html ?? '');
        $chunks = [(string) ($template->css ?? '')];
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

    private function builderContext(object $template): object
    {
        return (object) [
            'id' => $template->id,
            'title' => $template->name,
            'slug' => $template->slug,
            'content_type' => $template->template_type,
            'status' => $template->status,
            'seo_title' => $template->name,
            'meta_description' => $template->description,
            'html' => $template->html,
            'content' => $template->html,
            'css' => $template->css,
            'page_builder_json' => $template->page_builder_json,
            'updated_at' => $template->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function builderProject(object $template): ?array
    {
        if (! is_string($template->page_builder_json ?? null) || trim($template->page_builder_json) === '') {
            return null;
        }

        $project = json_decode($template->page_builder_json, true);

        return $this->normalizeBuilderProject($project);
    }

    /**
     * @param mixed $project
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
     * @return array{slug:string,status:string,updated_at:string}
     */
    private function saveBuilderTemplate(Request $request, object $template, BuilderSanitizer $sanitizer, string $reason): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160'],
            'content_type' => ['required', Rule::in(array_keys($this->templateTypes()))],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'page_builder_json' => ['nullable', 'string'],
            'html' => ['nullable', 'string'],
            'css' => ['nullable', 'string'],
        ]) + [
            'page_builder_json' => '',
            'html' => '',
            'css' => '',
        ];

        $sanitized = $sanitizer->sanitize(
            (string) $data['html'],
            (string) $data['css'],
            $this->allowsUnsafeBuilderMarkup($request),
        );
        $slug = $this->uniqueTemplateSlugForUpdate((string) ($data['slug'] ?: $data['title']), (int) $template->id);
        $metadata = $this->metadata($template);
        $metadata['last_updated_from'] = $reason;
        $metadata['last_builder_save_at'] = now()->toDateTimeString();
        $now = now();

        DB::table('platform_theme_builder_templates')
            ->where('id', $template->id)
            ->update([
                'template_type' => $data['content_type'],
                'name' => trim((string) $data['title']),
                'slug' => $slug,
                'description' => $data['meta_description'] ?? null,
                'status' => $data['status'],
                'source_type' => 'page_builder',
                'html' => $sanitized['html'] !== '' ? $sanitized['html'] : null,
                'css' => $sanitized['css'] !== '' ? $sanitized['css'] : null,
                'page_builder_json' => trim((string) $data['page_builder_json']) !== '' ? $data['page_builder_json'] : null,
                'metadata' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'updated_at' => $now,
            ]);

        return [
            'slug' => $slug,
            'status' => (string) $data['status'],
            'updated_at' => $now->toDateTimeString(),
        ];
    }

    private function uniqueTemplateSlugForUpdate(string $name, int $exceptId): string
    {
        $base = Str::slug($name) ?: 'theme-template';
        $slug = $base;
        $counter = 2;

        while (
            DB::table('platform_theme_builder_templates')
                ->where('slug', $slug)
                ->where('id', '!=', $exceptId)
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function allowsUnsafeBuilderMarkup(Request $request): bool
    {
        $user = $request->user();

        return $user && method_exists($user, 'hasRole') && $user->hasRole('super-admin');
    }

    private function firstElementInnerHtml(string $html): string
    {
        $html = trim($html);

        if (preg_match('/^<([a-z][a-z0-9:-]*)(?:\s[^>]*)?>(.*)<\/\1>$/is', $html, $matches)) {
            return (string) ($matches[2] ?? '');
        }

        return $html;
    }
}
