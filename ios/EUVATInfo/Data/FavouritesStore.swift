import Foundation
import Observation

/// The countries a person keeps at the top of every list, kept on the device.
@MainActor
@Observable
final class FavouritesStore {
    private(set) var codes: [String] {
        didSet { defaults.set(codes, forKey: Self.key) }
    }

    @ObservationIgnored private let defaults: UserDefaults
    private static let key = "favourites"

    init(defaults: UserDefaults = .app) {
        self.defaults = defaults
        codes = defaults.stringArray(forKey: Self.key) ?? []
    }

    func contains(_ code: String) -> Bool {
        codes.contains(code)
    }

    func toggle(_ code: String) {
        if contains(code) {
            codes.removeAll { $0 == code }
        } else {
            codes.append(code)
        }
    }

    /// The favourites the app has rates for, in the order of `countries`.
    func countries(among countries: [Country]) -> [Country] {
        countries.filter { contains($0.isoCode) }
    }
}
