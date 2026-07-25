<?php

namespace Tests\Unit;

use App\Platform\Core\Services\PlatformVersion;
use Tests\TestCase;

class PlatformVersionTest extends TestCase
{
    public function test_current_platform_version_satisfies_supported_plugin_contracts(): void
    {
        config()->set('platform.version', '2.0.0');
        $version = new PlatformVersion();

        $this->assertTrue($version->supports('>=2.0.0 <3.0.0'));
        $this->assertTrue($version->supports('^2.0'));
        $this->assertFalse($version->supports('>=3.0.0'));
        $this->assertFalse($version->supports('invalid-constraint'));
    }
}
