import Foundation
import Testing
@testable import EUVATInfo

@MainActor
private func makeStore(_ countries: [Country] = [Fixtures.germany, Fixtures.france, Fixtures.norway]) -> RatesStore {
    RatesStore(api: StubProtocol.client([:]), storage: MemoryStorage(Snapshot(fetchedAt: .now, countries: countries, changes: [])))
}

@Suite("Calculator")
@MainActor
struct CalculatorModelTests {
    @Test("offers every rate the country has, and a custom one")
    func options() {
        let model = CalculatorModel(defaults: .scratch())

        #expect(model.options(for: Fixtures.france).map(\.choice) == [.standard, .reduced(5.5), .reduced(10), .superReduced, .custom])
        #expect(model.options(for: Fixtures.germany).map(\.choice) == [.standard, .reduced(7), .custom])
    }

    @Test("falls back to the standard rate when the country lacks the chosen one")
    func fallback() {
        let model = CalculatorModel(defaults: .scratch())
        model.choice = .superReduced

        #expect(model.rate(for: Fixtures.france) == 2.1)
        #expect(model.effectiveChoice(for: Fixtures.germany) == .standard)
        #expect(model.rate(for: Fixtures.germany) == 19)

        model.choice = .reduced(5.5)
        #expect(model.rate(for: Fixtures.france) == 5.5)
        #expect(model.rate(for: Fixtures.germany) == 19)
    }

    @Test("accepts a custom rate from 0 to 100 only")
    func customRate() {
        let model = CalculatorModel(defaults: .scratch())
        model.choice = .custom

        model.customRateText = "12.5"
        #expect(model.rate(for: Fixtures.germany) == 12.5)
        #expect(!model.customRateIsInvalid)

        model.customRateText = "120"
        #expect(model.rate(for: Fixtures.germany) == nil)
        #expect(model.customRateIsInvalid)
        #expect(model.calculation(for: Fixtures.germany) == nil)

        model.customRateText = ""
        #expect(!model.customRateIsInvalid)
    }

    @Test("calculates as the amount is typed and rejects what is not an amount")
    func amount() {
        let model = CalculatorModel(defaults: .scratch())

        model.amountText = "200"
        #expect(model.calculation(for: Fixtures.germany)?.gross == 238)

        model.mode = .include
        model.amountText = "119"
        #expect(model.calculation(for: Fixtures.germany)?.net == 100)

        model.amountText = ""
        #expect(!model.amountIsInvalid)
        #expect(model.calculation(for: Fixtures.germany)?.gross == 0)

        model.amountText = "abc"
        #expect(model.amountIsInvalid)
        #expect(model.calculation(for: Fixtures.germany) == nil)

        model.amountText = "-5"
        #expect(model.amountIsInvalid)
    }

    @Test("remembers the country and shares a link to the calculation")
    func countryAndShare() throws {
        let defaults = UserDefaults.scratch()
        let store = makeStore()
        let model = CalculatorModel(defaults: defaults)

        model.countryCode = "FR"
        #expect(CalculatorModel(defaults: defaults).country(in: store)?.isoCode == "FR")

        model.amountText = "1000"
        let calculation = try #require(model.calculation(for: Fixtures.france))
        let text = model.shareText(calculation, in: Fixtures.france)

        #expect(text.hasSuffix("/vat-calculation/france/1000/20/exclude"))
        #expect(text.split(separator: "\n").count == 5)
    }

    @Test("starts on a country the app knows when none was chosen")
    func defaultCountry() {
        #expect(CalculatorModel(defaults: .scratch()).country(in: makeStore()) != nil)
        #expect(CalculatorModel(defaults: .scratch()).country(in: makeStore([])) == nil)
    }
}

@Suite("Validator")
@MainActor
struct ValidatorModelTests {
    private func makeModel(_ response: StubProtocol.Response?, defaults: UserDefaults = .scratch()) -> ValidatorModel {
        ValidatorModel(api: StubProtocol.client(response.map { ["/api/v1/validate": $0] } ?? [:]), defaults: defaults)
    }

    private func outcome(_ model: ValidatorModel) -> ValidationOutcome? {
        if case let .done(outcome) = model.phase { outcome } else { nil }
    }

    @Test("reads the country from the prefix and keeps a valid check")
    func valid() async {
        let defaults = UserDefaults.scratch()
        let store = makeStore()
        let model = makeModel(.init(body: Fixtures.validJSON), defaults: defaults)

        model.numberText = "de 123 456 789"
        model.numberChanged(countries: store.memberStates)
        #expect(model.countryCode == "DE")
        #expect(model.detected)
        #expect(!model.formatMismatch(for: Fixtures.germany))

        await model.validate(in: store)

        #expect(outcome(model)?.kind == .valid)
        #expect(outcome(model)?.number == "DE123456789")
        #expect(outcome(model)?.result?.name == "EXAMPLE GMBH")
        #expect(model.recent.map(\.number) == ["DE123456789"])
        #expect(ValidatorModel(api: StubProtocol.client([:]), defaults: defaults).recent.count == 1)

        model.clearRecent()
        #expect(ValidatorModel(api: StubProtocol.client([:]), defaults: defaults).recent.isEmpty)
    }

    @Test("reports an invalid number and hints at a wrong shape without blocking")
    func invalid() async {
        let store = makeStore()
        let model = makeModel(.init(body: Fixtures.invalidJSON))

        model.choose(Fixtures.germany)
        model.numberText = "12345"
        #expect(model.formatMismatch(for: Fixtures.germany))
        #expect(model.canValidate)

        await model.validate(in: store)

        #expect(outcome(model)?.kind == .invalid)
        #expect(outcome(model)?.number == "DE12345")
        #expect(model.recent.first?.valid == false)
    }

    @Test("does not call an outage an invalid number", arguments: [
        (StubProtocol.Response(body: Fixtures.unavailableJSON), ValidationOutcome.Kind.unavailable),
        (StubProtocol.Response(body: Fixtures.rejectedJSON), .formatRejected),
        (StubProtocol.Response(status: 429), .rateLimited),
        (StubProtocol.Response(status: 422), .formatRejected),
        (StubProtocol.Response(status: 503), .unavailable),
    ])
    func failures(response: StubProtocol.Response, expected: ValidationOutcome.Kind) async {
        let model = makeModel(response)
        model.choose(Fixtures.germany)
        model.numberText = "123456789"

        await model.validate(in: makeStore())

        #expect(outcome(model)?.kind == expected)
        #expect(model.recent.isEmpty)
    }

    @Test("asks for a member state and for letters and digits before calling VIES")
    func input() async {
        let store = makeStore()
        let model = makeModel(nil)

        model.numberText = "123456789"
        await model.validate(in: store)
        #expect(outcome(model)?.kind == .unsupportedCountry)

        model.choose(Fixtures.germany)
        model.numberText = "1234€6789"
        await model.validate(in: store)
        #expect(outcome(model)?.kind == .invalidFormat)

        model.numberText = "12"
        #expect(!model.canValidate)
    }

    @Test("forgets a detected country when the number is cleared, but not a chosen one")
    func detection() {
        let store = makeStore()
        let model = makeModel(nil)

        model.numberText = "FR12345678901"
        model.numberChanged(countries: store.memberStates)
        #expect(model.countryCode == "FR")

        model.numberText = ""
        model.numberChanged(countries: store.memberStates)
        #expect(model.countryCode == nil)

        model.choose(Fixtures.germany)
        model.numberChanged(countries: store.memberStates)
        #expect(model.countryCode == "DE")
    }
}

@Suite("Comparison")
@MainActor
struct ComparisonModelTests {
    @Test("starts with two countries and holds at most three")
    func limits() {
        let defaults = UserDefaults.scratch()
        let store = makeStore([Fixtures.germany, Fixtures.france, Fixtures.norway, Fixtures.country("IT")])
        let model = ComparisonModel(defaults: defaults)

        #expect(model.countries(in: store).map(\.isoCode) == ["DE", "FR"])

        model.add("NO", in: store)
        model.add("IT", in: store)
        #expect(model.countries(in: store).map(\.isoCode) == ["DE", "FR", "NO"])

        model.replace("FR", with: "IT", in: store)
        model.replace("DE", with: "NO", in: store)
        #expect(model.countries(in: store).map(\.isoCode) == ["DE", "IT", "NO"])

        model.remove("DE", in: store)
        model.remove("IT", in: store)
        #expect(model.countries(in: store).map(\.isoCode) == ["IT", "NO"])
        #expect(ComparisonModel(defaults: defaults).countries(in: store).map(\.isoCode) == ["IT", "NO"])
    }

    @Test("makes room for a country added from its own screen")
    func include() {
        let store = makeStore([Fixtures.germany, Fixtures.france, Fixtures.norway, Fixtures.country("IT")])
        let model = ComparisonModel(defaults: .scratch())

        model.include("NO")
        #expect(model.countries(in: store).map(\.isoCode) == ["NO", "DE"])

        model.include("DE")
        model.include("FR")
        model.include("IT")
        model.include("IT")
        #expect(model.countries(in: store).map(\.isoCode) == ["NO", "DE", "IT"])
    }
}

@Suite("Languages")
struct LanguageTests {
    @Test("ships the website's 24 languages")
    func languages() {
        let shipped = Set(Bundle.main.localizations.filter { $0 != "Base" })

        #expect(shipped.count == 24)
        #expect(shipped.isSuperset(of: ["en", "de", "fr", "lt", "el", "bg", "ga", "mt", "pt-PT"]))
    }

    @Test("fills placeholders in every language", arguments: Bundle.main.localizations.filter { $0 != "Base" })
    func placeholders(language: String) throws {
        let path = try #require(Bundle.main.path(forResource: language, ofType: "lproj"))
        let bundle = try #require(Bundle(path: path))
        let format = bundle.localizedString(forKey: "calculator.vat_label", value: nil, table: nil)
        let text = String(format: format, arguments: ["21"])

        #expect(format != "calculator.vat_label")
        #expect(text.contains("21") && text.contains("%") && !text.contains("$@"))
        #expect(bundle.localizedString(forKey: "app.try_again", value: nil, table: nil) != "app.try_again")
    }
}
