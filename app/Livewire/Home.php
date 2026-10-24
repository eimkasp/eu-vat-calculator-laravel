<?php

namespace App\Livewire;

use App\Models\Country;
use App\Models\VatRateChange;
use App\Support\SiteNavigation;
use App\Support\Vat\Money;
use App\Support\Vat\VatCalculation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Url;
use Livewire\Component;

class Home extends Component
{
    /** The worked example in the benefits and questions, as an invoice amount rather than a round number. */
    public const EXAMPLE_NET = 249.90;

    #[Url(except: '')]
    public string $search = '';

    public function render()
    {
        /** @var Collection<int, Country> $countries */
        $countries = Cache::remember('home_eu_countries_v3', 3600, fn () => Country::query()
            ->where('is_eu_member', true)
            ->orderBy('standard_rate')
            ->orderBy('name')
            ->get());

        $rates = $countries->map(fn (Country $country) => (float) $country->standard_rate);
        $stats = $countries->isEmpty() ? null : [
            'lowest' => $countries->first(),
            'highest' => $countries->sortByDesc(fn (Country $country) => (float) $country->standard_rate)->first(),
            'average' => round($rates->avg(), 1),
            'count' => $countries->count(),
        ];

        // The landing copy is English only for now, so every other language keeps the translated homepage.
        $landingPage = app()->getLocale() === 'en';

        return view('livewire.home', [
            'countries' => $countries,
            'stats' => $stats,
            'landingPage' => $landingPage,
            'landing' => $landingPage ? $this->landing($countries, $stats) : null,
        ]);
    }

    /**
     * Every figure the landing copy quotes, read from the data so the page never states a stale number.
     *
     * @param  Collection<int, Country>  $countries
     * @param  array<string, mixed>|null  $stats
     * @return array<string, mixed>
     */
    private function landing(Collection $countries, ?array $stats): array
    {
        $data = Cache::remember('home_landing_figures_v1', 3600, function () {
            $eu = fn (Builder $query) => $query->where('is_eu_member', true);

            return [
                'calculator_count' => Country::calculatorAvailable()->count(),
                'change_count' => VatRateChange::query()->whereHas('country', $eu)->count(),
                'latest_change' => VatRateChange::query()
                    ->with('country')
                    ->whereHas('country', $eu)
                    ->where('rate_type', 'standard')
                    ->whereDate('change_date', '<=', now())
                    ->latest('change_date')
                    ->first(),
            ];
        });

        $germany = $countries->firstWhere('slug', 'germany');
        $france = $countries->firstWhere('slug', 'france');
        $example = $germany ? VatCalculation::addVat(self::EXAMPLE_NET, $germany->standard_rate) : null;
        $money = fn (float $amount) => Money::format($amount, 'EUR');
        $percent = fn ($rate) => Country::formatPercent($rate);

        $worked = $example ? [
            'country' => $germany->name,
            'rate' => $percent($germany->standard_rate),
            'net' => $money($example->net),
            'vat' => $money($example->vat),
            'gross' => $money($example->gross),
        ] : null;

        $frenchRates = $france
            ? collect([...$france->reducedRates(), (float) $france->super_reduced_rate])
                ->filter(fn (float $rate) => $rate > 0)
                ->sortDesc()
                ->map($percent)
                ->values()
            : collect();
        $reduced = $frenchRates->isEmpty() ? null : [
            'country' => $france->name,
            'rates' => $frenchRates->count() > 1
                ? $frenchRates->slice(0, -1)->implode(', ').' and '.$frenchRates->last()
                : $frenchRates->first(),
        ];

        $latest = $data['latest_change'];

        return [
            'calculator_count' => $data['calculator_count'],
            'change_count' => $data['change_count'],
            'latest' => $latest && $latest->country ? [
                'country' => $latest->country->name,
                'old' => $percent($latest->old_rate),
                'new' => $percent($latest->new_rate),
                'date' => $latest->change_date->locale(app()->getLocale())->translatedFormat('j F Y'),
            ] : null,
            'worked' => $worked,
            'reduced' => $reduced,
            'germany' => $germany,
            'france' => $france,
            'comparisons' => collect(SiteNavigation::comparisons())->take(5),
            'faqs' => $this->faqs($worked, $reduced, $stats),
        ];
    }

    /**
     * @param  array<string, string>|null  $worked
     * @param  array<string, string>|null  $reduced
     * @param  array<string, mixed>|null  $stats
     * @return list<array{q: string, a: string}>
     */
    private function faqs(?array $worked, ?array $reduced, ?array $stats): array
    {
        $values = [
            ...($worked ?? []),
            'rates' => $reduced['rates'] ?? '',
            'reduced_country' => $reduced['country'] ?? '',
            'lowest_country' => $stats['lowest']->name ?? '',
            'lowest' => $stats ? Country::formatPercent($stats['lowest']->standard_rate) : '',
            'highest_country' => $stats['highest']->name ?? '',
            'highest' => $stats ? Country::formatPercent($stats['highest']->standard_rate) : '',
            'average' => $stats ? Country::formatPercent($stats['average']) : '',
            'count' => $stats['count'] ?? 27,
        ];

        // A question that needs a figure the data does not have is left out rather than shown with a gap.
        $needs = [2 => $worked, 3 => $worked, 4 => $reduced, 5 => $stats];

        return collect(__('landing.faq.items'))
            ->reject(fn (array $item, int $index) => array_key_exists($index, $needs) && ! $needs[$index])
            ->map(fn (array $item) => [
                'q' => $item['q'],
                'a' => strtr($item['a'], collect($values)->mapWithKeys(fn ($value, $key) => [':'.$key => (string) $value])->all()),
            ])
            ->values()
            ->all();
    }
}
