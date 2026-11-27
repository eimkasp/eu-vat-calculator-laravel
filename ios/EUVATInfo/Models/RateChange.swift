import Foundation

/// One recorded change of a VAT rate, as `/api/vat-changes` returns it.
struct RateChange: Codable, Identifiable, Hashable, Sendable {
    struct CountryReference: Codable, Hashable, Sendable {
        let name: String
        let code: String
        let slug: String
    }

    enum Direction: String, Sendable {
        case increase, decrease
    }

    let id: Int
    let country: CountryReference
    let rateType: String
    let oldRate: Double
    let newRate: Double
    let changeDate: Date?

    var kind: RateKind { RateKind(rawValue: rateType) ?? .standard }

    /// Worked out from the rates, so it never disagrees with the figures shown beside it.
    var direction: Direction { newRate >= oldRate ? .increase : .decrease }

    /// The size of the change in percentage points, always positive.
    var points: Double { abs(newRate - oldRate) }

    func isUpcoming(at date: Date = .now) -> Bool {
        changeDate.map { $0 > date } ?? false
    }
}

/// The rate types the API knows.
enum RateKind: String, CaseIterable, Sendable {
    case standard
    case reduced
    case superReduced = "super_reduced"
    case parking

    var title: String {
        switch self {
        case .standard: L10n.historyStandardRate
        case .reduced: L10n.historyReducedRate
        case .superReduced: L10n.historySuperReducedRate
        case .parking: L10n.historyParkingRate
        }
    }
}
