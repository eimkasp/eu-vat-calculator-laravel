<?php

namespace App\Support\DesignSystem;

use App\Livewire\Styleguide;
use App\Models\Country;
use InvalidArgumentException;

/**
 * Every live example in resources/views/styleguide/examples, with the section it belongs to and how it is
 * presented. The /styleguide page and the Storybook stories both read it, so the two never disagree.
 */
final class ComponentCatalog
{
    /**
     * The background each example is shown on, as classes.
     */
    public const CANVASES = [
        'surface' => 'bg-surface',
        'subtle' => 'bg-surface-subtle',
        'workspace' => 'bg-workspace',
        'brand' => 'hero-canvas',
    ];

    /**
     * The 27 EU standard rates the rate field specimen draws, fixed so the example renders the same everywhere
     * without reading the database. On a page, <x-hero-backdrop /> reads the live rates.
     *
     * @var array<int, float>
     */
    public const SAMPLE_RATES = [20, 21, 20, 25, 19, 21, 25, 24, 25.5, 20, 19, 24, 27, 23, 22, 21, 21, 17, 18, 21, 23, 23, 21, 23, 22, 21, 25];

    /**
     * @var array<string, array{section: string, title: string, description: string, canvas: 'surface'|'subtle'|'workspace'|'brand'}>
     */
    public const EXAMPLES = [
        'buttons-variants' => ['section' => 'buttons', 'title' => 'Primary, secondary and ghost', 'description' => 'app-button-primary is the one cobalt key per view', 'canvas' => 'surface'],
        'buttons-keys' => ['section' => 'buttons', 'title' => 'Key edges', 'description' => 'shadow-key and shadow-key-accent stand a key on a 2px edge; shadow-key-press sinks it', 'canvas' => 'workspace'],
        'buttons-on-brand' => ['section' => 'buttons', 'title' => 'On graphite', 'description' => 'app-button-inverse and app-button-on-brand, only inside hero-canvas', 'canvas' => 'brand'],
        'buttons-sizes' => ['section' => 'buttons', 'title' => 'Sizes and icon-only', 'description' => 'Size tweaks are extra classes; icon-only buttons need aria-label', 'canvas' => 'surface'],
        'buttons-states' => ['section' => 'buttons', 'title' => 'Loading and disabled', 'description' => 'Select Validate to see the busy state', 'canvas' => 'surface'],
        'field-text' => ['section' => 'fields', 'title' => 'Text field', 'description' => 'app-field, cut on two corners, with help text', 'canvas' => 'surface'],
        'field-error' => ['section' => 'fields', 'title' => 'Error', 'description' => 'aria-invalid plus a message that says what to fix', 'canvas' => 'surface'],
        'field-select' => ['section' => 'fields', 'title' => 'Select', 'description' => 'app-select keeps the native control', 'canvas' => 'surface'],
        'field-amount' => ['section' => 'fields', 'title' => 'Prefix and search', 'description' => 'Money inputs are tabular', 'canvas' => 'surface'],
        'field-format-hint' => ['section' => 'fields', 'title' => 'VAT number format hint', 'description' => 'A warning under the field when a number does not fit its country’s format; it never blocks the check', 'canvas' => 'surface'],
        'segmented' => ['section' => 'selection', 'title' => 'Segmented control', 'description' => 'A key-face thumb on a sunken tray slides 200ms; data-value is rendered on the server too', 'canvas' => 'surface'],
        'chips' => ['section' => 'selection', 'title' => 'Chips', 'description' => 'Small keys; app-chip-active presses one in, with aria-pressed; dashed for “add custom”', 'canvas' => 'surface'],
        'tabs' => ['section' => 'selection', 'title' => 'Chip tabs', 'description' => 'A tablist with arrow-key movement', 'canvas' => 'surface'],
        'surface-hero-field' => ['section' => 'surfaces', 'title' => 'Tool hero and the rate field', 'description' => 'hero-field with <x-hero-backdrop />: an ASCII field drawn from the 27 standard rates', 'canvas' => 'surface'],
        'surface-card' => ['section' => 'surfaces', 'title' => 'Card', 'description' => 'app-surface on paper', 'canvas' => 'workspace'],
        'surface-raised' => ['section' => 'surfaces', 'title' => 'Raised task card', 'description' => 'app-surface-raised, cut on two corners, once per view, on a hero', 'canvas' => 'workspace'],
        'surface-popover' => ['section' => 'surfaces', 'title' => 'Popover', 'description' => 'app-popover for menus, the palette and toasts', 'canvas' => 'workspace'],
        'surface-brand-panel' => ['section' => 'surfaces', 'title' => 'Brand panel', 'description' => 'app-brand-panel for tiles and fields inside a graphite band', 'canvas' => 'brand'],
        'surface-cuts' => ['section' => 'surfaces', 'title' => 'Cut corners', 'description' => 'cut and cut-sm bevel two opposite corners where corner-shape is supported, and keep the plain radius elsewhere', 'canvas' => 'workspace'],
        'surface-note' => ['section' => 'surfaces', 'title' => 'Note', 'description' => 'app-note: a quiet paper aside with an info icon', 'canvas' => 'surface'],
        'content-inline' => ['section' => 'content', 'title' => 'Links, keys and inline code', 'description' => 'app-link, app-kbd and an app-eyebrow source line', 'canvas' => 'surface'],
        'content-badges' => ['section' => 'content', 'title' => 'Badges', 'description' => 'app-badge with a tone pair', 'canvas' => 'surface'],
        'content-prose' => ['section' => 'content', 'title' => 'Prose', 'description' => 'app-prose for Markdown and long translations', 'canvas' => 'surface'],
        'content-code' => ['section' => 'content', 'title' => 'Code block', 'description' => 'app-code stays graphite in both themes', 'canvas' => 'surface'],
        'content-disclosure' => ['section' => 'content', 'title' => 'Disclosure', 'description' => 'Native details and summary for FAQs', 'canvas' => 'surface'],
        'content-faq' => ['section' => 'content', 'title' => 'Questions', 'description' => 'x-faq-list: native disclosures, with FAQPage data built from the same list', 'canvas' => 'workspace'],
        'data-table' => ['section' => 'data', 'title' => 'Table', 'description' => 'app-table inside a card, scrollable on phones', 'canvas' => 'surface'],
        'data-breakdown' => ['section' => 'data', 'title' => 'Breakdown', 'description' => 'A dl of label and value pairs', 'canvas' => 'surface'],
        'data-meter' => ['section' => 'data', 'title' => 'Meter', 'description' => 'app-meter: fifty mono cells under the calculator total, aria-hidden beside the figures', 'canvas' => 'surface'],
        'data-bars' => ['section' => 'data', 'title' => 'Bars and map legend', 'description' => 'bg-action on bg-action/15 with square ends; the map ramp swatches', 'canvas' => 'surface'],
        'nav-header' => ['section' => 'navigation', 'title' => 'Header links', 'description' => 'app-nav-link and app-nav-link-active on the paper header', 'canvas' => 'subtle'],
        'overlay-menu' => ['section' => 'overlays', 'title' => 'Menu', 'description' => 'Closes on Escape or an outside click', 'canvas' => 'surface'],
        'overlay-toast' => ['section' => 'overlays', 'title' => 'Toasts', 'description' => '$store.toasts.push(message, tone)', 'canvas' => 'surface'],
        'overlay-palette' => ['section' => 'overlays', 'title' => 'Quick search', 'description' => 'Opens with ⌘K, Ctrl K or /', 'canvas' => 'surface'],
        'identity-logo' => ['section' => 'identity', 'title' => 'Logo', 'description' => 'The mark with and without the wordmark; it inverts with the theme', 'canvas' => 'workspace'],
        'identity-flags' => ['section' => 'identity', 'title' => 'Flags', 'description' => 'Every country, sizes xs to xl', 'canvas' => 'surface'],
        'pattern-result' => ['section' => 'results', 'title' => 'Calculation result', 'description' => 'Answer, summary, breakdown and actions', 'canvas' => 'workspace'],
        'pattern-freshness' => ['section' => 'results', 'title' => 'Freshness and source', 'description' => 'x-rate-freshness: the update date and the source, next to every rate', 'canvas' => 'surface'],
        'country-heading' => ['section' => 'country', 'title' => 'Country heading', 'description' => 'The flag beside the heading and the rate panel on the content edge, on paper', 'canvas' => 'workspace'],
        'country-reference' => ['section' => 'country', 'title' => 'Rate reference', 'description' => 'Each rate with what it covers, its start date and the VAT number format', 'canvas' => 'workspace'],
        'country-common-amounts' => ['section' => 'country', 'title' => 'Common amounts', 'description' => 'Every figure links its indexable calculation page', 'canvas' => 'workspace'],
        'pattern-stat-tiles' => ['section' => 'tiles', 'title' => 'Stat tiles', 'description' => 'Status colour only on the icon', 'canvas' => 'workspace'],
        'pattern-feature-cards' => ['section' => 'tiles', 'title' => 'Link cards', 'description' => 'An icon well, a title and one line', 'canvas' => 'workspace'],
        'pattern-list-rows' => ['section' => 'tiles', 'title' => 'List rows', 'description' => 'Direction as an icon, a word for screen readers and a sign', 'canvas' => 'workspace'],
        'pattern-empty' => ['section' => 'states', 'title' => 'Empty', 'description' => 'A next step, not a dead end', 'canvas' => 'workspace'],
        'pattern-loading' => ['section' => 'states', 'title' => 'Loading', 'description' => 'Spinner plus aria-busy or a disabled button', 'canvas' => 'surface'],
        'pattern-alerts' => ['section' => 'states', 'title' => 'Success, outage and error', 'description' => 'Outages are about the service, never the visitor', 'canvas' => 'surface'],
        'pattern-cta' => ['section' => 'calls-to-action', 'title' => 'Graphite call to action', 'description' => 'hero-canvas rounded-panel, once per page', 'canvas' => 'workspace'],
    ];

    /**
     * @return array{section: string, title: string, description: string, canvas: string}
     */
    public static function example(string $name): array
    {
        return self::EXAMPLES[$name] ?? throw new InvalidArgumentException("No styleguide example named {$name} in ComponentCatalog.");
    }

    /**
     * The country the country page examples show: Germany as it is stored, never saved, so the examples render the
     * same everywhere without reading the database.
     */
    public static function sampleCountry(): Country
    {
        return (new Country)->forceFill([
            'name' => 'Germany',
            'slug' => 'germany',
            'iso_code' => 'DE',
            'standard_rate' => 19,
            'reduced_rate' => '7',
            'currency_code' => 'EUR',
            'currency_symbol' => '€',
            'is_eu_member' => true,
            'vies_available' => true,
            'updated_at' => '2026-10-01 08:00:00',
            'vat_rates_exists' => true,
            'vat_rate_changes_exists' => true,
        ]);
    }

    /**
     * Sections that hold examples, in page order, each with its group (Components or Patterns) and its examples.
     *
     * @return array<string, array{group: string, title: string, examples: array<string, array{section: string, title: string, description: string, canvas: string}>}>
     */
    public static function sections(): array
    {
        $sections = [];

        foreach (Styleguide::SECTIONS as $group => $ids) {
            foreach ($ids as $id => $title) {
                $examples = array_filter(self::EXAMPLES, fn (array $example) => $example['section'] === $id);

                if ($examples !== []) {
                    $sections[$id] = ['group' => $group, 'title' => $title, 'examples' => $examples];
                }
            }
        }

        return $sections;
    }
}
