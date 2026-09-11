<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit\Commands;

use AlexKassel\PackageAudit\DTOs\CheckResult;
use AlexKassel\PackageAudit\Services\AuditRunner;
use AlexKassel\PackageAudit\Services\CertificateVerifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class AuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:audit
        {path=. : Path to the package directory}
        {--verify : Verify an existing audit certificate}
        {--json : Output machine-readable JSON}
        {--no-commit : Skip git tag creation and committing AUDIT.json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and certify a Laravel/PHP package with cryptographic verification';

    public function __construct(
        protected AuditRunner $runner,
        protected CertificateVerifier $verifier
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $rawPath = (string) $this->argument('path');
        $packagePath = $this->runner->normalizePath($rawPath);

        if (! File::isDirectory($packagePath)) {
            if ($this->option('json')) {
                $this->output->writeln(json_encode([
                    'error' => "Package directory not found: {$rawPath}",
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                $this->components->error("Package directory not found: {$rawPath}");
            }

            return self::FAILURE;
        }

        if ($this->option('verify')) {
            return $this->handleVerify($packagePath);
        }

        return $this->handleAudit($packagePath);
    }

    /**
     * Handle audit execution, reporting, and certificate generation.
     */
    protected function handleAudit(string $packagePath): int
    {
        $report = $this->runner->audit($packagePath);

        if ($this->option('json')) {
            $this->line($report->toJson());

            return $report->allPassed() ? self::SUCCESS : self::FAILURE;
        }

        $this->output->writeln('');
        $this->components->info("Auditing package [{$report->package}] (v{$report->version})");
        $this->line(" <fg=gray>Directory: {$packagePath}</>");
        $this->line(" <fg=gray>Commit:    {$report->commit}</>");
        $this->line(" <fg=gray>Tree Hash: {$report->treeHash}</>");
        $this->output->writeln('');

        $rows = [];
        foreach ($report->checks as $checkKey => $check) {
            $rows[] = [
                $checkKey,
                $this->formatStatus($check->status),
                number_format($check->durationSeconds, 2).'s',
                $this->summarizeOutput($check),
            ];
        }

        $this->table(['Check', 'Status', 'Duration', 'Details'], $rows);

        $this->output->writeln('');

        if (! $report->allPassed()) {
            $this->components->error("Audit FAILED for [{$report->package}]. Resolve failing checks before certifying.");

            return self::FAILURE;
        }

        // Passed: Write AUDIT.json
        $certFilename = (string) (Config::get('package-audit.certificate_filename') ?? 'AUDIT.json');
        $certFilePath = $packagePath.DIRECTORY_SEPARATOR.$certFilename;
        File::put($certFilePath, $report->toJson().PHP_EOL);

        $gitAction = 'skipped (--no-commit)';
        if (! $this->option('no-commit')) {
            $gitDir = $packagePath.DIRECTORY_SEPARATOR.'.git';
            if (File::isDirectory($gitDir) || File::isFile($gitDir)) {
                $tag = "audit/v{$report->version}";
                Process::path($packagePath)->run(['git', 'tag', '-f', $tag, 'HEAD']);
                Process::path($packagePath)->run(['git', 'add', $certFilename]);
                Process::path($packagePath)->run(['git', 'commit', '-m', "Audit certificate for v{$report->version}"]);
                $gitAction = "tagged ({$tag}) and committed";
            }
        }

        $this->components->info("Audit PASSED! Package [{$report->package}] is certified.");
        $this->line(" <fg=green;options=bold>Certificate:</> {$certFilename} ({$gitAction})");
        $this->line(" <fg=cyan;options=bold>Fingerprint:</> {$report->fingerprint}");
        $this->output->writeln('');

        return self::SUCCESS;
    }

    /**
     * Handle certificate verification.
     */
    protected function handleVerify(string $packagePath): int
    {
        $result = $this->verifier->verify($packagePath);

        if ($this->option('json')) {
            $this->line($result->toJson());

            return $result->verified ? self::SUCCESS : self::FAILURE;
        }

        $this->output->writeln('');
        $this->components->info("Verifying audit certificate for [{$packagePath}]");
        $this->output->writeln('');

        if ($result->verified) {
            $this->components->info("Result: {$result->status}");
            $this->line(" <fg=green;options=bold>Reason:</> {$result->reason}");
            if ($result->certificate !== null) {
                $this->line(" <fg=gray>Version:</>     {$result->certificate->version}");
                $this->line(" <fg=gray>Commit:</>      {$result->certificate->commit}");
                $this->line(" <fg=gray>Tree Hash:</>   {$result->certificate->treeHash}");
                $this->line(" <fg=cyan;options=bold>Fingerprint:</> {$result->certificate->fingerprint}");
            }
            $this->output->writeln('');

            return self::SUCCESS;
        }

        $this->components->error("Verification FAILED: {$result->status}");
        $this->line(" <fg=red;options=bold>Reason:</> {$result->reason}");
        $this->output->writeln('');

        return self::FAILURE;
    }

    protected function formatStatus(string $status): string
    {
        return match ($status) {
            'passed' => '<fg=green;options=bold>PASSED</>',
            'failed' => '<fg=red;options=bold>FAILED</>',
            'skipped' => '<fg=yellow>SKIPPED</>',
            default => $status,
        };
    }

    protected function summarizeOutput(CheckResult $check): string
    {
        $output = trim($check->output);
        $firstLine = strtok($output, "\r\n");

        if ($firstLine === false || $firstLine === '') {
            return $check->status;
        }

        return mb_strimwidth($firstLine, 0, 70, '...');
    }
}
