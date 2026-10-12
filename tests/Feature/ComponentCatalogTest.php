<?php

use App\Livewire\Styleguide;
use App\Support\DesignSystem\ComponentCatalog;

it('describes every styleguide example exactly once', function () {
    $files = collect(glob(resource_path('views/styleguide/examples/*.blade.php')))
        ->map(fn (string $path) => basename($path, '.blade.php'))
        ->sort()->values()->all();

    expect(collect(array_keys(ComponentCatalog::EXAMPLES))->sort()->values()->all())->toBe($files);
});

it('places every example in a styleguide section with a known canvas', function () {
    $sections = collect(Styleguide::SECTIONS)->flatMap(fn (array $ids) => array_keys($ids))->all();

    foreach (ComponentCatalog::EXAMPLES as $name => $example) {
        expect($example['section'])->toBeIn($sections, "{$name} names an unknown section")
            ->and($example['canvas'])->toBeIn(['surface', 'subtle', 'workspace', 'brand'])
            ->and($example['title'])->not->toBeEmpty();
    }

    expect(array_keys(ComponentCatalog::sections()))->toContain('country', 'buttons', 'results');
});

it('shows the country page pieces on the styleguide without reading the database', function () {
    $this->get('/styleguide')
        ->assertOk()
        ->assertSee('Country pages')
        ->assertSee('In force since 1 Jan 2021, replacing 16%')
        ->assertSee('Germany VAT on common amounts')
        ->assertSee('Questions about VAT in Germany')
        ->assertSee('aria-label="Example breadcrumb"', false);
});
