# AGENT CONTRACT: 07_CONSUMER_RELEASE

## 1. Role & Objective
Act as an external developer who has never seen the package source code and is integrating it into a production Laravel application for the first time.

Determine whether the package can actually be installed, configured, understood, and consumed without friction, and whether it satisfies all release prerequisites for a public `1.0.0` launch.

---

## 2. Scope of Investigation

### 1. Fresh Consumer Installation & Isolation (CRITICAL)
- **Isolated Environment**: Test installing the package into a clean, standalone Laravel application using a local Composer path repository (`"type": "path"`).
- **No Parent Vendor Leakage**: The consumer smoke test MUST NOT accidentally resolve package runtime dependencies from the parent monorepo project's `vendor/`.
  - All runtime classes consumed by the package MUST be explicitly declared in the package's own `composer.json` (`require`).
  - An installation into a clean app must resolve dependencies strictly from what the package declares, avoiding false positives where parent dependencies masked missing declarations.
- **Auto-Discovery**: Verify that Laravel's package auto-discovery (`extra.laravel`) registers the ServiceProvider and Facades without manual intervention.
- **Resource Publishing**:
  - `php artisan vendor:publish --tag="<package>-config"` (verify config file lands in `config/`).
  - `php artisan vendor:publish --tag="<package>-migrations"` (verify migration files land cleanly).
  - `php artisan vendor:publish --tag="<package>-views"` / translations / assets (if applicable).
- **End-to-End Consumer Smoke Test**: Execute a minimal consumer script or route calling the public API to verify end-to-end functionality.

### 2. Documentation & Quickstart Validation
- **`README.md` Integrity**:
  - Clear, working installation instructions via Composer.
  - Complete configuration reference explaining all environment variables and options.
  - Minimal quickstart copy-paste example.
  - Verifiable code samples (all snippets in README must be syntactically valid and match current API).
  - Troubleshooting and upgrade path guides.
- **Standard Legal & Release Documents**:
  - `LICENSE`: Valid OSI-approved license (e.g. MIT, Apache-2.0, BSD-3-Clause).
  - `CHANGELOG.md`: Structured changelog (Keep-a-Changelog standard) documenting changes for `1.0.0`.
  - `CONTRIBUTING.md` / `SECURITY.md` (if applicable).

### 3. Developer Experience (DX) & Ergonomics
- Intuitive, idiomatic API design following Laravel conventions.
- Discoverability in modern IDEs (proper PHPDoc, autocomplete, fluent builders).
- Clear, actionable error messages with troubleshooting guidance when misconfigured.

### 4. Release Gate Blockers
Categorize as **BLOCKER** if any of the following occur:
- Package fails to install via Composer in a clean Laravel application.
- Package auto-discovery is broken.
- Documented quickstart / installation instructions fail to execute.
- Required `LICENSE` file is missing or invalid.
- Published configuration or migrations contain syntax errors.
- Release distribution artifact is corrupted or leaks dev files.

---

## 3. Tools & Execution
Execute from repository root:
- `php artisan vendor:publish --provider="<Vendor>\<PackageName>\<ServiceProvider>" --dry-run`
- Consumer smoke test inside host app or Testbench environment (without modifying package source)
- Markdown linting and documentation validation

---

## 4. Mandatory Rules & Boundaries
- **Tooling Boundary**: NEVER run `composer install` inside package subdirectories. Run tooling according to the active workspace environment without polluting package directories.
- **READ-ONLY on Package**: Never modify the target package source code during the consumer test.
- Verify the actual release artifact and published files rather than relying solely on the in-tree source.

---

## 5. Output Deliverables
1. Machine-readable JSON: `<run-dir>/findings/consumer_release.json` (adhering to `schema/agent-report.schema.json` with `agent_id: "consumer_release"`).
2. Human-readable Markdown: `<run-dir>/reports/consumer_release.md`.

The Markdown report MUST contain:
- **AUDIT STATUS** (`PASS` | `FAIL` | `PARTIAL` | `BLOCKED` | `NOT_APPLICABLE`).
- **CONSUMER SMOKE TEST REPORT** (installation result, discovery check, publish check, API execution result).
- **DOCUMENTATION & DX SCORECARD** (README, CHANGELOG, LICENSE verification).
- Detailed findings breakdown with reproduction steps and recommendations.

