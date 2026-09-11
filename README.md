<h1 align="center">🛡️ Laravel Package Audit & Certification</h1>

<p align="center">
  <strong>Self-contained pre-release audit and tamper-proof certification toolkit for Laravel and PHP packages</strong>
</p>

<p align="center">
  <a href="#-key-features">Key Features</a> •
  <a href="#-requirements">Requirements</a> •
  <a href="#-installation">Installation</a> •
  <a href="#-usage">Usage</a> •
  <a href="#-how-cryptographic-fingerprinting-works">Fingerprint Engine</a> •
  <a href="#-verifying-a-certified-package">Verification Guide</a> •
  <a href="#-testing">Testing</a> •
  <a href="#-license">License</a>
</p>

<p align="center">
  <a href="https://github.com/alex-kassel/laravel-package-audit"><img src="https://img.shields.io/badge/Release-v1.0.0-10b981?logo=shield" alt="Framework Version"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-ff2d20?logo=laravel&logoColor=white" alt="Laravel Support"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.2+-777bb4?logo=php&logoColor=white" alt="PHP Support"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License"></a>
</p>

---

## 🌟 Overview

**`alex-kassel/laravel-package-audit`** is a standalone Laravel package that automates comprehensive pre-release quality audits and issues tamper-proof cryptographic certificates (`AUDIT.json`) for any PHP or Laravel package.

It combines an automated CLI execution suite with reproducible verification and retains full compatibility with the universal AI Agentic Skill specification ([`SKILL.md`](SKILL.md)).

---

## 🚀 Key Features

- **Self-Contained & Zero External Package Bloat**: Works in any Laravel 11, 12, or 13 application without requiring workspace dev-toolkits or bundling heavyweight dev dependencies (`pint`, `phpstan`, `phpunit` are discovered dynamically on the host).
- **8 Automated Quality Gates**:
  1. `git_cleanliness`: Working tree clean status and independent repository check.
  2. `composer_validate`: Strict manifest validation (`composer validate --strict`).
  3. `pint`: Host code style verification (`pint --test`).
  4. `phpstan`: Strict static analysis (Level 8+).
  5. `tests`: Automated PHPUnit or Pest test execution.
  6. `isolated`: Standalone sandbox installation (`composer install` in clean temp directory) to detect hidden host leakage or path dependencies.
  7. `readme`: Standard structure and section compliance validation.
  8. `export_ignore`: Distribution archive cleanliness (`.gitattributes`).
- **Tamper-Proof Certification**: Generates `AUDIT.json` anchored to Git's immutable `tree_hash` and SHA-256 canonical check execution fingerprints.
- **Instant Reproducible Verification**: Verify if a package or release has drifted, forged results, or remains 100% compliant with a single command.

---

## 📋 Requirements

- **PHP**: 8.2 or higher
- **Laravel Framework**: 11.0, 12.0, or 13.0
- **Git**: Installed and available in PATH
- **Composer**: 2.2 or higher

---

## 📦 Installation

Require the package as a development dependency in your Laravel project:

```bash
composer require --dev alex-kassel/laravel-package-audit
```

Optionally publish the configuration file and CI workflow:

```bash
php artisan vendor:publish --tag=package-audit-config
php artisan vendor:publish --tag=package-audit-stubs
```

---

## 💻 Usage

### 1. Audit and Certify a Package

To run a complete audit against a local package directory:

```bash
php artisan package:audit packages/vendor/package-name
```

If all enabled checks pass:
1. An immutable cryptographic `AUDIT.json` certificate is generated in the package root.
2. A Git tag `audit/v{version}` is assigned to the certified commit.
3. The certificate is committed to Git.

### 2. Verify an Existing Audit Certificate

Verify whether a package's `AUDIT.json` is authentic and untampered:

```bash
php artisan package:audit packages/vendor/package-name --verify
```

Verdicts:
- `VERIFIED`: The Git source tree and recomputed check fingerprints match the certificate identically.
- `FORGED`: The certificate was tampered with, the Git tree hash does not match, or re-running checks produces different outputs.
- `OUTDATED`: Commits modifying source files have been added since the audit was certified.
- `MISSING`: No `AUDIT.json` file was found in the package root.

### 3. Machine-Readable JSON Output (for CI/CD)

```bash
php artisan package:audit packages/vendor/package-name --json
php artisan package:audit packages/vendor/package-name --verify --json
```

---

## 🔐 How Cryptographic Fingerprinting Works

Traditional badge-based or markdown-based audit reports are vulnerable to manual editing or AI agent hallucinations. `laravel-package-audit` implements **Reproducible Verification**:

```
1. tree_hash   = git rev-parse HEAD^{tree}   // Cryptographic hash of ALL source files
2. check_hash  = sha256(check_output)        // Output hash for each automated check
3. fingerprint = sha256(tree_hash + canonical_json(sorted_check_hashes))
```

Because `tree_hash` changes if even a single character in the source code is modified, and the fingerprint commits to both the source code and the deterministic check outputs, any alteration after the audit is immediately detected as `FORGED` or `OUTDATED`.

---

## 🔍 Verifying a Certified Package

Consumers and CI pipelines can independently verify any package that ships with an `AUDIT.json` certificate:

```bash
# Clone the package
git clone https://github.com/vendor/my-package.git
cd my-package

# Run audit verification in any Laravel host
php artisan package:audit . --verify
```

---

## ⚙️ Configuration

`config/package-audit.php`:

```php
return [
    'checks' => [
        'composer_validate' => true,
        'pint' => true,
        'phpstan' => [
            'enabled' => true,
            'level' => 8,
        ],
        'tests' => true,
        'isolated' => true,
        'readme' => [
            'enabled' => true,
            'required_sections' => [
                'Requirements',
                'Installation',
                'Usage',
                'Testing',
                'License',
            ],
        ],
        'export_ignore' => true,
        'git_cleanliness' => true,
    ],
    'certificate_filename' => 'AUDIT.json',
];
```

---

## 🤖 AI Agent & Skill Integration

This package preserves the universal Agentic Skill specification ([`SKILL.md`](SKILL.md)) and the 7 specialized domain review contracts located in [`references/agents/`](references/agents/).

When using autonomous coding agents (Google Antigravity, Cursor, Claude Code), trigger human-in-the-loop audits:
> *"Run a full package audit on `packages/vendor/package-name` using the package-audit skill."*

---

## 🧪 Testing

Run the test suite using PHPUnit:

```bash
vendor/bin/phpunit -c packages/alex-kassel/laravel-package-audit/phpunit.xml.dist
```

---

## 👤 Author & Maintainer

- **Alexander Macenko** ([@alex-kassel](https://github.com/alex-kassel)) — Author & Framework Steward

---

## ⚖️ License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
