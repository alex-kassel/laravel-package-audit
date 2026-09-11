<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit\Tests\Unit;

use AlexKassel\PackageAudit\DTOs\CheckResult;
use AlexKassel\PackageAudit\Services\FingerprintCalculator;
use PHPUnit\Framework\TestCase;

class FingerprintCalculatorTest extends TestCase
{
    protected FingerprintCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new FingerprintCalculator;
    }

    public function test_computes_deterministic_sha256_prefixed_fingerprint(): void
    {
        $checks = [
            'composer' => new CheckResult('composer', 'passed', 'composer is valid', 0.1),
            'pint' => new CheckResult('pint', 'passed', 'pint passed', 0.2),
        ];

        $hash1 = $this->calculator->compute('tree1234567890', $checks);
        $hash2 = $this->calculator->compute('tree1234567890', $checks);

        $this->assertSame($hash1, $hash2);
        $this->assertStringStartsWith('sha256:', $hash1);
        $this->assertSame(71, strlen($hash1)); // 'sha256:' (7 chars) + 64 hex chars
    }

    public function test_check_order_does_not_affect_fingerprint(): void
    {
        $checksA = [
            'composer' => new CheckResult('composer', 'passed', 'ok', 0.1),
            'pint' => new CheckResult('pint', 'passed', 'ok', 0.2),
            'tests' => new CheckResult('tests', 'passed', 'ok', 0.3),
        ];

        $checksB = [
            'tests' => new CheckResult('tests', 'passed', 'ok', 0.3),
            'composer' => new CheckResult('composer', 'passed', 'ok', 0.1),
            'pint' => new CheckResult('pint', 'passed', 'ok', 0.2),
        ];

        $hashA = $this->calculator->compute('tree_sha_abc', $checksA);
        $hashB = $this->calculator->compute('tree_sha_abc', $checksB);

        $this->assertSame($hashA, $hashB);
    }

    public function test_different_tree_hash_produces_different_fingerprint(): void
    {
        $checks = [
            'composer' => new CheckResult('composer', 'passed', 'ok', 0.1),
        ];

        $hash1 = $this->calculator->compute('tree_sha_111', $checks);
        $hash2 = $this->calculator->compute('tree_sha_222', $checks);

        $this->assertNotSame($hash1, $hash2);
    }

    public function test_different_output_produces_different_fingerprint(): void
    {
        $checks1 = [
            'composer' => new CheckResult('composer', 'passed', 'output version 1', 0.1),
        ];
        $checks2 = [
            'composer' => new CheckResult('composer', 'passed', 'output version 2', 0.1),
        ];

        $hash1 = $this->calculator->compute('tree_sha_same', $checks1);
        $hash2 = $this->calculator->compute('tree_sha_same', $checks2);

        $this->assertNotSame($hash1, $hash2);
    }

    public function test_different_check_status_produces_different_fingerprint(): void
    {
        $checks1 = [
            'composer' => new CheckResult('composer', 'passed', 'output', 0.1),
        ];
        $checks2 = [
            'composer' => new CheckResult('composer', 'failed', 'output', 0.1),
        ];

        $hash1 = $this->calculator->compute('tree_sha_same', $checks1);
        $hash2 = $this->calculator->compute('tree_sha_same', $checks2);

        $this->assertNotSame($hash1, $hash2);
    }
}
