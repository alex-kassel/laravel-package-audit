---
name: package-audit
description: >-
  Use this skill when auditing, certifying, or verifying quality of PHP/Laravel packages
  (e.g., "проведи полный аудит пакета", "полный аудит", "audit package", "run package audit").
---

# Package Audit Skill

This skill guides AI agents and developers through auditing, remediating, and certifying Laravel/PHP packages using the self-contained `php artisan package:audit` engine.

## Audit Workflow

### Phase 1: Read-Only Diagnosis & Decision Gate
1. Run audit in JSON or console mode:
   ```bash
   php artisan package:audit <path/to/package> --json --no-commit
   ```
2. Analyze the 8 automated quality gates:
   - **`git_cleanliness`**: Working tree status and git repository validity.
   - **`composer_validate`**: Strict Composer schema validation (`composer validate --strict`).
   - **`pint`**: Laravel Pint code formatting (`--test`).
   - **`phpstan`**: Strict static analysis (Level 8+).
   - **`tests`**: Automated test suite (PHPUnit / Pest).
   - **`isolated`**: Standalone sandbox installation (`composer install` in clean temp directory, verifying no leaky host dependencies).
   - **`readme`**: Title heading and standard sections compliance (`Requirements`, `Installation`, `Usage`, `Testing`, `License`).
   - **`export_ignore`**: Clean archive distribution in `.gitattributes`.
3. Report findings clearly:
   - **Automated Tool Baseline**: Status and output of each check.
   - **Planned Routine Fixes**: Code formatting, docblocks, type annotations.
   - **Architectural / Public Decisions**: Any breaking changes or design decisions requiring human approval.
4. 🛑 **Human Decision Gate**:
   - If any architectural or breaking changes exist, halt and request human approval before making modifications.

### Phase 2: Remediation & Certification
1. Apply approved fixes to the package source.
2. Commit clean changes to Git.
3. Run the certification audit:
   ```bash
   php artisan package:audit <path/to/package>
   ```
4. Verify that `AUDIT.json` was generated and cryptographic fingerprint verified:
   ```bash
   php artisan package:audit <path/to/package> --verify
   ```
