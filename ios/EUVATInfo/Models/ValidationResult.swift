import Foundation

/// The answer of `POST /api/v1/validate`.
struct ValidationResult: Codable, Hashable, Sendable {
    let valid: Bool
    let countryCode: String?
    let vatNumber: String?
    let name: String?
    let address: String?
    let requestDate: Date?
    let requestIdentifier: String?
    /// `vies_api`, `cache`, `database`, `database_fallback` or `error`.
    let source: String?
    let errorCode: String?

    /// VIES could not be asked, so `valid` says nothing about the number.
    var isFailure: Bool { source == "error" }

    /// VIES refused the number because of its shape.
    var isFormatRejection: Bool {
        guard isFailure, let errorCode else { return false }

        return errorCode == "INVALID_INPUT" || errorCode.hasPrefix("VOW-ERR")
    }

    init(
        valid: Bool,
        countryCode: String? = nil,
        vatNumber: String? = nil,
        name: String? = nil,
        address: String? = nil,
        requestDate: Date? = nil,
        requestIdentifier: String? = nil,
        source: String? = nil,
        errorCode: String? = nil
    ) {
        self.valid = valid
        self.countryCode = countryCode
        self.vatNumber = vatNumber
        self.name = name
        self.address = address
        self.requestDate = requestDate
        self.requestIdentifier = requestIdentifier
        self.source = source
        self.errorCode = errorCode
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        valid = try container.decodeIfPresent(Bool.self, forKey: .valid) ?? false
        countryCode = try container.decodeIfPresent(String.self, forKey: .countryCode)
        vatNumber = try container.decodeIfPresent(String.self, forKey: .vatNumber)
        name = try container.decodeIfPresent(String.self, forKey: .name)?.nilIfBlank
        address = try container.decodeIfPresent(String.self, forKey: .address)?.nilIfBlank
        requestIdentifier = try container.decodeIfPresent(String.self, forKey: .requestIdentifier)?.nilIfBlank
        source = try container.decodeIfPresent(String.self, forKey: .source)
        errorCode = try container.decodeIfPresent(String.self, forKey: .errorCode)

        // Stored results carry `last_checked_at` instead; a date the app cannot read is not worth failing for.
        let lastChecked = try? container.decodeIfPresent(Date.self, forKey: .lastCheckedAt)
        requestDate = (try? container.decodeIfPresent(Date.self, forKey: .requestDate)) ?? lastChecked
    }

    func encode(to encoder: Encoder) throws {
        var container = encoder.container(keyedBy: CodingKeys.self)
        try container.encode(valid, forKey: .valid)
        try container.encodeIfPresent(countryCode, forKey: .countryCode)
        try container.encodeIfPresent(vatNumber, forKey: .vatNumber)
        try container.encodeIfPresent(name, forKey: .name)
        try container.encodeIfPresent(address, forKey: .address)
        try container.encodeIfPresent(requestDate, forKey: .requestDate)
        try container.encodeIfPresent(requestIdentifier, forKey: .requestIdentifier)
        try container.encodeIfPresent(source, forKey: .source)
        try container.encodeIfPresent(errorCode, forKey: .errorCode)
    }

    private enum CodingKeys: String, CodingKey {
        case valid, countryCode, vatNumber, name, address, requestDate, requestIdentifier, source, errorCode, lastCheckedAt
    }
}

extension String {
    var nilIfBlank: String? {
        let trimmed = trimmingCharacters(in: .whitespacesAndNewlines)

        return trimmed.isEmpty ? nil : trimmed
    }
}
