# EU VAT Info — Backlog

Items identified during the audit/fix/growth/polish cycles. Grouped by priority. The newest cycle comes first.

---

## Next cycle (found in the 5.4.0 reviews)

5.4.0 came from four read-only reviews (visual, UX and content architecture, front-end performance, SEO). What they found and 5.4.0 did not ship:

### High
- [ ] **The old domain is gone.** `eu-vat.info` no longer resolves (NXDOMAIN), so the 301s that `RedirectLegacySeoHost` answers never run and every backlink to it is dead. Renew the domain, point it at Forge and keep the redirect; then use the change-of-address tool in Search Console.
- [ ] **Inline the CSS on a first visit.** Inlining the 16 KB (brotli) stylesheet moved first paint from about 1.24 s to 0.21 s after the HTML on production in the review's runs. Write it inline when a visitor has no CSS-version cookie, prefetch the file after load and use the normal link afterwards. Then keep the hero texture from being the phone LCP element (leave it out below 640px or make it small enough to be ignored).

### Medium
- [ ] **Turn on Early Hints in Cloudflare** (Speed → Optimization). 5.4.0 sends the `Link` preload headers it needs.
- [ ] **Start hidden widgets later.** `x-ignore` the search dialog, the phone menu, the language menu and the footer theme menu, and start them on idle or first use; make `RecentCountries` and `VatRateChanges` plain Blade components (home goes from 4 Livewire snapshots to 2).
- [ ] **A /vat-rates hub and rate categories.** One indexable page per rate type, with the TEDB categories (food, books, medicines, hotels…) per country, and the distance-selling threshold. The largest content gap the SEO review found.
- [ ] **Comparison pages that compare.** Each approved comparison is a table; add two or three sentences worked out from the data (rate gap, VAT on €100, last change) so the pages are not near-duplicates.
- [ ] **Validator pages per country** say little that differs; add the country's format notes, where to find a VAT number on an invoice and the local name of the number.
- [ ] **Map on phones.** The visual review found the map hard to use on a phone: small countries are hard to tap and the selected country's details sit away from the map. Show the selection next to the tap and lead with the ranked list on narrow screens.

### Low
- [ ] Footer archive links read "Previous version" and "Older version"; name the versions ("5.0 site", "4.x site").
- [ ] "Full calculator" under the home calculator could name the country ("Germany calculator").
- [ ] The MCP badge on the tools pages is louder than the tool cards around it.
- [ ] Inter without the optical-size axis is 48 KB instead of 73 KB (latin); a design trade-off to decide.

---

## Next cycle (found in the 5.3.0 cycle)

Measurements and the full list of what 5.3.0 fixed are in `docs/benchmarks/cycle-5.3/README.md`.

### High
- [ ] **Country names in every language.** Titles, headings and questions say "MwSt-Rechner Germany" and "Calculateur de TVA pour Germany": `Country::name` is English and goes into every `:country` placeholder (55 keys × 23 languages). Add the CLDR name per locale (`lang/{locale}/countries.php`, generated once from PHP intl) and rewrite each template so the name fits the sentence's case (nominative forms such as "Niemcy: kalkulator VAT" where a language inflects). Native-language queries ("Mehrwertsteuer Deutschland") are the largest search gain left.
- [ ] **Turn off Rocket Loader in Cloudflare** (Speed → Optimization → Content Optimization). 5.3.0 opts every script out, but the loader still injects its own script on every page.

### Medium
- [ ] **Hashed build assets cache for one year.** `/build/assets/*` is served with `max-age=2678400` (31 days) and no `immutable`; set `Cache-Control: public, max-age=31536000, immutable` for `/build/` in the Forge nginx config.
- [ ] **Duplicate security headers.** HTML responses carry `X-Frame-Options` and `X-Content-Type-Options` twice (nginx and `SecurityHeaders`); keep one source.
- [ ] **One brand name in titles.** Titles end in "| EU VAT Info", "| EU MwSt. Info", "| IVA na UE" and so on, while the WebSite entity and `og:site_name` say "EU VAT Info". Decide whether `ui.site_name` stays translated.
- [ ] **Translate the embed widget builder** (`widget/embed.blade.php`, linked from the Tools menu in every language), or give it a `locale.` route and list it as English-only.
- [ ] **A social image per country calculator** ("Germany VAT 19%"), rendered from the same template as `public/images/og-default.png`, regenerated when a rate changes.
- [ ] **Rate history for France and Greece.** The synced dataset has none, so their history pages do not exist.
- [ ] **Check the Roboto fallback on an Android phone.** Its 105% `size-adjust` was derived from Roboto's measured width against Arial, not measured on a device.

- [x] **Recheck TBT on the home page.** Profiled in 5.4.0: the country list built with `x-for`, the per-row filters and a new number formatter per call. Fixed; home 497 → about 360 ms and the country calculator 448 → 310 ms (4× CPU, median of 7, side by side).

### Low
- [ ] Inline source links (European Commission, pdfcheck.online, "Try LT100…") are under 24px tall; they pass as inline text, but a larger hit area would help on phones.
- [ ] The generic calculator and the map page still inline the 35 KB map; render it after the first screen on `/vat-calculator`.
- [ ] `generate-og.php` at the repository root draws the pre-5.0 share image and is unused; remove it.

---

## Medium Priority

### SEO
- [x] Create a proper OG image (`public/images/og-image.png`) — currently falls back to favicon
- [ ] Add internal cross-links between country pages (e.g., "See also: neighboring countries")
- [x] Add `BreadcrumbList` schema.org markup on country pages
- [x] Add `lastReviewed` / `dateModified` meta tags on country pages

### i18n
- [x] Translate new `ui.php` keys to all 23 non-English locales (tools, navigator, country_page, calculator, validator sections)
- [x] Rate labels in `vat-calculator-simple.blade.php` still hardcoded (Standard, Reduced, Super Reduced, Parking) — extract to translation keys
- [x] VAT Navigator result text (`$result['action']`, `$result['explanation']`) is generated server-side in English — needs i18n support in the Livewire component
- [ ] Country names are stored in English in the database — consider per-locale country name translations (now planned under Next cycle)

### Accessibility
- [x] Dark mode coverage ~70% — several components have incomplete dark mode styling
- [x] Add `aria-current="page"` to the active navigation link
- [x] Add visible focus ring to all interactive elements (the `*:focus-visible` rule with the gold ring on navy)
- [x] Improve color contrast on some light-gray text elements (e.g., `text-gray-400`)

### Performance
- [x] Consider switching to `wire:navigate` on country links in the home page table for faster transitions
- [x] Add `loading="lazy"` to flag images in vat-changes-history and vat-map pages
- [x] Consider `@once` directive for repeated inline SVG icons

---

## Low Priority

### Testing
- [x] **Critical:** Enable `RefreshDatabase` + `DatabaseSeeder` in test setup so Feature tests can run with seeded data
- [x] Fix `RootTagMissingFromViewException` in Livewire tests — components using `@section` before root `<div>` need restructuring
- [ ] Add unit tests for `ViesValidationService`, `CountryAnalyticsService`, and `SitemapGenerator`
- [x] Add Feature tests for API endpoints (`/api/countries`, `/api/vat/validate`)
- [ ] Add Livewire component tests for `Home`, `VatCalculator`, `VatValidator`
- [x] Convert PHPUnit-style `ExampleTest` to Pest syntax

### Code Quality
- [x] Remove dead routes: `/counter`, commented-out `/vat-check` and `/vat-navigator` in `routes/web.php`
- [x] `tools.blade.php` calculator link is hardcoded as `/vat-calculator` instead of using `locale_path()`
- [ ] Consider extracting FAQ generation logic from `country.blade.php` into a dedicated service or view model

### UX / Design
- [ ] Add `wire:transition` to more Livewire dynamic sections (currently only 2 uses)
- [x] Consider adding a global search/autocomplete for country navigation (the search dialog, ⌘K)
- [x] Mobile bottom navigation could highlight the current active page
- [ ] Add skeleton loading states for the VAT map SVG and country page sidebar

---

## Completed This Cycle

- [x] Add hreflang `<link>` tags to HTML head (was only in XML sitemap)
- [x] Add missing `@section('seo')` to vat-map, tools, vat-navigator pages
- [x] Fix OG image 404 (temporary: changed to favicon)
- [x] Add `Sitemap:` directive to `robots.txt`
- [x] Add skip-to-content link and `<main>` landmark
- [x] Add `aria-live` regions to dynamic content (validator, calculator)
- [x] Add `focus-visible` outline globally
- [x] Cache country queries in Home and VatChangesHistory components
- [x] Fix N+1 query in VatRateChanges component
- [x] Add `loading="lazy"` to flag images on home page
- [x] Remove unused imports in `routes/web.php` and `VatRateChanges.php`
- [x] Cache `llms-full.txt` route response
- [x] Auto-fix 100 Pint code style violations
- [x] Add Dataset + Organization JSON-LD schemas to home page
- [x] Add `@stack('head')` to layout (enables FAQPage schema on country pages)
- [x] Add `wire:navigate` to all navigation links (desktop + mobile)
- [x] Add loading state to VAT validator form
- [x] Add empty state to home page country search
- [x] Extract ~80 hardcoded English strings to translation keys
- [x] Enable SQLite in-memory for test environment (`phpunit.xml`)
- [x] Enable `RefreshDatabase` trait in Pest config
- [x] Fix SQLite-incompatible migration (SUBSTRING_INDEX → database-agnostic)
