import Foundation

enum AppInfo {
    static let version = Bundle.main.object(forInfoDictionaryKey: "CFBundleShortVersionString") as? String ?? "1.0"
    static let build = Bundle.main.object(forInfoDictionaryKey: "CFBundleVersion") as? String ?? "1"
}

/// The language the interface is shown in. Names, numbers and dates are written the way that language writes
/// them, as on the website: "20%" in English and "20 %" in French, whatever the device's region is.
enum AppLocale {
    static let current = Locale(identifier: Bundle.main.preferredLocalizations.first ?? "en")

    /// The language segment the website uses: nothing for English, "/de" for German.
    static var webPathPrefix: String {
        let code = current.language.languageCode?.identifier ?? "en"

        return code == "en" || !webLanguages.contains(code) ? "" : "/\(code)"
    }

    private static let webLanguages: Set<String> = [
        "bg", "cs", "da", "de", "el", "es", "et", "fi", "fr", "ga", "hr", "hu",
        "it", "lt", "lv", "mt", "nl", "pl", "pt", "ro", "sk", "sl", "sv",
    ]
}

/// Addresses on the website and of the sources the data comes from.
enum AppLinks {
    static let site: URL = {
        let configured = (Bundle.main.object(forInfoDictionaryKey: "SiteURL") as? String).flatMap(URL.init(string:))

        return configured ?? URL(string: "https://vat.businesspress.io")!
    }()

    static let commissionRates = URL(string: "https://taxation-customs.ec.europa.eu/taxation/vat/vat-directive/vat-rates_en")!
    static let dataset = URL(string: "https://github.com/kdeldycke/vat-rates")!
    static let vies = URL(string: "https://ec.europa.eu/taxation_customs/vies/")!
    static let repository = URL(string: "https://github.com/eimkasp/eu-vat.info")!

    static var home: URL { page("/") }
    static var privacy: URL { page("/privacy") }

    static func guide(for country: Country) -> URL {
        page("/vat-calculator/\(country.slug)")
    }

    /// The shareable page of a calculation, written the way `SharedCalculation::calculationUrl` writes it.
    static func calculation(_ calculation: VatCalculation, in country: Country) -> URL {
        page("/vat-calculation/\(country.slug)/\(segment(calculation.input))/\(segment(calculation.rate))/\(calculation.mode.rawValue)")
    }

    /// Two decimals with trailing zeros dropped: 1000, 19, 5.5.
    static func segment(_ value: Double) -> String {
        var text = String(format: "%.2f", locale: Locale(identifier: "en_US_POSIX"), value)

        while text.hasSuffix("0") { text.removeLast() }
        if text.hasSuffix(".") { text.removeLast() }

        return text
    }

    private static func page(_ path: String) -> URL {
        site.appending(path: AppLocale.webPathPrefix + (path == "/" && !AppLocale.webPathPrefix.isEmpty ? "" : path))
    }
}
