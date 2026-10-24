<?php

namespace App\Livewire;

use App\Models\Country;
use App\Services\Seo\InternalLinkService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class VatComparison extends Component
{
    /** @var list<string> Country slugs in canonical order: two or three. */
    #[Locked]
    public array $slugs = [];

    public function mount(string $pair, InternalLinkService $links)
    {
        $requested = explode('-vs-', $pair);
        $canonical = $links->canonicalComparison($requested);
        abort_unless($canonical && $links->comparableCountriesExist($canonical), 404);

        if ($requested !== $canonical) {
            return redirect()->to(locale_path(InternalLinkService::comparisonPath($canonical)), 301);
        }

        $this->slugs = $canonical;
    }

    public function render(InternalLinkService $links)
    {
        $countries = Country::calculatorAvailable()
            ->whereIn('slug', $this->slugs)
            ->withCount('vatRateChanges')
            ->get()
            ->sortBy(fn (Country $country) => array_search($country->slug, $this->slugs, true))
            ->values();

        abort_unless(in_array($countries->count(), [2, InternalLinkService::MAX_COMPARED], true), 404);

        return view('livewire.vat-comparison', [
            'countries' => $countries,
            'indexable' => $links->isApprovedComparison($this->slugs),
        ]);
    }
}
