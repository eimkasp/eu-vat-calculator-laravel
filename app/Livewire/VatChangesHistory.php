<?php

namespace App\Livewire;

use App\Models\Country;
use App\Models\VatRateChange;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class VatChangesHistory extends Component
{
    use WithPagination;

    public const RATE_TYPES = ['standard', 'reduced', 'super_reduced', 'parking'];

    public const DIRECTIONS = ['increase', 'decrease'];

    #[Url(as: 'country', except: '')]
    public string $selectedCountry = '';

    #[Url(as: 'type', except: '')]
    public string $selectedType = '';

    #[Url(as: 'direction', except: '')]
    public string $selectedDirection = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['selectedCountry', 'selectedType', 'selectedDirection'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('selectedCountry', 'selectedType', 'selectedDirection');
        $this->resetPage();
    }

    /**
     * @return list<array{slug: string, name: string, iso: string}>
     */
    #[Computed]
    public function countries(): array
    {
        return Cache::remember('vat_changes_countries_v2', 3600, fn () => Country::query()
            ->where('is_eu_member', true)
            ->orderBy('name')
            ->get(['name', 'slug', 'iso_code'])
            ->map(fn (Country $country) => ['slug' => $country->slug, 'name' => $country->name, 'iso' => $country->iso_code])
            ->all());
    }

    /**
     * @return array{total: int, increases: int, decreases: int, upcoming: int, latest: ?string}
     */
    #[Computed]
    public function summary(): array
    {
        return Cache::remember('vat_changes_summary_v1', 900, function () {
            $changes = VatRateChange::query()->whereHas('country', fn (Builder $query) => $query->where('is_eu_member', true));

            return [
                'total' => (clone $changes)->count(),
                'increases' => (clone $changes)->where('change_direction', 'increase')->count(),
                'decreases' => (clone $changes)->where('change_direction', 'decrease')->count(),
                'upcoming' => (clone $changes)->whereDate('change_date', '>', now())->count(),
                'latest' => (clone $changes)->whereDate('change_date', '<=', now())->max('change_date'),
            ];
        });
    }

    /**
     * Countries ordered from the fewest to the most recorded changes.
     *
     * @return list<array{name: string, slug: string, iso: string, changes: int, stability: string, history: bool}>
     */
    /**
     * The rate types that have recorded changes, so the filter offers no option that always comes back empty.
     *
     * @return list<string>
     */
    #[Computed]
    public function rateTypes(): array
    {
        $recorded = VatRateChange::query()->distinct()->pluck('rate_type')->all();

        return array_values(array_intersect(self::RATE_TYPES, $recorded));
    }

    #[Computed]
    public function stability(): array
    {
        return Cache::remember('vat_change_stability_v2', 3600, fn () => Country::query()
            ->where('is_eu_member', true)
            ->withCount('vatRateChanges')
            ->withExists(['vatRates', 'vatRateChanges'])
            ->orderBy('vat_rate_changes_count')
            ->orderBy('name')
            ->get()
            ->map(fn (Country $country) => [
                'name' => $country->name,
                'slug' => $country->slug,
                'iso' => $country->iso_code,
                'changes' => (int) $country->vat_rate_changes_count,
                'stability' => match (true) {
                    $country->vat_rate_changes_count <= 2 => 'excellent',
                    $country->vat_rate_changes_count <= 5 => 'good',
                    $country->vat_rate_changes_count <= 10 => 'moderate',
                    default => 'frequent',
                },
                'history' => $country->hasVatHistory(),
            ])
            ->all());
    }

    public function hasActiveFilters(): bool
    {
        return $this->selectedCountry !== '' || $this->selectedType !== '' || $this->selectedDirection !== '';
    }

    public function paginationView(): string
    {
        return 'pagination.livewire';
    }

    public function render()
    {
        $changes = VatRateChange::query()
            ->with('country:id,name,slug,iso_code')
            ->whereHas('country', fn (Builder $query) => $query->where('is_eu_member', true))
            ->when($this->selectedCountry !== '', fn (Builder $query) => $query->whereHas('country', fn (Builder $country) => $country->where('slug', $this->selectedCountry)))
            ->when(in_array($this->selectedType, self::RATE_TYPES, true), fn (Builder $query) => $query->where('rate_type', $this->selectedType))
            ->when(in_array($this->selectedDirection, self::DIRECTIONS, true), fn (Builder $query) => $query->where('change_direction', $this->selectedDirection))
            ->orderByDesc('change_date')
            ->orderBy('id')
            ->paginate(20);

        $datasetModified = VatRateChange::query()
            ->whereHas('country', fn (Builder $query) => $query->where('is_eu_member', true))
            ->max('updated_at');

        $firstChange = VatRateChange::query()
            ->whereHas('country', fn (Builder $query) => $query->where('is_eu_member', true))
            ->min('change_date');

        return view('livewire.vat-changes-history', [
            'changes' => $changes,
            'hasFilters' => $this->hasActiveFilters(),
            'datasetModified' => $datasetModified ? CarbonImmutable::parse($datasetModified) : null,
            'firstChangeYear' => $firstChange ? CarbonImmutable::parse($firstChange)->year : null,
        ]);
    }
}
