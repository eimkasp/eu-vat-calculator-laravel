<?php

/*
 * VAT identification number formats of the EU member states, as the European Commission publishes them for VIES.
 * Masks use the Commission's notation: 9 is a digit, L a letter and X a letter or a digit; any other character is
 * literal. The pattern matches a number written without spaces or punctuation. A number that does not match is not
 * rejected: VIES remains the authority, the format only helps people spot a typing mistake.
 */

$sourceUrl = 'https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/vat-identification-numbers_en';

$formats = [
    'AT' => ['masks' => ['ATU99999999'], 'pattern' => '^ATU\d{8}$'],
    'BE' => ['masks' => ['BE9999999999'], 'pattern' => '^BE[01]\d{9}$'],
    'BG' => ['masks' => ['BG999999999', 'BG9999999999'], 'pattern' => '^BG\d{9,10}$'],
    'HR' => ['masks' => ['HR99999999999'], 'pattern' => '^HR\d{11}$'],
    'CY' => ['masks' => ['CY99999999L'], 'pattern' => '^CY\d{8}[A-Z]$'],
    'CZ' => ['masks' => ['CZ99999999', 'CZ999999999', 'CZ9999999999'], 'pattern' => '^CZ\d{8,10}$'],
    'DK' => ['masks' => ['DK99999999'], 'pattern' => '^DK\d{8}$'],
    'EE' => ['masks' => ['EE999999999'], 'pattern' => '^EE\d{9}$'],
    'FI' => ['masks' => ['FI99999999'], 'pattern' => '^FI\d{8}$'],
    'FR' => ['masks' => ['FRXX999999999'], 'pattern' => '^FR[0-9A-Z]{2}\d{9}$'],
    'DE' => ['masks' => ['DE999999999'], 'pattern' => '^DE\d{9}$'],
    'GR' => ['masks' => ['EL999999999'], 'pattern' => '^EL\d{9}$'],
    'HU' => ['masks' => ['HU99999999'], 'pattern' => '^HU\d{8}$'],
    'IE' => ['masks' => ['IE9999999L', 'IE9999999LL', 'IE9X99999L'], 'pattern' => '^IE(\d{7}[A-Z]{1,2}|\d[A-Z+*]\d{5}[A-Z])$'],
    'IT' => ['masks' => ['IT99999999999'], 'pattern' => '^IT\d{11}$'],
    'LV' => ['masks' => ['LV99999999999'], 'pattern' => '^LV\d{11}$'],
    'LT' => ['masks' => ['LT999999999', 'LT999999999999'], 'pattern' => '^LT(\d{9}|\d{12})$'],
    'LU' => ['masks' => ['LU99999999'], 'pattern' => '^LU\d{8}$'],
    'MT' => ['masks' => ['MT99999999'], 'pattern' => '^MT\d{8}$'],
    'NL' => ['masks' => ['NL999999999B99'], 'pattern' => '^NL\d{9}B\d{2}$'],
    'PL' => ['masks' => ['PL9999999999'], 'pattern' => '^PL\d{10}$'],
    'PT' => ['masks' => ['PT999999999'], 'pattern' => '^PT\d{9}$'],
    'RO' => ['masks' => ['RO99', 'RO9999999999'], 'range' => true, 'pattern' => '^RO\d{2,10}$'],
    'SK' => ['masks' => ['SK9999999999'], 'pattern' => '^SK\d{10}$'],
    'SI' => ['masks' => ['SI99999999'], 'pattern' => '^SI\d{8}$'],
    'ES' => ['masks' => ['ESX9999999X'], 'pattern' => '^ES[0-9A-Z]\d{7}[0-9A-Z]$'],
    'SE' => ['masks' => ['SE999999999901'], 'pattern' => '^SE\d{10}01$'],
];

return collect($formats)->map(fn (array $format, string $code) => $format + [
    'prefix' => $code === 'GR' ? 'EL' : $code,
    'range' => false,
    'source_url' => $sourceUrl,
])->all();
