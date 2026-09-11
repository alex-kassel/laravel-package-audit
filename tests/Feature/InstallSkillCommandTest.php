<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit\Tests\Feature;

use AlexKassel\PackageAudit\Tests\TestCase;

class InstallSkillCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'package-audit-cmd-test-'.uniqid();
        @mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->deleteRecursive($this->tempDir);
        parent::tearDown();
    }

    public function test_it_installs_skill_via_artisan_command(): void
    {
        $this->artisan('package:audit:install', ['--path' => $this->tempDir])
            ->expectsOutputToContain('Package Audit Skill successfully installed')
            ->assertSuccessful();

        $this->assertFileExists($this->tempDir.'/package-audit/SKILL.md');
        $this->assertFileExists($this->tempDir.'/package-audit/references/contracts/01_architecture_api.md');
    }

    public function test_it_informs_when_already_installed_without_force(): void
    {
        // First run
        $this->artisan('package:audit:install', ['--path' => $this->tempDir])
            ->assertSuccessful();

        // Second run without force
        $this->artisan('package:audit:install', ['--path' => $this->tempDir])
            ->expectsOutputToContain('is already installed')
            ->assertSuccessful();

        // Third run with force
        $this->artisan('package:audit:install', ['--path' => $this->tempDir, '--force' => true])
            ->expectsOutputToContain('Package Audit Skill successfully installed')
            ->assertSuccessful();
    }

    private function deleteRecursive(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            is_dir($path) ? $this->deleteRecursive($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
