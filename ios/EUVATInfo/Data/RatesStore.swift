import Foundation
import Observation

/// The rates and their history: read from the device first, then refreshed from the API.
@MainActor
@Observable
final class RatesStore {
    private(set) var countries: [Country] = []
    private(set) var changes: [RateChange] = []
    /// When the data on screen was fetched; nil when there is none.
    private(set) var fetchedAt: Date?
    private(set) var isRefreshing = false
    /// The last refresh failed, so the data on screen may be behind.
    private(set) var refreshFailed = false

    /// The rate dataset is synced weekly and the API caches for ten minutes, so a few hours is fresh enough.
    static let staleAfter: TimeInterval = 6 * 60 * 60

    private let api: APIClient
    private let storage: any SnapshotStorage

    init(api: APIClient = APIClient(), storage: any SnapshotStorage = DiskSnapshotStorage()) {
        self.api = api
        self.storage = storage

        if let snapshot = storage.load() {
            apply(snapshot)
        }
    }

    var isEmpty: Bool { countries.isEmpty }

    var memberStates: [Country] { countries.filter(\.isEUMember) }

    var otherCountries: [Country] { countries.filter { !$0.isEUMember } }

    func country(iso: String) -> Country? {
        countries.first { $0.isoCode == iso }
    }

    func changes(for country: Country) -> [RateChange] {
        changes.filter { $0.country.code == country.isoCode }
    }

    func refreshIfStale() async {
        guard fetchedAt.map({ Date.now.timeIntervalSince($0) > Self.staleAfter }) ?? true else { return }

        await refresh()
    }

    func refresh() async {
        guard !isRefreshing else { return }

        isRefreshing = true
        defer { isRefreshing = false }

        do {
            async let countries = api.countries()
            async let changes = api.changes()
            let snapshot = Snapshot(fetchedAt: .now, countries: try await countries, changes: try await changes)

            // An empty answer is a broken one: keep what is on screen.
            guard !snapshot.countries.isEmpty else {
                refreshFailed = true
                return
            }

            apply(snapshot)
            refreshFailed = false
            storage.save(snapshot)
        } catch is CancellationError {
            // Leaving the screen is not a failure.
        } catch {
            refreshFailed = true
        }
    }

    private func apply(_ snapshot: Snapshot) {
        countries = snapshot.countries.sortedByName()
        changes = snapshot.changes.sorted { ($0.changeDate ?? .distantPast) > ($1.changeDate ?? .distantPast) }
        fetchedAt = snapshot.fetchedAt
    }
}
