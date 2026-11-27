import Foundation
import Testing
@testable import EUVATInfo

/// The same cases as tests/Unit/VatCalculationTest.php, so the app and the website agree to the cent.
@Suite("VAT calculation")
struct VatCalculationTests {
    @Test("adds VAT to a net amount", arguments: [
        (100.0, 20.0, 20.0, 120.0),
        (100.0, 9.0, 9.0, 109.0),
        (100.0, 5.0, 5.0, 105.0),
        (100.0, 2.1, 2.1, 102.1),
        (50.0, 19.0, 9.5, 59.5),
        (100_000.0, 21.0, 21000.0, 121_000.0),
        (0.0, 21.0, 0.0, 0.0),
        (100.0, 0.0, 0.0, 100.0),
    ])
    func addsVat(net: Double, rate: Double, vat: Double, gross: Double) {
        let calculation = VatCalculation(amount: net, rate: rate, mode: .exclude)

        #expect(calculation.net == net)
        #expect(calculation.vat == vat)
        #expect(calculation.gross == gross)
        #expect(calculation.input == net)
    }

    @Test("extracts VAT from a gross amount", arguments: [
        (119.0, 19.0, 100.0, 19.0),
        (100.0, 23.0, 81.3, 18.7),
        (121.0, 21.0, 100.0, 21.0),
        (1234.56, 20.0, 1028.8, 205.76),
    ])
    func removesVat(gross: Double, rate: Double, net: Double, vat: Double) {
        let calculation = VatCalculation(amount: gross, rate: rate, mode: .include)

        #expect(calculation.gross == gross)
        #expect(calculation.net == net)
        #expect(calculation.vat == vat)
        #expect(calculation.input == gross)
    }

    @Test("keeps net plus VAT equal to gross after rounding")
    func partsAddUp() {
        for amount in [0.01, 9.99, 17.5, 333.33, 1234.56, 99999.99] {
            for rate in [5, 7, 17, 19, 21, 23, 25.5, 27] {
                let calculation = VatCalculation(amount: amount, rate: rate, mode: .include)

                #expect(VatCalculation.round2(calculation.net + calculation.vat) == calculation.gross)
            }
        }
    }

    @Test("clamps rates and amounts to supported bounds")
    func clamps() {
        #expect(VatCalculation(amount: 100, rate: 150, mode: .exclude).rate == 100)
        #expect(VatCalculation(amount: 100, rate: -5, mode: .exclude).rate == 0)
        #expect(VatCalculation(amount: -50, rate: 20, mode: .exclude).net == 0)
        #expect(VatCalculation(amount: 1e15, rate: 20, mode: .exclude).net == VatCalculation.maxAmount)
        #expect(VatCalculation(amount: .nan, rate: .infinity, mode: .exclude).gross == 0)
    }

    @Test("rounds half away from zero on the decimal value")
    func rounding() {
        #expect(VatCalculation.round2(1.005) == 1.01)
        #expect(VatCalculation.round2(2.675) == 2.68)
        #expect(VatCalculation.round2(-1.005) == -1.01)
        #expect(VatCalculation.round2(0.004) == 0)
        #expect(VatCalculation.round2(1_000_000_000_000) == 1_000_000_000_000)
    }

    @Test("says how much of the total is VAT")
    func share() {
        #expect(VatCalculation(amount: 100, rate: 25, mode: .exclude).vatShare == 0.2)
        #expect(VatCalculation(amount: 0, rate: 25, mode: .exclude).vatShare == 0)
    }
}

/// The same cases as tests/Unit/AmountParserTest.php.
@Suite("Amount parser")
struct AmountParserTests {
    @Test("parses plain and grouped amounts", arguments: [
        ("100", ".", 100.0),
        ("99.99", ".", 99.99),
        ("99,99", ",", 99.99),
        ("1,234.56", ".", 1234.56),
        ("1.234,56", ",", 1234.56),
        ("1.234,56", ".", 1234.56),
        ("1 234,5", ",", 1234.5),
        ("12\u{202F}500,10", ",", 12500.10),
        ("12'500.10", ".", 12500.10),
        ("€ 100", ".", 100.0),
        (".5", ".", 0.5),
        ("1,000,000", ".", 1_000_000.0),
    ])
    func parses(input: String, separator: String, expected: Double) {
        #expect(AmountParser.parse(input, decimalSeparator: separator) == expected)
    }

    @Test("lets the locale resolve a single separator followed by three digits")
    func ambiguousSeparator() {
        #expect(AmountParser.parse("1,234", decimalSeparator: ".") == 1234)
        #expect(AmountParser.parse("1,234", decimalSeparator: ",") == 1.234)
        #expect(AmountParser.parse("1.234", decimalSeparator: ",") == 1234)
        #expect(AmountParser.parse("1.234", decimalSeparator: ".") == 1.234)
        #expect(AmountParser.parse("1,234", locale: Locale(identifier: "de_DE")) == 1.234)
    }

    @Test("keeps the sign of negative amounts so callers can reject them")
    func negative() {
        #expect(AmountParser.parse("-100", decimalSeparator: ".") == -100)
    }

    @Test("rejects input that is not a number", arguments: ["", "   ", "invalid", "1.2.3,4,5", "€", "..", ","])
    func rejects(input: String) {
        #expect(AmountParser.parse(input, decimalSeparator: ".") == nil)
    }
}

@Suite("VAT number format")
struct VatNumberFormatTests {
    private let germany = Country.VatNumber(prefix: "DE", format: "DE999999999", pattern: #"^DE\d{9}$"#)
    private let france = Country.VatNumber(prefix: "FR", format: "FRXX999999999", pattern: #"^FR[0-9A-Z]{2}\d{9}$"#)

    @Test("cleans separators and case")
    func cleans() {
        #expect(VatNumberFormat.clean(" de 123.456-789 ") == "DE123456789")
        #expect(VatNumberFormat.clean("IE 6388047/V") == "IE6388047V")
    }

    @Test("matches a number against the country's pattern")
    func matches() {
        #expect(VatNumberFormat.matches(germany, number: "de 123 456 789"))
        #expect(!VatNumberFormat.matches(germany, number: "DE12345678"))
        #expect(VatNumberFormat.matches(france, number: "FRAB123456789"))
        #expect(!VatNumberFormat.matches(france, number: "FR123"))
    }

    @Test("never blocks on a pattern it cannot read")
    func unreadablePattern() {
        #expect(VatNumberFormat.matches(Country.VatNumber(prefix: "XX", format: "", pattern: "(("), number: "XX1"))
    }

    @Test("splits the national part from the prefix")
    func national() {
        #expect(VatNumberFormat.national("DE123456789", for: germany) == "123456789")
        #expect(VatNumberFormat.national("123 456 789", for: germany) == "123456789")
    }

    @Test("reads the country from the prefix, EL for Greece")
    func detects() {
        let countries = [
            Fixtures.country("DE", vatNumber: germany),
            Fixtures.country("GR", vatNumber: Country.VatNumber(prefix: "EL", format: "EL999999999", pattern: #"^EL\d{9}$"#)),
        ]

        #expect(VatNumberFormat.detect(in: "el 123456789", among: countries)?.country.isoCode == "GR")
        #expect(VatNumberFormat.detect(in: "DE123456789", among: countries)?.national == "123456789")
        #expect(VatNumberFormat.detect(in: "GR123456789", among: countries) == nil)
        #expect(VatNumberFormat.detect(in: "123456789", among: countries) == nil)
        #expect(VatNumberFormat.detect(in: "DE", among: countries) == nil)
    }
}

@Suite("Formatting")
struct FormatTests {
    private let english = Locale(identifier: "en_US")
    private let german = Locale(identifier: "de_DE")

    @Test("writes rates without trailing zeros, the way the language does")
    func percent() {
        #expect(Format.percent(19, locale: english) == "19%")
        #expect(Format.percent(5.5, locale: english) == "5.5%")
        #expect(Format.percent(5.5, locale: german) == "5,5\u{00A0}%")
        #expect(Format.rateNumber(2.1, locale: german) == "2,1")
        #expect(Format.multiplier(5.5, locale: english) == "1.055")
        #expect(Format.multiplier(21, locale: english) == "1.21")
    }

    @Test("writes money with two decimals")
    func money() {
        #expect(Format.money(1234.5, currency: "EUR", locale: english) == "€1,234.50")
        #expect(Format.money(1234.5, currency: "EUR", locale: german) == "1.234,50\u{00A0}€")
    }

    @Test("writes dates day first")
    func date() throws {
        let date = try #require(APIDate.parse("2025-08-01"))

        #expect(Format.date(date, locale: english) == "1 Aug 2025")
    }

    @Test("writes the segments of a shared calculation the way the website does")
    func segments() {
        #expect(AppLinks.segment(1000) == "1000")
        #expect(AppLinks.segment(19) == "19")
        #expect(AppLinks.segment(5.5) == "5.5")
        #expect(AppLinks.segment(99.99) == "99.99")
        #expect(AppLinks.segment(0) == "0")
    }
}
