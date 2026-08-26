# Package Audit Contracts Reference

The `.audit` framework defines 7 specialized audit agents located in `.audit/agents/`:

| Agent Contract | Focus Area | Mandatory Verification Command / Tool |
|---|---|---|
| **`01_architecture_api`** | Public API Surface, BC compatibility, ServiceProvider registration, archetype rules | Manual AST review + static reflection |
| **`02_code_quality`** | PSR-12/Laravel formatting, PHPStan Level 8+ strictness, exception taxonomy | `composer pkg:check <pkg> --json` |
| **`03_database`** | Migrations isolation, SQLite/MySQL compatibility, prefix safety, composite indexes | Migration dry-run & context inspection |
| **`04_security_isolation`** | Secrets leakage, container singleton pollution, ambient state safety | Security scan & binding isolation check |
| **`05_composer_supply_chain`** | `composer validate --strict`, `.gitattributes export-ignore`, dependency boundaries | `composer validate --strict` + archive inspection |
| **`06_testing_compatibility`** | Unit/Feature test suite, Orchestra Testbench integration, edge-case coverage | `php artisan test -c ...` |
| **`07_consumer_release`** | README standard, GitHub topics (exact 3), CHANGELOG, LICENSE, Release Gate signature | `composer pkg:readme <pkg> --json` |

## RELEASE-GATE Certificate Immutability Rule
- `RELEASE-GATE.md` is a frozen digital audit certificate tied to a specific commit hash.
- Routine patch updates do not require re-auditing.
- Never edit `RELEASE-GATE.md` manually to bump version numbers.
