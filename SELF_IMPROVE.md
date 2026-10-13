# Self-Improvement Cycle for EU VAT Info

Paste this prompt into a new conversation to run a structured improvement cycle.
Each phase is self-contained — stop after any phase to review before continuing.

---

## Instructions

Read AGENTS.md and DESIGN.md first to understand the project. Then execute the phases below in order. Use the installed skills when applicable (the project's `design-system` skill for any UI change).

---

## Phase 0: Measure

Measure before changing anything, and again at the end, on the same machine:

```bash
docs/benchmarks/v3-claude-opus-5-5/tools/run.sh before   # 12 pages × en/de/mt: LCP, TBT, CLS, bytes, axe, keyboard tasks, SEO, AI, queries
BASE=http://127.0.0.1:8123 OUT=audit-before.json node docs/benchmarks/cycle-5.3/tools/audit.mjs   # every page type, light and dark, 390 and 1440px
```

Also check production directly (`curl -sI https://vat.businesspress.io/…`): caching headers, compression, what Cloudflare rewrites. The 5.3 cycle found Rocket Loader and Livewire's `no-store` this way; neither shows on a local server. Run the wide audit again after the fixes: the second pass of 5.3 caught a regression the first fixes introduced.

---

## Phase 1: Audit (Read-Only)

Run a full audit across four dimensions. Do NOT make any changes — only report findings.

### 1.1 SEO Audit
Use the `seo-audit` skill. Check:
- [ ] Meta titles & descriptions on all page types (home, country, calculator, map, changes)
- [ ] Open Graph / Twitter Card tags
- [ ] hreflang implementation across 24 locales
- [ ] Structured data (JSON-LD) — FAQPage, Dataset, BreadcrumbList
- [ ] Internal linking structure
- [ ] Canonical URLs and duplicate content
- [ ] Sitemap completeness (XML + HTML)
- [ ] `robots.txt` and crawl directives
- [ ] `llms.txt` / `llms-full.txt` accuracy
- [ ] Page speed indicators (render-blocking resources, image optimization)

### 1.2 Accessibility Audit
Use the `accessibility` skill. Check:
- [ ] Semantic HTML on all page types
- [ ] ARIA labels on interactive elements (calculator, validator, map)
- [ ] Color contrast (semantic tokens, light and dark)
- [ ] Keyboard navigation (tab order, focus indicators)
- [ ] Form labels and error messages
- [ ] Skip navigation link
- [ ] Image alt text
- [ ] Screen reader compatibility for dynamic Livewire content

### 1.3 Code Quality Audit
Check:
- [ ] Laravel 13 conventions (route files, middleware, providers)
- [ ] Livewire 4 best practices (lazy loading, wire:navigate, events)
- [ ] N+1 query issues in Livewire components
- [ ] Service class responsibilities (single responsibility)
- [ ] Test coverage gaps (compare existing tests vs. components)
- [ ] Unused routes, dead code, stale imports
- [ ] Code style (run `./vendor/bin/pint --test`)

### 1.4 Performance Audit
Use the `performance` skill. Check:
- [ ] Livewire component payload sizes
- [ ] Database query count per page load
- [ ] Cache usage effectiveness (Redis, response cache)
- [ ] Asset bundle size (Vite output)
- [ ] Image optimization (flags, maps)
- [ ] Lazy loading opportunities (below-fold components)

### Output
Create a prioritized findings table:

| # | Category | Severity | Finding | File(s) | Effort |
|---|----------|----------|---------|---------|--------|

Severity: Critical > High > Medium > Low
Effort: Quick (< 30 min) > Medium (1-2h) > Large (half day+)

---

## Phase 2: Fix (Critical + High Priority)

Address all Critical and High severity findings from Phase 1. Work through them one at a time.

For each fix:
1. Read the relevant file(s)
2. Make the change
3. Run tests: `php artisan test`
4. Run lint: `./vendor/bin/pint`
5. Verify no regressions

Commit after completing all fixes in a category using the `git-commit` skill:
```
fix(seo): add missing meta descriptions to country pages
fix(a11y): add ARIA labels to VAT calculator form
fix(perf): eager-load country relations in home page
```

---

## Phase 3: Growth (Content & SEO Opportunities)

Use `programmatic-seo` and `seo-audit` skills.

### 3.1 Content Gap Analysis
- [ ] Identify high-value keywords not yet covered (e.g., "VAT rate [country] 2026", "EU VAT changes")
- [ ] Propose new pages or tabs that would attract search traffic
- [ ] Check if all country tabs have unique, valuable content

### 3.2 Structured Data Expansion
- [ ] Add FAQPage schema to country pages
- [ ] Add Dataset schema for VAT rate data
- [ ] Add BreadcrumbList to all pages
- [ ] Validate with Google Rich Results Test format

### 3.3 Internal Linking
- [ ] Add contextual cross-links between countries
- [ ] Link calculator results to relevant country pages
- [ ] Add "related countries" or "similar VAT rates" sections

### 3.4 Translation Coverage
Use the `i18n-localization` skill.
- [ ] Audit missing translation keys across all 24 locales
- [ ] Ensure all user-facing strings use `__('ui.key')` — no hardcoded English
- [ ] Check DeepL translation quality for key pages

Commit growth improvements with the `git-commit` skill.

---

## Phase 4: Polish (UX & Design)

Use `frontend-design` and `web-design-reviewer` skills.

- [ ] Review visual hierarchy on key pages (home, country, calculator)
- [ ] Check responsive design at mobile / tablet / desktop breakpoints
- [ ] Verify dark mode consistency
- [ ] Improve loading states for Livewire components
- [ ] Add micro-interactions or transitions where appropriate
- [ ] Check empty states (no results, API errors)
- [ ] Review CTA placement and clarity

Commit polish improvements with the `git-commit` skill.

---

## Phase 5: Summary & Next Cycle

### 5.1 Summary Report
Create a brief summary of what was done:
- Number of issues found per category
- Number of issues fixed
- Key improvements made
- Remaining items for next cycle

### 5.2 Update Tests
- [ ] Add Pest tests for any new functionality
- [ ] Ensure all existing tests still pass
- [ ] Run: `php artisan test`

### 5.3 Final Commit
Use the `git-commit` skill to commit any remaining changes.

### 5.4 Next Cycle Backlog
List remaining Medium/Low items from Phase 1 plus any new ideas discovered during the cycle. Save to a `BACKLOG.md` file for the next session.
