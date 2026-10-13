# EU VAT Info 5.3.0 improvement cycle

One measure → fix → measure cycle on UX/UI, accessibility, performance, SEO and AI discoverability, starting from 5.2.0 (`6733779`).

| File | What it is |
|---|---|
| `before.json`, `after.json` | The v3 benchmark (`../v3-claude-opus-5-5/tools/run.sh`) on 12 pages in English, German and Maltese: 5.2.0, then the finished 5.3.0 branch |
| `audit-before.json`, `audit-after.json` | The wide audit (`tools/audit.mjs`): every page type in the sitemap, light and dark, 390 and 1440px |
| `ab-timing.txt` | Interleaved timing of 5.2.0 and 5.3.0 served side by side on the same machine |

## How the cycle ran

1. **Measure.** The benchmark and the wide audit locally, plus production headers and markup (`curl` on vat.businesspress.io), and two read-only code reviews (accessibility and UX; SEO and AI).
2. **Fix** what they found (below), with tests for each behaviour.
3. **Measure again.** The second wide audit found a regression from the first fixes (scroll regions repeating their section's landmark name). The second benchmark found two more: a layout shift on the Maltese popular-calculations page (the new fallback font was too narrow for medium-weight text) and 15 extra requests per page (the phone language list loaded 24 flags at start). All three were fixed and measured a third time.

## Results

Benchmark: mobile emulation, 4× CPU slowdown, Slow 4G, median of 3 runs, local PHP server (uncompressed HTML).

| Measure | 5.2.0 | 5.3.0 |
|---|---|---|
| Worst layout shift (CLS) | 0.110 (`/mt/vat-calculator`) | 0.038 (`/de`) |
| Summed CLS, 36 pages | 0.354 | 0.178 |
| Pages with CLS above 0.05 | 4 | 1 (`/de/vat-rates/categories`, a 404 page: 0.077 → 0.092) |
| Median LCP | 2,320 ms | 2,340 ms |
| Mean LCP | 2,325 ms | 2,330 ms |
| Median TBT | 179 ms | 224 ms (see below) |
| Median HTML (gzip) | 22.0 KB | 22.6 KB |
| Map page HTML (gzip) | 37.5 KB | 32.2 KB |
| Generic calculator HTML (gzip) | 41.6 KB | 36.4 KB |
| JavaScript / CSS (gzip) | 91.6 / 16.1 KB | 91.8 / 16.4 KB |
| Requests (median) | 16 | 16 |
| axe violations (benchmark pages) | 0 | 0 |
| Titles / descriptions out of range, invalid JSON-LD | 0 | 0 |
| Tests | 430, 3,772 assertions | 470, 3,977 assertions; Pint passes |

Wide audit: every page type, four views each.

| Measure | 5.2.0 | 5.3.0 |
|---|---|---|
| Page views with axe violations | 11 (10 serious `scrollable-region-focusable`, 1 serious `color-contrast`) | 0 |
| Page views that scroll sideways | 2 (Maltese header at 1440px) | 0 |
| Desktop header overflow, 24 languages × 6 widths | 63 of 144 | 0 |
| Pages with a title or description out of range | 4 | 0 |
| Internal links that redirect | 24 (`/lang/*`) | 0 |
| Internal links checked | 645 | 1,380 |

### Read with care

- **TBT.** The benchmark's median rose from 179 to 224 ms (mean 233 → 264 ms), but TBT under 4× slowdown swings by ±100 ms between runs of the same page. Served side by side and measured alternately (`ab-timing.txt`), 5.3.0 matched 5.2.0 on the map (131 vs 129 ms), the country calculator (381 vs 371 ms) and the comparison (93 vs 106 ms), and ran higher on the home page (436 vs 502 ms) and the MCP page (183 vs 213 ms). A small increase on the home page is possible and is listed in `BACKLOG.md`.
- **Keyboard task on the MCP page: 33/33 → 30/33.** The copy button still works from the keyboard and "Server URL copied" is announced. The benchmark reads only the first status element on the page, which is now the API tester's status line, empty until a request is sent.
- **Production differs.** Rocket Loader and Livewire's `no-store` only show behind Cloudflare, and production sends HTML compressed (the extra 3–4 KB of raw HTML is about 0.6 KB there). The bfcache result was checked in Chromium: production refuses with `CacheControlNoStoreHTTPOnlyCookieModified`, while the branch restores pages from the cache.

## What changed

**Performance**
- Every first-party script carries `data-cfasync="false"`, so Cloudflare Rocket Loader no longer defers the theme, Livewire and the calculator (production rewrote all of them).
- `AllowBackForwardCache` sends `no-cache, private` instead of Livewire's `no-store` to guests, so back and forward navigation is instant.
- Fallback fonts sized to Inter per weight (Arial, Arial Bold, Roboto), so the font swap no longer rewraps text.
- The Europe map's paths are simplified (`scripts/simplify-map.mjs`): 60 KB → 35 KB, no visible difference at 2× zoom.

**UX and accessibility**
- The desktop header fits every language: the full navigation from 1280px, and a container query that hides the search field before anything overflows.
- Language links open the current page in that language; the phone menu lists them instead of a select that navigated on change.
- Focus returns after the country picker, the search dialog (which now keeps focus inside), menus and the map; the add or remove VAT switch uses arrow keys; wide tables scroll from the keyboard; validator errors are tied to their fields; results and API statuses are announced briefly; links that open a new tab say so in 24 languages; larger clear buttons; reduced motion respected.

**SEO and AI discoverability**
- `og:locale` as `de_DE` with alternates, `twitter:*` as `name`, the share image's size and alt, and a new share image in the current design.
- hreflang only on indexable pages that exist in every language; AMP paired with English pages only; no canonical on the 404 page.
- One BreadcrumbList per page, built from the visible trail (the old microdata pointed at `http://` URLs); one Dataset node on the home and dataset pages; one WebSite id.
- Rate change pages have a dated title and a full description; the sitemap lists the footer pages, leaves out the empty categories section and dates template pages by their last release; `llms.txt` lists the guides and comparisons, `llms-full.txt` the last 24 months of changes; every page links its Markdown version.

## Not done in this cycle

See the top of `BACKLOG.md`. The largest item is country names in every language: titles still read "MwSt-Rechner Germany".
