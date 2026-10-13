---
name: EU VAT Info
description: A solid, institutional EU VAT reference. Flat EU navy identity, a thin EU gold signature, square-cornered controls and quiet white surfaces where every number is easy to read.
version: 5.3
source: resources/css/app.css
enforced-by: tests/Feature/DesignSystemTest.php
colors:
  brand: "#132B5A"
  brand-deep: "#0B1E44"
  gold: "#FFCC00"
  action: "#2159BF"
  action-deep: "#16479D"
  action-soft: "#EAF2FE"
  ink: "#0F1828"
  ink-muted: "#4A5364"
  ink-quiet: "#646C7B"
  workspace: "#F4F6F9"
  surface: "#FFFFFF"
  surface-subtle: "#F7F9FC"
  line: "#DDE2E8"
  success: "#007748"
  warning: "#AE4900"
  danger: "#BE2323"
typography:
  family: "Inter Variable (optical sizing), metric-matched Arial or Roboto fallback"
  display: { size: "3rem", weight: 700, lineHeight: 1.08, letterSpacing: "-0.03em" }
  headline: { size: "2.5rem", weight: 700, lineHeight: 1.1, letterSpacing: "-0.035em" }
  title: { size: "1.125rem", weight: 700, lineHeight: 1.35 }
  body: { size: "1rem", weight: 400, lineHeight: 1.6 }
  label: { size: "0.8125rem", weight: 600, lineHeight: 1.35 }
  kicker: { size: "0.75rem", weight: 600, lineHeight: 1.67, letterSpacing: "0.08em", transform: "uppercase" }
rounded:
  control: "4px"
  card: "6px"
  panel: "8px"
motion:
  out-quint: "cubic-bezier(0.22, 1, 0.36, 1)"
  duration: "150ms (colour), 200ms (position)"
---

# EU VAT Info design system

This file is the single source of truth for the interface. Read it before you change a Blade view, `resources/css/app.css` or an Alpine component. Rules written as **must** are checked by `tests/Feature/DesignSystemTest.php`, so a violation fails the build with the file, the line and the fix.

The rendered reference lives at [`/styleguide`](https://vat.businesspress.io/styleguide): every token, component and pattern drawn with the production stylesheet, with its code. Two machine-readable companions are generated from the same sources: `/styleguide/design.tokens.json` (Design Tokens Community Group format 2025.10, built from `app.css` and the frontmatter above) and `/styleguide.md` (this file).

**North star: the institutional reference desk.** People come here to get a rate, a total or a validation result they can put on an invoice. The interface should feel like a trustworthy public-sector or financial tool: flat EU navy for identity, one thin line of EU gold as the signature, white paper-like surfaces with hairline borders, square corners and figures set in tabular type. Nothing glows, floats, blurs or bounces.

## Quick start for agents

1. **Use tokens only.** Style with semantic utilities (`bg-surface`, `text-ink-muted`, `border-line`, `bg-action-soft`, `text-success`) and the `app-*` classes below. You must not use raw Tailwind palette colours (`bg-blue-600`), `dark:` variants, hex/rgb/oklch literals, default Tailwind shadows, blur, or gradient utilities in templates.
2. **Keep it flat and square.** Use the three radius tokens (`rounded-control`, `rounded-card`, `rounded-panel`). Buttons, chips and badges are rectangles; `rounded-full` is only for dots, spinners, avatars and numbered step markers.
3. **Pick the page skeleton.** A tool page opens with a navy hero (`hero-canvas` + `<x-hero-backdrop />`) holding one `app-surface-raised` card. A reference page opens with `<x-page-header>` followed by `app-surface` cards inside `app-container`.
4. **One primary action per view.** Use `app-button-primary` for it, `app-button-secondary` or `app-button-ghost` for the rest, and `app-button-inverse` / `app-button-on-brand` on navy.
5. **Text and numbers.** Put every string in `lang/en/ui.php` and all 24 locales. Money goes through `App\Support\Vat\Money::format`, and every column or total of numbers uses `tabular`.
6. **Before you commit**, run `yarn build`, `php artisan test` and `vendor/bin/pint --test`. The design tests run with the suite.

## Principles

1. **Solid, not shiny.** Surfaces are opaque. Depth comes from hairline borders and one soft shadow for things that float (menus, the palette, toasts). No glass, blur, glow, aurora or decorative gradients.
2. **Navy is identity, gold is the signature, blue is action.** Navy (`brand`) frames the page: header, heroes and footer. Gold marks "you are here" and the brand mark, in small doses. Action blue marks what you can press or what is selected. Status colours mean status and nothing else.
3. **Numbers are the hero.** Results use the largest type on the view, tabular figures and a plain-language summary line.
4. **Square geometry.** 4px controls, 6px cards, 8px panels. Rectangles read as precise and institutional; pills read as playful.
5. **Honest states.** Loading, empty, error and "service unavailable" states are designed, worded and announced; nothing fails silently.
6. **Accessible by default.** WCAG 2.2 AA in both themes, a visible focus ring on every surface (gold on navy), 44px touch targets and reduced-motion support.
7. **Quiet motion.** Colour changes take 150ms. Menus fade in with a 4px drop. Nothing scales, springs or loops.

## Tokens

All tokens live in `resources/css/app.css`. Semantic colours are CSS variables (`--ui-*`) exposed to Tailwind through `@theme inline`, so `--ui-surface` becomes `bg-surface`, `text-surface` and `border-surface`. The `.dark` class on `<html>` swaps every value, which is why templates never need `dark:`. The `.theme-light` class restores the light values inside a dark subtree; `/styleguide` uses the pair to show both themes side by side.

### Color roles

| Token | Utility examples | Light | Dark | Use |
|---|---|---|---|---|
| `workspace` | `bg-workspace` | `#F4F6F9` | `#090D14` | Page canvas behind cards |
| `surface` | `bg-surface` | `#FFFFFF` | `#11161F` | Cards, fields, tables, menus |
| `surface-subtle` | `bg-surface-subtle` | `#F7F9FC` | `#151B25` | Table heads, result panes, sticky bars inside cards |
| `surface-muted` | `bg-surface-muted` | `#EDF0F5` | `#1C232F` | Notes, tracks, hover fills, placeholders |
| `line` | `border-line`, `divide-line` | `#DDE2E8` | `#272E3A` | Hairlines between rows and around cards |
| `line-strong` | `border-line-strong`, `ring-line-strong` | `#C5CBD4` | `#3A4352` | Field and control outlines, popover edges |
| `ink` | `text-ink` | `#0F1828` | `#F1F4F8` | Headings, values, primary text |
| `ink-muted` | `text-ink-muted` | `#4A5364` | `#B7BEC8` | Supporting text and labels (AA on every surface) |
| `ink-quiet` | `text-ink-quiet` | `#646C7B` | `#949CA8` | Placeholders, disabled text, meta |
| `brand` | `bg-brand` | `#132B5A` | `#0E1D3A` | EU navy: heroes, navy cards and panels |
| `brand-deep` | `bg-brand-deep`, `text-brand-deep` | `#0B1E44` | `#091429` | Header, footer, text on white buttons |
| `brand-soft` | `bg-brand-soft` | `#ECF3FE` | `#1B2942` | Brand-tinted panels on light surfaces |
| `action` | `text-action`, `border-action`, `bg-action` | `#2159BF` | `#81B4F6` | Links, selection, focus, data bars (never behind white text) |
| `action-deep` | `text-action-deep` | `#16479D` | `#A5CFFE` | Text on `action-soft`, hovered links |
| `action-soft` | `bg-action-soft` | `#EAF2FE` | `#1C2B47` | Selected chips, active rows, icon wells |
| `button` | `bg-button` | `#2159BF` | `#295EBD` | Fills that carry white text (stays saturated in dark) |
| `button-hover` | `hover:bg-button-hover` | `#16479D` | `#3A70D1` | Hover for `button` fills |
| `success` / `success-soft` | `text-success`, `bg-success-soft` | `#007748` / `#E0FAEA` | `#63D99B` / `#103324` | Verified, valid, decrease in rate |
| `warning` / `warning-soft` | `text-warning`, `bg-warning-soft` | `#AE4900` / `#FFF2D7` | `#F9BA5F` / `#42270E` | Needs attention, scheduled |
| `danger` / `danger-soft` | `text-danger`, `bg-danger-soft` | `#BE2323` / `#FFEEEE` | `#FE8B83` / `#4B1D1B` | Invalid, error, increase in rate |
| `focus` | set by `on-brand` and surfaces | action | action | Colour of the focus outline; gold inside `on-brand` areas |

Fixed colours (identical in both themes):

| Token | Utility | Value | Use |
|---|---|---|---|
| `gold` | `bg-gold`, `text-gold`, `border-gold` | `#FFCC00` | EU gold signature, only on navy: active nav underline, kicker square, logo dot, accent icons, focus ring. Never on white, never for body text. |
| `code`, `code-raised`, `code-muted` | `bg-code` | `#0A0F1A`, `#151C29`, `#272E3C` | Code blocks, which stay dark in both themes |
| `--color-syntax-keyword` | `text-syntax-keyword` | `#81D3FD` | Keywords, variables, spinner on code |
| `--color-syntax-function` | `text-syntax-function` | `#F5E05D` | Function and property names |
| `--color-syntax-string` | `text-syntax-string` | `#84EFAA` | Strings, JSON responses |
| `--color-syntax-literal` | `text-syntax-literal` | `#FFBF67` | Request bodies, literals |
| `--color-syntax-comment` | `text-syntax-comment` | `#9AA5B8` | Comments, meta, placeholders on code |
| `--color-syntax-ok` | `text-syntax-ok` | `#3FD59B` | HTTP methods, 2xx status |
| `--color-syntax-error` | `text-syntax-error` | `#FB8087` | Errors, 4xx/5xx status |

`white` utilities are allowed on navy and code surfaces (`text-white/80`, `border-white/15`, `bg-white/10`). Anywhere else, use a token.

### Brand surfaces

Navy areas are flat `brand` or `brand-deep` fills. They carry the `on-brand` utility, which switches the focus ring to gold so it keeps 3:1 contrast against navy; `hero-canvas`, the header and the footer already include it, and every white surface inside (`app-surface`, `app-surface-raised`, `app-popover`) switches it back to action blue.

| Piece | Recipe |
|---|---|
| Header | `on-brand sticky top-0 z-40 border-b border-white/10 bg-brand-deep text-white` |
| Hero | `hero-canvas` (flat `brand`) with `<x-hero-backdrop />` |
| Footer | `on-brand bg-brand-deep text-white` |
| Navy card inside content | `bg-brand text-white` block at the top of an `app-surface` |
| Tile, stat group or field on navy | `app-brand-panel` (6% white fill, 15% white hairline) |

`<x-hero-backdrop />` is the only image treatment: the EU mountain photograph, desaturated through `mix-blend-luminosity` at 20% opacity (`opacity="opacity-30"` on country calculators) under `hero-scrim`, a navy-to-navy fade that keeps text above AA. It is the one sanctioned gradient. Do not add colour gradients, glows, patterns or other photos.

### Typography

Inter Variable is self-hosted with the optical-size axis, so large text automatically uses Inter's display cut. Body text uses the stylistic sets `cv11` and `ss03`. Until it loads, text uses `Inter Fallback` (local Arial and Arial Bold) or `Inter Fallback Roboto` (Android), with one face per weight range (regular, medium, bold) resized with `size-adjust` and the ascent and descent overrides to Inter's metrics, so the swap does not rewrap lines or shift the layout. Keep both in `--font-sans` right after Inter.

| Role | Classes | Where |
|---|---|---|
| Display | `text-4xl sm:text-5xl font-bold tracking-[-0.03em] sm:leading-[1.08]` | Hero headings, hero results |
| Headline | `text-3xl sm:text-[2.5rem] font-bold tracking-[-0.035em]` | `<x-page-header>` titles |
| Section | `text-xl font-bold text-ink` | Section headings inside a page |
| Title | `text-lg font-bold text-ink` or `text-base font-semibold` | Card titles |
| Body | `text-base leading-7 text-ink-muted` | Paragraphs, capped at 68–72ch |
| Small | `text-sm text-ink-muted` | Helper text, table cells |
| Label | `text-[0.8125rem] font-semibold text-ink-muted` | Field labels, data labels (sentence case) |
| Eyebrow | `app-eyebrow` | One short uppercase label above a heading on a light surface |
| Kicker | `app-kicker` | One short uppercase label with the gold square, on navy. In a country heading the country's flag takes the square's place from `sm` up (`sm:before:hidden`) |
| Column head | `app-table` `thead` (uppercase, 0.06em tracking) | Table headers |

Headings are always one solid colour: never highlight words in a headline. Use `tabular` for every figure that can change or be compared: rates, money, counts, dates in tables.

Write interface text in sentence case: headings, buttons, labels, menus and table heads capitalise only the first word and proper nouns (EU, VAT, VIES, country names, EU VAT Info). Rates drop trailing zeros (`19%`, `5.5%`) through `Country::formatRate` or `Money::percent`, and dates read day first (`1 Aug 2025`, `1 August 2025`).

### Shape

| Token | Utility | Size | Use |
|---|---|---|---|
| `--radius-control` | `rounded-control` | 4px | Buttons, fields, chips, segmented track, menu rows, icon wells, notes |
| `--radius-card` | `rounded-card` | 6px | `app-surface` cards, popovers, menus, code blocks, brand panels |
| `--radius-panel` | `rounded-panel` | 8px | The primary task card (`app-surface-raised`), the command palette, navy CTA blocks |
| extra small | `rounded-xs`, `rounded-sm` | 2px, 4px | Flags, badges, keyboard hints, inline code, bar ends |
| circle | `rounded-full` | — | Status dots, spinners, avatars, numbered step markers only |

Tailwind's `rounded-md` … `rounded-3xl` and arbitrary radii must not appear in templates.

### Elevation

| Token | Utility | Use |
|---|---|---|
| `--shadow-card` | `shadow-card` | Resting cards and secondary buttons (built into `app-surface`) |
| `--shadow-workflow` | `shadow-workflow` | The primary task card on a hero (built into `app-surface-raised`) |
| `--shadow-floating` | `shadow-floating` | Menus, popovers, the palette, toasts (built into `app-popover`) |

You must not use Tailwind's default `shadow-sm`…`shadow-2xl`.

### Motion

| Token | Utility | Use |
|---|---|---|
| `--ease-out-quint` | `ease-out-quint` | Every transition |
| `pressable` | `pressable` | Colour, border and shadow feedback (150ms) on buttons, chips and tappable cards; no scaling |
| `--animate-fade-in`, `--animate-pop-in` | `animate-fade-in`, `animate-pop-in` | Content that appears (toasts use `animate-pop-in`, a 4px rise) |

Enter popovers with `x-transition:enter="transition duration-150 ease-out-quint" x-transition:enter-start="-translate-y-1 opacity-0"`. `prefers-reduced-motion` collapses all durations and disables view transitions globally, so components need no extra code.

### Layout

- `app-container` is the only page width: max 80rem, with 16/24/32px gutters at mobile, `sm` and `lg`.
- Section rhythm: `py-8 sm:py-10` between content sections, `pb-12 sm:pb-16` at the bottom of heroes.
- Grids start from one column (`grid grid-cols-1 md:grid-cols-2`). A grid without a base `grid-cols-*` can overflow on phones.
- Wrap wide tables in `relative overflow-x-auto` with `tabindex="0" role="group"` and a name (`aria-labelledby` the section heading), so keyboard users can scroll them. No page may scroll horizontally at 320px.
- `mobile-nav-safe` on the footer reserves the height of the mobile tab bar on phones, so the end of every page scrolls clear of it.
- A card that holds a sticky bar uses `overflow-clip`, not `overflow-hidden`, so the bar can stick to the viewport.
- The header shows the full navigation from `xl` (1280px) and the menu button below it, so the longest languages fit. Its right-hand group is a container (`@container`): the search field and the GitHub link appear only when there is room for them (`@min-[27rem]`, `@min-[30rem]`), and the site name truncates before anything overflows. Check a header change in Bulgarian, Estonian and Maltese at 1024, 1280 and 1536px.

## Components

Copy these recipes; do not rebuild them from utilities.

### Page skeletons

Tool page (calculator, validator, shared result):

```blade
<section class="hero-canvas">
    <x-hero-backdrop />
    <div class="app-container relative pb-12 pt-6 sm:pb-16 sm:pt-8">
        <x-site-breadcrumbs :items="[__('ui.nav.vat_calculator') => '']" variant="dark" />
        <p class="app-kicker mt-6">…</p>
        <h1 class="mt-3 text-4xl font-bold tracking-[-0.03em] text-white sm:text-5xl">…</h1>
        <div class="app-surface-raised mx-auto mt-8 max-w-4xl overflow-hidden text-ink">…</div>
    </div>
</section>
<div class="app-container space-y-8 py-8 sm:py-10">
    <section class="app-surface p-5 sm:p-6">…</section>
</div>
```

`hero-canvas` alone (without the backdrop) is a flat navy block, for example a call-to-action with `hero-canvas rounded-panel p-8`.

Reference page (tables, lists, guides):

```blade
<x-page-header :title="__('ui.history.title')" :description="__('ui.history.subtitle')" :eyebrow="__('ui.nav.vat_tools')" :breadcrumbs="[__('ui.history.nav') => '']">
    <x-slot:actions>…</x-slot:actions>
</x-page-header>
<div class="app-container space-y-8 py-8 sm:py-10">…</div>
```

`<x-page-header>` renders `app-page-header`: a plain white band with a bottom hairline.

### Surfaces

| Class | Use |
|---|---|
| `app-workspace` | Body canvas (set on `<body>`) |
| `app-surface` | Default card: `rounded-card`, hairline border, `shadow-card`. Pad with `p-5 sm:p-6` |
| `app-surface-raised` | The one primary task card of a view (calculator, validator, shared result): `rounded-panel`, `shadow-workflow` |
| `app-popover` | Menus, the country combobox, the palette panel and toasts: `rounded-card`, strong border, `shadow-floating` |
| `app-brand-panel` | Tiles, stat groups, badges and fields on navy |
| `app-sticky-bar` | A sticky sub-header inside a card, such as the year rows in the rate history |
| `app-deferred` | A section that starts below the first screen (questions, reference tables, the API tester, the footer): the browser skips its layout and paint until it nears the viewport, while the content stays in the HTML for search engines, screen readers and find-in-page. Never on content in the first screen or on anything sticky. A tall section sets its expected height with `[--deferred-size:…]` (40rem by default) so the scrollbar does not jump |
| `app-note` | A quiet grey note with an info icon, inside a card |
| `app-page-header` | Page header band (used by `<x-page-header>`) |
| `hero-canvas` | Flat navy band; text inside is white and focus is gold |
| `hero-photo`, `hero-scrim` | The photograph and scrim inside `<x-hero-backdrop>`; do not use them directly |

Inside `app-surface-raised`, a secondary pane (results, breakdowns) is `border-line bg-surface-subtle`, and nested lists are `rounded-control border border-line bg-surface`.

### Buttons

```blade
<button type="button" class="app-button-primary">…</button>        {{-- one per view --}}
<a href="…" class="app-button-secondary">…</a>
<button type="button" class="app-button-ghost">…</button>
<a href="…" class="app-button-inverse">…</a>                         {{-- white, on navy --}}
<a href="…" class="app-button-on-brand">…</a>                        {{-- outlined, on navy --}}
```

All buttons are 44px-tall rectangles with `rounded-control` and `pressable` feedback. Pass size tweaks as extra classes (`h-10 min-h-10 px-4`, `h-9 min-h-9 px-3 text-xs`). Icon-only buttons need `aria-label`. White text only ever sits on `bg-button`, never on `bg-action`, which turns light blue in dark mode.

### Segmented control

```blade
<div role="radiogroup" aria-label="…" class="app-segmented" data-value="{{ $mode }}" :data-value="mode">
    <span class="app-segmented-thumb" aria-hidden="true"></span>
    <button type="button" role="radio" :aria-checked="(mode === 'exclude').toString()" class="app-segment" @click="mode = 'exclude'">…</button>
    <button type="button" role="radio" :aria-checked="(mode === 'include').toString()" class="app-segment" @click="mode = 'include'">…</button>
</div>
```

Two equal segments on a bordered `surface-muted` track; the white thumb slides in 200ms when `data-value` is the second value (`include`). Render `data-value` on the server as well so the first paint is correct.

### Chips and badges

- `app-chip` for a selectable option (rates, filters, amounts) and `app-chip-active` for the selected one (action border plus inset ring), with `aria-pressed` or `aria-current`. Use `border-dashed` for "add custom".
- `app-badge` plus a tone pair for static labels: `bg-action-soft text-action-deep`, `bg-surface-muted text-ink-muted`, `bg-success-soft text-success`, `bg-warning-soft text-warning`, `bg-danger-soft text-danger`. Status badges also carry an icon or a word, never colour alone.

### Fields

`app-field` for inputs and buttons that look like inputs, `app-select` for native selects. The label sits above (`mb-1.5 text-[0.8125rem] font-semibold text-ink-muted`) and the error below (`mt-1.5 text-xs font-medium text-danger`), linked with `aria-describedby` and `aria-invalid`.

### Data

- `app-table` inside `app-surface overflow-hidden` for tabular data, with numbers right-aligned and `tabular`.
- Definition lists (`dl` > `div` > `dt` + `dd`) for label and value pairs; `dt` and `dd` must be direct children of `dl` or of a `div` inside it.
- Bars and meters use a `bg-action` fill on a `bg-action/15` track with `rounded-xs` ends.
- The VAT map uses the five-step ramp `--map-0` … `--map-4`, stepped for each theme and validated for colour-vision deficiency. Every region is also exposed as text and in a ranked table.

### Navigation and overlays

| Piece | Implementation |
|---|---|
| Global header | `components/global-header.blade.php`: navy bar; each item is `app-nav-link`, and `app-nav-link-active` (with `aria-current="page"`) draws the gold underline |
| Mobile menu | Items with a 2px left border, `border-gold bg-white/10` when current |
| Mobile tab bar | `components/bottom-navigation.blade.php`: full-width white bar; each item is `app-tab`, and `aria-current="page"` draws the action-blue top rule |
| Menus and popovers | `app-popover p-2` (or `p-1.5`), rows `rounded-control hover:bg-surface-muted`, closed by Escape and outside click |
| Command palette | `components/command-palette.blade.php`: `app-popover rounded-panel` over a `scrim`, loads `/search-index.json` lazily |
| Toasts | `$copy(text, message)` or `$store.toasts`; rendered as `app-popover` |
| Pagination | `resources/views/pagination/livewire.blade.php`; return `'pagination.livewire'` from `paginationView()` |
| Breadcrumbs | `<x-site-breadcrumbs :items="[label => url]" variant="dark|light" />` |

### Content helpers

| Class or component | Use |
|---|---|
| `app-link` | Standalone links (underlined, action blue) |
| `app-prose` | Long-form text from Markdown or translations (headings, lists, tables, quotes with a left rule) |
| `app-code` + `text-syntax-*` | Code samples; make scrollable `pre` blocks keyboard-reachable with `tabindex="0"` |
| `app-kbd` | Keyboard hints such as ⌘K |
| `app-flag`, `<x-ui.flag :iso="…" size="xs|sm|md|lg|xl" />` | Country flags (local SVGs; Greece accepts `el`) |
| `<x-ui.icon name="…" class="size-4" />` | Lucide icons at 1.75 stroke; add missing paths to `components/ui/icon.blade.php` in alphabetical order |
| `<x-ui.logo />` | The EU VAT Info mark: white tile, navy slash and dot, gold dot; `size-8` unless a `size-*` class is passed |
| `<x-ui.new-tab />` | Screen-reader note "(opens in a new tab)" in the page language; put it last inside every `target="_blank"` link (or append the words to its `aria-label`). Markdown output leaves it out |
| `<x-json-ld :data="[…]" />` | Structured data; it adds `@context` for you |
| `<x-rate-freshness :updated="…" :eu="…" />` | The date rates were updated and their source (the European Commission, or the open vat-rates dataset outside the EU); `on-dark` on navy, extra items in the slot |
| `<x-faq-list :faqs="…" :title="…" id="…" />` | Questions as native disclosures, the first one open; build the FAQPage JSON-LD from the same list |
| `<x-calculator.country-header :country="…" />` | The country calculator heading on one grid with the calculator below: the flag in the kicker from `sm` up, the heading (`heading-level`, default 1) and the rate panel; `breadcrumb-label` names its trail wherever it is not the page's own |
| `<x-calculator.country-reference :country="…" />` | Each rate with what it covers, the standard rate's start date (`since` replaces the lookup), the VAT number format and the formulas |
| `<x-calculator.common-amounts :country="…" />` | VAT on the top amounts in both directions; every figure links its indexable calculation |
| `tabular`, `pressable`, `on-brand`, `scrim`, `app-container` | Utilities described above |

## Patterns

**Results.** Lead with the answer in display type, then one sentence that restates it in words, then a breakdown `dl`, then actions (share, copy, open). Announce changes through an `aria-live="polite"` region.

**One main action.** A view has one `app-button-primary`; links to other tools beside it are `app-button-secondary` or ghost. On phones the full calculator repeats its total in a `bg-action-soft` line under the amount (`aria-hidden`, since the result region announces it), so the answer is on the first screen.

**Freshness.** Every rate shows when it was last updated and where it comes from, close to the figure it qualifies: put `<x-rate-freshness>` under the calculator, in the header of a rate table and under a result. Say "Rates updated 1 Oct 2026", never "always up to date".

**Forms.** Validate on the client for instant feedback and again on the server. Never auto-submit input that came from a URL unless it passed the strict pattern.

**States.** Every async control has a loading state (spinner plus `aria-busy` or disabled button), an empty state with a next step, and an error that says what happened and what to do. Outages are errors about the service, never about the visitor's input.

**Localisation.** Budget 35% extra width for translated labels. Never concatenate translated fragments; use placeholders (`:country`, `:rate`). Country names come from the database in English, so phrase sentences so they need no declension. Uppercase labels are set with CSS (`uppercase`), never typed in capitals, so each language keeps its own casing rules.

**Dark mode.** Test both themes for every change. The theme toggle cycles system, light and dark, and the choice is applied before first paint. Navy areas stay navy; only their depth changes.

**Embed widget.** `resources/views/layouts/embed.blade.php` has a transparent background. Keep embed markup free of header, footer and tab bar.

**Performance budget.** Application CSS stays under 20 KB gzip and application JS under 10 KB gzip. Long lists are rendered on the server and filtered by one Alpine effect, not built with `x-for`. Sections below the first screen carry `app-deferred`, and `CollapseHtmlWhitespace` strips Blade's indentation from every HTML response, so long reference content costs little to the first paint. No third-party hosts on public pages. Heavy libraries are loaded only on the page that needs them.

## Accessibility

- Contrast is WCAG 2.2 AA in light and dark for every text and state, including text on navy.
- Focus is always visible: a 2px outline with 2px offset in action blue on light surfaces and gold inside `on-brand` areas. Never remove it.
- Touch targets are at least 44×44px; tab bar items and chips included.
- Icon-only buttons have an `aria-label`; decorative icons are `aria-hidden`.
- Images have `alt` (empty for decoration).
- Every page has one `h1`, landmarks (`header`, `nav`, `main`, `footer`) and a skip link.
- `prefers-reduced-motion` is handled globally in `app.css`.

## Do and don't

| Do | Don't |
|---|---|
| `bg-surface`, `text-ink-muted`, `border-line` | `bg-white`, `text-gray-500`, `border-gray-200` |
| Let tokens switch the theme | `dark:bg-slate-800` |
| `app-surface`, `app-popover`, `app-sticky-bar` | `bg-white/70 backdrop-blur-xl` |
| Flat `bg-brand` or `hero-canvas` | `bg-linear-to-r from-blue-600 to-violet-600` |
| `shadow-card`, `shadow-workflow`, `shadow-floating` | `shadow-lg`, `shadow-xl`, coloured glows |
| `rounded-control`, `rounded-card`, `rounded-panel` | `rounded-xl`, `rounded-[13px]`, pill buttons |
| `app-badge bg-success-soft text-success` | `rounded-full px-3 bg-green-100` |
| Gold as a thin rule or square on navy | Gold text, gold buttons, gold on white |
| `bg-button text-white` | `bg-action text-white` |
| `<x-ui.icon name="copy" />` | A new inline `<svg>` |
| One `app-button-primary` per view | Several competing primary buttons |
| Status colour plus icon plus text | Colour alone to show state |

## Recipes

**Add a page.** Choose a skeleton, build it from the components above, add strings to all 24 locales, then run the design tests and axe (see Enforcement).

**Add a component.** Search this file and `resources/css/app.css` first. If nothing fits, add one `app-*` class under `@layer components`, built only from tokens, and document it in this file in the same commit. The documentation test fails until you do. Then add a live example to `resources/views/styleguide/examples` and a specimen to `/styleguide`; the example file is both the preview and the code shown beside it.

**Need a colour that does not exist.** Add a semantic `--ui-*` token with a light and a dark value in `app.css`, expose it in `@theme inline`, check AA contrast in both themes, and add a row to the colour table.

**Need an icon that does not exist.** Copy the Lucide path into `components/ui/icon.blade.php` in alphabetical order; never paste an inline `<svg>` into a template.

## Storybook

Every example in `resources/views/styleguide/examples` is also a Storybook story. `App\Support\DesignSystem\ComponentCatalog` describes each example once (section, title, description, canvas), and both `/styleguide` and `php artisan design-system:storybook` read it. The command renders each example through Blade into `storybook/generated/examples.json`, copies the Design Tokens to `storybook/generated/design.tokens.json` and writes one story file per section in `storybook/stories`. Never edit the generated files; change the example or the catalog and run the command again.

`yarn storybook` serves it at http://localhost:6006. `yarn build`, which every deploy runs, also builds it into `public/storybook`, so it is published at [/storybook](/storybook/) with `noindex` (a failed Storybook build never stops the site build); `yarn build-storybook` writes a standalone copy to `storybook-static`. Storybook loads `resources/css/app.css` and the Alpine components in `resources/js/app.js` with the Alpine version Livewire bundles, switches light and dark with the `.dark` class and runs axe-core on every story. To add an example, create the Blade file, describe it in the catalog, show it on `/styleguide` with `<x-styleguide.specimen example="…" />` and regenerate. An example renders without the database: use `ComponentCatalog::sampleCountry()` for country data.

## Enforcement

`tests/Feature/DesignSystemTest.php` runs with `php artisan test` and fails when a template (outside `resources/views/vendor` and `resources/views/amp`):

1. uses a raw Tailwind palette colour;
2. uses a `dark:` variant;
3. writes a literal colour in a class or `style` attribute;
4. uses `backdrop-*` blur utilities;
5. uses a gradient utility (`bg-linear-*`, `bg-radial-*`, `bg-conic-*`, `bg-gradient-*`);
6. uses a default Tailwind shadow;
7. uses a radius outside the scale (`rounded-md` … `rounded-3xl` or an arbitrary `rounded-[…]`);
8. makes a pill (`rounded-full` together with horizontal padding);
9. writes an unescaped `@context` outside a PHP block;
10. has an `<img>` without `alt`;
11. adds inline `<svg>` outside the icon and logo components;
12. or when a token, utility or component class in `app.css` is missing from this file.

`tests/Feature/ComponentCatalogTest.php` fails when an example file has no catalog entry, and `tests/Feature/StorybookExportTest.php` fails while the generated Storybook files are out of date.

Also run an automated accessibility check on the pages you changed. The project was verified with axe-core (WCAG 2.2 A/AA) in both themes and with a horizontal-overflow check at 320px and 390px.

## File map

| Path | Contents |
|---|---|
| `resources/css/app.css` | Tokens, utilities, component classes |
| `resources/js/app.js` | Alpine stores and components (calculator, palette, validator, toasts, theme) |
| `resources/js/vat.js` | Client VAT maths; mirrors `App\Support\Vat` |
| `resources/views/components` | Shared Blade components (`ui/icon`, `ui/flag`, `ui/logo`, `page-header`, `hero-backdrop`, `global-header`, `bottom-navigation`, `command-palette`, `toasts`, `footer`) |
| `resources/views/pagination/livewire.blade.php` | Pagination for Livewire components |
| `resources/views/layouts/app.blade.php` | Page shell, theme bootstrap, skip link |
| `resources/views/layouts/embed.blade.php` | Transparent shell for the iframe widget |
| `resources/views/livewire/styleguide.blade.php` | The `/styleguide` reference page |
| `resources/views/livewire/brand-guidelines.blade.php` | The `/brand` page: the mark (also served as SVG at `/brand/eu-vat-info-mark.svg`), colours from the tokens, type, voice and how to credit the data |
| `resources/views/styleguide/examples` | Live component and pattern examples shown on `/styleguide` |
| `app/Support/DesignSystem` | Reads the tokens from `app.css` and this file, and exports them as Design Tokens JSON; `ComponentCatalog` describes the examples and `StorybookExport` builds the stories |
| `.storybook` | Storybook configuration: framework, add-ons, the brand theme and the preview that loads the site's CSS and Alpine |
| `storybook` | Generated stories and data (`stories`, `generated`), the render helper, the foundation stories and the introduction |
| `tests/Feature/DesignSystemTest.php` | Design rule checks |
| `tests/Feature/StyleguideTest.php` | Checks that `/styleguide` and its token and Markdown exports match the sources |
| `public/v1` | Frozen archive of the previous design. Out of scope for these rules; never restyle it (`tests/Feature/SiteArchiveTest.php` guards it) |
| `public/v2` | Frozen archive of the 5.0 site. Out of scope for these rules; never restyle it (`tests/Feature/SiteArchiveV2Test.php` guards it) |
