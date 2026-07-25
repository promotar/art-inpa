<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ManagedPluginAssetContractTest extends TestCase
{
    public function test_asset_entry_points_delegate_mutation_to_the_managed_filesystem(): void
    {
        $root = dirname(__DIR__, 2);

        foreach ([
            'app/Platform/Core/Assets/AssetPublisher.php',
            'app/Platform/Core/Assets/AssetRemover.php',
            'app/Platform/Core/Services/PluginAssetPublisher.php',
        ] as $relativePath) {
            $source = (string) file_get_contents($root.'/'.$relativePath);

            self::assertStringNotContainsString('unlink(', $source, $relativePath);
            self::assertStringNotContainsString('rmdir(', $source, $relativePath);
            self::assertStringNotContainsString('copy(', $source, $relativePath);
        }
    }

    public function test_permission_contract_is_explicit_for_cli_and_web_processes(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2).'/app/Platform/Core/Assets/ManagedAssetFilesystem.php',
        );

        self::assertStringContainsString('DIRECTORY_MODE = 0775', $source);
        self::assertStringContainsString('FILE_MODE = 0664', $source);
        self::assertStringContainsString('replaceFile(', $source);
        self::assertStringContainsString('prepareRemoval(', $source);
    }
}
