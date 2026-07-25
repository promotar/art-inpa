<?php

namespace Tests\Unit;

use App\Platform\Core\PageBuilder\TemplateEditableRenderer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TemplateEditableRendererTest extends TestCase
{
    public function test_it_applies_editable_data_to_template_markup(): void
    {
        $renderer = new TemplateEditableRenderer();
        $html = '<section data-pb-section="hero" data-pb-locked="true"><h1 data-pb-field="title">Default</h1><a data-pb-field="button" href="/old">Old</a><img data-pb-field="image" src="/old.png" alt=""></section>';
        $project = [
            'editable_schema' => [
                'template_key' => 'service-page-01',
                'template_name' => 'Service Page',
                'sections' => [[
                    'key' => 'hero',
                    'label' => 'Hero',
                    'locked_layout' => true,
                    'visible' => true,
                    'fields' => [
                        ['key' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Default'],
                        ['key' => 'button', 'label' => 'Button', 'type' => 'button', 'default' => ['text' => 'Old', 'url' => '/old']],
                        ['key' => 'image', 'label' => 'Image', 'type' => 'image', 'default' => ['src' => '/old.png', 'alt' => '']],
                    ],
                ]],
            ],
            'editable_data' => [
                'hero' => [
                    'title' => 'SEO Services in Jordan',
                    'button' => ['text' => 'Contact Us', 'url' => '/contact'],
                    'image' => ['src' => '/storage/media/hero.webp', 'alt' => 'SEO team'],
                ],
            ],
        ];

        $rendered = $renderer->render($html, $project);

        $this->assertStringContainsString('SEO Services in Jordan', $rendered);
        $this->assertStringContainsString('href="/contact"', $rendered);
        $this->assertStringContainsString('Contact Us', $rendered);
        $this->assertStringContainsString('src="/storage/media/hero.webp"', $rendered);
        $this->assertStringContainsString('alt="SEO team"', $rendered);
    }

    public function test_it_rejects_unsafe_editable_urls(): void
    {
        $renderer = new TemplateEditableRenderer();
        $schema = [
            'template_key' => 'service-page-01',
            'template_name' => 'Service Page',
            'sections' => [[
                'key' => 'hero',
                'label' => 'Hero',
                'fields' => [
                    ['key' => 'button', 'label' => 'Button', 'type' => 'button', 'default' => ['text' => 'Go', 'url' => '/']],
                ],
            ]],
        ];

        $this->expectException(InvalidArgumentException::class);

        $renderer->validateEditablePayload($schema, [
            'editable_data' => [
                'hero' => [
                    'button' => ['text' => 'Bad', 'url' => 'javascript:alert(1)'],
                ],
            ],
        ]);
    }
}
