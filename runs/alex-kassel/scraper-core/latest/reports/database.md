# Audit Report: Database & Migrations (Agent 03)

## 1. Audit Status
- **Status:** `PASS`
- **Rationale:** The schema contains robust tables with full rollback capability (`down()`), idempotent creation guards (`!Schema::hasTable`), and foreign key constraints.

---

## 2. Schema & Migration Summary

| Table | Primary Key | Foreign Keys | Indexes / Uniques |
|---|---|---|---|
| `spiders` | `id` (bigint) | None | `domain`, `slug`, `class`, `unique(['slug'])` |
| `service_runs` | `id` (bigint) | None | `domain`, `trigger`, `status`, `started_at` |
| `spider_runs` | `id` (bigint) | `service_run_id`, `spider_id` | `domain`, `status`, `started_at` |
| `raw_contents` | `id` (bigint) | `spider_id`, `last_seen_spider_run_id` | `domain`, `external_id`, `missing_since_at`, `fingerprints`, `unique(['spider_id', 'external_id'])` |
| `raw_content_changes` | `id` (bigint) | `raw_content_id`, `spider_run_id` | Foreign keys |
| `raw_content_missing_periods` | `id` (bigint) | `raw_content_id` | Foreign keys |

---

## 3. Findings Breakdown

### [DB-001] Spiders table defines unique constraint on ['slug'] instead of composite ['domain', 'slug']
- **Severity:** `major`
- **Requires Human Decision:** `true`
- **Root Cause:** `RC-DB-SPIDER-SLUG-UNIQUE`
- **Context & Options:**
  - *Option A (Recommended):* Update unique constraint to `['domain', 'slug']` to enable reusing spider slugs across different domain contexts sharing a single database.
  - *Option B:* Keep `['slug']` unique globally.

### [DB-002] Missing explicit index on raw_content_missing_periods.raw_content_id
- **Severity:** `minor`
- **Root Cause:** `RC-DB-FOREIGN-KEY-INDEXES`
- **Location:** `database/migrations/2026_01_01_000000_create_scraper_tables.php:105-113`
- **Remediation:** Add composite index on `['raw_content_id', 'missing_since_at']`.
