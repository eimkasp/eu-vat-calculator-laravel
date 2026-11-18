import Foundation
@testable import EUVATInfo

enum Fixtures {
    static func country(
        _ iso: String,
        name: String? = nil,
        standard: Double = 20,
        reduced: [Double] = [],
        superReduced: Double? = nil,
        parking: Double? = nil,
        currency: String = "EUR",
        vatNumber: Country.VatNumber? = nil
    ) -> Country {
        Country(
            isoCode: iso,
            name: name ?? iso,
            slug: (name ?? iso).lowercased(),
            currency: currency,
            rates: .init(standard: standard, reducedRates: reduced, superReduced: superReduced, parking: parking),
            vatNumber: vatNumber,
            lastUpdated: nil
        )
    }

    static let germany = country("DE", name: "Germany", standard: 19, reduced: [7], vatNumber: .init(prefix: "DE", format: "DE999999999", pattern: #"^DE\d{9}$"#))
    static let france = country("FR", name: "France", standard: 20, reduced: [5.5, 10], superReduced: 2.1, vatNumber: .init(prefix: "FR", format: "FRXX999999999", pattern: #"^FR[0-9A-Z]{2}\d{9}$"#))
    static let norway = country("NO", name: "Norway", standard: 25, reduced: [12, 15], currency: "NOK")

    static let countriesJSON = Data("""
    {"data":[
      {"name":"Germany","iso_code":"DE","code":"DE","slug":"germany","currency":"EUR",
       "rates":{"standard":19,"reduced":7,"reduced_rates":[7],"super_reduced":null,"parking":null},
       "vat_number":{"prefix":"DE","format":"DE999999999","masks":["DE999999999"],"pattern":"^DE\\\\d{9}$"},
       "last_updated":"2026-10-11T14:32:02+00:00"},
      {"name":"Norway","iso_code":"NO","code":"NO","slug":"norway","currency":"NOK",
       "rates":{"standard":25,"reduced":12,"reduced_rates":[15,12],"super_reduced":null,"parking":null},
       "vat_number":null,"last_updated":null}
    ]}
    """.utf8)

    static let changesJSON = Data("""
    [{"id":106,"country":{"name":"Romania","code":"RO","slug":"romania"},"rate_type":"standard","old_rate":19,"new_rate":21,
      "change_date":"2025-08-01","change_direction":"increase","change_reason":null,"description":"Rate changed"},
     {"id":7,"country":{"name":"Germany","code":"DE","slug":"germany"},"rate_type":"reduced","old_rate":7,"new_rate":5,
      "change_date":null,"change_direction":"decrease","change_reason":null,"description":null}]
    """.utf8)

    static let validJSON = Data("""
    {"data":{"valid":true,"country_code":"DE","vat_number":"123456789","name":"EXAMPLE GMBH","address":" ",
     "request_date":"2026-11-14T11:18:20.866Z","request_identifier":null,"source":"vies_api"}}
    """.utf8)

    static let invalidJSON = Data(#"{"data":{"valid":false,"country_code":"DE","vat_number":"123456789","name":null,"address":null,"request_date":"2026-11-14","source":"cache"}}"#.utf8)

    static let unavailableJSON = Data(#"{"data":{"valid":false,"error":"The VIES service is temporarily unavailable.","error_code":"MS_UNAVAILABLE","source":"error"}}"#.utf8)

    static let rejectedJSON = Data(#"{"data":{"valid":false,"error":"The VAT number format is not valid.","error_code":"INVALID_INPUT","source":"error"}}"#.utf8)
}

/// Answers requests from memory. Every client gets its own host, so tests can run side by side.
final class StubProtocol: URLProtocol, @unchecked Sendable {
    struct Response: Sendable {
        var status = 200
        var body = Data()
    }

    private static let lock = NSLock()
    nonisolated(unsafe) private static var routes: [String: [String: Response]] = [:]

    static func client(_ routes: [String: Response]) -> APIClient {
        let host = "\(UUID().uuidString.lowercased()).test"
        lock.withLock { self.routes[host] = routes }

        let configuration = URLSessionConfiguration.ephemeral
        configuration.protocolClasses = [StubProtocol.self]

        return APIClient(baseURL: URL(string: "https://\(host)")!, session: URLSession(configuration: configuration))
    }

    override class func canInit(with request: URLRequest) -> Bool { true }

    override class func canonicalRequest(for request: URLRequest) -> URLRequest { request }

    override func startLoading() {
        guard let url = request.url, let response = Self.lock.withLock({ Self.routes[url.host() ?? ""]?[url.path()] }) else {
            client?.urlProtocol(self, didFailWithError: URLError(.notConnectedToInternet))
            return
        }

        let head = HTTPURLResponse(url: url, statusCode: response.status, httpVersion: "HTTP/1.1", headerFields: ["Content-Type": "application/json"])!
        client?.urlProtocol(self, didReceive: head, cacheStoragePolicy: .notAllowed)
        client?.urlProtocol(self, didLoad: response.body)
        client?.urlProtocolDidFinishLoading(self)
    }

    override func stopLoading() {}
}

/// A snapshot kept in memory instead of on disk.
final class MemoryStorage: SnapshotStorage, @unchecked Sendable {
    private let lock = NSLock()
    private var snapshot: Snapshot?

    init(_ snapshot: Snapshot? = nil) {
        self.snapshot = snapshot
    }

    var saved: Snapshot? { lock.withLock { snapshot } }

    func load() -> Snapshot? { saved }

    func save(_ snapshot: Snapshot) {
        lock.withLock { self.snapshot = snapshot }
    }
}

extension UserDefaults {
    /// Settings that start empty and are shared with nothing.
    static func scratch() -> UserDefaults {
        UserDefaults(suiteName: "tests.\(UUID().uuidString)")!
    }
}
