<?php

use App\Support\DesignSystem\ComponentCatalog;
use App\Support\DesignSystem\StorybookExport;

it('keeps the Storybook stories in step with the styleguide examples', function () {
    $this->artisan('design-system:storybook', ['--check' => true])
        ->expectsOutputToContain('up to date')
        ->assertSuccessful();
});

it('links the generated tokens to the public styleguide whatever APP_URL is', function () {
    config(['app.url' => 'http://127.0.0.1:9999']);
    url()->useOrigin('http://127.0.0.1:9999');

    expect(StorybookExport::files()['storybook/generated/design.tokens.json'])
        ->toContain(config('seo.canonical_url').'/styleguide')
        ->not->toContain('127.0.0.1');
});

it('writes one story per example, rendered through Blade without the site origin', function () {
    $files = StorybookExport::files();
    $examples = json_decode($files['storybook/generated/examples.json'], true)['examples'];

    expect(array_keys($examples))->toBe(array_keys(ComponentCatalog::EXAMPLES));

    foreach (ComponentCatalog::sections() as $id => $section) {
        $story = $files['storybook/stories/'.strtolower($section['group']).'/'.$id.'.stories.js'];

        expect($story)->toContain('title: "'.$section['group'].'/'.$section['title'].'"');

        foreach ($section['examples'] as $name => $example) {
            expect($story)->toContain('renderExample("'.$name.'")');
        }
    }

    expect($examples['country-heading']['html'])
        ->toContain('Germany VAT calculator')
        ->toContain('src="/images/flags/de.svg"')
        ->not->toContain(url('/').'/')
        ->not->toContain('[if BLOCK]');
});

it('exports the Design Tokens the styleguide serves', function () {
    $tokens = json_decode(StorybookExport::files()['storybook/generated/design.tokens.json'], true);

    expect($tokens['color']['semantic']['ink']['$extensions']['io.businesspress.vat']['cssVariable'])->toBe('--ui-ink');
    $this->get('/styleguide/design.tokens.json')->assertOk()->assertJsonPath('color.semantic.ink.$value.hex', $tokens['color']['semantic']['ink']['$value']['hex']);
});
