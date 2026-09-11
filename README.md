<h1 align="center">🛡️ Laravel Package Audit & Certification</h1>

<p align="center">
  <strong>Autonomous AI Agent Skill + Deterministic CLI Quality Engine for Laravel and PHP Packages</strong>
</p>

<p align="center">
  <a href="#-why-this-exists">Why This Exists</a> •
  <a href="#-quickstart">Quickstart</a> •
  <a href="#-how-it-works-the-hybrid-model">How It Works</a> •
  <a href="#-the-7-audit-contracts">7 Audit Contracts</a> •
  <a href="#-the-8-automated-quality-gates">8 Quality Gates</a> •
  <a href="#-cryptographic-certification">Certification</a> •
  <a href="#-configuration">Configuration</a> •
  <a href="#-license">License</a>
</p>

<p align="center">
  <a href="https://github.com/alex-kassel/laravel-package-audit"><img src="https://img.shields.io/badge/Release-v1.0.0-10b981?logo=shield" alt="Framework Version"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11%20%7C%2012-ff2d20?logo=laravel&logoColor=white" alt="Laravel Support"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.2+-777bb4?logo=php&logoColor=white" alt="PHP Support"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License"></a>
</p>

---

## 💡 Why This Exists

Testing Laravel packages is notoriously hard:
- Unit tests often pass locally only because they leak dependencies from the parent workspace.
- Traditional markdown release badges ("🟢 READY") are easily faked or hallucinated by AI agents.
- Most linters check syntax, but cannot judge **architectural encapsulation**, **Octane state leaks**, **"Laravel First" idiomatic design**, or **breaking changes**.

**`alex-kassel/laravel-package-audit`** solves this by pairing two complementary forces:
1. **The Cognitive Layer (Autonomous AI Agent Skill)**: Guided by 7 specialized domain contracts, the AI agent performs architectural review, SemVer boundary evaluation, and human-in-the-loop decision gating.
2. **The Mechanical Layer (Deterministic PHP CLI)**: Executes 8 rigid quality gates in seconds, tests the package in an isolated sandbox, and issues a tamper-proof cryptographic receipt (`AUDIT.json`) anchored to Git's `tree_hash`.

---

## ⚡ Quickstart

### 1. Install the Package

Require the package as a development dependency in your Laravel host application:

```bash
composer require --dev alex-kassel/laravel-package-audit
```

> **Zero-Touch Auto-Materialization:** Upon `composer require`, Laravel runs `package:discover`, and the package **automatically materializes** the agent skill into your `.agents/skills/package-audit/` directory. AI agents (Antigravity, Cursor, Claude Code) immediately recognize it in your next session.

### 2. Using with an AI Agent (Recommended)

In your agent chat (Antigravity, Cursor, Claude Code), simply say:

```text
"Audit packages/my-vendor/my-package"
// or
"Run a full package audit and prepare release certification"
```

The agent will autonomously:
1. Run the mechanical baseline via CLI in non-mutating mode.
2. Review the codebase against the 7 specialized audit contracts.
3. Present a structured **3-Section Report** (Baseline, Planned Fixes, and Architectural Decisions with explicit `(Recommended)` rationales).
4. **🛑 STOP (Human Gate)**: Wait for your approval before modifying any code.
5. Upon approval, apply atomic semantic fixes and issue the cryptographic `AUDIT.json` certificate.

### 3. Using via Artisan CLI (Direct / CI)

You can also run audits directly from the command line without an agent:

```bash
# Run full audit and certify with AUDIT.json
php artisan package:audit packages/my-vendor/my-package

# Run audit in dry-run / inspect mode (read-only)
php artisan package:audit packages/my-vendor/my-package --no-commit --json

# Verify authenticity and integrity of a certified package
php artisan package:audit packages/my-vendor/my-package --verify
```

---

## 🧩 How It Works: The Hybrid Model

```
┌─────────────────────────────────────────────────────────────┐
│  AI AGENT SKILL (.agents/skills/package-audit/SKILL.md)     │
│  • 2-Phase Lifecycle with Mandatory Hard Stop               │
│  • 7 Specialized Audit Contracts ("Laravel First" design)    │
│  • Human Decision Gate: Structured Choices & Rationales     │
└──────────────────────────────┬──────────────────────────────┘
                               │
                Runs CLI in non-mutating mode
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│  PHP CLI ENGINE (php artisan package:audit)                 │
│  • 8 Deterministic Quality Gates (Pint, PHPStan, Tests, ...) │
│  • Isolated Consumer Sandbox (Zero Host Leakage)            │
│  • Tamper-Proof Cryptographic Receipt (AUDIT.json)          │
└─────────────────────────────────────────────────────────────┘
```

---

## 📋 The 7 Audit Contracts

The AI agent conducts deep inspections based on 7 specialized domain contracts stored in [`references/contracts/`](references/contracts/):

| # | Contract | Core Scope |
|:---:|---|---|
| **01** | [`01_architecture_api.md`](references/contracts/01_architecture_api.md) | **"Laravel First" Standard**: Prefer native framework abstractions (`Process`, `Http`, `Sleep`, `Cache`). Pure `ServiceProvider` with zero third-party wrapper bloat. Public API encapsulation, `@internal` boundaries, and BC break protection. |
| **02** | [`02_code_quality.md`](references/contracts/02_code_quality.md) | Strict typing (`declare(strict_types=1);`), return/param types, PHPStan Level 8+/max, baseline audit (no sweeping bugs under baselines), Pint styling, cross-platform path safety. |
| **03** | [`03_database.md`](references/contracts/03_database.md) | Schema reversibility (`up`/`down`), table prefix isolation (preventing collisions with host apps), foreign keys, N+1 query prevention, multi-DB engine safety (SQLite/MySQL/PostgreSQL). |
| **04** | [`04_security_isolation.md`](references/contracts/04_security_isolation.md) | Host application isolation: guarded container bindings (`bindIf`, `singletonIf`), no runtime config pollution, **Octane state safety** (no request-bound singletons), injection prevention. |
| **05** | [`05_composer_supply_chain.md`](references/contracts/05_composer_supply_chain.md) | Strict manifest validation (`composer validate --strict`), strict segregation (`require` vs `require-dev`), **no committed `composer.lock` for libraries**, `.gitattributes` export-ignore. |
| **06** | [`06_testing_compatibility.md`](references/contracts/06_testing_compatibility.md) | Test suite quality (PHPUnit 11+ / Pest 3+), Orchestral Testbench isolation, happy & error paths, custom exceptions, mock boundaries, multi-DB test execution. |
| **07** | [`07_consumer_release.md`](references/contracts/07_consumer_release.md) | Fresh sandbox consumer smoke test, clean installation without parent dependency leakage, auto-discovery verification, asset publishing, README & CHANGELOG compliance, **`AUDIT.json` single source of truth**. |

---

## 🚦 The 8 Automated Quality Gates

The mechanical CLI engine executes 8 automated gates:

```text
 1. git_cleanliness   ✔ Clean working tree & independent git repository
 2. composer_validate ✔ Valid composer.json schema (strict mode)
 3. pint              ✔ Laravel Pint PSR-12 / PER-CS 2.0 formatting
 4. phpstan           ✔ Strict static analysis (Level 8+)
 5. tests             ✔ Automated test suite pass rate (100%)
 6. isolated          ✔ Standalone sandbox install (verifies no parent vendor leaks)
 7. readme            ✔ Required structural sections and quickstart accuracy
 8. export_ignore     ✔ Clean distribution archive without test or dev file leakage
```

---

## 🔐 Cryptographic Certification

Traditional badges can be edited by anyone in Markdown. `laravel-package-audit` produces **mathematically verifiable receipts**:

```
1. tree_hash   = git rev-parse HEAD^{tree}   // Immutable hash of all package files
2. check_hash  = sha256(normalized_output)   // Hash of normalized tool output
3. fingerprint = sha256(tree_hash + canonical_json(check_hashes))
```

The resulting `AUDIT.json` is committed to the package. Anyone can verify it anytime:

```bash
php artisan package:audit packages/my-vendor/my-package --verify
```

Verdicts:
- **`VERIFIED`**: Exact match. The package is authentic and untampered.
- **`FORGED`**: The certificate was manually modified or check outputs differ.
- **`OUTDATED`**: Commits have modified source files since the audit was issued.
- **`MISSING`**: No certificate found.

---

## 🛠️ Skill Management CLI

Although the skill auto-installs on `composer require`, you can manage it explicitly:

```bash
# Explicitly install or update the skill in the project
php artisan package:audit:install

# Force overwrite with the latest version from the package
php artisan package:audit:install --force

# Create a symlink instead of copying (great for local package development)
php artisan package:audit:install --symlink

# Install into a custom directory
php artisan package:audit:install --path=.custom/skills
```

Standard Laravel publishing tags are also available:
```bash
php artisan vendor:publish --tag=package-audit-skill
php artisan vendor:publish --tag=package-audit-config
php artisan vendor:publish --tag=package-audit-stubs
```

---

## ⚙️ Configuration

Publish the config file to `config/package-audit.php`:

```bash
php artisan vendor:publish --tag=package-audit-config
```

Key options:
```php
return [
    // Toggle individual quality gates
    'checks' => [
        'composer_validate' => true,
        'pint' => true,
        'phpstan' => ['enabled' => true, 'level' => 8],
        'tests' => true,
        'isolated' => true,
        'readme' => ['enabled' => true],
        'export_ignore' => true,
        'git_cleanliness' => true,
    ],

    // Target skills directory (null for smart auto-detection: .agents/skills or .cursor/skills)
    'skills_path' => null,

    // Auto-materialize skill into host during local discovery
    'auto_publish_skill' => true,

    // Certificate settings
    'certificate_filename' => 'AUDIT.json',
    'git' => [
        'tag_prefix' => 'audit/v',
        'commit_message' => 'Audit certificate for v{version}',
    ],
];
```

---

## 🧪 Testing

Run the test suite via PHPUnit:

```bash
composer test
# or
vendor/bin/phpunit
```

Format code style using Laravel Pint:

```bash
composer format
# or
vendor/bin/pint
```

---

## 📄 License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
