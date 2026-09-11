<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit\Tests\Unit;

use AlexKassel\PackageAudit\DTOs\AuditReport;
use AlexKassel\PackageAudit\DTOs\CheckResult;
use AlexKassel\PackageAudit\DTOs\VerificationResult;
use PHPUnit\Framework\TestCase;

class CheckResultAndReportTest extends TestCase
{
    public function test_check_result_status_helpers_and_serialization(): void
    {
        $passed = new CheckResult('composer', 'passed', 'all good', 1.25);
        $failed = new CheckResult('pint', 'failed', 'syntax error', 0.5);
        $skipped = new CheckResult('tests', 'skipped', 'no tests found', 0.0);

        $this->assertTrue($passed->isPassed());
        $this->assertFalse($passed->isFailed());
        $this->assertFalse($passed->isSkipped());

        $this->assertTrue($failed->isFailed());
        $this->assertFalse($failed->isPassed());

        $this->assertTrue($skipped->isSkipped());

        $array = $passed->toArray();
        $this->assertSame('composer', $array['check']);
        $this->assertSame('passed', $array['status']);
        $this->assertSame('all good', $array['output']);
        $this->assertSame(1.25, $array['duration_seconds']);

        $hydrated = CheckResult::fromArray($array);
        $this->assertSame($passed->check, $hydrated->check);
        $this->assertSame($passed->status, $hydrated->status);
    }

    public function test_audit_report_serialization_and_verdict_helpers(): void
    {
        $checks = [
            'composer' => new CheckResult('composer', 'passed', 'ok', 0.1),
            'pint' => new CheckResult('pint', 'passed', 'ok', 0.2),
        ];

        $report = new AuditReport(
            package: 'alex-kassel/test-pkg',
            version: '1.0.0',
            commit: 'commit123',
            treeHash: 'tree456',
            branch: 'main',
            timestamp: '2026-09-11T12:00:00Z',
            environment: ['php' => '8.4', 'laravel' => '12.x', 'os' => 'windows'],
            checks: $checks,
            verdict: 'PASSED',
            fingerprint: 'sha256:abcd',
            auditorVersion: '1.0.0',
        );

        $this->assertTrue($report->allPassed());

        $json = $report->toJson();
        $this->assertStringContainsString('"package": "alex-kassel/test-pkg"', $json);
        $this->assertStringContainsString('"verdict": "PASSED"', $json);

        $hydrated = AuditReport::fromJson($json);
        $this->assertSame($report->package, $hydrated->package);
        $this->assertSame($report->version, $hydrated->version);
        $this->assertSame($report->commit, $hydrated->commit);
        $this->assertSame($report->treeHash, $hydrated->treeHash);
        $this->assertSame($report->fingerprint, $hydrated->fingerprint);
        $this->assertCount(2, $hydrated->checks);
    }

    public function test_audit_report_all_passed_is_false_when_check_failed(): void
    {
        $checks = [
            'composer' => new CheckResult('composer', 'passed', 'ok', 0.1),
            'pint' => new CheckResult('pint', 'failed', 'style issues', 0.2),
        ];

        $report = new AuditReport(
            package: 'alex-kassel/test-pkg',
            version: '1.0.0',
            commit: 'commit123',
            treeHash: 'tree456',
            branch: 'main',
            timestamp: '2026-09-11T12:00:00Z',
            environment: ['php' => '8.4'],
            checks: $checks,
            verdict: 'FAILED',
            fingerprint: 'sha256:abcd',
            auditorVersion: '1.0.0',
        );

        $this->assertFalse($report->allPassed());
    }

    public function test_verification_result_serialization(): void
    {
        $result = new VerificationResult(
            verified: true,
            status: 'VERIFIED',
            reason: 'All checks verified',
        );

        $this->assertTrue($result->verified);
        $this->assertSame('VERIFIED', $result->status);
        $this->assertStringContainsString('"status": "VERIFIED"', $result->toJson());
    }
}
