(function () {
    const runtimeKey = '__ArtInpaSchemaFirstPageBuilderRuntime';
    const previousRuntime = window[runtimeKey];

    if (previousRuntime) {
        try {
            (previousRuntime.cleanup || []).forEach(cleanup => cleanup());
        } catch (error) {
            console.warn('Page Builder cleanup failed.', error);
        }

        try {
            previousRuntime.editor?.destroy?.();
        } catch (error) {
            console.warn('Previous Page Builder instance could not be destroyed.', error);
        }
    }

    const runtime = {
        bootCount: (previousRuntime?.bootCount || 0) + 1,
        cleanup: [],
        editor: null,
        startedAt: Date.now(),
    };
    window[runtimeKey] = runtime;

    const addRuntimeListener = (target, type, listener, options) => {
        target.addEventListener(type, listener, options);
        runtime.cleanup.push(() => target.removeEventListener(type, listener, options));
    };

    const config = window.PageBuilderConfig || {};
    const dynamicSources = config.dynamicSources || {};
    const widgets = Array.isArray(config.widgets) ? config.widgets : [];
    const blocks = Array.isArray(config.blocks) ? config.blocks : [];
    const elementRegistry = config.elementRegistry || {};
    const schemaFirstWidgetIds = new Set(widgets.filter(widget => widget && widget.schema_first).map(widget => widget.id || widget.type));
    const defaultMenuKey = dynamicSources.defaultMenuKey || 'platform.frontend';
    const menuPreviewItems = dynamicSources.menuPreviewItems || {};
    const siteLogo = dynamicSources.siteLogo || '';
    const siteTitle = dynamicSources.siteTitle || 'Site';
    const currentPage = dynamicSources.currentPage || {};

    const form = document.getElementById('page-builder-form');
    const htmlInput = document.getElementById('page_html');
    const cssInput = document.getElementById('page_css');
    const projectInput = document.getElementById('page_builder_json');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const saveUrl = form?.dataset.builderSaveUrl || config.builderSaveUrl || '';
    const autosaveUrl = form?.dataset.builderAutosaveUrl || config.autosaveUrl || '';
    const revisionsUrl = form?.dataset.builderRevisionsUrl || config.revisionsUrl || '';
    const mediaUrl = form?.dataset.builderMediaUrl || config.mediaUrl || '';
    const editorComponentPreviewUrl = config.editorComponentPreviewUrl || '';
    const editorCanvasCss = typeof config.editorCanvasCss === 'string' ? config.editorCanvasCss : '';
    const editorCanvasStyleUrls = Array.isArray(config.editorCanvasStyleUrls) ? config.editorCanvasStyleUrls : [];
    const editorPreviewCssStart = '/* __Z4RANK_EDITOR_PREVIEW_CSS_START__ */';
    const editorPreviewCssEnd = '/* __Z4RANK_EDITOR_PREVIEW_CSS_END__ */';
    const saveStatus = document.querySelector('[data-builder-save-status]');
    const pageSettingsToggle = document.querySelector('[data-page-settings-toggle]');
    const pageSettingsDrawer = document.querySelector('[data-page-settings-drawer]');
    const pageSettingsClose = document.querySelector('[data-page-settings-close]');
    const publishButton = document.querySelector('[data-builder-publish]');
    let dirty = false;
    let saving = false;
    let previewRendering = false;
    let autosaveTimer = null;
    let mediaItemsCache = null;

    const schemaEditorCss = [
        '[data-pb-schema-first="true"] { min-height: 24px; }',
        '[data-pb-widget="container"][data-pb-schema-first="true"] { outline: 1px dashed rgba(37, 99, 235, .28); outline-offset: -1px; }',
        '[data-pb-widget="container"][data-pb-schema-first="true"]:empty::before { content: "Drop widgets here"; color: #64748b; font: 12px system-ui; }',
        '[data-pb-placeholder="true"] { pointer-events: none; color: #64748b; font: 12px system-ui; }',
        'img { max-width: 100%; }',
    ].join('\n');

    const editorPreviewCssBlock = () => {
        const css = [editorCanvasCss, schemaEditorCss].filter(Boolean).join('\n\n');

        return css ? `${editorPreviewCssStart}\n${css}\n${editorPreviewCssEnd}` : '';
    };

    const stripEditorPreviewCss = css => String(css || '')
        .replace(new RegExp(`${editorPreviewCssStart.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}[\\s\\S]*?${editorPreviewCssEnd.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`, 'g'), '')
        .trim();
    const stripSchemaFirstBaseCss = css => String(css || '')
        .replace(/\/\* __SCHEMA_FIRST_BASE_CSS__ \*\/[\s\S]*$/g, '')
        .trim();

    const openPageSettings = () => {
        if (pageSettingsDrawer) {
            pageSettingsDrawer.hidden = false;
        }
    };

    const closePageSettings = () => {
        if (pageSettingsDrawer) {
            pageSettingsDrawer.hidden = true;
        }
    };

    if (pageSettingsToggle) {
        pageSettingsToggle.addEventListener('click', () => {
            if (!pageSettingsDrawer) {
                return;
            }

            pageSettingsDrawer.hidden ? openPageSettings() : closePageSettings();
        });
    }

    if (pageSettingsClose) {
        pageSettingsClose.addEventListener('click', closePageSettings);
    }

    addRuntimeListener(document, 'keydown', event => {
        if (event.key === 'Escape') {
            closePageSettings();
        }
    });

    const setSaveStatus = (message, tone = 'muted') => {
        if (!saveStatus) {
            return;
        }

        saveStatus.hidden = !message;
        saveStatus.textContent = message || '';
        saveStatus.classList.toggle('page-builder-alert--success', tone === 'success');
        saveStatus.classList.toggle('page-builder-alert--danger', tone === 'danger');
        saveStatus.classList.toggle('page-builder-alert--muted', tone === 'muted');
    };

    const escapeHtml = value => String(value || '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const simpleConfig = config.simpleEditor || {};

    if (simpleConfig.enabled) {
        const state = simpleConfig.state || {};
        const schema = state.schema || {};
        const sections = Array.isArray(schema.sections) ? schema.sections : [];
        let editableData = state.editable_data || {};
        let sectionVisibility = state.section_visibility || {};
        let sectionOrder = Array.isArray(state.section_order) && state.section_order.length
            ? state.section_order.slice()
            : sections.map(section => section.key);
        let activeSectionKey = sectionOrder[0] || (sections[0] ? sections[0].key : '');
        let simpleMediaItems = null;

        const sectionList = document.querySelector('[data-simple-section-list]');
        const fieldsHost = document.querySelector('[data-simple-fields]');
        const activeSectionLabel = document.querySelector('[data-simple-active-section]');
        const previewHost = document.querySelector('[data-simple-preview]');
        const simpleSaveUrl = form?.dataset.simpleTemplateSaveUrl || config.simpleTemplateSaveUrl || '';

        const sectionByKey = key => sections.find(section => section.key === key);
        const orderedSections = () => sectionOrder.map(sectionByKey).filter(Boolean);

        const getSectionData = sectionKey => {
            editableData[sectionKey] = editableData[sectionKey] || {};
            return editableData[sectionKey];
        };

        const fetchSimpleMediaItems = async () => {
            if (simpleMediaItems) {
                return simpleMediaItems;
            }

            if (!mediaUrl) {
                return [];
            }

            const response = await fetch(mediaUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Unable to load media library.');
            }

            const payload = await response.json();
            simpleMediaItems = Array.isArray(payload.items) ? payload.items : [];

            return simpleMediaItems;
        };

        const openSimpleMediaPicker = async callback => {
            const overlay = document.createElement('div');
            overlay.className = 'pb-media-picker-overlay';
            overlay.innerHTML = `
                <div class="pb-media-picker" role="dialog" aria-modal="true">
                    <header>
                        <strong>Choose Image</strong>
                        <button type="button" aria-label="Close media picker">×</button>
                    </header>
                    <div class="pb-media-picker-body"><p class="pb-media-picker-loading">Loading media...</p></div>
                </div>
            `;
            document.body.appendChild(overlay);

            const close = () => overlay.remove();
            overlay.querySelector('header button').addEventListener('click', close);
            overlay.addEventListener('click', event => {
                if (event.target === overlay) {
                    close();
                }
            });

            const body = overlay.querySelector('.pb-media-picker-body');

            try {
                const items = await fetchSimpleMediaItems();

                if (!items.length) {
                    body.innerHTML = '<p class="pb-media-picker-empty">No media files found.</p>';
                    return;
                }

                body.innerHTML = items.map((item, index) => `
                    <button type="button" class="pb-media-picker-item" data-media-index="${index}">
                        <span><img src="${escapeHtml(item.url || '')}" alt="${escapeHtml(item.alt_text || item.name || '')}"></span>
                        <strong>${escapeHtml(item.title || item.name || item.url || 'Image')}</strong>
                    </button>
                `).join('');

                body.querySelectorAll('[data-media-index]').forEach(button => {
                    button.addEventListener('click', () => {
                        const item = items[Number(button.dataset.mediaIndex)];
                        callback(item || {});
                        close();
                    });
                });
            } catch (error) {
                body.innerHTML = `<p class="pb-media-picker-empty">${escapeHtml(error.message || 'Unable to load media.')}</p>`;
            }
        };

        const applySimplePreview = () => {
            if (!previewHost) {
                return;
            }

            sections.forEach(section => {
                const sectionKey = section.key;
                previewHost.querySelectorAll(`[data-pb-section="${CSS.escape(sectionKey)}"]`).forEach(sectionNode => {
                    sectionNode.hidden = sectionVisibility[sectionKey] === false;
                    const data = getSectionData(sectionKey);

                    (section.fields || []).forEach(field => {
                        if (field.type === 'repeater') {
                            return;
                        }

                        sectionNode.querySelectorAll(`[data-pb-field="${CSS.escape(field.key)}"]`).forEach(node => {
                            const value = data[field.key] ?? field.default ?? '';

                            if (field.type === 'image') {
                                const image = typeof value === 'object' && value ? value : { src: value, alt: '' };
                                node.setAttribute('src', image.src || '');
                                node.setAttribute('alt', image.alt || '');
                                return;
                            }

                            if (field.type === 'button') {
                                const button = typeof value === 'object' && value ? value : { text: value, url: '' };
                                node.textContent = button.text || '';
                                if (node.tagName.toLowerCase() === 'a') {
                                    node.setAttribute('href', button.url || '');
                                }
                                return;
                            }

                            if (field.type === 'url' && node.tagName.toLowerCase() === 'a') {
                                node.setAttribute('href', value || '');
                                return;
                            }

                            node.textContent = value || '';
                        });
                    });
                });
            });
        };

        const markSimpleDirty = () => {
            dirty = true;
            setSaveStatus('Unsaved template changes', 'muted');
            applySimplePreview();
        };

        const renderSectionList = () => {
            if (!sectionList) {
                return;
            }

            sectionList.innerHTML = orderedSections().map(section => {
                const visible = sectionVisibility[section.key] !== false;
                return `
                    <article class="page-builder-simple-section ${section.key === activeSectionKey ? 'is-active' : ''}" data-section-key="${escapeHtml(section.key)}">
                        <button type="button" class="page-builder-simple-section-edit" data-simple-edit-section="${escapeHtml(section.key)}">
                            <strong>${escapeHtml(section.label || section.key)}</strong>
                            <span>${visible ? 'Visible' : 'Hidden'}</span>
                        </button>
                        <div class="page-builder-simple-section-actions">
                            ${section.allow_hide ? `<button type="button" data-simple-toggle-section="${escapeHtml(section.key)}">${visible ? 'Hide' : 'Show'}</button>` : ''}
                            ${section.allow_reorder ? `<button type="button" data-simple-move-section="${escapeHtml(section.key)}" data-direction="up">Up</button><button type="button" data-simple-move-section="${escapeHtml(section.key)}" data-direction="down">Down</button>` : ''}
                            <button type="button" data-simple-reset-section="${escapeHtml(section.key)}">Reset</button>
                        </div>
                    </article>
                `;
            }).join('');

            sectionList.querySelectorAll('[data-simple-edit-section]').forEach(button => {
                button.addEventListener('click', () => {
                    activeSectionKey = button.dataset.simpleEditSection || activeSectionKey;
                    renderSimpleEditor();
                });
            });

            sectionList.querySelectorAll('[data-simple-toggle-section]').forEach(button => {
                button.addEventListener('click', () => {
                    const key = button.dataset.simpleToggleSection;
                    sectionVisibility[key] = sectionVisibility[key] === false;
                    markSimpleDirty();
                    renderSimpleEditor();
                });
            });

            sectionList.querySelectorAll('[data-simple-move-section]').forEach(button => {
                button.addEventListener('click', () => {
                    const key = button.dataset.simpleMoveSection;
                    const direction = button.dataset.direction;
                    const index = sectionOrder.indexOf(key);
                    const target = direction === 'up' ? index - 1 : index + 1;

                    if (index < 0 || target < 0 || target >= sectionOrder.length) {
                        return;
                    }

                    [sectionOrder[index], sectionOrder[target]] = [sectionOrder[target], sectionOrder[index]];
                    markSimpleDirty();
                    renderSimpleEditor();
                });
            });

            sectionList.querySelectorAll('[data-simple-reset-section]').forEach(button => {
                button.addEventListener('click', () => {
                    const section = sectionByKey(button.dataset.simpleResetSection);
                    if (!section) {
                        return;
                    }

                    editableData[section.key] = {};
                    (section.fields || []).forEach(field => {
                        editableData[section.key][field.key] = structuredClone(field.default ?? '');
                    });
                    sectionVisibility[section.key] = section.visible !== false;
                    markSimpleDirty();
                    renderSimpleEditor();
                });
            });
        };

        const fieldValue = (sectionData, field) => sectionData[field.key] ?? field.default ?? '';

        const createSimpleField = (section, field) => {
            const sectionData = getSectionData(section.key);
            const wrapper = document.createElement('label');
            wrapper.className = 'page-builder-simple-field';
            wrapper.innerHTML = `<span>${escapeHtml(field.label || field.key)}</span>`;

            const updateValue = value => {
                sectionData[field.key] = value;
                markSimpleDirty();
            };

            if (field.type === 'textarea' || field.type === 'rich_text_basic') {
                const input = document.createElement('textarea');
                input.rows = 4;
                input.value = fieldValue(sectionData, field) || '';
                input.addEventListener('input', () => updateValue(input.value));
                wrapper.appendChild(input);
            } else if (field.type === 'button') {
                const current = fieldValue(sectionData, field) || {};
                const group = document.createElement('div');
                group.className = 'page-builder-simple-compound';
                group.innerHTML = `
                    <input type="text" placeholder="Button text" value="${escapeHtml(current.text || '')}">
                    <input type="url" placeholder="Button link" value="${escapeHtml(current.url || '')}">
                `;
                const inputs = group.querySelectorAll('input');
                group.addEventListener('input', () => updateValue({ text: inputs[0].value, url: inputs[1].value }));
                wrapper.appendChild(group);
            } else if (field.type === 'image') {
                const current = fieldValue(sectionData, field) || {};
                const group = document.createElement('div');
                group.className = 'page-builder-simple-media-field';
                group.innerHTML = `
                    <div class="page-builder-simple-image-preview">${current.src ? `<img src="${escapeHtml(current.src)}" alt="${escapeHtml(current.alt || '')}">` : '<span>No image selected</span>'}</div>
                    <input type="url" placeholder="Image URL" value="${escapeHtml(current.src || '')}">
                    <input type="text" placeholder="Image alt text" value="${escapeHtml(current.alt || '')}">
                    <button type="button">Choose Image</button>
                `;
                const inputs = group.querySelectorAll('input');
                const sync = () => updateValue({ src: inputs[0].value, alt: inputs[1].value });
                inputs.forEach(input => input.addEventListener('input', sync));
                group.querySelector('button').addEventListener('click', () => {
                    openSimpleMediaPicker(item => {
                        inputs[0].value = item.url || '';
                        inputs[1].value = item.alt_text || item.title || '';
                        sync();
                        renderSimpleEditor();
                    });
                });
                wrapper.appendChild(group);
            } else if (field.type === 'toggle') {
                const input = document.createElement('input');
                input.type = 'checkbox';
                input.checked = Boolean(fieldValue(sectionData, field));
                input.addEventListener('change', () => updateValue(input.checked));
                wrapper.appendChild(input);
            } else if (field.type === 'select') {
                const input = document.createElement('select');
                (field.options || []).forEach(option => {
                    const value = typeof option === 'object' ? option.value : option;
                    const label = typeof option === 'object' ? (option.label || option.value) : option;
                    input.insertAdjacentHTML('beforeend', `<option value="${escapeHtml(value)}">${escapeHtml(label)}</option>`);
                });
                input.value = fieldValue(sectionData, field) || '';
                input.addEventListener('change', () => updateValue(input.value));
                wrapper.appendChild(input);
            } else {
                const input = document.createElement('input');
                input.type = field.type === 'url' ? 'url' : 'text';
                input.value = fieldValue(sectionData, field) || '';
                input.addEventListener('input', () => updateValue(input.value));
                wrapper.appendChild(input);
            }

            if (field.help_text) {
                wrapper.insertAdjacentHTML('beforeend', `<small>${escapeHtml(field.help_text)}</small>`);
            }

            return wrapper;
        };

        const renderSimpleFields = () => {
            if (!fieldsHost) {
                return;
            }

            const section = sectionByKey(activeSectionKey);
            fieldsHost.innerHTML = '';

            if (!section) {
                fieldsHost.innerHTML = '<p class="page-builder-simple-empty">No editable section selected.</p>';
                return;
            }

            if (activeSectionLabel) {
                activeSectionLabel.textContent = section.label || section.key;
            }

            (section.fields || []).forEach(field => {
                fieldsHost.appendChild(createSimpleField(section, field));
            });

            if (!(section.fields || []).length) {
                fieldsHost.innerHTML = '<p class="page-builder-simple-empty">This section has no editable fields.</p>';
            }
        };

        const renderSimpleEditor = () => {
            renderSectionList();
            renderSimpleFields();
            applySimplePreview();
        };

        const saveSimpleTemplate = async () => {
            if (!simpleSaveUrl || saving) {
                return;
            }

            saving = true;
            setSaveStatus('Saving template content...', 'muted');

            const payload = new FormData();
            payload.append('_method', 'PATCH');
            payload.append('editable_data', JSON.stringify(editableData));
            payload.append('section_visibility', JSON.stringify(sectionVisibility));
            payload.append('section_order', JSON.stringify(sectionOrder));

            try {
                const response = await fetch(simpleSaveUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-HTTP-Method-Override': 'PATCH',
                    },
                    body: payload,
                });
                const result = await response.json();

                if (!response.ok || !result.ok) {
                    throw new Error(result.message || 'Template content could not be saved.');
                }

                dirty = false;
                setSaveStatus(result.message || 'Template content saved.', 'success');

                if (result.simple_editor && result.simple_editor.preview_html && previewHost) {
                    previewHost.innerHTML = result.simple_editor.preview_html;
                }
            } catch (error) {
                setSaveStatus(error.message || 'Template content could not be saved.', 'danger');
            } finally {
                saving = false;
            }
        };

        if (form) {
            form.addEventListener('submit', event => {
                event.preventDefault();
                saveSimpleTemplate();
            });
        }

        renderSimpleEditor();
        setSaveStatus('', 'muted');
        return;
    }

    const sourceOptions = source => {
        if (!source) {
            return [];
        }

        if (source === 'menus') {
            return dynamicSources.menus || [];
        }

        if (source === 'pages') {
            return dynamicSources.pages || [];
        }

        if (source === 'blocks') {
            return dynamicSources.blocks || [];
        }

        if (source === 'fields') {
            return dynamicSources.fields || [];
        }

        return dynamicSources[source] || [];
    };

    const traitOptions = trait => {
        if (trait.dynamicSource) {
            return sourceOptions(trait.dynamicSource);
        }

        return trait.options || [];
    };

    const schemaTraitType = type => ({
        richtext: 'textarea',
        media: 'media',
        icon: 'text',
        switch: 'checkbox',
    })[type] || type;

    const controlAttributeName = control => ({
        element_id: 'id',
        css_classes: 'class',
        anchor_id: 'id',
        text: 'data-pb-text',
        rich_text: 'data-pb-text',
        src: 'data-pb-src',
        media_library: 'data-pb-src',
        url: 'data-pb-url',
        target: 'data-pb-link-target',
        link_url: 'data-pb-link-url',
        link: 'data-pb-link',
        link_type: 'data-pb-link-type',
        link_target: 'data-pb-link-target',
        link_nofollow: 'data-pb-link-nofollow',
        lightbox_size: 'data-pb-lightbox-size',
        lightbox: 'data-pb-image-lightbox',
        semantic_tag: 'data-pb-semantic-tag',
        html_tag: 'data-pb-html-tag',
        media_id: 'data-pb-media-id',
        media_url: 'data-pb-media-url',
        image_url: 'data-pb-image-url',
        thumbnail_url: 'data-pb-thumbnail-url',
        media_width: 'data-pb-media-width',
        media_height: 'data-pb-media-height',
        mime_type: 'data-pb-mime-type',
        caption_mode: 'data-pb-caption-mode',
        loading: 'data-pb-loading',
        decoding: 'data-pb-decoding',
        css_id: 'id',
        css_class: 'class',
        icon: 'data-pb-icon',
        icon_position: 'data-pb-icon-position',
    })[control.key] || `data-pb-${control.key.replaceAll('_', '-')}`;

    const schemaControlsFor = widgetId => {
        const schema = elementRegistry[widgetId] || {};

        return Array.isArray(schema.controls) ? schema.controls : [];
    };

    const widgetTraits = widget => {
        const schemaControls = schemaControlsFor(widget.id);

        if (schemaControls.length) {
            return schemaControls
                .filter(control => ['general', 'special'].includes(control.tab) && !control.cssProperty)
                .map(control => ({
                    type: schemaTraitType(control.type),
                    name: controlAttributeName(control),
                    label: control.label,
                    options: selectOptions(control).map(option => ({ value: option.value, name: option.label })),
                }));
        }

        return (widget.traits || []).map(trait => ({
            ...trait,
            options: traitOptions(trait),
        }));
    };

    const selectOptions = control => {
        const options = {
            semantic_tag: ['div', 'section', 'article', 'aside', 'header', 'footer', 'main'].map(value => ({ value, label: value })),
            html_tag: ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div'].map(value => ({ value, label: value.toUpperCase() })),
            layout_display: ['block', 'flex', 'grid', 'inline-flex'].map(value => ({ value, label: value })),
            display: ['block', 'flex', 'grid', 'inline-flex'].map(value => ({ value, label: value })),
            direction: ['row', 'column', 'row-reverse', 'column-reverse'].map(value => ({ value, label: value })),
            flex_direction: ['row', 'column', 'row-reverse', 'column-reverse'].map(value => ({ value, label: value })),
            wrap: ['nowrap', 'wrap', 'wrap-reverse'].map(value => ({ value, label: value })),
            justify_content: ['flex-start', 'center', 'flex-end', 'space-between', 'space-around'].map(value => ({ value, label: value })),
            justify: ['flex-start', 'center', 'flex-end', 'space-between', 'space-around'].map(value => ({ value, label: value })),
            align_items: ['stretch', 'flex-start', 'center', 'flex-end'].map(value => ({ value, label: value })),
            align: ['stretch', 'flex-start', 'center', 'flex-end'].map(value => ({ value, label: value })),
            children_wrap: ['nowrap', 'wrap', 'wrap-reverse'].map(value => ({ value, label: value })),
            content_width: [
                { value: 'boxed', label: 'Boxed' },
                { value: 'full', label: 'Full width' },
            ],
            position: ['static', 'relative', 'absolute', 'fixed', 'sticky'].map(value => ({ value, label: value })),
            overflow: ['visible', 'hidden', 'auto', 'scroll'].map(value => ({ value, label: value })),
            responsive_visibility: ['all', 'hide_desktop', 'hide_tablet', 'hide_mobile'].map(value => ({ value, label: value.replaceAll('_', ' ') })),
            font_family: [
                { value: 'inherit', label: 'Default' },
                { value: 'Arial, sans-serif', label: 'Arial' },
                { value: 'Georgia, serif', label: 'Georgia' },
                { value: '"Times New Roman", serif', label: 'Times New Roman' },
                { value: '"Tahoma", sans-serif', label: 'Tahoma' },
                { value: '"Trebuchet MS", sans-serif', label: 'Trebuchet MS' },
            ],
            font_weight: ['300', '400', '500', '600', '700', '800', '900'].map(value => ({ value, label: value })),
            border_style: ['none', 'solid', 'dashed', 'dotted', 'double'].map(value => ({ value, label: value })),
            shadow: [
                { value: 'none', label: 'None' },
                { value: '0 1px 2px rgba(15, 23, 42, .12)', label: 'Soft' },
                { value: '0 10px 24px rgba(15, 23, 42, .16)', label: 'Medium' },
                { value: '0 18px 45px rgba(15, 23, 42, .24)', label: 'Strong' },
            ],
            link_type: [
                { value: 'none', label: 'None' },
                { value: 'custom', label: 'Custom URL' },
                { value: 'media_file', label: 'Media file' },
            ],
            caption_mode: [
                { value: 'none', label: 'None' },
                { value: 'attachment', label: 'Attachment caption' },
                { value: 'custom', label: 'Custom caption' },
            ],
            target: [
                { value: '_self', label: 'Same window' },
                { value: '_blank', label: 'New window' },
            ],
            link_target: [
                { value: '_self', label: 'Same window' },
                { value: '_blank', label: 'New window' },
            ],
            loading: ['lazy', 'eager'].map(value => ({ value, label: value })),
            decoding: ['auto', 'async'].map(value => ({ value, label: value })),
            object_fit: ['fill', 'contain', 'cover', 'none', 'scale-down'].map(value => ({ value, label: value })),
            object_position: ['center', 'top', 'bottom', 'left', 'right'].map(value => ({ value, label: value })),
            opacity: [
                { value: '1', label: '100%' },
                { value: '0.8', label: '80%' },
                { value: '0.75', label: '75%' },
                { value: '0.5', label: '50%' },
                { value: '0.25', label: '25%' },
            ],
            alignment: ['left', 'center', 'right'].map(value => ({ value, label: value })),
            caption_alignment: ['left', 'center', 'right'].map(value => ({ value, label: value })),
            text_transform: ['none', 'uppercase', 'lowercase', 'capitalize'].map(value => ({ value, label: value })),
            text_decoration: ['none', 'underline', 'line-through', 'overline'].map(value => ({ value, label: value })),
            lightbox_size: [
                { value: 'contain', label: 'Fit screen' },
                { value: 'full', label: 'Full width' },
            ],
            text_tag: ['p', 'div', 'span'].map(value => ({ value, label: value })),
            width_mode: [
                { value: 'auto', label: 'Auto' },
                { value: 'full', label: 'Full' },
                { value: 'custom', label: 'Custom' },
            ],
            line_style: ['solid', 'dashed', 'dotted'].map(value => ({ value, label: value })),
            source_type: [
                { value: 'embed', label: 'Embed' },
                { value: 'self_hosted', label: 'Self hosted' },
            ],
        };

        return options[control.key] || [];
    };

    const normalizedMenuKey = key => menuPreviewItems[key] ? key : defaultMenuKey;

    const attributeText = (attrs, excludedNames = []) => {
        const excluded = new Set(excludedNames.map(name => String(name).toLowerCase()));

        return Object.entries(attrs || {}).map(([name, value]) => {
            const normalizedName = String(name || '').toLowerCase();

            if (!normalizedName || excluded.has(normalizedName) || normalizedName.startsWith('on')) {
                return '';
            }

            const normalizedValue = Array.isArray(value) ? value.join(' ') : String(value ?? '').trim();

            return normalizedValue === '' ? '' : ` ${normalizedName}="${escapeHtml(normalizedValue)}"`;
        }).join('');
    };

    const logoHtml = (imageAttrs = {}) => {
        if (!siteLogo) {
            return escapeHtml(siteTitle);
        }

        const attrs = { ...imageAttrs };

        if (!attrs.style) {
            attrs.style = 'max-height:64px;width:auto;';
        }

        return `<img src="${escapeHtml(siteLogo)}" alt="${escapeHtml(siteTitle)}"${attributeText(attrs, ['src', 'alt'])}>`;
    };

    const linkAttributes = component => (component.find('a') || []).map(link => {
        const attrs = link.getAttributes ? link.getAttributes() : {};

        return {
            id: attrs.id || (link.getId ? link.getId() : ''),
            class: attrs.class || '',
            style: attrs.style || '',
        };
    });

    const linkAttributeText = attrs => {
        const id = attrs && attrs.id ? ` id="${escapeHtml(attrs.id)}"` : '';
        const cssClass = attrs && attrs.class ? ` class="${escapeHtml(attrs.class)}"` : '';
        const style = attrs && attrs.style ? ` style="${escapeHtml(attrs.style)}"` : '';

        return `${id}${cssClass}${style}`;
    };

    const menuLinksHtml = (key, templates) => {
        const items = menuPreviewItems[normalizedMenuKey(key)] || [];
        const linkTemplates = Array.isArray(templates) ? templates : [];

        if (!items.length) {
            return `<a href="#"${linkAttributeText(linkTemplates[0] || {})}>No menu items</a>`;
        }

        return items.map((item, index) => {
            const fallback = linkTemplates[index] || linkTemplates[0] || {};
            const attrs = { ...fallback };

            if (index > 0 && !linkTemplates[index]) {
                delete attrs.id;
            }

            const target = item.target === '_blank' ? ' target="_blank" rel="noopener"' : '';
            const href = item.href || '#';

            return `<a href="${escapeHtml(href)}"${target}${linkAttributeText(attrs)}>${escapeHtml(item.label)}</a>`;
        }).join('');
    };

    const styleSectors = [
        {
            name: 'Layout',
            open: true,
            properties: ['display', 'position', 'top', 'right', 'bottom', 'left', 'width', 'height', 'min-height', 'max-width', 'overflow', 'z-index'],
        },
        {
            name: 'Spacing',
            open: true,
            properties: ['margin', 'padding'],
        },
        {
            name: 'Typography',
            open: true,
            properties: ['font-family', 'font-size', 'font-weight', 'line-height', 'letter-spacing', 'text-align', 'text-decoration', 'color'],
        },
        {
            name: 'Background',
            open: false,
            properties: ['background-color', 'background-image', 'background-size', 'background-position', 'background-repeat'],
        },
        {
            name: 'Border',
            open: false,
            properties: ['border', 'border-radius', 'box-shadow'],
        },
        {
            name: 'Flex and Grid',
            open: false,
            properties: ['flex-direction', 'justify-content', 'align-items', 'gap', 'grid-template-columns'],
        },
        {
            name: 'Responsive',
            open: false,
            properties: ['order', 'flex-wrap'],
        },
    ];

    const editor = grapesjs.init({
        container: '#gjs',
        height: '100%',
        storageManager: false,
        fromElement: false,
        components: '',
        style: '',
        canvas: {
            styles: editorCanvasStyleUrls,
            scripts: [],
        },
        deviceManager: {
            devices: [
                { name: 'Desktop', width: '1280px' },
                { name: 'Tablet', width: '768px', widthMedia: '992px' },
                { name: 'Mobile portrait', width: '375px', widthMedia: '575px' },
            ],
        },
        styleManager: {
            sectors: styleSectors,
        },
        selectorManager: {
            componentFirst: true,
        },
        blockManager: {
            appendTo: null,
            blocks,
        },
    });
    runtime.editor = editor;
    window.__ArtInpaPageBuilderPerf = {
        bootCount: runtime.bootCount,
        editor,
        startedAt: runtime.startedAt,
    };

    const injectEditorCanvasCss = () => {
        if (!editorCanvasCss) {
            return;
        }

        const frameElement = editor.Canvas.getFrameEl ? editor.Canvas.getFrameEl() : null;
        const canvasDocument = editor.Canvas.getDocument()
            || (frameElement ? frameElement.contentDocument : null);

        if (!canvasDocument || !canvasDocument.head || canvasDocument.head.querySelector('style[data-editor-canvas-css]')) {
            return;
        }

        const style = canvasDocument.createElement('style');
        style.setAttribute('data-editor-canvas-css', 'true');
        style.textContent = `${editorCanvasCss}\n\nbody { min-height: 100%; }`;
        canvasDocument.head.appendChild(style);
    };

    const scheduleEditorCanvasCssInjection = () => {
        injectEditorCanvasCss();
        window.setTimeout(injectEditorCanvasCss, 250);
        window.setTimeout(injectEditorCanvasCss, 1000);
        window.setTimeout(injectEditorCanvasCss, 2500);
    };

    const inspectorHost = document.querySelector('[data-builder-inspector-host]') || document.querySelector('[data-builder-right-panel-host]');
    const inspectorTitle = document.querySelector('[data-builder-sidebar-title]');
    let sidebarMode = 'blocks';
    let sidebarTabs = null;
    let settingsPanel = null;

    const mountBuilderPanels = () => {
        if (!inspectorHost) {
            return;
        }

        const views = document.querySelector('.gjs-pn-views');
        const viewsContainer = document.querySelector('.gjs-pn-views-container');

        if (views && !inspectorHost.contains(views)) {
            views.classList.add('pb-sidebar-view', 'pb-sidebar-buttons');
            inspectorHost.appendChild(views);
        }

        if (viewsContainer && !inspectorHost.contains(viewsContainer)) {
            viewsContainer.classList.add('pb-sidebar-view', 'pb-sidebar-content');
            inspectorHost.appendChild(viewsContainer);
        }
    };

    const settingsState = {
        tab: 'general',
        component: null,
    };
    let suppressUpdateDirty = 0;

    settingsPanel = document.createElement('div');
    settingsPanel.className = 'pb-schema-settings-panel';
    settingsPanel.hidden = true;
    settingsPanel.innerHTML = '<div class="pb-schema-empty">Select an element to edit its settings.</div>';

    const attachSettingsPanel = () => {
        mountBuilderPanels();
        const viewsContainer = document.querySelector('.gjs-pn-views-container');

        if (inspectorHost && !inspectorHost.contains(settingsPanel)) {
            inspectorHost.appendChild(settingsPanel);
        }
    };

    const setInspectorTitle = text => {
        if (inspectorTitle) {
            inspectorTitle.textContent = text;
        }
    };

    const showElementBrowser = () => {
        attachSettingsPanel();
        const views = document.querySelector('.gjs-pn-views');
        const viewsContainer = document.querySelector('.gjs-pn-views-container');

        if (settingsPanel) {
            settingsPanel.hidden = true;
        }

        if (views) {
            views.hidden = false;
        }

        if (viewsContainer) {
            viewsContainer.hidden = false;
        }

        setInspectorTitle(sidebarMode === 'layers' ? 'Layers' : 'Elements');
    };

    const showSchemaEditor = () => {
        attachSettingsPanel();
        const views = document.querySelector('.gjs-pn-views');
        const viewsContainer = document.querySelector('.gjs-pn-views-container');

        if (views) {
            views.hidden = true;
        }

        if (viewsContainer) {
            viewsContainer.hidden = true;
        }

        if (settingsPanel) {
            settingsPanel.hidden = false;
        }
    };

    const setSchemaPanelVisible = visible => {
        if (visible) {
            showSchemaEditor();
        } else {
            showElementBrowser();
        }
    };

    const schemaRootComponent = component => {
        let current = component;

        while (current && current.getAttributes) {
            const attrs = current.getAttributes() || {};
            const widgetId = attrs['data-pb-widget'] || '';

            if (attrs['data-pb-schema-first'] === 'true' || schemaFirstWidgetIds.has(widgetId)) {
                return current;
            }

            current = current.parent ? current.parent() : null;
        }

        return component;
    };

    const selectedWidgetId = component => {
        const root = schemaRootComponent(component);
        const attrs = root && root.getAttributes ? root.getAttributes() : {};

        return attrs['data-pb-widget'] || '';
    };

    const schemaComponentFor = component => {
        const root = schemaRootComponent(component);

        return selectedWidgetId(root) ? root : component;
    };

    const selectedWidgetIdFromSelf = component => {
        const attrs = component && component.getAttributes ? component.getAttributes() : {};

        return attrs['data-pb-widget'] || '';
    };

    const schemaForComponent = component => elementRegistry[selectedWidgetId(component)] || null;

    const targetComponent = (component, target) => {
        if (!component || !target || target === 'wrapper' || target === 'content') {
            return component;
        }

        const selectors = {
            image: 'img',
            caption: 'figcaption',
            button: 'a,button',
            line: '.pb-divider-line',
            icon: 'svg,i,span',
        };
        const selector = selectors[target] || null;

        if (!selector || !component.find) {
            return component;
        }

        const found = component.find(selector);

        return found && found.length ? found[0] : component;
    };

    const readAttribute = (component, name) => {
        const attrs = component && component.getAttributes ? component.getAttributes() : {};

        return attrs[name] || '';
    };

    const canonicalImageAction = action => {
        const value = String(action || '').trim().toLowerCase();

        if (['link', 'custom', 'custom_url'].includes(value)) {
            return 'link';
        }

        if (['lightbox', 'media_file'].includes(value)) {
            return 'lightbox';
        }

        return 'none';
    };

    const imageLinkTypeFromAction = action => {
        const value = canonicalImageAction(action);

        if (value === 'link') {
            return 'custom';
        }

        if (value === 'lightbox') {
            return 'media_file';
        }

        return 'none';
    };

    const imageActionFromLinkType = linkType => {
        if (linkType === 'custom') {
            return 'link';
        }

        if (linkType === 'media_file') {
            return 'lightbox';
        }

        return 'none';
    };

    const isComponentSchemaFirst = component => {
        const attrs = component && component.getAttributes ? component.getAttributes() : {};
        const widgetId = attrs['data-pb-widget'] || '';

        return schemaFirstWidgetIds.has(widgetId) && (attrs['data-pb-schema-first'] === 'true' || Boolean(attrs['data-pb-widget']));
    };

    const readControlValue = (component, control) => {
        component = schemaComponentFor(component);

        if (!component) {
            return control.default ?? '';
        }

        if (isComponentSchemaFirst(component)) {
            const attrValue = readAttribute(component, controlAttributeName(control));

            if (attrValue !== '') {
                return attrValue;
            }
        }

        if (control.cssProperty) {
            const target = targetComponent(component, control.target);
            const style = target && target.getStyle ? target.getStyle() : {};

            return style[control.cssProperty] || control.default || '';
        }

        if (control.key === 'link_type') {
            const target = targetComponent(component, control.target);
            const action = readAttribute(target, 'data-pb-image-action') || readAttribute(component, 'data-pb-image-action');

            return imageLinkTypeFromAction(action);
        }

        if (control.key === 'lightbox') {
            const target = targetComponent(component, control.target);

            return canonicalImageAction(readAttribute(target, 'data-pb-image-action') || readAttribute(component, 'data-pb-image-action')) === 'lightbox';
        }

        if (control.key === 'rich_text') {
            return component.components ? component.components().map(child => child.toHTML ? child.toHTML() : '').join('') : '';
        }

        if (control.key === 'text') {
            const attrValue = readAttribute(component, 'data-pb-text');

            return attrValue || (component.components ? component.components().map(child => child.toHTML ? child.toHTML() : '').join('') : '');
        }

        return readAttribute(targetComponent(component, control.target), controlAttributeName(control)) || control.default || '';
    };

    const conditionPasses = (component, control) => {
        component = schemaComponentFor(component);

        if (!control.condition) {
            return true;
        }

        const relatedControls = schemaControlsFor(selectedWidgetId(component)) || [];
        const related = Array.isArray(relatedControls)
            ? relatedControls.find(item => item.key === control.condition.key)
            : null;
        const value = related ? readControlValue(component, related) : readAttribute(component, control.condition.key);

        if (control.condition.operator === 'equals') {
            return value === control.condition.value;
        }

        return true;
    };

    const normalizeCssValue = (value, control = {}) => {
        const stringValue = String(value ?? '').trim();

        if (stringValue === '') {
            return '';
        }

        if (['line-height', 'font-weight', 'opacity'].includes(control.cssProperty) && /^-?\d+(\.\d+)?$/.test(stringValue)) {
            return stringValue;
        }

        if (/^-?\d+(\.\d+)?$/.test(stringValue)) {
            return `${stringValue}px`;
        }

        return stringValue;
    };

    const applyCssControl = (component, control, value) => {
        component = schemaComponentFor(component);
        const target = targetComponent(component, control.target);

        if (!target || !target.addStyle) {
            return;
        }

        target.addStyle({ [control.cssProperty]: normalizeCssValue(value, control) });
    };

    const runWithoutUpdateDirty = callback => {
        suppressUpdateDirty += 1;

        try {
            return callback();
        } finally {
            window.setTimeout(() => {
                suppressUpdateDirty = Math.max(0, suppressUpdateDirty - 1);
            }, 0);
        }
    };

    const controlNeedsPreviewRender = control => {
        if (!control.cssProperty) {
            return true;
        }

        return [
            'text', 'rich_text', 'html_tag', 'semantic_tag',
            'media_id', 'media_url', 'image_url', 'thumbnail_url', 'src', 'media_library',
            'alt', 'caption_mode', 'caption', 'link', 'link_type', 'link_url', 'link_target',
            'link_nofollow', 'loading', 'decoding',
        ].includes(control.key);
    };

    const persistSchemaProp = (component, control, value) => {
        component = schemaComponentFor(component);

        if (!component || !component.addAttributes) {
            return;
        }

        const attr = controlAttributeName(control);

        if (attr === 'id' || attr === 'class') {
            component.addAttributes({ [attr]: String(value ?? '') });
            return;
        }

        component.addAttributes({ [attr]: typeof value === 'boolean' ? (value ? 'true' : 'false') : String(value ?? '') });
    };

    const applyAttributeControl = (component, control, rawValue) => {
        component = schemaComponentFor(component);
        const value = typeof rawValue === 'boolean' ? rawValue : String(rawValue ?? '');
        const target = targetComponent(component, control.target);

        if (!target || !target.addAttributes) {
            return;
        }

        if (control.key === 'element_id' || control.key === 'anchor_id' || control.key === 'css_id') {
            component.addAttributes({ id: value });
            return;
        }

        if (control.key === 'css_classes' || control.key === 'css_class') {
            component.addAttributes({ class: value });
            return;
        }

        if (control.key === 'custom_attributes') {
            value.split('\n').map(line => line.trim()).filter(Boolean).forEach(line => {
                const [name, ...parts] = line.split('=');
                const key = String(name || '').trim();

                if (!key || key.toLowerCase().startsWith('on')) {
                    return;
                }

                component.addAttributes({ [key]: parts.join('=').trim().replace(/^["']|["']$/g, '') });
            });
            return;
        }

        if (control.key === 'semantic_tag') {
            component.set('tagName', value);
            component.addAttributes({ 'data-pb-semantic-tag': value });
            return;
        }

        if (control.key === 'html_tag') {
            component.set('tagName', value);
            component.addAttributes({ 'data-pb-html-tag': value });
            return;
        }

        if (control.key === 'text') {
            component.addAttributes({ 'data-pb-text': value });
            updateTextContent(component, value);
            return;
        }

        if (control.key === 'rich_text') {
            component.addAttributes({ 'data-pb-text': value });
            component.components(value);
            return;
        }

        if (['src', 'media_library', 'media_url'].includes(control.key)) {
            target.addAttributes({ src: value, 'data-pb-src': value, 'data-pb-media-url': value });
            component.addAttributes({ 'data-pb-media-url': value, 'data-pb-image-url': value });
            return;
        }

        if (control.key === 'alt') {
            target.addAttributes({ alt: value });
            return;
        }

        if (control.key === 'link_type') {
            component.addAttributes({
                'data-pb-link-type': value,
                'data-pb-image-action': imageActionFromLinkType(value),
            });
            renderSchemaPanel();
            return;
        }

        if (control.key === 'loading') {
            targetComponent(component, 'image')?.addAttributes?.({ loading: value, 'data-pb-loading': value });
            return;
        }

        if (control.key === 'decoding') {
            targetComponent(component, 'image')?.addAttributes?.({ decoding: value, 'data-pb-decoding': value });
            return;
        }

        if (control.key === 'lightbox') {
            target.addAttributes({ 'data-pb-image-action': value ? 'lightbox' : 'none' });
            return;
        }

        if (control.key === 'url') {
            target.addAttributes({ href: value, 'data-pb-url': value });
            return;
        }

        target.addAttributes({ [controlAttributeName(control)]: value });
    };

    const applyControlValue = (component, control, value) => {
        component = schemaComponentFor(component);

        runWithoutUpdateDirty(() => {
            if (isComponentSchemaFirst(component)) {
                persistSchemaProp(component, control, value);
            }

            if (control.cssProperty) {
                applyCssControl(component, control, value);
            } else {
                applyAttributeControl(component, control, value);
            }

            if (isComponentSchemaFirst(component)) {
                applySchemaFirstStyles(component);
            }

            if (controlNeedsPreviewRender(control)) {
                updateComponentPreview(component);
            }
        });
        markDirty();
    };

    const fetchMediaItems = async () => {
        if (Array.isArray(mediaItemsCache)) {
            return mediaItemsCache;
        }

        if (!mediaUrl) {
            return [];
        }

        const response = await fetch(mediaUrl, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error('Unable to load media library.');
        }

        const payload = await response.json();
        mediaItemsCache = Array.isArray(payload.items) ? payload.items : [];

        return mediaItemsCache;
    };

    const closeMediaPicker = () => {
        document.querySelector('[data-builder-media-picker]')?.remove();
    };

    const mediaField = (item, ...keys) => {
        for (const key of keys) {
            if (item && item[key] !== undefined && item[key] !== null && String(item[key]) !== '') {
                return String(item[key]);
            }
        }

        return '';
    };

    const applyMediaSelection = (component, control, item, input, altInput) => {
        const root = schemaComponentFor(component);
        const url = mediaField(item, 'url', 'media_url', 'image_url');
        const alt = mediaField(item, 'alt_text', 'alt', 'title', 'name');
        const caption = mediaField(item, 'caption');
        const attrs = {
            'data-pb-media-id': mediaField(item, 'media_id', 'id', 'path', 'url'),
            'data-pb-media-url': url,
            'data-pb-image-url': mediaField(item, 'image_url', 'url', 'media_url'),
            'data-pb-thumbnail-url': mediaField(item, 'thumbnail_url', 'url', 'media_url'),
            'data-pb-media-width': mediaField(item, 'width', 'media_width'),
            'data-pb-media-height': mediaField(item, 'height', 'media_height'),
            'data-pb-mime-type': mediaField(item, 'mime_type', 'type'),
            'data-pb-alt': alt,
        };

        if (caption) {
            attrs['data-pb-caption'] = caption;
        }

        root?.addAttributes?.(attrs);
        targetComponent(root, 'image')?.addAttributes?.({
            src: url,
            alt,
            'data-pb-src': url,
            'data-pb-media-url': url,
        });

        if (input) {
            input.value = url;
        }

        if (altInput) {
            altInput.value = alt;
        }

        applyControlValue(root, control, url);
        updateComponentPreview(root);
    };

    const openMediaPicker = async (component, control, input, altInput) => {
        closeMediaPicker();

        const overlay = document.createElement('div');
        overlay.className = 'pb-media-picker-overlay';
        overlay.dataset.builderMediaPicker = 'true';
        overlay.innerHTML = `
            <div class="pb-media-picker">
                <header>
                    <strong>Choose Image</strong>
                    <button type="button" data-media-close aria-label="Close media picker">×</button>
                </header>
                <div class="pb-media-picker-body" data-media-picker-body>
                    <div class="pb-media-picker-loading">Loading media library...</div>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);

        overlay.querySelector('[data-media-close]')?.addEventListener('click', closeMediaPicker);
        overlay.addEventListener('click', event => {
            if (event.target === overlay) {
                closeMediaPicker();
            }
        });

        const body = overlay.querySelector('[data-media-picker-body]');

        try {
            const items = await fetchMediaItems();

            if (!items.length) {
                body.innerHTML = '<div class="pb-media-picker-empty">No uploaded images found.</div>';
                return;
            }

            body.innerHTML = '';
            items.forEach(item => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'pb-media-picker-item';
                button.innerHTML = `
                    <span><img src="${escapeHtml(item.url)}" alt="${escapeHtml(item.alt_text || item.name || '')}"></span>
                    <strong>${escapeHtml(item.title || item.name || item.url)}</strong>
                `;
                button.addEventListener('click', () => {
                    applyMediaSelection(component, control, item, input, altInput);
                    closeMediaPicker();
                });
                body.appendChild(button);
            });
        } catch (error) {
            body.innerHTML = `<div class="pb-media-picker-empty">${escapeHtml(error.message || 'Unable to load media library.')}</div>`;
        }
    };

    const unitOptionsFor = control => {
        if (control.key === 'line_height') {
            return [
                { value: '', label: '-' },
                { value: 'px', label: 'px' },
                { value: '%', label: '%' },
                { value: 'rem', label: 'rem' },
                { value: 'em', label: 'em' },
            ];
        }

        return ['px', '%', 'rem', 'em', 'vh', 'vw'].map(value => ({ value, label: value }));
    };

    const splitUnitValue = (value, control = {}) => {
        const match = String(value || '').trim().match(/^(-?\d+(?:\.\d+)?)(px|%|rem|em|vh|vw)?$/);

        if (!match) {
            return { number: String(value || '').trim(), unit: control.key === 'line_height' ? '' : 'px' };
        }

        return { number: match[1], unit: match[2] || (control.key === 'line_height' ? '' : 'px') };
    };

    const createButtonGroupControl = (component, control, value, options) => {
        const group = document.createElement('div');
        group.className = 'pb-control-button-group';

        options.forEach(option => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = option.short || option.label;
            button.title = option.label;
            button.dataset.value = option.value;
            button.setAttribute('aria-label', option.label);
            button.className = String(value) === String(option.value) ? 'is-active' : '';
            button.addEventListener('click', () => {
                group.querySelectorAll('button').forEach(item => item.classList.remove('is-active'));
                button.classList.add('is-active');
                applyControlValue(component, control, option.value);
            });
            group.appendChild(button);
        });

        return group;
    };

    const createUnitControl = (component, control, value) => {
        const parsed = splitUnitValue(value, control);
        const wrapper = document.createElement('div');
        wrapper.className = 'pb-control-unit';
        const amount = document.createElement('input');
        amount.type = 'number';
        amount.value = parsed.number.replace(/[^\d.-]/g, '');
        const unit = document.createElement('select');

        unitOptionsFor(control).forEach(unitOption => {
            const option = document.createElement('option');
            option.value = unitOption.value;
            option.textContent = unitOption.label;
            option.selected = parsed.unit === unitOption.value;
            unit.appendChild(option);
        });

        const commit = () => applyControlValue(component, control, amount.value === '' ? '' : `${amount.value}${unit.value}`);
        amount.addEventListener('input', commit);
        unit.addEventListener('change', commit);
        wrapper.appendChild(amount);
        wrapper.appendChild(unit);

        return wrapper;
    };

    const createDimensionsControl = (component, control, value) => {
        const dimensionParts = String(value || '').trim().split(/\s+/).filter(Boolean);
        const values = dimensionParts.length === 1 ? [dimensionParts[0], dimensionParts[0], dimensionParts[0], dimensionParts[0]]
            : dimensionParts.length === 2 ? [dimensionParts[0], dimensionParts[1], dimensionParts[0], dimensionParts[1]]
                : dimensionParts.length === 3 ? [dimensionParts[0], dimensionParts[1], dimensionParts[2], dimensionParts[1]]
                    : [dimensionParts[0] || '', dimensionParts[1] || '', dimensionParts[2] || '', dimensionParts[3] || ''];
        const wrapper = document.createElement('div');
        wrapper.className = 'pb-control-dimensions';
        let linked = dimensionParts.length <= 1;
        const inputsHost = document.createElement('div');
        inputsHost.className = 'pb-control-dimensions-inputs';
        const unitSelect = document.createElement('select');
        unitSelect.className = 'pb-control-dimensions-unit';
        const labels = ['Top', 'Right', 'Bottom', 'Left'];
        const parsedValues = values.map(item => splitUnitValue(item, control));
        const initialUnit = parsedValues.find(item => item.unit)?.unit || 'px';
        const commit = inputs => {
            const unitValue = unitSelect.value;
            const next = inputs.map(item => item.value.trim()).map(item => item === '' ? '' : `${item}${unitValue}`);
            applyControlValue(component, control, next.join(' '));
        };

        ['px', '%', 'rem', 'em'].forEach(unitValue => {
            const option = document.createElement('option');
            option.value = unitValue;
            option.textContent = unitValue;
            option.selected = initialUnit === unitValue;
            unitSelect.appendChild(option);
        });

        const inputs = labels.map((label, index) => {
            const item = document.createElement('label');
            item.className = 'pb-control-dimension-item';
            const input = document.createElement('input');
            input.type = 'number';
            input.placeholder = '0';
            input.title = label;
            input.value = parsedValues[index]?.number?.replace(/[^\d.-]/g, '') || '';
            input.addEventListener('input', () => {
                if (linked) {
                    inputs.forEach(item => {
                        if (item !== input) {
                            item.value = input.value;
                        }
                    });
                }
                commit(inputs);
            });
            const labelText = document.createElement('span');
            labelText.textContent = label;
            item.appendChild(input);
            item.appendChild(labelText);
            inputsHost.appendChild(item);
            return input;
        });
        unitSelect.addEventListener('change', () => commit(inputs));
        const linkButton = document.createElement('button');
        linkButton.type = 'button';
        linkButton.className = `pb-control-dimensions-link${linked ? ' is-active' : ''}`;
        linkButton.textContent = 'Link';
        linkButton.setAttribute('aria-label', 'Link dimension values');
        linkButton.addEventListener('click', () => {
            linked = !linked;
            linkButton.classList.toggle('is-active', linked);
            if (linked) {
                inputs.forEach(input => {
                    input.value = inputs[0].value;
                });
                commit(inputs);
            }
        });
        wrapper.appendChild(inputsHost);
        const tools = document.createElement('div');
        tools.className = 'pb-control-dimensions-tools';
        tools.appendChild(linkButton);
        tools.appendChild(unitSelect);
        wrapper.appendChild(tools);

        return wrapper;
    };

    const createColorControl = (component, control, value) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'pb-control-color';
        const picker = document.createElement('input');
        picker.type = 'color';
        picker.value = /^#[0-9a-f]{6}$/i.test(String(value)) ? value : '#000000';
        const text = document.createElement('input');
        text.type = 'text';
        text.value = value || '';
        text.placeholder = '#000000';
        text.dataset.controlKey = control.key;

        const commit = nextValue => {
            const normalized = String(nextValue || '').trim();
            text.value = normalized;
            if (/^#[0-9a-f]{6}$/i.test(normalized)) {
                picker.value = normalized;
            }
            applyControlValue(component, control, normalized);
        };

        picker.addEventListener('input', () => commit(picker.value));
        picker.addEventListener('change', () => commit(picker.value));
        text.addEventListener('input', () => commit(text.value));
        text.addEventListener('change', () => commit(text.value));

        wrapper.appendChild(picker);
        wrapper.appendChild(text);

        return wrapper;
    };

    const createVisibilityControl = (component, control, value) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'pb-control-visibility';
        const current = String(value || 'all');
        const isShown = device => current === 'all' || current !== `hide_${device}`;
        const options = [
            ['desktop', 'Show desktop'],
            ['tablet', 'Show tablet'],
            ['mobile', 'Show mobile'],
        ];
        const state = {
            desktop: isShown('desktop'),
            tablet: isShown('tablet'),
            mobile: isShown('mobile'),
        };
        const serialize = () => {
            const hidden = Object.entries(state).filter(([, shown]) => !shown).map(([device]) => `hide_${device}`);
            return hidden.length === 0 ? 'all' : hidden.join(' ');
        };

        options.forEach(([device, label]) => {
            const item = document.createElement('label');
            item.className = 'pb-toggle-row';
            const input = document.createElement('input');
            input.type = 'checkbox';
            input.checked = state[device];
            input.addEventListener('change', () => {
                state[device] = input.checked;
                applyControlValue(component, control, serialize());
            });
            item.appendChild(input);
            const labelText = document.createElement('span');
            labelText.textContent = label;
            item.appendChild(labelText);
            wrapper.appendChild(item);
        });

        return wrapper;
    };

    const createControlInput = (component, control) => {
        const value = readControlValue(component, control);
        const options = selectOptions(control);
        let input;

        if (control.type === 'media') {
            const wrapper = document.createElement('div');
            wrapper.className = 'pb-media-control';

            input = document.createElement('input');
            input.type = 'text';
            input.value = value;
            input.placeholder = 'Selected image URL';
            input.dataset.controlKey = control.key;
            input.readOnly = true;

            const preview = document.createElement('div');
            preview.className = 'pb-media-control-preview';
            preview.innerHTML = value
                ? `<img src="${escapeHtml(value)}" alt="">`
                : '<span>No image selected</span>';

            const chooseButton = document.createElement('button');
            chooseButton.type = 'button';
            chooseButton.className = 'pb-media-control-button';
            chooseButton.textContent = value ? 'Change Image' : 'Select Image';

            const altInput = document.createElement('input');
            altInput.type = 'text';
            altInput.placeholder = 'Alt text';
            altInput.value = readAttribute(targetComponent(component, 'image'), 'alt')
                || readAttribute(schemaComponentFor(component), 'data-pb-alt')
                || '';

            input.addEventListener('input', () => applyControlValue(component, control, input.value));
            input.addEventListener('change', () => applyControlValue(component, control, input.value));
            altInput.addEventListener('input', () => {
                const altControl = schemaControlsFor(selectedWidgetId(component)).find(item => item.key === 'alt');

                if (altControl) {
                    applyControlValue(component, altControl, altInput.value);
                    return;
                }

                targetComponent(schemaComponentFor(component), 'image')?.addAttributes?.({ alt: altInput.value });
                markDirty();
            });
            chooseButton.addEventListener('click', () => openMediaPicker(component, control, input, altInput));

            wrapper.appendChild(preview);
            wrapper.appendChild(input);
            wrapper.appendChild(chooseButton);
            wrapper.appendChild(altInput);

            return wrapper;
        }

        const buttonGroupKeys = ['direction', 'justify', 'align', 'alignment', 'caption_alignment', 'width_mode'];
        const dimensionKeys = ['margin', 'padding', 'radius', 'border_radius'];
        const unitKeys = ['gap', 'min_height', 'width', 'max_width', 'height', 'font_size', 'line_height', 'letter_spacing', 'border_width', 'caption_font_size', 'caption_spacing', 'thickness', 'spacing_top', 'spacing_bottom'];

        if (buttonGroupKeys.includes(control.key) && options.length) {
            return createButtonGroupControl(component, control, value, options.map(option => ({
                ...option,
                short: {
                    row: 'Row',
                    column: 'Column',
                    'flex-start': 'Start',
                    center: 'Center',
                    'flex-end': 'End',
                    'space-between': 'Between',
                    stretch: 'Stretch',
                    left: 'Left',
                    right: 'Right',
                    auto: 'Auto',
                    full: 'Full',
                    custom: 'Custom',
                }[option.value] || option.label,
            })));
        }

        if (unitKeys.includes(control.key)) {
            return createUnitControl(component, control, value);
        }

        if (dimensionKeys.includes(control.key)) {
            return createDimensionsControl(component, control, value);
        }

        if (control.key === 'responsive_visibility') {
            return createVisibilityControl(component, control, value);
        }

        if (control.type === 'color') {
            return createColorControl(component, control, value);
        }

        if (control.type === 'select' && options.length) {
            input = document.createElement('select');
            options.forEach(option => {
                const opt = document.createElement('option');
                opt.value = option.value;
                opt.textContent = option.label;
                opt.selected = String(value) === String(option.value);
                input.appendChild(opt);
            });
        } else if (['textarea', 'richtext', 'repeater'].includes(control.type)) {
            input = document.createElement('textarea');
            input.rows = control.type === 'richtext' ? 5 : 3;
            input.value = Array.isArray(value) ? JSON.stringify(value, null, 2) : value;
        } else if (control.type === 'switch') {
            input = document.createElement('input');
            input.type = 'checkbox';
            input.checked = value === true || value === 'true' || value === '1';
        } else {
            input = document.createElement('input');
            input.type = control.type === 'number' ? 'number' : 'text';
            input.value = value;
        }

        input.dataset.controlKey = control.key;
        input.addEventListener('input', () => {
            const nextValue = input.type === 'checkbox' ? input.checked : input.value;
            applyControlValue(component, control, nextValue);
        });
        input.addEventListener('change', () => {
            const nextValue = input.type === 'checkbox' ? input.checked : input.value;
            applyControlValue(component, control, nextValue);
        });

        return input;
    };

    const renderSchemaPanel = () => {
        attachSettingsPanel();
        const component = schemaComponentFor(settingsState.component || editor.getSelected());
        const schema = schemaForComponent(component);

        if (!schema) {
            settingsPanel.innerHTML = '<div class="pb-schema-empty">Select a registered element to edit schema settings.</div>';
            setInspectorTitle('Elements');
            return;
        }

        const widgetId = selectedWidgetId(component);
        const tabLabels = widgetId === 'container'
            ? { general: 'Layout', style: 'Style', advanced: 'Advanced' }
            : { general: 'Content', style: 'Style', advanced: 'Advanced' };
        const allowedTabs = Object.keys(tabLabels);

        if (!allowedTabs.includes(settingsState.tab)) {
            settingsState.tab = 'general';
        }

        const controls = (schema.controls || [])
            .filter(control => control.tab === settingsState.tab)
            .filter(control => control.type !== 'hidden')
            .filter(control => conditionPasses(component, control));

        const grouped = controls.reduce((carry, control) => {
            carry[control.group] = carry[control.group] || [];
            carry[control.group].push(control);
            return carry;
        }, {});

        settingsPanel.innerHTML = '';
        const header = document.createElement('div');
        header.className = 'pb-schema-header';
        const backButton = document.createElement('button');
        backButton.type = 'button';
        backButton.className = 'pb-schema-back';
        backButton.textContent = 'Back';
        backButton.setAttribute('aria-label', 'Back to elements');
        backButton.addEventListener('click', () => {
            editor.select(null);
            activateSidebarMode('blocks');
        });
        const heading = document.createElement('div');
        heading.className = 'pb-schema-title';
        heading.innerHTML = `<span>Edit</span><strong>Edit ${escapeHtml(schema.label || widgetId)}</strong>`;
        header.appendChild(backButton);
        header.appendChild(heading);
        settingsPanel.appendChild(header);
        setInspectorTitle(`Edit ${schema.label || widgetId}`);

        const tabs = document.createElement('div');
        tabs.className = 'pb-schema-tabs';
        allowedTabs.forEach(tab => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = tabLabels[tab];
            button.dataset.schemaTab = tab;
            button.className = settingsState.tab === tab ? 'is-active' : '';
            button.addEventListener('click', () => {
                settingsState.tab = tab;
                renderSchemaPanel();
            });
            tabs.appendChild(button);
        });
        settingsPanel.appendChild(tabs);

        if (!controls.length) {
            const empty = document.createElement('div');
            empty.className = 'pb-schema-empty';
            empty.textContent = 'No controls in this tab for this element.';
            settingsPanel.appendChild(empty);
            return;
        }

        Object.entries(grouped).forEach(([group, groupControls]) => {
            const section = document.createElement('section');
            section.className = 'pb-schema-group';
            const title = document.createElement('h3');
            title.textContent = group;
            section.appendChild(title);

            groupControls.forEach(control => {
                const field = document.createElement('label');
                field.className = 'pb-schema-control';
                field.dataset.controlKey = control.key;
                field.dataset.controlTab = control.tab;
                field.dataset.controlTarget = control.target;
                field.innerHTML = `<span>${escapeHtml(control.label)}</span>`;
                field.appendChild(createControlInput(component, control));
                section.appendChild(field);
            });

            settingsPanel.appendChild(section);
        });
    };

    const updateTextContent = (component, value) => {
        if (typeof value === 'string' && value !== '') {
            component.components(escapeHtml(value));
        }
    };

    const hasRenderableTag = component => {
        const tagName = component && component.get ? component.get('tagName') : '';

        return typeof tagName === 'string' && tagName !== '' && tagName !== 'wrapper';
    };

    const ensureComponentIdAttribute = component => {
        if (!hasRenderableTag(component) || !component.getAttributes || !component.addAttributes) {
            return;
        }

        const attrs = component.getAttributes() || {};

        if (attrs.id) {
            return;
        }

        const id = component.getId ? component.getId() : '';

        if (id) {
            component.addAttributes({ id });
        }
    };

    const firstImageComponent = component => {
        const tagName = String(component && component.get ? (component.get('tagName') || '') : '').toLowerCase();

        if (tagName === 'img') {
            return component;
        }

        const images = component && component.find ? component.find('img') : [];

        return images && images.length ? images[0] : null;
    };

    const componentImageAttributes = component => {
        const image = firstImageComponent(component);

        if (!image || !image.getAttributes) {
            return {};
        }

        const attrs = { ...(image.getAttributes() || {}) };

        if (!attrs.id && image.getId) {
            attrs.id = image.getId();
        }

        return attrs;
    };

    const normalizeImageActionAttributes = component => {
        const target = firstImageComponent(component) || component;

        if (!target || !target.getAttributes || !target.addAttributes) {
            return;
        }

        const attrs = target.getAttributes() || {};
        const currentAction = attrs['data-pb-image-action'];
        const canonicalAction = canonicalImageAction(currentAction);

        if (currentAction && currentAction !== canonicalAction) {
            target.addAttributes({ 'data-pb-image-action': canonicalAction });
        }
    };

    const walkComponents = (components, callback) => {
        if (!components || !components.forEach) {
            return;
        }

        components.forEach(component => {
            callback(component);
            walkComponents(component.components && component.components(), callback);
        });
    };

    const persistStyleTargets = () => {
        const wrapper = editor.getWrapper();
        walkComponents(wrapper.components(), ensureComponentIdAttribute);
    };

    const dynamicPreviewSignature = (component, attrs) => {
        const normalized = Object.keys(attrs || {})
            .sort()
            .reduce((result, key) => {
                result[key] = attrs[key];
                return result;
            }, {});

        return JSON.stringify({
            tag: component && component.get ? component.get('tagName') : '',
            attrs: normalized,
        });
    };

    const renderDynamicComponentPreview = async (component, attrs) => {
        if (!editorComponentPreviewUrl || !component || !attrs || !attrs['data-art-news-element']) {
            return;
        }

        if ((attrs['data-pb-source-type'] || 'dynamic') === 'static' && attrs['data-art-news-element'] !== 'dynamic-categories') {
            return;
        }

        const signature = dynamicPreviewSignature(component, attrs);

        if (component.get('__dynamicPreviewSignature') === signature || component.get('__dynamicPreviewPendingSignature') === signature) {
            return;
        }

        component.set('__dynamicPreviewPendingSignature', signature);

        try {
            const payload = new FormData();
            payload.append('html', component.toHTML());

            const response = await fetch(editorComponentPreviewUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: payload,
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.ok === false || component.get('__dynamicPreviewPendingSignature') !== signature) {
                return;
            }

            previewRendering = true;
            component.components(data.inner_html || data.html || '');
            component.set('__dynamicPreviewSignature', signature);
        } catch (error) {
            console.warn('Dynamic editor preview failed.', error);
        } finally {
            component.set('__dynamicPreviewPendingSignature', '');
            previewRendering = false;
        }
    };

    const isSchemaFirstComponent = component => {
        const attrs = component && component.getAttributes ? component.getAttributes() : {};
        const widgetId = attrs['data-pb-widget'] || '';

        return schemaFirstWidgetIds.has(widgetId) && (attrs['data-pb-schema-first'] === 'true' || Boolean(attrs['data-pb-widget']));
    };

    const schemaFirstControls = component => schemaControlsFor(selectedWidgetId(schemaComponentFor(component)))
        .filter(control => control && control.schema_first);

    const applySchemaFirstDefaults = component => {
        const schema = schemaForComponent(component);

        if (!schema || !schema.default_props || !component.addAttributes) {
            return;
        }

        const attrs = component.getAttributes ? component.getAttributes() : {};
        const nextAttrs = {
            'data-pb-schema-first': 'true',
            'data-pb-type': schema.type || selectedWidgetId(component),
            'data-pb-component-type': schema.component_type || (schema.type === 'image' ? 'pb_image' : `pb-schema-${schema.type || selectedWidgetId(component)}`),
        };

        Object.entries(schema.default_props).forEach(([key, value]) => {
            const attrName = `data-pb-${String(key).replaceAll('_', '-')}`;

            if (attrs[attrName] === undefined || attrs[attrName] === '') {
                nextAttrs[attrName] = String(value ?? '');
            }
        });

        component.addAttributes(nextAttrs);
    };

    const applyContentWidth = component => {
        const attrs = component.getAttributes ? component.getAttributes() : {};
        const value = attrs['data-pb-content-width'] || 'boxed';

        const hasCustomMargin = attrs['data-pb-margin'] !== undefined && attrs['data-pb-margin'] !== '';

        if (value === 'boxed') {
            component.addStyle(hasCustomMargin
                ? { width: 'min(100%, 1120px)' }
                : { width: 'min(100%, 1120px)', margin: '0 auto' });
            return;
        }

        component.addStyle(hasCustomMargin ? { width: '100%' } : { width: '100%', margin: '0' });
    };

    const applyResponsiveVisibility = component => {
        const attrs = component.getAttributes ? component.getAttributes() : {};
        const value = attrs['data-pb-responsive-visibility'] || 'all';

        component.addAttributes({ 'data-pb-responsive-visibility': value });
    };

    const selectorForComponent = component => {
        ensureComponentIdAttribute(component);
        const attrs = component && component.getAttributes ? component.getAttributes() : {};
        const id = attrs.id || component.getId?.();

        if (!id) {
            return '';
        }

        return `#${window.CSS && CSS.escape ? CSS.escape(id) : String(id).replace(/[^A-Za-z0-9_-]/g, '\\\\$&')}`;
    };

    const schemaFirstControlSelector = (component, control) => {
        const root = schemaComponentFor(component);
        const rootSelector = selectorForComponent(root);

        if (!rootSelector) {
            return '';
        }

        if (selectedWidgetId(root) === 'image') {
            if (control.target === 'image') {
                return `${rootSelector} img`;
            }

            if (control.target === 'caption') {
                return `${rootSelector} figcaption`;
            }
        }

        if (selectedWidgetId(root) === 'button' && control.target === 'button') {
            return `${rootSelector} .pb-button-link`;
        }

        if (selectedWidgetId(root) === 'divider' && control.target === 'line') {
            return `${rootSelector} .pb-divider-line`;
        }

        return selectorForComponent(targetComponent(root, control.target));
    };

    const cssDeclarations = declarations => Object.entries(declarations)
        .filter(([, value]) => String(value ?? '').trim() !== '')
        .map(([property, value]) => `${property}:${String(value).trim()};`)
        .join('');

    const schemaFirstComponentCss = component => {
        if (!isSchemaFirstComponent(component)) {
            return '';
        }

        const rulesByTarget = {};

        schemaFirstControls(component)
            .filter(control => control.cssProperty)
            .forEach(control => {
                const selector = schemaFirstControlSelector(component, control);

                if (!selector) {
                    return;
                }

                rulesByTarget[selector] ??= {};
                rulesByTarget[selector][control.cssProperty] = normalizeCssValue(readControlValue(component, control), control);
            });

        if (selectedWidgetId(component) === 'container') {
            const attrs = component.getAttributes ? component.getAttributes() : {};
            const selector = selectorForComponent(component);
            const hasCustomMargin = attrs['data-pb-margin'] !== undefined && attrs['data-pb-margin'] !== '';

            if (selector) {
                rulesByTarget[selector] ??= {};
                rulesByTarget[selector].width = (attrs['data-pb-content-width'] || 'boxed') === 'boxed' ? 'min(100%, 1120px)' : '100%';
                if (!hasCustomMargin) {
                    rulesByTarget[selector].margin = (attrs['data-pb-content-width'] || 'boxed') === 'boxed' ? '0 auto' : '0';
                }
            }
        }

        if (selectedWidgetId(component) === 'image') {
            const attrs = component.getAttributes ? component.getAttributes() : {};
            const selector = selectorForComponent(component);

            if (selector) {
                rulesByTarget[selector] ??= {};
                rulesByTarget[selector].width = 'auto';
                rulesByTarget[selector]['max-width'] = 'none';
                rulesByTarget[selector].flex = attrs['data-pb-width'] ? '0 0 auto' : 'initial';
            }
        }

        if (selectedWidgetId(component) === 'text') {
            const attrs = component.getAttributes ? component.getAttributes() : {};
            const selector = selectorForComponent(component);

            if (selector && attrs['data-pb-link']) {
                rulesByTarget[`${selector} a`] ??= {};
                rulesByTarget[`${selector} a`].color = 'inherit';
                rulesByTarget[`${selector} a`]['text-decoration'] = 'inherit';
            }
        }

        if (selectedWidgetId(component) === 'button') {
            const attrs = component.getAttributes ? component.getAttributes() : {};
            const selector = selectorForComponent(component);
            const widthMode = attrs['data-pb-width-mode'] || 'auto';

            if (selector) {
                rulesByTarget[`${selector} .pb-button-link`] ??= {};
                rulesByTarget[`${selector} .pb-button-link`].display = 'inline-flex';
                rulesByTarget[`${selector} .pb-button-link`]['align-items'] = 'center';
                rulesByTarget[`${selector} .pb-button-link`]['justify-content'] = 'center';
                rulesByTarget[`${selector} .pb-button-link`]['text-decoration'] = 'none';
                rulesByTarget[`${selector} .pb-button-link`]['box-sizing'] = 'border-box';

                if (widthMode === 'full') {
                    rulesByTarget[`${selector} .pb-button-link`].width = '100%';
                } else if (widthMode === 'auto') {
                    rulesByTarget[`${selector} .pb-button-link`].width = 'auto';
                } else if (attrs['data-pb-width']) {
                    rulesByTarget[`${selector} .pb-button-link`].width = normalizeCssValue(attrs['data-pb-width'], { cssProperty: 'width' });
                }

                if (attrs['data-pb-hover-text-color'] || attrs['data-pb-hover-background-color']) {
                    rulesByTarget[`${selector} .pb-button-link:hover`] ??= {};
                    if (attrs['data-pb-hover-text-color']) {
                        rulesByTarget[`${selector} .pb-button-link:hover`].color = attrs['data-pb-hover-text-color'];
                    }
                    if (attrs['data-pb-hover-background-color']) {
                        rulesByTarget[`${selector} .pb-button-link:hover`]['background-color'] = attrs['data-pb-hover-background-color'];
                    }
                }
            }
        }

        if (selectedWidgetId(component) === 'divider') {
            const selector = selectorForComponent(component);

            if (selector) {
                rulesByTarget[selector] ??= {};
                rulesByTarget[selector].display = 'flex';

                rulesByTarget[`${selector} .pb-divider-line`] ??= {};
                rulesByTarget[`${selector} .pb-divider-line`].display = 'block';
                rulesByTarget[`${selector} .pb-divider-line`].height = '0';
                rulesByTarget[`${selector} .pb-divider-line`]['border-right-width'] = '0';
                rulesByTarget[`${selector} .pb-divider-line`]['border-bottom-width'] = '0';
                rulesByTarget[`${selector} .pb-divider-line`]['border-left-width'] = '0';
            }
        }

        return Object.entries(rulesByTarget)
            .map(([selector, declarations]) => {
                const body = cssDeclarations(declarations);

                return body ? `${selector}{${body}}` : '';
            })
            .filter(Boolean)
            .join('');
    };

    const schemaFirstBaseCss = () => {
        const rules = [];

        walkComponents(editor.getWrapper().components(), component => {
            const css = schemaFirstComponentCss(component);

            if (css) {
                rules.push(css);
            }
        });

        return rules.length ? `/* __SCHEMA_FIRST_BASE_CSS__ */\n${rules.join('\n')}` : '';
    };

    const applySchemaFirstStyles = component => {
        if (!isSchemaFirstComponent(component)) {
            return;
        }

        schemaFirstControls(component)
            .filter(control => control.cssProperty)
            .forEach(control => applyCssControl(component, control, readControlValue(component, control)));

        if (selectedWidgetId(component) === 'container') {
            applyContentWidth(component);
        }

        if (selectedWidgetId(component) === 'image') {
            const attrs = component.getAttributes ? component.getAttributes() : {};

            component.addStyle({
                width: 'auto',
                'max-width': 'none',
                flex: attrs['data-pb-width'] ? '0 0 auto' : 'initial',
            });
        }

        if (selectedWidgetId(component) === 'button') {
            const attrs = component.getAttributes ? component.getAttributes() : {};
            const button = targetComponent(component, 'button');
            const widthMode = attrs['data-pb-width-mode'] || 'auto';
            const buttonStyle = {
                display: 'inline-flex',
                'align-items': 'center',
                'justify-content': 'center',
                'text-decoration': 'none',
                'box-sizing': 'border-box',
            };

            if (widthMode === 'full') {
                buttonStyle.width = '100%';
            } else if (widthMode === 'auto') {
                buttonStyle.width = 'auto';
            } else if (attrs['data-pb-width']) {
                buttonStyle.width = normalizeCssValue(attrs['data-pb-width'], { cssProperty: 'width' });
            }

            button?.addStyle?.(buttonStyle);
        }

        if (selectedWidgetId(component) === 'divider') {
            const line = targetComponent(component, 'line');

            component.addStyle({ display: 'flex' });
            line?.addStyle?.({
                display: 'block',
                height: '0',
                'border-right-width': '0',
                'border-bottom-width': '0',
                'border-left-width': '0',
            });
        }

        applyResponsiveVisibility(component);
    };

    const removeContainerPlaceholder = component => {
        if (!component || selectedWidgetId(component) !== 'container' || !component.components) {
            return;
        }

        const children = component.components();
        const realChildren = [];
        const placeholders = [];

        children.forEach(child => {
            const attrs = child.getAttributes ? child.getAttributes() : {};

            if (attrs['data-pb-placeholder'] === 'true') {
                placeholders.push(child);
            } else {
                realChildren.push(child);
            }
        });

        if (realChildren.length) {
            placeholders.forEach(child => child.remove && child.remove());
        }
    };

    const ensureContainerPlaceholder = component => {
        if (!component || selectedWidgetId(component) !== 'container' || !component.components) {
            return;
        }

        if (component.components().length === 0) {
            component.components('<div data-pb-placeholder="true">Drop widgets here</div>');
        }
    };

    const syncSchemaPropsFromStyle = component => {
        if (!isSchemaFirstComponent(component) || !component.addAttributes) {
            return;
        }

        applySchemaFirstDefaults(component);

        const attrs = component.getAttributes ? component.getAttributes() : {};

        schemaFirstControls(component).forEach(control => {
            const attr = controlAttributeName(control);

            if (!control.cssProperty || attrs[attr]) {
                return;
            }

            const target = targetComponent(component, control.target);
            const style = target && target.getStyle ? target.getStyle() : {};
            const value = style[control.cssProperty];

            if (value !== undefined && value !== '') {
                component.addAttributes({ [attr]: value });
            }
        });

        const nextAttrs = component.getAttributes ? component.getAttributes() : {};
        const semantic = nextAttrs['data-pb-semantic-tag'] || nextAttrs['data-pb-tag'];

        if (semantic && selectedWidgetId(component) === 'container') {
            component.set('tagName', semantic);
            component.addAttributes({ 'data-pb-semantic-tag': semantic });
        }

        if (selectedWidgetId(component) === 'container') {
            removeContainerPlaceholder(component);
            ensureContainerPlaceholder(component);
        }

        applySchemaFirstStyles(component);
    };

    const normalizeSchemaFirstTree = () => {
        const wrapper = editor.getWrapper();
        walkComponents(wrapper.components(), component => {
            if (isSchemaFirstComponent(component)) {
                syncSchemaPropsFromStyle(component);
                updateComponentPreview(component);
            }
        });
    };

    const renderSchemaFirstHeading = (component, attrs) => {
        const text = attrs['data-pb-text'] || 'Heading text';
        const tag = attrs['data-pb-html-tag'] || attrs['data-pb-tag'] || 'h2';
        const link = attrs['data-pb-link'] || '';

        component.set('tagName', tag);

        if (link) {
            component.components(`<a href="${escapeHtml(link)}">${escapeHtml(text)}</a>`);
            return;
        }

        updateTextContent(component, text);
    };

    const renderSchemaFirstText = (component, attrs) => {
        const text = attrs['data-pb-text'] || 'Text content';
        const tag = ['p', 'div', 'span'].includes(attrs['data-pb-html-tag']) ? attrs['data-pb-html-tag'] : 'p';
        const link = attrs['data-pb-link'] || '';

        component.set('tagName', tag);

        if (link) {
            component.components(`<a href="${escapeHtml(link)}">${escapeHtml(text).replace(/\n/g, '<br>')}</a>`);
            return;
        }

        component.components(escapeHtml(text).replace(/\n/g, '<br>'));
    };

    const renderSchemaFirstButton = component => {
        component = schemaComponentFor(component);
        const attrs = component.getAttributes ? component.getAttributes() : {};
        const text = attrs['data-pb-text'] || 'Button';
        const href = attrs['data-pb-link-url'] || '#';
        const target = attrs['data-pb-link-target'] === '_blank' ? '_blank' : '_self';
        const targetAttr = target === '_blank' ? ' target="_blank" rel="noopener"' : '';

        component.set('tagName', 'div');
        component.components(`<a href="${escapeHtml(href || '#')}" class="pb-button-link"${targetAttr}>${escapeHtml(text)}</a>`);
    };

    const renderSchemaFirstDivider = component => {
        component = schemaComponentFor(component);
        component.set('tagName', 'div');
        component.components('<span class="pb-divider-line"></span>');
    };

    const renderSchemaFirstImage = component => {
        component = schemaComponentFor(component);
        const attrs = component.getAttributes ? component.getAttributes() : {};
        const src = attrs['data-pb-media-url'] || attrs['data-pb-image-url'] || attrs['data-pb-src'] || '';
        const captionMode = attrs['data-pb-caption-mode'] || 'none';
        const caption = captionMode === 'none' ? '' : (attrs['data-pb-caption'] || '');
        const linkType = attrs['data-pb-link-type'] || imageLinkTypeFromAction(attrs['data-pb-image-action']) || 'none';
        const link = linkType === 'media_file'
            ? src
            : (attrs['data-pb-link-url'] || attrs['data-pb-link'] || '');
        const linkTarget = attrs['data-pb-link-target'] || '_self';
        const linkNofollow = attrs['data-pb-link-nofollow'] === 'true' || attrs['data-pb-link-nofollow'] === true;
        const loading = ['lazy', 'eager'].includes(attrs['data-pb-loading']) ? attrs['data-pb-loading'] : 'lazy';
        const decoding = ['auto', 'async'].includes(attrs['data-pb-decoding']) ? attrs['data-pb-decoding'] : 'async';
        const image = firstImageComponent(component);
        const alt = (image ? readAttribute(image, 'alt') : '') || attrs['data-pb-alt'] || attrs.alt || '';

        if (image) {
            image.addAttributes({ src, alt, loading, decoding });
        }

        const imgHtml = `<img src="${escapeHtml(src)}" alt="${escapeHtml(alt)}" loading="${escapeHtml(loading)}" decoding="${escapeHtml(decoding)}">`;
        const targetAttr = linkTarget === '_blank' ? ' target="_blank"' : '';
        const relAttr = linkNofollow ? ' rel="nofollow"' : '';
        const linkedImage = linkType !== 'none' && link
            ? `<a href="${escapeHtml(link)}"${targetAttr}${relAttr}>${imgHtml}</a>`
            : imgHtml;
        const captionHtml = caption ? `<figcaption>${escapeHtml(caption)}</figcaption>` : '';
        component.components(`${linkedImage}${captionHtml}`);
    };

    const updateComponentPreview = component => {
        ensureComponentIdAttribute(component);

        const attrs = component.getAttributes ? component.getAttributes() : {};
        const widgetId = attrs['data-pb-widget'];

        if (!widgetId) {
            return;
        }

        if (isSchemaFirstComponent(component)) {
            applySchemaFirstDefaults(component);

            if (widgetId === 'container') {
                const semanticTag = attrs['data-pb-semantic-tag'] || attrs['data-pb-tag'];

                if (semanticTag) {
                    component.set('tagName', semanticTag);
                    component.addAttributes({ 'data-pb-semantic-tag': semanticTag });
                }

                removeContainerPlaceholder(component);
                ensureContainerPlaceholder(component);
            }

            if (widgetId === 'heading') {
                renderSchemaFirstHeading(component, component.getAttributes ? component.getAttributes() : attrs);
            }

            if (widgetId === 'text') {
                renderSchemaFirstText(component, component.getAttributes ? component.getAttributes() : attrs);
            }

            if (widgetId === 'button') {
                renderSchemaFirstButton(component);
            }

            if (widgetId === 'divider') {
                renderSchemaFirstDivider(component);
            }

            if (widgetId === 'image') {
                renderSchemaFirstImage(component);
            }

            applySchemaFirstStyles(component);
            return;
        }

        if (['heading', 'text', 'badge', 'alert', 'copyright'].includes(widgetId)) {
            updateTextContent(component, attrs['data-pb-text']);
        }

        if (widgetId === 'heading' && attrs['data-pb-tag']) {
            component.set('tagName', attrs['data-pb-tag']);
        }

        if (['button', 'login-button', 'dynamic-button'].includes(widgetId)) {
            updateTextContent(component, attrs['data-pb-text']);
            if (attrs['data-pb-url']) {
                component.addAttributes({ href: attrs['data-pb-url'] });
            }
        }

        if (['image', 'dynamic-image'].includes(widgetId)) {
            normalizeImageActionAttributes(component);

            if (attrs['data-pb-src']) {
                component.addAttributes({ src: attrs['data-pb-src'] });
            }
        }

        if (widgetId === 'spacer' && attrs['data-pb-height']) {
            component.addStyle({ height: `${attrs['data-pb-height']}px` });
        }

        if (widgetId === 'logo') {
            const imageAttrs = componentImageAttributes(component);
            component.addAttributes({ 'data-platform-logo': 'site' });
            component.components(logoHtml(imageAttrs));
            (component.find ? component.find('img') : []).forEach(image => ensureComponentIdAttribute(image));
        }

        if (['menu', 'footer-menu', 'mobile-menu'].includes(widgetId) || attrs['data-platform-menu-key'] !== undefined) {
            const key = normalizedMenuKey(attrs['data-platform-menu-key'] || defaultMenuKey);
            component.addAttributes({ 'data-platform-menu-key': key });

            if (widgetId === 'mobile-menu') {
                updateTextContent(component, attrs['data-pb-label'] || 'Menu');
            } else {
                component.components(menuLinksHtml(key, linkAttributes(component)));
            }
        }

        if (widgetId === 'dynamic-title') {
            updateTextContent(component, currentPage.title || 'Current page title');
        }

        if (widgetId === 'dynamic-content') {
            updateTextContent(component, currentPage.meta_description || 'Current page content');
        }

        renderDynamicComponentPreview(component, attrs);
    };

    const widgetSupportsChildren = widget => Boolean(widget && widget.supports && widget.supports.children);

    const registerWidgetComponentType = (widget, componentType) => {
        editor.DomComponents.addType(componentType, {
            isComponent: el => el && el.getAttribute && el.getAttribute('data-pb-widget') === widget.id,
            model: {
                defaults: {
                    draggable: true,
                    droppable: widgetSupportsChildren(widget) ? '[data-pb-widget]' : false,
                    stylable: true,
                    highlightable: true,
                    attributes: {
                        'data-pb-widget': widget.id,
                        'data-pb-type': widget.id,
                        'data-pb-component-type': widget.component_type || widget.type || `pb-${widget.id}`,
                        'data-pb-module': widget.module || 'core',
                    },
                    traits: widgetTraits(widget),
                },
                init() {
                    this.on('change:attributes', () => {
                        if (!isSchemaFirstComponent(this)) {
                            updateComponentPreview(this);
                        }
                    });
                    updateComponentPreview(this);
                },
            },
        });
    };

    widgets.forEach(widget => {
        const componentTypes = [widget.component_type || widget.type || `pb-${widget.id}`];

        if (widget.id === 'image' && !componentTypes.includes('pb-schema-image')) {
            componentTypes.push('pb-schema-image');
        }

        [...new Set(componentTypes)].forEach(componentType => registerWidgetComponentType(widget, componentType));
    });

    editor.on('component:add', component => {
        ensureComponentIdAttribute(component);
        updateComponentPreview(component);
        const parent = component.parent && component.parent();

        if (parent && selectedWidgetId(parent) === 'container') {
            removeContainerPlaceholder(parent);
            updateComponentPreview(parent);
        }
    });
    editor.on('component:selected', component => {
        const root = schemaComponentFor(component);

        if (root && root !== component && selectedWidgetId(root)) {
            editor.select(root);
            return;
        }

        ensureComponentIdAttribute(root);
        updateComponentPreview(root);
        settingsState.component = root;
        settingsState.tab = 'general';
        setSchemaPanelVisible(true);
        renderSchemaPanel();
    });
    editor.on('component:deselected', () => {
        settingsState.component = null;
        showElementBrowser();
    });

    const activatePanelButton = (panel, buttonId) => {
        const button = editor.Panels.getButton(panel, buttonId);
        if (button) {
            button.set('active', true);
        }
    };

    const panelMap = {
        blocks: ['views', 'open-blocks'],
        layers: ['views', 'open-layers'],
    };

    const widgetById = id => widgets.find(widget => String(widget.id || widget.type) === String(id));
    const widgetComponentCount = widgetId => editor.getWrapper().find(`[data-pb-widget="${widgetId}"]`).length;

    const selectedInsertTarget = widgetId => {
        const selected = editor.getSelected();

        if (selected && selectedWidgetId(selected) === 'container' && widgetId !== 'container') {
            return selected;
        }

        if (widgetId !== 'container') {
            const containers = editor.getWrapper().find('[data-pb-widget="container"]');

            if (containers.length) {
                return containers[containers.length - 1];
            }
        }

        return null;
    };

    const insertWidget = widgetId => {
        const widget = widgetById(widgetId);

        if (!widget || !widget.content) {
            return;
        }

        let added = null;
        const target = selectedInsertTarget(widgetId);

        if (target && target.append) {
            removeContainerPlaceholder(target);
            added = target.append(widget.content);
            removeContainerPlaceholder(target);
        } else {
            added = editor.addComponents(widget.content);
        }

        const addedComponent = Array.isArray(added) ? added[0] : added;

        if (addedComponent) {
            updateComponentPreview(addedComponent);
            editor.select(addedComponent);
        }

        markDirty();
    };

    const dragInsertState = {
        widgetId: '',
        startCount: 0,
        lastClientX: 0,
        lastClientY: 0,
    };

    const canvasShell = document.querySelector('.page-builder-canvas-shell') || document.querySelector('.page-builder-canvas') || document.querySelector('#gjs');

    const pointInsideCanvasShell = (clientX, clientY) => {
        if (!canvasShell || !clientX || !clientY) {
            return false;
        }

        const rect = canvasShell.getBoundingClientRect();

        return clientX >= rect.left && clientX <= rect.right && clientY >= rect.top && clientY <= rect.bottom;
    };

    addRuntimeListener(document, 'dragover', event => {
        dragInsertState.lastClientX = event.clientX;
        dragInsertState.lastClientY = event.clientY;
    }, true);

    const bindBlockInsertFallback = () => {
        document.querySelectorAll('.gjs-block').forEach(block => {
            if (block.dataset.phase1bBound === 'true') {
                return;
            }

            const label = block.querySelector('.gjs-block-label')?.textContent?.trim().toLowerCase() || '';
            const widgetId = (widgets.find(widget => String(widget.label || '').trim().toLowerCase() === label)?.id)
                || (widgets.find(widget => String(widget.type || '').trim().toLowerCase() === label)?.id)
                || '';

            if (!widgetId) {
                return;
            }

            block.dataset.pbWidgetId = widgetId;
            block.dataset.phase1bBound = 'true';
            block.addEventListener('dblclick', event => {
                event.preventDefault();
                insertWidget(widgetId);
            });
            block.addEventListener('dragstart', () => {
                dragInsertState.widgetId = widgetId;
                dragInsertState.startCount = widgetComponentCount(widgetId);
                document.body.dataset.pbDraggingBlock = 'true';
            });
            block.addEventListener('dragend', () => {
                window.setTimeout(() => {
                    if (
                        dragInsertState.widgetId === widgetId
                        && widgetComponentCount(widgetId) === dragInsertState.startCount
                        && pointInsideCanvasShell(dragInsertState.lastClientX, dragInsertState.lastClientY)
                    ) {
                        insertWidget(widgetId);
                    }

                    dragInsertState.widgetId = '';
                    dragInsertState.startCount = 0;
                    delete document.body.dataset.pbDraggingBlock;
                }, 100);
            });
        });
    };

    const activateSidebarMode = mode => {
        sidebarMode = mode === 'layers' ? 'layers' : 'blocks';
        setSchemaPanelVisible(false);

        if (sidebarTabs) {
            sidebarTabs.querySelectorAll('button').forEach(button => {
                button.classList.toggle('is-active', button.dataset.sidebarMode === sidebarMode);
            });
        }

        const target = panelMap[sidebarMode];
        if (target) {
            activatePanelButton(target[0], target[1]);
        }

        window.setTimeout(bindBlockInsertFallback, 150);
    };

    const createSidebarTabs = () => {
        if (!inspectorHost || sidebarTabs) {
            return;
        }

        sidebarTabs = document.createElement('div');
        sidebarTabs.className = 'pb-sidebar-tabs';
        [
            ['blocks', 'Elements'],
            ['layers', 'Layers'],
        ].forEach(([mode, label]) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.sidebarMode = mode;
            button.textContent = label;
            button.className = mode === sidebarMode ? 'is-active' : '';
            button.addEventListener('click', () => {
                editor.select(null);
                activateSidebarMode(mode);
            });
            sidebarTabs.appendChild(button);
        });

        inspectorHost.prepend(sidebarTabs);
    };

    document.querySelectorAll('[data-builder-device]').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-builder-device]').forEach(item => item.classList.remove('is-active'));
            button.classList.add('is-active');
            editor.setDevice(button.dataset.builderDevice);
        });
    });

    if (config.builderProject) {
        editor.loadProjectData(config.builderProject);
    } else {
        editor.setComponents(config.initialHtml || '');
        editor.setStyle(config.initialCss || '');
    }

    if (editorPreviewCssBlock()) {
        previewRendering = true;
        editor.addStyle(editorPreviewCssBlock());
        previewRendering = false;
    }

    editor.getWrapper().find('[data-pb-widget]').forEach(component => updateComponentPreview(component));
    scheduleEditorCanvasCssInjection();
    persistStyleTargets();
    mountBuilderPanels();
    attachSettingsPanel();
    createSidebarTabs();
    activateSidebarMode('blocks');

    editor.on('load', () => {
        scheduleEditorCanvasCssInjection();
        mountBuilderPanels();
        attachSettingsPanel();
        createSidebarTabs();
        if (!editor.getSelected()) {
            activateSidebarMode(sidebarMode);
        }
    });

    editor.on('canvas:frame:load', scheduleEditorCanvasCssInjection);

    const serializeBuilder = () => {
        normalizeSchemaFirstTree();
        persistStyleTargets();
        const baseSchemaCss = schemaFirstBaseCss();
        const cleanCss = [stripSchemaFirstBaseCss(stripEditorPreviewCss(editor.getCss())), baseSchemaCss].filter(Boolean).join('\n');

        if (editorPreviewCssBlock()) {
            previewRendering = true;
            editor.setStyle(cleanCss);
        }

        const projectData = editor.getProjectData();
        projectData.schema_first = {
            source_of_truth: 'project_json',
            html_css_role: 'publish_output',
            version: 'schema-first/v1',
        };

        htmlInput.value = editor.getHtml({ cleanId: false });
        cssInput.value = cleanCss;
        projectInput.value = JSON.stringify(projectData);

        if (editorPreviewCssBlock()) {
            editor.addStyle(editorPreviewCssBlock());
            previewRendering = false;
        }
    };

    const formPayload = () => {
        serializeBuilder();

        return new FormData(form);
    };

    const setFormStatus = status => {
        const statusInput = form?.querySelector('[name="status"]');

        if (statusInput) {
            statusInput.value = status;
        }
    };

    const refreshPageState = page => {
        if (!page) {
            return;
        }

        const state = document.querySelector('.page-builder-statusbar-state strong');
        const publicLink = document.querySelector('.page-builder-public-url');

        if (state && page.status) {
            state.innerHTML = `<span class="page-builder-dot page-builder-dot--${escapeHtml(page.status === 'published' ? 'published' : 'draft')}"></span>${escapeHtml(String(page.status).charAt(0).toUpperCase() + String(page.status).slice(1))}`;
        }

        if (publicLink && page.public_url) {
            publicLink.href = page.public_url;
            publicLink.textContent = page.public_url;
        }
    };

    const refreshRevisions = revisions => {
        const host = document.querySelector('[data-builder-revisions-list]');

        if (!host || !Array.isArray(revisions)) {
            return;
        }

        if (!revisions.length) {
            host.innerHTML = '<p class="page-builder-revision-empty">No revisions yet. The first manual save will create one.</p>';
            return;
        }

        host.innerHTML = '';
        revisions.forEach(revision => {
            const item = document.createElement('div');
            item.className = 'page-builder-revision-item';
            item.dataset.revisionId = revision.id;
            item.innerHTML = `
                <div>
                    <strong>${escapeHtml(revision.title || 'Revision')}</strong>
                    <small>${escapeHtml(revision.created_at || '')} · ${escapeHtml((revision.meta && revision.meta.reason) || 'snapshot')}</small>
                </div>
                <button type="button" class="page-builder-action page-builder-action--compact page-builder-action--muted" data-revision-restore-url="${escapeHtml(revision.restore_url || '')}">
                    Restore
                </button>
            `;
            host.appendChild(item);
        });

        bindRevisionButtons();
    };

    const submitBuilder = async (url, mode = 'save') => {
        if (!form || !url || saving) {
            return;
        }

        saving = true;
        setSaveStatus(mode === 'autosave' ? 'Autosaving...' : 'Saving...', 'muted');

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-HTTP-Method-Override': 'PATCH',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formPayload(),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok || payload.ok === false) {
                throw new Error(payload.message || 'Builder save failed.');
            }

            dirty = false;
            setSaveStatus(mode === 'autosave' ? 'Autosaved' : 'Saved', 'success');
            refreshPageState(payload.page);

            if (Array.isArray(payload.revisions)) {
                refreshRevisions(payload.revisions);
            }
        } catch (error) {
            setSaveStatus(error.message || 'Builder save failed.', 'danger');
        } finally {
            saving = false;
        }
    };

    const scheduleAutosave = () => {
        if (!autosaveUrl || saving) {
            return;
        }

        window.clearTimeout(autosaveTimer);
        autosaveTimer = window.setTimeout(() => {
            if (dirty && !saving) {
                submitBuilder(autosaveUrl, 'autosave');
            }
        }, 15000);
    };

    function markDirty() {
        dirty = true;
        setSaveStatus('Unsaved changes', 'muted');
        scheduleAutosave();
    }

    const bindRevisionButtons = () => {
        document.querySelectorAll('[data-revision-restore-url]').forEach(button => {
            if (button.dataset.bound === 'true') {
                return;
            }

            button.dataset.bound = 'true';
            button.addEventListener('click', async () => {
                if (!button.dataset.revisionRestoreUrl || !confirm('Restore this revision? Current content will be snapshotted first.')) {
                    return;
                }

                button.disabled = true;
                setSaveStatus('Restoring revision...', 'muted');

                try {
                    const response = await fetch(button.dataset.revisionRestoreUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok || payload.ok === false) {
                        throw new Error(payload.message || 'Revision restore failed.');
                    }

                    setSaveStatus('Revision restored. Reloading editor...', 'success');
                    window.setTimeout(() => window.location.reload(), 700);
                } catch (error) {
                    button.disabled = false;
                    setSaveStatus(error.message || 'Revision restore failed.', 'danger');
                }
            });
        });
    };

    editor.on('update', () => {
        if (!saving && !previewRendering && suppressUpdateDirty === 0) {
            markDirty();
        }
    });

    addRuntimeListener(window, 'pagehide', () => {
        try {
            (runtime.cleanup || []).forEach(cleanup => cleanup());
            runtime.cleanup = [];
            editor.destroy?.();
        } catch (error) {
            console.warn('Page Builder teardown failed.', error);
        }
    });

    bindRevisionButtons();

    if (form) {
        form.addEventListener('submit', event => {
            if (!saveUrl) {
                serializeBuilder();
                return;
            }

            event.preventDefault();
            submitBuilder(saveUrl, 'save');
        });
    }

    if (publishButton) {
        publishButton.addEventListener('click', () => {
            setFormStatus('published');
            submitBuilder(saveUrl, 'publish');
        });
    }
})();
