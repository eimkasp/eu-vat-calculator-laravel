import Foundation

/// Whether the amount typed excludes VAT (add it) or includes VAT (take it out).
enum VatMode: String, CaseIterable, Sendable {
    case exclude
    case include
}

/// A VAT breakdown rounded to the cent. It mirrors `App\Support\Vat\VatCalculation` (PHP) and
/// `resources/js/vat.js`, so the app, the website and the API return the same figures. Keep the three in step.
struct VatCalculation: Equatable, Sendable {
    static let maxAmount: Double = 1_000_000_000_000

    let net: Double
    let vat: Double
    let gross: Double
    let rate: Double
    let mode: VatMode

    init(amount: Double, rate: Double, mode: VatMode) {
        let amount = min(max(amount.isFinite ? amount : 0, 0), Self.maxAmount)
        let rate = min(max(rate.isFinite ? rate : 0, 0), 100)

        self.rate = rate
        self.mode = mode

        switch mode {
        case .include:
            gross = Self.round2(amount)
            net = Self.round2(gross / (1 + rate / 100))
            vat = Self.round2(gross - net)
        case .exclude:
            net = Self.round2(amount)
            vat = Self.round2(net * rate / 100)
            gross = Self.round2(net + vat)
        }
    }

    /// The amount that was typed: net when adding VAT, gross when removing it.
    var input: Double { mode == .include ? gross : net }

    /// The share of the total that is VAT, from 0 to 1.
    var vatShare: Double { gross > 0 ? vat / gross : 0 }

    /// Rounds half away from zero on the decimal value, so 1.005 becomes 1.01 as it does in PHP's `round()`
    /// and in `round2()` of vat.js, not 1.00 as binary floating point would have it.
    static func round2(_ value: Double) -> Double {
        guard value.isFinite else { return 0 }

        // The shortest text that reads back as the same Double is the decimal value the person means.
        guard var decimal = Decimal(string: "\(abs(value))", locale: Locale(identifier: "en_US_POSIX")) else { return 0 }

        var rounded = Decimal()
        NSDecimalRound(&rounded, &decimal, 2, .plain)
        let result = NSDecimalNumber(decimal: rounded).doubleValue

        return value < 0 ? -result : result
    }
}
