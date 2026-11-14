<?php

/**
 * Builds the app's strings from the website's language files, so the app speaks the same 24 languages
 * with the same wording and nothing is translated twice.
 *
 *     php ios/scripts/sync-strings.php
 *
 * It writes EUVATInfo/Resources/Localizable.xcstrings and EUVATInfo/Generated/L10n.swift. Add a string by
 * listing its key from lang/en/ui.php below; a key with placeholders lists them in the order the Swift
 * function takes them. Strings that exist only in the app go in APP_ONLY, in every language.
 */
$repo = dirname(__DIR__, 2);
$ios = dirname(__DIR__);

/** Web locale => Xcode language code. */
const LANGUAGES = [
    'en' => 'en', 'bg' => 'bg', 'cs' => 'cs', 'da' => 'da', 'de' => 'de', 'el' => 'el', 'es' => 'es', 'et' => 'et',
    'fi' => 'fi', 'fr' => 'fr', 'ga' => 'ga', 'hr' => 'hr', 'hu' => 'hu', 'it' => 'it', 'lt' => 'lt', 'lv' => 'lv',
    'mt' => 'mt', 'nl' => 'nl', 'pl' => 'pl', 'pt' => 'pt-PT', 'ro' => 'ro', 'sk' => 'sk', 'sl' => 'sl', 'sv' => 'sv',
];

/** Key in lang/{locale}/ui.php => its placeholders, in the order of the Swift arguments. */
const KEYS = [
    // Shared
    'site_name' => [],
    'countries' => [],
    'country' => [],
    'search' => [],
    'close' => [],
    'filters' => [],
    'no_results' => [],
    'trust.updated' => ['date'],
    'trust.source_ec' => [],
    'trust.source_dataset' => [],
    'trust.official_ec_data' => [],
    'stats.last_updated' => [],
    'errors.data_load_failed_title' => [],
    'calculator.unavailable_desc' => [],

    // Tabs
    'bottom_nav.calculator' => [],
    'bottom_nav.validator' => [],
    'bottom_nav.history' => [],
    'compare_index.submit' => [],

    // Rates and country
    'country_page.vat_rates' => [],
    'calculator.search_countries' => [],
    'calculator.groups.eu' => [],
    'calculator.groups.other_europe' => [],
    'calculator.scope.eu' => [],
    'calculator.scope.other_europe' => [],
    'home_page.stat_highest' => [],
    'home_page.stat_lowest' => [],
    'country_page.standard_rate' => [],
    'country_page.reduced_rate' => [],
    'country_page.super_reduced' => [],
    'country_page.parking_rate' => [],
    'country_page.key_information' => [],
    'country_page.country_code' => [],
    'country_page.currency' => [],
    'country_page.eu_member' => [],
    'country_page.yes' => [],
    'country_page.no' => [],
    'country_page.vies_validation' => [],
    'country_page.available' => [],
    'country_page.not_available' => [],
    'country_page.vat_calculator' => [],
    'country_page.validate_vat_cta' => ['country'],
    'country_page.compare_with' => ['country'],
    'calculator.vat_number_format' => [],
    'calculator.view_vat_guide' => ['country'],
    'vies_page.format_legend' => [],
    'vies_page.format_note_gr' => [],
    'history.rate_history' => [],

    // Calculator
    'calculator.title' => [],
    'calculator.select_country' => [],
    'calculator.no_countries_found' => [],
    'calculator.mode_label' => [],
    'calculator.add_vat_mode' => [],
    'calculator.remove_vat_mode' => [],
    'calculator.amount_excl_vat' => [],
    'calculator.amount_incl_vat' => [],
    'calculator.vat_rate' => [],
    'calculator.standard_rate_label' => ['rate'],
    'calculator.reduced_rate_label' => ['rate'],
    'calculator.super_reduced_rate_label' => ['rate'],
    'calculator.parking_rate_label' => ['rate'],
    'calculator.custom_label' => [],
    'calculator.custom_percent' => [],
    'calculator.enter_custom' => [],
    'calculator.net_amount' => [],
    'calculator.vat_label' => ['rate'],
    'calculator.total_incl_vat' => [],
    'calculator.summary_add' => ['amount', 'rate', 'country'],
    'calculator.summary_remove' => ['amount', 'rate', 'country'],
    'calculator.invalid_amount' => [],
    'calculator.copy_result' => [],
    'calculator.copied' => [],
    'calculator.clear_all' => [],
    'calculator.formula_add' => ['multiplier'],
    'calculator.formula_remove' => ['multiplier'],

    // Validator
    'vies_page.nav_title' => [],
    'vies_page.vat_number_label' => [],
    'vies_page.vat_placeholder' => [],
    'vies_page.auto_detect_hint' => [],
    'vies_page.detected_country' => [],
    'vies_page.validate_btn' => [],
    'vies_page.validating' => [],
    'vies_page.result_valid' => [],
    'vies_page.result_invalid' => [],
    'vies_page.result_valid_desc' => ['country'],
    'vies_page.result_invalid_desc' => [],
    'vies_page.field_company' => [],
    'vies_page.field_address' => [],
    'vies_page.field_request_id' => [],
    'vies_page.field_source' => [],
    'vies_page.source_live' => [],
    'vies_page.source_recent' => [],
    'vies_page.source_fallback' => [],
    'vies_page.checked_on' => ['date'],
    'vies_page.not_provided' => [],
    'vies_page.copy_number' => [],
    'vies_page.copied' => [],
    'vies_page.invalid_format' => [],
    'vies_page.unsupported_country' => [],
    'vies_page.rate_limited' => [],
    'vies_page.service_unavailable' => [],
    'vies_page.format_rejected' => ['country'],
    'vies_page.format_mismatch' => ['country', 'format'],
    'vies_page.recent_heading' => [],
    'vies_page.recent_privacy' => [],
    'vies_page.disclaimer' => [],

    // Comparison
    'nav.compare' => [],
    'compare_index.picker_heading' => [],
    'compare_index.choose' => [],
    'compare_index.error' => [],
    'compare.reduced_rates' => [],
    'compare.super_reduced' => [],
    'compare.parking' => [],
    'compare.currency' => [],
    'compare.vies' => [],
    'compare.none' => [],
    'compare.example_heading' => [],
    'compare.example_note' => [],
    'compare.higher_by' => ['points'],
    'compare.same_rate' => [],
    'compare.disclaimer' => [],

    // History
    'nav.vat_history' => [],
    'history.all_countries' => [],
    'history.direction' => [],
    'history.all_directions' => [],
    'history.increases' => [],
    'history.decreases' => [],
    'history.changed_on' => ['date'],
    'history.effective_from' => ['date'],
    'history.upcoming_badge' => [],
    'history.no_changes' => [],
    'history.clear_filters' => [],
    'history.standard_rate' => [],
    'history.reduced_rate' => [],
    'history.super_reduced_rate' => [],
    'history.parking_rate' => [],
    'rate_changes.increase' => [],
    'rate_changes.decrease' => [],
    'rate_changes.points' => ['value'],

    // About
    'sitemap.about_title' => [],
    'sitemap.github_repo' => [],
    'footer.privacy' => [],
];

/** Strings the website has no use for. Key => web locale => text. */
const APP_ONLY = [
    'app.try_again' => [
        'en' => 'Try again', 'bg' => 'Опитайте отново', 'cs' => 'Zkusit znovu', 'da' => 'Prøv igen',
        'de' => 'Erneut versuchen', 'el' => 'Δοκιμάστε ξανά', 'es' => 'Reintentar', 'et' => 'Proovi uuesti',
        'fi' => 'Yritä uudelleen', 'fr' => 'Réessayer', 'ga' => 'Bain triail eile as', 'hr' => 'Pokušaj ponovno',
        'hu' => 'Próbálja újra', 'it' => 'Riprova', 'lt' => 'Bandyti dar kartą', 'lv' => 'Mēģināt vēlreiz',
        'mt' => 'Erġa\' pprova', 'nl' => 'Opnieuw proberen', 'pl' => 'Spróbuj ponownie', 'pt' => 'Tentar novamente',
        'ro' => 'Încercați din nou', 'sk' => 'Skúsiť znova', 'sl' => 'Poskusi znova', 'sv' => 'Försök igen',
    ],
];

function lookup(array $strings, string $key): ?string
{
    foreach (explode('.', $key) as $segment) {
        if (! is_array($strings) || ! array_key_exists($segment, $strings)) {
            return null;
        }

        $strings = $strings[$segment];
    }

    return is_string($strings) ? $strings : null;
}

/** Turns ":rate%" into "%1$@%%" so the string works with String(format:). */
function toFormat(string $text, array $placeholders, string $key, string $locale): string
{
    $text = trim($text);

    if ($placeholders === []) {
        return $text;
    }

    $text = str_replace('%', '%%', $text);

    foreach ($placeholders as $index => $name) {
        $count = 0;
        $text = preg_replace('/:'.preg_quote($name, '/').'\b/i', '%'.($index + 1).'$@', $text, -1, $count);

        if ($count === 0) {
            fwrite(STDERR, "warning: {$locale} {$key} does not use :{$name}\n");
        }
    }

    return $text;
}

function swiftName(string $key): string
{
    $words = preg_split('/[._]+/', $key);

    return lcfirst(implode('', array_map('ucfirst', $words)));
}

$failures = [];
$catalog = ['sourceLanguage' => 'en', 'strings' => [], 'version' => '1.0'];
$english = require "{$repo}/lang/en/ui.php";

foreach (LANGUAGES as $locale => $language) {
    $strings = require "{$repo}/lang/{$locale}/ui.php";

    foreach (KEYS as $key => $placeholders) {
        $text = lookup($strings, $key);

        if ($text === null) {
            $failures[] = "{$locale}: {$key} is missing";

            continue;
        }

        if (preg_match('/:[a-z_]+/i', $text, $match) && ! in_array(strtolower(ltrim($match[0], ':')), $placeholders, true) && lookup($english, $key) !== null && str_contains(lookup($english, $key), $match[0])) {
            $failures[] = "{$locale}: {$key} has the placeholder {$match[0]}, which is not listed";
        }

        $catalog['strings'][$key]['extractionState'] = 'manual';
        $catalog['strings'][$key]['localizations'][$language] = [
            'stringUnit' => ['state' => 'translated', 'value' => toFormat($text, $placeholders, $key, $locale)],
        ];
    }

    foreach (APP_ONLY as $key => $translations) {
        if (! isset($translations[$locale])) {
            $failures[] = "{$locale}: {$key} is missing from APP_ONLY";

            continue;
        }

        $catalog['strings'][$key]['extractionState'] = 'manual';
        $catalog['strings'][$key]['localizations'][$language] = [
            'stringUnit' => ['state' => 'translated', 'value' => $translations[$locale]],
        ];
    }
}

if ($failures !== []) {
    fwrite(STDERR, implode("\n", $failures)."\n");
    exit(1);
}

ksort($catalog['strings']);

$swift = "// Generated by ios/scripts/sync-strings.php from lang/*/ui.php. Do not edit by hand.\n\nimport Foundation\n\n";
$swift .= "enum L10n {\n";

foreach (array_merge(KEYS, array_fill_keys(array_keys(APP_ONLY), [])) as $key => $placeholders) {
    $name = swiftName($key);
    $comment = str_replace("\n", ' ', (string) (lookup($english, $key) ?? APP_ONLY[$key]['en']));
    $swift .= "    /// {$comment}\n";

    if ($placeholders === []) {
        $swift .= "    static var {$name}: String { text(\"{$key}\") }\n";

        continue;
    }

    $parameters = implode(', ', array_map(fn (string $placeholder) => "{$placeholder}: String", $placeholders));
    $arguments = implode(', ', $placeholders);
    $swift .= "    static func {$name}({$parameters}) -> String { text(\"{$key}\", {$arguments}) }\n";
}

$swift .= <<<'SWIFT'

    private static func text(_ key: String, _ arguments: String...) -> String {
        let format = Bundle.main.localizedString(forKey: key, value: nil, table: nil)

        return arguments.isEmpty ? format : String(format: format, arguments: arguments)
    }
}

SWIFT;

@mkdir("{$ios}/EUVATInfo/Generated", 0o755, true);
file_put_contents("{$ios}/EUVATInfo/Resources/Localizable.xcstrings", json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
file_put_contents("{$ios}/EUVATInfo/Generated/L10n.swift", $swift);

echo 'Wrote '.count($catalog['strings']).' strings in '.count(LANGUAGES)." languages.\n";
