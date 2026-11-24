<?php

use App\Models\Country;
use App\Support\EntityMap\EntityMap;
use App\Support\EntityMap\PassageChecker;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    Country::factory()->create(['name' => 'Germany', 'slug' => 'germany', 'iso_code' => 'DE', 'standard_rate' => 19, 'is_eu_member' => true]);
});

it('defines every entity once with a stable anchor', function () {
    $ids = array_column(EntityMap::entities(), 'id');

    expect($ids)->toBe(array_values(array_unique($ids)))
        ->and(array_diff(array_column(EntityMap::entities(), 'section'), array_keys(EntityMap::SECTIONS)))->toBe([]);
});

it('publishes an indexable English entity map with structured data for every entity', function () {
    $response = $this->get('/entity-map')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://eu-vat.info/entity-map">', false)
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('Germany')
        ->assertSee('"@type":"DefinedTermSet"', false)
        ->assertSee('"@id":"https://eu-vat.info/datasets/eu-vat-rates#dataset"', false);

    foreach (EntityMap::entities() as $entity) {
        $response->assertSee('id="'.$entity['id'].'"', false);

        if (! in_array($entity['id'], ['eu-vat-info', 'vat-dataset'], true)) {
            $response->assertSee('https://eu-vat.info/entity-map#'.$entity['id'], false);
        }
    }
});

it('canonicalizes translated URLs to the English page', function () {
    $this->get('/de/entity-map')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://eu-vat.info/entity-map">', false)
        ->assertDontSee('hreflang="de"', false);
});

it('finds every passage on the page it cites', function () {
    $problems = app(PassageChecker::class)->problems(fn (string $path) => $this->get($path)->getContent());

    expect($problems)->toBe([]);
});

it('reports a passage that is no longer on its page', function () {
    $problems = app(PassageChecker::class)->problems(fn (string $path) => '<p>Nothing relevant here.</p>');

    expect($problems)->not->toBeEmpty()->and($problems[0])->toContain('passage not found');
});

it('reports a page that cannot be read', function () {
    expect(app(PassageChecker::class)->problems(fn (string $path) => null))->not->toBeEmpty();
});

it('lists the entity map in llms.txt and the sitemap', function () {
    $this->get('/llms.txt')->assertSee('https://eu-vat.info/entity-map', false);
    $this->get('/sitemaps/core.xml')->assertSee('<loc>https://eu-vat.info/entity-map</loc>', false);
});

it('verifies from the command line', function () {
    $this->artisan('entity-map:verify')->assertSuccessful();
});
