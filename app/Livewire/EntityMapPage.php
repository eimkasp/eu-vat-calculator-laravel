<?php

namespace App\Livewire;

use App\Support\EntityMap\EntityMap;
use App\Support\Seo\DatasetSchema;
use Livewire\Component;

/**
 * /entity-map: one page that defines every entity of EU VAT Info once, each backed by passages from the site itself.
 */
class EntityMapPage extends Component
{
    public function render()
    {
        $entities = collect(EntityMap::entities());

        return view('livewire.entity-map', [
            'sections' => collect(EntityMap::SECTIONS)->map(fn (string $title, string $key) => [
                'title' => $title,
                'entities' => $entities->where('section', $key)->values(),
            ])->filter(fn (array $section) => $section['entities']->isNotEmpty()),
            'countries' => EntityMap::countries(),
            'graph' => EntityMap::graph(),
            'dateModified' => DatasetSchema::ratesModified(),
        ]);
    }
}
