import Foundation
import Testing
import UIKit
@testable import EUVATInfo

@Suite("API responses")
struct DecodingTests {
    @Test("reads countries with every rate type")
    func countries() async throws {
        let countries = try await StubProtocol.client(["/api/v1/countries": .init(body: Fixtures.countriesJSON)]).countries()
        let germany = try #require(countries.first)
        let norway = try #require(countries.last)

        #expect(germany.isoCode == "DE")
        #expect(germany.rates.standard == 19)
        #expect(germany.rates.reducedRates == [7])
        #expect(germany.vatNumber?.pattern == #"^DE\d{9}$"#)
        #expect(germany.isEUMember)
        #expect(germany.lastUpdated != nil)
        #expect(norway.rates.reducedRates == [12, 15])
        #expect(norway.vatNumber == nil)
        #expect(!norway.isEUMember)
    }

    @Test("reads rate changes, with or without a date")
    func changes() async throws {
        let changes = try await StubProtocol.client(["/api/vat-changes": .init(body: Fixtures.changesJSON)]).changes()

        #expect(changes.count == 2)
        #expect(changes[0].direction == .increase)
        #expect(changes[0].points == 2)
        #expect(changes[0].kind == .standard)
        #expect(changes[0].changeDate == APIDate.parse("2025-08-01"))
        #expect(!changes[0].isUpcoming())
        #expect(changes[1].direction == .decrease)
        #expect(changes[1].kind == .reduced)
        #expect(changes[1].changeDate == nil)
    }

    @Test("reads the date shapes the API writes", arguments: [
        "2026-10-11T14:32:02+00:00",
        "2026-11-14T11:18:20.866Z",
        "2026-11-01T10:00:00.000000Z",
        "2025-08-01",
    ])
    func dates(text: String) {
        #expect(APIDate.parse(text) != nil)
    }

    @Test("does not read a date out of something else")
    func notADate() {
        #expect(APIDate.parse("soon") == nil)
        #expect(APIDate.parse("") == nil)
    }

    @Test("reads a VIES answer and treats blank fields as missing")
    func validation() async throws {
        let result = try await StubProtocol.client(["/api/v1/validate": .init(body: Fixtures.validJSON)]).validate(prefix: "DE", number: "DE123456789")

        #expect(result.valid)
        #expect(result.name == "EXAMPLE GMBH")
        #expect(result.address == nil)
        #expect(result.requestDate != nil)
        #expect(!result.isFailure)
    }

    @Test("tells an outage from an invalid number")
    func failure() throws {
        struct Envelope: Decodable { let data: ValidationResult }

        let outage = try JSONDecoder.api.decode(Envelope.self, from: Fixtures.unavailableJSON).data
        let rejected = try JSONDecoder.api.decode(Envelope.self, from: Fixtures.rejectedJSON).data

        #expect(outage.isFailure && !outage.isFormatRejection)
        #expect(rejected.isFailure && rejected.isFormatRejection)
    }

    @Test("maps HTTP statuses to errors", arguments: [(429, APIError.rateLimited), (422, .rejected), (500, .server(status: 500))])
    func statuses(status: Int, expected: APIError) async {
        let api = StubProtocol.client(["/api/v1/countries": .init(status: status)])

        await #expect(throws: expected) { try await api.countries() }
    }

    @Test("reports an unreachable server and an unreadable answer")
    func unreachable() async {
        await #expect(throws: APIError.unreachable) { try await StubProtocol.client([:]).countries() }
        await #expect(throws: APIError.unreadable) {
            try await StubProtocol.client(["/api/v1/countries": .init(body: Data("<html>".utf8))]).countries()
        }
    }
}

@Suite("Shipped data")
struct SeedTests {
    @Test("ships every member state with a standard rate and a VAT number format")
    func seed() throws {
        let snapshot = try #require(DiskSnapshotStorage(fileURL: URL(filePath: "/nonexistent/snapshot.json")).load())
        let members = snapshot.countries.filter(\.isEUMember)

        #expect(Set(members.map(\.isoCode)) == Country.memberStates)
        #expect(members.allSatisfy { $0.rates.standard > 0 && $0.vatNumber != nil })
        #expect(snapshot.countries.allSatisfy { !$0.slug.isEmpty && $0.currency.count == 3 })
        #expect(!snapshot.changes.isEmpty)
        #expect(snapshot.fetchedAt > Date(timeIntervalSince1970: 1_700_000_000))
    }

    @Test("has a flag for every country it ships")
    func flags() throws {
        let snapshot = try #require(DiskSnapshotStorage(fileURL: URL(filePath: "/nonexistent/snapshot.json")).load())

        for country in snapshot.countries {
            #expect(UIImage(named: "flag-\(country.isoCode.lowercased())") != nil, "No flag for \(country.isoCode)")
        }
    }

    @Test("prefers whichever of the stored and shipped snapshots is newer")
    func newerWins() throws {
        let directory = URL.temporaryDirectory.appending(path: UUID().uuidString)
        let storage = DiskSnapshotStorage(fileURL: directory.appending(path: "snapshot.json"))
        let seed = try #require(storage.load())

        storage.save(Snapshot(fetchedAt: seed.fetchedAt.addingTimeInterval(60), countries: [Fixtures.germany], changes: []))
        #expect(storage.load()?.countries.map(\.isoCode) == ["DE"])

        storage.save(Snapshot(fetchedAt: seed.fetchedAt.addingTimeInterval(-60), countries: [Fixtures.germany], changes: []))
        #expect(storage.load()?.countries.count == seed.countries.count)

        try? FileManager.default.removeItem(at: directory)
    }
}

@Suite("Rates store")
@MainActor
struct RatesStoreTests {
    private let stored = Snapshot(fetchedAt: Date(timeIntervalSince1970: 1_750_000_000), countries: [Fixtures.france], changes: [])

    @Test("shows stored rates at once and replaces them after a refresh")
    func refresh() async {
        let storage = MemoryStorage(stored)
        let api = StubProtocol.client([
            "/api/v1/countries": .init(body: Fixtures.countriesJSON),
            "/api/vat-changes": .init(body: Fixtures.changesJSON),
        ])
        let store = RatesStore(api: api, storage: storage)

        #expect(store.countries.map(\.isoCode) == ["FR"])

        await store.refresh()

        #expect(Set(store.countries.map(\.isoCode)) == ["DE", "NO"])
        #expect(store.memberStates.map(\.isoCode) == ["DE"])
        #expect(store.otherCountries.map(\.isoCode) == ["NO"])
        #expect(store.changes.first?.country.code == "RO")
        #expect(store.changes(for: Fixtures.germany).count == 1)
        #expect(!store.refreshFailed)
        #expect(storage.saved?.countries.count == 2)
        #expect(store.fetchedAt.map { $0 > stored.fetchedAt } == true)
    }

    @Test("keeps the stored rates when the refresh fails")
    func failure() async {
        let storage = MemoryStorage(stored)
        let store = RatesStore(api: StubProtocol.client([:]), storage: storage)

        await store.refresh()

        #expect(store.countries.map(\.isoCode) == ["FR"])
        #expect(store.refreshFailed)
        #expect(store.fetchedAt == stored.fetchedAt)
        #expect(storage.saved?.countries.count == 1)
    }

    @Test("does not replace rates with an empty answer")
    func emptyAnswer() async {
        let api = StubProtocol.client([
            "/api/v1/countries": .init(body: Data(#"{"data":[]}"#.utf8)),
            "/api/vat-changes": .init(body: Data("[]".utf8)),
        ])
        let store = RatesStore(api: api, storage: MemoryStorage(stored))

        await store.refresh()

        #expect(store.countries.map(\.isoCode) == ["FR"])
        #expect(store.refreshFailed)
    }

    @Test("refreshes only when the data is stale")
    func staleness() async {
        let fresh = Snapshot(fetchedAt: .now, countries: [Fixtures.france], changes: [])
        let api = StubProtocol.client([
            "/api/v1/countries": .init(body: Fixtures.countriesJSON),
            "/api/vat-changes": .init(body: Fixtures.changesJSON),
        ])

        let upToDate = RatesStore(api: api, storage: MemoryStorage(fresh))
        await upToDate.refreshIfStale()
        #expect(upToDate.countries.map(\.isoCode) == ["FR"])

        let stale = RatesStore(api: api, storage: MemoryStorage(stored))
        await stale.refreshIfStale()
        #expect(stale.countries.count == 2)
    }
}
