import Foundation
import Observation

/// Which of a country's rates the calculation uses.
enum RateChoice: Hashable, Sendable {
    case standard
    case reduced(Double)
    case superReduced
    case parking
    case custom
}

struct RateOption: Identifiable, Hashable {
    let choice: RateChoice
    let label: String

    var id: RateChoice { choice }
}

/// What is typed into the calculator. The figures come from `VatCalculation`.
@MainActor
@Observable
final class CalculatorModel {
    var countryCode: String? {
        didSet { defaults.set(countryCode, forKey: Self.countryKey) }
    }

    var mode: VatMode = .exclude
    var amountText = "100"
    var choice: RateChoice = .standard
    var customRateText = ""

    @ObservationIgnored private let defaults: UserDefaults
    private static let countryKey = "calculator.country"

    init(defaults: UserDefaults = .standard) {
        self.defaults = defaults
        countryCode = defaults.string(forKey: Self.countryKey)
    }

    /// The chosen country, or the person's own when it is one the app knows, or the first member state.
    func country(in store: RatesStore) -> Country? {
        countryCode.flatMap(store.country(iso:))
            ?? Locale.autoupdatingCurrent.region.flatMap { store.country(iso: $0.identifier) }
            ?? store.memberStates.first
            ?? store.countries.first
    }

    func options(for country: Country) -> [RateOption] {
        var options = [RateOption(choice: .standard, label: L10n.calculatorStandardRateLabel(rate: Format.rateNumber(country.rates.standard)))]

        options += country.rates.reducedRates.map {
            RateOption(choice: .reduced($0), label: L10n.calculatorReducedRateLabel(rate: Format.rateNumber($0)))
        }

        if let superReduced = country.rates.superReduced {
            options.append(RateOption(choice: .superReduced, label: L10n.calculatorSuperReducedRateLabel(rate: Format.rateNumber(superReduced))))
        }

        if let parking = country.rates.parking {
            options.append(RateOption(choice: .parking, label: L10n.calculatorParkingRateLabel(rate: Format.rateNumber(parking))))
        }

        return options + [RateOption(choice: .custom, label: L10n.calculatorCustomLabel)]
    }

    /// The choice as it applies to this country: a rate the country does not have falls back to the standard rate.
    func effectiveChoice(for country: Country) -> RateChoice {
        switch choice {
        case let .reduced(rate) where !country.rates.reducedRates.contains(rate): .standard
        case .superReduced where country.rates.superReduced == nil: .standard
        case .parking where country.rates.parking == nil: .standard
        default: choice
        }
    }

    /// The rate in percent, or nil while the custom field does not hold a rate from 0 to 100.
    func rate(for country: Country) -> Double? {
        switch effectiveChoice(for: country) {
        case .standard: country.rates.standard
        case let .reduced(rate): rate
        case .superReduced: country.rates.superReduced
        case .parking: country.rates.parking
        case .custom: AmountParser.parse(customRateText).flatMap { (0 ... 100).contains($0) ? $0 : nil }
        }
    }

    /// The amount typed, or nil when it is not a number the calculator accepts.
    var amount: Double? {
        AmountParser.parse(amountText).flatMap { $0 >= 0 && $0 <= VatCalculation.maxAmount ? $0 : nil }
    }

    var amountIsInvalid: Bool {
        !amountText.trimmingCharacters(in: .whitespaces).isEmpty && amount == nil
    }

    var customRateIsInvalid: Bool {
        choice == .custom && !customRateText.trimmingCharacters(in: .whitespaces).isEmpty
            && AmountParser.parse(customRateText).map { !(0 ... 100).contains($0) } ?? true
    }

    func calculation(for country: Country) -> VatCalculation? {
        guard let rate = rate(for: country), !amountIsInvalid else { return nil }

        return VatCalculation(amount: amount ?? 0, rate: rate, mode: mode)
    }

    /// One sentence that says what was calculated: "€100.00 plus 21% VAT in Lithuania".
    func summary(_ calculation: VatCalculation, in country: Country) -> String {
        let amount = Format.money(calculation.input, currency: country.currency)
        let rate = Format.percent(calculation.rate)

        return calculation.mode == .exclude
            ? L10n.calculatorSummaryAdd(amount: amount, rate: rate, country: country.localizedName)
            : L10n.calculatorSummaryRemove(amount: amount, rate: rate, country: country.localizedName)
    }

    /// The calculation as text for the clipboard and the share sheet, ending with its page on the website.
    func shareText(_ calculation: VatCalculation, in country: Country) -> String {
        [
            summary(calculation, in: country),
            "\(L10n.calculatorNetAmount): \(Format.money(calculation.net, currency: country.currency))",
            "\(L10n.calculatorVatLabel(rate: Format.rateNumber(calculation.rate))): \(Format.money(calculation.vat, currency: country.currency))",
            "\(L10n.calculatorTotalInclVat): \(Format.money(calculation.gross, currency: country.currency))",
            AppLinks.calculation(calculation, in: country).absoluteString,
        ].joined(separator: "\n")
    }
}
