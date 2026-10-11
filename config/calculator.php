<?php

return [
    /*
     * Where the rates come from, linked next to every rate the site shows: the European Commission for member states,
     * and the open dataset the rate history is synced from for the other countries.
     */
    'rates_source_url' => 'https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/vat-rates_en',
    'dataset_source_url' => 'https://github.com/kdeldycke/vat-rates',

    'additional_country_slugs' => [
        'iceland',
        'norway',
        'switzerland',
        'turkey',
        'united-kingdom',
    ],

    'groups' => [
        'eu' => 'European Union',
        'other_europe' => 'Other European countries',
    ],
];
