<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit\Commands;

use AlexKassel\PackageAudit\Services\SkillInstaller;
use Illuminate\Console\Command;

class InstallSkillCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:audit:install
        {--path= : Explicit target directory for skills}
        {--force : Overwrite existing skill files}
        {--symlink : Create a symlink instead of copying}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Package Audit skill into the host project for AI agents';

    /**
     * Execute the console command.
     */
    public function handle(SkillInstaller $installer): int
    {
        $explicitPath = $this->option('path');
        $force = (bool) $this->option('force');
        $symlink = (bool) $this->option('symlink');

        $baseSkillsDir = is_string($explicitPath) && $explicitPath !== ''
            ? (str_starts_with($explicitPath, '/') || str_contains($explicitPath, ':') ? $explicitPath : base_path($explicitPath))
            : $installer->detectSkillsDirectory();

        $targetDir = $baseSkillsDir.DIRECTORY_SEPARATOR.'package-audit';

        if ($installer->isInstalled($baseSkillsDir) && ! $force) {
            $this->comment("Package Audit skill is already installed at: {$targetDir}");
            $this->line('Use <info>--force</info> to overwrite with the latest version.');

            return self::SUCCESS;
        }

        $success = $installer->install(
            targetSkillsDir: $baseSkillsDir,
            force: $force,
            symlink: $symlink
        );

        if (! $success) {
            $this->error("Failed to install Package Audit skill to: {$targetDir}");

            return self::FAILURE;
        }

        $this->info("🛡️  Package Audit Skill successfully installed to: {$targetDir}");
        $this->line('   AI agents (Antigravity, Cursor, Claude Code) will now automatically detect this skill.');

        return self::SUCCESS;
    }
}
