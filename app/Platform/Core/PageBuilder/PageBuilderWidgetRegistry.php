<?php

namespace App\Platform\Core\PageBuilder;

class PageBuilderWidgetRegistry
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function widgets(): array
    {
        $coreWidgets = [
            $this->widget('section', 'Section', 'General', '<section data-pb-widget="section" style="padding:64px 32px;"><div style="max-width:1120px;margin:0 auto;min-height:80px;"></div></section>', [
                $this->textTrait('data-pb-label', 'Section Label'),
                $this->selectTrait('data-pb-width', 'Content Width', ['boxed' => 'Boxed', 'full' => 'Full Width']),
            ], ['layout', 'spacing', 'background', 'border', 'effects', 'responsive']),
            $this->widget('container', 'Container', 'General', '<div data-pb-widget="container" style="max-width:1120px;margin:0 auto;min-height:80px;padding:24px;"></div>', [
                $this->selectTrait('data-pb-container-width', 'Width', ['boxed' => 'Boxed', 'fluid' => 'Fluid']),
            ], ['layout', 'spacing', 'background', 'border', 'responsive']),
            $this->widget('box', 'Box', 'General', '<div data-pb-widget="box" style="border:1px solid #e5e7eb;border-radius:8px;min-height:120px;padding:24px;"></div>', [
                $this->textTrait('data-pb-title', 'Box Label'),
            ], ['layout', 'spacing', 'background', 'border', 'effects', 'responsive']),
            $this->widget('grid', 'Grid', 'General', '<div data-pb-widget="grid" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;"><div style="min-height:64px;"></div><div style="min-height:64px;"></div><div style="min-height:64px;"></div></div>', [
                $this->numberTrait('data-pb-columns', 'Columns'),
                $this->numberTrait('data-pb-gap', 'Gap'),
            ], ['layout', 'spacing', 'background', 'responsive']),
            $this->widget('columns', 'Columns', 'General', '<div data-pb-widget="columns" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:32px;"><div style="min-height:80px;"></div><div style="min-height:80px;"></div></div>', [
                $this->numberTrait('data-pb-columns', 'Columns'),
                $this->selectTrait('data-pb-stack', 'Mobile Stack', ['yes' => 'Yes', 'no' => 'No']),
            ], ['layout', 'spacing', 'responsive']),
            $this->widget('heading', 'Heading', 'General', '<h2 data-pb-widget="heading" data-pb-text="Heading text">Heading text</h2>', [
                $this->textTrait('data-pb-text', 'Text'),
                $this->selectTrait('data-pb-tag', 'HTML Tag', ['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => 'Paragraph']),
                $this->selectTrait('data-dynamic-field', 'Dynamic Source', ['' => 'None', 'title' => 'Current Page Title', 'seo_title' => 'SEO Title']),
            ], ['typography', 'spacing', 'text', 'responsive']),
            $this->widget('text', 'Text', 'General', '<p data-pb-widget="text" data-pb-text="Write your text here.">Write your text here.</p>', [
                $this->textareaTrait('data-pb-text', 'Text'),
                $this->selectTrait('data-dynamic-field', 'Dynamic Source', ['' => 'None', 'meta_description' => 'Meta Description']),
            ], ['typography', 'spacing', 'text', 'responsive']),
            $this->widget('button', 'Button', 'General', '<a data-pb-widget="button" data-pb-text="Button" data-pb-url="#" href="#" style="display:inline-flex;align-items:center;justify-content:center;border-radius:6px;background:#111827;color:#fff;padding:12px 18px;text-decoration:none;font-weight:700;">Button</a>', [
                $this->textTrait('data-pb-text', 'Text'),
                $this->textTrait('data-pb-url', 'URL'),
                $this->textTrait('data-pb-icon', 'Icon Class'),
                $this->selectTrait('data-pb-link-target', 'Target', ['_self' => 'Same Window', '_blank' => 'New Window']),
                $this->selectTrait('data-dynamic-field', 'Dynamic URL Source', ['' => 'None', 'slug' => 'Current Page Slug']),
            ], ['layout', 'typography', 'spacing', 'background', 'border', 'effects', 'responsive']),
            $this->widget('image', 'Image', 'General', '<img data-pb-widget="image" data-pb-src="https://picsum.photos/900/500" data-pb-image-action="none" src="https://picsum.photos/900/500" alt="Image" style="max-width:100%;height:auto;">', [
                $this->textTrait('data-pb-src', 'Image URL'),
                $this->textTrait('alt', 'Alt Text'),
                $this->selectTrait('data-dynamic-field', 'Dynamic Image', ['' => 'None', 'site_logo' => 'Site Logo']),
                $this->selectTrait('data-pb-object-fit', 'Object Fit', ['contain' => 'Contain', 'cover' => 'Cover', 'fill' => 'Fill']),
                $this->selectTrait('data-pb-image-action', 'Click Action', ['none' => 'None', 'link' => 'Open Link', 'lightbox' => 'Open Lightbox']),
                $this->textTrait('data-pb-link-url', 'Link URL'),
                $this->selectTrait('data-pb-link-target', 'Link Target', ['_self' => 'Same Window', '_blank' => 'New Window']),
                $this->selectTrait('data-pb-lightbox-size', 'Lightbox Size', ['contain' => 'Fit Screen', 'full' => 'Full Width']),
            ], ['layout', 'spacing', 'border', 'effects', 'responsive']),
            $this->widget('icon', 'Icon', 'General', '<span data-pb-widget="icon" data-pb-icon="star" style="display:inline-flex;width:44px;height:44px;align-items:center;justify-content:center;border-radius:999px;background:#111827;color:#fff;font-size:22px;">★</span>', [
                $this->textTrait('data-pb-icon', 'Icon'),
                $this->textTrait('data-pb-label', 'Accessible Label'),
            ], ['layout', 'typography', 'spacing', 'background', 'border']),
            $this->widget('icon_list', 'Icon List', 'General', '<ul data-pb-widget="icon_list" style="display:grid;gap:10px;list-style:none;margin:0;padding:0;"><li style="display:flex;gap:10px;align-items:center;"><span>✓</span><span>Icon list item</span></li><li style="display:flex;gap:10px;align-items:center;"><span>✓</span><span>Icon list item</span></li></ul>', [
                $this->textareaTrait('data-pb-items', 'Items'),
                $this->textTrait('data-pb-icon', 'Icon'),
            ], ['layout', 'typography', 'spacing', 'text']),
            $this->widget('video', 'Video', 'General', '<video data-pb-widget="video" controls style="width:100%;max-width:960px;"><source src="" type="video/mp4"></video>', [
                $this->textTrait('data-pb-video-url', 'Video URL'),
                $this->selectTrait('data-pb-autoplay', 'Autoplay', ['no' => 'No', 'yes' => 'Yes']),
            ], ['layout', 'spacing', 'border', 'responsive']),
            $this->widget('spacer', 'Spacer', 'General', '<div data-pb-widget="spacer" style="height:48px;"></div>', [
                $this->numberTrait('data-pb-height', 'Height'),
            ], ['layout', 'responsive']),
            $this->widget('divider', 'Divider', 'General', '<hr data-pb-widget="divider" style="border:0;border-top:1px solid #e5e7eb;margin:32px 0;">', [
                $this->selectTrait('data-pb-style', 'Line Style', ['solid' => 'Solid', 'dashed' => 'Dashed', 'dotted' => 'Dotted']),
            ], ['spacing', 'border', 'responsive']),
            $this->widget('html', 'HTML', 'General', '<div data-pb-widget="html" data-custom-html="true">Custom HTML block</div>', [
                $this->textareaTrait('data-pb-html-note', 'Admin Note'),
            ], ['layout', 'spacing', 'background']),
            $this->widget('embed', 'Embed', 'General', '<iframe data-pb-widget="embed" src="https://example.com" title="Embed" style="width:100%;height:420px;border:0;"></iframe>', [
                $this->textTrait('src', 'Embed URL'),
                $this->numberTrait('data-pb-height', 'Height'),
            ], ['layout', 'spacing', 'border', 'responsive']),
            $this->widget('map', 'Map', 'General', '<iframe data-pb-widget="map" title="Map" src="https://www.openstreetmap.org/export/embed.html?bbox=35.8%2C31.8%2C36.1%2C32.1&layer=mapnik" style="width:100%;height:360px;border:0;"></iframe>', [
                $this->textTrait('src', 'Map Embed URL'),
                $this->numberTrait('data-pb-height', 'Height'),
            ], ['layout', 'spacing', 'border']),
            $this->widget('accordion', 'Accordion', 'General', '<div data-pb-widget="accordion" class="pb-accordion"><details open style="border:1px solid #e5e7eb;padding:16px;"><summary style="font-weight:700;">Accordion item</summary><p>Accordion content.</p></details><details style="border:1px solid #e5e7eb;padding:16px;margin-top:8px;"><summary style="font-weight:700;">Second item</summary><p>More content.</p></details></div>', [
                $this->numberTrait('data-pb-items', 'Items'),
                $this->selectTrait('data-pb-first-open', 'First Open', ['yes' => 'Yes', 'no' => 'No']),
            ], ['typography', 'spacing', 'background', 'border']),
            $this->widget('tabs', 'Tabs', 'General', '<div data-pb-widget="tabs" class="pb-tabs"><div style="display:flex;gap:8px;border-bottom:1px solid #e5e7eb;"><button type="button">Tab 1</button><button type="button">Tab 2</button></div><div style="padding:16px 0;"><h3>Tab content</h3><p>Edit this content.</p></div></div>', [
                $this->numberTrait('data-pb-tabs', 'Tabs'),
                $this->selectTrait('data-pb-orientation', 'Orientation', ['horizontal' => 'Horizontal', 'vertical' => 'Vertical']),
            ], ['layout', 'typography', 'spacing', 'border']),
            $this->widget('slider', 'Slider', 'General', '<div data-pb-widget="slider" class="pb-slider" style="display:grid;gap:12px;"><div style="background:#f3f4f6;padding:48px;text-align:center;">Slide 1</div><div style="display:flex;gap:8px;justify-content:center;"><span>●</span><span>○</span><span>○</span></div></div>', [
                $this->numberTrait('data-pb-slides', 'Slides'),
                $this->selectTrait('data-pb-autoplay', 'Autoplay', ['no' => 'No', 'yes' => 'Yes']),
            ], ['layout', 'spacing', 'background', 'responsive']),
            $this->widget('gallery', 'Gallery', 'General', '<div data-pb-widget="gallery" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;"><img src="https://picsum.photos/300/220?1" alt=""><img src="https://picsum.photos/300/220?2" alt=""><img src="https://picsum.photos/300/220?3" alt=""></div>', [
                $this->numberTrait('data-pb-columns', 'Columns'),
                $this->selectTrait('data-pb-crop', 'Crop', ['cover' => 'Cover', 'contain' => 'Contain']),
            ], ['layout', 'spacing', 'border', 'responsive']),
            $this->widget('gallery_carousel', 'Gallery Carousel', 'General', '<div data-pb-widget="gallery_carousel" style="display:flex;gap:12px;overflow:auto;"><img src="https://picsum.photos/420/260?1" alt="" style="width:320px;object-fit:cover;"><img src="https://picsum.photos/420/260?2" alt="" style="width:320px;object-fit:cover;"><img src="https://picsum.photos/420/260?3" alt="" style="width:320px;object-fit:cover;"></div>', [
                $this->textareaTrait('data-pb-items', 'Images'),
                $this->selectTrait('data-pb-autoplay', 'Autoplay', ['no' => 'No', 'yes' => 'Yes']),
            ], ['layout', 'spacing', 'border', 'responsive']),
            $this->widget('card', 'Card', 'General', '<article data-pb-widget="card" style="border:1px solid #e5e7eb;border-radius:8px;min-height:120px;padding:20px;"></article>', [
                $this->textTrait('data-pb-title', 'Title'),
                $this->selectTrait('data-pb-media', 'Media', ['none' => 'None', 'image' => 'Image']),
            ], ['layout', 'typography', 'spacing', 'background', 'border', 'effects']),
            $this->widget('list', 'List', 'General', '<ul data-pb-widget="list"><li>List item</li><li>List item</li><li>List item</li></ul>', [
                $this->selectTrait('data-pb-list-style', 'List Style', ['disc' => 'Disc', 'number' => 'Number', 'none' => 'None']),
            ], ['typography', 'spacing', 'text']),
            $this->widget('table', 'Table', 'General', '<table data-pb-widget="table" style="width:100%;border-collapse:collapse;"><thead><tr><th style="border:1px solid #e5e7eb;padding:8px;">Heading</th><th style="border:1px solid #e5e7eb;padding:8px;">Heading</th></tr></thead><tbody><tr><td style="border:1px solid #e5e7eb;padding:8px;">Cell</td><td style="border:1px solid #e5e7eb;padding:8px;">Cell</td></tr></tbody></table>', [
                $this->numberTrait('data-pb-columns', 'Columns'),
                $this->numberTrait('data-pb-rows', 'Rows'),
            ], ['typography', 'spacing', 'border', 'background']),
            $this->widget('form', 'Form', 'General', '<form data-pb-widget="form" style="display:grid;gap:12px;max-width:560px;"><input placeholder="Name" style="padding:12px;border:1px solid #d1d5db;border-radius:6px;"><input placeholder="Email" style="padding:12px;border:1px solid #d1d5db;border-radius:6px;"><button type="submit" style="padding:12px;border:0;border-radius:6px;background:#111827;color:#fff;">Submit</button></form>', [
                $this->textTrait('data-pb-form-key', 'Form Key'),
                $this->textTrait('data-pb-submit-label', 'Submit Label'),
                $this->selectTrait('data-pb-submit-action', 'Submit Action', ['database' => 'Save to Database', 'email' => 'Email', 'webhook' => 'Webhook']),
            ], ['layout', 'typography', 'spacing', 'background', 'border']),
            $this->widget('input', 'Input', 'General', '<input data-pb-widget="input" placeholder="Input" style="padding:12px;border:1px solid #d1d5db;border-radius:6px;">', [
                $this->textTrait('name', 'Field Name'),
                $this->textTrait('placeholder', 'Placeholder'),
                $this->selectTrait('type', 'Type', ['text' => 'Text', 'email' => 'Email', 'number' => 'Number', 'password' => 'Password']),
            ], ['typography', 'spacing', 'border', 'background']),
            $this->widget('textarea', 'Textarea', 'General', '<textarea data-pb-widget="textarea" placeholder="Textarea" rows="4" style="padding:12px;border:1px solid #d1d5db;border-radius:6px;width:100%;"></textarea>', [
                $this->textTrait('name', 'Field Name'),
                $this->textTrait('placeholder', 'Placeholder'),
                $this->numberTrait('rows', 'Rows'),
            ], ['typography', 'spacing', 'border', 'background']),
            $this->widget('select', 'Select', 'General', '<select data-pb-widget="select" style="padding:12px;border:1px solid #d1d5db;border-radius:6px;"><option>Option one</option><option>Option two</option></select>', [
                $this->textTrait('name', 'Field Name'),
                $this->textareaTrait('data-pb-options', 'Options'),
            ], ['typography', 'spacing', 'border', 'background']),
            $this->widget('checkbox', 'Checkbox', 'General', '<label data-pb-widget="checkbox" style="display:flex;align-items:center;gap:8px;"><input type="checkbox"> Checkbox label</label>', [
                $this->textTrait('data-pb-label', 'Label'),
                $this->textTrait('name', 'Field Name'),
            ], ['typography', 'spacing']),
            $this->widget('radio', 'Radio', 'General', '<label data-pb-widget="radio" style="display:flex;align-items:center;gap:8px;"><input type="radio" name="radio-group"> Radio label</label>', [
                $this->textTrait('data-pb-label', 'Label'),
                $this->textTrait('name', 'Group Name'),
            ], ['typography', 'spacing']),
            $this->widget('file-upload', 'File Upload', 'General', '<input data-pb-widget="file-upload" type="file" style="padding:12px;border:1px solid #d1d5db;border-radius:6px;">', [
                $this->textTrait('name', 'Field Name'),
                $this->selectTrait('data-pb-multiple', 'Multiple', ['no' => 'No', 'yes' => 'Yes']),
            ], ['spacing', 'border', 'background']),
            $this->widget('alert', 'Alert', 'General', '<div data-pb-widget="alert" role="alert" style="border:1px solid #bfdbfe;background:#eff6ff;color:#1e3a8a;border-radius:6px;padding:14px;">Alert message</div>', [
                $this->textareaTrait('data-pb-text', 'Message'),
                $this->selectTrait('data-pb-alert-type', 'Type', ['info' => 'Info', 'success' => 'Success', 'warning' => 'Warning', 'danger' => 'Danger']),
            ], ['typography', 'spacing', 'background', 'border']),
            $this->widget('badge', 'Badge', 'General', '<span data-pb-widget="badge" style="display:inline-flex;border-radius:999px;background:#e5e7eb;color:#111827;padding:4px 10px;font-size:12px;font-weight:700;">Badge</span>', [
                $this->textTrait('data-pb-text', 'Text'),
                $this->selectTrait('data-pb-tone', 'Tone', ['neutral' => 'Neutral', 'primary' => 'Primary', 'success' => 'Success']),
            ], ['typography', 'spacing', 'background', 'border']),
            $this->widget('progress-bar', 'Progress Bar', 'General', '<div data-pb-widget="progress-bar" style="background:#e5e7eb;border-radius:999px;overflow:hidden;"><div style="width:65%;background:#2563eb;color:#fff;padding:6px 10px;text-align:right;">65%</div></div>', [
                $this->numberTrait('data-pb-value', 'Value'),
                $this->textTrait('data-pb-label', 'Label'),
            ], ['layout', 'typography', 'spacing', 'background', 'border']),
            $this->widget('counter', 'Counter', 'General', '<div data-pb-widget="counter" data-pb-value="1250" style="font-size:40px;font-weight:800;">1,250</div>', [
                $this->numberTrait('data-pb-value', 'Value'),
                $this->textTrait('data-pb-prefix', 'Prefix'),
                $this->textTrait('data-pb-suffix', 'Suffix'),
            ], ['typography', 'spacing', 'text']),
            $this->widget('testimonial', 'Testimonial', 'General', '<blockquote data-pb-widget="testimonial" style="border-left:4px solid #111827;padding-left:18px;"><p>Testimonial quote goes here.</p><footer style="font-weight:700;">Customer Name</footer></blockquote>', [
                $this->textareaTrait('data-pb-quote', 'Quote'),
                $this->textTrait('data-pb-author', 'Author'),
                $this->textTrait('data-pb-role', 'Role'),
            ], ['typography', 'spacing', 'background', 'border']),
            $this->widget('faq', 'FAQ', 'General', '<section data-pb-widget="faq"><h2>FAQ</h2><details open><summary>Question?</summary><p>Answer text.</p></details><details><summary>Question?</summary><p>Answer text.</p></details></section>', [
                $this->numberTrait('data-pb-items', 'Questions'),
                $this->textTrait('data-pb-title', 'Title'),
            ], ['typography', 'spacing', 'background']),
            $this->widget('pricing-table', 'Pricing Table', 'General', '<div data-pb-widget="pricing-table" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;"><article style="border:1px solid #e5e7eb;padding:20px;border-radius:8px;"><h3>Basic</h3><p style="font-size:32px;font-weight:800;">$19</p><a href="#">Choose</a></article><article style="border:2px solid #111827;padding:20px;border-radius:8px;"><h3>Pro</h3><p style="font-size:32px;font-weight:800;">$49</p><a href="#">Choose</a></article><article style="border:1px solid #e5e7eb;padding:20px;border-radius:8px;"><h3>Team</h3><p style="font-size:32px;font-weight:800;">$99</p><a href="#">Choose</a></article></div>', [
                $this->numberTrait('data-pb-plans', 'Plans'),
                $this->selectTrait('data-pb-featured', 'Featured Plan', ['none' => 'None', '1' => 'First', '2' => 'Second', '3' => 'Third']),
            ], ['layout', 'typography', 'spacing', 'background', 'border', 'responsive']),
            $this->widget('call-to-action', 'Call To Action', 'General', '<section data-pb-widget="call-to-action" style="background:#111827;color:#fff;padding:48px 32px;text-align:center;"><h2>Call to action</h2><p>Invite visitors to take the next step.</p><a href="#" style="display:inline-flex;margin-top:12px;background:#fff;color:#111827;padding:12px 18px;border-radius:6px;text-decoration:none;font-weight:700;">Start now</a></section>', [
                $this->textTrait('data-pb-title', 'Title'),
                $this->textareaTrait('data-pb-text', 'Text'),
                $this->textTrait('data-pb-button-text', 'Button Text'),
                $this->textTrait('data-pb-button-url', 'Button URL'),
            ], ['layout', 'typography', 'spacing', 'background', 'border', 'responsive']),

            $this->widget('logo', 'Logo', 'Header & Footer', '<a data-pb-widget="logo" href="/" data-platform-logo="site" style="display:inline-flex;align-items:center;font-size:24px;font-weight:800;text-decoration:none;color:#111827;">Site Logo</a>', [
                $this->selectTrait('data-platform-logo', 'Logo Source', ['site' => 'Site Logo From DB']),
                $this->textTrait('href', 'Link'),
                $this->numberTrait('data-pb-max-height', 'Max Height'),
            ], ['layout', 'spacing', 'typography', 'responsive']),
            $this->widget('menu', 'Menu', 'Header & Footer', '<nav data-pb-widget="menu" data-platform-menu-key="" style="display:flex;gap:18px;align-items:center;"><a href="#">Database menu</a></nav>', [
                $this->dynamicSelectTrait('data-platform-menu-key', 'Menu', 'menus'),
                $this->selectTrait('data-pb-orientation', 'Orientation', ['horizontal' => 'Horizontal', 'vertical' => 'Vertical']),
                $this->numberTrait('data-pb-gap', 'Item Gap'),
                $this->selectTrait('data-pb-mobile', 'Mobile Behavior', ['wrap' => 'Wrap', 'collapse' => 'Collapse']),
            ], ['layout', 'typography', 'spacing', 'background', 'border', 'effects', 'responsive']),
            $this->widget('mobile-menu', 'Mobile Menu', 'Header & Footer', '<button data-pb-widget="mobile-menu" type="button" aria-label="Open menu" style="border:1px solid #d1d5db;border-radius:6px;background:#fff;padding:10px;">Menu</button>', [
                $this->dynamicSelectTrait('data-platform-menu-key', 'Menu', 'menus'),
                $this->textTrait('data-pb-label', 'Button Label'),
            ], ['typography', 'spacing', 'background', 'border']),
            $this->widget('language-switcher', 'Language Switcher', 'Header & Footer', '<select data-pb-widget="language-switcher" aria-label="Language" style="padding:8px;border:1px solid #d1d5db;border-radius:6px;"><option>Arabic</option><option>English</option></select>', [
                $this->textareaTrait('data-pb-languages', 'Languages'),
            ], ['typography', 'spacing', 'border', 'background']),
            $this->widget('login-button', 'Login Button', 'Header & Footer', '<a data-pb-widget="login-button" href="/login" style="display:inline-flex;border-radius:6px;background:#111827;color:#fff;padding:10px 14px;text-decoration:none;font-weight:700;">Login</a>', [
                $this->textTrait('data-pb-text', 'Text'),
                $this->textTrait('href', 'Login URL'),
            ], ['typography', 'spacing', 'background', 'border', 'effects']),
            $this->widget('user-dropdown', 'User Dropdown', 'Header & Footer', '<div data-pb-widget="user-dropdown" style="position:relative;display:inline-block;"><button type="button" style="border:1px solid #d1d5db;border-radius:6px;background:#fff;padding:10px 14px;">User</button><div style="margin-top:8px;border:1px solid #e5e7eb;padding:10px;background:#fff;"><a href="/account">Account</a></div></div>', [
                $this->textTrait('data-pb-label', 'Button Label'),
                $this->textTrait('data-pb-account-url', 'Account URL'),
            ], ['typography', 'spacing', 'background', 'border']),
            $this->widget('search', 'Search', 'Header & Footer', '<form data-pb-widget="search" role="search" style="display:flex;gap:8px;"><input placeholder="Search" style="padding:10px;border:1px solid #d1d5db;border-radius:6px;"><button type="submit">Search</button></form>', [
                $this->textTrait('data-pb-placeholder', 'Placeholder'),
                $this->textTrait('data-pb-action', 'Search Action'),
            ], ['layout', 'typography', 'spacing', 'border', 'background']),
            $this->widget('social-icons', 'Social Icons', 'Header & Footer', '<div data-pb-widget="social-icons" style="display:flex;gap:10px;"><a href="#">Facebook</a><a href="#">Instagram</a><a href="#">X</a></div>', [
                $this->textareaTrait('data-pb-social-links', 'Social Links'),
                $this->selectTrait('data-pb-shape', 'Shape', ['text' => 'Text', 'circle' => 'Circle', 'square' => 'Square']),
            ], ['layout', 'typography', 'spacing', 'background', 'border']),
            $this->widget('contact-info', 'Contact Info', 'Header & Footer', '<address data-pb-widget="contact-info" style="font-style:normal;"><div>Email: hello@example.com</div><div>Phone: +000 000 000</div></address>', [
                $this->textTrait('data-pb-email', 'Email'),
                $this->textTrait('data-pb-phone', 'Phone'),
            ], ['typography', 'spacing', 'text']),
            $this->widget('copyright', 'Copyright', 'Header & Footer', '<p data-pb-widget="copyright">Copyright 2026 Your Brand. All rights reserved.</p>', [
                $this->textTrait('data-pb-text', 'Text'),
            ], ['typography', 'spacing', 'text']),
            $this->widget('footer-menu', 'Footer Menu', 'Header & Footer', '<nav data-pb-widget="footer-menu" style="display:flex;flex-wrap:wrap;gap:14px;"><a href="#">Privacy</a><a href="#">Terms</a><a href="#">Support</a></nav>', [
                $this->dynamicSelectTrait('data-platform-menu-key', 'Menu', 'menus'),
                $this->selectTrait('data-pb-orientation', 'Orientation', ['horizontal' => 'Horizontal', 'vertical' => 'Vertical']),
            ], ['layout', 'typography', 'spacing']),
            $this->widget('newsletter-form', 'Newsletter Form', 'Header & Footer', '<form data-pb-widget="newsletter-form" style="display:flex;gap:8px;max-width:520px;"><input type="email" placeholder="Email address" style="flex:1;padding:12px;border:1px solid #d1d5db;border-radius:6px;"><button type="submit" style="padding:12px 16px;border:0;border-radius:6px;background:#111827;color:#fff;">Subscribe</button></form>', [
                $this->textTrait('data-pb-placeholder', 'Placeholder'),
                $this->textTrait('data-pb-submit-label', 'Submit Label'),
                $this->selectTrait('data-pb-storage', 'Storage', ['database' => 'Database', 'email' => 'Email']),
            ], ['layout', 'typography', 'spacing', 'background', 'border']),

            $this->widget('dynamic-title', 'Dynamic Title', 'Dynamic Content', '<h1 data-pb-widget="dynamic-title" data-dynamic-field="title">Current page title</h1>', [
                $this->selectTrait('data-dynamic-field', 'Field', ['title' => 'Title', 'seo_title' => 'SEO Title', 'slug' => 'Slug']),
            ], ['typography', 'spacing', 'text']),
            $this->widget('dynamic-content', 'Dynamic Content', 'Dynamic Content', '<div data-pb-widget="dynamic-content" data-dynamic-field="content">Current page content</div>', [
                $this->selectTrait('data-dynamic-field', 'Field', ['content' => 'Content', 'meta_description' => 'Meta Description']),
            ], ['typography', 'spacing']),
            $this->widget('dynamic-image', 'Dynamic Image', 'Dynamic Content', '<img data-pb-widget="dynamic-image" data-dynamic-field="site_logo" data-pb-image-action="none" src="https://picsum.photos/900/500" alt="Dynamic image" style="max-width:100%;height:auto;">', [
                $this->selectTrait('data-dynamic-field', 'Image Source', ['site_logo' => 'Site Logo']),
                $this->textTrait('alt', 'Alt Text'),
                $this->selectTrait('data-pb-image-action', 'Click Action', ['none' => 'None', 'link' => 'Open Link', 'lightbox' => 'Open Lightbox']),
                $this->textTrait('data-pb-link-url', 'Link URL'),
                $this->selectTrait('data-pb-link-target', 'Link Target', ['_self' => 'Same Window', '_blank' => 'New Window']),
                $this->selectTrait('data-pb-lightbox-size', 'Lightbox Size', ['contain' => 'Fit Screen', 'full' => 'Full Width']),
            ], ['layout', 'spacing', 'border', 'responsive']),
            $this->widget('dynamic-button', 'Dynamic Button', 'Dynamic Content', '<a data-pb-widget="dynamic-button" data-dynamic-field="slug" href="#" style="display:inline-flex;border-radius:6px;background:#111827;color:#fff;padding:12px 18px;text-decoration:none;font-weight:700;">Dynamic Button</a>', [
                $this->selectTrait('data-dynamic-field', 'URL Source', ['slug' => 'Page Slug']),
                $this->textTrait('data-pb-text', 'Text'),
            ], ['typography', 'spacing', 'background', 'border']),
            $this->widget('dynamic-list', 'Dynamic List', 'Dynamic Content', '<ul data-pb-widget="dynamic-list" data-dynamic-list="pages"><li>Dynamic item</li></ul>', [
                $this->selectTrait('data-dynamic-list', 'Source', ['pages' => 'Pages', 'blocks' => 'Blocks']),
            ], ['typography', 'spacing']),
            $this->widget('dynamic-cards', 'Dynamic Cards', 'Dynamic Content', '<div data-pb-widget="dynamic-cards" data-dynamic-cards="pages" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;"><article style="border:1px solid #e5e7eb;padding:16px;border-radius:8px;"><h3>Dynamic title</h3><p>Dynamic summary</p></article></div>', [
                $this->selectTrait('data-dynamic-cards', 'Source', ['pages' => 'Pages', 'blocks' => 'Blocks']),
                $this->numberTrait('data-pb-columns', 'Columns'),
            ], ['layout', 'typography', 'spacing', 'background', 'border', 'responsive']),
            $this->widget('dynamic-repeater', 'Dynamic Repeater', 'Dynamic Content', '<div data-pb-widget="dynamic-repeater" data-dynamic-repeater="pages"><div>Dynamic item content</div></div>', [
                $this->selectTrait('data-dynamic-repeater', 'Source', ['pages' => 'Pages', 'blocks' => 'Blocks']),
            ], ['layout', 'spacing']),
            $this->widget('dynamic-custom-field', 'Dynamic Custom Field', 'Dynamic Content', '<span data-pb-widget="dynamic-custom-field" data-dynamic-custom-field="field_key">Custom field</span>', [
                $this->textTrait('data-dynamic-custom-field', 'Field Key'),
            ], ['typography', 'spacing', 'text']),
            $this->widget('dynamic-breadcrumb', 'Dynamic Breadcrumb', 'Dynamic Content', '<nav data-pb-widget="dynamic-breadcrumb" aria-label="Breadcrumb" data-dynamic-breadcrumb><a href="/">Home</a> / <span>Current Page</span></nav>', [
                $this->textTrait('data-pb-home-label', 'Home Label'),
            ], ['typography', 'spacing']),
            $this->widget('dynamic-seo-meta', 'Dynamic SEO Meta', 'Dynamic Content', '<div data-pb-widget="dynamic-seo-meta" data-dynamic-seo-meta style="display:none;">SEO meta placeholder</div>', [
                $this->selectTrait('data-dynamic-field', 'Field', ['seo_title' => 'SEO Title', 'meta_description' => 'Meta Description']),
            ], ['advanced']),
        ];

        return array_values(array_merge($coreWidgets, $this->pluginWidgets()));
    }

    /**
     * @param array<int, array{id: string, label: string, category: string, content: string}> $savedBlocks
     * @return array<int, array<string, mixed>>
     */
    public function blocks(array $savedBlocks = []): array
    {
        return $this->schemaFirstRegistry()->blocks();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function elementRegistry(): array
    {
        return $this->schemaFirstRegistry()->elementRegistry();

        $control = fn (
            string $key,
            string $label,
            string $tab,
            string $group,
            string $type,
            mixed $default = '',
            bool $responsive = false,
            ?string $cssProperty = null,
            string $target = 'wrapper',
            ?array $condition = null,
            string $sanitize = 'string'
        ): array => [
            'key' => $key,
            'label' => $label,
            'tab' => $tab,
            'group' => $group,
            'type' => $type,
            'default' => $default,
            'responsive' => $responsive,
            'cssProperty' => $cssProperty,
            'target' => $target,
            'condition' => $condition,
            'sanitize' => $sanitize,
        ];

        $advanced = fn (bool $dimensions = true): array => array_values(array_filter([
            $control('element_id', 'Element ID', 'advanced', 'Identity', 'text', '', false, null, 'wrapper', null, 'html_id'),
            $control('css_classes', 'CSS Classes', 'advanced', 'Identity', 'text', '', false, null, 'wrapper', null, 'class_list'),
            $control('custom_attributes', 'Custom Attributes', 'advanced', 'Identity', 'textarea', '', false, null, 'wrapper', null, 'attributes'),
            $control('anchor_id', 'Anchor ID', 'advanced', 'Navigation', 'text', '', false, null, 'wrapper', null, 'html_id'),
            $control('margin', 'Margin', 'advanced', 'Spacing', 'spacing', '', true, 'margin', 'wrapper', null, 'css_size_group'),
            $control('padding', 'Padding', 'advanced', 'Spacing', 'spacing', '', true, 'padding', 'wrapper', null, 'css_size_group'),
            $dimensions ? $control('width', 'Width', 'advanced', 'Size', 'text', '', true, 'width', 'wrapper', null, 'css_size') : null,
            $dimensions ? $control('max_width', 'Max Width', 'advanced', 'Size', 'text', '', true, 'max-width', 'wrapper', null, 'css_size') : null,
            $dimensions ? $control('min_width', 'Min Width', 'advanced', 'Size', 'text', '', true, 'min-width', 'wrapper', null, 'css_size') : null,
            $dimensions ? $control('height', 'Height', 'advanced', 'Size', 'text', '', true, 'height', 'wrapper', null, 'css_size') : null,
            $dimensions ? $control('min_height', 'Min Height', 'advanced', 'Size', 'text', '', true, 'min-height', 'wrapper', null, 'css_size') : null,
            $control('position', 'Position', 'advanced', 'Position', 'select', 'static', true, 'position', 'wrapper', null, 'position'),
            $control('z_index', 'Z Index', 'advanced', 'Position', 'number', '', true, 'z-index', 'wrapper', null, 'integer'),
            $control('overflow', 'Overflow', 'advanced', 'Position', 'select', 'visible', false, 'overflow', 'wrapper', null, 'overflow'),
            $control('responsive_visibility', 'Responsive Visibility', 'advanced', 'Responsive', 'select', 'all', true, null, 'wrapper', null, 'visibility'),
            $control('custom_css', 'Custom CSS', 'advanced', 'Custom', 'textarea', '', false, null, 'wrapper', ['key' => 'custom_css_enabled', 'operator' => 'equals', 'value' => true], 'css'),
        ]));

        $textStyle = [
            $control('typography', 'Typography', 'style', 'Typography', 'typography', '', true, null, 'content', null, 'typography'),
            $control('text_color', 'Text Color', 'style', 'Typography', 'color', '', true, 'color', 'content', null, 'color'),
            $control('text_align', 'Text Align', 'style', 'Typography', 'select', '', true, 'text-align', 'content', null, 'text_align'),
        ];

        $backgroundBorder = [
            $control('background_color', 'Background Color', 'style', 'Background', 'color', '', true, 'background-color', 'wrapper', null, 'color'),
            $control('border', 'Border', 'style', 'Border', 'border', '', true, 'border', 'wrapper', null, 'border'),
            $control('border_radius', 'Border Radius', 'style', 'Border', 'text', '', true, 'border-radius', 'wrapper', null, 'css_size'),
            $control('box_shadow', 'Box Shadow', 'style', 'Effects', 'text', '', true, 'box-shadow', 'wrapper', null, 'box_shadow'),
        ];

        $coreRegistry = [
            'container' => [
                'label' => 'Container',
                'tabs' => ['general', 'style', 'advanced', 'special'],
                'controls' => array_merge([
                    $control('semantic_tag', 'Semantic Tag', 'general', 'Semantic HTML', 'select', 'div', false, null, 'wrapper', null, 'html_tag'),
                    $control('layout_display', 'Display', 'style', 'Layout', 'select', 'flex', true, 'display', 'wrapper', null, 'display'),
                    $control('flex_direction', 'Direction', 'style', 'Layout', 'select', 'row', true, 'flex-direction', 'wrapper', null, 'flex_direction'),
                    $control('justify_content', 'Justify Content', 'style', 'Layout', 'select', 'flex-start', true, 'justify-content', 'wrapper', null, 'justify_content'),
                    $control('align_items', 'Align Items', 'style', 'Layout', 'select', 'stretch', true, 'align-items', 'wrapper', null, 'align_items'),
                    $control('gap', 'Gap', 'style', 'Layout', 'text', '', true, 'gap', 'wrapper', null, 'css_size'),
                    $control('children_wrap', 'Children Wrap', 'special', 'Children', 'select', 'wrap', true, 'flex-wrap', 'wrapper', null, 'flex_wrap'),
                ], $backgroundBorder, $advanced()),
            ],
            'box' => [
                'label' => 'Box',
                'tabs' => ['general', 'style', 'advanced', 'special'],
                'controls' => array_merge([
                    $control('semantic_tag', 'Semantic Tag', 'general', 'Semantic HTML', 'select', 'div', false, null, 'wrapper', null, 'html_tag'),
                    $control('box_label', 'Admin Label', 'general', 'Content', 'text', '', false, null, 'wrapper', null, 'plain_text'),
                    $control('children_layout', 'Children Layout', 'special', 'Children', 'select', 'stack', true, null, 'wrapper', null, 'layout_mode'),
                    $control('children_gap', 'Children Gap', 'special', 'Children', 'text', '', true, 'gap', 'wrapper', null, 'css_size'),
                ], $backgroundBorder, $advanced()),
            ],
            'heading' => [
                'label' => 'Heading',
                'tabs' => ['general', 'style', 'advanced', 'special'],
                'controls' => array_merge([
                    $control('text', 'Heading Text', 'general', 'Content', 'text', 'Heading text', false, null, 'content', null, 'plain_text'),
                    $control('semantic_tag', 'HTML Tag', 'general', 'Semantic HTML', 'select', 'h2', false, null, 'content', null, 'heading_tag'),
                    $control('dynamic_source', 'Dynamic Source', 'general', 'Data', 'select', '', false, null, 'content', null, 'dynamic_field'),
                ], $textStyle, [
                    $control('link_url', 'Link URL', 'general', 'Link Behavior', 'text', '', false, null, 'content', null, 'url'),
                    $control('hover_color', 'Hover Color', 'style', 'States', 'color', '', true, 'color', 'content:hover', null, 'color'),
                ], $advanced()),
            ],
            'text' => [
                'label' => 'Text',
                'tabs' => ['general', 'style', 'advanced', 'special'],
                'controls' => array_merge([
                    $control('rich_text', 'Rich Text Editor', 'general', 'Content', 'richtext', '', false, null, 'content', null, 'html_fragment'),
                    $control('semantic_tag', 'Semantic Tag', 'general', 'Semantic HTML', 'select', 'p', false, null, 'content', null, 'text_tag'),
                    $control('link_color', 'Link Color', 'style', 'Links', 'color', '', true, 'color', 'content a', null, 'color'),
                    $control('columns', 'Columns', 'style', 'Text Layout', 'number', '', true, 'column-count', 'content', null, 'integer'),
                    $control('drop_cap', 'Drop Cap', 'special', 'Text Effects', 'switch', false, false, null, 'content', null, 'boolean'),
                ], $textStyle, $advanced()),
            ],
            'image' => [
                'label' => 'Image',
                'tabs' => ['general', 'style', 'advanced', 'special'],
                'controls' => array_merge([
                    $control('media_library', 'Media Library', 'general', 'Media', 'media', '', false, null, 'image', null, 'media_url'),
                    $control('src', 'Image URL', 'general', 'Media', 'text', '', false, null, 'image', null, 'media_url'),
                    $control('alt', 'Alt Text', 'general', 'Media', 'text', '', false, null, 'image', null, 'plain_text'),
                    $control('caption', 'Caption', 'general', 'Content', 'text', '', false, null, 'content', null, 'plain_text'),
                    $control('image_size', 'Image Size', 'general', 'Media', 'select', 'full', false, null, 'image', null, 'image_size'),
                    $control('object_fit', 'Object Fit', 'style', 'Image', 'select', 'cover', true, 'object-fit', 'image', null, 'object_fit'),
                    $control('object_position', 'Object Position', 'style', 'Image', 'text', 'center center', true, 'object-position', 'image', null, 'object_position'),
                    $control('link_type', 'Link Type', 'general', 'Link Behavior', 'select', 'none', false, null, 'image', null, 'image_link_type'),
                    $control('link_url', 'Link URL', 'general', 'Link Behavior', 'text', '', false, null, 'image', ['key' => 'link_type', 'operator' => 'equals', 'value' => 'custom'], 'url'),
                    $control('lightbox', 'Lightbox', 'special', 'Media Behavior', 'switch', false, false, null, 'image', ['key' => 'link_type', 'operator' => 'equals', 'value' => 'media_file'], 'boolean'),
                    $control('lightbox_size', 'Lightbox Size', 'special', 'Media Behavior', 'select', 'contain', false, null, 'image', ['key' => 'link_type', 'operator' => 'equals', 'value' => 'media_file'], 'lightbox_size'),
                ], [
                    $control('image_width', 'Image Width', 'style', 'Image', 'text', '', true, 'width', 'image', null, 'css_size'),
                    $control('image_border_radius', 'Image Radius', 'style', 'Image', 'text', '', true, 'border-radius', 'image', null, 'css_size'),
                ], $advanced()),
            ],
            'button' => [
                'label' => 'Button',
                'tabs' => ['general', 'style', 'advanced', 'special'],
                'controls' => array_merge([
                    $control('text', 'Button Text', 'general', 'Content', 'text', 'Button', false, null, 'button', null, 'plain_text'),
                    $control('url', 'URL / Action', 'general', 'Link Behavior', 'text', '#', false, null, 'button', null, 'url'),
                    $control('target', 'Target', 'general', 'Link Behavior', 'select', '_self', false, null, 'button', null, 'link_target'),
                    $control('icon', 'Icon', 'general', 'Icon', 'icon', '', false, null, 'button', null, 'icon'),
                    $control('icon_position', 'Icon Position', 'general', 'Icon', 'select', 'before', false, null, 'button', null, 'icon_position'),
                ], $textStyle, [
                    $control('button_padding', 'Button Padding', 'style', 'Button', 'spacing', '', true, 'padding', 'button', null, 'css_size_group'),
                    $control('button_background', 'Background', 'style', 'Button', 'color', '', true, 'background-color', 'button', null, 'color'),
                    $control('button_border', 'Border', 'style', 'Button', 'border', '', true, 'border', 'button', null, 'border'),
                    $control('hover_background', 'Hover Background', 'style', 'Hover State', 'color', '', true, 'background-color', 'button:hover', null, 'color'),
                    $control('hover_color', 'Hover Text Color', 'style', 'Hover State', 'color', '', true, 'color', 'button:hover', null, 'color'),
                ], $advanced()),
            ],
            'icon' => [
                'label' => 'Icon',
                'tabs' => ['general', 'style', 'advanced', 'special'],
                'controls' => array_merge([
                    $control('icon', 'Icon', 'general', 'Icon', 'icon', 'star', false, null, 'icon', null, 'icon'),
                    $control('label', 'Accessible Label', 'general', 'Semantic HTML', 'text', '', false, null, 'icon', null, 'plain_text'),
                    $control('link_url', 'Link URL', 'general', 'Link Behavior', 'text', '', false, null, 'icon', null, 'url'),
                    $control('icon_size', 'Icon Size', 'style', 'Icon', 'text', '', true, 'font-size', 'icon', null, 'css_size'),
                    $control('icon_color', 'Icon Color', 'style', 'Icon', 'color', '', true, 'color', 'icon', null, 'color'),
                    $control('svg_media_library', 'SVG Icon Media', 'general', 'Icon', 'media', '', false, null, 'icon', null, 'media_url'),
                ], $backgroundBorder, $advanced()),
            ],
            'icon_list' => [
                'label' => 'Icon List',
                'tabs' => ['general', 'style', 'advanced', 'special'],
                'controls' => array_merge([
                    $control('items', 'Repeater Items', 'general', 'Content', 'repeater', [], false, null, 'content', null, 'repeater'),
                    $control('icon', 'Default Icon', 'general', 'Icon', 'icon', 'check', false, null, 'icon', null, 'icon'),
                    $control('gap', 'Item Gap', 'style', 'Layout', 'text', '', true, 'gap', 'wrapper', null, 'css_size'),
                    $control('icon_color', 'Icon Color', 'style', 'Icon', 'color', '', true, 'color', 'icon', null, 'color'),
                ], $textStyle, $advanced()),
            ],
            'divider' => [
                'label' => 'Divider',
                'tabs' => ['general', 'style', 'advanced', 'special'],
                'controls' => array_merge([
                    $control('semantic_tag', 'Semantic Tag', 'general', 'Semantic HTML', 'select', 'hr', false, null, 'wrapper', null, 'divider_tag'),
                    $control('line_style', 'Line Style', 'style', 'Line', 'select', 'solid', true, 'border-top-style', 'wrapper', null, 'border_style'),
                    $control('line_width', 'Line Width', 'style', 'Line', 'text', '1px', true, 'border-top-width', 'wrapper', null, 'css_size'),
                    $control('line_color', 'Line Color', 'style', 'Line', 'color', '', true, 'border-top-color', 'wrapper', null, 'color'),
                ], $advanced()),
            ],
            'spacer' => [
                'label' => 'Spacer',
                'tabs' => ['general', 'style', 'advanced'],
                'controls' => array_merge([
                    $control('height', 'Height', 'general', 'Size', 'text', '48px', true, 'height', 'wrapper', null, 'css_size'),
                ], $advanced(false)),
            ],
            'video' => [
                'label' => 'Video',
                'tabs' => ['general', 'style', 'advanced', 'special'],
                'controls' => array_merge([
                    $control('source_type', 'Source Type', 'general', 'Media', 'select', 'embed', false, null, 'content', null, 'video_source'),
                    $control('video_url', 'Video URL', 'general', 'Media', 'text', '', false, null, 'content', null, 'url'),
                    $control('media_library', 'Self-hosted Video', 'general', 'Media', 'media', '', false, null, 'content', ['key' => 'source_type', 'operator' => 'equals', 'value' => 'self_hosted'], 'media_url'),
                    $control('poster', 'Poster Image', 'general', 'Media', 'media', '', false, null, 'image', ['key' => 'source_type', 'operator' => 'equals', 'value' => 'self_hosted'], 'media_url'),
                    $control('object_fit', 'Object Fit', 'style', 'Media', 'select', 'cover', true, 'object-fit', 'content', ['key' => 'source_type', 'operator' => 'equals', 'value' => 'self_hosted'], 'object_fit'),
                    $control('autoplay', 'Autoplay', 'special', 'Playback', 'switch', false, false, null, 'content', null, 'boolean'),
                    $control('controls', 'Controls', 'special', 'Playback', 'switch', true, false, null, 'content', null, 'boolean'),
                    $control('lightbox', 'Lightbox', 'special', 'Media Behavior', 'switch', false, false, null, 'content', null, 'boolean'),
                ], $advanced()),
            ],
            'gallery_carousel' => [
                'label' => 'Gallery Carousel',
                'tabs' => ['general', 'style', 'advanced', 'special'],
                'controls' => array_merge([
                    $control('images', 'Repeater Images', 'general', 'Media', 'repeater', [], false, null, 'content', null, 'repeater'),
                    $control('media_library', 'Add Images', 'general', 'Media', 'media', '', false, null, 'image', null, 'media_url'),
                    $control('caption_source', 'Caption Source', 'general', 'Content', 'select', 'none', false, null, 'content', null, 'caption_source'),
                    $control('thumb_object_fit', 'Thumbnail Object Fit', 'style', 'Images', 'select', 'cover', true, 'object-fit', 'image', null, 'object_fit'),
                    $control('thumb_object_position', 'Thumbnail Object Position', 'style', 'Images', 'text', 'center center', true, 'object-position', 'image', null, 'object_position'),
                    $control('columns', 'Visible Columns', 'style', 'Layout', 'number', 3, true, null, 'wrapper', null, 'integer'),
                    $control('autoplay', 'Autoplay', 'special', 'Carousel', 'switch', false, false, null, 'wrapper', null, 'boolean'),
                    $control('lightbox', 'Lightbox', 'special', 'Media Behavior', 'switch', true, false, null, 'image', null, 'boolean'),
                    $control('children_slide_layout', 'Slide Children Layout', 'special', 'Children', 'select', 'stack', true, null, 'wrapper', null, 'layout_mode'),
                ], $advanced()),
            ],
        ];

        return array_replace($coreRegistry, $this->pluginElementRegistry());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function widgetMap(): array
    {
        return $this->schemaFirstRegistry()->widgetMap();
    }

    private function schemaFirstRegistry(): SchemaFirstWidgetRegistry
    {
        return app(SchemaFirstWidgetRegistry::class);
    }

    /**
     * @param array<int, array<string, mixed>> $traits
     * @param array<int, string> $styleGroups
     * @return array<string, mixed>
     */
    private function widget(string $id, string $label, string $category, string $content, array $traits = [], array $styleGroups = []): array
    {
        return [
            'id' => $id,
            'type' => 'pb-'.$id,
            'label' => $label,
            'category' => $category,
            'content' => $content,
            'traits' => $traits,
            'styleGroups' => $styleGroups,
            'module' => 'core',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function textTrait(string $name, string $label): array
    {
        return ['type' => 'text', 'name' => $name, 'label' => $label];
    }

    /**
     * @return array<string, mixed>
     */
    private function textareaTrait(string $name, string $label): array
    {
        return ['type' => 'textarea', 'name' => $name, 'label' => $label];
    }

    /**
     * @return array<string, mixed>
     */
    private function numberTrait(string $name, string $label): array
    {
        return ['type' => 'number', 'name' => $name, 'label' => $label];
    }

    /**
     * @param array<string, string> $options
     * @return array<string, mixed>
     */
    private function selectTrait(string $name, string $label, array $options): array
    {
        return [
            'type' => 'select',
            'name' => $name,
            'label' => $label,
            'options' => collect($options)->map(fn (string $optionLabel, string $value): array => [
                'value' => $value,
                'name' => $optionLabel,
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dynamicSelectTrait(string $name, string $label, string $source): array
    {
        return [
            'type' => 'select',
            'name' => $name,
            'label' => $label,
            'dynamicSource' => $source,
            'options' => [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pluginWidgets(): array
    {
        $widgets = [];

        foreach (glob(base_path('modules/*/module.json')) ?: [] as $manifestPath) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true);

            if (! is_array($manifest)) {
                continue;
            }

            $module = (string) ($manifest['slug'] ?? basename(dirname($manifestPath)));

            if (! $this->pluginAllowed($module)) {
                continue;
            }

            $declaredWidgets = $manifest['page_builder']['widgets'] ?? $manifest['widgets'] ?? [];

            if (! is_array($declaredWidgets)) {
                continue;
            }

            foreach ($declaredWidgets as $declaredWidget) {
                if (! is_array($declaredWidget)) {
                    continue;
                }

                $id = trim((string) ($declaredWidget['id'] ?? ''));
                $label = trim((string) ($declaredWidget['label'] ?? ''));
                $content = (string) ($declaredWidget['content'] ?? '');

                if ($id === '' || $label === '' || $content === '') {
                    continue;
                }

                $widgets[] = [
                    'id' => $module.'.'.$id,
                    'type' => 'pb-'.$module.'-'.$id,
                    'label' => $label,
                    'category' => (string) ($declaredWidget['category'] ?? 'Plugin Widgets'),
                    'content' => $content,
                    'traits' => is_array($declaredWidget['traits'] ?? null) ? $declaredWidget['traits'] : [],
                    'styleGroups' => is_array($declaredWidget['styleGroups'] ?? null) ? $declaredWidget['styleGroups'] : ['layout', 'spacing', 'typography'],
                    'module' => $module,
                ];
            }
        }

        return $widgets;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function pluginElementRegistry(): array
    {
        $registry = [];

        foreach (glob(base_path('modules/*/module.json')) ?: [] as $manifestPath) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true);

            if (! is_array($manifest)) {
                continue;
            }

            $module = (string) ($manifest['slug'] ?? basename(dirname($manifestPath)));

            if (! $this->pluginAllowed($module)) {
                continue;
            }

            $declaredRegistry = $manifest['page_builder']['element_registry'] ?? [];

            if (! is_array($declaredRegistry)) {
                continue;
            }

            foreach ($declaredRegistry as $key => $schema) {
                if (! is_string($key) || ! is_array($schema)) {
                    continue;
                }

                $registry[str_contains($key, '.') ? $key : $module.'.'.$key] = $schema;
            }
        }

        return $registry;
    }

    private function pluginAllowed(string $module): bool
    {
        try {
            return app(\App\Platform\Core\Services\PluginRuntimeGate::class)->allows($module);
        } catch (\Throwable) {
            return false;
        }
    }
}
