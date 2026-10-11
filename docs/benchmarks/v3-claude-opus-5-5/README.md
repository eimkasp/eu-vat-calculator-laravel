# EU VAT Info v3 benchmark: Opus 5.5

Measurements for the Opus 5.5 run of the v3 build prompt, starting from `cf5c6f7`.

| File | What it is |
|---|---|
| `baseline.json` | The site at `cf5c6f7`, before any v3 change: 12 pages in English, German and Maltese |
| `after-v2-freeze.json` | The same measurement after the v2 archive and its footer link. Every count is unchanged; timings ran faster in a fresh container (LCP −176 to +4 ms, TBT −210 to +5 ms per page), which is machine variance, not an effect of the change |
| `tools/run.sh` | Rebuilds the site from the checked-out commit and writes `<label>.json` again |
| `tools/measure.mjs` | Performance, accessibility, SEO and AI readiness per page (Playwright and axe-core) |
| `tools/site.mjs` | Sitemap membership, internal links in, and the facts in llms.txt, the API and the MCP server |
| `tools/backend.php` | Database queries, server time and Livewire snapshot sizes per request |
| `tools/compile.mjs` | Merges the raw files into one JSON file with a summary |
| `tools/meta.json` | Environment, exact commands, method and caveats, copied into every result |

To measure again, from the repository root:

```bash
docs/benchmarks/v3-claude-opus-5-5/tools/run.sh baseline
```

The script needs PHP 8.3, Composer, Node 22, Yarn and Chromium for Playwright. It rebuilds the SQLite database, so run it in a scratch checkout.

## Baseline at a glance

Mobile emulation, 4× CPU slowdown, Slow 4G, median of 3 runs.

| Measure | Result |
|---|---|
| LCP | median 2,356 ms, worst 2,604 ms (`/de`) |
| TBT (INP proxy) | median 262 ms, worst 484 ms (`/mt`) |
| CLS | worst 0.112 (`/mt/vat-calculator`) |
| JavaScript | 91 KB gzip on every page, 94% of it the Livewire runtime (86 KB) |
| axe-core | 0 critical, 3 serious (`scrollable-region-focusable` on the comparison page), 3 moderate (`landmark-unique` on the home page) |
| Keyboard-only main task | 33 of 33 pages that load |
| Titles outside 30–60 characters | 13 of 33, up to 109 characters (`/mt`) |
| Descriptions outside 70–160 characters | 14 of 33 |
| Pages without JSON-LD | the VAT map, shared calculations, top calculations |
| Answer in the first 600 characters | 30 of 33 (missing on the MCP page) |
| "As of" date shown | 0 of 33 |
| Source named inside `<main>` | 21 of 33 |
| Not found | `/vat-rates/categories` in every language (also 404 on the live site: there are no verified category rules) |
| Not in the sitemap | the indexable top calculation `/vat-calculation/germany/1000/19/exclude` |
| Warm DB queries | median 3, max 9 (country calculators) |
| Tests | 359 passed, 2,808 assertions, 29.0 s; Pint passes |

## Found while freezing v2

- `/sitemaps/changes.xml` lists 26 year pages from `/vat-changes/year/1968` to `/vat-changes/year/1999` that return 404, because `VatChangesArchive` only accepts years from 2000 to 2100. The archive leaves them out (290 pages archived, 26 not found).
- The archive bar of `public/v1` links to the archived page itself instead of the live page; `public/v1` is frozen, so `public/v2` does it right instead of changing it.
