<?php

/*
 * Every interface string exists in all 24 languages with the same placeholders, so no page falls back to English
 * or prints a raw :placeholder.
 */

function flattenTranslations(array $strings, string $prefix = ''): array
{
    $flat = [];

    foreach ($strings as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
        $flat += is_array($value) ? flattenTranslations($value, $path) : [$path => (string) $value];
    }

    return $flat;
}

function translationPlaceholders(string $value): array
{
    preg_match_all('/(?<![\p{L}\p{N}%]):([a-z_]+)/u', $value, $matches);

    return collect($matches[1])->unique()->sort()->values()->all();
}

it('translates every interface string into every language with the same placeholders', function () {
    $english = flattenTranslations(require lang_path('en/ui.php'));
    $problems = [];

    foreach (array_keys(config('translation.supported_languages')) as $locale) {
        if ($locale === 'en') {
            continue;
        }

        $translated = flattenTranslations(require lang_path($locale.'/ui.php'));

        foreach ($english as $key => $value) {
            if (! array_key_exists($key, $translated)) {
                $problems[] = "{$locale}: missing {$key}";

                continue;
            }

            if (translationPlaceholders($value) !== translationPlaceholders($translated[$key])) {
                $problems[] = "{$locale}: {$key} has placeholders ".implode(', ', translationPlaceholders($translated[$key])).' instead of '.implode(', ', translationPlaceholders($value));
            }
        }
    }

    expect($problems)->toBe([]);
});
