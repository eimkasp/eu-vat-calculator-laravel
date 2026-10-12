<?php

namespace App\Support;

use App\Models\Country;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Turns resources/images/europe.svg into an accessible choropleth: every country with data gets a
 * sequential colour bucket, a focusable button role and an accessible name, so the map is coloured
 * before any JavaScript runs and can be explored with a keyboard.
 */
final class EuropeMapSvg
{
    /** Lower bounds of the standard-rate buckets, lowest first. */
    public const BUCKETS = [0, 20, 21, 23, 25];

    /** Malta is too small for the source map, so it is drawn as a marker south of Sicily. */
    private const MALTA_MARKER = ['cx' => 588.2, 'cy' => 632.2, 'r' => 3.4];

    public static function bucket(float $rate): int
    {
        $bucket = 0;

        foreach (self::BUCKETS as $index => $lowerBound) {
            if ($rate >= $lowerBound) {
                $bucket = $index;
            }
        }

        return $bucket;
    }

    /**
     * @return list<string>
     */
    public static function bucketLabels(): array
    {
        return ['< 20%', '20%', '21–22%', '23–24%', '≥ 25%'];
    }

    /**
     * @param  Collection<int, Country>  $countries
     */
    public static function render(Collection $countries, string $labelledBy): string
    {
        $version = md5($countries->map(fn (Country $country) => $country->iso_code.':'.$country->standard_rate.':'.$country->slug)->implode('|'));

        $markup = Cache::remember("europe_map_svg_v4_{$version}_".app()->getLocale(), 3600, function () use ($countries) {
            $byIso = $countries->keyBy(fn (Country $country) => strtoupper((string) $country->iso_code));
            $source = (string) file_get_contents(resource_path('images/europe.svg'));
            $seen = [];

            $svg = (string) preg_replace('/^<\?xml[^>]*>\s*/', '', $source);
            $svg = (string) preg_replace('/<circle\b[^>]*>\s*<\/circle>/', '', $svg);
            $svg = (string) preg_replace_callback(
                '/<path d="([^"]+)"\s+id="([A-Z]{2})"\s+name="([^"]*)">\s*<\/path>/',
                function (array $match) use ($byIso, &$seen) {
                    [$all, $d, $iso, $name] = $match;
                    $duplicate = isset($seen[$iso]);
                    $seen[$iso] = true;

                    return self::region($d, $iso, $byIso->get($iso), $name, $duplicate);
                },
                $svg,
            );

            if ($malta = $byIso->get('MT')) {
                $marker = self::MALTA_MARKER;
                $svg = str_replace('</svg>', self::marker($marker, $malta).'</svg>', $svg);
            }

            return (string) preg_replace('/<svg\b[^>]*>/', '<svg class="eu-map" viewBox="130 110 870 560" xmlns="http://www.w3.org/2000/svg" role="group" aria-labelledby="__LABEL__">', $svg, 1);
        });

        return str_replace('__LABEL__', e($labelledBy), $markup);
    }

    private static function region(string $d, string $iso, ?Country $country, string $fallbackName, bool $duplicate): string
    {
        if (! $country) {
            return '<path d="'.$d.'" class="eu-map-nodata"><title>'.e($fallbackName).'</title></path>';
        }

        $attributes = self::attributes($country);

        if ($duplicate) {
            return '<path d="'.$d.'" class="eu-map-region eu-map-b'.self::bucket((float) $country->standard_rate).'" data-iso="'.$iso.'" aria-hidden="true"></path>';
        }

        return '<path d="'.$d.'" '.$attributes.'></path>';
    }

    private static function marker(array $marker, Country $country): string
    {
        return '<circle cx="'.$marker['cx'].'" cy="'.$marker['cy'].'" r="'.$marker['r'].'" '.self::attributes($country).'></circle>';
    }

    private static function attributes(Country $country): string
    {
        $iso = strtoupper((string) $country->iso_code);
        $rate = Country::formatRate($country->standard_rate);

        return implode(' ', [
            'class="eu-map-region eu-map-b'.self::bucket((float) $country->standard_rate).'"',
            'data-iso="'.$iso.'"',
            'tabindex="0"',
            'role="button"',
            'aria-label="'.e($country->name.', '.__('ui.rate_type.standard').' '.$rate.'%').'"',
        ]);
    }
}
