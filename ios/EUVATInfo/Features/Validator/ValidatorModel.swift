import Foundation
import Observation

/// A check kept on the device so it can be run again.
struct RecentLookup: Codable, Identifiable, Hashable, Sendable {
    /// The whole number, prefix included.
    let number: String
    /// ISO code of the country, for its flag.
    let countryCode: String
    let valid: Bool
    let name: String?
    let checkedAt: Date

    var id: String { number }
}

/// How a check ended.
struct ValidationOutcome: Equatable, Sendable {
    enum Kind: Sendable {
        case valid, invalid
        /// The number has characters a VAT number cannot have, or is too short.
        case invalidFormat
        /// No member state is chosen and the number has no known prefix.
        case unsupportedCountry
        /// VIES refused the number because of its shape.
        case formatRejected
        case rateLimited
        /// VIES or the connection is down; the number may still be valid.
        case unavailable
    }

    let kind: Kind
    var number = ""
    var country: Country?
    var result: ValidationResult?
}

/// The VAT number being checked in VIES, and the checks made before.
@MainActor
@Observable
final class ValidatorModel {
    enum Phase: Equatable {
        case idle, checking
        case done(ValidationOutcome)
    }

    var numberText = ""
    /// ISO code of the member state, chosen by hand or read from the number's prefix.
    private(set) var countryCode: String?
    /// The country came from the prefix of the number rather than from the picker.
    private(set) var detected = false
    private(set) var phase: Phase = .idle
    private(set) var recent: [RecentLookup] = []

    @ObservationIgnored private let api: APIClient
    @ObservationIgnored private let defaults: UserDefaults
    private static let recentKey = "validator.recent"
    private static let recentLimit = 10

    init(api: APIClient = APIClient(), defaults: UserDefaults = .app) {
        self.api = api
        self.defaults = defaults

        if let data = defaults.data(forKey: Self.recentKey), let stored = try? JSONDecoder().decode([RecentLookup].self, from: data) {
            recent = stored
        }
    }

    func country(in store: RatesStore) -> Country? {
        countryCode.flatMap(store.country(iso:))
    }

    /// Picks the member state by hand.
    func choose(_ country: Country) {
        countryCode = country.isoCode
        detected = false
        phase = .idle
    }

    /// Reads the country from the prefix as the number is typed.
    func numberChanged(countries: [Country]) {
        if case .done = phase {
            phase = .idle
        }

        if let match = VatNumberFormat.detect(in: numberText, among: countries) {
            countryCode = match.country.isoCode
            detected = true
        } else if detected, VatNumberFormat.clean(numberText).count < 2 {
            countryCode = nil
            detected = false
        }
    }

    /// A hint that the number does not look like one from the chosen country. It never blocks the check.
    func formatMismatch(for country: Country?) -> Bool {
        guard let format = country?.vatNumber else { return false }

        let national = VatNumberFormat.national(numberText, for: format)

        return national.count >= 4 && !VatNumberFormat.matches(format, number: format.prefix + national)
    }

    var canValidate: Bool {
        phase != .checking && VatNumberFormat.clean(numberText).count >= 4
    }

    func validate(in store: RatesStore) async {
        let cleaned = VatNumberFormat.clean(numberText)

        guard cleaned.count >= 4, cleaned.allSatisfy({ $0.isASCII && ($0.isLetter || $0.isNumber) }) else {
            phase = .done(ValidationOutcome(kind: .invalidFormat))
            return
        }

        guard let country = country(in: store), let format = country.vatNumber else {
            phase = .done(ValidationOutcome(kind: .unsupportedCountry))
            return
        }

        let number = format.prefix + VatNumberFormat.national(cleaned, for: format)
        phase = .checking

        do {
            let result = try await api.validate(prefix: format.prefix, number: number)
            let kind: ValidationOutcome.Kind = result.isFormatRejection ? .formatRejected : result.isFailure ? .unavailable : result.valid ? .valid : .invalid
            phase = .done(ValidationOutcome(kind: kind, number: number, country: country, result: result))

            if kind == .valid || kind == .invalid {
                remember(RecentLookup(number: number, countryCode: country.isoCode, valid: result.valid, name: result.name, checkedAt: .now))
            }
        } catch APIError.rateLimited {
            phase = .done(ValidationOutcome(kind: .rateLimited, number: number, country: country))
        } catch APIError.rejected {
            phase = .done(ValidationOutcome(kind: .formatRejected, number: number, country: country))
        } catch is CancellationError {
            phase = .idle
        } catch {
            phase = .done(ValidationOutcome(kind: .unavailable, number: number, country: country))
        }
    }

    /// The number the website offers as an example (`vies_page.try_example`).
    static let exampleNumber = "LT100019070512"

    /// Fills the field from the clipboard or an example and reads the country from the prefix.
    func enter(_ number: String, countries: [Country]) {
        numberText = String(VatNumberFormat.clean(number).prefix(20))
        numberChanged(countries: countries)
    }

    /// A finished check as text for the share sheet.
    func shareText(_ outcome: ValidationOutcome) -> String {
        var lines = [outcome.number, outcome.kind == .valid ? L10n.viesPageResultValid : L10n.viesPageResultInvalid]

        if outcome.kind == .valid, let result = outcome.result {
            lines += [result.name, result.address].compactMap { $0 }
        }

        if let date = outcome.result?.requestDate {
            lines.append(L10n.viesPageCheckedOn(date: Format.date(date)))
        }

        return lines.joined(separator: "\n")
    }

    /// Fills the form with an earlier check.
    func restore(_ lookup: RecentLookup) {
        numberText = lookup.number
        countryCode = lookup.countryCode
        detected = true
        phase = .idle
    }

    func clearRecent() {
        recent = []
        defaults.removeObject(forKey: Self.recentKey)
    }

    private func remember(_ lookup: RecentLookup) {
        recent = Array(([lookup] + recent.filter { $0.number != lookup.number }).prefix(Self.recentLimit))

        if let data = try? JSONEncoder().encode(recent) {
            defaults.set(data, forKey: Self.recentKey)
        }
    }
}
