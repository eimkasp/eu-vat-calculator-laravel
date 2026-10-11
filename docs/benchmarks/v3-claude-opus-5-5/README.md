# EU VAT Info v3 benchmark: Opus 5.5

Measurements for the Opus 5.5 run of the v3 build prompt, starting from `cf5c6f7`.

| File | What it is |
|---|---|
| `baseline.json` | The site at `cf5c6f7`, before any v3 change: 12 pages in English, German and Maltese |
| `after-v2-freeze.json` | The same measurement after the v2 archive and its footer link. Every count is unchanged; timings ran faster in a fresh container (LCP −176 to +4 ms, TBT −210 to +5 ms per page), which is machine variance, not an effect of the change |
| `after-v3.json` | The same 12 pages after the 5.1.0 calculator and validator work (`91c8374`) |
| `after-v3-1.json` | The same 12 pages after deferring below-the-fold rendering and collapsing HTML indentation (`971e872`) |
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

## After 5.1.0 (`after-v3.json`)

Same profile and pages. Lower is better except where marked.

| Measure | Baseline | After | Note |
|---|---|---|---|
| axe-core violations | 6 (3 serious, 3 moderate) | 0 | |
| Titles outside 30–60 characters | 13 of 33 | 0 | |
| Descriptions outside 70–160 characters | 14 of 33 | 0 | |
| Pages without JSON-LD | 9 of 33 | 0 | |
| Not in the sitemap | 1 | 0 | |
| Answer in the first 600 characters (higher is better) | 30 of 33 | 33 of 33 | |
| "As of" date shown (higher is better) | 0 of 33 | 24 of 33 | not on the MCP, changes and categories pages |
| Source named inside `<main>` (higher is better) | 21 of 33 | 30 of 33 | |
| Keyboard-only main task (higher is better) | 33 of 33 | 33 of 33 | |
| Median TBT | 262 ms | 153 ms | |
| Median LCP | 2,356 ms | 2,464 ms | **worse**: +104 to +172 ms on country calculators, +124 to +196 ms on the validator |
| Median HTML (gzip) | 21.2 KB | 23.9 KB | **worse**: +2.4 KB on country calculators, +2.7 KB on the validator (common amounts, FAQ, formats) |
| Max warm DB queries | 9 | 12 | **worse**: country calculators read the rate history and the latest change (3 indexed queries, 2 ms in total); top calculations 1 → 3 |
| JavaScript (gzip) | 91.6 KB | 91.6 KB | |
| Tests | 359, 2,808 assertions | 400, 3,413 assertions | Pint passes |

LCP equals FCP on every page, so it follows how much HTML the throttled phone has to parse; the regressions sit on the pages that gained content. Other pages moved within machine variance.

Closed after the measurement (no effect on the measured pages):

- Reduced, super-reduced and parking rates were the January 2024 snapshot. They now follow the Commission's Taxes in Europe Database (TEDB) for Estonia, Finland, Lithuania, Romania, Slovakia, Austria, Cyprus and Malta, dated by querying TEDB on past dates (`RateCorrections`, applied by a guarded data migration).
- Geolocation is cached per address for a day (an hour after a failure), so repeat views no longer call ip-api.

Still open:

- `/vat-rates/categories` returns 404 by design until verified category rules exist; nothing links to it, and the sitemap and `llms.txt` leave it out.
- TEDB files a few rates differently from the Commission's summary table: it lists no parking rate for Austria or Ireland and no super-reduced rate for Ireland, files Luxembourg's 14% under both reduced and parking, and adds a 4% super-reduced and a 13% parking rate for Greece. These were left as they are.
- The `public/v1` archive bar links to itself; `public/v1` is frozen.

## After the render optimisation (`after-v3-1.json`)

The extra LCP in `after-v3.json` came from markup: the local PHP server sends HTML uncompressed, so 24–36 KB more raw HTML took 100–190 ms more to arrive on Slow 4G. Production (Cloudflare, Brotli) sends about 2.5 KB more for the same sections. Two changes followed: `app-deferred` (`content-visibility: auto`) on the common amounts, questions, VAT number formats, the API tester and the footer, and `CollapseHtmlWhitespace`, which removes Blade's indentation (raw HTML −18 to −24%, Brotli −7 to −10%).

| Measure | Baseline | After 5.1.0 | After optimisation |
|---|---|---|---|
| Median LCP | 2,356 ms | 2,464 ms | 2,280 ms |
| Country calculators, LCP | 2,300–2,356 ms | 2,460–2,492 ms | 2,256–2,328 ms |
| Validator, LCP | 2,344–2,376 ms | 2,500–2,540 ms | 2,272–2,284 ms |
| Median TBT | 262 ms | 153 ms | 173 ms |
| Median HTML, gzip | 21.2 KB | 23.9 KB | 22.0 KB |
| axe violations, SEO and AI counts | as above | as above | unchanged from after 5.1.0 |

Two short pages got slower: the comparison (+28 to +116 ms against the baseline) and the shared calculation (+76 to +108 ms). An A/B run with only the whitespace middleware toggled reproduces it: the HTML arrives 100–170 ms sooner, so the browser finds `livewire.min.js` sooner, and under the emulation's equal bandwidth split that script slows the stylesheet, which the first paint waits for. Production sends both compressed over HTTP/2, which serves the stylesheet first.

## Found while freezing v2

- `/sitemaps/changes.xml` lists 26 year pages from `/vat-changes/year/1968` to `/vat-changes/year/1999` that return 404, because `VatChangesArchive` only accepts years from 2000 to 2100. The archive leaves them out (290 pages archived, 26 not found).
- The archive bar of `public/v1` links to the archived page itself instead of the live page; `public/v1` is frozen, so `public/v2` does it right instead of changing it.
