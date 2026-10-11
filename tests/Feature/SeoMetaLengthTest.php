<?php

use App\Models\Country;

/*
 * Search results show about 60 characters of a title and 160 of a description. The pages people land on from
 * search must fit those limits in every language, and say enough to be useful (30 and 70 characters at least).
 */

beforeEach(function () {
    foreach ([['Germany', 'germany', 'DE', 19, 7], ['France', 'france', 'FR', 20, 5.5], ['Czech Republic', 'czech-republic', 'CZ', 21, 12]] as [$name, $slug, $iso, $standard, $reduced]) {
        Country::factory()->create([
            'name' => $name, 'slug' => $slug, 'iso_code' => $iso, 'standard_rate' => $standard, 'reduced_rate' => $reduced,
            'is_eu_member' => true, 'vies_available' => true,
        ]);
    }
});

it('fits search result limits in every language', function (string $path) {
    $problems = [];

    foreach (array_keys(config('translation.supported_languages')) as $locale) {
        $url = $locale === 'en' ? $path : '/'.$locale.($path === '/' ? '' : $path);
        $html = $this->get($url)->assertOk()->getContent();

        preg_match('#<title>(.*?)</title>#s', $html, $title);
        preg_match('#<meta name="description" content="([^"]*)"#', $html, $description);

        $titleLength = mb_strlen(html_entity_decode(trim($title[1] ?? ''), ENT_QUOTES | ENT_HTML5));
        $descriptionLength = mb_strlen(html_entity_decode(trim($description[1] ?? ''), ENT_QUOTES | ENT_HTML5));

        if ($titleLength < 30 || $titleLength > 60) {
            $problems[] = "{$url}: title is {$titleLength} characters";
        }

        if ($descriptionLength < 70 || $descriptionLength > 160) {
            $problems[] = "{$url}: description is {$descriptionLength} characters";
        }
    }

    expect($problems)->toBe([]);
})->with([
    'home' => '/',
    'calculator' => '/vat-calculator',
    'country calculator' => '/vat-calculator/czech-republic',
    'validator' => '/vat-number-validator',
    'country validator' => '/vat-number-validator/czech-republic',
    'VAT map' => '/vat-map',
    'rate history' => '/vat-changes',
    'top calculations' => '/top-vat-calculations',
    'top calculations for one amount' => '/top-vat-calculations/10000',
    'shared calculation' => '/vat-calculation/czech-republic/10000/21/include',
    'MCP server' => '/mcp-server',
    'tools' => '/tools',
]);
