<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\VatRateChange;
use App\Services\Seo\VatCategorySeoService;
use App\Support\Mcp\VatMcpServer;
use App\Support\Vat\VatNumberFormat;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * /llms.txt and /llms-full.txt, following https://llmstxt.org: a title, a one-paragraph summary,
 * then sections of annotated links. Both are rebuilt whenever a country's rates change.
 */
class LlmsController extends Controller
{
    public function index(VatCategorySeoService $vatCategories): Response
    {
        [$countries, $updatedAt] = $this->countries();

        $text = Cache::remember('llms_txt:v5:'.$updatedAt->timestamp, 3600, function () use ($countries, $updatedAt, $vatCategories) {
            $base = $this->baseUrl();
            $mcp = VatMcpServer::endpoint();

            $text = "# EU VAT Info\n\n";
            $text .= '> '.$this->summary()."\n\n";
            $text .= "Canonical site: {$base}\n";
            $text .= 'Rates last updated: '.$updatedAt->toDateString()."\n";
            $text .= "VAT treatment depends on the goods or services and the customer, so use these rates as reference data rather than tax advice.\n\n";

            $text .= "## Tools\n\n";
            $text .= "- [EU VAT calculator]({$base}/vat-calculator): Add or remove VAT for any EU country. Prefill with ?amount=100&rate=21&mode=exclude.\n";
            $text .= "- [VAT number validator]({$base}/vat-number-validator): Check an EU VAT number against the European Commission's VIES service.\n";
            $text .= "- [VAT rate changes]({$base}/vat-changes): Recorded and upcoming rate changes with their official sources.\n";
            $text .= "- [VAT map]({$base}/vat-map): Standard rates across Europe with a ranked table.\n";
            $text .= "- [Popular VAT calculations]({$base}/top-vat-calculations): VAT on common amounts in every EU country.\n";
            $text .= "- [All VAT tools]({$base}/tools): Every calculator, checker and data download on the site.\n\n";

            $text .= "## Guides\n\n";

            foreach (config('vat-scenarios', []) as $slug => $guide) {
                $text .= "- [{$guide['title']}]({$base}/vat-guides/{$slug}): {$guide['summary']}\n";
            }

            $text .= "\n## Comparisons\n\n";
            $bySlug = $countries->keyBy('slug');

            foreach (config('seo.comparisons', []) as [$first, $second]) {
                if ($bySlug->has($first) && $bySlug->has($second)) {
                    $text .= "- [{$bySlug[$first]->name} vs {$bySlug[$second]->name} VAT]({$base}/compare/{$first}-vs-{$second}-vat): Rates side by side, with the difference on common amounts.\n";
                }
            }

            $text .= "\n";

            $text .= "## Data and APIs\n\n";
            $text .= "- [Full VAT rates in Markdown]({$base}/llms-full.txt): Every rate, currency and VAT number format in one table, plus upcoming changes and sources.\n";
            $text .= "- [MCP server]({$base}/mcp-server): Connect Claude, ChatGPT, VS Code, Cursor or any MCP client to live VAT data at {$mcp} (Streamable HTTP, no sign-up).\n";
            $text .= "- [REST API v1]({$base}/api/v1): Countries, rates and VAT calculations as JSON. No API key needed.\n";
            $text .= "- [OpenAPI description]({$base}/api/v1/openapi.json): OpenAPI 3.1 document for the v1 API.\n";
            $text .= "- [VAT number validation API]({$base}/vat-validation-api): Single and batch VIES checks over HTTP.\n";
            $text .= "- [EU VAT dataset]({$base}/datasets/eu-vat-rates): Every rate as [CSV]({$base}/datasets/eu-vat-rates.csv) or [JSON]({$base}/datasets/eu-vat-rates.json), licensed CC BY 4.0.\n\n";

            $text .= "## EU member states\n\n";
            $text .= $this->countryLines($countries->where('is_eu_member', true), $base);

            $others = $countries->where('is_eu_member', false);

            if ($others->isNotEmpty()) {
                $text .= "\n## Other European countries\n\n";
                $text .= $this->countryLines($others, $base);
            }

            $categories = $vatCategories->eligibleCategories();

            if ($categories->isNotEmpty()) {
                $text .= "\n## VAT categories\n\n";
                $text .= "- [All verified VAT categories]({$base}/vat-rates/categories): Which rate applies to common goods and services.\n";

                foreach ($categories as $category) {
                    $text .= "- [{$category['name']} VAT rates]({$base}/vat-rates/categories/{$category['slug']}): {$category['country_count']} EU countries\n";
                }
            }

            $text .= "\n".$this->sources();

            $text .= "## Optional\n\n";
            $text .= "- [VAT updates and guides]({$base}/blog): Explainers on confirmed EU VAT changes.\n";
            $text .= "- [What's new]({$base}/changelog): Release notes for this site.\n";
            $text .= "- [API catalog]({$base}/.well-known/api-catalog): RFC 9727 list of the public APIs.\n";
            $text .= "- [MCP server card]({$base}/.well-known/mcp/server-card.json): Machine-readable description of the MCP server and its tools.\n";
            $text .= "- [Design system]({$base}/styleguide): Colour, type, shape and motion tokens, components and page patterns, with the tokens as [Design Tokens JSON]({$base}/styleguide/design.tokens.json) and the guide as [Markdown]({$base}/styleguide.md); every component also as a [Storybook]({$base}/storybook/).\n";
            $text .= "- [Brand guidelines]({$base}/brand): The mark, colours, type and voice of EU VAT Info, and how to credit its data.\n";
            $text .= "- [Sitemap]({$base}/sitemap.xml): Every indexable page in all 24 EU languages.\n";

            return $text;
        });

        return $this->markdown($text, $updatedAt);
    }

    public function fullTxt(): Response
    {
        [$countries, $updatedAt] = $this->countries();

        $text = Cache::remember('llms_full_txt:v3:'.$updatedAt->timestamp, 3600, function () use ($countries, $updatedAt) {
            $base = $this->baseUrl();

            $text = "# EU VAT Info: EU VAT rates reference\n\n";
            $text .= '> '.$this->summary()."\n\n";
            $text .= "Canonical site: {$base}\n";
            $text .= 'Rates last updated: '.$updatedAt->toIso8601String()."\n";
            $text .= "License: CC BY 4.0 (https://creativecommons.org/licenses/by/4.0/)\n\n";

            $text .= "## How to read the rates\n\n";
            $text .= "- Standard: the default rate for most goods and services.\n";
            $text .= "- Reduced: lower rates that apply to listed goods and services such as food, books or medicines. Some countries have two.\n";
            $text .= "- Super-reduced: a rate below 5% that some member states keep for specific items.\n";
            $text .= "- Parking: a transitional rate of at least 12% that a few member states still apply to certain items.\n";
            $text .= "- VAT prefix: the two letters that start a VAT number in VIES. Greece uses EL.\n";
            $text .= "- VAT number format: the Commission's notation, where 9 is a digit, L a letter and X a letter or a digit. A number that fits can still be unregistered, so check it in VIES.\n";
            $text .= "- VAT treatment depends on the goods or services and the customer, so treat these rates as reference data rather than tax advice.\n\n";

            $text .= "## EU member states\n\n";
            $text .= "| Country | ISO | VAT prefix | VAT number format | Currency | Standard | Reduced | Super-reduced | Parking |\n";
            $text .= "|---|---|---|---|---|---|---|---|---|\n";

            foreach ($countries->where('is_eu_member', true) as $country) {
                $format = VatNumberFormat::forCountry($country);

                $text .= '| '.implode(' | ', [
                    $country->name,
                    $country->iso_code,
                    $format['prefix'] ?? ($country->iso_code === 'GR' ? 'EL' : $country->iso_code),
                    $format['format'] ?? '-',
                    $country->currencyCode(),
                    ...$this->rateCells($country),
                ])." |\n";
            }

            $others = $countries->where('is_eu_member', false);

            if ($others->isNotEmpty()) {
                $text .= "\n## Other European countries\n\n";
                $text .= "These countries are outside the EU VAT system, so their VAT numbers cannot be checked in VIES.\n\n";
                $text .= "| Country | ISO | Currency | Standard | Reduced | Super-reduced | Parking |\n";
                $text .= "|---|---|---|---|---|---|---|\n";

                foreach ($others as $country) {
                    $text .= '| '.implode(' | ', [$country->name, $country->iso_code, $country->currencyCode(), ...$this->rateCells($country)])." |\n";
                }
            }

            $upcoming = VatRateChange::query()
                ->with('country:id,name,iso_code')
                ->whereHas('country')
                ->whereDate('change_date', '>=', today())
                ->orderBy('change_date')
                ->limit(50)
                ->get();

            if ($upcoming->isNotEmpty()) {
                $text .= "\n## Upcoming VAT rate changes\n\n";
                $text .= "| Country | Rate | From | To | Takes effect |\n";
                $text .= "|---|---|---|---|---|\n";

                foreach ($upcoming as $change) {
                    $text .= '| '.implode(' | ', [
                        $change->country->name,
                        str_replace('_', '-', (string) $change->rate_type),
                        $change->old_rate === null ? '-' : Country::formatRate($change->old_rate).'%',
                        $change->new_rate === null ? '-' : Country::formatRate($change->new_rate).'%',
                        $change->change_date?->toDateString() ?? '-',
                    ])." |\n";
                }
            }

            $recent = VatRateChange::query()
                ->with('country:id,name,slug')
                ->whereHas('country', fn ($query) => $query->where('is_eu_member', true))
                ->whereDate('change_date', '<', today())
                ->whereDate('change_date', '>=', today()->subMonths(24))
                ->orderByDesc('change_date')
                ->limit(50)
                ->get();

            if ($recent->isNotEmpty()) {
                $text .= "\n## Recent VAT rate changes (last 24 months)\n\n";
                $text .= "| Country | Rate | From | To | Took effect |\n";
                $text .= "|---|---|---|---|---|\n";

                foreach ($recent as $change) {
                    $date = $change->change_date->toDateString();
                    $text .= '| '.implode(' | ', [
                        $change->country->name,
                        str_replace('_', '-', (string) $change->rate_type),
                        $change->old_rate === null ? '-' : Country::formatRate($change->old_rate).'%',
                        $change->new_rate === null ? '-' : Country::formatRate($change->new_rate).'%',
                        "[{$date}]({$base}/vat-changes/{$change->country->slug}/{$change->rate_type}/{$date})",
                    ])." |\n";
                }
            }

            $mcp = VatMcpServer::endpoint();

            $text .= "\n## Using the data\n\n";
            $text .= "- One country: GET {$base}/api/v1/countries/{slug-or-iso}, for example {$base}/api/v1/countries/germany\n";
            $text .= "- Calculate VAT: GET {$base}/api/v1/calculate?amount=100&country=DE&rate_type=standard&mode=add (mode=remove extracts VAT from a gross amount)\n";
            $text .= "- Validate a VAT number: POST {$base}/api/v1/validate with {\"country_code\": \"DE\", \"vat_number\": \"123456789\"}\n";
            $text .= "- OpenAPI description: {$base}/api/v1/openapi.json\n";
            $text .= "- MCP server: {$mcp} over Streamable HTTP, no sign-up. Tools: ".implode(', ', VatMcpServer::toolNames())."\n";
            $text .= "- Downloads: {$base}/datasets/eu-vat-rates.csv and {$base}/datasets/eu-vat-rates.json\n\n";

            $text .= $this->sources();

            $text .= "## Country pages\n\n";

            foreach ($countries as $country) {
                $links = ["[calculator]({$base}/vat-calculator/{$country->slug})"];

                if ($country->is_eu_member && $country->vies_available) {
                    $links[] = "[VAT number validator]({$base}/vat-number-validator/{$country->slug})";
                }

                if ($country->is_eu_member && $country->hasVatHistory()) {
                    $links[] = "[rate history]({$base}/vat-rates/{$country->slug}/history)";
                }

                $text .= "- {$country->name}: ".implode(' · ', $links)."\n";
            }

            return $text;
        });

        return $this->markdown($text, $updatedAt);
    }

    /**
     * @return array{0: Collection<int, Country>, 1: CarbonImmutable}
     */
    private function countries(): array
    {
        $countries = Country::query()
            ->calculatorAvailable()
            ->withExists(['vatRates', 'vatRateChanges'])
            ->orderBy('name')
            ->get();

        $latest = $countries->max('updated_at');

        return [$countries, $latest ? CarbonImmutable::parse($latest) : CarbonImmutable::now()->startOfDay()];
    }

    private function countryLines(Collection $countries, string $base): string
    {
        return $countries->map(function (Country $country) use ($base) {
            $reduced = $country->reducedRates();
            $line = "- [{$country->name}]({$base}/vat-calculator/{$country->slug}): ".Country::formatRate($country->standard_rate).'% standard';

            if ($reduced !== []) {
                $line .= '; reduced '.implode(', ', array_map(fn (float $rate) => Country::formatRate($rate).'%', $reduced));
            }

            foreach (['super_reduced' => 'super-reduced', 'parking' => 'parking'] as $type => $label) {
                if ($rate = $country->rateForType($type)) {
                    $line .= "; {$label} ".Country::formatRate($rate).'%';
                }
            }

            $line .= '; '.$country->currencyCode();

            if ($format = VatNumberFormat::forCountry($country)) {
                $line .= "; VAT numbers {$format['format']}";
            }

            if ($country->is_eu_member && $country->vies_available) {
                $line .= " · [VAT number validator]({$base}/vat-number-validator/{$country->slug})";
            }

            if ($country->is_eu_member && $country->hasVatHistory()) {
                $line .= " · [rate history]({$base}/vat-rates/{$country->slug}/history)";
            }

            return $line."\n";
        })->implode('');
    }

    /**
     * @return list<string>
     */
    private function rateCells(Country $country): array
    {
        $rate = fn (mixed $value) => $value ? Country::formatRate($value).'%' : '-';

        return [
            Country::formatRate($country->standard_rate).'%',
            $country->formattedReducedRates(', ') ?? '-',
            $rate($country->rateForType('super_reduced')),
            $rate($country->rateForType('parking')),
        ];
    }

    private function summary(): string
    {
        return 'Current and historical VAT rates, a VAT calculator, VIES VAT number validation and machine-readable VAT data for the 27 EU member states and five other European countries. '
            .'Free to use without sign-up. Rates come from European Commission data and are updated every week.';
    }

    private function sources(): string
    {
        $text = "## Sources\n\n";
        $text .= '- [European Commission: VAT rates]('.config('calculator.rates_source_url')."): The framework for EU VAT rates and the Taxes in Europe Database the member states report to.\n";
        $text .= "- [Your Europe: VAT rules and rates](https://europa.eu/youreurope/business/finance-and-tax/vat/vat-rules-rates/index_en.htm): The Commission's table of standard, reduced, super-reduced and parking rates, which decides where sources disagree.\n";
        $text .= '- [Open vat-rates dataset]('.config('calculator.dataset_source_url')."): The standard rate history, synced every week, and the rates of the countries outside the EU.\n";
        $text .= "- [VIES](https://ec.europa.eu/taxation_customs/vies/): The Commission's register behind every VAT number check.\n\n";

        return $text;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('seo.canonical_url', 'https://vat.businesspress.io'), '/');
    }

    private function markdown(string $text, CarbonImmutable $updatedAt): Response
    {
        return response($text, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'Last-Modified' => $updatedAt->toRfc7231String(),
        ]);
    }
}
