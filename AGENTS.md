# AGENTS.MD — Laravel Package Audit Framework

Welcome to the **Laravel Package Audit Framework** repository. This repository defines a deterministic, evidence-based, 360-degree quality audit and pre-release verification system for packages in the **Laravel / Composer** ecosystem.

---

## 📌 1. Framework Components Index

- **Universal Agent Skill**: [`SKILL.md`](SKILL.md) — Universal Agentic Skill entry point for autonomous AI coding agents.
- **Master Orchestrator**: [`references/orchestrator.md`](references/orchestrator.md) — Coordinates the 2-phase lifecycle, validates JSON schemas, builds the human decision sheet, and enforces the Human Decision Gate.
- **Audit Contracts Reference**: [`references/audit-contracts.md`](references/audit-contracts.md) — Index and summary of all specialized domain audit contracts.
- **Specialized Agent Contracts (`references/agents/`)**:
  - [`01_architecture_api.md`](references/agents/01_architecture_api.md): Public API surface, ServiceProviders, Facades, backward compatibility, and host isolation.
  - [`02_code_quality.md`](references/agents/02_code_quality.md): Strict typing, Pint formatting, PHPStan (highest reasonable strictness), baseline integrity, and exception taxonomy.
  - [`03_database.md`](references/agents/03_database.md): Migrations, schema isolation, queries, index coverage, transaction safety, and dynamic storage contexts.
  - [`04_security_isolation.md`](references/agents/04_security_isolation.md): Vulnerabilities, injection vectors, and service container isolation.
  - [`05_composer_supply_chain.md`](references/agents/05_composer_supply_chain.md): Manifest validation, dependency segregation, `.gitattributes export-ignore`, and release archive inspection.
  - [`06_testing_compatibility.md`](references/agents/06_testing_compatibility.md): PHPUnit/Pest suite, Testbench integration, and boundary matrices (`prefer-lowest` vs `prefer-stable`).
  - [`07_consumer_release.md`](references/agents/07_consumer_release.md): Fresh consumer smoke test, `vendor:publish`, documentation, and release readiness.
- **Validation Schemas (`resources/schema/`)**:
  - [`finding.schema.json`](resources/schema/finding.schema.json): Strict JSON schema for individual findings (severity, confidence, root_cause_id, verification).
  - [`agent-report.schema.json`](resources/schema/agent-report.schema.json): Envelope schema for agent audit reports (status, tools_run, tools_missing).
- **Report & Manifest Templates (`resources/templates/`)**:
  - [`audit-manifest.template.json`](resources/templates/audit-manifest.template.json): Snapshot format for environment, PHP/Composer/Laravel versions, and support matrix.
  - [`release-gate.template.md`](resources/templates/release-gate.template.md): Standard template for pre-release verification snapshots.

---

## 📐 2. Core Rules for AI Agents Working in this Repository

1. **Language Policy**:
   - All framework contracts, prompts, schemas, comments, documentation, and commit messages MUST be written in **English**.
2. **Evidence-Based Philosophy**:
   - Strictly enforce deterministic CLI commands, exact file locations, and confidence levels (`0.90–1.00`, `0.75–0.89`, `0.50–0.74`, `<0.50`).
   - Never claim a check passed if the tool was missing or skipped.
3. **Honest Reporting**:
   - Record missing or failed tools under `tools_missing` / `tools_failed` with status `BLOCKED` or `PARTIAL`.
4. **Pre-flight Tooling Readiness Gate**:
   - Verify required CLI binaries (`phpstan`, `pint`, `phpunit`) in Phase 0. If any essential tool is missing, halt/prompt the user immediately on turn 1 with the exact install command before proceeding.
5. **Cross-Platform Compatibility**:
   - Ensure all tooling invocations and filesystem operations work seamlessly across Windows, Linux, and macOS.
6. **Engineering Humility Policy**:
   - Strictly prohibit hyperbolic, absolute, or overconfident claims ("100% ready", "flawless", "zero bugs", "official certification"). Use factual, evidence-based statements.
7. **Human-in-the-Loop Decision Gate (🛑 HARD STOP)**:
   - Phase 1 must always halt with a 3-section structured findings report. No modifying actions or Phase 2 steps may proceed without explicit human confirmation.
8. **Strict No-Stubs Policy**:
   - Maintain complete, production-grade logic across all contracts, schemas, and templates.
