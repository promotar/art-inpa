<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DeploymentAssetBuildContractTest extends TestCase
{
    public function test_nixpacks_build_must_produce_a_vite_manifest(): void
    {
        $config = $this->projectFile('nixpacks.toml');
        $package = json_decode($this->projectFile('package.json'), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('22.x', $package['engines']['node'] ?? null);
        self::assertStringContainsString('npm ci --include=dev', $config);
        self::assertStringContainsString('npm run build', $config);
        self::assertStringContainsString('test -s public/build/manifest.json', $config);
    }

    public function test_php_image_contains_a_verified_frontend_build(): void
    {
        $dockerfile = $this->projectFile('docker/php/Dockerfile');

        self::assertStringContainsString('FROM node:22-bookworm-slim AS vite-assets', $dockerfile);
        self::assertStringContainsString('npm ci --include=dev', $dockerfile);
        self::assertStringContainsString('test -s public/build/manifest.json', $dockerfile);
        self::assertStringContainsString(
            'COPY --from=vite-assets /build/public/build /opt/art-inpa/public/build',
            $dockerfile,
        );
    }

    public function test_runtime_restores_only_the_prebuilt_artifact_and_fails_when_it_is_missing(): void
    {
        $entrypoint = $this->projectFile('docker/php/entrypoint.sh');

        self::assertStringContainsString('/opt/art-inpa/public/build/manifest.json', $entrypoint);
        self::assertStringContainsString('cp -R /opt/art-inpa/public/build/. public/build/', $entrypoint);
        self::assertStringContainsString('The deployment image is incomplete.', $entrypoint);
        self::assertStringNotContainsString('npm install', $entrypoint);
        self::assertStringNotContainsString('npm run build', $entrypoint);
        self::assertStringNotContainsString('apt-get install', $entrypoint);
    }

    public function test_local_generated_assets_cannot_leak_into_the_image_build_context(): void
    {
        $dockerignore = $this->projectFile('.dockerignore');

        self::assertMatchesRegularExpression('/^public\/build$/m', $dockerignore);
        self::assertMatchesRegularExpression('/^public\/hot$/m', $dockerignore);
    }

    private function projectFile(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/'.$path);

        self::assertIsString($contents, $path.' must exist and be readable.');

        return $contents;
    }
}
