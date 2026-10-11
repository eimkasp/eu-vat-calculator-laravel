<?php

namespace App\Support\Seo;

class MetaTitle
{
    public const MAX_LENGTH = 60;

    /**
     * Drops a trailing brand suffix ("… | EU VAT Info") when it pushes the title past the length search results show,
     * so the words that describe the page stay visible. Titles that fit, or that have no brand suffix, are unchanged.
     *
     * @param  array<int, string>  $brands
     */
    public static function fit(string $title, array $brands = []): string
    {
        $title = trim($title);

        if (mb_strlen($title) <= self::MAX_LENGTH) {
            return $title;
        }

        foreach (array_unique(array_filter([...$brands, 'EU VAT Info'])) as $brand) {
            $pattern = '/\s+[|–—-]\s+'.preg_quote($brand, '/').'$/u';

            if (preg_match($pattern, $title)) {
                return rtrim(preg_replace($pattern, '', $title));
            }
        }

        return $title;
    }
}
