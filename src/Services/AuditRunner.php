<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit\Services;

use AlexKassel\PackageAudit\DTOs\AuditReport;
use AlexKassel\PackageAudit\DTOs\CheckResult;
use AlexKassel\PackageAudit\PackageAuditServiceProvider;
use FilesystemIterator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class AuditRunner
{
    public function __construct(
        protected ?FingerprintCalculator $fingerprintCalculator = null
    ) {
        $this->fingerprintCalculator ??= new FingerprintCalculator;
    }

    /**
     * Run all audit checks on the package and generate an AuditReport.
     */
    public function audit(string $packagePath): AuditReport
    {
        $realPackagePath = $this->normalizePath($packagePath);
        $packageName = $this->resolvePackageName($realPackagePath);
        $version = $this->resolvePackageVersion($realPackagePath);

        $checks = [];

        // 1. Git cleanliness
        if ($this->isCheckEnabled('git_cleanliness')) {
            $checks['git_cleanliness'] = $this->checkGitCleanliness($realPackagePath);
        }

        // 2. Composer validate
        if ($this->isCheckEnabled('composer_validate')) {
            $checks['composer_validate'] = $this->checkComposerValidate($realPackagePath);
        }

        // 3. Pint
        if ($this->isCheckEnabled('pint')) {
            $checks['pint'] = $this->checkPint($realPackagePath);
        }

        // 4. PHPStan
        if ($this->isCheckEnabled('phpstan')) {
            $checks['phpstan'] = $this->checkPhpstan($realPackagePath);
        }

        // 5. Tests
        if ($this->isCheckEnabled('tests')) {
            $checks['tests'] = $this->checkTests($realPackagePath);
        }

        // 6. Isolated verification
        if ($this->isCheckEnabled('isolated')) {
            $checks['isolated'] = $this->checkIsolated($realPackagePath);
        }

        // 7. README standard compliance
        if ($this->isCheckEnabled('readme')) {
            $checks['readme'] = $this->checkReadme($realPackagePath);
        }

        // 8. Export-ignore in .gitattributes
        if ($this->isCheckEnabled('export_ignore')) {
            $checks['export_ignore'] = $this->checkExportIgnore($realPackagePath);
        }

        // Git metadata
        $commit = $this->resolveGitCommit($realPackagePath);
        $treeHash = $this->fingerprintCalculator->getTreeHash($realPackagePath);
        $branch = $this->resolveGitBranch($realPackagePath);

        // Overall verdict calculation
        $allPassed = ! empty($checks);
        foreach ($checks as $check) {
            if ($check->isFailed()) {
                $allPassed = false;
                break;
            }
        }

        $verdict = $allPassed ? 'PASSED' : 'FAILED';
        $fingerprint = $this->fingerprintCalculator->compute($treeHash, $checks);

        $environment = [
            'php' => PHP_VERSION,
            'laravel' => function_exists('app') && app() instanceof Application ? app()->version() : (defined(Application::class.'::VERSION') ? Application::VERSION : 'unknown'),
            'os' => strtolower(PHP_OS_FAMILY),
        ];

        return new AuditReport(
            package: $packageName,
            version: $version,
            commit: $commit,
            treeHash: $treeHash,
            branch: $branch,
            timestamp: now()->toIso8601String(),
            environment: $environment,
            checks: $checks,
            verdict: $verdict,
            fingerprint: $fingerprint,
            auditorVersion: PackageAuditServiceProvider::VERSION,
        );
    }

    /**
     * Check 1: Git cleanliness and independent repository.
     */
    public function checkGitCleanliness(string $packagePath): CheckResult
    {
        $startTime = microtime(true);

        $gitDir = $packagePath.DIRECTORY_SEPARATOR.'.git';
        if (! File::isDirectory($gitDir) && ! File::isFile($gitDir)) {
            return new CheckResult(
                check: 'git_cleanliness',
                status: 'failed',
                output: 'Not an independent git repository (.git directory missing in package root).',
                durationSeconds: (float) round(microtime(true) - $startTime, 3),
            );
        }

        $statusProcess = Process::path($packagePath)->run(['git', 'status', '--porcelain']);
        if (! $statusProcess->successful()) {
            return new CheckResult(
                check: 'git_cleanliness',
                status: 'failed',
                output: 'Failed to query git status: '.$statusProcess->errorOutput(),
                durationSeconds: (float) round(microtime(true) - $startTime, 3),
            );
        }

        $statusOutput = trim($statusProcess->output());
        if ($statusOutput !== '') {
            $lines = array_filter(explode("\n", str_replace("\r", '', $statusOutput)));
            $certFilename = (string) (Config::get('package-audit.certificate_filename') ?? 'AUDIT.json');
            $unrelated = array_filter($lines, function ($line) use ($certFilename) {
                $file = trim(substr($line, 3));

                return $file !== $certFilename;
            });

            if (! empty($unrelated)) {
                return new CheckResult(
                    check: 'git_cleanliness',
                    status: 'failed',
                    output: "Working tree has uncommitted or untracked changes:\n".implode("\n", $unrelated),
                    durationSeconds: (float) round(microtime(true) - $startTime, 3),
                );
            }
        }

        $commit = $this->resolveGitCommit($packagePath);
        $branch = $this->resolveGitBranch($packagePath);

        return new CheckResult(
            check: 'git_cleanliness',
            status: 'passed',
            output: "Working tree is clean. Commit: {$commit}, Branch: {$branch}",
            durationSeconds: (float) round(microtime(true) - $startTime, 3),
        );
    }

    /**
     * Check 2: Composer validate --strict.
     */
    public function checkComposerValidate(string $packagePath): CheckResult
    {
        $startTime = microtime(true);
        $composerJson = $packagePath.DIRECTORY_SEPARATOR.'composer.json';

        if (! File::exists($composerJson)) {
            return new CheckResult(
                check: 'composer_validate',
                status: 'failed',
                output: 'composer.json not found in package directory.',
                durationSeconds: (float) round(microtime(true) - $startTime, 3),
            );
        }

        $process = Process::path($packagePath)->run(['composer', 'validate', '--strict']);
        $duration = (float) round(microtime(true) - $startTime, 3);
        $output = trim($process->output()."\n".$process->errorOutput());

        return new CheckResult(
            check: 'composer_validate',
            status: $process->successful() ? 'passed' : 'failed',
            output: $output !== '' ? $output : 'composer.json is valid.',
            durationSeconds: $duration,
        );
    }

    /**
     * Check 3: Pint code style check.
     */
    public function checkPint(string $packagePath): CheckResult
    {
        $startTime = microtime(true);
        $pintBin = $this->resolveHostBinary('pint');

        if ($pintBin === null) {
            return new CheckResult(
                check: 'pint',
                status: 'skipped',
                output: 'Binary not found: vendor/bin/pint. Install it via composer require --dev laravel/pint on host.',
                durationSeconds: (float) round(microtime(true) - $startTime, 3),
            );
        }

        $process = Process::path(base_path())->run([$pintBin, $packagePath, '--test']);
        $duration = (float) round(microtime(true) - $startTime, 3);
        $output = trim($process->output()."\n".$process->errorOutput());

        return new CheckResult(
            check: 'pint',
            status: $process->successful() ? 'passed' : 'failed',
            output: $output !== '' ? $output : 'All files formatted according to Pint standards.',
            durationSeconds: $duration,
        );
    }

    /**
     * Check 4: PHPStan static analysis.
     */
    public function checkPhpstan(string $packagePath): CheckResult
    {
        $startTime = microtime(true);
        $phpstanBin = $this->resolveHostBinary('phpstan');

        if ($phpstanBin === null) {
            return new CheckResult(
                check: 'phpstan',
                status: 'skipped',
                output: 'Binary not found: vendor/bin/phpstan. Install it via composer require --dev phpstan/phpstan on host.',
                durationSeconds: (float) round(microtime(true) - $startTime, 3),
            );
        }

        $neonFile = null;
        foreach (['phpstan.neon', 'phpstan.neon.dist'] as $file) {
            if (File::exists($packagePath.DIRECTORY_SEPARATOR.$file)) {
                $neonFile = $packagePath.DIRECTORY_SEPARATOR.$file;
                break;
            }
        }

        $memoryLimit = (string) (Config::get('package-audit.checks.phpstan.memory_limit') ?? '1G');
        $command = [$phpstanBin, 'analyse', '--memory-limit='.$memoryLimit];

        if ($neonFile !== null) {
            $command[] = '--configuration='.$neonFile;
        } else {
            $srcDir = $packagePath.DIRECTORY_SEPARATOR.'src';
            if (! File::isDirectory($srcDir)) {
                return new CheckResult(
                    check: 'phpstan',
                    status: 'skipped',
                    output: 'No src/ directory or phpstan.neon configuration found to analyse.',
                    durationSeconds: (float) round(microtime(true) - $startTime, 3),
                );
            }
            $level = (string) (Config::get('package-audit.checks.phpstan.level') ?? 8);
            $command[] = $srcDir;
            $command[] = '--level='.$level;
        }

        $process = Process::path(base_path())->run($command);
        $duration = (float) round(microtime(true) - $startTime, 3);
        $output = trim($process->output()."\n".$process->errorOutput());

        return new CheckResult(
            check: 'phpstan',
            status: $process->successful() ? 'passed' : 'failed',
            output: $output !== '' ? $output : 'PHPStan static analysis passed with zero errors.',
            durationSeconds: $duration,
        );
    }

    /**
     * Check 5: PHPUnit / Pest automated test suite.
     */
    public function checkTests(string $packagePath): CheckResult
    {
        $startTime = microtime(true);

        $phpunitXml = null;
        foreach (['phpunit.xml', 'phpunit.xml.dist'] as $file) {
            if (File::exists($packagePath.DIRECTORY_SEPARATOR.$file)) {
                $phpunitXml = $packagePath.DIRECTORY_SEPARATOR.$file;
                break;
            }
        }

        if ($phpunitXml === null) {
            return new CheckResult(
                check: 'tests',
                status: 'skipped',
                output: 'No phpunit.xml or phpunit.xml.dist found in package directory.',
                durationSeconds: (float) round(microtime(true) - $startTime, 3),
            );
        }

        $pestBin = $this->resolveHostBinary('pest');
        $phpunitBin = $this->resolveHostBinary('phpunit');
        $isPest = File::exists($packagePath.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Pest.php') && $pestBin !== null;

        $runner = $isPest ? $pestBin : $phpunitBin;
        if ($runner === null) {
            return new CheckResult(
                check: 'tests',
                status: 'skipped',
                output: 'Binary not found: vendor/bin/phpunit or vendor/bin/pest. Install via composer require --dev phpunit/phpunit.',
                durationSeconds: (float) round(microtime(true) - $startTime, 3),
            );
        }

        $command = [$runner, '-c', $phpunitXml, '--fail-on-empty-test-suite'];
        $env = (array) (Config::get('package-audit.test_environment') ?? [
            'APP_ENV' => 'testing',
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'MAIL_MAILER' => 'array',
        ]);
        $timeout = (int) (Config::get('package-audit.timeouts.tests') ?? 120);

        $process = Process::path(base_path())->timeout($timeout)->env($env)->run($command);
        $duration = (float) round(microtime(true) - $startTime, 3);
        $output = trim($process->output()."\n".$process->errorOutput());

        return new CheckResult(
            check: 'tests',
            status: $process->successful() ? 'passed' : 'failed',
            output: $output !== '' ? $output : 'Test suite passed successfully.',
            durationSeconds: $duration,
        );
    }

    /**
     * Check 6: Isolated package verification sandbox.
     */
    public function checkIsolated(string $packagePath): CheckResult
    {
        $startTime = microtime(true);
        $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'package-audit-'.bin2hex(random_bytes(8));

        try {
            File::ensureDirectoryExists($tempDir);

            // Step 1: Export tracked and untracked unignored files via git ls-files
            $lsFiles = Process::path($packagePath)->run(['git', 'ls-files', '--cached', '--others', '--exclude-standard', '-z']);
            if ($lsFiles->successful() && trim($lsFiles->output()) !== '') {
                $files = explode("\0", trim($lsFiles->output(), "\0"));
                foreach ($files as $file) {
                    if ($file === '') {
                        continue;
                    }
                    $source = $packagePath.DIRECTORY_SEPARATOR.$file;
                    $target = $tempDir.DIRECTORY_SEPARATOR.$file;
                    File::ensureDirectoryExists(dirname($target));
                    if (File::exists($source) && ! File::isDirectory($source)) {
                        File::copy($source, $target);
                    }
                }
            } else {
                // Fallback copy excluding vendor & .git if git ls-files returned nothing
                $this->copyDirectoryExcept($packagePath, $tempDir, ['vendor', '.git', '.audit']);
            }

            // Step 2: Ensure composer.json exists and does NOT contain path repositories
            $composerJsonPath = $tempDir.DIRECTORY_SEPARATOR.'composer.json';
            if (! File::exists($composerJsonPath)) {
                return new CheckResult(
                    check: 'isolated',
                    status: 'failed',
                    output: 'composer.json missing in exported package snapshot.',
                    durationSeconds: (float) round(microtime(true) - $startTime, 3),
                );
            }

            $composerData = json_decode((string) File::get($composerJsonPath), true);
            if (is_array($composerData) && isset($composerData['repositories']) && is_array($composerData['repositories'])) {
                foreach ($composerData['repositories'] as $repo) {
                    if (is_array($repo) && ($repo['type'] ?? '') === 'path') {
                        return new CheckResult(
                            check: 'isolated',
                            status: 'failed',
                            output: 'Isolated verification failed: composer.json contains path repositories. Dependencies must be independently installable from Packagist.',
                            durationSeconds: (float) round(microtime(true) - $startTime, 3),
                        );
                    }
                }
            }

            // Step 3: Require phpunit.xml or phpunit.xml.dist
            $phpunitXml = null;
            foreach (['phpunit.xml', 'phpunit.xml.dist'] as $file) {
                if (File::exists($tempDir.DIRECTORY_SEPARATOR.$file)) {
                    $phpunitXml = $file;
                    break;
                }
            }

            if ($phpunitXml === null) {
                return new CheckResult(
                    check: 'isolated',
                    status: 'skipped',
                    output: 'Isolated verification skipped: no phpunit.xml found in package.',
                    durationSeconds: (float) round(microtime(true) - $startTime, 3),
                );
            }

            // Step 4 & 5: Setup isolated environment
            $testEnv = (array) (Config::get('package-audit.test_environment') ?? [
                'APP_ENV' => 'testing',
                'CACHE_STORE' => 'array',
                'SESSION_DRIVER' => 'array',
                'QUEUE_CONNECTION' => 'sync',
                'MAIL_MAILER' => 'array',
            ]);

            $env = array_merge($testEnv, [
                'COMPOSER_HOME' => $tempDir.DIRECTORY_SEPARATOR.'composer-home',
                'COMPOSER_VENDOR_DIR' => $tempDir.DIRECTORY_SEPARATOR.'vendor',
                'COMPOSER_BIN_DIR' => $tempDir.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin',
            ]);

            $cacheDir = $this->resolveComposerCacheDir();
            if ($cacheDir !== null) {
                $env['COMPOSER_CACHE_DIR'] = $cacheDir;
            }

            $timeout = (int) (Config::get('package-audit.timeouts.isolated') ?? 300);

            // Step 7: composer install --prefer-dist --no-interaction --no-progress
            $installProcess = Process::path($tempDir)
                ->timeout($timeout)
                ->env($env)
                ->run(['composer', 'install', '--prefer-dist', '--no-interaction', '--no-progress']);

            if (! $installProcess->successful()) {
                return new CheckResult(
                    check: 'isolated',
                    status: 'failed',
                    output: "Isolated composer install failed:\n".$installProcess->errorOutput()."\n".$installProcess->output(),
                    durationSeconds: (float) round(microtime(true) - $startTime, 3),
                );
            }

            // Step 8: Run isolated tests
            $runnerName = File::exists($tempDir.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'Pest.php') ? 'pest' : 'phpunit';
            $isolatedRunner = $this->resolveIsolatedBinary($tempDir.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin', $runnerName);

            if ($isolatedRunner === null) {
                return new CheckResult(
                    check: 'isolated',
                    status: 'failed',
                    output: "Declare the test runner ({$runnerName}) in package require-dev; isolated vendor has no binary.",
                    durationSeconds: (float) round(microtime(true) - $startTime, 3),
                );
            }

            $testProcess = Process::path($tempDir)
                ->timeout($timeout)
                ->env($env)
                ->run([$isolatedRunner, '-c', $phpunitXml, '--fail-on-empty-test-suite']);

            $duration = (float) round(microtime(true) - $startTime, 3);
            $output = trim($testProcess->output()."\n".$testProcess->errorOutput());

            return new CheckResult(
                check: 'isolated',
                status: $testProcess->successful() ? 'passed' : 'failed',
                output: $output !== '' ? $output : 'Isolated package installation and test suite passed successfully.',
                durationSeconds: $duration,
            );
        } catch (Throwable $e) {
            return new CheckResult(
                check: 'isolated',
                status: 'failed',
                output: 'Isolated verification exception: '.$e->getMessage(),
                durationSeconds: (float) round(microtime(true) - $startTime, 3),
            );
        } finally {
            // Step 9: Cleanup in finally block
            $this->safeDeleteDirectory($tempDir);
        }
    }

    /**
     * Check 7: README standard compliance.
     */
    public function checkReadme(string $packagePath): CheckResult
    {
        $startTime = microtime(true);
        $readmePath = $packagePath.DIRECTORY_SEPARATOR.'README.md';

        if (! File::exists($readmePath)) {
            return new CheckResult(
                check: 'readme',
                status: 'failed',
                output: 'README.md file does not exist in package root.',
                durationSeconds: (float) round(microtime(true) - $startTime, 3),
            );
        }

        $content = (string) File::get($readmePath);

        // Header check
        $hasMarkdownHeader = preg_match('/^#\s+[^\r\n]+/m', $content) === 1;
        $hasHtmlHeader = preg_match('/<h1\s+align=["\']center["\']>/i', $content) === 1;

        if (! $hasMarkdownHeader && ! $hasHtmlHeader) {
            return new CheckResult(
                check: 'readme',
                status: 'failed',
                output: 'README.md is missing a title header (# Heading or <h1 align="center">).',
                durationSeconds: (float) round(microtime(true) - $startTime, 3),
            );
        }

        $requiredSections = (array) (Config::get('package-audit.checks.readme.required_sections') ?? [
            'Requirements',
            'Installation',
            'Usage',
            'Testing',
            'License',
        ]);

        $missingSections = [];
        foreach ($requiredSections as $section) {
            $pattern = '/^#+\s+.*?\b'.preg_quote($section, '/').'\b/mi';
            if (preg_match($pattern, $content) !== 1) {
                $missingSections[] = $section;
            }
        }

        if (! empty($missingSections)) {
            return new CheckResult(
                check: 'readme',
                status: 'failed',
                output: 'README.md is missing required section(s): '.implode(', ', $missingSections),
                durationSeconds: (float) round(microtime(true) - $startTime, 3),
            );
        }

        return new CheckResult(
            check: 'readme',
            status: 'passed',
            output: 'README.md contains title and all required sections: '.implode(', ', $requiredSections),
            durationSeconds: (float) round(microtime(true) - $startTime, 3),
        );
    }

    /**
     * Check 8: Export-ignore directives in .gitattributes.
     */
    public function checkExportIgnore(string $packagePath): CheckResult
    {
        $startTime = microtime(true);
        $gitattributesPath = $packagePath.DIRECTORY_SEPARATOR.'.gitattributes';

        if (! File::exists($gitattributesPath)) {
            return new CheckResult(
                check: 'export_ignore',
                status: 'failed',
                output: '.gitattributes file missing in package root.',
                durationSeconds: (float) round(microtime(true) - $startTime, 3),
            );
        }

        $content = (string) File::get($gitattributesPath);
        if (! str_contains($content, 'export-ignore')) {
            return new CheckResult(
                check: 'export_ignore',
                status: 'failed',
                output: '.gitattributes exists but has no "export-ignore" directives configured.',
                durationSeconds: (float) round(microtime(true) - $startTime, 3),
            );
        }

        return new CheckResult(
            check: 'export_ignore',
            status: 'passed',
            output: '.gitattributes contains export-ignore directives for clean archive distribution.',
            durationSeconds: (float) round(microtime(true) - $startTime, 3),
        );
    }

    /**
     * Check if a specific check is enabled in config.
     */
    protected function isCheckEnabled(string $checkKey): bool
    {
        $value = Config::get("package-audit.checks.{$checkKey}", true);

        if (is_array($value)) {
            return (bool) ($value['enabled'] ?? true);
        }

        return (bool) $value;
    }

    /**
     * Resolve host binary path in vendor/bin.
     */
    public function resolveHostBinary(string $binaryName): ?string
    {
        $vendorBin = function_exists('base_path') ? base_path('vendor/bin') : (getcwd().'/vendor/bin');

        if (PHP_OS_FAMILY === 'Windows') {
            foreach (['.bat', '.exe', '.cmd'] as $ext) {
                $candidate = $vendorBin.DIRECTORY_SEPARATOR.$binaryName.$ext;
                if (File::exists($candidate)) {
                    return $candidate;
                }
            }
        }

        $candidate = $vendorBin.DIRECTORY_SEPARATOR.$binaryName;
        if (File::exists($candidate)) {
            return $candidate;
        }

        return null;
    }

    /**
     * Resolve binary in a specific bin directory.
     */
    protected function resolveIsolatedBinary(string $binDir, string $binaryName): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            foreach (['.bat', '.exe', '.cmd'] as $ext) {
                $candidate = $binDir.DIRECTORY_SEPARATOR.$binaryName.$ext;
                if (File::exists($candidate)) {
                    return $candidate;
                }
            }
        }

        $candidate = $binDir.DIRECTORY_SEPARATOR.$binaryName;
        if (File::exists($candidate)) {
            return $candidate;
        }

        return null;
    }

    /**
     * Resolve default Composer cache directory for current operating system.
     */
    protected function resolveComposerCacheDir(): ?string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $localAppData = getenv('LOCALAPPDATA');
            if ($localAppData !== false && $localAppData !== '') {
                return $localAppData.DIRECTORY_SEPARATOR.'Composer';
            }
        } else {
            $home = getenv('HOME');
            if ($home !== false && $home !== '') {
                return PHP_OS_FAMILY === 'Darwin'
                    ? $home.'/Library/Caches/composer'
                    : $home.'/.cache/composer';
            }
        }

        return null;
    }

    /**
     * Resolve canonical package name from composer.json.
     */
    public function resolvePackageName(string $packagePath): string
    {
        $composerPath = $packagePath.DIRECTORY_SEPARATOR.'composer.json';
        if (File::exists($composerPath)) {
            $data = json_decode((string) File::get($composerPath), true);
            if (is_array($data) && ! empty($data['name'])) {
                return (string) $data['name'];
            }
        }

        return basename($packagePath);
    }

    /**
     * Resolve package version from composer.json, git tag, or default to 0.1.0.
     */
    public function resolvePackageVersion(string $packagePath): string
    {
        $composerPath = $packagePath.DIRECTORY_SEPARATOR.'composer.json';
        if (File::exists($composerPath)) {
            $data = json_decode((string) File::get($composerPath), true);
            if (is_array($data) && ! empty($data['version'])) {
                return (string) $data['version'];
            }
        }

        $tagProcess = Process::path($packagePath)->run(['git', 'describe', '--tags', '--exact-match']);
        if ($tagProcess->successful() && trim($tagProcess->output()) !== '') {
            return ltrim(trim($tagProcess->output()), 'v');
        }

        $latestTagProcess = Process::path($packagePath)->run(['git', 'describe', '--tags', '--abbrev=0']);
        if ($latestTagProcess->successful() && trim($latestTagProcess->output()) !== '') {
            return ltrim(trim($latestTagProcess->output()), 'v');
        }

        return 'dev-main';
    }

    public function resolveGitCommit(string $packagePath): string
    {
        $process = Process::path($packagePath)->run(['git', 'rev-parse', 'HEAD']);

        return $process->successful() ? trim($process->output()) : '';
    }

    public function resolveGitBranch(string $packagePath): string
    {
        $process = Process::path($packagePath)->run(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);

        return $process->successful() ? trim($process->output()) : 'main';
    }

    public function normalizePath(string $path): string
    {
        if (function_exists('base_path') && ! File::isDirectory($path)) {
            $candidate = base_path($path);
            if (File::isDirectory($candidate)) {
                return realpath($candidate) ?: $candidate;
            }
        }

        return realpath($path) ?: $path;
    }

    /**
     * Recursively copy a directory excluding specific directory names.
     *
     * @param  array<int, string>  $exclude
     */
    protected function copyDirectoryExcept(string $source, string $destination, array $exclude): void
    {
        File::ensureDirectoryExists($destination);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $subPath = $iterator->getSubPathName();
            $parts = explode(DIRECTORY_SEPARATOR, $subPath);
            if (count(array_intersect($parts, $exclude)) > 0) {
                continue;
            }

            $target = $destination.DIRECTORY_SEPARATOR.$subPath;
            if ($item->isDir()) {
                File::ensureDirectoryExists($target);
            } else {
                File::ensureDirectoryExists(dirname($target));
                File::copy($item->getPathname(), $target);
            }
        }
    }

    /**
     * Safely delete a directory tree even with read-only files on Windows.
     */
    protected function safeDeleteDirectory(string $directory): void
    {
        if (! File::isDirectory($directory)) {
            return;
        }

        try {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $path = $fileinfo->getRealPath();
                if ($path === false) {
                    continue;
                }
                @chmod($path, 0777);
                if ($fileinfo->isDir()) {
                    @rmdir($path);
                } else {
                    @unlink($path);
                }
            }

            @rmdir($directory);
        } catch (Throwable) {
            // Ignore temporary directory cleanup failures
        }
    }
}
