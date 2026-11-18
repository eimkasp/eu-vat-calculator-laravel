<?php

$indexableLocales = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('SEO_INDEXABLE_LOCALES', ''))
)));

return [
    'canonical_url' => rtrim(env('SEO_CANONICAL_URL', 'https://eu-vat.info'), '/'),

    'legacy_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('SEO_LEGACY_HOSTS', 'vat.businesspress.io,www.eu-vat.info'))
    ))),

    // Leave SEO_INDEXABLE_LOCALES empty to index every supported language, or list codes ("en,de") to limit it.
    'indexable_locales' => $indexableLocales,

    'category_minimum_country_coverage' => (int) env('SEO_CATEGORY_MIN_COUNTRIES', 3),

    'indexnow' => [
        'enabled' => (bool) env('INDEXNOW_ENABLED', false),
        'key' => env('INDEXNOW_KEY'),
        'endpoint' => env('INDEXNOW_ENDPOINT', 'https://api.indexnow.org/indexnow'),
    ],

    'comparisons' => [
        ['germany', 'france'],
        ['germany', 'netherlands'],
        ['germany', 'austria'],
        ['france', 'belgium'],
        ['spain', 'portugal'],
        ['ireland', 'netherlands'],
        ['sweden', 'denmark'],
        ['finland', 'sweden'],
        ['poland', 'czech-republic'],
        ['italy', 'spain'],
    ],
];
