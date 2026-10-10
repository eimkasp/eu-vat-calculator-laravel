# EU VAT Info v3 — frontier model build prompt

Third iteration of EU VAT Info. Every frontier model gets the same prompt, the same base commit and the same scorecard, so the results can be compared fairly and shown publicly.

| Iteration | What it is | Where it lives |
|---|---|---|
| v1 | The site before the 5.0 redesign | `public/v1/`, frozen |
| v2 | The current site (5.0.x, base commit `cf5c6f7`) | `main`, to be frozen at `public/v2/` |
| v3 | What each model builds from this prompt | `v3/<model-slug>` branches |

## How to run it

1. Start every run from `cf5c6f7`, on a branch named `v3/<model-slug>` (for example `v3/claude-opus-5-5`).
2. Paste everything under **The prompt** into a fresh session. Replace `<MODEL_NAME>` and `<MODEL_SLUG>`.
3. Give every model the same tools, network policy and time budget, and write them down in the scorecard.
4. If you have a newer Search Console export, put it in `gsc-data/` before you start. Every model must get the same files.

---

## The prompt

You are `<MODEL_NAME>`. You are building **v3 of EU VAT Info** (`vat.businesspress.io`), a Laravel 13, Livewire 4 and Tailwind 4 site with EU VAT rates, calculators, VIES validation, rate history, a REST API and an MCP server in all 24 official EU languages.

This is a public benchmark. Several frontier models get this same prompt and base commit. Your branch will be judged on measured results, on how well your code meets this repository's own standards, and on how honest your report is. Act as one senior team (architect, SEO and AI-search lead, UX and accessibility lead, QA lead) with a single voice. Deliver production-ready work and keep it simple. One finished, measured improvement is worth more than five half-built features.

### 0. Ground rules (do not break these)

- Work on branch `v3/<MODEL_SLUG>`, starting from commit `cf5c6f7`. Commit in small, logical steps with clear messages, and push. Do not open a pull request.
- Read these first and follow them: `AGENTS.md`, `DESIGN.md`, `PRODUCT.md`, `CHANGELOG.md` (keep its tone), `app/Support/Seo/SeoPolicy.php`, `app/Support/Seo/CalculationIndexing.php`, `app/Support/Mcp/VatMcpServer.php`, `app/Support/Vat/`, `resources/js/vat.js`, `routes/web.php`, and the tests in `tests/Feature/`.
- Load `.claude/skills/design-system` before you touch any UI. Use only the semantic tokens and the `app-*` classes. Do not add `dark:` variants, raw palette colours, glass effects, gradients, pill shapes or default `shadow-*` utilities. Pages must keep the solid EU navy and gold look.
- Never edit `public/v1/`.
- Every URL that is indexed today must keep working, either by returning 200 or by redirecting with a 301 to its new address. Do not change the canonical host, the locale URL scheme or the shared-calculation URL format.
- `App\Support\Vat` and `resources/js/vat.js` must stay in sync. A rounding difference between them is a release blocker.
- Any new UI text must exist in all 24 `lang/{locale}/ui.php` files and be properly translated. Placeholder English is not allowed. You write the `ga`, `hr` and `mt` translations yourself, because DeepL does not cover them.
- Do not game the benchmark. That means no deleting, skipping or loosening tests, no `noindex` to hide weak pages, no blocking crawlers, no hiding content from users while showing it to bots, and no fabricated metrics. If a measurement fails, report the failure.
- `php artisan test` and `./vendor/bin/pint --test` must pass on every commit you push.

### 1. Measure the baseline first

Before you change anything, write `docs/benchmarks/v3-<MODEL_SLUG>/baseline.json` and include the exact commands so a human can rerun them. Measure on these representative pages, in English plus `/de/` and `/mt/`:

`/`, `/vat-calculator`, `/vat-calculator/germany`, `/vat-calculator/malta`, `/vat-number-validator`, `/vat-map`, `/vat-changes`, `/compare/germany-vs-france-vat`, `/vat-calculation/germany/1000/19/exclude`, `/top-vat-calculations/1000`, `/vat-rates/categories`, `/mcp-server`

Record these values for each page:
- **Performance:** HTML bytes, JS and CSS bytes (compressed), request count, LCP, CLS and INP-proxy (TBT). Measure with headless Chromium through Playwright, which is pre-installed at `/opt/pw-browsers`. Never run `playwright install`. Use mobile emulation with 4× CPU throttling and Slow 4G.
- **Accessibility:** the axe-core violation count by impact. Load axe from npm, or from a pinned CDN version if npm is blocked. Also check keyboard-only completion of the page's main task.
- **SEO:** the title and its length, the meta description and its length, canonical, robots, hreflang count, JSON-LD types, whether the JSON-LD is valid (parse it and check required properties), the H1, word count, internal links in and out, and whether the page is in `sitemap.xml`.
- **AI readiness:** whether the main answer appears in the first 600 characters of visible text, whether there is a machine-readable alternate (Markdown or JSON), whether sources and an "as of" date are shown, and whether the facts are reachable through `llms.txt`, the API and the MCP server.
- **Backend:** DB queries per request and the Livewire snapshot size per component.
- **Repository:** test count, assertion count, test duration and Pint status.

If a tool can't be installed in your environment, record `"unavailable"` with the reason and use the next-best measurement. Do not skip the metric silently.

### 2. Freeze v2

Archive the current site at `public/v2/`, following exactly the pattern `public/v1/` set:
- Each page is a static English snapshot, `noindex, nofollow`, with an archive bar and a "· Archive v2" title suffix.
- Calculations keep working in the browser through a small `public/v2/archive.js`.
- Server actions show a read-only notice instead.
- Links to pages that were not archived point to the live site.
- Write `tests/Feature/SiteArchiveV2Test.php`, modelled on `SiteArchiveTest.php`, so the archive stays complete, unindexed, self-contained and free of server calls.
- Link it from the footer and the changelog next to v1, and add a short `AGENTS.md` section for it.

Generate the snapshot with a script you commit (`scripts/`). Do not hand-edit the pages. Keep the archive's total size reasonable and report it.

### 3. Build v3

Use the baseline and the Search Console export in `gsc-data/` to decide where to spend your effort. That export covers 12–26 April 2026, before the 5.0 redesign, and shows:
- 68 clicks from 11,454 impressions, with an average position of about 30.
- `/vat-calculator/malta` ranks around position 5 for "vat calculator malta" but gets almost no clicks. The titles and snippets aren't earning the click.
- Short country queries such as "vat hungary", "vat poland" and "vat romania" rank at position 40–80, so there's no page that clearly answers "what is the VAT in X".
- 2,065 impressions from the United States brought 0 clicks.
- Validator pages for non-EU countries (Switzerland, Norway, Iceland, Turkey) and some translated validator pages get clicks above their weight.
- Desktop accounts for 86% of impressions.

Write a plan before you build: `docs/benchmarks/v3-<MODEL_SLUG>/plan.md`. For each candidate, estimate impact, confidence and effort, then commit to the highest-value set you can finish to production quality. The workstreams below are the scope. Within them, you decide what to build, but every item you ship needs a measurable acceptance check.

**A. Search (classic SEO)**
- Answer intent for each country on the country calculator pages. Use titles and descriptions that make people click, build them from data (rate, effective date, currency) in every language, and keep each one inside its length budget.
- Add internal links between neighbouring countries and countries that are often compared. This is an open backlog item.
- Add country names per locale. Country names are still English in the database, which is an open backlog item.
- Show freshness signals that are true: `dateModified` and a visible "rates checked on" date, taken from the actual data sync.
- Make the JSON-LD complete and valid on every page type (WebApplication, Dataset, FAQPage only where the FAQ is visible, BreadcrumbList, Organization).
- Keep `SeoPolicy::ENGLISH_BODY_ROUTES` honest. If you translate a page, remove it from the list.

**B. AI search and agents (GEO)**
- Put the answer first on every fact page: one sentence that a model can quote, with the number, the date and the source in the same sentence.
- Offer a Markdown alternate for every content page (for example `Accept: text/markdown` and/or a `.md` URL) and advertise it with `<link rel="alternate" type="text/markdown">`. Keep it consistent with the HTML, generated from the same data, never written twice.
- Expand `llms.txt` and `llms-full.txt` from that same source, with stable anchors for each country and rate type.
- Improve the MCP server where agents get real value, such as clearer tool descriptions, better structured outputs, or one new tool if the plan justifies it. Follow the three-change rule in `AGENTS.md` and keep `McpServerTest.php` green.
- Keep the OpenAPI document, the API responses and the pages in agreement, and add a test that fails if they drift.

**C. UX**
- Find the fastest path to the answer for the four core tasks: calculate, validate a VAT number, compare two countries, and see what changed. Count the steps and the time to answer before and after.
- Close gaps that real users hit, judged against `PRODUCT.md` (lead with the task, earn trust visibly, reveal complexity progressively). Do not add features for show.
- Check every changed page at 390px and 1440px, in light and dark mode, with Playwright screenshots. Commit the key before/after shots to `docs/screenshots/v3-<MODEL_SLUG>/`.

**D. Performance**
- Target these budgets on throttled mobile: LCP ≤ 2.0s, CLS ≤ 0.02, TBT ≤ 150ms, and no regression in JS bytes on any measured page. Get there with real fixes (payload, queries, caching, critical CSS, images), not by removing content.

**E. Accessibility**
- Reach zero serious or critical axe violations on all measured pages, meet WCAG 2.2 AA, and make sure every core task can be completed with the keyboard alone and is announced correctly by a screen reader. This includes the open backlog item on visible focus for every interactive element.

**F. The showcase page**
- Build `/iterations`, localised like every other route, which tells the story of v1 → v2 → v3. For each version, show its date, who or what built it (for example the model name for v3), a link to the archive and its scorecard.
- Read the content from one data file (for example `config/iterations.php` or a JSON file) so later model runs can add a row without touching the view.
- Use the existing design system and table patterns, and only real numbers from your scorecard.
- Link the page from the footer and the changelog.

### 4. How to work

- **Use parallelism where it helps.** Split independent work (the 24-locale translations, the per-page audits, the screenshot runs, the archive generation) across subagents or workflows. Keep the decisions that cut across workstreams (URL scheme, data sources, shared components) in one place.
- **Verify before you claim.** Every acceptance check needs evidence: a test, a measurement in `after.json`, or a screenshot. Look at your own screenshots and fix what you see.
- **Review adversarially.** Before each push, re-read your diff as a strict reviewer would, run the code-review skill if you have it, and fix what it finds.
- **Write tests for behaviour.** Every new route, SEO rule, Markdown alternate, MCP change and archive guard gets a Pest test. Keep the PHP and JS VAT maths covered by matching test cases.
- **Keep the documentation current.** Update `AGENTS.md`, `DESIGN.md` (only if you add a real pattern), `README.md` and `CHANGELOG.md`. Add a `## [6.0.0]` changelog entry in the house style: plain, user-facing language with no file names.

### 5. Deliverables

At `docs/benchmarks/v3-<MODEL_SLUG>/`:
- `plan.md`: the ranked candidates and what you chose and why.
- `baseline.json` and `after.json`: the same schema and pages, measured by the same commands.
- `scorecard.json`: every metric with before, after and delta; the test count before and after; the number of commits; files changed; lines added and removed; wall-clock time; and the tools and environment used.
- `report.md`: what you shipped, the measured results, what you tried and abandoned, known issues, any metric that got worse and why, and the next three things you would do. State your failures plainly.
- `docs/screenshots/v3-<MODEL_SLUG>/`: before and after screenshots of the key pages.

### 6. Definition of done

- [ ] Branch `v3/<MODEL_SLUG>` is pushed, `php artisan test` passes and `./vendor/bin/pint --test` is clean
- [ ] `public/v2/` is complete and guarded by its own test; `public/v1/` is untouched
- [ ] Every URL indexed at baseline returns 200 or a 301 to its replacement (prove it with a test or a committed crawl)
- [ ] Every new string exists in all 24 locales
- [ ] `/iterations` works in every locale and shows only real numbers
- [ ] The performance, accessibility and SEO targets are met on the measured pages, or `report.md` explains each miss
- [ ] PHP and JS VAT maths agree, and tests cover it
- [ ] The scorecard and report are complete and honest

Start with section 1. Don't ask for confirmation unless you are blocked by something only a human can decide. If you are, write the question at the top of `report.md` and carry on with everything else.
