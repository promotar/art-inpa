@php
    $itemType = old('type', $item->type ?? 'route');
    $isActive = old('is_active', $item->is_active ?? true);
    $style = is_array($item?->metadata ?? null) ? ($item->metadata['style'] ?? []) : [];
    $parentChoices = $parentChoices ?? collect();
@endphp

<div class="admin-menu-form-grid admin-menu-form-grid-two">
    <label class="admin-field">
        <span class="admin-field-label">Title</span>
        <input name="title" value="{{ old('title', $item->title ?? '') }}" required class="admin-input">
    </label>

    <label class="admin-field">
        <span class="admin-field-label">Label</span>
        <input name="label" value="{{ old('label', $item->label ?? '') }}" class="admin-input">
    </label>
</div>

<div class="admin-menu-form-grid admin-menu-form-grid-three">
    <label class="admin-field">
        <span class="admin-field-label">Parent item</span>
        <select name="parent_id" class="admin-input">
            <option value="">Main item</option>
            @foreach ($parentChoices as $choice)
                @continue($item && (int) $choice['item']->id === (int) $item->id)
                <option value="{{ $choice['item']->id }}" @selected((int) old('parent_id', $item->parent_id ?? 0) === (int) $choice['item']->id)>
                    {{ str_repeat('— ', (int) $choice['depth']) }}{{ $choice['item']->title }}
                </option>
            @endforeach
        </select>
        <span class="admin-field-hint">Use Main item for primary links, or choose a parent to make this a submenu item.</span>
    </label>

    <label class="admin-field">
        <span class="admin-field-label">Required Permission</span>
        <select name="permission" class="admin-input">
            <option value="">No permission</option>
            @foreach ($permissions as $permission)
                <option value="{{ $permission }}" @selected(old('permission', $item->permission ?? '') === $permission)>{{ $permission }}</option>
            @endforeach
        </select>
    </label>

    <label class="admin-field admin-menu-check-field">
        <input type="hidden" name="is_active" value="0">
        <span class="admin-menu-check-option">
            <input type="checkbox" name="is_active" value="1" @checked((bool) $isActive)>
            <span>Active</span>
        </span>
    </label>
</div>

@if (($activeLocation ?? '') === 'frontend')
    <section class="admin-menu-style-panel">
        <div class="admin-menu-panel-heading">
            <div>
                <h4 class="admin-menu-panel-title">Frontend Style</h4>
                <p class="admin-menu-panel-description">These values apply to this item in the public frontend navigation.</p>
            </div>
        </div>

        <div class="admin-menu-form-grid admin-menu-form-grid-two">
            <label class="admin-field">
                <span class="admin-field-label">CSS Classes</span>
                <input
                    name="css_class"
                    value="{{ old('css_class', $style['css_class'] ?? '') }}"
                    placeholder="rounded-md px-3 py-2"
                    class="admin-input"
                >
            </label>

            <label class="admin-field">
                <span class="admin-field-label">Font Weight</span>
                <select name="font_weight" class="admin-input">
                    <option value="">Default</option>
                    @foreach (['normal' => 'Normal', 'medium' => 'Medium', 'semibold' => 'Semibold', 'bold' => 'Bold'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('font_weight', $style['font_weight'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="admin-menu-color-grid">
            <label class="admin-field">
                <span class="admin-field-label">Text</span>
                <input type="color" name="text_color" value="{{ old('text_color', $style['text_color'] ?? '#334155') }}" class="admin-color-input">
            </label>

            <label class="admin-field">
                <span class="admin-field-label">Background</span>
                <input type="color" name="background_color" value="{{ old('background_color', $style['background_color'] ?? '#ffffff') }}" class="admin-color-input">
            </label>

            <label class="admin-field">
                <span class="admin-field-label">Hover Text</span>
                <input type="color" name="hover_text_color" value="{{ old('hover_text_color', $style['hover_text_color'] ?? '#0f172a') }}" class="admin-color-input">
            </label>

            <label class="admin-field">
                <span class="admin-field-label">Hover Background</span>
                <input type="color" name="hover_background_color" value="{{ old('hover_background_color', $style['hover_background_color'] ?? '#f8fafc') }}" class="admin-color-input">
            </label>
        </div>

        <div class="admin-menu-form-grid admin-menu-form-grid-two">
            <label class="admin-field">
                <span class="admin-field-label">Border Radius</span>
                <input name="border_radius" value="{{ old('border_radius', $style['border_radius'] ?? '') }}" placeholder="6px" class="admin-input">
            </label>

            <label class="admin-field">
                <span class="admin-field-label">Padding</span>
                <input name="padding" value="{{ old('padding', $style['padding'] ?? '') }}" placeholder="8px 12px" class="admin-input">
            </label>
        </div>
    </section>
@endif

<div x-data="{ itemType: @js($itemType) }" class="admin-menu-form-stack">
    <div class="admin-menu-form-grid admin-menu-form-grid-four">
        <label class="admin-field">
            <span class="admin-field-label">Type</span>
            <select name="type" x-model="itemType" class="admin-input">
                <option value="route" @selected($itemType === 'route')>Route</option>
                <option value="link" @selected($itemType === 'link')>Link</option>
                <option value="header" @selected($itemType === 'header')>Header</option>
            </select>
        </label>

        <label class="admin-field">
            <span class="admin-field-label">Icon</span>
            <input name="icon" value="{{ old('icon', $item->icon ?? '') }}" maxlength="24" class="admin-input">
        </label>

        <label class="admin-field">
            <span class="admin-field-label">Sort</span>
            <input name="sort_order" type="number" value="{{ old('sort_order', $item->sort_order ?? 0) }}" class="admin-input">
        </label>

        <label class="admin-field">
            <span class="admin-field-label">Target</span>
            <select name="target" class="admin-input">
                <option value="_self" @selected(old('target', $item->target ?? '_self') === '_self')>Same tab</option>
                <option value="_blank" @selected(old('target', $item->target ?? '_self') === '_blank')>New tab</option>
            </select>
        </label>
    </div>

    <div class="admin-menu-form-grid admin-menu-form-grid-two">
        <label class="admin-field" x-show="itemType === 'route'">
            <span class="admin-field-label">Route Name</span>
            <select name="route_name" class="admin-input">
                <option value="">No route</option>
                @foreach ($routeNames as $routeName)
                    <option value="{{ $routeName }}" @selected(old('route_name', $item->route_name ?? '') === $routeName)>{{ $routeName }}</option>
                @endforeach
            </select>
        </label>

        <label class="admin-field" x-show="itemType === 'link'">
            <span class="admin-field-label">URL</span>
            <input name="url" value="{{ old('url', $item->url ?? '') }}" placeholder="/custom-path or https://example.com" class="admin-input">
        </label>

        <div x-show="itemType === 'header'" class="admin-menu-note admin-menu-note-warning">
            Header items are labels only and do not render as links.
        </div>
    </div>
</div>
