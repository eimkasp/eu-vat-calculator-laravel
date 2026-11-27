import Foundation

/// Reads amounts the way people type them: "1,234.56", "1.234,56", "1 234,5", "€100" or "12'500.10".
/// It mirrors `App\Support\Vat\AmountParser` (PHP) and `parseAmount()` in resources/js/vat.js.
enum AmountParser {
    /// A single separator followed by exactly three digits is ambiguous ("1,234"), so the locale decides.
    /// The default is the device's own, because that is the separator its number pad types.
    static func parse(_ input: String, locale: Locale = .autoupdatingCurrent) -> Double? {
        parse(input, decimalSeparator: locale.decimalSeparator ?? ".")
    }

    static func parse(_ input: String, decimalSeparator: String) -> Double? {
        var value = input
            .trimmingCharacters(in: .whitespacesAndNewlines)
            .replacing(/[\s\u{00A0}\u{202F}']/, with: "")

        guard !value.isEmpty else { return nil }

        let negative = value.hasPrefix("-") || value.hasSuffix("-")
        // Digits in other scripts are left out on purpose: the API and the website read Western digits only.
        value = String(value.filter { ("0" ... "9").contains($0) || $0 == "." || $0 == "," })

        guard value.contains(where: \.isNumber) else { return nil }

        let lastDot = value.lastIndex(of: ".")
        let lastComma = value.lastIndex(of: ",")

        if let lastDot, let lastComma {
            let decimal: Character = lastDot > lastComma ? "." : ","
            let thousands: Character = decimal == "." ? "," : "."
            value.removeAll { $0 == thousands }

            guard value.filter({ $0 == decimal }).count == 1 else { return nil }

            value = value.replacing(String(decimal), with: ".")
        } else if let last = lastDot ?? lastComma {
            let separator = value[last]
            let occurrences = value.filter { $0 == separator }.count
            let fraction = value[value.index(after: last)...]

            if occurrences > 1 || (fraction.count == 3 && String(separator) != decimalSeparator) {
                value.removeAll { $0 == separator }
            } else {
                value = value.replacing(String(separator), with: ".")
            }
        }

        guard value.wholeMatch(of: /\d+\.?\d*|\.\d+/) != nil, let number = Double(value) else { return nil }

        return negative ? -number : number
    }
}
