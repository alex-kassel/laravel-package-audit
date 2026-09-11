---
name: package-audit
description: >-
  Use this skill when auditing, certifying, or verifying quality of PHP/Laravel packages
  (e.g., "проведи полный аудит пакета", "полный аудит", "audit package", "run package audit").
---

# Package Audit & Certification Skill

This skill provides an autonomous, industrial audit and certification framework for Laravel/PHP packages. It couples deterministic mechanical CLI verification with deep cognitive architectural evaluation guided by the "Laravel First" philosophy.

The skill is **fully standalone**: it can be distributed as a standalone skill unit (e.g. copied to `.agents/skills/package-audit/` in any project) and automatically bootstraps the required tooling.

---

## Directory Structure & Resources

- [**Master Orchestrator Guide**](./references/orchestrator.md): Lifecycle coordination, 3-section reporting protocol, and remediation rules.
- [**7 Specialized Audit Contracts**](./references/contracts/):
  - [`01_architecture_api.md`](./references/contracts/01_architecture_api.md) — Public API surface, "Laravel First" abstractions, Octane safety, BC safety.
  - [`02_code_quality.md`](./references/contracts/02_code_quality.md) — Strict types, PHPStan Level 8+/max, Pint formatting.
  - [`03_database.md`](./references/contracts/03_database.md) — Migrations, table prefixes, multi-DB compatibility.
  - [`04_security_isolation.md`](./references/contracts/04_security_isolation.md) — Container hijacking prevention, secret leaks, injection protection.
  - [`05_composer_supply_chain.md`](./references/contracts/05_composer_supply_chain.md) — `composer validate --strict`, export-ignore, lockfile hygiene.
  - [`06_testing_compatibility.md`](./references/contracts/06_testing_compatibility.md) — PHPUnit/Pest coverage, test isolation, Testbench.
  - [`07_consumer_release.md`](./references/contracts/07_consumer_release.md) — Fresh sandbox smoke test, README quickstart, `AUDIT.json`.
- **Templates**:
  - Manifest Template: [`resources/templates/audit-manifest.template.json`](./resources/templates/audit-manifest.template.json)
- **Local Run Artifacts**: Stored in target package `.audit/<timestamp>/` (gitignored).

---

## Operational Workflow

### Phase 0: Tooling Verification & Self-Bootstrapping
When the skill is invoked, the agent checks if `php artisan package:audit` is available:
```bash
php artisan list package:audit
```
- **If available**: Proceed directly to Phase 1.
- **If missing (Self-Bootstrapping)**:
  Install the audit engine into `require-dev`:
  ```bash
  composer require --dev alex-kassel/laravel-package-audit
  ```
- **Direct CLI Fallback**: If the environment prevents installing composer packages, the agent directly executes the underlying tools (`vendor/bin/pint --test`, `vendor/bin/phpstan`, `vendor/bin/phpunit`, `composer validate --strict`).

---

### Phase 1: Audit-Only & Human Decision Gate (Read-Only)

1. **Mechanical Baseline Diagnosis**:
   Run the package audit in non-mutating mode:
   ```bash
   php artisan package:audit <path/to/package> --json --no-commit
   ```
2. **Cognitive Domain Evaluation**:
   Inspect the package code against the 7 specialized contracts in [`references/contracts/`](./references/contracts/).
3. **Compile Local Review Artifacts**:
   Store diagnostic details in `.audit/<timestamp>/`:
   - `decisions.md`: Architectural questions, API breaking changes, and table prefix choices.
   - `FINAL-REPORT.md`: Comprehensive domain findings and metrics.
4. **Mandatory 3-Section Chat Output Protocol**:
   The agent MUST present findings to the user formatted into 3 distinct sections:
   - **Section 1: Test & Tooling Baseline**: Exact command execution results (Git, Pint, PHPStan, Tests, Composer, Sandbox).
   - **Section 2: Mechanical & Routine Fixes**: Non-invasive fixes planned for Phase 2 (Pint formatting, PHPStan type annotations, missing docblocks, syntax cleanups).
   - **Section 3: Human Decisions & Architectural Interventions**: High-impact items requiring human confirmation (API modifications, adding/removing public methods, schema alterations).
     - **Mandatory Technical Recommendation & Rationale**: For every non-obvious point or dilemma, the agent MUST state its explicit recommendation (`(Recommended)`) accompanied by concrete technical justification.
5. **🛑 MANDATORY HUMAN GATE (HARD STOP)**:
   - **Zero Code Modification**: The agent is **strictly prohibited** from modifying source files, running mutating commands, creating git commits, or proceeding to Phase 2 in the same turn.
   - The agent **MUST stop calling tools and end the turn** immediately after outputting the Phase 1 report, awaiting user confirmation.

---

### Phase 2: Remediation & Certification (After User Confirmation)

1. **Sequential Remediation Graph**:
   Execute fixes in order: Schema/Migrations → Public API & ServiceProvider → Code quality & types → Tests.
2. **Atomic Semantic Commits**:
   Create atomic commits for discrete defect groups (`fix(types): ...`, `style: ...`, `chore: ...`).
3. **Certification & Cryptographic Receipt**:
   Run the certifying audit:
   ```bash
   php artisan package:audit <path/to/package>
   ```
   - Automatically generates the tamper-proof `AUDIT.json` certificate recording Git `tree_hash` and normalized check output fingerprints.
4. **Verification**:
   Verify certificate integrity:
   ```bash
   php artisan package:audit <path/to/package> --verify
   ```
