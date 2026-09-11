<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit\Tests\Unit;

require_once __DIR__.'/../TestCase.php';

use AlexKassel\PackageAudit\DTOs\AuditReport;
use AlexKassel\PackageAudit\DTOs\CheckResult;
use AlexKassel\PackageAudit\Services\AuditRunner;
use AlexKassel\PackageAudit\Services\CertificateVerifier;
use AlexKassel\PackageAudit\Services\FingerprintCalculator;
use AlexKassel\PackageAudit\Tests\TestCase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class CertificateVerifierTest extends TestCase
{
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cert_verifier_test_'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    public function test_verify_returns_missing_when_certificate_does_not_exist(): void
    {
        $verifier = new CertificateVerifier;
        $result = $verifier->verify($this->tempDir);

        $this->assertFalse($result->verified);
        $this->assertSame('MISSING', $result->status);
        $this->assertStringContainsString('AUDIT.json', (string) $result->reason);
    }

    public function test_verify_returns_forged_when_json_is_invalid(): void
    {
        File::put($this->tempDir.DIRECTORY_SEPARATOR.'AUDIT.json', '{invalid_json}');

        $verifier = new CertificateVerifier;
        $result = $verifier->verify($this->tempDir);

        $this->assertFalse($result->verified);
        $this->assertSame('FORGED', $result->status);
        $this->assertStringContainsString('Malformed', (string) $result->reason);
    }

    public function test_verify_returns_forged_when_not_a_git_repository(): void
    {
        $validReport = new AuditReport(
            package: 'alex-kassel/dummy',
            version: '1.0.0',
            commit: 'abc12345',
            treeHash: 'tree12345',
            branch: 'main',
            timestamp: '2026-09-11T12:00:00Z',
            environment: ['php' => '8.4'],
            checks: ['composer' => new CheckResult('composer', 'passed', 'ok', 0.1)],
            verdict: 'PASSED',
            fingerprint: 'sha256:fingerprint123',
            auditorVersion: '1.0.0',
        );

        File::put($this->tempDir.DIRECTORY_SEPARATOR.'AUDIT.json', $validReport->toJson());

        $verifier = new CertificateVerifier;
        $result = $verifier->verify($this->tempDir);

        $this->assertFalse($result->verified);
        $this->assertSame('FORGED', $result->status);
        $this->assertStringContainsString('git repository', (string) $result->reason);
    }

    public function test_verify_detects_fingerprint_mismatch(): void
    {
        // Setup git directory
        File::ensureDirectoryExists($this->tempDir.DIRECTORY_SEPARATOR.'.git');

        $originalReport = new AuditReport(
            package: 'alex-kassel/dummy',
            version: '1.0.0',
            commit: 'abc12345',
            treeHash: 'tree_correct_hash',
            branch: 'main',
            timestamp: '2026-09-11T12:00:00Z',
            environment: ['php' => '8.4'],
            checks: ['composer' => new CheckResult('composer', 'passed', 'ok', 0.1)],
            verdict: 'PASSED',
            fingerprint: 'sha256:forged_fingerprint_here',
            auditorVersion: '1.0.0',
        );
        File::put($this->tempDir.DIRECTORY_SEPARATOR.'AUDIT.json', $originalReport->toJson());

        Process::fake(function (PendingProcess $process) {
            $cmd = is_array($process->command) ? implode(' ', $process->command) : (string) $process->command;
            if (str_contains($cmd, 'rev-parse') && str_contains($cmd, 'tree')) {
                return Process::result("tree_correct_hash\n");
            }
            if (str_contains($cmd, 'rev-parse')) {
                return Process::result("abc12345\n");
            }
            if (str_contains($cmd, 'status')) {
                return Process::result('');
            }
            if (str_contains($cmd, 'checkout')) {
                return Process::result('');
            }

            return Process::result('ok');
        });

        $mockAuditRunner = $this->createMock(AuditRunner::class);
        $mockAuditRunner->method('normalizePath')->willReturn($this->tempDir);
        $mockAuditRunner->method('audit')->willReturn(new AuditReport(
            package: 'alex-kassel/dummy',
            version: '1.0.0',
            commit: 'abc12345',
            treeHash: 'tree_correct_hash',
            branch: 'main',
            timestamp: '2026-09-11T12:00:00Z',
            environment: ['php' => '8.4'],
            checks: ['composer' => new CheckResult('composer', 'passed', 'ok', 0.1)],
            verdict: 'PASSED',
            fingerprint: 'sha256:real_actual_computed_fingerprint',
            auditorVersion: '1.0.0',
        ));

        $verifier = new CertificateVerifier(
            auditRunner: $mockAuditRunner,
            fingerprintCalculator: new FingerprintCalculator
        );

        $result = $verifier->verify($this->tempDir);

        $this->assertFalse($result->verified);
        $this->assertSame('FORGED', $result->status);
        $this->assertStringContainsString('Fingerprint mismatch', (string) $result->reason);
    }
}
