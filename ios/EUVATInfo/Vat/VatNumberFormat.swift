import Foundation

/// The shape of VAT numbers, used for a hint before a number is checked. VIES stays the authority.
/// It mirrors `App\Support\Vat\VatNumberFormat`.
enum VatNumberFormat {
    /// A number without spaces, dots, dashes or slashes, in capitals.
    static func clean(_ number: String) -> String {
        number.uppercased().replacing(/[\s.\-\/]+/, with: "")
    }

    /// Whether a number, prefix included, has the usual shape for its country.
    static func matches(_ format: Country.VatNumber, number: String) -> Bool {
        guard let regex = try? Regex(format.pattern) else { return true }

        return clean(number).wholeMatch(of: regex) != nil
    }

    /// Splits "LT100019070512" into the country it belongs to and the national part.
    static func detect(in number: String, among countries: [Country]) -> (country: Country, national: String)? {
        let cleaned = clean(number)

        guard cleaned.count > 2, cleaned.prefix(2).allSatisfy(\.isLetter) else { return nil }

        let prefix = String(cleaned.prefix(2))

        guard let country = countries.first(where: { $0.vatNumber?.prefix == prefix }) else { return nil }

        return (country, String(cleaned.dropFirst(2)))
    }

    /// The national part of a number typed with or without the country's prefix.
    static func national(_ number: String, for format: Country.VatNumber) -> String {
        let cleaned = clean(number)

        return cleaned.hasPrefix(format.prefix) ? String(cleaned.dropFirst(format.prefix.count)) : cleaned
    }
}
