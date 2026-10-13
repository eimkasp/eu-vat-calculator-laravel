# Changelog

All notable changes to EU VAT Info are documented here.

---

## [5.4.0] — 2026-10-13 — Quicker to answer, easier to find your way

### Improved
- **The calculator is ready sooner** — the country list is part of the page instead of being built in the browser, and the rates table filters with one check instead of one per row, so the calculator and the home page react about a third sooner on a mid-range phone (blocking time 448 → 310 ms on a country calculator, 497 → about 360 ms on the home page).
- **The total on a phone's first screen** — on phones the calculator shows the total right under the amount, so you see the answer without scrolling.
- **Search finds more** — the search dialog matches every word you type, finds countries by their name in your language, opens each country's validator and rate history, and checks a VAT number when you type one, such as DE123456789.
- **Clearer navigation** — the header links to the VAT history, the Tools menu holds updates, the MCP server and popular calculations, and the tools page lists the VAT guides.
- **For developers** — the API page documents the rates and calculator API with sample responses, and the dataset page links the REST API and the MCP server.
- **A validator result you can act on** — a valid number shows the date VIES checked it, with links to that country's calculator and the B2B services guide; each country's validator shows its own number format as the example.
- **Calmer pages** — one main button per page, tighter rows in the rates and format tables, tables that fit a phone on comparisons and shared calculations, the rate change filter only offers rate types that have changes, and the date line above the map, the comparison and the popular calculations no longer touches the card below it.
- **Numbers in your language** — rates and multipliers in the country questions are written the way each language writes them (5,5 % in French).
- **Honest freshness** — the home page and the footer say the rates are checked every week, which is how often the dataset is synced.

### Fixed
- The rate change pages lead back to the VAT history in their breadcrumbs, and the comparison breadcrumbs no longer link to the home page twice.
- The footer no longer lists the PDF tools link twice.
- Pages send their stylesheet and script as preload hints, so a CDN with Early Hints can start fetching them before the page arrives.

---

## [5.3.0] — 2026-10-12 — Faster, steadier and easier to use with a keyboard

### Improved
- **The calculator responds sooner** — the page scripts no longer wait for Cloudflare's script loader, so the calculator, the theme and dark mode are ready as soon as the page is.
- **Back and forward are instant** — going back to a page you just left restores it straight away instead of loading it again.
- **No jump when the font arrives** — text first shows in a font sized like Inter, so headings and paragraphs no longer move when it loads (on the Maltese calculator this layout shift drops from 0.11 to none).
- **A lighter map** — the Europe map is 40% smaller with the same shapes, so the map and calculator pages load less.
- **The header fits every language** — in Bulgarian, Estonian, Maltese, Spanish, Greek, Lithuanian and Portuguese the desktop header no longer pushes the page sideways; below 1280px the menu button holds the navigation and the search field stays visible.
- **Language links open the same page** — choosing a language opens the page you are on in that language, with ordinary links instead of a list that switches as you scroll it.
- **Keyboard and screen reader fixes** — the country picker and the search dialog give focus back where it was, the search dialog keeps focus inside it, the add or remove VAT switch works with the arrow keys, wide tables scroll with the keyboard, validator errors are read with their field, results are announced briefly instead of in full, links that open a new tab say so, and the clear buttons are easier to tap.
- **Clearer pages in search and social previews** — each rate change has a dated title and a full description, every page names its language and translations for social previews, the share image follows the current design, and breadcrumbs, the dataset and the site name are described the same way everywhere.
- **More for AI assistants** — llms.txt lists the VAT guides and the country comparisons, and llms-full.txt lists the rate changes of the last 24 months; every page links its Markdown version.

### Fixed
- Translated pages no longer point to AMP pages in English, pages with no translation or with noindex no longer list other languages, and the 404 page no longer names a canonical address.
- The sitemap lists the changelog, privacy, donation and Chrome extension pages, leaves out the empty categories section, and dates pages by their last release instead of the last deploy.
- Percentage points in the rate history are written the way each language writes them.

---

## [5.2.0] — 2026-10-12 — The design system and brand, in one place

### Added
- **The country calculator pieces in the design system** — the [design system reference](/styleguide) now shows the country heading, the rate reference, VAT on common amounts, the questions list and the VAT number format hint, with code you can copy.
- **Brand guidelines** — [/brand](/brand) shows the mark (with an SVG to download), the colours, the type and the voice of EU VAT Info, and how to credit our VAT data.
- **A Storybook for developers** — every component and pattern is also a story at [/storybook](/storybook/), built from the same templates as the site, with a light and dark switch and an accessibility check on each one.

### Improved
- **Clearer design system figures** — the reference counts its live examples and components, and lists how the stories are made and how to run them.

---

## [5.1.1] — 2026-10-11 — A calmer calculator heading

### Improved
- **Everything on one line** — on each country calculator, the heading, the rates, the calculator and the sections below now start and end on the same edges, and the country's flag sits beside "EU member" instead of pushing the heading in.
- **Common amounts in an even row** — the five amounts fill one row under the amount field at every screen size.
- **Reduced rates read clearly on phones** — two reduced rates stack one above the other instead of breaking across lines.

---

## [5.1.0] — 2026-10-11 — A calculator and validator you can check

### Added
- **The date and the source next to every rate** — the calculator, each country's rate table, shared calculations, comparisons, the VAT map and the popular calculations now say when the rates were last updated and link to where they come from: the European Commission for EU countries.
- **Common amounts in one tap** — choose 100, 500, 1,000, 5,000 or 10,000 under the amount field instead of typing it.
- **Since when a rate applies** — each country's standard rate shows the date it took effect and the rate it replaced, with a link to the full rate history.
- **VAT on common amounts for every country** — a table of €100 to €10,000 with VAT added and removed, each figure opening its full calculation.
- **Answers to the common questions** — every country calculator answers what the rate is, how to add and remove VAT, when the rate last changed and what a VAT number looks like, worked out from that country's own figures.
- **Real VAT number formats** — the validator shows each country's exact format, such as DE999999999 or NL999999999B99, with a table of all 27 member states, and points out a number that does not fit its country's format before you check it.

### Improved
- **Clearer search results in every language** — page titles and descriptions now fit what search engines show, in all 24 languages.
- **The rate history in the site map** — the year pages before 2000 open again, and the popular calculations are listed for search engines.
- **Better answers in AI assistants** — the AI guide (llms.txt), the API and the MCP server now give every rate type, each country's VAT number format and the sources behind them, and any page can be read as clean Markdown with its tables and questions intact.
- **Faster first view** — pages send less markup, and the sections further down, such as the questions, the format table and the footer, are laid out only as you scroll to them, while staying fully readable for search engines and screen readers.
- **Easier on phones and keyboards** — the comparison table can be scrolled with the keyboard, page regions have unique names for screen readers, long translated site names no longer push the header off the screen, and on phones the country flag sits beside the calculator heading so the text lines up with the rest of the page.

### Fixed
- Reduced rates now match the European Commission's tax database: Estonia 9% and 13%, Finland 10% and 13.5%, Lithuania 5% and 12%, Romania 11% and Slovakia 5% and 19%. Austria's 4.9% rate on basic foods from 1 July 2026, Cyprus's 3% rate, Greece's 4% rate and Malta's 12% rate are listed too.
- A fresh installation showed outdated standard rates for Estonia, Finland, Romania and Slovakia until the rates were refreshed.

---

## [5.0.3] — 2026-10-10 — The 5.0 site, preserved

### Added
- **A second archive** — EU VAT Info 5.0 as it looked on 10 October 2026 stays online as a read-only archive at [/v2](/v2/), next to the [/v1](/v1/) archive of the site before the redesign. Calculations, the country picker and search still work there; VAT number checks, filters and sign-ups point you to the current site.

---

## [5.0.2] — 2026-10-01 — Every language and popular calculations in search

### Added
- **Every language is open to search engines** — translated pages in all 24 languages can now appear in Google, each linked to its translations. Pages that are still English-only point search engines to their English version.
- **Popular calculations can be found in search** — VAT on the top amounts, from €100 to €10,000, at each country's standard rate now has its own page in every language. Other amounts still work and can be shared, but stay out of search results.

### Improved
- **Tidier calculation links** — addresses such as `/vat-calculation/germany/1000.00/19.00/exclude` now redirect to `/vat-calculation/germany/1000/19/exclude`, and the share button builds the short form directly.
- **A fuller site map for search engines** — the top calculation pages and the MCP server page are listed, and each page is listed in exactly the languages it is translated into.

---

## [5.0.1] — 2026-09-29 — A more polished site and better AI connections

### Added
- **A public design system** — the [design system reference](/styleguide) shows every colour, type style, component and page pattern the site is built from, with code you can copy. The same tokens download as a Design Tokens file and the full guide reads as Markdown, so AI coding agents can build with it too.

### Improved
- **A clearer MCP server page** — step-by-step setup for Claude, Claude Code, VS Code and Cursor, a live request tester and a plain description of every tool, in all 24 languages.
- **A sixth MCP tool** — AI assistants can now list recorded and upcoming VAT rate changes, and every tool returns structured results that newer clients read directly.
- **Easier for AI assistants to find and use** — llms.txt, the API catalog, the MCP server card and the agent skill guides now point to the current API, its OpenAPI description and every tool.
- **Richer search results** — the VAT calculator, the API documentation and the MCP server page now describe themselves to search engines.
- **One consistent look on every page** — rate histories, yearly and upcoming VAT changes, category comparisons, scenario guides, the dataset, the API documentation, the donate page, the Chrome extension page and the privacy policy now share the same layout, icons and number formats as the rest of the site.
- **Calmer, more consistent wording** — menus, headings and buttons use sentence case throughout, and rates read 19% instead of 19.00%.
- **Better on phones** — the rates table on the home page fits the screen with each country's reduced rates underneath, and the end of every page scrolls clear of the tab bar.
- **A more useful site map** — pages and developer resources sit in two balanced columns, and link arrows no longer wrap onto a line of their own.

### Fixed
- The site map showed stray code next to each country flag.
- Links to the privacy policy in the site's machine-readable settings pointed to a page that does not exist.
- The "On this page" links in the API documentation went nowhere, and the example request was cut off.
- The VAT map table and the site map were labelled "EU" while also listing other European countries.
- The header search box cut off its own label.

---

## [5.0.0] — 2026-09-29 — A faster, clearer EU VAT Info

### Added
- **A new, more professional look** — a solid EU navy and gold identity with crisp, square-cornered controls, clean white cards and clear tables, so rates and totals are easier to read and feel more trustworthy.
- **Dark mode** — the site follows your device's light or dark setting, and you can switch it any time from the header.
- **Instant search** — press ⌘K, Ctrl K or / on any page to jump straight to a country calculator or tool.
- **Open a shared calculation in the calculator** — shared result pages now reopen in the full calculator with the same amount, rate and direction.
- **Widget builder** — choose a country and a vertical or horizontal layout, see a live preview and copy the embed code in one click.
- **Recent lookups** — the VAT number validator remembers your latest checks on your own device, so you can re-run them with one click.
- **The previous version, preserved** — the site as it looked before this redesign stays online as a read-only archive at [/v1](/v1/), so you can follow how EU VAT Info has evolved.

### Improved
- Every page has been redesigned with one consistent, accessible look, including clearer tables, larger touch targets and visible keyboard focus.
- The VAT calculator updates results instantly as you type and understands both 1,234.56 and 1.234,56.
- The VAT map can be explored with a keyboard and screen reader, and every colour is backed by a ranked table.
- The VAT number validator detects the country from the prefix and explains results in plain language, including when a member state does not publish company details.
- Pages load faster: fonts and flags are served from our own servers and background images are three times smaller.
- All new interface text is available in the 24 official EU languages, including page navigation on the rate history.
- Code examples on the API and MCP pages can be scrolled with the keyboard, and their copy buttons are announced by screen readers.

### Fixed
- Countries with more than one reduced rate, such as Austria (10% and 13%), now show every rate instead of an average.
- Bulgaria now uses the euro.
- A valid VAT number is no longer shown as invalid when a member state's system is temporarily unavailable.
- Greek VAT numbers are checked with the EL prefix that VIES expects.
- Shared calculation pages now calculate "remove VAT" comparisons correctly and show each country's own currency.
- The embeddable widget can be placed on other websites again.
- Fixed a security issue on the VAT number validator page.
- Text on the donate page that was nearly invisible in light mode is readable again.

---

## [4.1.0] — 2026-04-29 — Shareable calculations

### Added
- **Shareable calculation links** — every VAT calculation gets its own unique URL. Copy and send it to your accountant, client, or colleague and they'll see the exact same breakdown — country, amount, rate, and direction all preserved.
- **Popular VAT calculations** — browse ready-made VAT breakdowns for the most common amounts (€100, €500, €1,000, €5,000…) across all 27 EU countries. No typing required, just click and see.
- **This changelog** — you can now track what's new without digging through anything.

### Improved
- Every country's VAT calculator page now shows a clearer title and description including the exact standard rate, making it much easier to find the right page in search results.
- The VAT rate change history page now surfaces in search results with richer information, so you can see at a glance that it covers all 27 EU countries.

---

## [4.0.0] — 2025-07-17 — Accessibility & Usability

### Improved
- Text on country hero images now has stronger contrast so it stays readable regardless of how dark or light the flag colours are.
- Animations and transitions now respect your operating system's "reduce motion" preference — no more spinning or sliding if you've asked your device to keep things still.

### Fixed
- "Skip to content" link now works correctly for keyboard and screen-reader users.
- Navigation focus rings are clearly visible again when using the keyboard to move through the site.

---

## [3.0.0] — 2026-04-01 — Trust & Transparency

### Added
- Trust badges on the homepage and calculator pages: **Official European Commission data**, **Always free**, **All 27 EU countries**, **No sign-up required** — so you know exactly what you're getting.

### Fixed
- VAT calculator pages in all 24 EU languages now correctly show translated titles and descriptions in search results, not English text.
- Country selector dropdowns now work correctly with screen readers and other assistive technology.

---

## [2.0.0] — 2026-04-01 — Homepage Refresh

### Added
- New headline and introduction on the homepage — first-time visitors can now immediately understand what EU VAT Info is and what it does.

### Improved
- Mobile layout across calculator pages — better spacing, larger tap targets, and improved readability on small screens.

---

## [1.0.0] — 2026-04-01 — Launch

### Added
- Full support for all 24 official EU languages — the entire site adapts to your preferred language automatically.
- VAT calculator for all 27 EU countries with standard, reduced, super-reduced, and parking rates.
- VIES VAT number validator to verify EU business VAT registrations instantly.
- Interactive Europe map showing current VAT rates at a glance.
- VAT rate change history going back to 2000.
- DaisyUI theme had placeholder test colors (`#ff00ff`, `#00ff00`) — replaced with brand palette
  (`secondary: #0EA5E9`, `neutral: #374151`, `warning: #F59E0B`)
- Country rates table heading hardcoded English — replaced with translation calls
- FAQ accordion on country page had no ARIA attributes — added full accordion ARIA pattern
  (`aria-expanded`, `aria-controls`, `role="region"`, `aria-hidden` on decorative SVG)
