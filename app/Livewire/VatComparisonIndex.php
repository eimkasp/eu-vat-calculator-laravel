<?php

namespace App\Livewire;

use App\Services\Seo\InternalLinkService;
use App\Support\SiteNavigation;
use Illuminate\Http\Request;
use Livewire\Attributes\Locked;
use Livewire\Component;

class VatComparisonIndex extends Component
{
    /** @var list<string> The picker's three fields in order; '' for an empty or unrecognised choice. */
    #[Locked]
    public array $selected = [];

    /** @var list<int> Fields that caused the error: an empty required choice or a country chosen twice. */
    #[Locked]
    public array $invalid = [];

    #[Locked]
    public ?string $error = null;

    /**
     * The picker submits ?countries[]=…; a valid choice of two or three different countries redirects to its one
     * canonical comparison URL, anything else comes back here with the choice kept and the fields at fault marked.
     */
    public function mount(Request $request, InternalLinkService $links)
    {
        if (! $request->has('countries')) {
            return;
        }

        // Keep each field in its position. Only countries the picker offers count; anything else reads as empty.
        $comparable = SiteNavigation::comparableCountries()->pluck('slug')->all();
        $this->selected = collect(range(0, InternalLinkService::MAX_COMPARED - 1))
            ->map(fn (int $index) => $request->query('countries')[$index] ?? '')
            ->map(fn ($slug) => is_string($slug) && in_array($slug, $comparable, true) ? $slug : '')
            ->all();

        $chosen = array_values(array_unique(array_filter($this->selected, 'strlen')));
        $canonical = $links->canonicalComparison($chosen);

        if ($canonical && $links->comparableCountriesExist($canonical)) {
            return redirect()->to(locale_path(InternalLinkService::comparisonPath($canonical)));
        }

        $counts = array_count_values(array_filter($this->selected, 'strlen'));
        foreach ($this->selected as $index => $slug) {
            if (($index < 2 && $slug === '') || ($slug !== '' && $counts[$slug] > 1)) {
                $this->invalid[] = $index;
            }
        }

        $this->error = __('ui.compare_index.error');
    }

    public function render()
    {
        return view('livewire.vat-comparison-index', [
            'comparisons' => SiteNavigation::comparisons(),
        ]);
    }
}
