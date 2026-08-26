# Audit Report: Architecture & API (Agent 01)

## 1. Audit Status
- **Status:** `PASS`
- **Rationale:** The architecture of `alex-kassel/scraper-core` provides high-performance, domain-isolated web scraping capability. The package cleanly separates the scraping engine, data synchronizer, cache warmup, spider lifecycle, and domain storage contexts.

---

## 2. Public API Surface Summary

| Component / Symbol | Type | Responsibility |
|---|:---:|---|
| `AlexKassel\ScraperCore\Services\ScraperEngineService` | Service | Resolves and triggers spiders within isolated ambient domain contexts (`DomainContext::using`) with runtime override support. |
| `AlexKassel\ScraperCore\Services\DataSyncService` | Service | Persists raw scraped contents, computes stable fingerprints, tracks changes, detects missing records, and dispatches domain events. |
| `AlexKassel\ScraperCore\Services\RawContentCache` | Service | In-memory preloaded cache of spider items, optimizing away redundant network requests using TTL and discovery hashes. |
| `AlexKassel\ScraperCore\Spiders\AbstractSpider` | Abstract Class | Base spider class extending RoachPHP with built-in cache access, fingerprinting helpers, and item factory helpers. |
| `AlexKassel\ScraperCore\Providers\AbstractScrapingServiceProvider` | ServiceProvider | Base provider auto-registering domain scraping storage context and auto-discovering spider classes. |
| `AlexKassel\ScraperCore\Providers\ScraperCoreServiceProvider` | ServiceProvider | Core package service provider registering engine singletons and CLI commands. |
| `AlexKassel\ScraperCore\DTOs\RawContentItem` | DTO / Item | Strongly-typed RoachPHP item DTO implementing `ItemInterface` and `ArrayAccess`. |
| `AlexKassel\ScraperCore\DTOs\RawContentEventDTO` | DTO | Readonly data transfer object for domain events. |
| `AlexKassel\ScraperCore\Middleware\RequestMiddleware` | Middleware | RoachPHP request middleware with default browser headers and fail-fast timeouts. |
| `AlexKassel\ScraperCore\Events\*` | Events | Comprehensive lifecycle and data change events (`RawContentCreated`, `RawContentUpdated`, `RawContentDisappeared`, `RawContentRestored`, `ScraperAnomalyDetected`, `ScraperRunStarting`, `ScraperRunFinished`, `ScraperRunFailed`). |

---

## 3. Findings Breakdown

### [ARCH-001] ScraperCoreServiceProvider does not provide standard publishable config or migration tags
- **Severity:** `minor`
- **Root Cause:** `RC-CONFIG-PUBLISH-INTEGRATION`
- **Location:** `src/Providers/ScraperCoreServiceProvider.php:15-34`
- **Evidence:** `ScraperCoreServiceProvider::boot()` registers console commands but does not provide `publishes()` tags or `mergeConfigFrom()`.
- **Recommendation:** Add standard publish configuration and migrations tag for hybrid/standalone use.

### [ARCH-002] Internal pipeline processors lack @internal annotations
- **Severity:** `info`
- **Root Cause:** `RC-INTERNAL-PIPELINE-PROCESSORS`
- **Location:** `src/Processors/SaveRawContentProcessor.php:31-35`
- **Evidence:** `SaveRawContentProcessor` and `ConsoleProgressProcessor` lack `@internal` annotations in PHPDoc.
- **Recommendation:** Add `@internal` annotations.
