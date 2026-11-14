import Foundation

/// Everything the app needs to work without a connection, with the moment it was fetched.
struct Snapshot: Codable, Sendable {
    var fetchedAt: Date
    var countries: [Country]
    var changes: [RateChange]

    static let empty = Snapshot(fetchedAt: .distantPast, countries: [], changes: [])
}

/// Where the last snapshot is kept between launches.
protocol SnapshotStorage: Sendable {
    func load() -> Snapshot?
    func save(_ snapshot: Snapshot)
}

/// The snapshot in Application Support, with the one shipped in the app as the fallback.
struct DiskSnapshotStorage: SnapshotStorage {
    var fileURL: URL = URL.applicationSupportDirectory.appending(path: "snapshot.json")
    var seedURL: URL? = Bundle.main.url(forResource: "snapshot", withExtension: "json")

    func load() -> Snapshot? {
        let stored = read(fileURL)
        let seed = seedURL.flatMap(read)

        // An update of the app can ship newer rates than the last fetch on this device.
        switch (stored, seed) {
        case let (stored?, seed?): return stored.fetchedAt >= seed.fetchedAt ? stored : seed
        case let (stored?, nil): return stored
        case let (nil, seed): return seed
        }
    }

    func save(_ snapshot: Snapshot) {
        do {
            try FileManager.default.createDirectory(at: fileURL.deletingLastPathComponent(), withIntermediateDirectories: true)
            try JSONEncoder.api.encode(snapshot).write(to: fileURL, options: .atomic)
        } catch {
            // The next refresh writes it again; the snapshot in memory keeps the app working until then.
        }
    }

    private func read(_ url: URL) -> Snapshot? {
        guard let data = try? Data(contentsOf: url) else { return nil }

        return try? JSONDecoder.api.decode(Snapshot.self, from: data)
    }
}
