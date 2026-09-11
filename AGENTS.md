# AGENTS.md — Laravel Package Audit & Certification

This repository provides an automated, self-contained audit and tamper-proof cryptographic certification package for PHP and Laravel packages.

---

## 📌 1. Package Architecture

- **Service Provider**: `src/PackageAuditServiceProvider.php`
- **Console Command**: `src/Commands/AuditCommand.php` (`php artisan package:audit`)
- **Core Services**:
  - `src/Services/AuditRunner.php` — Coordinates all 8 quality gates.
  - `src/Services/FingerprintCalculator.php` — Calculates deterministic SHA-256 fingerprint from `tree_hash` and normalized check results.
  - `src/Services/CertificateVerifier.php` — Cryptographically verifies `AUDIT.json` authenticity.
- **Data Transfer Objects**:
  - `src/DTOs/CheckResult.php` — Outcome of an individual check.
  - `src/DTOs/AuditReport.php` — Complete audit report with JSON serialization.
  - `src/DTOs/VerificationResult.php` — Verification outcome (`VERIFIED`, `FORGED`, `OUTDATED`, `MISSING`).
- **Configuration**: `config/package-audit.php`
- **Agent Skill**: `SKILL.md`
- **CI Template**: `stubs/github-audit-workflow.yml.stub`
- **Tests**: `tests/` (Unit & Feature suites with Orchestra Testbench)

---

## 📐 2. Core Rules for AI Agents Working in this Repository

1. **Self-Contained Architecture**:
   - The package must NOT depend on `workspace-development-toolkit` or bundle heavyweight dev tools (`pint`, `phpstan`, `phpunit`).
   - Host binaries must be resolved dynamically via `vendor/bin/`.
2. **Reproducible Verification**:
   - Dynamic outputs (timestamps, test durations, ephemeral temp paths) must be normalized before computing the output hash.
3. **Pint Code Style**:
   - Run `vendor/bin/pint --dirty --format agent` after any PHP changes.
4. **Automated Testing**:
   - Ensure all tests pass via `vendor/bin/phpunit -c packages/alex-kassel/laravel-package-audit/phpunit.xml.dist`.
