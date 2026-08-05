<x-app-layout>
    @php
        $isEdit = $post->exists;
        $action = $isEdit ? route('admin.plugins.blog.posts.update', $post, false) : route('admin.plugins.blog.posts.store', [], false);
        $selectedTemplate = old('template', $post->template ?: $post->layout_template ?: 'default');
        $selectedFeaturedId = (int) old('featured_image_id', $post->featured_image_id);
        $featuredUrl = old('featured_image', $post->featuredImage?->url ?: $post->featured_image);
        $featuredAlt = old('featured_image_alt', $post->featured_image_alt ?: $post->featuredImage?->alt_text);
        $hasError = fn (string $field): bool => isset($errors) && $errors->has($field);
        $errorClass = fn (string $field): string => $hasError($field) ? ' wp-field-error' : '';
        $errorId = fn (string $field): string => 'error-'.str_replace(['.', '_'], '-', $field);
        $errorLabels = [
            'title' => 'Title',
            'slug' => 'Slug',
            'content' => 'Content',
            'excerpt' => 'Excerpt',
            'status' => 'Status',
            'visibility' => 'Visibility',
            'password' => 'Password',
            'published_at' => 'Publish date',
            'scheduled_at' => 'Schedule date',
            'category_id' => 'Category',
            'featured_image_id' => 'Featured image',
            'featured_image_alt' => 'Featured image alt',
            'template' => 'Template',
            'seo_title' => 'SEO title',
            'seo_description' => 'SEO meta description',
            'focus_keyword' => 'Focus keyword',
            'canonical_url' => 'Canonical URL',
            'schema_type' => 'Schema type',
        ];
        $publicPostPath = $isEdit && $post->slug ? '/blog/'.$post->slug : null;
        $isPubliclyViewable = $publicPostPath
            && $post->status === 'published'
            && $post->visibility === 'public'
            && (! $post->published_at || $post->published_at->isPast())
            && (! $post->scheduled_at || $post->scheduled_at->isPast());
    @endphp

    <style>
        body { background: #f0f0f1; }
        .wp-post-screen { background:#f0f0f1; color:#1d2327; font-family: Arial, sans-serif; padding: 18px 22px 32px; }
        .wp-post-screen * { box-sizing: border-box; }
        .wp-heading { font-size:23px; font-weight:400; margin:0 0 10px; }
        .wp-notice { background:#fff; border-left:4px solid #00a32a; box-shadow:0 1px 1px rgba(0,0,0,.04); margin:0 0 14px; padding:10px 12px; }
        .wp-notice.error { border-left-color:#d63638; }
        .wp-error-summary { margin:8px 0 0 18px; }
        .wp-error-summary a { color:#b32d2e; text-decoration:underline; }
        .wp-field-error { border-color:#d63638 !important; box-shadow:0 0 0 1px #d63638 !important; }
        .wp-error-text { color:#d63638; font-size:12px; margin-top:5px; }
        .wp-grid { display:grid; grid-template-columns:minmax(0, 1fr) 280px; gap:18px; align-items:start; }
        .wp-title-input { width:100%; height:38px; border:1px solid #8c8f94; padding:4px 8px; font-size:1.7em; line-height:1.1; background:#fff; }
        .wp-permalink { margin:8px 0 16px; color:#50575e; font-size:13px; }
        .wp-permalink input { width:220px; border:1px solid #c3c4c7; padding:4px 6px; }
        .wp-builder-row { margin: 22px 0; }
        .wp-blue-btn { background:#3858e9; border:1px solid #3858e9; color:#fff; padding:11px 28px; font-weight:600; cursor:pointer; }
        .wp-toolbar-row { display:flex; justify-content:space-between; align-items:end; margin-top:10px; }
        .wp-media-actions { display:flex; gap:6px; }
        .wp-secondary, .wp-tab, .wp-quicktag { background:#f6f7f7; border:1px solid #2271b1; color:#2271b1; cursor:pointer; padding:7px 10px; text-decoration:none; }
        .wp-tab { border-color:#c3c4c7; color:#50575e; border-bottom:0; background:#f6f7f7; }
        .wp-tab.active { background:#fff; color:#1d2327; }
        .wp-editor-wrap { border:1px solid #c3c4c7; background:#fff; }
        .wp-quicktags { border-bottom:1px solid #dcdcde; padding:6px; background:#f6f7f7; display:flex; flex-wrap:wrap; gap:4px; }
        .wp-quicktag { padding:4px 8px; border-color:#8c8f94; color:#1d2327; font-size:12px; }
        #post-content { min-height:360px; }
        #html-editor { display:none; width:100%; min-height:430px; border:0; padding:12px; font-family:Consolas, monospace; font-size:13px; resize:vertical; }
        .wp-word-count { border-top:1px solid #dcdcde; color:#50575e; padding:6px 8px; font-size:12px; }
        .wp-box { background:#fff; border:1px solid #c3c4c7; margin-bottom:14px; }
        .wp-box-title { margin:0; padding:9px 12px; border-bottom:1px solid #dcdcde; font-size:14px; font-weight:600; display:flex; justify-content:space-between; align-items:center; cursor:pointer; }
        .wp-box-body { padding:12px; }
        .wp-box.is-collapsed .wp-box-body { display:none; }
        .wp-input, .wp-select, .wp-textarea { width:100%; border:1px solid #8c8f94; background:#fff; padding:6px 8px; min-height:32px; }
        .wp-textarea { resize:vertical; min-height:64px; }
        .wp-help { color:#646970; font-size:12px; line-height:1.5; margin-top:6px; }
        .wp-publish-actions { display:flex; justify-content:space-between; gap:8px; margin-bottom:12px; }
        .wp-status-line { margin:10px 0; font-size:13px; }
        .wp-side-submit { background:#f6f7f7; border-top:1px solid #dcdcde; margin:12px -12px -12px; padding:10px 12px; text-align:right; }
        .wp-primary { background:#2271b1; border:1px solid #2271b1; color:#fff; cursor:pointer; padding:7px 14px; font-weight:600; }
        .wp-danger { background:#fff; border:1px solid #d63638; color:#d63638; cursor:pointer; padding:6px 10px; }
        .wp-seo-mini { background:#fce8e8; color:#d63638; margin:10px -12px; padding:10px 12px; font-weight:600; }
        .wp-cat-list { max-height:170px; overflow:auto; border:1px solid #dcdcde; padding:8px; }
        .wp-cat-list label { display:block; margin:4px 0; font-size:13px; }
        .seo-preview { padding:14px 12px; border-bottom:1px solid #f0f0f1; }
        .seo-preview-title { color:#1a0dab; font-size:16px; margin-bottom:4px; }
        .seo-preview-url { color:#006621; font-size:12px; word-break:break-all; }
        .seo-preview-description { color:#4d5156; font-size:13px; margin-top:4px; }
        .seo-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .rank-score-row { display:grid; grid-template-columns:1fr 80px; gap:0; align-items:center; }
        .rank-score-cell { background:#fce8e8; color:#d63638; border:1px solid #f3c5c5; border-left:0; padding:7px; text-align:center; font-weight:600; }
        .rank-section { border-top:1px solid #dcdcde; padding:12px; }
        .rank-pill { background:#f4a6a6; color:#fff; border-radius:12px; font-size:11px; padding:2px 8px; margin-left:6px; }
        .rank-check { display:flex; align-items:center; gap:8px; margin:8px 0; color:#50575e; font-size:13px; }
        .rank-check .mark { width:14px; height:14px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; color:#fff; font-size:10px; background:#d63638; }
        .rank-check[data-state="good"] .mark { background:#00a32a; }
        .layout-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:12px; }
        .layout-option { border:2px solid transparent; cursor:pointer; display:block; padding:6px; }
        .layout-option:has(input:checked) { border-color:#2271b1; }
        .layout-option input { margin-bottom:5px; }
        .layout-thumb { height:95px; border:1px solid #ccd0d4; background:#fff; padding:8px; }
        .layout-thumb .line { height:6px; background:#dcdcde; margin-bottom:6px; }
        .layout-thumb .hero { height:35px; background:#a7c7e7; margin-bottom:6px; }
        .media-modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,.58); z-index:1000; }
        .media-modal.is-open { display:flex; }
        .media-panel { width:min(980px, 92vw); max-height:88vh; background:#fff; border:1px solid #1d2327; display:grid; grid-template-rows:auto auto 1fr auto; }
        .media-head, .media-foot { padding:12px 16px; border-bottom:1px solid #dcdcde; display:flex; justify-content:space-between; align-items:center; }
        .media-foot { border-top:1px solid #dcdcde; border-bottom:0; }
        .media-tabs { display:flex; gap:0; border-bottom:1px solid #dcdcde; padding:0 16px; background:#f6f7f7; }
        .media-tabs button { border:0; border-right:1px solid #dcdcde; background:transparent; cursor:pointer; padding:10px 14px; }
        .media-tabs button.is-active { background:#fff; font-weight:600; }
        .media-body { min-height:440px; overflow:auto; }
        .media-panel-view { padding:18px; }
        .media-upload-drop { border:2px dashed #c3c4c7; min-height:245px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; text-align:center; background:#fbfbfc; }
        .media-upload-status { color:#50575e; min-height:20px; }
        .media-library { overflow:auto; display:grid; grid-template-columns:repeat(auto-fill, minmax(115px, 1fr)); gap:10px; align-content:start; }
        .media-tile { border:2px solid transparent; background:#f6f7f7; min-height:100px; cursor:pointer; padding:5px; text-align:center; overflow:hidden; }
        .media-tile.is-selected { border-color:#2271b1; }
        .media-tile img { width:100%; height:86px; object-fit:cover; display:block; margin-bottom:4px; }
        .media-selected-preview { display:flex; align-items:center; gap:10px; color:#50575e; font-size:13px; }
        .media-selected-preview img { width:48px; height:48px; object-fit:cover; border:1px solid #dcdcde; }
        .revisions-list { max-height:210px; overflow:auto; }
        .revision-row { border-top:1px solid #dcdcde; padding:8px 0; display:flex; justify-content:space-between; gap:8px; align-items:center; }
        @media (max-width: 980px) { .wp-grid { grid-template-columns:1fr; } .wp-side-column { order:-1; } .layout-grid { grid-template-columns:1fr 1fr; } }
    </style>

    <div class="wp-post-screen">
        <h1 class="wp-heading">{{ $isEdit ? 'Edit Post' : 'Add Post' }}</h1>

        @if (session('status'))
            <div class="wp-notice">{{ session('status') }}</div>
        @endif

        @if (isset($errors) && $errors->any())
            <div class="wp-notice error">
                <strong>Post was not saved. Please fix the highlighted fields below.</strong>
                <ul class="wp-error-summary">
                    @foreach ($errors->messages() as $field => $messages)
                        <li><a href="#field-{{ str_replace(['.', '_'], '-', $field) }}" data-error-link="{{ $field }}">{{ $errorLabels[$field] ?? ucfirst(str_replace('_', ' ', $field)) }}: {{ $messages[0] }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="post-form" method="POST" action="{{ $action }}">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <input type="hidden" name="featured_image_id" id="featured-image-id" value="{{ $selectedFeaturedId ?: '' }}">
            <input type="hidden" name="featured_image" id="featured-image-url" value="{{ $featuredUrl }}">

            <div class="wp-grid">
                <main>
                    <input id="post-title" class="wp-title-input{{ $errorClass('title') }}" name="title" value="{{ old('title', $post->title) }}" placeholder="Add title" required aria-describedby="{{ $hasError('title') ? $errorId('title') : '' }}">
                    @error('title')
                        <div class="wp-error-text" id="{{ $errorId('title') }}">{{ $message }}</div>
                    @enderror
                    <div class="wp-permalink">
                        Permalink:
                        <span>/blog/</span>
                        <input id="post-slug" class="{{ trim($errorClass('slug')) }}" name="slug" value="{{ old('slug', $post->slug) }}" placeholder="auto-generated" aria-describedby="{{ $hasError('slug') ? $errorId('slug') : '' }}">
                        <button class="wp-secondary" type="button" id="generate-slug">Generate Slug</button>
                        @if ($isPubliclyViewable)
                            <a class="wp-secondary" href="{{ $publicPostPath }}" target="_blank" rel="noopener">View Public Post</a>
                        @endif
                        @error('slug')
                            <div class="wp-error-text" id="{{ $errorId('slug') }}">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="wp-builder-row" id="builder-row" style="display:none;">
                        <button class="wp-blue-btn" type="button">Edit with Builder</button>
                    </div>

                    <div class="wp-toolbar-row">
                        <div class="wp-media-actions">
                            <button type="button" class="wp-secondary" data-media-open="insert">Add Media</button>
                        </div>
                        <div>
                            <button type="button" class="wp-tab active" data-editor-tab="visual">Visual</button>
                            <button type="button" class="wp-tab" data-editor-tab="code">Code</button>
                        </div>
                    </div>

                    <div id="field-content" class="wp-editor-wrap{{ $errorClass('content') }}">
                        <div class="wp-quicktags">
                            @foreach (['b','i','link','blockquote','del','ins','img','ul','ol','li','code'] as $tag)
                                <button type="button" class="wp-quicktag" data-quicktag="{{ $tag }}">{{ $tag }}</button>
                            @endforeach
                        </div>
                        <textarea id="post-content" name="content">{{ old('content', $post->content) }}</textarea>
                        <textarea id="html-editor" aria-label="HTML source editor"></textarea>
                        <div class="wp-word-count">Word count: <span id="word-count">0</span></div>
                    </div>
                    @error('content')
                        <div class="wp-error-text" id="{{ $errorId('content') }}">{{ $message }}</div>
                    @enderror

                    <section class="wp-box">
                        <h2 class="wp-box-title">Excerpt <span>^</span></h2>
                        <div class="wp-box-body">
                            <textarea id="field-excerpt" class="wp-textarea{{ $errorClass('excerpt') }}" name="excerpt">{{ old('excerpt', $post->excerpt) }}</textarea>
                            @error('excerpt')
                                <div class="wp-error-text" id="{{ $errorId('excerpt') }}">{{ $message }}</div>
                            @enderror
                            <div class="wp-help">Excerpts are optional hand-crafted summaries used by themes and SEO previews.</div>
                        </div>
                    </section>

                    <section class="wp-box">
                        <h2 class="wp-box-title">Rank Math SEO <span>^</span></h2>
                        <div class="seo-preview">
                            <strong>Preview</strong>
                            <div id="seo-preview-title" class="seo-preview-title">{{ old('seo_title', $post->seo_title) ?: old('title', $post->title ?: 'News ART') }}</div>
                            <div id="seo-preview-url" class="seo-preview-url">/blog/{{ old('slug', $post->slug ?: 'post-slug') }}</div>
                            <div id="seo-preview-description" class="seo-preview-description">{{ old('seo_description', $post->seo_description ?: $post->excerpt) }}</div>
                        </div>
                        <div class="wp-box-body">
                            <label><strong>Focus Keyword</strong></label>
                            <div class="rank-score-row">
                                <input id="focus-keyword" class="wp-input{{ $errorClass('focus_keyword') }}" name="focus_keyword" value="{{ old('focus_keyword', $post->focus_keyword) }}" placeholder="Example: Rank Math SEO">
                                <div class="rank-score-cell"><span id="seo-score-inline">{{ (int) old('seo_score', $post->seo_score ?? 0) }}</span> / 100</div>
                            </div>
                            @error('focus_keyword')
                                <div class="wp-error-text" id="{{ $errorId('focus_keyword') }}">{{ $message }}</div>
                            @enderror
                            <div class="wp-help">The score updates live from the saved SEO fields and content.</div>

                            <div class="seo-grid">
                                <label><strong>SEO Title</strong><input id="seo-title" class="wp-input{{ $errorClass('seo_title') }}" name="seo_title" value="{{ old('seo_title', $post->seo_title) }}">@error('seo_title')<div class="wp-error-text" id="{{ $errorId('seo_title') }}">{{ $message }}</div>@enderror</label>
                                <label><strong>Canonical URL</strong><input id="canonical-url" class="wp-input{{ $errorClass('canonical_url') }}" name="canonical_url" value="{{ old('canonical_url', $post->canonical_url) }}">@error('canonical_url')<div class="wp-error-text" id="{{ $errorId('canonical_url') }}">{{ $message }}</div>@enderror</label>
                            </div>
                            <label style="display:block;margin-top:12px;"><strong>SEO Meta Description</strong><textarea id="seo-description" class="wp-textarea{{ $errorClass('seo_description') }}" name="seo_description">{{ old('seo_description', $post->seo_description) }}</textarea>@error('seo_description')<div class="wp-error-text" id="{{ $errorId('seo_description') }}">{{ $message }}</div>@enderror</label>
                            <label style="display:block;margin-top:12px;"><strong>Schema</strong>
                                <select id="schema-type" class="wp-select{{ $errorClass('schema_type') }}" name="schema_type">
                                    @foreach ($schemaTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(old('schema_type', $post->schema_type ?: 'BlogPosting') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('schema_type')<div class="wp-error-text" id="{{ $errorId('schema_type') }}">{{ $message }}</div>@enderror
                            </label>
                            <label style="display:inline-block;margin-top:12px;margin-right:18px;"><input type="checkbox" name="robots_index" value="1" @checked(old('robots_index', $post->robots_index ?? true))> Index this post</label>
                            <label style="display:inline-block;margin-top:12px;"><input type="checkbox" name="robots_follow" value="1" @checked(old('robots_follow', $post->robots_follow ?? true))> Follow links</label>
                        </div>
                        <div class="rank-section">
                            <strong>Basic SEO</strong><span id="seo-error-pill" class="rank-pill">0 Errors</span>
                            @foreach ([
                                'keyword-title' => 'Add Focus Keyword to the SEO title.',
                                'keyword-description' => 'Add Focus Keyword to your SEO Meta Description.',
                                'keyword-url' => 'Use Focus Keyword in the URL.',
                                'keyword-content' => 'Use Focus Keyword in the content.',
                                'content-length' => 'Content should be 600 words or longer.',
                                'description-length' => 'Meta description should be 120-160 characters.',
                                'title-length' => 'SEO title should be 35-65 characters.',
                            ] as $key => $label)
                                <div class="rank-check" data-check="{{ $key }}" data-state="bad"><span class="mark">x</span><span>{{ $label }}</span></div>
                            @endforeach
                        </div>
                    </section>

                    <section class="wp-box">
                        <h2 class="wp-box-title">Post Layout Options <span>^</span></h2>
                        <div class="wp-box-body">
                            <div class="layout-grid">
                                @foreach ($templates as $value => $label)
                                    <label class="layout-option">
                                        <input type="radio" name="template" value="{{ $value }}" @checked($selectedTemplate === $value)>
                                        <div class="layout-thumb">
                                            <div class="line"></div><div class="line" style="width:75%"></div><div class="hero"></div><div class="line"></div>
                                        </div>
                                        <div class="wp-help">{{ $label }}</div>
                                    </label>
                                @endforeach
                            </div>
                            @error('template')<div class="wp-error-text" id="{{ $errorId('template') }}">{{ $message }}</div>@enderror
                            <input type="hidden" name="layout" id="post-layout" value="{{ old('layout', $post->layout ?: $selectedTemplate) }}">
                            @error('layout')<div class="wp-error-text" id="{{ $errorId('layout') }}">{{ $message }}</div>@enderror
                        </div>
                    </section>

                    @if ($isEdit && $post->revisions->isNotEmpty())
                        <section class="wp-box">
                            <h2 class="wp-box-title">Revisions <span>^</span></h2>
                            <div class="wp-box-body revisions-list">
                                @foreach ($post->revisions->take(4) as $revision)
                                    <div class="revision-row">
                                        <span>{{ $revision->revision_type }} - {{ $revision->created_at?->format('Y-m-d H:i') }} - {{ $revision->user?->name }}</span>
                                        <div style="display:flex;gap:6px;">
                                            <form method="POST" action="{{ route('admin.plugins.blog.posts.revisions.restore', [$post, $revision], false) }}">
                                                @csrf
                                                <button class="wp-secondary" type="submit">Restore</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.plugins.blog.posts.revisions.destroy', [$post, $revision], false) }}" onsubmit="return confirm('Delete this revision?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="wp-danger" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="wp-help">Only the latest 4 revisions are kept.</div>
                            </div>
                        </section>
                    @endif
                </main>

                <aside class="wp-side-column">
                    <section class="wp-box">
                        <h2 class="wp-box-title">Publish <span>^</span></h2>
                        <div class="wp-box-body">
                            <div class="wp-publish-actions">
                                <button class="wp-secondary" name="intent" value="draft" type="submit">Save Draft</button>
                                @if ($isPubliclyViewable)
                                    <a class="wp-secondary" href="{{ $publicPostPath }}" target="_blank" rel="noopener">View Public Post</a>
                                @else
                                    <button class="wp-secondary" name="intent" value="preview" type="submit">Preview Draft</button>
                                @endif
                            </div>
                            <div class="wp-status-line">Status:
                                <select id="field-status" class="wp-select{{ $errorClass('status') }}" name="status">
                                    @foreach (['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled', 'private' => 'Private'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $post->status ?: 'draft') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status')<div class="wp-error-text" id="{{ $errorId('status') }}">{{ $message }}</div>@enderror
                            </div>
                            <div class="wp-status-line">Visibility:
                                <select class="wp-select{{ $errorClass('visibility') }}" name="visibility" id="post-visibility">
                                    @foreach (['public' => 'Public', 'private' => 'Private', 'password' => 'Password protected'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('visibility', $post->visibility ?: 'public') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('visibility')<div class="wp-error-text" id="{{ $errorId('visibility') }}">{{ $message }}</div>@enderror
                            </div>
                            <div class="wp-status-line" id="password-row">Password:
                                <input id="field-password" class="wp-input{{ $errorClass('password') }}" name="password" value="{{ old('password', $post->password) }}">
                                @error('password')<div class="wp-error-text" id="{{ $errorId('password') }}">{{ $message }}</div>@enderror
                            </div>
                            <div class="wp-status-line">Publish:
                                <input id="field-published-at" class="wp-input{{ $errorClass('published_at') }}" type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                                @error('published_at')<div class="wp-error-text" id="{{ $errorId('published_at') }}">{{ $message }}</div>@enderror
                            </div>
                            <div class="wp-status-line">Schedule:
                                <input id="field-scheduled-at" class="wp-input{{ $errorClass('scheduled_at') }}" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $post->scheduled_at?->format('Y-m-d\TH:i')) }}">
                                @error('scheduled_at')<div class="wp-error-text" id="{{ $errorId('scheduled_at') }}">{{ $message }}</div>@enderror
                            </div>
                            <div class="wp-seo-mini">SEO: <span id="seo-sidebar-score">{{ (int) old('seo_score', $post->seo_score ?? 0) }}</span> / 100</div>
                            <div class="wp-side-submit">
                                <button class="wp-secondary" name="intent" value="schedule" type="submit">Schedule</button>
                                <button class="wp-primary" name="intent" value="publish" type="submit">{{ $isEdit ? 'Update' : 'Publish' }}</button>
                            </div>
                        </div>
                    </section>

                    <section class="wp-box">
                        <h2 class="wp-box-title">Categories <span>^</span></h2>
                        <div class="wp-box-body">
                            <div class="wp-cat-list" id="category-list">
                                <label><input type="radio" name="category_id" value="" @checked(! old('category_id', $post->category_id))> Uncategorized</label>
                                @foreach ($categories as $category)
                                    <label><input type="radio" name="category_id" value="{{ $category->id }}" @checked((int) old('category_id', $post->category_id) === $category->id)> {{ $category->name }}</label>
                                @endforeach
                            </div>
                            @error('category_id')<div class="wp-error-text" id="{{ $errorId('category_id') }}">{{ $message }}</div>@enderror
                            <div style="display:flex; gap:6px; margin-top:8px;">
                                <input id="new-category-name" class="wp-input" placeholder="New category name">
                                <button type="button" class="wp-secondary" id="add-category">Add</button>
                            </div>
                        </div>
                    </section>

                    <section class="wp-box">
                        <h2 class="wp-box-title">Tags <span>^</span></h2>
                        <div class="wp-box-body">
                            <input id="post-tags" class="wp-input" name="tags" value="{{ old('tags', $isEdit ? $post->tags->pluck('name')->implode(', ') : '') }}">
                            <div class="wp-help">Separate tags with commas. New tags are created on save.</div>
                        </div>
                    </section>

                    <section class="wp-box">
                        <h2 class="wp-box-title">Featured image <span>^</span></h2>
                        <div class="wp-box-body">
                            <button type="button" class="wp-secondary" data-media-open="featured">Set featured image</button>
                            <input class="wp-input{{ $errorClass('featured_image_alt') }}" style="margin-top:8px;" name="featured_image_alt" id="featured-image-alt" value="{{ $featuredAlt }}" placeholder="Image alt text">
                            @error('featured_image_id')<div class="wp-error-text" id="{{ $errorId('featured_image_id') }}">{{ $message }}</div>@enderror
                            @error('featured_image_alt')<div class="wp-error-text" id="{{ $errorId('featured_image_alt') }}">{{ $message }}</div>@enderror
                            <div id="featured-preview" style="margin-top:10px;">
                                @if ($featuredUrl)
                                    <img src="{{ $featuredUrl }}" alt="" style="max-width:100%; display:block;">
                                @endif
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </form>

        @if ($isEdit)
            <form method="POST" action="{{ route('admin.plugins.blog.posts.destroy', $post, false) }}" onsubmit="return confirm('Move this post to trash?')">
                @csrf
                @method('DELETE')
                <button class="wp-danger" type="submit">Move to Trash</button>
            </form>
        @endif
    </div>

    <div class="media-modal" id="media-modal" aria-hidden="true">
        <div class="media-panel">
            <div class="media-head"><strong>Media Library</strong><button class="wp-secondary" type="button" id="media-close">Close</button></div>
            <div class="media-tabs">
                <button type="button" class="is-active" data-media-tab="upload">Upload files</button>
                <button type="button" data-media-tab="library">Media Library</button>
            </div>
            <div class="media-body">
                <div class="media-panel-view" data-media-panel="upload">
                    <div class="media-upload-drop">
                        <strong>Drop files to upload</strong>
                        <span>or</span>
                        <button type="button" class="wp-secondary" id="media-select-file">Select image</button>
                        <input type="file" id="media-file" accept=".png,.jpg,.jpeg,.webp,.gif,.ico" hidden>
                        <div class="media-upload-status" id="media-upload-status">Selecting an image uploads it immediately.</div>
                    </div>
                </div>
                <div class="media-panel-view" data-media-panel="library" hidden>
                    <div class="media-library" id="media-library">
                        <p class="wp-help">Open the library to load existing media.</p>
                    </div>
                </div>
            </div>
            <div class="media-foot">
                <div class="media-selected-preview" id="selected-media-details">Select or upload an image.</div>
                <div>
                    <button class="wp-primary" type="button" id="media-use" disabled>Use image</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        (() => {
            const csrf = @json(csrf_token());
            const routes = {
                autosave: @json(route('admin.plugins.blog.posts.autosave', [], false)),
                slug: @json(route('admin.plugins.blog.posts.slug', [], false)),
                mediaIndex: @json(route('admin.media.index', [], false)),
                mediaStore: @json(route('admin.media.store', [], false)),
                categoryQuick: @json(route('admin.plugins.blog.categories.quick-store', [], false)),
            };
            let postId = @json($post->id);
            let mediaMode = 'insert';
            let selectedMedia = null;
            let currentTab = 'visual';
            let mediaLibraryLoaded = false;
            let mediaLibraryLoading = false;

            const title = document.getElementById('post-title');
            const slug = document.getElementById('post-slug');
            const content = document.getElementById('post-content');
            const htmlEditor = document.getElementById('html-editor');
            const focus = document.getElementById('focus-keyword');
            const seoTitle = document.getElementById('seo-title');
            const seoDescription = document.getElementById('seo-description');
            const tags = document.getElementById('post-tags');
            const wordCount = document.getElementById('word-count');
            const scoreInline = document.getElementById('seo-score-inline');
            const scoreSidebar = document.getElementById('seo-sidebar-score');
            const errorPill = document.getElementById('seo-error-pill');
            const previewTitle = document.getElementById('seo-preview-title');
            const previewUrl = document.getElementById('seo-preview-url');
            const previewDescription = document.getElementById('seo-preview-description');
            const visibility = document.getElementById('post-visibility');
            const passwordRow = document.getElementById('password-row');

            function getEditorContent() {
                if (window.tinymce && tinymce.get('post-content')) {
                    return tinymce.get('post-content').getContent();
                }
                return content.value;
            }

            function setEditorContent(value) {
                if (window.tinymce && tinymce.get('post-content')) {
                    tinymce.get('post-content').setContent(value || '');
                }
                content.value = value || '';
                htmlEditor.value = value || '';
                refreshSeo();
            }

            function syncFromVisual() {
                const html = getEditorContent();
                content.value = html;
                htmlEditor.value = html;
            }

            function syncFromCode() {
                setEditorContent(htmlEditor.value);
            }

            function plainText(html) {
                const div = document.createElement('div');
                div.innerHTML = html || '';
                return (div.textContent || div.innerText || '').replace(/\s+/g, ' ').trim();
            }

            function slugify(value) {
                return (value || '').toString().trim().toLowerCase()
                    .replace(/[\s_]+/g, '-')
                    .replace(/[^\u0600-\u06FFa-z0-9-]/g, '')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
            }

            async function generateSlug() {
                if (!title.value.trim()) return;
                const response = await fetch(routes.slug, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                    body: JSON.stringify({title: title.value, post_id: postId})
                });
                if (response.ok) {
                    const data = await response.json();
                    slug.value = data.slug;
                    refreshSeo();
                }
            }

            function refreshSeo() {
                const html = currentTab === 'code' ? htmlEditor.value : getEditorContent();
                const text = plainText(html);
                const words = text ? text.split(/\s+/).filter(Boolean).length : 0;
                const keyword = (focus.value || '').toLowerCase().trim();
                const titleText = (seoTitle.value || title.value || '').toLowerCase();
                const descText = (seoDescription.value || '').toLowerCase();
                const slugText = (slug.value || slugify(title.value)).toLowerCase();
                const checks = {
                    'keyword-title': keyword && titleText.includes(keyword),
                    'keyword-description': keyword && descText.includes(keyword),
                    'keyword-url': keyword && slugText.includes(slugify(keyword)),
                    'keyword-content': keyword && text.toLowerCase().includes(keyword),
                    'content-length': words >= 600,
                    'description-length': seoDescription.value.length >= 120 && seoDescription.value.length <= 160,
                    'title-length': (seoTitle.value || title.value).length >= 35 && (seoTitle.value || title.value).length <= 65,
                };
                let score = 0;
                Object.entries(checks).forEach(([key, ok]) => {
                    const row = document.querySelector(`[data-check="${key}"]`);
                    if (row) {
                        row.dataset.state = ok ? 'good' : 'bad';
                        row.querySelector('.mark').textContent = ok ? '✓' : 'x';
                    }
                    if (ok) score += key === 'content-length' ? 16 : 14;
                });
                if (keyword) score += 2;
                score = Math.min(100, score);
                const errors = Object.values(checks).filter(ok => !ok).length;
                scoreInline.textContent = score;
                scoreSidebar.textContent = score;
                errorPill.textContent = `${errors} Errors`;
                wordCount.textContent = words;
                previewTitle.textContent = seoTitle.value || title.value || 'News ART';
                previewUrl.textContent = `/blog/${slugText || 'post-slug'}`;
                previewDescription.textContent = seoDescription.value || document.querySelector('[name="excerpt"]').value || '';
            }

            function switchTab(tab) {
                if (tab === currentTab) return;
                const editor = window.tinymce ? tinymce.get('post-content') : null;
                const editorContainer = editor?.getContainer();
                if (tab === 'code') {
                    syncFromVisual();
                    htmlEditor.style.display = 'block';
                    content.style.display = 'none';
                    if (editorContainer) editorContainer.style.display = 'none';
                } else {
                    syncFromCode();
                    htmlEditor.style.display = 'none';
                    content.style.display = 'none';
                    if (editorContainer) editorContainer.style.display = '';
                }
                currentTab = tab;
                document.querySelectorAll('[data-editor-tab]').forEach(btn => btn.classList.toggle('active', btn.dataset.editorTab === tab));
            }

            function insertHtml(html) {
                if (currentTab === 'code') {
                    const start = htmlEditor.selectionStart || 0;
                    htmlEditor.value = htmlEditor.value.slice(0, start) + html + htmlEditor.value.slice(start);
                    syncFromCode();
                } else if (window.tinymce && tinymce.get('post-content')) {
                    tinymce.get('post-content').insertContent(html);
                    syncFromVisual();
                }
                refreshSeo();
            }

            async function autosave() {
                if (!postId) return;
                syncFromVisual();
                const response = await fetch(routes.autosave, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                    body: JSON.stringify({
                        post_id: postId,
                        title: title.value,
                        slug: slug.value,
                        excerpt: document.querySelector('[name="excerpt"]').value,
                        content: content.value,
                        seo_title: seoTitle.value,
                        seo_description: seoDescription.value,
                        focus_keyword: focus.value
                    })
                }).catch(() => {});
                if (response && response.ok) {
                    const data = await response.json().catch(() => ({}));
                    if (data.post_id) postId = data.post_id;
                }
            }

            function openMedia(mode) {
                mediaMode = mode || 'insert';
                selectedMedia = null;
                updateMediaAction();
                activateMediaTab('upload');
                document.getElementById('media-modal').classList.add('is-open');
            }

            function closeMedia() {
                document.getElementById('media-modal').classList.remove('is-open');
            }

            function selectMedia(tile) {
                document.querySelectorAll('.media-tile').forEach(item => item.classList.remove('is-selected'));
                tile.classList.add('is-selected');
                selectedMedia = {
                    url: tile.dataset.url,
                    title: tile.dataset.title || '',
                    alt: tile.dataset.alt || '',
                    caption: tile.dataset.caption || '',
                    image: tile.dataset.image === '1'
                };
                updateMediaAction();
            }

            function updateMediaAction() {
                const details = document.getElementById('selected-media-details');
                const button = document.getElementById('media-use');
                button.disabled = !selectedMedia;
                button.textContent = mediaMode === 'featured' ? 'Set Featured Image' : 'Insert into editor';

                if (!selectedMedia) {
                    details.textContent = 'Select or upload an image.';
                    return;
                }

                details.innerHTML = `<img src="${selectedMedia.url}" alt=""><span><strong>${selectedMedia.title || 'Image'}</strong><br>${selectedMedia.url}</span>`;
            }

            function activateMediaTab(activeTab) {
                document.querySelectorAll('[data-media-tab]').forEach(tab => tab.classList.toggle('is-active', tab.dataset.mediaTab === activeTab));
                document.querySelectorAll('[data-media-panel]').forEach(panel => {
                    panel.hidden = panel.dataset.mediaPanel !== activeTab;
                });
                if (activeTab === 'library') {
                    loadMediaLibrary();
                }
            }

            function addMediaTile(media) {
                const library = document.getElementById('media-library');
                const help = library.querySelector('.wp-help');
                if (help) help.remove();
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'media-tile';
                button.dataset.url = media.url;
                button.dataset.title = media.title || '';
                button.dataset.alt = media.alt_text || '';
                button.dataset.caption = media.caption || '';
                button.dataset.image = '1';
                button.innerHTML = `<img src="${media.url}" alt="${media.alt_text || ''}"><small>${media.title || media.name || 'Image'}</small>`;
                button.addEventListener('click', () => selectMedia(button));
                library.prepend(button);
                selectMedia(button);
                activateMediaTab('library');
            }

            async function loadMediaLibrary() {
                if (mediaLibraryLoaded || mediaLibraryLoading) return;
                const library = document.getElementById('media-library');
                mediaLibraryLoading = true;
                library.innerHTML = '<p class="wp-help">Loading media...</p>';

                try {
                    const response = await fetch(routes.mediaIndex, {headers: {'Accept': 'application/json'}});
                    if (!response.ok) throw new Error('media-index-failed');
                    const data = await response.json();
                    library.innerHTML = '';
                    (data.items || []).forEach(item => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'media-tile';
                        button.dataset.url = item.url;
                        button.dataset.title = item.title || item.name || '';
                        button.dataset.alt = item.alt_text || '';
                        button.dataset.caption = item.caption || '';
                        button.dataset.image = '1';
                        button.innerHTML = `<img src="${item.url}" alt="${item.alt_text || ''}"><small>${item.title || item.name || 'Image'}</small>`;
                        button.addEventListener('click', () => selectMedia(button));
                        library.appendChild(button);
                    });
                    if (!library.children.length) {
                        library.innerHTML = '<p class="wp-help">No images are available in the media library yet.</p>';
                    }
                    mediaLibraryLoaded = true;
                } catch (error) {
                    library.innerHTML = '<p class="wp-help">Media library could not be loaded. Upload a new image or try again.</p>';
                } finally {
                    mediaLibraryLoading = false;
                }
            }

            async function uploadMedia() {
                const file = document.getElementById('media-file').files[0];
                if (!file) return;
                const status = document.getElementById('media-upload-status');
                status.textContent = 'Uploading...';
                const form = new FormData();
                form.append('image', file);
                form.append('title', file.name.replace(/\.[^.]+$/, ''));
                const response = await fetch(routes.mediaStore, {method:'POST', headers:{'X-CSRF-TOKEN':csrf, 'Accept':'application/json'}, body:form});
                if (response.ok) {
                    const data = await response.json();
                    addMediaTile(data.media);
                    mediaLibraryLoaded = false;
                    status.textContent = 'Uploaded. Use the image or choose another one.';
                } else {
                    let message = 'Upload failed. Please choose a valid image up to 4 MB.';
                    try {
                        const data = await response.json();
                        message = data.message || Object.values(data.errors || {})[0]?.[0] || message;
                    } catch (error) {
                        //
                    }
                    status.textContent = message;
                }
            }

            function setFeatured() {
                if (!selectedMedia) return;
                document.getElementById('featured-image-id').value = '';
                document.getElementById('featured-image-url').value = selectedMedia.url;
                document.getElementById('featured-image-alt').value = selectedMedia.alt;
                document.getElementById('featured-preview').innerHTML = selectedMedia.image ? `<img src="${selectedMedia.url}" alt="" style="max-width:100%;display:block;">` : `<a href="${selectedMedia.url}">${selectedMedia.title || selectedMedia.url}</a>`;
                closeMedia();
            }

            function useSelectedMedia() {
                if (!selectedMedia) return;
                if (mediaMode === 'featured') {
                    setFeatured();
                    return;
                }

                insertHtml(`<figure><img src="${selectedMedia.url}" alt="${selectedMedia.alt || ''}">${selectedMedia.caption ? `<figcaption>${selectedMedia.caption}</figcaption>` : ''}</figure>`);
                closeMedia();
            }

            async function quickCategory() {
                const input = document.getElementById('new-category-name');
                if (!input.value.trim()) return;
                const response = await fetch(routes.categoryQuick, {
                    method:'POST',
                    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
                    body:JSON.stringify({name: input.value})
                });
                if (response.ok) {
                    const data = await response.json();
                    const label = document.createElement('label');
                    label.innerHTML = `<input type="radio" name="category_id" value="${data.category.id}" checked> ${data.category.name}`;
                    document.getElementById('category-list').appendChild(label);
                    input.value = '';
                }
            }

            function refreshVisibility() {
                passwordRow.style.display = visibility.value === 'password' ? 'block' : 'none';
            }

            function focusFirstError() {
                const target = document.querySelector('.wp-field-error') || document.querySelector('.wp-error-text');
                if (!target) return;
                const focusable = target.matches('input, textarea, select, button, a') ? target : target.querySelector('input, textarea, select, button, a');
                target.scrollIntoView({behavior: 'smooth', block: 'center'});
                setTimeout(() => {
                    if (target.id === 'field-content' && window.tinymce && tinymce.get('post-content')) {
                        tinymce.get('post-content').focus();
                    } else if (focusable) {
                        focusable.focus({preventScroll: true});
                    } else if (target.focus) {
                        target.focus({preventScroll: true});
                    }
                }, 350);
            }

            document.querySelectorAll('.wp-box-title').forEach(title => title.addEventListener('click', () => title.closest('.wp-box').classList.toggle('is-collapsed')));
            document.querySelectorAll('[data-error-link]').forEach(link => link.addEventListener('click', event => {
                event.preventDefault();
                const href = link.getAttribute('href');
                const fieldName = link.dataset.errorLink;
                let target = href ? document.querySelector(href) : null;
                if (!target && fieldName) {
                    target = document.querySelector(`[name="${fieldName}"]`) || document.getElementById(`field-${fieldName.replace(/[._]/g, '-')}`);
                }
                if (target) {
                    target.scrollIntoView({behavior:'smooth', block:'center'});
                    const field = target.matches('input, textarea, select') ? target : target.querySelector('input, textarea, select');
                    if (field) setTimeout(() => field.focus({preventScroll:true}), 250);
                }
            }));
            document.querySelectorAll('[data-editor-tab]').forEach(btn => btn.addEventListener('click', () => switchTab(btn.dataset.editorTab)));
            document.querySelectorAll('[data-media-open]').forEach(btn => btn.addEventListener('click', () => openMedia(btn.dataset.mediaOpen)));
            document.querySelectorAll('.media-tile').forEach(tile => tile.addEventListener('click', () => selectMedia(tile)));
            document.querySelectorAll('[data-media-tab]').forEach(tab => tab.addEventListener('click', () => activateMediaTab(tab.dataset.mediaTab)));
            document.querySelectorAll('[data-quicktag]').forEach(btn => btn.addEventListener('click', () => {
                const tag = btn.dataset.quicktag;
                const map = {b:['<strong>','</strong>'], i:['<em>','</em>'], blockquote:['<blockquote>','</blockquote>'], del:['<del>','</del>'], ins:['<ins>','</ins>'], ul:['<ul><li>','</li></ul>'], ol:['<ol><li>','</li></ol>'], li:['<li>','</li>'], code:['<code>','</code>'], img:['<img src="" alt="">',''], link:['<a href="">','</a>']};
                const pair = map[tag] || ['',''];
                insertHtml(pair[0] + pair[1]);
            }));
            document.querySelectorAll('input[name="template"]').forEach(input => input.addEventListener('change', () => document.getElementById('post-layout').value = input.value));
            document.getElementById('media-close').addEventListener('click', closeMedia);
            document.getElementById('media-select-file').addEventListener('click', () => document.getElementById('media-file').click());
            document.getElementById('media-file').addEventListener('change', uploadMedia);
            document.getElementById('media-use').addEventListener('click', useSelectedMedia);
            document.getElementById('add-category').addEventListener('click', quickCategory);
            document.getElementById('generate-slug').addEventListener('click', generateSlug);
            visibility.addEventListener('change', refreshVisibility);
            title.addEventListener('blur', () => { if (!slug.value.trim()) generateSlug(); });
            [title, slug, focus, seoTitle, seoDescription, tags, htmlEditor, document.querySelector('[name="excerpt"]')].filter(Boolean).forEach(el => el.addEventListener('input', refreshSeo));
            document.getElementById('post-form').addEventListener('submit', () => currentTab === 'code' ? syncFromCode() : syncFromVisual());

            if (window.tinymce) {
                tinymce.init({
                    selector: '#post-content',
                    height: 430,
                    menubar: false,
                    branding: false,
                    plugins: 'lists link image table code codesample fullscreen wordcount media autoresize',
                    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist blockquote | link image table codesample | code fullscreen',
                    block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6',
                    paste_as_text: false,
                    valid_elements: '*[*]',
                    extended_valid_elements: 'iframe[src|width|height|allowfullscreen|frameborder],script[src|type]',
                    setup: editor => {
                        editor.on('change input keyup undo redo', () => {
                            syncFromVisual();
                            refreshSeo();
                        });
                    },
                    images_upload_handler: (blobInfo) => new Promise((resolve, reject) => {
                        const form = new FormData();
                        form.append('image', blobInfo.blob(), blobInfo.filename());
                        form.append('title', blobInfo.filename().replace(/\.[^.]+$/, ''));
                        fetch(routes.mediaStore, {method:'POST', headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'}, body:form})
                            .then(response => response.ok ? response.json() : Promise.reject())
                            .then(data => { addMediaTile(data.media); resolve(data.media.url); })
                            .catch(() => reject('Upload failed'));
                    }),
                    init_instance_callback: () => {
                        syncFromVisual();
                        refreshSeo();
                    }
                });
            }

            refreshVisibility();
            refreshSeo();
            focusFirstError();
            setInterval(autosave, 60000);
        })();
    </script>
</x-app-layout>
