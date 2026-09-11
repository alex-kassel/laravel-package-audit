<?php

declare(strict_types=1);

namespace AlexKassel\PackageAudit;

use AlexKassel\PackageAudit\Commands\AuditCommand;
use AlexKassel\PackageAudit\Services\AuditRunner;
use AlexKassel\PackageAudit\Services\CertificateVerifier;
use AlexKassel\PackageAudit\Services\FingerprintCalculator;
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
            ]);

            $this->publishes([
                __DIR__.'/../config/package-audit.php' => config_path('package-audit.php'),
            ], 'package-audit-config');

            $this->publishes([
                __DIR__.'/../stubs/github-audit-workflow.yml.stub' => base_path('.github/workflows/package-audit.yml'),
            ], 'package-audit-stubs');
        }
    }
}
