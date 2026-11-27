import Foundation
import Testing
@testable import EUVATInfo

private func change(_ id: Int, _ code: String = "DE", type: String = "standard", from old: Double, to new: Double, on date: String?) -> RateChange {
    RateChange(
        id: id,
        country: .init(name: code, code: code, slug: code.lowercased()),
        rateType: type,
        oldRate: old,
        newRate: new,
        changeDate: date.flatMap(APIDate.parse)
    )
}

@MainActor
private func makeStore(_ countries: [Country] = [Fixtures.germany, Fixtures.france, Fixtures.norway]) -> RatesStore {
    RatesStore(api: StubProtocol.client([:]), storage: MemoryStorage(Snapshot(fetchedAt: .now, countries: countries, changes: [])))
}

@Suite("Favourites")
@MainActor
struct FavouritesStoreTests {
    @Test("keeps the chosen countries between launches, in the order of the list")
    func toggles() {
        let defaults = UserDefaults.scratch()
        let favourites = FavouritesStore(defaults: defaults)
        let countries = [Fixtures.germany, Fixtures.france, Fixtures.norway]

        #expect(favourites.countries(among: countries).isEmpty)

        favourites.toggle("NO")
        favourites.toggle("DE")
        #expect(favourites.contains("NO"))
        #expect(favourites.countries(among: countries).map(\.isoCode) == ["DE", "NO"])
        #expect(FavouritesStore(defaults: defaults).codes == ["NO", "DE"])

        favourites.toggle("NO")
        #expect(!favourites.contains("NO"))
        #expect(FavouritesStore(defaults: defaults).codes == ["DE"])
    }
}

@Suite("Rates of one type")
struct RateKindTests {
    @Test("lists a country's rates of each type, lowest first")
    func values() {
        let rates = Fixtures.france.rates

        #expect(rates.values(.standard) == [20])
        #expect(rates.values(.reduced) == [5.5, 10])
        #expect(rates.values(.superReduced) == [2.1])
        #expect(rates.values(.parking).isEmpty)
        #expect(Fixtures.germany.rates.values(.superReduced).isEmpty)
    }
}

@Suite("Calculator shortcuts")
@MainActor
struct CalculatorShortcutTests {
    @Test("opens on a country at one of its rates and a net amount")
    func open() {
        let model = CalculatorModel(defaults: .scratch())
        model.mode = .include

        model.open(Fixtures.france, choice: .reduced(5.5), amount: 1000)

        #expect(model.countryCode == "FR")
        #expect(model.mode == .exclude)
        #expect(model.amount == 1000)
        #expect(model.calculation(for: Fixtures.france)?.gross == 1055)

        model.open(Fixtures.germany)
        #expect(model.countryCode == "DE")
        #expect(model.choice == .standard)
        #expect(model.amount == 1000)
    }

    @Test("recognises a country's own rate and falls back to a custom one")
    func choice() {
        #expect(CalculatorModel.choice(for: 20, in: Fixtures.france) == .standard)
        #expect(CalculatorModel.choice(for: 10, in: Fixtures.france) == .reduced(10))
        #expect(CalculatorModel.choice(for: 2.1, in: Fixtures.france) == .superReduced)
        #expect(CalculatorModel.choice(for: 12.5, in: Fixtures.france) == .custom)
    }

    @Test("keeps finished calculations, newest first, without repeats or empty amounts")
    func remembers() {
        let defaults = UserDefaults.scratch()
        let model = CalculatorModel(defaults: defaults)

        model.remember(VatCalculation(amount: 0, rate: 19, mode: .exclude), in: Fixtures.germany)
        #expect(model.recent.isEmpty)

        model.remember(VatCalculation(amount: 200, rate: 19, mode: .exclude), in: Fixtures.germany)
        model.remember(VatCalculation(amount: 119, rate: 19, mode: .include), in: Fixtures.germany)
        model.remember(VatCalculation(amount: 200, rate: 19, mode: .exclude), in: Fixtures.germany)

        #expect(model.recent.map(\.amount) == [200, 119])
        #expect(model.recent.first?.calculation.gross == 238)
        #expect(CalculatorModel(defaults: defaults).recent.count == 2)

        for amount in 1 ... 12 {
            model.remember(VatCalculation(amount: Double(amount), rate: 20, mode: .exclude), in: Fixtures.france)
        }

        #expect(model.recent.count == 8)
        #expect(model.recent.first?.amount == 12)

        model.clearRecent()
        #expect(CalculatorModel(defaults: defaults).recent.isEmpty)
    }

    @Test("puts an earlier calculation back, custom rates included")
    func restores() {
        let store = makeStore()
        let model = CalculatorModel(defaults: .scratch())

        model.restore(RecentCalculation(countryCode: "FR", amount: 99.5, rate: 10, mode: .include), in: store)
        #expect(model.countryCode == "FR")
        #expect(model.mode == .include)
        #expect(model.amount == 99.5)
        #expect(model.choice == .reduced(10))

        model.restore(RecentCalculation(countryCode: "DE", amount: 50, rate: 12.5, mode: .exclude), in: store)
        #expect(model.choice == .custom)
        #expect(model.rate(for: Fixtures.germany) == 12.5)
        #expect(model.calculation(for: Fixtures.germany)?.vat == 6.25)

        model.restore(RecentCalculation(countryCode: "XX", amount: 1, rate: 1, mode: .exclude), in: store)
        #expect(model.countryCode == "DE")
    }

    @Test("offers the amounts of the website's top calculations")
    func commonAmounts() {
        #expect(CalculatorModel.commonAmounts == [100, 200, 500, 1000, 2500, 5000, 10000])
    }
}

@Suite("Validator shortcuts")
@MainActor
struct ValidatorShortcutTests {
    @Test("takes a pasted number as it comes and reads its country")
    func enter() {
        let store = makeStore()
        let model = ValidatorModel(api: StubProtocol.client([:]), defaults: .scratch())

        model.enter("  fr 12 345.678-901 \n", countries: store.memberStates)
        #expect(model.numberText == "FR12345678901")
        #expect(model.countryCode == "FR")
        #expect(model.detected)

        model.enter(ValidatorModel.exampleNumber, countries: store.memberStates)
        #expect(model.numberText == "LT100019070512")

        model.enter(String(repeating: "9", count: 60), countries: store.memberStates)
        #expect(model.numberText.count == 20)
    }

    @Test("shares what VIES said, without fields the member state did not share")
    func share() {
        let model = ValidatorModel(api: StubProtocol.client([:]), defaults: .scratch())
        let valid = ValidationOutcome(
            kind: .valid,
            number: "DE123456789",
            country: Fixtures.germany,
            result: ValidationResult(valid: true, name: "EXAMPLE GMBH", requestDate: APIDate.parse("2026-11-14"))
        )
        let invalid = ValidationOutcome(kind: .invalid, number: "DE123", country: Fixtures.germany, result: ValidationResult(valid: false, name: "HIDDEN"))

        let lines = model.shareText(valid).split(separator: "\n")
        #expect(lines.count == 4)
        #expect(lines[0] == "DE123456789")
        #expect(lines[2] == "EXAMPLE GMBH")
        #expect(!model.shareText(invalid).contains("HIDDEN"))
    }
}

@Suite("Rate timeline")
struct RateTimelineTests {
    private let now = APIDate.parse("2026-11-14")!

    @Test("draws the standard rate as steps and reads the rate on any date")
    func steps() throws {
        let timeline = try #require(RateTimeline(changes: [
            change(2, from: 19, to: 21, on: "2009-09-01"),
            change(1, from: 18, to: 19, on: "2009-01-01"),
            change(3, type: "reduced", from: 5, to: 9, on: "2012-01-01"),
            change(4, from: 21, to: 22, on: nil),
        ], now: now))

        #expect(timeline.points.map(\.rate) == [18, 19, 21, 21])
        #expect(timeline.points.first.map { $0.date < APIDate.parse("2009-01-01")! } == true)
        #expect(timeline.points.last?.date == now)
        #expect(timeline.rate(at: APIDate.parse("2008-12-31")!) == 18)
        #expect(timeline.rate(at: APIDate.parse("2009-05-01")!) == 19)
        #expect(timeline.rate(at: now) == 21)
        #expect(timeline.since(now) == APIDate.parse("2009-09-01"))
        #expect(timeline.since(APIDate.parse("2008-06-01")!) == nil)
        #expect(timeline.domain == 16 ... 23)
    }

    @Test("runs past today when a change is still to come")
    func upcoming() throws {
        let timeline = try #require(RateTimeline(changes: [change(1, from: 20, to: 22, on: "2027-01-01")], now: now))

        #expect(timeline.rate(at: now) == 20)
        #expect(timeline.since(now) == nil)
        #expect(timeline.points.last.map { $0.date > APIDate.parse("2027-01-01")! } == true)
    }

    @Test("has nothing to draw without a dated change of the standard rate")
    func empty() {
        #expect(RateTimeline(changes: [], now: now) == nil)
        #expect(RateTimeline(changes: [change(1, type: "reduced", from: 5, to: 9, on: "2012-01-01")], now: now) == nil)
        #expect(RateTimeline(changes: [change(1, from: 5, to: 9, on: nil)], now: now) == nil)
    }
}

@Suite("Changes by year")
struct ChangesByYearTests {
    @Test("counts increases and decreases per year, oldest first")
    func counts() {
        let summary = ChangesByYear(changes: [
            change(1, from: 19, to: 21, on: "2025-08-01"),
            change(2, from: 22, to: 24, on: "2025-07-01"),
            change(3, from: 21, to: 20, on: "2025-01-01"),
            change(4, from: 20, to: 22, on: "2010-06-15"),
            change(5, from: 20, to: 22, on: nil),
        ])

        #expect(summary.years.map(\.year) == [2010, 2025])
        #expect(summary.years.last?.increases == 2)
        #expect(summary.years.last?.decreases == 1)
        #expect(summary.increases == 3)
        #expect(summary.decreases == 1)
        #expect(ChangesByYear.calendar.component(.year, from: ChangesByYear.start(of: 2010)) == 2010)
        #expect(ChangesByYear(changes: []).years.isEmpty)
    }
}

@Suite("Numbers for fields and tables")
struct FieldFormatTests {
    @Test("writes amounts for keys, tables and fields")
    func formats() {
        let english = Locale(identifier: "en_US")
        let german = Locale(identifier: "de_DE")

        #expect(Format.amount(1000, locale: english) == "1,000")
        #expect(Format.amount(2500, locale: german) == "2.500")
        #expect(Format.wholeMoney(5000, currency: "EUR", locale: english) == "€5,000")
        #expect(Format.fieldValue(1000, locale: english) == "1000")
        #expect(Format.fieldValue(99.5, locale: german) == "99,5")
        #expect(AmountParser.parse(Format.fieldValue(1234.56, locale: german), locale: german) == 1234.56)
    }
}
