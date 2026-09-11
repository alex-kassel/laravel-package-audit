<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit\Services;

use AlexKassel\PackageAudit\DTOs\AuditReport;
use AlexKassel\PackageAudit\DTOs\VerificationResult;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Throwable;

class CertificateVerifier
{
    public function __construct(
        protected ?AuditRunner $auditRunner = null,
        protected ?FingerprintCalculator $fingerprintCalculator = null
    ) {
        $this->fingerprintCalculator ??= new FingerprintCalculator;
        $this->auditRunner ??= new AuditRunner($this->fingerprintCalculator);
    }

    /**
     * Verify an existing audit certificate (AUDIT.json) against the package source.
     */
    public function verify(string $packagePath): VerificationResult
    {
        $realPackagePath = $this->auditRunner->normalizePath($packagePath);
        $certificateFilename = (string) (Config::get('package-audit.certificate_filename') ?? 'AUDIT.json');
        $certificatePath = $realPackagePath.DIRECTORY_SEPARATOR.$certificateFilename;

        // 1. Check if certificate file exists
        if (! File::exists($certificatePath)) {
            return new VerificationResult(
                verified: false,
                status: 'MISSING',
                reason: "Audit certificate file [{$certificateFilename}] not found in package root.",
            );
        }

        // 2. Read and parse certificate
        try {
            $certificateJson = (string) File::get($certificatePath);
            $report = AuditReport::fromJson($certificateJson);
        } catch (Throwable $e) {
            return new VerificationResult(
                verified: false,
                status: 'FORGED',
                reason: 'Malformed or unparseable certificate JSON: '.$e->getMessage(),
            );
        }

        $certifiedCommit = $report->commit;
        $certifiedTreeHash = $report->treeHash;

        if ($certifiedCommit === '' || $certifiedTreeHash === '') {
            return new VerificationResult(
                verified: false,
                status: 'FORGED',
                reason: 'Certificate is missing certified commit or tree hash.',
                certificate: $report,
            );
        }

        // 3. Verify git repository exists
        $gitDir = $realPackagePath.DIRECTORY_SEPARATOR.'.git';
        if (! File::isDirectory($gitDir) && ! File::isFile($gitDir)) {
            return new VerificationResult(
                verified: false,
                status: 'FORGED',
                reason: 'Package directory is not a valid git repository.',
                certificate: $report,
            );
        }

        // 4. Verify tree_hash: git rev-parse {commit}^{tree} == certificate.tree_hash
        $treeProcess = Process::path($realPackagePath)->run(['git', 'rev-parse', "{$certifiedCommit}^{tree}"]);
        if (! $treeProcess->successful()) {
            return new VerificationResult(
                verified: false,
                status: 'FORGED',
                reason: "Certified commit [{$certifiedCommit}] is not reachable in git repository.",
                certificate: $report,
            );
        }

        $actualTreeHash = trim($treeProcess->output());
        if ($actualTreeHash !== $certifiedTreeHash) {
            return new VerificationResult(
                verified: false,
                status: 'FORGED',
                reason: "Tree hash mismatch: certificate specifies [{$certifiedTreeHash}], but commit tree is [{$actualTreeHash}].",
                certificate: $report,
            );
        }

        // 5. Check if source code has drifted since the certified commit
        $originalRef = trim(Process::path($realPackagePath)->run(['git', 'rev-parse', 'HEAD'])->output());
        $originalBranch = trim(Process::path($realPackagePath)->run(['git', 'rev-parse', '--abbrev-ref', 'HEAD'])->output());

        $statusProcess = Process::path($realPackagePath)->run(['git', 'status', '--porcelain']);
        $isDirty = trim($statusProcess->output()) !== '';

        $needsCheckout = ($originalRef !== $certifiedCommit);
        $stashed = false;

        try {
            if ($needsCheckout) {
                if ($isDirty) {
                    $stashProcess = Process::path($realPackagePath)->run(['git', 'stash', 'create']);
                    $stashSha = trim($stashProcess->output());
                    if ($stashSha !== '') {
                        Process::path($realPackagePath)->run(['git', 'stash', 'store', '-m', 'package-audit-temp-verify', $stashSha]);
                        $stashed = true;
                    }
                }

                $checkoutProcess = Process::path($realPackagePath)->run(['git', 'checkout', '--quiet', $certifiedCommit]);
                if (! $checkoutProcess->successful()) {
                    return new VerificationResult(
                        verified: false,
                        status: 'FORGED',
                        reason: 'Failed to checkout certified commit: '.$checkoutProcess->errorOutput(),
                        certificate: $report,
                    );
                }
            }

            // 6. Re-run all checks on the certified commit snapshot
            $reAudit = $this->auditRunner->audit($realPackagePath, $report->version);

            // 7. Recompute fingerprint and compare
            if ($reAudit->fingerprint !== $report->fingerprint) {
                return new VerificationResult(
                    verified: false,
                    status: 'FORGED',
                    reason: "Fingerprint mismatch: expected [{$report->fingerprint}], but recomputed audit produced [{$reAudit->fingerprint}].",
                    certificate: $report,
                );
            }

            // 8. Check if subsequent commits modified source files (excluding AUDIT.json)
            if ($needsCheckout) {
                $diffProcess = Process::path($realPackagePath)->run([
                    'git', 'diff', '--name-only', "{$certifiedCommit}..{$originalRef}",
                ]);

                if ($diffProcess->successful()) {
                    $changedFiles = array_filter(
                        explode("\n", str_replace("\r", '', trim($diffProcess->output()))),
                        fn ($f) => $f !== '' && $f !== $certificateFilename
                    );

                    if (! empty($changedFiles)) {
                        return new VerificationResult(
                            verified: false,
                            status: 'OUTDATED',
                            reason: 'Certificate verified for commit '.$certifiedCommit.', but subsequent commits modified source files: '.implode(', ', array_slice($changedFiles, 0, 3)).(count($changedFiles) > 3 ? '...' : ''),
                            certificate: $report,
                        );
                    }
                }
            }

            return new VerificationResult(
                verified: true,
                status: 'VERIFIED',
                reason: 'Audit certificate and cryptographic fingerprint verified successfully.',
                certificate: $report,
            );
        } finally {
            if ($needsCheckout) {
                $targetRef = ($originalBranch !== '' && $originalBranch !== 'HEAD') ? $originalBranch : $originalRef;
                Process::path($realPackagePath)->run(['git', 'checkout', '--quiet', $targetRef]);

                if ($stashed) {
                    Process::path($realPackagePath)->run(['git', 'stash', 'pop']);
                }
            }
        }
    }
}
