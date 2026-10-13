<?php

namespace App\Support\Vat;

use App\Models\Country;
use App\Models\VatRateChange;

/**
 * The questions a country calculator page answers, written from the country's own data so the visible FAQ and its
 * FAQPage structured data always agree.
 */
final class CountryFaq
{
    /**
     * @return list<array{q: string, a: string}>
     */
    public static function for(Country $country): array
    {
        $rate = (float) $country->standard_rate;
        $rateLabel = Country::formatNumber($rate);
        $currency = $country->currencyCode();
        $add = VatCalculation::addVat(100, $rate);
        $remove = VatCalculation::removeVat(100, $rate);
        $multiplier = Country::formatNumber(1 + $rate / 100, 4);
        $reduced = $country->reducedRates() === [] ? null : implode(', ', array_map(fn (float $value) => Country::formatPercent($value), $country->reducedRates()));
        $replace = ['country' => $country->name, 'rate' => $rateLabel];

        $faqs = [[
            'q' => __('ui.calculator.faq_rate_q', $replace),
            'a' => $reduced
                ? __('ui.calculator.faq_rate_a', $replace + ['reduced' => $reduced])
                : __('ui.calculator.faq_rate_a_standard_only', $replace),
        ], [
            'q' => __('ui.calculator.faq_add_q', $replace),
            'a' => __('ui.calculator.faq_add_a', $replace + [
                'multiplier' => $multiplier,
                'net' => Money::format($add->net, $currency),
                'gross' => Money::format($add->gross, $currency),
            ]),
        ], [
            'q' => __('ui.calculator.faq_remove_q', $replace),
            'a' => __('ui.calculator.faq_remove_a', $replace + [
                'multiplier' => $multiplier,
                'gross' => Money::format($remove->gross, $currency),
                'net' => Money::format($remove->net, $currency),
            ]),
        ]];

        $change = $country->is_eu_member
            ? VatRateChange::query()
                ->where('country_id', $country->id)
                ->where('rate_type', 'standard')
                ->whereDate('change_date', '<=', today())
                ->orderByDesc('change_date')
                ->first()
            : null;

        if ($change && abs((float) $change->new_rate - $rate) < 0.001) {
            $faqs[] = [
                'q' => __('ui.calculator.faq_changed_q', $replace),
                'a' => __('ui.calculator.faq_changed_a', $replace + [
                    'old' => Country::formatNumber($change->old_rate),
                    'new' => Country::formatNumber($change->new_rate),
                    'date' => $change->change_date->locale(app()->getLocale())->translatedFormat('j F Y'),
                ]),
            ];
        }

        $format = $country->vies_available ? config('vat-number-formats.'.strtoupper((string) $country->iso_code)) : null;

        if ($format) {
            $faqs[] = [
                'q' => __('ui.calculator.faq_number_q', $replace),
                'a' => __('ui.calculator.faq_number_a', $replace + [
                    'prefix' => $format['prefix'],
                    'format' => VatNumberFormat::describe($format),
                ]),
            ];
        }

        return $faqs;
    }
}
