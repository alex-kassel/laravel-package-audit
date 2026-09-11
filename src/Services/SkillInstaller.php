<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class SkillInstaller
{
    public function __construct(
        private readonly ?string $sourcePath = null
    ) {}

    /**
     * Get the source directory of the skill files.
     */
    public function getSourcePath(): string
    {
        return $this->sourcePath ?? dirname(__DIR__, 2);
    }

    /**
     * Detect the single most appropriate skills directory for the host project.
     *
     * Smart Single-Target Priority:
     * 1. Explicit configuration (config('package-audit.skills_path'))
     * 2. Existing .agents/skills (primary standard for Antigravity, Claude, etc.)
     * 3. Existing .cursor/skills (if user only uses Cursor)
     * 4. Existing .claude/skills (if user only uses Claude Code)
     * 5. Default fallback to .agents/skills
     */
    public function detectSkillsDirectory(): string
    {
        $configured = config('package-audit.skills_path');
        if (is_string($configured) && $configured !== '') {
            return base_path($configured);
        }

        if (is_dir(base_path('.agents/skills'))) {
            return base_path('.agents/skills');
        }

        if (is_dir(base_path('.cursor/skills'))) {
            return base_path('.cursor/skills');
        }

        if (is_dir(base_path('.claude/skills'))) {
            return base_path('.claude/skills');
        }

        return base_path('.agents/skills');
    }

    /**
     * Check if the skill is already installed at the target location.
     */
    public function isInstalled(?string $targetDir = null): bool
    {
        $target = ($targetDir ?? $this->detectSkillsDirectory()).DIRECTORY_SEPARATOR.'package-audit';

        return file_exists($target.DIRECTORY_SEPARATOR.'SKILL.md');
    }

    /**
     * Install the skill files to the target directory.
     */
    public function install(?string $targetSkillsDir = null, bool $force = false, bool $symlink = false): bool
    {
        $baseSkillsDir = $targetSkillsDir ?? $this->detectSkillsDirectory();
        $targetDir = $baseSkillsDir.DIRECTORY_SEPARATOR.'package-audit';
        $sourceDir = $this->getSourcePath();

        if (file_exists($targetDir)) {
            if (! $force) {
                return false;
            }
            $this->deleteDirectory($targetDir);
        }

        if (! is_dir($baseSkillsDir) && ! @mkdir($baseSkillsDir, 0755, true) && ! is_dir($baseSkillsDir)) {
            return false;
        }

        if ($symlink) {
            if (@symlink($sourceDir, $targetDir)) {
                return true;
            }
        }

        // Copy SKILL.md
        if (! is_dir($targetDir) && ! @mkdir($targetDir, 0755, true) && ! is_dir($targetDir)) {
            return false;
        }

        $sourceSkill = $sourceDir.DIRECTORY_SEPARATOR.'SKILL.md';
        if (file_exists($sourceSkill)) {
            @copy($sourceSkill, $targetDir.DIRECTORY_SEPARATOR.'SKILL.md');
        }

        // Copy references/
        $sourceReferences = $sourceDir.DIRECTORY_SEPARATOR.'references';
        if (is_dir($sourceReferences)) {
            $this->copyDirectory($sourceReferences, $targetDir.DIRECTORY_SEPARATOR.'references');
        }

        // Copy resources/
        $sourceResources = $sourceDir.DIRECTORY_SEPARATOR.'resources';
        if (is_dir($sourceResources)) {
            $this->copyDirectory($sourceResources, $targetDir.DIRECTORY_SEPARATOR.'resources');
        }

        return file_exists($targetDir.DIRECTORY_SEPARATOR.'SKILL.md');
    }

    /**
     * Recursively copy a directory.
     */
    private function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($destination)) {
            @mkdir($destination, 0755, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            $subPath = $iterator->getSubPathName();
            $destPath = $destination.DIRECTORY_SEPARATOR.$subPath;

            if ($item->isDir()) {
                if (! is_dir($destPath)) {
                    @mkdir($destPath, 0755, true);
                }
            } else {
                $destParent = dirname($destPath);
                if (! is_dir($destParent)) {
                    @mkdir($destParent, 0755, true);
                }
                @copy($item->getRealPath(), $destPath);
            }
        }
    }

    /**
     * Recursively delete a directory or symlink.
     */
    private function deleteDirectory(string $dir): void
    {
        if (is_link($dir)) {
            @unlink($dir) || @rmdir($dir);

            return;
        }

        if (! is_dir($dir)) {
            if (file_exists($dir)) {
                @unlink($dir);
            }

            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            if (is_dir($path) && ! is_link($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path) || @rmdir($path);
            }
        }

        @rmdir($dir);
    }
}
