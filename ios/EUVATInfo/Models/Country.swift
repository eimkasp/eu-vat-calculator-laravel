import Foundation

/// A country as `/api/v1/countries` returns it.
struct Country: Codable, Identifiable, Hashable, Sendable {
    struct Rates: Codable, Hashable, Sendable {
        let standard: Double
        /// Every reduced rate, lowest first.
        let reducedRates: [Double]
        let superReduced: Double?
        let parking: Double?

        init(standard: Double, reducedRates: [Double] = [], superReduced: Double? = nil, parking: Double? = nil) {
            self.standard = standard
            self.reducedRates = reducedRates
            self.superReduced = superReduced
            self.parking = parking
        }

        init(from decoder: Decoder) throws {
            let container = try decoder.container(keyedBy: CodingKeys.self)
            standard = try container.decode(Double.self, forKey: .standard)
            superReduced = try container.decodeIfPresent(Double.self, forKey: .superReduced)
            parking = try container.decodeIfPresent(Double.self, forKey: .parking)

            let list = try container.decodeIfPresent([Double].self, forKey: .reducedRates) ?? []
            let single = try container.decodeIfPresent(Double.self, forKey: .reduced)
            reducedRates = list.isEmpty ? [single].compactMap { $0 } : list.sorted()
        }

        func encode(to encoder: Encoder) throws {
            var container = encoder.container(keyedBy: CodingKeys.self)
            try container.encode(standard, forKey: .standard)
            try container.encode(reducedRates, forKey: .reducedRates)
            try container.encodeIfPresent(superReduced, forKey: .superReduced)
            try container.encodeIfPresent(parking, forKey: .parking)
        }

        private enum CodingKeys: String, CodingKey {
            case standard, reduced, reducedRates, superReduced, parking
        }

        /// The rates of one type, lowest first; empty when the country has none of that type.
        func values(_ kind: RateKind) -> [Double] {
            switch kind {
            case .standard: [standard]
            case .reduced: reducedRates
            case .superReduced: [superReduced].compactMap { $0 }
            case .parking: [parking].compactMap { $0 }
            }
        }
    }

    /// The shape of the country's VAT numbers in the Commission's notation; nil outside the EU VAT system.
    struct VatNumber: Codable, Hashable, Sendable {
        /// EL for Greece, otherwise the ISO code.
        let prefix: String
        let format: String
        let pattern: String

        /// The shapes a number can have, one per entry. A range of lengths stays one entry.
        var masks: [String] {
            format.components(separatedBy: " · ").map { $0.trimmingCharacters(in: .whitespaces) }.filter { !$0.isEmpty }
        }
    }

    /// ISO 3166-1 alpha-2.
    let isoCode: String
    /// The English name, used when the system has no name for the region.
    let name: String
    let slug: String
    let currency: String
    let rates: Rates
    let vatNumber: VatNumber?
    let lastUpdated: Date?

    var id: String { isoCode }

    /// VIES and the Commission's rates cover member states only.
    var isEUMember: Bool { vatNumber != nil || Self.memberStates.contains(isoCode) }

    var localizedName: String {
        AppLocale.current.localizedString(forRegionCode: isoCode) ?? name
    }

    /// What the search field matches: the name in the app's language and in English, the code and the VAT prefix.
    var searchText: String {
        [localizedName, name, isoCode, vatNumber?.prefix ?? ""].joined(separator: " ").searchNormalized
    }

    static let memberStates: Set<String> = [
        "AT", "BE", "BG", "CY", "CZ", "DE", "DK", "EE", "ES", "FI", "FR", "GR", "HR", "HU",
        "IE", "IT", "LT", "LU", "LV", "MT", "NL", "PL", "PT", "RO", "SE", "SI", "SK",
    ]
}

extension Sequence where Element == Country {
    func sortedByName() -> [Country] {
        sorted { $0.localizedName.compare($1.localizedName, locale: AppLocale.current) == .orderedAscending }
    }
}

extension String {
    /// Lower case without diacritics, so "espana" finds España.
    var searchNormalized: String {
        folding(options: [.diacriticInsensitive, .caseInsensitive], locale: nil)
            .trimmingCharacters(in: .whitespacesAndNewlines)
    }
}
