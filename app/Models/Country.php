<?php

namespace App\Models;

use App\Traits\HasAnalytics;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Country extends Model implements Auditable, Sitemapable
{
    use HasAnalytics;
    use HasFactory;
    use HasSlug;
    use \OwenIt\Auditing\Auditable;

    public const EU_MEMBER_CODES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
        'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
    ];

    protected $auditExclude = [
        'id',
    ];

    protected $fillable = [
        'iso_code',
        'iso_code_2',
        'name',
        'native_name',
        'slug',
        'standard_rate',
        'reduced_rate',
        'super_reduced_rate',
        'zero_rate',
        'parking_rate',
        'currency',
        'currency_code',
        'currency_symbol',
        'flag',
        'is_eu_member',
        'vies_available',
    ];

    protected function casts(): array
    {
        return [
            'is_eu_member' => 'boolean',
            'vies_available' => 'boolean',
        ];
    }

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function countryRank()
    {
        // Find the rank of the country based on the standard rate
        return Country::where('standard_rate', '<', $this->standard_rate)->count() + 1;
    }

    /**
     * Countries drawn on the VAT map, cached because every map render needs the full set.
     *
     * @return Collection<int, self>
     */
    public static function forMap(): Collection
    {
        return Cache::remember('vat_map_countries_v2', 600, fn () => self::query()
            ->whereNotNull('slug')
            ->where('standard_rate', '>', 0)
            ->whereRaw('LENGTH(iso_code) = 2')
            ->orderBy('name')
            ->get());
    }

    public function scopeCalculatorAvailable(Builder $query): Builder
    {
        return $query
            ->whereNotNull('slug')
            ->whereRaw("TRIM(slug) <> ''")
            ->whereRaw('LENGTH(iso_code) = 2')
            ->where('standard_rate', '>', 0)
            ->where(fn (Builder $scope) => $scope
                ->where('is_eu_member', true)
                ->orWhereIn('slug', config('calculator.additional_country_slugs', [])));
    }

    public function isCalculatorAvailable(): bool
    {
        return filled($this->slug)
            && strlen((string) $this->iso_code) === 2
            && (float) $this->standard_rate > 0
            && ($this->is_eu_member || in_array($this->slug, config('calculator.additional_country_slugs', []), true));
    }

    public function calculatorGroup(): string
    {
        return $this->is_eu_member ? 'eu' : 'other_europe';
    }

    public function toSitemapTag(): Url|string|array
    {
        // Simple return:
        return route('vat-calculator.country', $this->slug);
    }

    public function analytics()
    {
        return $this->hasMany(CountryAnalytic::class);
    }

    public function vatRates()
    {
        return $this->hasMany(VatRate::class);
    }

    public function vatRateChanges()
    {
        return $this->hasMany(VatRateChange::class);
    }

    /**
     * When the current standard rate took effect and the rate it replaced, from the stored rate history.
     * Null when the history does not record the current rate.
     *
     * @return array{since: CarbonInterface, previous: ?float}|null
     */
    public function standardRateSince(): ?array
    {
        $periods = $this->vatRates()
            ->where('type', 'standard')
            ->whereDate('effective_from', '<=', today())
            ->orderByDesc('effective_from')
            ->limit(2)
            ->get(['rate', 'effective_from', 'effective_to']);

        $current = $periods->first();

        if (! $current || ($current->effective_to && $current->effective_to->isPast()) || abs((float) $current->rate - (float) $this->standard_rate) > 0.001) {
            return null;
        }

        $previous = $periods->get(1);

        return ['since' => $current->effective_from, 'previous' => $previous ? (float) $previous->rate : null];
    }

    public function hasVatHistory(): bool
    {
        if (array_key_exists('vat_rates_exists', $this->attributes)
            && array_key_exists('vat_rate_changes_exists', $this->attributes)) {
            return (bool) $this->vat_rates_exists || (bool) $this->vat_rate_changes_exists;
        }

        return $this->vatRates()->exists() || $this->vatRateChanges()->exists();
    }

    public function vatRateRules()
    {
        return $this->hasMany(VatRateRule::class);
    }

    /**
     * Current reduced rates, lowest first. The column holds one rate or a list such as "10 / 13".
     *
     * @return list<float>
     */
    public function reducedRates(): array
    {
        return self::parseRateList($this->reduced_rate);
    }

    public function primaryReducedRate(): ?float
    {
        return $this->reducedRates()[0] ?? null;
    }

    /**
     * Distinct rates a calculator should offer, labelled by the first category that uses them.
     *
     * @return list<array{type: string, rate: float}>
     */
    public function rateOptions(): array
    {
        $candidates = [['type' => 'standard', 'rate' => (float) $this->standard_rate]];

        foreach (array_reverse($this->reducedRates()) as $rate) {
            $candidates[] = ['type' => 'reduced', 'rate' => $rate];
        }

        $candidates[] = ['type' => 'super_reduced', 'rate' => (float) $this->super_reduced_rate];
        $candidates[] = ['type' => 'parking', 'rate' => (float) $this->parking_rate];

        $options = [];

        foreach ($candidates as $candidate) {
            if ($candidate['rate'] > 0 && ! in_array($candidate['rate'], array_column($options, 'rate'), true)) {
                $options[] = $candidate;
            }
        }

        return $options;
    }

    public function rateForType(string $type): ?float
    {
        $rate = match ($type) {
            'reduced' => $this->primaryReducedRate(),
            'super_reduced' => (float) $this->super_reduced_rate,
            'parking' => (float) $this->parking_rate,
            default => (float) $this->standard_rate,
        };

        return $rate > 0 ? $rate : null;
    }

    /**
     * @return array{standard: float, reduced: ?float, reduced_rates: list<float>, super_reduced: ?float, parking: ?float}
     */
    public function apiRates(): array
    {
        return [
            'standard' => (float) $this->standard_rate,
            'reduced' => $this->primaryReducedRate(),
            'reduced_rates' => $this->reducedRates(),
            'super_reduced' => $this->rateForType('super_reduced'),
            'parking' => $this->rateForType('parking'),
        ];
    }

    public function formattedReducedRates(string $separator = ' / '): ?string
    {
        $rates = $this->reducedRates();

        return $rates === [] ? null : implode($separator, array_map(fn (float $rate) => self::formatRate($rate).'%', $rates));
    }

    public function currencyCode(): string
    {
        return strtoupper((string) ($this->currency_code ?: match ($this->iso_code) {
            'CZ' => 'CZK',
            'DK' => 'DKK',
            'HU' => 'HUF',
            'PL' => 'PLN',
            'RO' => 'RON',
            'SE' => 'SEK',
            'CH' => 'CHF',
            'IS' => 'ISK',
            'NO' => 'NOK',
            'GB' => 'GBP',
            'TR' => 'TRY',
            default => 'EUR',
        }));
    }

    /**
     * @return list<float>
     */
    public static function parseRateList(mixed $value): array
    {
        if (is_int($value) || is_float($value)) {
            return $value > 0 && $value < 100 ? [(float) $value] : [];
        }

        preg_match_all('/\d+(?:[.,]\d+)?/', (string) $value, $matches);

        $rates = [];

        foreach ($matches[0] as $match) {
            $rate = (float) str_replace(',', '.', $match);

            if ($rate > 0 && $rate < 100 && ! in_array($rate, $rates, true)) {
                $rates[] = $rate;
            }
        }

        sort($rates);

        return $rates;
    }

    public static function formatRate(float|int|string|null $rate): string
    {
        $formatted = number_format((float) $rate, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    public function getCurrencyDisplayAttribute(): string
    {
        return $this->currency_symbol ?: match ($this->currencyCode()) {
            'CZK' => 'Kč',
            'DKK', 'SEK', 'NOK', 'ISK' => 'kr',
            'HUF' => 'Ft',
            'PLN' => 'zł',
            'RON' => 'lei',
            'CHF' => 'CHF',
            'GBP' => '£',
            'TRY' => '₺',
            default => '€',
        };
    }
}
