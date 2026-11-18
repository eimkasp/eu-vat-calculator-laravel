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

/// A calculation kept on the device so it can be opened again.
struct RecentCalculation: Codable, Identifiable, Hashable, Sendable {
    let countryCode: String
    let amount: Double
    let rate: Double
    let mode: VatMode

    var id: String { "\(countryCode)|\(amount)|\(rate)|\(mode.rawValue)" }

    var calculation: VatCalculation { VatCalculation(amount: amount, rate: rate, mode: mode) }
}

/// What is typed into the calculator. The figures come from `VatCalculation`.
@MainActor
@Observable
final class CalculatorModel {
    /// The amounts of the website's top calculations (`TopCalculations::AMOUNTS`).
    static let commonAmounts: [Double] = [100, 200, 500, 1000, 2500, 5000, 10000]

    var countryCode: String? {
        didSet { defaults.set(countryCode, forKey: Self.countryKey) }
    }

    var mode: VatMode = .exclude
    var amountText = "100"
    var choice: RateChoice = .standard
    var customRateText = ""
    private(set) var recent: [RecentCalculation] = []

    @ObservationIgnored private let defaults: UserDefaults
    private static let countryKey = "calculator.country"
    private static let recentKey = "calculator.recent"
    private static let recentLimit = 8

    init(defaults: UserDefaults = .app) {
        self.defaults = defaults
        countryCode = defaults.string(forKey: Self.countryKey)

        if let data = defaults.data(forKey: Self.recentKey), let stored = try? JSONDecoder().decode([RecentCalculation].self, from: data) {
            recent = stored
        }
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

    /// Puts an amount in the field, written the way the device's number pad writes it.
    func setAmount(_ value: Double) {
        amountText = Format.fieldValue(value)
    }

    /// Opens the calculator on a country, at one of its rates and, when given, a net amount.
    func open(_ country: Country, choice: RateChoice = .standard, amount: Double? = nil) {
        countryCode = country.isoCode
        self.choice = choice

        if let amount {
            mode = .exclude
            setAmount(amount)
        }
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

    // MARK: - Recent calculations

    /// Keeps a finished calculation, newest first. An amount of zero is not worth keeping.
    func remember(_ calculation: VatCalculation, in country: Country) {
        guard calculation.input > 0 else { return }

        let entry = RecentCalculation(countryCode: country.isoCode, amount: calculation.input, rate: calculation.rate, mode: calculation.mode)
        recent = Array(([entry] + recent.filter { $0.id != entry.id }).prefix(Self.recentLimit))
        persistRecent()
    }

    /// Puts an earlier calculation back into the calculator.
    func restore(_ entry: RecentCalculation, in store: RatesStore) {
        guard let country = store.country(iso: entry.countryCode) else { return }

        countryCode = country.isoCode
        mode = entry.mode
        setAmount(entry.amount)
        choice = Self.choice(for: entry.rate, in: country)

        if choice == .custom {
            customRateText = Format.fieldValue(entry.rate)
        }
    }

    func clearRecent() {
        recent = []
        defaults.removeObject(forKey: Self.recentKey)
    }

    /// The country's own rate that a percentage is, or a custom rate when it is none of them.
    static func choice(for rate: Double, in country: Country) -> RateChoice {
        if rate == country.rates.standard {
            .standard
        } else if country.rates.reducedRates.contains(rate) {
            .reduced(rate)
        } else if rate == country.rates.superReduced {
            .superReduced
        } else if rate == country.rates.parking {
            .parking
        } else {
            .custom
        }
    }

    private func persistRecent() {
        if let data = try? JSONEncoder().encode(recent) {
            defaults.set(data, forKey: Self.recentKey)
        }
    }
}
