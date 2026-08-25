# AGENT CONTRACT: 06_TESTING_COMPATIBILITY

## 1. Role & Objective
Act as a senior test engineer responsible for proving that a public Laravel package actually works across its declared support range.

Determine whether package behavior is sufficiently tested and whether declared PHP, Laravel, and database compatibility is real and proven by tests.

---

## 2. Scope of Investigation

### 1. Test Harness & Infrastructure
- Test runner configuration: PHPUnit (`phpunit.xml`) / Pest (`Pest.php`).
- Integration with `orchestra/testbench` for full Laravel service container and facade mocking.
- Clean and dynamic autoloader bootstrap (`tests/bootstrap.php`).

### 2. Test Coverage & Quality
- **Core Logic & Paths**: Happy paths, error paths, custom exception assertions, edge cases.
- **Database & State**: Real database integration tests, transaction rollback checks, migration tests.
- **Public API Coverage**: Verification that all documented public methods and contracts have active test coverage.
- **Assertion Quality**: Absence of brittle tests, vacuous assertions, over-mocking, or tests asserting private implementation details.

### 3. Boundary & Compatibility Matrix
- Audit declared support from `composer.json` (PHP versions, Laravel versions).
- **Prefer-Lowest Resolution**: Test dependency resolution with `composer update --prefer-lowest` to verify the minimum declared version boundary.
- **Prefer-Stable Resolution**: Test dependency resolution with `composer update --prefer-stable` for the latest boundary.
- Multi-database engine test runs (SQLite in-memory, MySQL, PostgreSQL where configured).

### 4. CI Matrix & Automation
- Audit GitHub Actions workflows (`.github/workflows/`) for matrix test definitions across PHP (8.2, 8.3, 8.4) and Laravel (10.x, 11.x, 12.x).

---

## 3. Tools & Execution
Execute from repository root using the unified host `vendor/`:
- `php artisan test -c packages/<vendor>/<package-name>/phpunit.xml`
- `./vendor/bin/phpunit -c packages/<vendor>/<package-name>/phpunit.xml`
- Boundary matrix runs:
  - CI Matrix: Automated via `.github/workflows/` across PHP and Laravel matrix.
  - Local Boundary Check: If tested locally, execute without modifying package subdirectories or creating local `vendor/` folders.
- Optional/Advisory: Mutation testing (Infection) and code coverage reporting if configured.

---

## 4. Mandatory Rules & Boundaries
- **Unified Root Vendor Rule**: NEVER run `composer install` inside package subdirectories. Follow [Unified Root `vendor/` & Package Tooling Standard](file:///.agents/rules/architecture.md#3-unified-root-vendor--package-tooling-standard).
- **Dynamic Autoloader Bootstrap**: Verify that `packages/<vendor>/<package-name>/tests/bootstrap.php` resolves the autoloader dynamically and registers the test namespace via `$autoloader->addPsr4(...)`.
- **READ-ONLY**: Never modify package test files or assertions during Phase 1.
- **Evidence-Based Compatibility**: Do NOT claim compatibility merely because `composer.json` declares it; compatibility must be demonstrated through test execution.
- **Matrix Failure Distinction**: Explicitly distinguish between genuine package defects and local environment tool unavailability.

---

## 5. Output Deliverables
1. Machine-readable JSON: `.audit/findings/testing.json` (adhering to `schema/agent-report.schema.json` with `agent_id: "testing"`).
2. Human-readable Markdown: `.audit/reports/testing.md`.

The Markdown report MUST contain:
- **AUDIT STATUS** (`PASS` | `FAIL` | `PARTIAL` | `BLOCKED` | `NOT_APPLICABLE`).
- **TEST EXECUTION SUMMARY** (total tests, assertions, execution time, pass/fail metrics).
- **COMPATIBILITY MATRIX RESULTS** (minimum vs stable versions, engine test outcomes).
- Detailed findings breakdown with failing assertions, reproduction commands, and recommendations.

