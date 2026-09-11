<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit\Services;

use AlexKassel\PackageAudit\DTOs\CheckResult;
use Illuminate\Support\Facades\Process;

class FingerprintCalculator
{
    /**
     * Compute a deterministic SHA-256 fingerprint from the git tree hash and checks.
     *
     * @param  array<string, CheckResult|array<string, mixed>>  $checks
     */
    public function compute(string $treeHash, array $checks): string
    {
        ksort($checks);

        $canonicalChecks = [];
        foreach ($checks as $key => $r) {
            if ($r instanceof CheckResult) {
                $canonicalChecks[$key] = [
                    'check' => $r->check,
                    'status' => $r->status,
                    'output_hash' => hash('sha256', $r->output),
                ];
            } elseif (is_array($r)) {
                $outputHash = isset($r['output_hash'])
                    ? (string) $r['output_hash']
                    : hash('sha256', (string) ($r['output'] ?? ''));

                $canonicalChecks[$key] = [
                    'check' => (string) ($r['check'] ?? $key),
                    'status' => (string) ($r['status'] ?? 'unknown'),
                    'output_hash' => $outputHash,
                ];
            }
        }

        $canonical = json_encode([
            'tree_hash' => $treeHash,
            'checks' => $canonicalChecks,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return 'sha256:'.hash('sha256', (string) $canonical);
    }

    /**
     * Resolve the git tree hash of HEAD in the target package directory.
     */
    public function getTreeHash(string $packagePath): string
    {
        $result = Process::path($packagePath)->run(['git', 'rev-parse', 'HEAD^{tree}']);

        if (! $result->successful()) {
            return '';
        }

        return trim($result->output());
    }
}
