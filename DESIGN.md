---
name: EU VAT Info
description: An exact, technical EU VAT reference. Cool paper, blue-grey ink and hairlines, one cobalt signal, keycap controls, cut-corner panels and figures set to be compared.
version: 6.0
source: resources/css/app.css
enforced-by: tests/Feature/DesignSystemTest.php
theme: Cobalt (Hallmark v2, deal 1/3) · genre modern-minimal · shape app screen
colors:
  workspace: "#F8FBFE"
  surface: "#FDFEFF"
  surface-subtle: "#F3F6FA"
  line: "#DADEE4"
  ink: "#111825"
  ink-muted: "#3C434E"
  ink-quiet: "#5D646E"
  brand: "#121823"
  action: "#0462D3"
  action-deep: "#0040BB"
  action-soft: "#E3F0FF"
  success: "#007748"
  warning: "#AE4900"
  danger: "#BE2323"
typography:
  family: "Familjen Grotesk Variable for headings and figures, IBM Plex Sans Variable for text, JetBrains Mono Variable for machine values; each with a metric-matched local fallback"
  display: { size: "3rem", weight: 600, lineHeight: 1.04, letterSpacing: "-0.03em", family: "display" }
  headline: { size: "2.5rem", weight: 600, lineHeight: 1.08, letterSpacing: "-0.03em", family: "display" }
  title: { size: "1.125rem", weight: 600, lineHeight: 1.35, family: "display" }
  body: { size: "1rem", weight: 400, lineHeight: 1.6 }
  label: { size: "0.8125rem", weight: 500, lineHeight: 1.35, family: "display" }
  mono: { size: "0.875rem", weight: 500, lineHeight: 1.4, family: "mono" }
rounded:
  control: "6px"
  card: "8px"
  panel: "8px, cut 16px on two opposite corners"
motion:
  out-quint: "cubic-bezier(0.22, 1, 0.36, 1)"
  duration: "120ms (keys and colour), 200ms (position)"
---

# EU VAT Info design system

This file is the single source of truth for the interface. Read it before you change a Blade view, `resources/css/app.css` or an Alpine component. Rules written as **must** are checked by `tests/Feature/DesignSystemTest.php`, so a violation fails the build with the file, the line and the fix.

The rendered reference lives at [`/styleguide`](https://vat.businesspress.io/styleguide): every token, component and pattern drawn with the production stylesheet, with its code. Two machine-readable companions are generated from the same sources: `/styleguide/design.tokens.json` (Design Tokens Community Group format 2025.10, built from `app.css` and the frontmatter above) and `/styleguide.md` (this file).

**North star: the exact reference desk.** People come here to get a rate, a total or a validation result they can put on an invoice. The interface reads like a well-kept technical reference: cool near-white paper ruled with hairlines, Familjen Grotesk heads over IBM Plex Sans, keys you press, panels cut on two corners, and one electric cobalt signal for what you can act on. Figures are the loudest type on any screen. Nothing glows, blurs or bounces.

The system is the Hallmark v2 **Cobalt** theme applied to an app-screen shape; `app.css` opens with its stamp.

## Quick start for agents

1. **Use tokens only.** Style with semantic utilities (`bg-surface`, `text-ink-muted`, `border-line`, `bg-action-soft`, `text-success`) and the `app-*` classes below. You must not use raw Tailwind palette colours (`bg-blue-600`), `dark:` variants, hex/rgb/oklch literals, default Tailwind shadows, blur, or gradient utilities in templates.
2. **Paper first.** Pages sit on `workspace` paper. Tool heroes are `hero-field` (paper with the rate field); `hero-canvas` is the graphite band for code and calls to action, at most one per page.
3. **Pick the page skeleton.** A tool page opens with `hero-field` + `<x-hero-backdrop />`, a left-aligned heading and one `app-surface-raised` card. A reference page opens with `<x-page-header>` followed by `app-surface` cards inside `app-container`.
4. **One primary key per view.** `app-button-primary` for it, `app-button-secondary` or `app-button-ghost` for the rest; `app-button-inverse` / `app-button-on-brand` only inside a graphite `hero-canvas` block.
5. **Labels are plain.** No eyebrows over headings, no tracked capitals, no small caps, no `uppercase` on labels, table heads or navigation. Labels are the display face at 0.8125rem, weight 500, sentence case, `text-ink-muted`.
6. **Text and numbers.** Put every string in `lang/en/ui.php` and all 24 locales. Money goes through `App\Support\Vat\Money::format`, and every column or total of numbers uses `tabular`.
7. **Before you commit**, run `yarn build`, `php artisan test` and `vendor/bin/pint --test`. The design tests run with the suite.

## Principles

1. **Exact, not shiny.** Surfaces are opaque paper. Depth is a hairline, a paper step and, on keys only, a real bottom edge. No glass, blur, glow or decorative gradients.
2. **Ink is identity, cobalt is action.** Blue-grey ink carries headings and figures; cobalt (`action`, `button`) marks what you can press, what is selected and keyboard focus, and stays under about 5% of a screen outside the rate field. Status colours mean status and nothing else.
3. **Numbers are the hero.** Results use the largest type on the view (Familjen Grotesk 600, tabular), a plain-language summary line and, under the calculator total, the fifty-cell meter.
4. **Keys and cuts.** Buttons are keycaps that lift 1px on hover and sink on press. Panels, chips and fields are cut on two opposite corners where the browser supports `corner-shape`, and fall back to a plain 6–8px radius.
5. **Honest states.** Loading, empty, error and "service unavailable" states are designed, worded and announced; nothing fails silently.
6. **Accessible by default.** WCAG 2.2 AA in both themes, a visible cobalt focus ring on every surface, field edges at 3:1 or better, 44px touch targets and reduced-motion support.
7. **Quiet motion.** Keys travel in 120ms; menus fade in with a 4px drop; the rate field drifts at 30 fps, pauses off screen and is one still under reduced motion.

## Tokens

All tokens live in `resources/css/app.css`. Semantic colours are CSS variables (`--ui-*`) exposed to Tailwind through `@theme inline`, so `--ui-surface` becomes `bg-surface`, `text-surface` and `border-surface`. `data-theme="dark"` on `<html>` swaps every value, which is why templates never need `dark:`. `data-theme="light"` restores the light values inside a dark subtree; `/styleguide` uses the pair to show both themes side by side.

### Color roles

| Token | Utility examples | Light | Dark | Use |
|---|---|---|---|---|
| `workspace` | `bg-workspace` | `oklch(0.986 0.005 250)` | `oklch(0.165 0.02 262)` | Paper: page canvas, header, heroes |
| `surface` | `bg-surface` | `oklch(0.997 0.002 250)` | `oklch(0.205 0.024 262)` | Cards, fields, tables, menus |
| `surface-subtle` | `bg-surface-subtle` | `oklch(0.972 0.006 252)` | `oklch(0.225 0.025 262)` | Table heads, result panes, the footer |
| `surface-muted` | `bg-surface-muted` | `oklch(0.945 0.01 252)` | `oklch(0.26 0.026 262)` | Sunken trays, hover fills, placeholders |
| `line` | `border-line`, `divide-line` | `oklch(0.9 0.009 252)` | `oklch(0.3 0.026 262)` | Hairlines between rows and around cards |
| `line-strong` | `border-line-strong`, `ring-line-strong` | `oklch(0.83 0.012 252)` | `oklch(0.4 0.03 260)` | Key outlines, popover edges |
| `ink` | `text-ink` | `oklch(0.21 0.028 262)` | `oklch(0.95 0.008 250)` | Headings, values, primary text |
| `ink-muted` | `text-ink-muted` | `oklch(0.38 0.022 260)` | `oklch(0.8 0.016 254)` | Supporting text and labels (AA on every surface) |
| `ink-quiet` | `text-ink-quiet`, `border-ink-quiet` | `oklch(0.5 0.018 258)` | `oklch(0.69 0.022 256)` | Placeholders, meta, field edges (3:1 or better) |
| `brand` | `bg-brand` | `oklch(0.21 0.024 262)` | `oklch(0.135 0.018 262)` | Graphite: the `hero-canvas` band for code and calls to action |
| `brand-deep` | `bg-brand-deep`, `text-brand-deep` | `oklch(0.185 0.022 262)` | `oklch(0.12 0.016 262)` | Text on white keys inside graphite |
| `brand-soft` | `bg-brand-soft` | `oklch(0.95 0.028 256)` | `oklch(0.27 0.05 260)` | Cobalt wash panels on paper |
| `action` | `text-action`, `border-action`, `bg-action` | `oklch(0.52 0.19 258)` | `oklch(0.76 0.13 254)` | Links, selection, focus, data bars (never behind white text) |
| `action-deep` | `text-action-deep` | `oklch(0.43 0.2 262)` | `oklch(0.85 0.08 252)` | Text on `action-soft`, hovered links |
| `action-soft` | `bg-action-soft` | `oklch(0.95 0.028 256)` | `oklch(0.29 0.06 260)` | Pressed chips, active rows, the one highlighted table row |
| `button` | `bg-button` | `oklch(0.52 0.19 258)` | `oklch(0.52 0.19 258)` | The primary key face, which carries white text (stays saturated in dark) |
| `button-hover` | `hover:bg-button-hover` | `oklch(0.47 0.2 260)` | `oklch(0.57 0.18 258)` | Hover for `button` faces |
| `button-edge` | `border-button-edge` | `oklch(0.39 0.16 260)` | `oklch(0.36 0.15 260)` | The primary key's border and bottom edge |
| `key-face` | `bg-key-face` | `oklch(0.997 0.002 250)` | `oklch(0.255 0.026 262)` | Secondary keys and chips |
| `key-edge` | used by `shadow-key` | `oklch(0.8 0.016 254)` | `oklch(0.12 0.016 262)` | The bottom edge of secondary keys and chips |
| `success` / `success-soft` | `text-success`, `bg-success-soft` | `oklch(0.5 0.12 158)` / `oklch(0.962 0.035 158)` | `oklch(0.8 0.14 158)` / `oklch(0.29 0.05 162)` | Verified, valid, decrease in rate |
| `warning` / `warning-soft` | `text-warning`, `bg-warning-soft` | `oklch(0.53 0.15 48)` / `oklch(0.968 0.04 78)` | `oklch(0.83 0.13 75)` / `oklch(0.3 0.055 60)` | Needs attention, scheduled |
| `danger` / `danger-soft` | `text-danger`, `bg-danger-soft` | `oklch(0.52 0.19 27)` / `oklch(0.965 0.02 20)` | `oklch(0.76 0.14 25)` / `oklch(0.3 0.07 25)` | Invalid, error, increase in rate |
| `glyph-1` … `glyph-4` | read by `rateField` | cobalt tints from `0.89` to `0.6` lightness | graphite tints | The rate field, lightest to densest; `action-deep` for the densest cells |
| `graphite-glyph-1` … `graphite-glyph-4` | set on `hero-canvas` | graphite tints | graphite tints | The rate field when it sits on a graphite band |
| `focus` | set by surfaces | action | action | Colour of the focus outline on every surface |

Fixed colours (identical in both themes):

| Token | Utility | Value | Use |
|---|---|---|---|
| `code`, `code-raised`, `code-muted` | `bg-code` | graphite `oklch(0.21 0.024 262)`, `0.265`, `0.34` | Code blocks, which stay graphite in both themes |
| `--color-syntax-keyword` | `text-syntax-keyword` | `oklch(0.76 0.13 254)` | Keywords, variables, spinner on code (cobalt) |
| `--color-syntax-function` | `text-syntax-function` | `oklch(0.93 0.01 250)` | Function and property names |
| `--color-syntax-string` | `text-syntax-string` | `oklch(0.84 0.07 236)` | Strings, JSON responses |
| `--color-syntax-literal` | `text-syntax-literal` | `oklch(0.82 0.09 266)` | Request bodies, literals |
| `--color-syntax-comment` | `text-syntax-comment` | `oklch(0.69 0.022 256)` | Comments, meta, placeholders on code |
| `--color-syntax-ok` | `text-syntax-ok` | `oklch(0.8 0.13 158)` | HTTP methods, 2xx status |
| `--color-syntax-error` | `text-syntax-error` | `oklch(0.76 0.14 25)` | Errors, 4xx/5xx status |

Syntax stays in the cobalt family; only status codes take green and red. `white` utilities are allowed inside a graphite `hero-canvas` and on code (`text-white/80`, `border-white/15`, `bg-white/10`). Anywhere else, use a token.

### Brand surfaces

| Piece | Recipe |
|---|---|
| Header | `sticky top-0 z-40 border-b border-line bg-workspace text-ink`: paper with a hairline |
| Tool hero | `hero-field` with `<x-hero-backdrop />`: paper, a bottom hairline and the rate field |
| Graphite band | `hero-canvas` (flat `brand`, white text allowed): code samples and one call to action per page |
| Footer | `border-t border-line bg-surface-subtle text-ink`: a paper mast, the wordmark beside four link columns (VAT tools, Compare countries, Resources, Developers); partner links sit in the bottom bar with the copyright |
| Tile, stat group or field on graphite | `app-brand-panel` (6% white fill, 15% white hairline) |

`<x-hero-backdrop />` renders `hero-ground`: an ASCII field on an 8 × 16 cell drawn by the `rateField` Alpine component from the 27 standard rates. Raw, drifting cells on the left resolve into one column per member state on the right, each as tall as its rate, so the ground is the product's own data. It is tinted `glyph-1` to `glyph-4` with `action-deep` for the densest cells, follows the pointer with a small lens, pauses off screen and is one still under reduced motion; until the script runs a dot on every cell stands in. No type sits on its densest cells: keep headings on the left half. It is the only image treatment; do not add photographs, patterns or gradients.

### Typography

Three self-hosted variable faces, each with a local fallback sized to its metrics (`Familjen Fallback`, `Plex Fallback`) so the swap does not reflow text. Familjen Grotesk covers Latin; Greek and Cyrillic headings fall through to IBM Plex Sans, which covers both.

| Role | Classes | Where |
|---|---|---|
| Display | `text-4xl sm:text-5xl font-semibold tracking-[-0.03em] sm:leading-[1.04]` | Hero headings, hero results (`font-display` on a figure) |
| Headline | `text-3xl sm:text-[2.5rem] font-semibold tracking-[-0.03em]` | `<x-page-header>` titles |
| Section | `text-xl font-semibold text-ink` | Section headings inside a page |
| Title | `text-lg font-semibold text-ink` or `font-display text-base font-medium` | Card titles |
| Body | `text-base leading-7 text-ink-muted` | Paragraphs, capped at 68–72ch |
| Small | `text-sm text-ink-muted` | Helper text, table cells |
| Label | `font-display text-[0.8125rem] font-medium text-ink-muted` | Field labels, data labels, table heads (sentence case, untracked) |
| Context label | `app-eyebrow` | A short label that is not above a heading: a group name, a source line |
| Kicker | `app-kicker` | The country flag line in a country heading; never above a heading on its own |
| Mono | `font-mono` | VAT numbers, API paths, code, the meter and the rate field; never a label or heading |

Headings use the display face at weight 600 (`font-bold` and `font-semibold` both resolve to 600, so three weights are in use: 400, 500, 600). Headings are always one solid colour: never highlight words in a headline. Use `tabular` for every figure that can change or be compared.

Write interface text in sentence case: headings, buttons, labels, menus and table heads capitalise only the first word and proper nouns (EU, VAT, VIES, country names, EU VAT Info). Never join meta with middle dots; put each fact in its own element. Rates drop trailing zeros (`19%`, `5.5%`) through `Country::formatRate` or `Money::percent`, and dates read day first (`1 Aug 2025`, `1 August 2025`).

### Shape

| Token | Utility | Size | Use |
|---|---|---|---|
| `--radius-control` | `rounded-control` | 6px | Keys, fields, chips, segmented tray, menu rows, icon wells, notes |
| `--radius-card` | `rounded-card` | 8px | `app-surface` cards, popovers, menus, code blocks, graphite panels |
| `--radius-panel` | `rounded-panel` | 8px | The primary task card (`app-surface-raised`, also `cut`), the command palette, graphite CTA blocks |
| extra small | `rounded-xs`, `rounded-sm` | 2px, 4px | Flags, badges, keyboard hints, inline code, bar ends |
| circle | `rounded-full` | — | Spinners, avatars, numbered step markers only (no decorative status dots) |
| `cut` | `cut` | 16px bevel | Two opposite corners bevelled on a panel where `corner-shape` is supported |
| `cut-sm` | `cut-sm` | 8px bevel | The same on fields and chips |

Tailwind's `rounded-md` … `rounded-3xl` and arbitrary radii must not appear in templates.

### Elevation

| Token | Utility | Use |
|---|---|---|
| `--shadow-card` | `shadow-card` | Resting cards (built into `app-surface`) |
| `--shadow-workflow` | `shadow-workflow` | The primary task card (built into `app-surface-raised`) |
| `--shadow-floating` | `shadow-floating` | Menus, popovers, the palette, toasts (built into `app-popover`) |
| `--shadow-key` | `shadow-key` | The 2px bottom edge of secondary keys and chips |
| `--shadow-key-accent` | `shadow-key-accent` | The bottom edge of the primary key |
| `--shadow-key-press` | `shadow-key-press` | A key pressed in: the chosen chip, the segmented tray, `:active` |

You must not use Tailwind's default `shadow-sm`…`shadow-2xl`.

### Motion

| Token | Utility | Use |
|---|---|---|
| `--ease-out-quint` | `ease-out-quint` | Every transition |
| `pressable` | `pressable` | Key feedback: colour, border, shadow and a 1–2px vertical travel (120ms); never scale |
| `--animate-fade-in`, `--animate-pop-in` | `animate-fade-in`, `animate-pop-in` | Content that appears (toasts use `animate-pop-in`, a 4px rise) |

Enter popovers with `x-transition:enter="transition duration-150 ease-out-quint" x-transition:enter-start="-translate-y-1 opacity-0"`. `prefers-reduced-motion` collapses all durations, stills the rate field and disables view transitions globally, so components need no extra code.

### Layout

- `app-container` is the only page width: max 80rem, with 16/24/32px gutters at mobile, `sm` and `lg`.
- Section rhythm: `py-8 sm:py-10` between content sections, `pb-10 sm:pb-14` at the bottom of heroes.
- Headings and their sentences are left-aligned; centre only a short empty state.
- Grids start from one column (`grid grid-cols-1 md:grid-cols-2`). A grid without a base `grid-cols-*` can overflow on phones; image-bearing tracks use `minmax(0,1fr)`.
- Wrap wide tables in `relative overflow-x-auto` with `tabindex="0" role="group"` and a name (`aria-labelledby` the section heading), so keyboard users can scroll them. No page may scroll horizontally at 320px; `html` and `body` carry `overflow-x: clip`.
- `mobile-nav-safe` on the footer reserves the height of the mobile tab bar on phones, so the end of every page scrolls clear of it.
- A card that holds a sticky bar uses `overflow-clip`, not `overflow-hidden`, so the bar can stick to the viewport.
- The header shows the full navigation from `xl` (1280px) and the menu button below it, so the longest languages fit. Its right-hand group is a container (`@container`): the search key and the GitHub link appear only when there is room for them (`@min-[27rem]`, `@min-[30rem]`), and the site name truncates before anything overflows. Check a header change in Bulgarian, Estonian and Maltese at 1024, 1280 and 1536px.

## Components

Copy these recipes; do not rebuild them from utilities.

### Page skeletons

Tool page (calculator, validator, shared result):

```blade
<section class="hero-field">
    <x-hero-backdrop />
    <div class="app-container relative pb-10 pt-6 sm:pb-14 sm:pt-8">
        <x-site-breadcrumbs :items="[__('ui.nav.vat_calculator') => '']" />
        <h1 class="mt-6 max-w-2xl text-4xl font-semibold tracking-[-0.03em] text-ink sm:text-5xl">…</h1>
        <p class="mt-3 max-w-[54ch] text-base leading-7 text-ink-muted">…</p>
        <div class="app-surface-raised mt-8 max-w-4xl overflow-hidden text-ink">…</div>
    </div>
</section>
<div class="app-container space-y-8 py-8 sm:py-10">
    <section class="app-surface p-5 sm:p-6">…</section>
</div>
```

`hero-canvas` is the graphite band, for example a call to action with `hero-canvas rounded-panel p-8`, or a code sample. Text inside it may use `text-white` and `white/` utilities.

Reference page (tables, lists, guides):

```blade
<x-page-header :title="__('ui.history.title')" :description="__('ui.history.subtitle')" :breadcrumbs="[__('ui.history.nav') => '']">
    <x-slot:actions>…</x-slot:actions>
</x-page-header>
<div class="app-container space-y-8 py-8 sm:py-10">…</div>
```

`<x-page-header>` renders `app-page-header`: a paper band with a bottom hairline. Its `eyebrow` prop is ignored; the breadcrumbs carry the context.

### Surfaces

| Class | Use |
|---|---|
| `app-workspace` | Body canvas (set on `<body>`) |
| `app-surface` | Default card: `rounded-card`, hairline border, `shadow-card`. Pad with `p-5 sm:p-6` |
| `app-surface-raised` | The one primary task card of a view (calculator, validator, shared result): `cut`, `rounded-panel`, `shadow-workflow` |
| `app-popover` | Menus, the country combobox, the palette panel and toasts: `rounded-card`, strong border, `shadow-floating` |
| `app-brand-panel` | Tiles, stat groups, badges and fields inside a graphite band |
| `app-sticky-bar` | A sticky sub-header inside a card, such as the year rows in the rate history |
| `app-deferred` | A section that starts below the first screen (questions, reference tables, the API tester, the footer): the browser skips its layout and paint until it nears the viewport, while the content stays in the HTML for search engines, screen readers and find-in-page. Never on content in the first screen or on anything sticky. A tall section sets its expected height with `[--deferred-size:…]` (40rem by default) so the scrollbar does not jump |
| `app-note` | A quiet note with an info icon, inside a card: hairline, `surface-subtle` |
| `app-page-header` | Page header band (used by `<x-page-header>`) |
| `hero-field` | The tool hero: paper, bottom hairline, room for the rate field |
| `hero-ground` | The rate field layer inside `<x-hero-backdrop>`; do not use it directly |
| `hero-canvas` | Graphite band; text inside is white |

Inside `app-surface-raised`, a secondary pane (results, breakdowns) is `border-line bg-surface-subtle`, and nested lists are `rounded-control border border-line bg-surface`. Never put a card inside a card.

### Buttons

```blade
<button type="button" class="app-button-primary">…</button>        {{-- one per view: the cobalt key --}}
<a href="…" class="app-button-secondary">…</a>                       {{-- a paper key --}}
<button type="button" class="app-button-ghost">…</button>
<a href="…" class="app-button-inverse">…</a>                         {{-- white key, inside graphite --}}
<a href="…" class="app-button-on-brand">…</a>                        {{-- outlined, inside graphite --}}
```

All buttons are 44px-tall keys with `rounded-control`, the display face at 500 and `pressable` travel: up 1px on hover, down 2px on press. Pass size tweaks as extra classes (`h-10 min-h-10 px-4`, `h-9 min-h-9 px-3 text-xs`). Icon-only buttons need `aria-label`. White text only ever sits on `bg-button`, never on `bg-action`, which turns light blue in dark mode.

### Segmented control

```blade
<div role="radiogroup" aria-label="…" class="app-segmented" data-value="{{ $mode }}" :data-value="mode">
    <span class="app-segmented-thumb" aria-hidden="true"></span>
    <button type="button" role="radio" :aria-checked="(mode === 'exclude').toString()" class="app-segment" @click="mode = 'exclude'">…</button>
    <button type="button" role="radio" :aria-checked="(mode === 'include').toString()" class="app-segment" @click="mode = 'include'">…</button>
</div>
```

Two equal segments on a sunken `surface-muted` tray; the key-face thumb slides in 200ms when `data-value` is the second value (`include`). Render `data-value` on the server as well so the first paint is correct.

### Chips and badges

- `app-chip` for a selectable option (rates, filters, amounts): a small key. `app-chip-active` presses it in (action border, `action-soft` face, `shadow-key-press`), with `aria-pressed` or `aria-current`. Use `border-dashed` for "add custom".
- `app-badge` plus a tone pair for static labels: `bg-action-soft text-action-deep`, `bg-surface-muted text-ink-muted`, `bg-success-soft text-success`, `bg-warning-soft text-warning`, `bg-danger-soft text-danger`. Status badges also carry an icon or a word, never colour alone, and never a decorative dot.

### Meter

`app-meter` wraps `app-meter-cells`: fifty JetBrains Mono cells under the calculator total, `░` for the net share in `text-ink-quiet` and `█` for the VAT share in `text-action`, sized to the pane with a container query. It is `aria-hidden`; the figures beside it say the same thing in words.

### Fields

`app-field` for inputs and buttons that look like inputs, `app-select` for native selects. Fields are cut on two corners and edged in `ink-quiet`, so the edge holds 3:1 against paper. The label sits above (`mb-1.5 font-display text-[0.8125rem] font-medium text-ink-muted`) and the error below (`mt-1.5 text-xs font-medium text-danger`), linked with `aria-describedby` and `aria-invalid`.

### Data

- `app-table` inside `app-surface overflow-hidden` for tabular data, with numbers right-aligned and `tabular`. Column heads are the label style: sentence case, untracked, never `uppercase`. A table may put one row on the `action-soft` wash (the visitor's country).
- Definition lists (`dl` > `div` > `dt` + `dd`) for label and value pairs; `dt` and `dd` must be direct children of `dl` or of a `div` inside it.
- Bars use a `bg-action` fill on a `bg-action/15` track with `rounded-xs` ends; the calculator total uses the fifty-cell `app-meter`.
- The VAT map uses the five-step ramp `--map-0` … `--map-4`, stepped for each theme and validated for colour-vision deficiency. Every region is also exposed as text and in a ranked table.

### Navigation and overlays

| Piece | Implementation |
|---|---|
| Global header | `components/global-header.blade.php`: paper bar with a hairline; each item is `app-nav-link`, and `app-nav-link-active` (with `aria-current="page"`) draws a 2px cobalt underline; search is a field-shaped key with `app-kbd` |
| Mobile menu | Items with a 2px left border, `border-action bg-action-soft text-action-deep` when current |
| Mobile tab bar | `components/bottom-navigation.blade.php`: full-width surface bar; each item is `app-tab`, and `aria-current="page"` draws the action-blue top rule |
| Menus and popovers | `app-popover p-2` (or `p-1.5`), rows `rounded-control hover:bg-surface-muted`, closed by Escape and outside click |
| Command palette | `components/command-palette.blade.php`: `app-popover rounded-panel` over a `scrim`, loads `/search-index.json` lazily |
| Toasts | `$copy(text, message)` or `$store.toasts`; rendered as `app-popover` |
| Pagination | `resources/views/pagination/livewire.blade.php`; return `'pagination.livewire'` from `paginationView()` |
| Breadcrumbs | `<x-site-breadcrumbs :items="[label => url]" />`; `variant="dark"` only inside a graphite band |

### Content helpers

| Class or component | Use |
|---|---|
| `app-link` | Standalone links (underlined, action blue) |
| `app-prose` | Long-form text from Markdown or translations (headings, lists, tables, quotes with a left rule) |
| `app-code` + `text-syntax-*` | Code samples; make scrollable `pre` blocks keyboard-reachable with `tabindex="0"` |
| `app-kbd` | Keyboard hints such as ⌘K |
| `app-flag`, `<x-ui.flag :iso="…" size="xs|sm|md|lg|xl" />` | Country flags (local SVGs; Greece accepts `el`) |
| `<x-ui.icon name="…" class="size-4" />` | Lucide icons at 1.75 stroke; add missing paths to `components/ui/icon.blade.php` in alphabetical order |
| `<x-ui.logo />` | The EU VAT Info mark: an ink tile cut on two corners, a paper slash and dot, a cobalt dot; it inverts with the theme; `size-8` unless a `size-*` class is passed |
| `<x-ui.new-tab />` | Screen-reader note "(opens in a new tab)" in the page language; put it last inside every `target="_blank"` link (or append the words to its `aria-label`). Markdown output leaves it out |
| `<x-json-ld :data="[…]" />` | Structured data; it adds `@context` for you |
| `<x-rate-freshness :updated="…" :eu="…" />` | The date rates were updated and their source (the European Commission, or the open vat-rates dataset outside the EU); `on-dark` inside a graphite band, extra items in the slot |
| `<x-faq-list :faqs="…" :title="…" id="…" />` | Questions as native disclosures, the first one open; build the FAQPage JSON-LD from the same list |
| `<x-calculator.country-header :country="…" />` | The country calculator heading on one grid with the calculator below: the flag beside the heading, the heading (`heading-level`, default 1) and the rate panel; `breadcrumb-label` names its trail wherever it is not the page's own |
| `<x-calculator.country-reference :country="…" />` | Each rate with what it covers, the standard rate's start date (`since` replaces the lookup), the VAT number format and the formulas |
| `<x-compare-picker :selected="…" :invalid="…" :error="…" />` | Choose two or three countries to compare: three native selects (the third optional) in a plain GET form to `/compare`, which redirects to the one canonical comparison URL, so it works without script. Errors mark only the fields at fault (`aria-invalid`, `aria-describedby`, focus on the first). It reads the database, so it has no Storybook example |
| `<x-calculator.common-amounts :country="…" />` | VAT on the top amounts in both directions; every figure links its indexable calculation |
| `tabular`, `pressable`, `on-brand`, `scrim`, `app-container` | Utilities described above |

## Patterns

**Results.** Lead with the answer in display type, then one sentence that restates it in words, then a breakdown `dl`, then actions (share, copy, open). Announce changes through an `aria-live="polite"` region.

**One main action.** A view has one `app-button-primary`; links to other tools beside it are `app-button-secondary` or ghost. On phones the full calculator repeats its total in a `bg-action-soft` line under the amount (`aria-hidden`, since the result region announces it), so the answer is on the first screen.

**Freshness.** Every rate shows when it was last updated and where it comes from, close to the figure it qualifies: put `<x-rate-freshness>` under the calculator, in the header of a rate table and under a result. Say "Rates updated 1 Oct 2026", never "always up to date".

**Forms.** Validate on the client for instant feedback and again on the server. Never auto-submit input that came from a URL unless it passed the strict pattern.

**States.** Every async control has a loading state (spinner plus `aria-busy` or disabled button), an empty state with a next step, and an error that says what happened and what to do. Outages are errors about the service, never about the visitor's input.

**Localisation.** Budget 35% extra width for translated labels. Never concatenate translated fragments; use placeholders (`:country`, `:rate`). Country names come from the database in English, so phrase sentences so they need no declension. Labels are never set in capitals; abbreviations (EU, VAT, ISO codes) keep their own casing.

**Dark mode.** Test both themes for every change. The theme toggle cycles system, light and dark, and the choice is applied before first paint. Graphite bands stay graphite; only their depth changes. The rate field reads its tints again when the theme changes.

**Embed widget.** `resources/views/layouts/embed.blade.php` has a transparent background. Keep embed markup free of header, footer and tab bar.

**Performance budget.** Application CSS stays under 20 KB gzip and application JS under 10 KB gzip. Long lists are rendered on the server and filtered by one Alpine effect, not built with `x-for`. Sections below the first screen carry `app-deferred`, and `CollapseHtmlWhitespace` strips Blade's indentation from every HTML response, so long reference content costs little to the first paint. No third-party hosts on public pages; the three faces are self-hosted from `@fontsource-variable`. Heavy libraries are loaded only on the page that needs them.

## Accessibility

- Contrast is WCAG 2.2 AA in light and dark for every text and state, including text on graphite.
- Focus is always visible: a 2px outline with 2px offset in cobalt on every surface. Never remove it.
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
| Paper `hero-field`, or one graphite `hero-canvas` band | `bg-linear-to-r from-blue-600 to-violet-600` |
| `shadow-card`, `shadow-workflow`, `shadow-floating` | `shadow-lg`, `shadow-xl`, coloured glows |
| `rounded-control`, `rounded-card`, `rounded-panel` | `rounded-xl`, `rounded-[13px]`, pill buttons |
| `app-badge bg-success-soft text-success` | `rounded-full px-3 bg-green-100` |
| Labels in the display face, sentence case | Eyebrows, `uppercase`, `tracking-[0.08em]`, middle-dot meta strings |
| `bg-button text-white` | `bg-action text-white` |
| `<x-ui.icon name="copy" />` | A new inline `<svg>` |
| One `app-button-primary` per view | Several competing primary buttons |
| The rate field from `<x-hero-backdrop />` | Photographs, illustrations or patterns behind a heading |
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
