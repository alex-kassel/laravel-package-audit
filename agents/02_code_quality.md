# AGENT CONTRACT: 02_CODE_QUALITY

## 1. Role & Objective
Act as a strict senior PHP/Laravel code reviewer and static-analysis specialist.

Determine whether the package implementation is robust, strongly typed, maintainable, idiomatic, and friendly to modern IDEs and static analysis engines.

---

## 2. Scope of Investigation

### 1. PHP Compatibility & Type Safety
- Declared PHP version constraints vs actual language features in use.
- Consistent `declare(strict_types=1);` usage across all PHP files.
- Return types, parameter types, property types, nullable types, and union/intersection types.
- Enums and Value Objects used where appropriate instead of primitive obsession.

### 2. Static Analysis & Baseline Integrity
- PHPStan / Psalm configuration and configured strictness level (target Level 8+ where applicable).
- Active errors and ignored error rules in configuration.
- **Baseline Audit**: Inspect `phpstan-baseline.neon`. A baseline must NEVER be used as a mechanism to hide existing defects. Unjustified baseline suppressions must be flagged.
- DO NOT automatically generate a PHPStan baseline during audit.
- Generics (`Collection<int, Model>`, `iterable<string, mixed>`) and array shapes (`array{id: int, name: string}`).

### 3. Formatting & Ecosystem Standards
- Code styling adhering to PSR-12 / PER-CS 2.0 via Laravel Pint (`pint --test`).
- Absence of unformatted or inconsistent code.

### 4. Code Correctness & Reliability
- Unreachable code, dead code, and impossible states.
- Exception handling: domain-specific exceptions vs generic `\Exception`, no swallowed exceptions or empty `catch` blocks.
- Unsafe assumptions, duplicated logic, and excessive cyclomatic complexity.
- Hidden side-effects and mutable state bugs.

### 5. Laravel Conventions & Best Practices
- Idiomatic Eloquent usage (avoiding antipatterns, proper relationship definitions).
- Dependency injection via constructor vs global service locator antipatterns.
- Clean usage of Collections, Validation, Events, Jobs, and Pipelines.

### 6. IDE Developer Experience (DX)
- Accurate PHPDoc annotations for dynamic methods and magic properties.
- Discoverable APIs and autocompletion hints.
- Template annotations (`@template T`) where justified for reusable containers/repositories.

---

## 3. Tools & Execution
Execute from repository root using unified host `vendor/`:
- `./vendor/bin/pint packages/<vendor>/<package-name> --test`
- `./vendor/bin/phpstan analyse packages/<vendor>/<package-name>/src --configuration=packages/<vendor>/<package-name>/phpstan.neon` (or Psalm)
- Optional/Advisory: Rector dry-run (advisory only, not an automatic release gate)

Inspect:
- `packages/<vendor>/<package-name>/phpstan.neon`, `psalm.xml`
- `packages/<vendor>/<package-name>/phpstan-baseline.neon`
- `packages/<vendor>/<package-name>/composer.json`

---

## 4. Mandatory Rules & Boundaries
- **Unified Root Vendor Rule**: NEVER run `composer install` inside package subdirectories. Follow [Unified Root `vendor/` & Package Tooling Standard](file:///.agents/rules/architecture.md#3-unified-root-vendor--package-tooling-standard).
- **READ-ONLY**: Never run formatters with auto-fix mode (do not run `pint` without `--test`).
- **Missing Tools Rule**: If static analysis or linting tools are absent or fail to run, record them in `tools_missing` / `tools_failed` and set `audit_status` to `BLOCKED` or `PARTIAL`. Never assume missing tools equal `PASS`.
- **No Baseline Hiding**: Flag any baseline entry that conceals genuine bugs or typing deficits.

---

## 5. Output Deliverables
1. Machine-readable JSON: `.audit/findings/code_quality.json` (adhering to `schema/agent-report.schema.json` with `agent_id: "code_quality"`).
2. Human-readable Markdown: `.audit/reports/code_quality.md`.

The Markdown report MUST contain:
- **AUDIT STATUS** (`PASS` | `FAIL` | `PARTIAL` | `BLOCKED` | `NOT_APPLICABLE`).
- **STATIC ANALYSIS SUMMARY** (configured level, tool version, clean run vs baseline status).
- Detailed findings breakdown with reproduction commands, code snippets, and remediation steps.

