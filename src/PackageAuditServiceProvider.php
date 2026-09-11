<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit;

use AlexKassel\PackageAudit\Commands\AuditCommand;
use AlexKassel\PackageAudit\Commands\InstallSkillCommand;
use AlexKassel\PackageAudit\Services\AuditRunner;
use AlexKassel\PackageAudit\Services\CertificateVerifier;
use AlexKassel\PackageAudit\Services\FingerprintCalculator;
use AlexKassel\PackageAudit\Services\SkillInstaller;
use Illuminate\Support\ServiceProvider;

class PackageAuditServiceProvider extends ServiceProvider
{
    public const VERSION = '1.0.0';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/package-audit.php',
            'package-audit'
        );

        $this->app->singleton(FingerprintCalculator::class, function () {
            return new FingerprintCalculator;
        });

        $this->app->singleton(SkillInstaller::class, function () {
            return new SkillInstaller;
        });

        $this->app->singleton(AuditRunner::class, function ($app) {
            return new AuditRunner(
                fingerprintCalculator: $app->make(FingerprintCalculator::class)
            );
        });

        $this->app->singleton(CertificateVerifier::class, function ($app) {
            return new CertificateVerifier(
                auditRunner: $app->make(AuditRunner::class),
                fingerprintCalculator: $app->make(FingerprintCalculator::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AuditCommand::class,
                InstallSkillCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/package-audit.php' => config_path('package-audit.php'),
            ], 'package-audit-config');

            $this->publishes([
                __DIR__.'/../stubs/github-audit-workflow.yml.stub' => base_path('.github/workflows/package-audit.yml'),
            ], 'package-audit-stubs');

            $this->publishes([
                __DIR__.'/../SKILL.md' => base_path('.agents/skills/package-audit/SKILL.md'),
                __DIR__.'/../references' => base_path('.agents/skills/package-audit/references'),
                __DIR__.'/../resources' => base_path('.agents/skills/package-audit/resources'),
            ], 'package-audit-skill');

            $this->autoPublishSkill();
        }
    }

    /**
     * Automatically materialize the skill into the project during local development discovery.
     */
    protected function autoPublishSkill(): void
    {
        if ($this->app->isProduction()) {
            return;
        }

        if (! config('package-audit.auto_publish_skill', true)) {
            return;
        }

        /** @var SkillInstaller $installer */
        $installer = $this->app->make(SkillInstaller::class);

        if (! $installer->isInstalled()) {
            $installer->install();
        }
    }
}
