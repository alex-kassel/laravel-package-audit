# Changelog

All notable changes to the **Laravel Package Audit Framework** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-08-26

### Added
- Structured 3-section Phase 1 reporting format (Test & Tooling Baseline, Planned Mechanical Fixes, Human Decision Gate).
- Mandatory agent technical recommendation (`(Recommended)`) and architectural rationale for all non-obvious decision points.
- Explicit **🛑 HARD STOP** mechanism preventing automated execution of Phase 2 before human confirmation.
- Generic detection patterns for packages managing dynamic / programmatic storage contexts and migrations.

### Changed
- Refactored internal file paths to canonical relative paths across all 7 agent contracts and orchestrator guides.
- Streamlined documentation and repository layout with focus on objective verification and engineering humility.

### Removed
- Removed internal example artifacts and references to private packages.

## [1.0.13] - 2026-08-26

### Added
- Standardized universal Agentic Skill entrypoint (`SKILL.md`) for Antigravity, Cursor, and modern AI coding assistants.
- Standardized directory layout: `references/` for contracts and orchestrator, `resources/` for schemas and templates.
- Full compatibility matrix for PHP 8.2–8.4 and Laravel 10–13.
- Streamlined 2-phase lifecycle with human decision gates and immutable `RELEASE-GATE.md` snapshot certification.
