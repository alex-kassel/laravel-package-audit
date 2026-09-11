<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use AlexKassel\PackageAudit\Services\AuditRunner;
use AlexKassel\PackageAudit\Tests\TestCase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class AuditRunnerTest extends TestCase
{
    protected string $tempPkg;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempPkg = sys_get_temp_dir().DIRECTORY_SEPARATOR.'audit_runner_test_'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($this->tempPkg);

        // Seed basic git directory
        File::ensureDirectoryExists($this->tempPkg.DIRECTORY_SEPARATOR.'.git');

        // Seed valid composer.json
        File::put($this->tempPkg.DIRECTORY_SEPARATOR.'composer.json', json_encode([
            'name' => 'alex-kassel/test-fixture',
            'version' => '1.0.0',
            'require' => ['php' => '^8.2'],
        ], JSON_PRETTY_PRINT));

        // Seed valid README.md
        File::put($this->tempPkg.DIRECTORY_SEPARATOR.'README.md', "# Test Package\n\n## Requirements\nPHP 8.2\n\n## Installation\ncomposer require\n\n## Usage\nuse it\n\n## Testing\nrun tests\n\n## License\nMIT\n");

        // Seed valid .gitattributes
        File::put($this->tempPkg.DIRECTORY_SEPARATOR.'.gitattributes', "* text=auto\n/tests export-ignore\n");
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->tempPkg)) {
            File::deleteDirectory($this->tempPkg);
        }
        parent::tearDown();
    }

    public function test_audit_passes_with_passing_checks(): void
    {
        Config::set('package-audit.checks.isolated', false);
        Config::set('package-audit.checks.pint', false);
        Config::set('package-audit.checks.phpstan', false);
        Config::set('package-audit.checks.tests', false);

        Process::fake(function (PendingProcess $process) {
            $cmd = is_array($process->command) ? implode(' ', $process->command) : (string) $process->command;
            if (str_contains($cmd, 'status')) {
                return Process::result('');
            }
            if (str_contains($cmd, 'validate')) {
                return Process::result('Valid composer.json');
            }
            if (str_contains($cmd, 'rev-parse') && str_contains($cmd, 'tree')) {
                return Process::result("tree123\n");
            }
            if (str_contains($cmd, 'rev-parse')) {
                return Process::result("commit123\n");
            }
            if (str_contains($cmd, 'describe')) {
                return Process::result("1.0.0\n");
            }

            return Process::result('ok');
        });

        $runner = new AuditRunner;
        $report = $runner->audit($this->tempPkg);

        $this->assertSame('PASSED', $report->verdict);
        $this->assertSame('alex-kassel/test-fixture', $report->package);
        $this->assertSame('1.0.0', $report->version);
        $this->assertArrayHasKey('git_cleanliness', $report->checks);
        $this->assertTrue($report->checks['git_cleanliness']->isPassed());
        $this->assertArrayHasKey('composer_validate', $report->checks);
        $this->assertTrue($report->checks['composer_validate']->isPassed());
        $this->assertArrayHasKey('readme', $report->checks);
        $this->assertTrue($report->checks['readme']->isPassed());
        $this->assertArrayHasKey('export_ignore', $report->checks);
        $this->assertTrue($report->checks['export_ignore']->isPassed());
        $this->assertStringStartsWith('sha256:', $report->fingerprint);
    }

    public function test_audit_fails_when_git_working_tree_is_dirty(): void
    {
        Config::set('package-audit.checks.isolated', false);
        Config::set('package-audit.checks.pint', false);
        Config::set('package-audit.checks.phpstan', false);
        Config::set('package-audit.checks.tests', false);

        Process::fake(function (PendingProcess $process) {
            $cmd = is_array($process->command) ? implode(' ', $process->command) : (string) $process->command;
            if (str_contains($cmd, 'status')) {
                return Process::result(" M src/ModifiedFile.php\n");
            }
            if (str_contains($cmd, 'validate')) {
                return Process::result('Valid composer.json');
            }
            if (str_contains($cmd, 'rev-parse') && str_contains($cmd, 'tree')) {
                return Process::result("tree123\n");
            }
            if (str_contains($cmd, 'rev-parse')) {
                return Process::result("commit123\n");
            }

            return Process::result('ok');
        });

        $runner = new AuditRunner;
        $report = $runner->audit($this->tempPkg);

        $this->assertSame('FAILED', $report->verdict);
        $this->assertArrayHasKey('git_cleanliness', $report->checks);
        $this->assertTrue($report->checks['git_cleanliness']->isFailed());
        $this->assertStringContainsString('uncommitted or untracked changes', $report->checks['git_cleanliness']->output);
    }

    public function test_audit_fails_when_readme_missing_sections(): void
    {
        // Write invalid README missing Installation and Testing
        File::put($this->tempPkg.DIRECTORY_SEPARATOR.'README.md', "# Test Package\n\n## Requirements\nPHP 8.2\n\n## Usage\nuse it\n\n## License\nMIT\n");

        Config::set('package-audit.checks.isolated', false);
        Config::set('package-audit.checks.pint', false);
        Config::set('package-audit.checks.phpstan', false);
        Config::set('package-audit.checks.tests', false);

        Process::fake(function (PendingProcess $process) {
            $cmd = is_array($process->command) ? implode(' ', $process->command) : (string) $process->command;
            if (str_contains($cmd, 'status')) {
                return Process::result('');
            }
            if (str_contains($cmd, 'validate')) {
                return Process::result('Valid composer.json');
            }
            if (str_contains($cmd, 'rev-parse') && str_contains($cmd, 'tree')) {
                return Process::result("tree123\n");
            }
            if (str_contains($cmd, 'rev-parse')) {
                return Process::result("commit123\n");
            }

            return Process::result('ok');
        });

        $runner = new AuditRunner;
        $report = $runner->audit($this->tempPkg);

        $this->assertSame('FAILED', $report->verdict);
        $this->assertArrayHasKey('readme', $report->checks);
        $this->assertTrue($report->checks['readme']->isFailed());
        $this->assertStringContainsString('Installation', $report->checks['readme']->output);
        $this->assertStringContainsString('Testing', $report->checks['readme']->output);
    }

    public function test_audit_fails_when_export_ignore_missing(): void
    {
        File::put($this->tempPkg.DIRECTORY_SEPARATOR.'.gitattributes', "* text=auto\n");

        Config::set('package-audit.checks.isolated', false);
        Config::set('package-audit.checks.pint', false);
        Config::set('package-audit.checks.phpstan', false);
        Config::set('package-audit.checks.tests', false);

        Process::fake(function (PendingProcess $process) {
            $cmd = is_array($process->command) ? implode(' ', $process->command) : (string) $process->command;
            if (str_contains($cmd, 'status')) {
                return Process::result('');
            }
            if (str_contains($cmd, 'validate')) {
                return Process::result('Valid composer.json');
            }
            if (str_contains($cmd, 'rev-parse') && str_contains($cmd, 'tree')) {
                return Process::result("tree123\n");
            }
            if (str_contains($cmd, 'rev-parse')) {
                return Process::result("commit123\n");
            }

            return Process::result('ok');
        });

        $runner = new AuditRunner;
        $report = $runner->audit($this->tempPkg);

        $this->assertSame('FAILED', $report->verdict);
        $this->assertArrayHasKey('export_ignore', $report->checks);
        $this->assertTrue($report->checks['export_ignore']->isFailed());
    }
}
