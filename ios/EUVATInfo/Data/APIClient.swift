import Foundation

enum APIError: Error, Equatable {
    /// No connection, or the server could not be reached in time.
    case unreachable
    /// HTTP 429: the VIES endpoint allows 20 checks a minute.
    case rateLimited
    /// HTTP 422: the server refused the input.
    case rejected
    case server(status: Int)
    case unreadable
}

/// The public JSON API of EU VAT Info. No key, no account.
struct APIClient: Sendable {
    var baseURL: URL = AppLinks.site
    var session: URLSession = .api

    func countries() async throws -> [Country] {
        try await get("api/v1/countries", as: Envelope<[Country]>.self).data
    }

    func changes() async throws -> [RateChange] {
        try await get("api/vat-changes", as: [RateChange].self)
    }

    /// `prefix` is the VAT prefix (EL for Greece) and `number` the part after it.
    func validate(prefix: String, number: String) async throws -> ValidationResult {
        var request = URLRequest(url: baseURL.appending(path: "api/v1/validate"))
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try JSONEncoder().encode(["country_code": prefix, "vat_number": number])

        return try await send(request, as: Envelope<ValidationResult>.self).data
    }

    private func get<Value: Decodable>(_ path: String, as type: Value.Type) async throws -> Value {
        try await send(URLRequest(url: baseURL.appending(path: path)), as: type)
    }

    private func send<Value: Decodable>(_ request: URLRequest, as type: Value.Type) async throws -> Value {
        var request = request
        request.setValue("application/json", forHTTPHeaderField: "Accept")

        let data: Data
        let response: URLResponse

        do {
            (data, response) = try await session.data(for: request)
        } catch is CancellationError {
            throw CancellationError()
        } catch let error as URLError where error.code == .cancelled {
            throw CancellationError()
        } catch {
            throw APIError.unreachable
        }

        switch (response as? HTTPURLResponse)?.statusCode ?? 0 {
        case 200 ..< 300:
            break
        case 429:
            throw APIError.rateLimited
        case 422:
            throw APIError.rejected
        case let status:
            throw APIError.server(status: status)
        }

        do {
            return try JSONDecoder.api.decode(type, from: data)
        } catch {
            throw APIError.unreadable
        }
    }

    private struct Envelope<Value: Decodable>: Decodable {
        let data: Value
    }
}

extension URLSession {
    /// Short timeouts: the app always has stored rates to show, so it should not wait long for fresh ones.
    static let api: URLSession = {
        let configuration = URLSessionConfiguration.default
        configuration.timeoutIntervalForRequest = 15
        configuration.timeoutIntervalForResource = 30
        configuration.waitsForConnectivity = false
        configuration.requestCachePolicy = .reloadIgnoringLocalCacheData
        configuration.httpAdditionalHeaders = ["User-Agent": "EUVATInfo-iOS/\(AppInfo.version)"]

        return URLSession(configuration: configuration)
    }()
}

extension JSONDecoder {
    /// Snake-case keys and the three date shapes the API writes: with or without fractional seconds, and day only.
    static var api: JSONDecoder {
        let decoder = JSONDecoder()
        decoder.keyDecodingStrategy = .convertFromSnakeCase
        decoder.dateDecodingStrategy = .custom { decoder in
            let text = try decoder.singleValueContainer().decode(String.self)

            guard let date = APIDate.parse(text) else {
                throw DecodingError.dataCorrupted(.init(codingPath: decoder.codingPath, debugDescription: "Unreadable date: \(text)"))
            }

            return date
        }

        return decoder
    }
}

extension JSONEncoder {
    /// Writes what `JSONDecoder.api` reads, for the snapshot on disk.
    static var api: JSONEncoder {
        let encoder = JSONEncoder()
        encoder.keyEncodingStrategy = .convertToSnakeCase
        encoder.dateEncodingStrategy = .iso8601

        return encoder
    }
}

enum APIDate {
    static func parse(_ text: String) -> Date? {
        let text = text.trimmingCharacters(in: .whitespaces)

        if let date = try? Date(text, strategy: .iso8601) {
            return date
        }

        if let date = try? Date(text, strategy: Date.ISO8601FormatStyle(includingFractionalSeconds: true)) {
            return date
        }

        // "2026-11-01T10:00:00.000000Z": drop the fraction, whatever its length.
        let withoutFraction = text.replacing(/\.\d+/, with: "")

        if withoutFraction != text, let date = try? Date(withoutFraction, strategy: .iso8601) {
            return date
        }

        return try? Date(String(text.prefix(10)), strategy: Date.ISO8601FormatStyle().year().month().day())
    }
}
