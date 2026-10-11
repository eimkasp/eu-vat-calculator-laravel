<?php

namespace App\Support\Vat;

/**
 * Reads the member state VAT number formats in config/vat-number-formats.php.
 */
final class VatNumberFormat
{
    /**
     * The masks of a format in the Commission's notation, for example "DE999999999" or "RO99 … RO9999999999".
     *
     * @param  array{masks: list<string>, range?: bool}  $format
     */
    public static function describe(array $format): string
    {
        return implode(($format['range'] ?? false) ? ' … ' : ' · ', $format['masks']);
    }

    /**
     * Whether a number, written without spaces or punctuation, has the usual shape for its country.
     *
     * @param  array{pattern: string}  $format
     */
    public static function matches(array $format, string $number): bool
    {
        return preg_match('/'.$format['pattern'].'/', strtoupper((string) preg_replace('/[\s.\-\/]+/', '', $number))) === 1;
    }

    /**
     * Patterns keyed by prefix for the browser, which hints at a typing mistake before the number is checked.
     *
     * @return array<string, array{pattern: string, format: string}>
     */
    public static function forBrowser(): array
    {
        return collect(config('vat-number-formats'))
            ->mapWithKeys(fn (array $format) => [$format['prefix'] => ['pattern' => $format['pattern'], 'format' => self::describe($format)]])
            ->all();
    }
}
