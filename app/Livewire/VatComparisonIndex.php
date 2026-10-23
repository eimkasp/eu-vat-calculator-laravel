<?php

namespace App\Livewire;

use App\Support\SiteNavigation;
use Livewire\Component;

class VatComparisonIndex extends Component
{
    public function render()
    {
        return view('livewire.vat-comparison-index', [
            'comparisons' => SiteNavigation::comparisons(),
        ]);
    }
}
