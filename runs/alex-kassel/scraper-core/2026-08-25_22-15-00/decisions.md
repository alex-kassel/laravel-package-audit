# ⚖️ Human Decision Sheet: `alex-kassel/scraper-core`

Please review and select options for the architectural and design tradeoffs identified during Phase 1 audit.

---

## 1. Spider Slug Uniqueness Boundary (`DB-001` / `RC-DB-SPIDER-SLUG-UNIQUE`)

### Context & Impact
In `database/migrations/2026_01_01_000000_create_scraper_tables.php`, the `spiders` table defines a unique constraint on `$table->unique(['slug'])`.
- In standard usage, each business domain maintains its own isolated database connection (e.g. `sqlite_{domainSlug}_raw`).
- However, if multiple domains share a single database, two spiders with the same name (such as `catalog-spider`) in different domains will collide due to the single-column uniqueness constraint.

### Selectable Options

- [x] **Option A (Recommended):** Update migration unique constraint to `['domain', 'slug']` (`$table->unique(['domain', 'slug'])`).
  - *Tradeoff:* Enables multi-domain spider slug reuse in shared databases without affecting single-database isolation.
- [ ] **Option B:** Maintain global `unique(['slug'])`.
  - *Tradeoff:* Requires unique spider slugs across all domains globally.

**Selected Choice:** `Option A (Recommended)`
