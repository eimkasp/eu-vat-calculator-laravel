<?php

namespace App\Services\Seo;

use App\Models\Country;
use Illuminate\Support\Collection;

class InternalLinkService
{
    public const MAX_COMPARED = 3;

    public function relatedCountries(Country $country, int $limit = 6): Collection
    {
        return Country::query()
            ->where('is_eu_member', true)
            ->whereKeyNot($country->getKey())
            ->orderByRaw('ABS(standard_rate - ?)', [(float) $country->standard_rate])
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    public function relatedCalculatorCountries(Country $country, int $limit = 6): Collection
    {
        return Country::calculatorAvailable()
            ->whereKeyNot($country->getKey())
            ->orderByRaw('ABS(standard_rate - ?)', [(float) $country->standard_rate])
            ->orderByDesc('is_eu_member')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    public function approvedPairsFor(Country $country): Collection
    {
        return collect(config('seo.comparisons', []))
            ->filter(fn (array $pair) => in_array($country->slug, $pair, true))
            ->values();
    }

    /**
     * The canonical slug order for a comparison of two or three countries, or null when the request is not a valid
     * comparison (fewer than two or more than three slugs, or a slug repeated). An approved pair keeps its curated
     * order; every other combination is sorted alphabetically, so each set of countries has exactly one URL.
     *
     * @param  list<string>  $slugs
     * @return list<string>|null
     */
    public function canonicalComparison(array $slugs): ?array
    {
        $slugs = array_values($slugs);

        if (count($slugs) < 2 || count($slugs) > self::MAX_COMPARED || in_array('', $slugs, true) || count(array_unique($slugs)) !== count($slugs)) {
            return null;
        }

        if (count($slugs) === 2 && ($approved = $this->canonicalPair(...$slugs))) {
            return $approved;
        }

        sort($slugs);

        return $slugs;
    }

    /**
     * Whether every slug is a country with a VAT calculator, so the comparison can be shown.
     *
     * @param  list<string>  $slugs
     */
    public function comparableCountriesExist(array $slugs): bool
    {
        return Country::calculatorAvailable()->whereIn('slug', $slugs)->count() === count($slugs);
    }

    /**
     * Whether a canonical comparison is one of the curated pairs in config/seo.php, which are the only ones indexed.
     *
     * @param  list<string>  $slugs
     */
    public function isApprovedComparison(array $slugs): bool
    {
        return count($slugs) === 2 && $this->canonicalPair(...$slugs) === array_values($slugs);
    }

    public static function comparisonPath(array $slugs): string
    {
        return '/compare/'.implode('-vs-', $slugs).'-vat';
    }

    public function canonicalPair(string $left, string $right): ?array
    {
        foreach (config('seo.comparisons', []) as $pair) {
            if ($pair === [$left, $right]) {
                return $pair;
            }

            if ($pair === [$right, $left]) {
                return $pair;
            }
        }

        return null;
    }
}
