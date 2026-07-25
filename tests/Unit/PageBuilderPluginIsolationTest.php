<?php

namespace Tests\Unit;

use App\Platform\Core\PageBuilder\PageBuilderRenderService;
use App\Platform\Core\PageBuilder\TemplateEditableRenderer;
use App\Platform\Core\Rendering\PlatformContentRenderer;
use App\Platform\Core\Services\PluginRuntimeGate;
use App\Platform\Core\Services\SettingsRepository;
use Mockery;
use Tests\TestCase;

class PageBuilderPluginIsolationTest extends TestCase
{
    public function test_inactive_plugin_widget_is_removed_without_removing_core_content(): void
    {
        $html = '<section id="core-before">Before</section>'
            .'<section data-pb-widget="lms.courses-catalog"><div><p>Loading courses...</p></div></section>'
            .'<section id="core-after">After</section>';
        $renderer = Mockery::mock(PlatformContentRenderer::class);
        $renderer->shouldReceive('renderHtml')->once()->with($html)->andReturn($html);
        $gate = Mockery::mock(PluginRuntimeGate::class);
        $gate->shouldReceive('inspect')->with('lms')->once()->andReturn([
            'allowed' => false,
            'reason' => 'plugin_disabled',
        ]);

        $service = new PageBuilderRenderService(
            $renderer,
            Mockery::mock(SettingsRepository::class),
            Mockery::mock(TemplateEditableRenderer::class),
            null,
            $gate,
        );
        $output = $service->renderHtml($html);

        $this->assertStringContainsString('core-before', $output);
        $this->assertStringContainsString('core-after', $output);
        $this->assertStringNotContainsString('lms.courses-catalog', $output);
        $this->assertStringNotContainsString('Loading courses', $output);
    }

    public function test_active_plugin_widget_is_preserved(): void
    {
        $html = '<section data-pb-widget="lms.courses-catalog">Courses</section>';
        $renderer = Mockery::mock(PlatformContentRenderer::class);
        $renderer->shouldReceive('renderHtml')->once()->with($html)->andReturn($html);
        $gate = Mockery::mock(PluginRuntimeGate::class);
        $gate->shouldReceive('inspect')->with('lms')->once()->andReturn([
            'allowed' => true,
            'reason' => 'plugin_enabled',
        ]);

        $service = new PageBuilderRenderService(
            $renderer,
            Mockery::mock(SettingsRepository::class),
            Mockery::mock(TemplateEditableRenderer::class),
            null,
            $gate,
        );

        $this->assertStringContainsString('lms.courses-catalog', $service->renderHtml($html));
    }

    public function test_core_widget_namespace_is_preserved_when_it_is_not_an_installed_plugin(): void
    {
        $html = '<header data-pb-widget="theme-builder.header">Header</header>';
        $renderer = Mockery::mock(PlatformContentRenderer::class);
        $renderer->shouldReceive('renderHtml')->once()->with($html)->andReturn($html);
        $gate = Mockery::mock(PluginRuntimeGate::class);
        $gate->shouldReceive('inspect')->with('theme-builder')->once()->andReturn([
            'allowed' => false,
            'reason' => 'plugin_not_installed',
        ]);

        $service = new PageBuilderRenderService(
            $renderer,
            Mockery::mock(SettingsRepository::class),
            Mockery::mock(TemplateEditableRenderer::class),
            null,
            $gate,
        );

        $output = $service->renderHtml($html);

        $this->assertStringContainsString('theme-builder.header', $output);
        $this->assertStringContainsString('Header', $output);
    }
}
