<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit\Tests\Unit;

use AlexKassel\PackageAudit\Services\SkillInstaller;
use AlexKassel\PackageAudit\Tests\TestCase;

class SkillInstallerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'package-audit-installer-test-'.uniqid();
        @mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->deleteRecursive($this->tempDir);
        parent::tearDown();
    }

    public function test_it_detects_default_skills_directory(): void
    {
        $installer = new SkillInstaller;
        $dir = $installer->detectSkillsDirectory();

        $this->assertNotEmpty($dir);
        $this->assertStringContainsString('skills', $dir);
    }

    public function test_it_respects_configured_skills_directory(): void
    {
        config(['package-audit.skills_path' => '.custom/agent-skills']);

        $installer = new SkillInstaller;
        $dir = $installer->detectSkillsDirectory();

        $this->assertStringContainsString('.custom', $dir);
        $this->assertStringContainsString('agent-skills', $dir);
    }

    public function test_it_installs_skill_files_to_target_directory(): void
    {
        $installer = new SkillInstaller;
        $installed = $installer->install($this->tempDir, force: true);

        $this->assertTrue($installed);
        $this->assertFileExists($this->tempDir.'/package-audit/SKILL.md');
        $this->assertFileExists($this->tempDir.'/package-audit/references/orchestrator.md');
        $this->assertFileExists($this->tempDir.'/package-audit/references/contracts/01_architecture_api.md');
        $this->assertFileExists($this->tempDir.'/package-audit/resources/templates/audit-manifest.template.json');

        $this->assertTrue($installer->isInstalled($this->tempDir));
    }

    public function test_it_does_not_overwrite_without_force(): void
    {
        $installer = new SkillInstaller;
        $installer->install($this->tempDir, force: true);

        // Modify installed file
        file_put_contents($this->tempDir.'/package-audit/SKILL.md', 'custom content');

        // Re-install without force
        $result = $installer->install($this->tempDir, force: false);
        $this->assertFalse($result);
        $this->assertEquals('custom content', file_get_contents($this->tempDir.'/package-audit/SKILL.md'));

        // Re-install with force
        $resultForce = $installer->install($this->tempDir, force: true);
        $this->assertTrue($resultForce);
        $this->assertNotEquals('custom content', file_get_contents($this->tempDir.'/package-audit/SKILL.md'));
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
