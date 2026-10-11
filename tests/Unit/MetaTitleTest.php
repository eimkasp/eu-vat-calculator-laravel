<?php

use App\Support\Seo\MetaTitle;

it('keeps titles that fit unchanged', function () {
    expect(MetaTitle::fit('Germany VAT Calculator — 19% Standard Rate | EU VAT Info'))
        ->toBe('Germany VAT Calculator — 19% Standard Rate | EU VAT Info');
});

it('drops the brand suffix when it pushes a title past 60 characters', function () {
    expect(MetaTitle::fit('Czech Republic VAT Calculator — 21% Standard Rate | EU VAT Info'))
        ->toBe('Czech Republic VAT Calculator — 21% Standard Rate')
        ->and(MetaTitle::fit('Kalkulator PDV-a za Czech Republic — opća stopa 21% | PDV u EU', ['PDV u EU']))
        ->toBe('Kalkulator PDV-a za Czech Republic — opća stopa 21%');
});

it('leaves long titles without a brand suffix alone', function () {
    $title = str_repeat('VAT on amounts ', 5).'in every country';

    expect(MetaTitle::fit($title))->toBe($title);
});
