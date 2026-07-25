<?php

namespace Tests\Unit;

use App\Platform\Core\PageBuilder\BuilderSanitizer;
use PHPUnit\Framework\TestCase;

class PageBuilderSanitizerTest extends TestCase
{
    public function test_it_strips_unsafe_html_for_non_super_admin_users(): void
    {
        $sanitizer = new BuilderSanitizer();

        $result = $sanitizer->sanitize(
            '<section onclick="alert(1)"><script>alert(1)</script><a href="javascript:alert(1)">Click</a></section>',
            '.hero{background:url("javascript:alert(1)");} @import url("https://example.com/a.css");',
            false,
        );

        $this->assertStringNotContainsString('<script', $result['html']);
        $this->assertStringNotContainsString('onclick', $result['html']);
        $this->assertStringNotContainsString('javascript:', $result['html']);
        $this->assertStringNotContainsString('@import', $result['css']);
        $this->assertStringNotContainsString('javascript:', $result['css']);
    }

    public function test_super_admin_override_keeps_original_builder_markup(): void
    {
        $sanitizer = new BuilderSanitizer();
        $html = '<section onclick="alert(1)"><script>alert(1)</script></section>';
        $css = '@import url("https://example.com/a.css");';

        $result = $sanitizer->sanitize($html, $css, true);

        $this->assertSame($html, $result['html']);
        $this->assertSame($css, $result['css']);
    }
}
