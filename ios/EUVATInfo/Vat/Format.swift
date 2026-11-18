import Foundation

/// Numbers, money and dates written the way the interface language writes them.
enum Format {
    /// "19%" in English, "5,5 %" in French.
    static func percent(_ rate: Double, locale: Locale = AppLocale.current) -> String {
        (rate / 100).formatted(.percent.precision(.fractionLength(0 ... 2)).locale(locale))
    }

    /// The bare number of a rate, for strings that add the percent sign themselves: "19", "5,5".
    static func rateNumber(_ rate: Double, locale: Locale = AppLocale.current) -> String {
        rate.formatted(.number.precision(.fractionLength(0 ... 2)).locale(locale))
    }

    static func money(_ value: Double, currency: String, locale: Locale = AppLocale.current) -> String {
        value.formatted(.currency(code: currency).precision(.fractionLength(2)).locale(locale))
    }

    /// Money without cents, for round amounts in a table: "€1,000".
    static func wholeMoney(_ value: Double, currency: String, locale: Locale = AppLocale.current) -> String {
        value.formatted(.currency(code: currency).precision(.fractionLength(0)).locale(locale))
    }

    /// A round amount on a key: "1,000".
    static func amount(_ value: Double, locale: Locale = AppLocale.current) -> String {
        value.formatted(.number.precision(.fractionLength(0 ... 2)).locale(locale))
    }

    /// A number as it is typed into a field: no grouping, and the decimal separator of the device's number pad.
    static func fieldValue(_ value: Double, locale: Locale = .autoupdatingCurrent) -> String {
        value.formatted(.number.grouping(.never).precision(.fractionLength(0 ... 2)).locale(locale))
    }

    /// Percentage points: "2", "0.5".
    static func points(_ value: Double, locale: Locale = AppLocale.current) -> String {
        value.formatted(.number.precision(.fractionLength(0 ... 2)).locale(locale))
    }

    /// Day first, short month: "1 Aug 2025". English reads day first as well, as it does on the website.
    static func date(_ date: Date, locale: Locale = AppLocale.current) -> String {
        let locale = locale.language.languageCode == "en" ? Locale(identifier: "en_GB") : locale

        return date.formatted(.dateTime.day().month(.abbreviated).year().locale(locale))
    }

    /// The factor a net amount is multiplied by: "1.21", "1.055".
    static func multiplier(_ rate: Double, locale: Locale = AppLocale.current) -> String {
        (1 + rate / 100).formatted(.number.precision(.fractionLength(0 ... 4)).locale(locale))
    }

    /// Several rates, joined the way the language joins a list: "5.5%, 10%".
    static func rates(_ rates: [Double], locale: Locale = AppLocale.current) -> String {
        rates.map { percent($0, locale: locale) }.formatted(.list(type: .and, width: .narrow).locale(locale))
    }
}
