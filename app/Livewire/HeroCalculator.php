<?php

namespace App\Livewire;

use App\Models\Country;
use App\Services\CountryAnalyticsService;
use App\Support\Vat\AmountParser;
use App\Support\Vat\Money;
use App\Support\Vat\VatCalculation;
use App\Support\Vat\VatMode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Url;
use Livewire\Component;

class HeroCalculator extends Component
{
    /** Amounts offered as one-tap buttons below the amount field. */
    public const QUICK_AMOUNTS = [100, 500, 1000, 5000, 10000];

    private const HISTORY_KEY = 'hero_calc_history';

    private const HISTORY_LIMIT = 6;

    #[Url(as: 'country', history: true)]
    public string $selectedCountrySlug = '';

    #[Locked]
    public string $mode = 'exclude';

    #[Locked]
    public string $amount = '100';

    #[Locked]
    public float $selectedRate = 0;

    #[Locked]
    public string $currency = 'EUR';

    #[Locked]
    public string $currencySymbol = '€';

    /** @var list<array{type: string, rate: float, label: string}> */
    #[Locked]
    public array $rates = [];

    /** @var list<array<string, mixed>> */
    #[Locked]
    public array $history = [];

    #[Locked]
    public string $surface = 'dark';

    #[Locked]
    public string $variant = 'full';

    #[Locked]
    public ?string $errorMessage = null;

    #[Locked]
    public ?float $customRate = null;

    private ?string $previousCountrySlug = null;

    public function mount(
        ?string $initialCountry = null,
        string $surface = 'dark',
        string $variant = 'full',
        ?string $initialAmount = null,
        ?string $initialRate = null,
        ?string $initialMode = null,
    ): void {
        $this->surface = $surface;
        $this->variant = in_array($variant, ['full', 'compact'], true) ? $variant : 'full';

        $candidates = [$initialCountry, $this->selectedCountrySlug, request()->cookie('hero_calc_country'), 'germany'];
        $slug = collect($candidates)->first(fn ($candidate) => filled($candidate) && $this->findCountry((string) $candidate))
            ?? $this->countries[0]['slug'] ?? '';

        $this->selectedCountrySlug = (string) $slug;
        $this->loadCountry();
        $this->applyPrefill($initialAmount, $initialRate, $initialMode);
        $this->history = array_values(array_filter((array) session(self::HISTORY_KEY, []), 'is_array'));
    }

    /**
     * @return list<array{slug: string, name: string, iso: string, rate: float, group: string}>
     */
    #[Computed]
    public function countries(): array
    {
        return Cache::remember('hero_calculator_countries_v4', 600, fn () => Country::calculatorAvailable()
            ->orderByDesc('is_eu_member')
            ->orderBy('name')
            ->get()
            ->map(fn (Country $country) => [
                'slug' => $country->slug,
                'name' => $country->name,
                'iso' => strtolower((string) $country->iso_code),
                'rate' => (float) $country->standard_rate,
                'group' => $country->calculatorGroup(),
            ])
            ->all());
    }

    #[Computed]
    public function country(): ?Country
    {
        return $this->findCountry($this->selectedCountrySlug);
    }

    public function updatingSelectedCountrySlug(): void
    {
        $this->previousCountrySlug = $this->selectedCountrySlug;
    }

    /**
     * The slug is bound to the query string, so back/forward navigation may set it directly; it is
     * validated exactly like an explicit selection and an unsupported value keeps the current country.
     */
    public function updatedSelectedCountrySlug(mixed $slug): void
    {
        $this->selectedCountrySlug = (string) $this->previousCountrySlug;
        $this->selectCountry(is_string($slug) ? $slug : '');
    }

    public function selectCountry(string $slug): void
    {
        if (! $this->findCountry($slug)) {
            $this->errorMessage = __('ui.calculator.unsupported_country');

            return;
        }

        $this->selectedCountrySlug = $slug;
        unset($this->country);
        $this->loadCountry();

        cookie()->queue('hero_calc_country', $slug, 60 * 24 * 90);
    }

    /**
     * Persists a settled calculation to the visitor's history and records it for analytics.
     */
    #[Renderless]
    public function calculate(string $mode, float|int|string $rate, float|int|string|null $amount): void
    {
        $country = $this->country;
        $parsed = AmountParser::parse($amount, app()->getLocale());

        if (! $country || $parsed === null || $parsed <= 0) {
            return;
        }

        $calculation = VatCalculation::make($parsed, min(max((float) $rate, 0), 100), VatMode::fromInput($mode));

        $entry = [
            'key' => implode('|', [$country->slug, $calculation->rate, $calculation->input(), $calculation->mode->value]),
            'slug' => $country->slug,
            'country' => $country->name,
            'iso' => strtolower((string) $country->iso_code),
            'amount' => $calculation->input(),
            'rate' => $calculation->rate,
            'mode' => $calculation->mode->value,
            'net' => $calculation->net,
            'vat' => $calculation->vat,
            'gross' => $calculation->gross,
            'currency' => $country->currencyCode(),
            'label' => Money::format($calculation->gross, $country->currencyCode()),
        ];

        $history = array_values(array_filter($this->history, fn (array $item) => ($item['key'] ?? null) !== $entry['key']));
        $this->history = array_slice([$entry, ...$history], 0, self::HISTORY_LIMIT);
        session()->put(self::HISTORY_KEY, $this->history);

        if (RateLimiter::attempt('calculator-analytics:'.session()->getId(), 30, fn () => true)) {
            app(CountryAnalyticsService::class)->trackView($country, request(), 'calculator', [
                'amount' => $calculation->input(),
                'rate_used' => $calculation->rate,
                'result' => $calculation->gross,
                'vat_included' => $calculation->mode->value,
            ]);
        }
    }

    public function clearHistory(): void
    {
        $this->history = [];
        session()->forget(self::HISTORY_KEY);
    }

    public function render()
    {
        $calculation = VatCalculation::make(
            AmountParser::parse($this->amount, app()->getLocale()) ?? 0,
            $this->customRate ?? $this->selectedRate,
            VatMode::fromInput($this->mode),
        );

        return view('livewire.hero-calculator', [
            'calculation' => $calculation,
            'country' => $this->country,
        ]);
    }

    /**
     * Restores a calculation opened from a shared result link; a rate the country does not use becomes a custom rate.
     */
    private function applyPrefill(?string $amount, ?string $rate, ?string $mode): void
    {
        if ($mode !== null) {
            $this->mode = VatMode::fromInput($mode)->value;
        }

        $parsed = $amount !== null ? AmountParser::parse($amount, 'en') : null;

        if ($parsed !== null && $parsed > 0 && $parsed <= VatCalculation::MAX_AMOUNT) {
            $this->amount = number_format($parsed, fmod($parsed, 1.0) === 0.0 ? 0 : 2, '.', '');
        }

        if ($rate === null || ! is_numeric($rate)) {
            return;
        }

        $rate = round(min(max((float) $rate, 0), 100), 2);

        if (in_array($rate, array_map('floatval', array_column($this->rates, 'rate')), true)) {
            $this->selectedRate = $rate;
        } else {
            $this->customRate = $rate;
        }
    }

    private function loadCountry(): void
    {
        $country = $this->country;

        if (! $country) {
            $this->rates = [];

            return;
        }

        $this->errorMessage = null;
        $this->currency = $country->currencyCode();
        $this->currencySymbol = $country->currency_display;
        $this->rates = array_map(fn (array $option) => $option + [
            'label' => __('ui.rate_type.'.$option['type']),
        ], $country->rateOptions());

        $available = array_column($this->rates, 'rate');

        if (! in_array((float) $this->selectedRate, $available, true)) {
            $this->selectedRate = (float) ($available[0] ?? 0);
        }
    }

    private function findCountry(string $slug): ?Country
    {
        if ($slug === '') {
            return null;
        }

        return Country::calculatorAvailable()->where('slug', $slug)->first();
    }
}
