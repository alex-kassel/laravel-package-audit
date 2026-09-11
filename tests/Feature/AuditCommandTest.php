<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit\Tests\Feature;

require_once __DIR__.'/../TestCase.php';

use AlexKassel\PackageAudit\DTOs\AuditReport;
use AlexKassel\PackageAudit\DTOs\CheckResult;
use AlexKassel\PackageAudit\DTOs\VerificationResult;
use AlexKassel\PackageAudit\Services\AuditRunner;
use AlexKassel\PackageAudit\Services\CertificateVerifier;
use AlexKassel\PackageAudit\Tests\TestCase;
use Illuminate\Support\Facades\File;

class AuditCommandTest extends TestCase
{
    protected string $tempPkg;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempPkg = sys_get_temp_dir().DIRECTORY_SEPARATOR.'audit_cmd_test_'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($this->tempPkg);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->tempPkg)) {
            File::deleteDirectory($this->tempPkg);
        }
        parent::tearDown();
    }

    public function test_audit_command_fails_for_non_existent_directory(): void
    {
        $nonExistent = $this->tempPkg.DIRECTORY_SEPARATOR.'does_not_exist';

        $this->artisan('package:audit', ['path' => $nonExistent])
            ->assertExitCode(1);
    }

    public function test_audit_command_executes_and_generates_certificate(): void
    {
        $mockRunner = $this->createMock(AuditRunner::class);
        $mockRunner->method('normalizePath')->willReturn($this->tempPkg);
        $mockRunner->method('audit')->willReturn(new AuditReport(
            package: 'alex-kassel/mock-package',
            version: '1.2.0',
            commit: 'commit123',
            treeHash: 'tree123',
            branch: 'main',
            timestamp: '2026-09-11T12:00:00Z',
            environment: ['php' => '8.4', 'laravel' => '12.x', 'os' => 'windows'],
            checks: [
                'composer_validate' => new CheckResult('composer_validate', 'passed', 'composer.json is valid', 0.1),
                'pint' => new CheckResult('pint', 'passed', 'all styled', 0.2),
            ],
            verdict: 'PASSED',
            fingerprint: 'sha256:abcd1234567890',
            auditorVersion: '1.0.0',
        ));

        $this->app->instance(AuditRunner::class, $mockRunner);

        $this->artisan('package:audit', ['path' => $this->tempPkg, '--no-commit' => true])
            ->expectsOutputToContain('Auditing package [alex-kassel/mock-package]')
            ->expectsOutputToContain('PASSED')
            ->assertExitCode(0);

        $certPath = $this->tempPkg.DIRECTORY_SEPARATOR.'AUDIT.json';
        $this->assertFileExists($certPath);

        $certData = json_decode((string) File::get($certPath), true);
        $this->assertSame('alex-kassel/mock-package', $certData['package']);
        $this->assertSame('PASSED', $certData['verdict']);
        $this->assertSame('sha256:abcd1234567890', $certData['fingerprint']);
    }

    public function test_audit_command_outputs_json(): void
    {
        $mockRunner = $this->createMock(AuditRunner::class);
        $mockRunner->method('normalizePath')->willReturn($this->tempPkg);
        $mockRunner->method('audit')->willReturn(new AuditReport(
            package: 'alex-kassel/mock-package',
            version: '1.2.0',
            commit: 'commit123',
            treeHash: 'tree123',
            branch: 'main',
            timestamp: '2026-09-11T12:00:00Z',
            environment: ['php' => '8.4'],
            checks: [
                'composer_validate' => new CheckResult('composer_validate', 'passed', 'valid', 0.1),
            ],
            verdict: 'PASSED',
            fingerprint: 'sha256:abcd',
            auditorVersion: '1.0.0',
        ));

        $this->app->instance(AuditRunner::class, $mockRunner);

        $this->artisan('package:audit', ['path' => $this->tempPkg, '--json' => true, '--no-commit' => true])
            ->expectsOutputToContain('"verdict": "PASSED"')
            ->assertExitCode(0);
    }

    public function test_audit_command_verify_flag(): void
    {
        $mockVerifier = $this->createMock(CertificateVerifier::class);
        $mockVerifier->method('verify')->willReturn(new VerificationResult(
            verified: true,
            status: 'VERIFIED',
            reason: 'Audit certificate and cryptographic fingerprint verified successfully.',
        ));

        $this->app->instance(CertificateVerifier::class, $mockVerifier);

        $this->artisan('package:audit', ['path' => $this->tempPkg, '--verify' => true])
            ->expectsOutputToContain('Result: VERIFIED')
            ->assertExitCode(0);
    }
}
