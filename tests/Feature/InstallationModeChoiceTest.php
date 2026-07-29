<?php

namespace Tests\Feature;

use App\Installation\InstallationState;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class InstallationModeChoiceTest extends TestCase
{
    private string $stateDirectory;

    private string $runtimePath;

    private string $environmentPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateDirectory = storage_path('framework/testing/installer-mode-choice');
        $this->runtimePath = $this->stateDirectory.'/installation.env';
        $this->environmentPath = $this->stateDirectory.'/.env';

        File::deleteDirectory($this->stateDirectory);
        File::ensureDirectoryExists($this->stateDirectory);
        File::put($this->runtimePath, "APP_KEY=\"test-key\"\nCUSTOM_KEEP=\"original-value\"\nINSTAAL_IS_ACTIVE=\"0\"\nINSTAAL_IS_ATIVE=\"0\"\n");
        File::put($this->environmentPath, "APP_NAME=\"Existing platform\"\nINSTAAL_IS_ACTIVE=\"0\"\nINSTAAL_IS_ATIVE=\"0\"\n");

        $this->app->instance(
            InstallationState::class,
            new InstallationState($this->runtimePath, $this->environmentPath),
        );
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->stateDirectory);

        parent::tearDown();
    }

    public function test_installer_starts_with_update_and_fresh_installation_choices(): void
    {
        $this->get(route('install.index'))
            ->assertOk()
            ->assertSee('Update platform')
            ->assertSee('Fresh installation')
            ->assertSee(route('install.update'), false)
            ->assertSee(route('install.fresh'), false);
    }

    public function test_fresh_wizard_cannot_be_opened_before_selecting_fresh_installation(): void
    {
        $this->get(route('install.platform'))
            ->assertRedirect(route('install.index'));
    }

    public function test_selecting_fresh_installation_continues_the_existing_wizard(): void
    {
        $this->post(route('install.fresh'))
            ->assertRedirect(route('install.platform'));

        $this->get(route('install.platform'))
            ->assertOk()
            ->assertSee('Platform identity')
            ->assertSee('Database', false)
            ->assertSee('Owner', false);
    }

    public function test_update_mode_only_changes_installation_state_flags(): void
    {
        $runtimeBefore = File::get($this->runtimePath);
        $environmentBefore = File::get($this->environmentPath);

        $this->post(route('install.update'))
            ->assertRedirect('/');

        $runtimeAfter = File::get($this->runtimePath);
        $environmentAfter = File::get($this->environmentPath);

        $this->assertSame(
            $this->withoutInstallationFlags($runtimeBefore),
            $this->withoutInstallationFlags($runtimeAfter),
        );
        $this->assertSame(
            $this->withoutInstallationFlags($environmentBefore),
            $this->withoutInstallationFlags($environmentAfter),
        );
        $this->assertStringContainsString('INSTAAL_IS_ACTIVE="1"', $runtimeAfter);
        $this->assertStringContainsString('INSTAAL_IS_ATIVE="1"', $runtimeAfter);
        $this->assertStringContainsString('INSTAAL_IS_ACTIVE="1"', $environmentAfter);
        $this->assertStringContainsString('INSTAAL_IS_ATIVE="1"', $environmentAfter);
    }

    private function withoutInstallationFlags(string $content): string
    {
        return (string) preg_replace('/^INSTAAL_IS_(?:ACTIVE|ATIVE)=.*\R?/m', '', $content);
    }
}
