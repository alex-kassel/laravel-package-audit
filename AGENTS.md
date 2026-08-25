# AGENTS.MD — Laravel Package Audit Framework

Welcome to the **Laravel Package Audit Framework** repository. This repository defines an industrial, evidence-based, 360-degree audit and release gating system for packages in the **Laravel / Composer** ecosystem.

---

## 📌 1. Framework Components Index

- **Master Orchestrator**: [`orchestrator.md`](orchestrator.md) — Orchestrates the 2-phase lifecycle, validates JSON schemas, builds the human decision sheet, and controls release gating.
- **Audit Registry & Live Dashboard**: [`DASHBOARD.md`](DASHBOARD.md) — Live audit status matrix and verdict overview across all packages.
- **Developer Guide & Manual**: [`README.md`](README.md) — Comprehensive technical reference and developer manual.
- **Environment Manifest Template**: [`audit-manifest.template.json`](audit-manifest.template.json) — Snapshot format for environment, PHP/Composer/Laravel versions, and support matrix.
- **Specialized Agent Contracts (`agents/`)**:
  - [`01_architecture_api.md`](agents/01_architecture_api.md): Public API Surface, ServiceProviders, Facades, BC breaks, layer purity.
  - [`02_code_quality.md`](agents/02_code_quality.md): Strict typing, Pint, PHPStan (highest reasonable strictness), baseline integrity, exception correctness.
  - [`03_database.md`](agents/03_database.md): Migrations, schema isolation, queries, index coverage, transaction safety, multi-DB support.
  - [`04_security_isolation.md`](agents/04_security_isolation.md): Vulnerabilities, injection vectors, host container isolation.
  - [`05_composer_supply_chain.md`](agents/05_composer_supply_chain.md): Manifest validation, dependency segregation, `.gitattributes export-ignore`, release zip check.
  - [`06_testing_compatibility.md`](agents/06_testing_compatibility.md): PHPUnit/Pest suite, Testbench, boundary matrices (`prefer-lowest` vs `prefer-stable`).
  - [`07_consumer_release.md`](agents/07_consumer_release.md): Fresh consumer smoke test, `vendor:publish`, README/CHANGELOG/LICENSE.
- **Validation Schemas (`schema/`)**:
  - [`finding.schema.json`](schema/finding.schema.json): Strict JSON schema for individual findings (severity, confidence, root_cause_id, verification).
  - [`agent-report.schema.json`](schema/agent-report.schema.json): Envelope schema for agent audit reports (status, tools_run, tools_missing).

---

## 📐 2. Core Rules for AI Agents Working in this Repository

1. **Language Policy**:
   - All framework contracts, prompts, schemas, comments, documentation, and commit messages MUST be written in **English**.
2. **Evidence-Based Philosophy**:
   - Strictly enforce deterministic CLI commands, exact file locations, and confidence levels (`0.90–1.00`, `0.75–0.89`, `0.50–0.74`, `<0.50`).
   - Never claim a check passed if the tool was missing or skipped.
3. **Unified Root Vendor Compliance**:
   - Enforce that packages share the host `vendor/` autoloader via `tests/bootstrap.php` and never create nested `vendor/` folders.
4. **Honest Reporting**:
   - Record missing or failed tools under `tools_missing` / `tools_failed` with status `BLOCKED` or `PARTIAL`.
5. **Pre-flight Tooling Readiness Gate**:
   - Verify required CLI binaries (`phpstan`, `pint`, `phpunit`) in Phase 0. If any essential tool is missing, halt/prompt the user immediately on turn 1 with the exact install command before proceeding.
6. **Zero Stubs Policy**:
   - Maintain production-grade completeness across all contracts, schemas, and reports.
